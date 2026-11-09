<?php

namespace App\Support;

use App\Product;
use App\Product_Sale;
use App\Product_Warehouse;
use App\RestaurantOrder;
use App\RestaurantOrderEvent;
use App\RestaurantOrderItem;
use App\RestaurantTable;
use App\Sale;
use App\Services\Restaurant\RestaurantAccess;
use App\Services\Restaurant\RestaurantDepartment;
use App\Services\Restaurant\RestaurantOrderService;
use App\Unit;
use App\User;
use Illuminate\Support\Facades\DB;

class TableOrderEditor
{
    public function orderOnTable($tableId, $saleId)
    {
        $tableId = (int) $tableId;
        $saleId = (int) $saleId;
        $order = RestaurantOrder::with(['items.productSale', 'sale', 'diningTable'])
            ->where('restaurant_table_id', $tableId)
            ->where('sale_id', $saleId)
            ->first();
        if (! $order || ! $order->sale || (int) $order->sale->sale_status === 3) {
            return null;
        }

        return $order;
    }

    public function lines(RestaurantOrder $order)
    {
        $lines = [];
        foreach ($order->items as $item) {
            if (in_array($item->status, ['rejected', 'cancelled'], true)) {
                continue;
            }
            $line = $item->productSale;
            $unit = 0;
            if ($line && (float) $line->qty > 0.0001) {
                $unit = round((float) $line->total / (float) $line->qty, 2);
            }
            $lines[] = [
                'id' => (int) $item->id,
                'name' => (string) $item->product_name,
                'qty' => (float) $item->qty,
                'unit' => $unit,
                'status' => (string) $item->status,
            ];
        }

        return $lines;
    }

