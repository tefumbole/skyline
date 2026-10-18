<?php

namespace App\Services\Restaurant;

use App\Biller;
use App\Customer;
use App\CustomerGroup;
use App\MealRejection;
use App\OrderReview;
use App\Product;
use App\Product_Sale;
use App\Product_Warehouse;
use App\RestaurantOrder;
use App\RestaurantOrderEvent;
use App\RestaurantOrderItem;
use App\RestaurantTable;
use App\Sale;
use App\Unit;
use App\User;
use App\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class RestaurantOrderService
{
    public function attachFromRequest(Sale $sale, Request $request)
    {
        if (! Schema::hasTable('restaurant_orders')) {
            return null;
        }
        $existing = RestaurantOrder::where('sale_id', $sale->id)->first();
        if ($existing) {
            return $existing;
        }

        $lines = Product_Sale::where('sale_id', $sale->id)->orderBy('id')->get();
        $routed = [];
        foreach ($lines as $index => $line) {
            $department = RestaurantDepartment::forCategoryId($line->category_id);
            if (! $department && $line->product_id) {
                $product = Product::find($line->product_id);
                if ($product) {
                    $department = RestaurantDepartment::forCategoryId($product->category_id);
                }
            }
            if ($department) {
                $routed[] = ['line' => $line, 'department' => $department, 'index' => $index];
            }
        }

        $service = $request->input('service_type');
        if (! $routed && ! in_array($service, ['dine_in', 'take_away'], true)) {
            return null;
        }

        $waitressId = $this->staffId($request->input('waitress_user_id'));
        $comments = (array) $request->input('item_comment', []);

        $order = RestaurantOrder::create([
            'sale_id' => $sale->id,
            'source' => $request->input('order_source') === 'frontend' ? 'frontend' : 'pos',
            'service_type' => $service === 'dine_in' ? 'dine_in' : 'take_away',
            'restaurant_table_id' => $this->tableId($request->input('restaurant_table_id')),
            'placed_by_user_id' => $request->input('order_source') === 'frontend' ? null : (auth()->id() ?: null),
            'waitress_user_id' => $waitressId,
            'customer_comment' => $request->input('customer_comment') ?: $sale->sale_note,
            'internal_note' => $request->input('internal_note') ?: $sale->staff_note,
            'token' => $this->token(),
            'checkout_token' => $request->input('checkout_token') ?: null,
        ]);

        foreach ($routed as $row) {
            $line = $row['line'];
            $product = Product::find($line->product_id);
            $this->makeItem($order, [
                'product_sale_id' => $line->id,
                'product_id' => $line->product_id,
                'product_name' => $product ? $product->name : 'Item',
                'department' => $row['department'],
                'qty' => $line->qty,
                'customer_comment' => isset($comments[$row['index']]) ? $comments[$row['index']] : null,
            ]);
        }

        $this->event($order, null, 'placed', $order->placed_by_user_id, 'Order '.$sale->reference_no.' from '.$order->source);
        $this->notifyReceived($order);

        return $order;
    }

    public function placePublic(array $input)
    {
        $token = isset($input['checkout_token']) ? (string) $input['checkout_token'] : '';
        if ($token !== '') {
            $existing = RestaurantOrder::where('checkout_token', $token)->first();
            if ($existing) {
                return Sale::find($existing->sale_id);
            }
        }

        $items = isset($input['items']) ? $input['items'] : [];
        $lines = [];
        foreach ($items as $item) {
            $qty = isset($item['qty']) ? (float) $item['qty'] : 0;
            if ($qty <= 0 || empty($item['product_id'])) {
                continue;
            }
            $product = Product::where('is_active', true)->find($item['product_id']);
            if (! $product) {
                continue;
            }
            $department = RestaurantDepartment::forCategoryId($product->category_id);
            if (! $department) {
                continue;
            }
            $lines[] = ['product' => $product, 'qty' => $qty, 'comment' => isset($item['comment']) ? $item['comment'] : null, 'department' => $department];
        }
        if (! $lines) {
            throw new \InvalidArgumentException('Choose at least one food or drink.');
        }

        $phone = \App\Support\WhatsAppPhone::normalize($input['phone']);
        $customer = Customer::where('phone_number', $phone)->first();
        if (! $customer) {
            $group = CustomerGroup::query()->orderBy('id')->first();
            $customer = Customer::create([
                'customer_group_id' => $group ? $group->id : 1,
                'name' => $input['name'],
                'phone_number' => $phone,
                'country' => isset($input['country']) ? $input['country'] : null,
                'address' => 'Restaurant',
                'city' => 'Bamenda',
                'is_active' => 1,
            ]);
        } elseif (! empty($input['name'])) {
            $customer->name = $input['name'];
            if (! empty($input['country'])) {
                $customer->country = $input['country'];
            }
            $customer->save();
        }

        $warehouse = Warehouse::query()->orderBy('id')->first();
        $biller = Biller::query()->orderBy('id')->first();
        if (! $warehouse || ! $biller) {
            throw new \RuntimeException('Warehouse or biller is not configured.');
        }

        $totalQty = 0;
        $totalPrice = 0;
        $totalTax = 0;
        foreach ($lines as $line) {
            $priced = $this->priceLine($line['product'], $line['qty']);
            $totalQty += $line['qty'];
            $totalPrice += $priced['net'];
            $totalTax += $priced['tax'];
        }
        $grand = $totalPrice + $totalTax;
        $reference = $this->reference();

        $sale = Sale::create([
            'reference_no' => $reference,
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'biller_id' => $biller->id,
            'user_id' => null,
            'item' => count($lines),
            'total_qty' => $totalQty,
            'total_discount' => 0,
            'total_tax' => $totalTax,
            'total_price' => $totalPrice,
            'order_tax_rate' => 0,
            'order_tax' => 0,
            'order_discount' => 0,
            'shipping_cost' => 0,
            'grand_total' => $grand,
            'sale_status' => 1,
            'payment_status' => 2,
            'paid_amount' => 0,
            'paid_by_id' => 9,
            'sale_note' => isset($input['customer_comment']) ? $input['customer_comment'] : null,
        ]);

        $comments = [];
        foreach ($lines as $line) {
            $priced = $this->priceLine($line['product'], $line['qty']);
            Product_Sale::create([
                'sale_id' => $sale->id,
                'product_id' => $line['product']->id,
                'category_id' => $line['product']->category_id,
                'warehouse_id' => $warehouse->id,
                'qty' => $line['qty'],
                'sale_unit_id' => $line['product']->sale_unit_id ?: 0,
                'net_unit_price' => $priced['unit'],
                'discount' => 0,
                'tax_rate' => $priced['rate'],
                'tax' => $priced['tax'],
                'total' => $priced['net'] + $priced['tax'],
            ]);
            $this->deductStock($line['product'], $line['qty'], $warehouse->id);
            $comments[] = $line['comment'];
        }

        $fake = Request::create('/', 'POST', [
            'service_type' => (isset($input['service_type']) && $input['service_type'] === 'dine_in') ? 'dine_in' : 'take_away',
            'restaurant_table_id' => isset($input['restaurant_table_id']) ? $input['restaurant_table_id'] : null,
            'customer_comment' => isset($input['customer_comment']) ? $input['customer_comment'] : null,
            'order_source' => 'frontend',
            'checkout_token' => $token !== '' ? $token : null,
            'item_comment' => $comments,
        ]);
        $this->attachFromRequest($sale, $fake);

        return $sale->fresh();
    }

    public function accept(RestaurantOrderItem $item, User $user, $minutes)
    {
        $this->assertStatus($item, 'awaiting', 'preparing');
        $minutes = (int) $minutes;
        if ($minutes < 1 || $minutes > 240) {
            throw new \InvalidArgumentException('Enter a preparation time between 1 and 240 minutes.');
        }
        $item->status = 'preparing';
        $item->accepted_by = $user->id;
        $item->accepted_at = now();
        $item->estimate_minutes = $minutes;
        if (! $item->original_estimate_minutes) {
            $item->original_estimate_minutes = $minutes;
        }
        $item->expected_ready_at = now()->addMinutes($minutes);
        $item->save();
        $this->event($item->order, $item, 'accepted', $user->id, $minutes.' min');
        $this->notify($item->order, ucfirst($item->department).' accepted '.$item->product_name.' (x'.$this->qty($item).'). Ready in about '.$minutes.' minutes.');

        return $item;
    }

    public function reviseEstimate(RestaurantOrderItem $item, User $user, $minutes)
    {
        if (! in_array($item->status, ['preparing', 'awaiting'], true)) {
            throw new \InvalidArgumentException('The estimate can be changed while the item is still being prepared.');
        }
        $minutes = (int) $minutes;
        if ($minutes < 1 || $minutes > 240) {
            throw new \InvalidArgumentException('Enter a preparation time between 1 and 240 minutes.');
        }
        if (! $item->original_estimate_minutes) {
            $item->original_estimate_minutes = $item->estimate_minutes ?: $minutes;
        }
        $item->estimate_minutes = $minutes;
        $item->expected_ready_at = now()->addMinutes($minutes);
        $item->save();
        $this->event($item->order, $item, 'estimate_revised', $user->id, $minutes.' min');
        $this->notify($item->order, 'Updated time for '.$item->product_name.': about '.$minutes.' minutes.');

        return $item;
    }

    public function markReady(RestaurantOrderItem $item, User $user)
    {
        $this->assertStatus($item, 'preparing', 'ready');
        $item->status = 'ready';
        $item->ready_by = $user->id;
        $item->ready_at = now();
        $item->save();
        $this->event($item->order, $item, 'ready', $user->id, null);
        $this->notify($item->order, $item->product_name.' is ready. '.$item->order->serviceLabel().'.');

        return $item;
    }

    public function deliver(RestaurantOrderItem $item, User $user)
    {
        $this->assertStatus($item, 'ready', 'delivered');
        $item->status = 'delivered';
        $item->delivered_by = $user->id;
        $item->delivered_at = now();
        $item->save();
        $order = $item->order()->with('items', 'sale.customer')->first();
        $this->event($order, $item, 'delivered', $user->id, null);
        $word = $order->service_type === 'take_away' ? 'handed over' : 'served';
        $this->notify($order, $item->product_name.' has been '.$word.'.');
        if ($order->isFulfilled()) {
            $this->sendReviewLink($order);
        }

        return $item;
    }

    public function reject(RestaurantOrderItem $item, User $user, $qty, $reason, $resolution)
    {
        $allowed = ['remake', 'replacement', 'cancellation', 'price_adjustment', 'refund'];
        if (! in_array($resolution, $allowed, true)) {
            throw new \InvalidArgumentException('Choose a resolution.');
        }
        $qty = (float) $qty;
        if ($qty <= 0 || $qty > (float) $item->qty) {
            throw new \InvalidArgumentException('Quantity must be between 1 and the served amount.');
        }
        $reason = trim((string) $reason);
        if ($reason === '') {
            throw new \InvalidArgumentException('A reason is required.');
        }

        $remake = null;
        if ($resolution === 'remake' || $resolution === 'replacement') {
            $remake = $this->makeItem($item->order, [
                'product_sale_id' => $item->product_sale_id,
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'department' => $item->department,
                'qty' => $qty,
                'customer_comment' => $item->customer_comment,
                'attempt_no' => ((int) $item->attempt_no) + 1,
                'remake_of_id' => $item->id,
            ]);
        }

        $rejection = MealRejection::create([
            'restaurant_order_id' => $item->restaurant_order_id,
            'restaurant_order_item_id' => $item->id,
            'qty' => $qty,
            'reason' => $reason,
            'resolution' => $resolution,
            'user_id' => $user->id,
            'remake_item_id' => $remake ? $remake->id : null,
        ]);
        $this->event($item->order, $item, 'rejected', $user->id, $resolution.': '.$reason);

        return $rejection;
    }

    public function assignWaitress(RestaurantOrder $order, $userId, User $actor)
    {
        $staffId = $this->staffId($userId);
        $order->waitress_user_id = $staffId;
        $order->save();
        $this->event($order, null, 'waitress_assigned', $actor->id, $staffId ? 'user '.$staffId : 'cleared');

        return $order;
    }

    public function sendReviewLink(RestaurantOrder $order)
    {
        if ($order->review_sent_at) {
            return;
        }
        if (OrderReview::where('sale_id', $order->sale_id)->exists()) {
            return;
        }
        $order->review_sent_at = now();
        $order->save();
        $sale = $order->sale ?: Sale::find($order->sale_id);
        $ref = $sale ? $sale->reference_no : $order->sale_id;
        $this->notify($order, 'Please review order '.$ref.'. Delivery time, food, environment and service: '.$order->reviewUrl());
    }

    public function notifyReceived(RestaurantOrder $order)
    {
        $sale = $order->sale ?: Sale::find($order->sale_id);
        if (! $sale) {
            return;
        }
        $lines = $order->items->map(function ($item) {
            return $item->product_name.' x'.$this->qty($item);
        })->implode(', ');
        $text = 'Skyline Hotel received order '.$sale->reference_no.' ('.$order->serviceLabel().').';
        if ($lines !== '') {
            $text .= ' '.$lines.'.';
        }
        $text .= ' Total '.number_format((float) $sale->grand_total, 0).'. Track: '.$order->trackUrl();
        $this->notify($order, $text);
    }

    protected function notify(RestaurantOrder $order, $body)
    {
        try {
            $sale = $order->relationLoaded('sale') ? $order->sale : Sale::find($order->sale_id);
            if (! $sale) {
                return;
            }
            $customer = $sale->relationLoaded('customer') ? $sale->customer : Customer::find($sale->customer_id);
            if (! $customer || ! $customer->phone_number) {
                return;
            }
            app(\App\Services\Messaging\NotificationRouter::class)
                ->sendWhatsAppTextWithLink($customer->phone_number, $body."\n".$order->trackUrl());
        } catch (\Throwable $e) {
            \Log::warning('restaurant notify: '.$e->getMessage());
        }
    }

    protected function makeItem(RestaurantOrder $order, array $data)
    {
        return RestaurantOrderItem::create([
            'restaurant_order_id' => $order->id,
            'product_sale_id' => isset($data['product_sale_id']) ? $data['product_sale_id'] : null,
            'product_id' => isset($data['product_id']) ? $data['product_id'] : null,
            'product_name' => $data['product_name'],
            'department' => $data['department'],
            'qty' => $data['qty'],
            'status' => 'awaiting',
            'customer_comment' => isset($data['customer_comment']) ? $data['customer_comment'] : null,
            'attempt_no' => isset($data['attempt_no']) ? $data['attempt_no'] : 1,
            'remake_of_id' => isset($data['remake_of_id']) ? $data['remake_of_id'] : null,
        ]);
    }

    protected function event(RestaurantOrder $order, $item, $action, $userId, $note)
    {
        RestaurantOrderEvent::create([
            'restaurant_order_id' => $order->id,
            'restaurant_order_item_id' => $item ? $item->id : null,
            'action' => $action,
            'user_id' => $userId,
            'note' => $note,
        ]);
    }

    protected function assertStatus(RestaurantOrderItem $item, $from, $to)
    {
        if ($item->status !== $from) {
            throw new \InvalidArgumentException('This item is already '.$item->status.' and cannot move to '.$to.'.');
        }
    }

    protected function staffId($id)
    {
        $id = (int) $id;
        if ($id < 1) {
            return null;
        }

        return User::where('id', $id)->exists() ? $id : null;
    }

    protected function tableId($id)
    {
        $id = (int) $id;
        if ($id < 1) {
            return null;
        }

        return RestaurantTable::where('id', $id)->where('is_active', 1)->exists() ? $id : null;
    }

    protected function token()
    {
        return bin2hex(random_bytes(20));
    }

    protected function reference()
    {
        do {
            $ref = 'rst-'.date('Ymd').'-'.date('His').'-'.mt_rand(10, 99);
        } while (Sale::where('reference_no', $ref)->exists());

        return $ref;
    }

    protected function priceLine(Product $product, $qty)
    {
        $unit = (float) $product->price;
        $rate = 0;
        if ($product->tax_id) {
            $tax = \App\Tax::find($product->tax_id);
            if ($tax) {
                $rate = (float) $tax->rate;
            }
        }
        $net = $unit * (float) $qty;
        $taxAmount = $net * $rate / 100;

        return ['unit' => $unit, 'net' => $net, 'rate' => $rate, 'tax' => $taxAmount];
    }

    protected function deductStock(Product $product, $qty, $warehouseId)
    {
        $quantity = $this->baseQty($product, $qty);
        if ($product->type === 'combo' && $product->product_list) {
            $ids = explode(',', $product->product_list);
            $qtyList = explode(',', (string) $product->qty_list);
            foreach ($ids as $key => $childId) {
                $child = Product::find($childId);
                if (! $child) {
                    continue;
                }
                $childQty = $quantity * (isset($qtyList[$key]) ? (float) $qtyList[$key] : 1);
                $this->applyQty($child, $childQty, $warehouseId);
            }

            return;
        }
        $this->applyQty($product, $quantity, $warehouseId);
    }

    protected function baseQty(Product $product, $qty)
    {
        $quantity = (float) $qty;
        if (! $product->sale_unit_id) {
            return $quantity;
        }
        $unit = Unit::find($product->sale_unit_id);
        if (! $unit) {
            return $quantity;
        }
        if ($unit->operator === '*') {
            return $quantity * (float) $unit->operation_value;
        }
        if ($unit->operator === '/' && (float) $unit->operation_value != 0) {
            return $quantity / (float) $unit->operation_value;
        }

        return $quantity;
    }

    protected function applyQty(Product $product, $quantity, $warehouseId)
    {
        $product->qty = (float) $product->qty - (float) $quantity;
        $product->save();
        $bin = Product_Warehouse::where('product_id', $product->id)->where('warehouse_id', $warehouseId)->first();
        if ($bin) {
            $bin->qty = (float) $bin->qty - (float) $quantity;
            if ($bin->qty < 0) {
                $bin->qty = 0;
            }
            $bin->save();
        }
    }

    protected function qty(RestaurantOrderItem $item)
    {
        $qty = (float) $item->qty;

        return floor($qty) == $qty ? (string) (int) $qty : (string) $qty;
    }
}