    public function products()
    {
        return Product::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'price']);
    }

    /**
     * @param  array<int, float|string>  $qtys
     */
    public function apply(RestaurantOrder $order, array $qtys, $productId, $addQty, User $user)
    {
        $sale = $order->sale ?: Sale::find($order->sale_id);
        if (! $sale) {
            throw new \InvalidArgumentException('This order has no bill.');
        }
        $items = [];
        foreach ($order->items as $item) {
            if (! in_array($item->status, ['rejected', 'cancelled'], true)) {
                $items[$item->id] = $item;
            }
        }
        $changes = [];
        foreach ($qtys as $id => $qty) {
            $id = (int) $id;
            if (! isset($items[$id])) {
                continue;
            }
            $qty = round((float) $qty, 2);
            if ($qty < 0 || $qty > 99) {
                throw new \InvalidArgumentException('Quantity must be between 0 and 99.');
            }
            $current = round((float) $items[$id]->qty, 2);
            if (abs($qty - $current) > 0.001) {
                $changes[$id] = $qty;
            }
        }
        $productId = (int) $productId;
        $addQty = round((float) $addQty, 2);
        $product = null;
        if ($productId > 0 && $addQty > 0) {
            if ($addQty > 99) {
                throw new \InvalidArgumentException('Quantity must be between 1 and 99.');
            }
            $product = Product::where('id', $productId)->where('is_active', true)->first();
            if (! $product) {
                throw new \InvalidArgumentException('Choose a product that is still for sale.');
            }
        } else {
            $addQty = 0;
        }
        if (! $changes && ! $product) {
            return 'No change was made.';
        }

        $projected = (float) $sale->grand_total;
        foreach ($changes as $id => $qty) {
            $item = $items[$id];
            $projected += $this->unitPrice($item) * ($qty - (float) $item->qty);
        }
        if ($product) {
            $projected += (float) $product->price * $addQty;
        }
        $projected = round($projected, 2);
        if ($projected + 0.009 < (float) $sale->paid_amount) {
            throw new \InvalidArgumentException('This order already has '.TableBoard::money($sale->paid_amount).' paid. The new total cannot be less than that.');
        }

        DB::transaction(function () use ($order, $sale, $items, $changes, $product, $addQty, $user) {
            $orders = app(RestaurantOrderService::class);
            foreach ($changes as $id => $qty) {
                $item = $items[$id];
                $current = (float) $item->qty;
                if ($qty < $current - 0.001) {
                    $orders->removeFromBill($item->fresh(), $user, $current - $qty);
                }
            }
            foreach ($changes as $id => $qty) {
                $item = RestaurantOrderItem::find($id);
                if (! $item || in_array($item->status, ['rejected', 'cancelled'], true)) {
                    continue;
                }
                $extra = round($qty - (float) $item->qty, 2);
                if ($extra > 0.001) {
                    $this->addQuantity($order, $sale, $item, $extra, $user);
                }
            }
            if ($product) {
                $this->addProduct($order, $sale->fresh(), $product, $addQty, $user);
            }
            $this->refreshPayment($sale->fresh());
        });
        RestaurantAccess::forgetCounts();

        return 'Order updated.';
    }

    protected function unitPrice(RestaurantOrderItem $item)
    {
        $line = $item->productSale;
        if ($line && (float) $line->qty > 0.0001) {
            return (float) $line->total / (float) $line->qty;
        }
        $product = $item->product_id ? Product::find($item->product_id) : null;

        return $product ? (float) $product->price : 0;
    }

    protected function addQuantity(RestaurantOrder $order, Sale $sale, RestaurantOrderItem $item, $extra, User $user)
    {
        $product = $item->product_id ? Product::find($item->product_id) : null;
        $unit = $this->unitPrice($item);
        $amount = round($unit * $extra, 2);
        $open = in_array($item->status, ['awaiting', 'preparing', 'hold'], true);
        if ($open) {
            $line = $item->product_sale_id ? Product_Sale::find($item->product_sale_id) : null;
            if ($line) {
                $line->qty = round((float) $line->qty + $extra, 4);
                $line->total = round((float) $line->total + $amount, 2);
                $line->save();
            }
            $item->qty = round((float) $item->qty + $extra, 4);
            $item->save();
        } else {
            $this->addProduct($order, $sale, $product ?: $this->namedProduct($item), $extra, $user, $unit, $item->product_name, $item->lane, $item->department);

            return;
        }
        $this->growSale($sale, $extra, $amount);
        if ($product) {
            $this->deductStock($product, $extra, $sale->warehouse_id);
        }
        $this->event($order, $item, $user, 'Added '.$extra.' more');
    }

    protected function addProduct(RestaurantOrder $order, Sale $sale, Product $product, $qty, User $user, $unit = null, $name = null, $lane = null, $department = null)
    {
        $unit = $unit === null ? (float) $product->price : (float) $unit;
        $amount = round($unit * (float) $qty, 2);
        $name = $name ?: $product->name;
        $line = Product_Sale::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'category_id' => $product->category_id,
            'warehouse_id' => $sale->warehouse_id,
            'qty' => $qty,
            'sale_unit_id' => $product->sale_unit_id ?: 0,
            'net_unit_price' => $unit,
            'discount' => 0,
            'tax_rate' => 0,
            'tax' => 0,
            'total' => $amount,
        ]);
        if (! $lane || ! $department) {
            list($lane, $department) = RestaurantDepartment::laneAndDepartment($product->category_id);
        }
        if ($lane && $department) {
            $item = RestaurantOrderItem::create([
                'restaurant_order_id' => $order->id,
                'product_sale_id' => $line->id,
                'product_id' => $product->id,
                'product_name' => $name,
                'department' => $department,
                'lane' => $lane,
                'qty' => $qty,
                'status' => 'awaiting',
                'attempt_no' => 1,
            ]);
            $this->event($order, $item, $user, 'Added from the table');
        }
        $this->growSale($sale, $qty, $amount);
        $this->deductStock($product, $qty, $sale->warehouse_id);
    }

    protected function namedProduct(RestaurantOrderItem $item)
    {
        $product = new Product();
        $product->id = $item->product_id;
        $product->name = $item->product_name;
        $product->price = $this->unitPrice($item);
        $product->exists = true;

        return $product;
    }

    protected function growSale(Sale $sale, $qty, $amount)
    {
        $locked = Sale::where('id', $sale->id)->lockForUpdate()->first();
        if (! $locked) {
            return;
        }
        $locked->grand_total = round((float) $locked->grand_total + $amount, 2);
        $locked->total_price = round((float) $locked->total_price + $amount, 2);
        $locked->total_qty = round((float) $locked->total_qty + (float) $qty, 4);
        $locked->save();
    }

    protected function refreshPayment(Sale $sale)
    {
        $due = round((float) $sale->grand_total - (float) $sale->paid_amount, 2);
        if ($due <= 0.009) {
            $sale->payment_status = 4;
        } elseif ((float) $sale->paid_amount > 0.009) {
            $sale->payment_status = 3;
        } elseif ((int) $sale->payment_status !== 1) {
            $sale->payment_status = 2;
        }
        $sale->save();
    }

    protected function event(RestaurantOrder $order, RestaurantOrderItem $item, User $user, $note)
    {
        RestaurantOrderEvent::create([
            'restaurant_order_id' => $order->id,
            'restaurant_order_item_id' => $item->id,
            'action' => 'added',
            'user_id' => $user->id,
            'note' => $note,
        ]);
    }

    protected function deductStock(Product $product, $qty, $warehouseId)
    {
        if (! $product->exists && ! $product->id) {
            return;
        }
        $stored = Product::where('id', $product->id)->lockForUpdate()->first();
        if (! $stored) {
            return;
        }
        $quantity = $this->baseQty($stored, $qty);
        if ($stored->type === 'combo' && $stored->product_list) {
            $ids = explode(',', $stored->product_list);
            $qtyList = explode(',', (string) $stored->qty_list);
            foreach ($ids as $key => $childId) {
                $child = Product::where('id', $childId)->lockForUpdate()->first();
                if (! $child) {
                    continue;
                }
                $childQty = $quantity * (isset($qtyList[$key]) ? (float) $qtyList[$key] : 1);
                $this->applyQty($child, $childQty, $warehouseId);
            }

            return;
        }
        $this->applyQty($stored, $quantity, $warehouseId);
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
        if (! $warehouseId) {
            return;
        }
        $bin = Product_Warehouse::where('product_id', $product->id)->where('warehouse_id', $warehouseId)->lockForUpdate()->first();
        if ($bin) {
            $bin->qty = (float) $bin->qty - (float) $quantity;
            if ($bin->qty < 0) {
                $bin->qty = 0;
            }
            $bin->save();
        }
    }

    public function tableName($tableId)
    {
        $table = RestaurantTable::find((int) $tableId);

        return $table ? $table->name : 'Table';
    }
}
