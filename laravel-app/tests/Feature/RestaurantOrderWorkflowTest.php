<?php

namespace Tests\Feature;

use App\Category;
use App\CustomerGroup;
use App\Biller;
use App\Payment;
use App\Product;
use App\Product_Sale;
use App\Product_Warehouse;
use App\RestaurantOrder;
use App\RestaurantOrderItem;
use App\Sale;
use App\Services\Restaurant\RestaurantOrderService;
use App\Services\Restaurant\RestaurantPayments;
use App\User;
use App\Warehouse;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RestaurantOrderWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        config(['services.whatsapp.enabled' => false]);
        $this->schema();
        $this->artisan('migrate', [
            '--path' => 'database/migrations/2026_10_18_180000_create_restaurant_orders.php',
            '--force' => true,
        ]);
        $this->artisan('migrate', [
            '--path' => 'database/migrations/2026_10_22_170000_add_restaurant_lanes_and_order_permissions.php',
            '--force' => true,
        ]);
    }

    public function test_mixed_order_is_one_bill_split_by_department()
    {
        $food = $this->product('Jollof', 'food');
        $drink = $this->product('Juice', 'drinks');
        $sale = $this->order([
            ['product_id' => $food->id, 'qty' => 2, 'comment' => 'no pepper'],
            ['product_id' => $drink->id, 'qty' => 1, 'comment' => 'cold'],
        ]);

        $this->assertSame(1, Sale::count());
        $this->assertSame(1, RestaurantOrder::count());
        $order = RestaurantOrder::first();
        $this->assertSame('frontend', $order->source);
        $this->assertNull($order->waitress_user_id);
        $this->assertSame(1, $order->items()->where('department', 'food')->count());
        $this->assertSame(1, $order->items()->where('department', 'drinks')->count());
        $this->assertSame(2, Product_Sale::where('sale_id', $sale->id)->count());
    }

    public function test_prepaid_and_unpaid_orders_both_enter_the_queue()
    {
        $food = $this->product('Rice', 'food');
        $unpaid = $this->order([['product_id' => $food->id, 'qty' => 1]]);
        $this->assertSame(2, (int) $unpaid->payment_status);
        $this->assertSame('awaiting', RestaurantOrderItem::whereHas('order', function ($q) use ($unpaid) {
            $q->where('sale_id', $unpaid->id);
        })->value('status'));

        $prepaid = Sale::create($this->saleRow($unpaid->customer_id, 4, 1500));
        Product_Sale::create([
            'sale_id' => $prepaid->id, 'product_id' => $food->id, 'category_id' => $food->category_id,
            'qty' => 1, 'sale_unit_id' => 0, 'net_unit_price' => 1500, 'discount' => 0,
            'tax_rate' => 0, 'tax' => 0, 'total' => 1500,
        ]);
        app(RestaurantOrderService::class)->attachFromRequest($prepaid, Request::create('/', 'POST', [
            'service_type' => 'take_away',
        ]));
        $prepaid->refresh();
        $this->assertSame(4, (int) $prepaid->payment_status);
        $this->assertSame('awaiting', RestaurantOrder::where('sale_id', $prepaid->id)->first()->items()->value('status'));
    }

    public function test_accept_records_staff_and_estimate_and_leaves_awaiting()
    {
        $chef = $this->user('Chef', 1);
        $sale = $this->order([['product_id' => $this->product('Eru', 'food')->id, 'qty' => 1]]);
        $item = RestaurantOrder::where('sale_id', $sale->id)->first()->items()->first();
        app(RestaurantOrderService::class)->accept($item, $chef, 20);
        $item->refresh();
        $this->assertSame('preparing', $item->status);
        $this->assertSame($chef->id, (int) $item->accepted_by);
        $this->assertSame(20, (int) $item->estimate_minutes);
        $this->assertNotNull($item->expected_ready_at);
        $this->assertSame(0, RestaurantOrderItem::where('status', 'awaiting')->where('id', $item->id)->count());

        $this->expectException(\InvalidArgumentException::class);
        app(RestaurantOrderService::class)->accept($item, $chef, 10);
    }

    public function test_tracking_shows_progress_and_does_not_leak_internal_notes()
    {
        $sale = $this->order([['product_id' => $this->product('Ndole', 'food')->id, 'qty' => 1, 'comment' => 'extra veg']]);
        $order = RestaurantOrder::where('sale_id', $sale->id)->first();
        $order->internal_note = 'SECRET-STAFF-NOTE';
        $order->save();
        $item = $order->items()->first();
        $item->internal_note = 'SECRET-LINE';
        $item->expected_ready_at = now()->subMinute();
        $item->status = 'preparing';
        $item->save();

        $this->get('/order-track/'.$sale->id.'/'.$order->token)
            ->assertOk()
            ->assertSee($sale->reference_no)
            ->assertSee('extra veg')
            ->assertSee('Taking longer than expected')
            ->assertDontSee('SECRET-STAFF-NOTE')
            ->assertDontSee('SECRET-LINE');

        $this->get('/order-track/'.$sale->id.'/wrong-token')->assertNotFound();
    }

    public function test_partial_delivery_keeps_item_and_order_status()
    {
        $waiter = $this->user('Ada', 1);
        $sale = $this->order([
            ['product_id' => $this->product('Chicken', 'food')->id, 'qty' => 1],
            ['product_id' => $this->product('Beer', 'drinks')->id, 'qty' => 1],
        ]);
        $items = RestaurantOrder::where('sale_id', $sale->id)->first()->items;
        $service = app(RestaurantOrderService::class);
        foreach ($items as $item) {
            $service->accept($item, $waiter, 5);
            $service->markReady($item->fresh(), $waiter);
        }
        $ready = RestaurantOrderItem::where('status', 'ready')->count();
        $this->assertSame(2, $ready);
        $service->deliver($items[0]->fresh(), $waiter);
        $order = RestaurantOrder::where('sale_id', $sale->id)->first();
        $this->assertFalse($order->isFulfilled());
        $this->assertTrue($order->partiallyDelivered());
        $service->deliver($items[1]->fresh(), $waiter);
        $this->assertTrue($order->fresh()->isFulfilled());
        $sale->refresh();
        $this->assertSame(2, (int) $sale->payment_status);
        $this->assertSame(0, Payment::count());
    }

    public function test_gateway_callback_is_idempotent_and_pages_do_not_create_payments()
    {
        $sale = $this->order([['product_id' => $this->product('Water', 'drinks')->id, 'qty' => 1]]);
        $before = Payment::count();
        $order = RestaurantOrder::where('sale_id', $sale->id)->first();
        $this->get('/pay/invoice/'.$sale->id.'/'.\App\Support\SalePayQr::token($sale))->assertOk()->assertSee('MoMo');
        $this->get('/order-confirm/'.$sale->id.'/'.$order->token)->assertOk();
        $this->assertSame($before, Payment::count());

        $this->assertSame('recorded', RestaurantPayments::recordGateway($sale, $sale->grand_total, 'campay-ref-1'));
        $sale->refresh();
        $this->assertSame(4, (int) $sale->payment_status);
        $this->assertSame('duplicate', RestaurantPayments::recordGateway($sale, $sale->grand_total, 'campay-ref-1'));
        $this->assertSame('paid', RestaurantPayments::recordGateway($sale->fresh(), $sale->grand_total, 'campay-ref-2'));
        $this->assertSame(1, Payment::where('sale_id', $sale->id)->count());
    }

    public function test_rejection_remake_keeps_history_and_does_not_touch_money()
    {
        $chef = $this->user('Chef', 1);
        $sale = $this->order([['product_id' => $this->product('Fish', 'food')->id, 'qty' => 2]]);
        $service = app(RestaurantOrderService::class);
        $item = RestaurantOrder::where('sale_id', $sale->id)->first()->items()->first();
        $service->accept($item, $chef, 8);
        $service->markReady($item->fresh(), $chef);
        $service->deliver($item->fresh(), $chef);
        $sale->paid_amount = 500;
        $sale->payment_status = 3;
        $sale->save();
        $service->reject($item->fresh(), $chef, 1, 'Too salty', 'remake');
        $sale->refresh();
        $this->assertSame(3, (int) $sale->payment_status);
        $this->assertEquals(500, (float) $sale->paid_amount);
        $this->assertSame(0, Payment::count());
        $this->assertSame('delivered', $item->fresh()->status);
        $remake = RestaurantOrderItem::where('remake_of_id', $item->id)->first();
        $this->assertNotNull($remake);
        $this->assertSame('awaiting', $remake->status);
        $this->assertEquals(1, (float) $remake->qty);
    }

    public function test_department_and_customer_access()
    {
        $sale = $this->order([
            ['product_id' => $this->product('Fufu', 'food')->id, 'qty' => 1],
            ['product_id' => $this->product('Wine', 'drinks')->id, 'qty' => 1],
        ]);
        $food = RestaurantOrderItem::where('department', 'food')->first();
        $bartender = $this->user('Bar', 9);
        $this->withoutMiddleware();
        $this->actingAs($bartender);
        $this->post('/orders/items/'.$food->id.'/accept', ['minutes' => 10])
            ->assertRedirect()
            ->assertSessionHas('not_permitted');
        $this->assertSame('awaiting', $food->fresh()->status);

        $other = $this->order([['product_id' => $this->product('Salad', 'food')->id, 'qty' => 1]]);
        $first = RestaurantOrder::where('sale_id', $sale->id)->first();
        $this->get('/order-track/'.$other->id.'/'.$first->token)->assertNotFound();
    }

    public function test_stock_is_deducted_once()
    {
        $product = $this->product('Cake', 'food', 10);
        $token = 'same-checkout-token';
        $service = app(RestaurantOrderService::class);
        $service->placePublic([
            'name' => 'Amina', 'phone' => '675000111', 'service_type' => 'take_away',
            'checkout_token' => $token,
            'items' => [['product_id' => $product->id, 'qty' => 2]],
        ]);
        $service->placePublic([
            'name' => 'Amina', 'phone' => '675000111', 'service_type' => 'take_away',
            'checkout_token' => $token,
            'items' => [['product_id' => $product->id, 'qty' => 2]],
        ]);
        $this->assertEquals(8, (float) $product->fresh()->qty);
        $item = RestaurantOrderItem::where('product_id', $product->id)->first();
        $service->accept($item, $this->user('Chef', 1), 6);
        $this->assertEquals(8, (float) $product->fresh()->qty);
        $this->assertSame(1, Sale::count());
    }

    public function test_public_menu_checkout_creates_one_order()
    {
        $food = $this->product('Menu Rice', 'food');
        $this->get('/menu')->assertOk();
        $payload = [
            'name' => 'Guest',
            'phone' => '675321700',
            'service_type' => 'take_away',
            'checkout_token' => 'menu-once',
            'product_id' => [$food->id],
            'qty' => [1],
            'item_comment' => ['mild'],
        ];
        $this->post('/menu', $payload)->assertRedirect();
        $this->assertSame(1, Sale::count());
        $this->post('/menu', $payload)->assertRedirect();
        $this->assertSame(1, Sale::count());
        $this->assertSame('online', RestaurantOrderItem::where('product_id', $food->id)->value('lane'));
    }

    public function test_review_saves_scores()
    {
        $sale = $this->order([['product_id' => $this->product('Soup', 'food')->id, 'qty' => 1]]);
        $order = RestaurantOrder::where('sale_id', $sale->id)->first();
        $this->post('/order-review/'.$sale->id.'/'.$order->token, [
            'guest_name' => 'Chantal',
            'country' => 'Cameroon',
            'delivery_time' => 5,
            'food_quality' => 4,
            'environment' => 5,
            'services' => 4,
        ])->assertRedirect();
        $this->assertDatabaseHas('order_reviews', [
            'sale_id' => $sale->id,
            'guest_name' => 'Chantal',
            'country' => 'Cameroon',
            'delivery_time' => 5,
        ]);
    }

    protected function order(array $items)
    {
        return app(RestaurantOrderService::class)->placePublic([
            'name' => 'Guest',
            'phone' => '675321700',
            'country' => 'Cameroon',
            'service_type' => 'dine_in',
            'restaurant_table_id' => 1,
            'customer_comment' => 'window seat',
            'checkout_token' => bin2hex(random_bytes(8)),
            'items' => $items,
        ]);
    }

    protected function product($name, $department, $qty = 20)
    {
        $category = Category::where('department', $department)->first();
        $product = Product::create([
            'name' => $name,
            'code' => strtolower($name).'-'.mt_rand(100, 999),
            'type' => 'standard',
            'barcode_symbology' => 'C128',
            'category_id' => $category->id,
            'unit_id' => 1,
            'purchase_unit_id' => 1,
            'sale_unit_id' => 0,
            'cost' => '500',
            'price' => '1500',
            'qty' => $qty,
            'is_active' => 1,
        ]);
        Product_Warehouse::create([
            'product_id' => $product->id,
            'warehouse_id' => 1,
            'qty' => $qty,
        ]);

        return $product;
    }

    protected function user($name, $roleId)
    {
        return User::create([
            'name' => $name,
            'email' => strtolower($name).$roleId.'@skyline.test',
            'password' => 'secret',
            'role_id' => $roleId,
            'is_active' => 1,
        ]);
    }

    protected function saleRow($customerId, $paymentStatus, $total)
    {
        return [
            'reference_no' => 'sr-test-'.mt_rand(1000, 9999),
            'customer_id' => $customerId,
            'warehouse_id' => 1,
            'biller_id' => 1,
            'item' => 1,
            'total_qty' => 1,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => $total,
            'grand_total' => $total,
            'sale_status' => 1,
            'payment_status' => $paymentStatus,
            'paid_amount' => $paymentStatus === 4 ? $total : 0,
        ];
    }

    public function test_dine_in_routes_lanes_and_reject_lowers_the_bill()
    {
        $coffeeCategory = Category::create(['name' => 'Coffee & Tea', 'is_active' => 1]);
        $coffee = $this->product('Espresso', 'drinks');
        $coffee->category_id = $coffeeCategory->id;
        $coffee->save();
        $food = $this->product('Plantain', 'food');
        $drink = $this->product('Soda', 'drinks');
        $customer = \App\Customer::create([
            'customer_group_id' => 1,
            'name' => 'Ada',
            'phone_number' => '675000222',
            'is_active' => 1,
        ]);
        $sale = Sale::create($this->saleRow($customer->id, 2, 4500));
        foreach ([$food, $drink, $coffee] as $product) {
            Product_Sale::create([
                'sale_id' => $sale->id,
                'product_id' => $product->id,
                'category_id' => $product->category_id,
                'qty' => 1,
                'sale_unit_id' => 0,
                'net_unit_price' => 1500,
                'discount' => 0,
                'tax_rate' => 0,
                'tax' => 0,
                'total' => 1500,
            ]);
        }
        $service = app(RestaurantOrderService::class);
        $order = $service->attachFromRequest($sale, Request::create('/', 'POST', [
            'service_type' => 'dine_in',
            'restaurant_table_id' => 1,
        ]));
        $this->assertSame(1, RestaurantOrder::where('sale_id', $sale->id)->count());
        $this->assertSame('restaurant', $order->items()->where('product_id', $food->id)->value('lane'));
        $this->assertSame('bar', $order->items()->where('product_id', $drink->id)->value('lane'));
        $this->assertSame('coffee', $order->items()->where('product_id', $coffee->id)->value('lane'));
        $this->assertSame($order->id, $service->attachFromRequest($sale, Request::create('/', 'POST', [
            'service_type' => 'dine_in',
        ]))->id);

        $web = Sale::create($this->saleRow($customer->id, 2, 1500));
        Product_Sale::create([
            'sale_id' => $web->id, 'product_id' => $food->id, 'category_id' => $food->category_id,
            'qty' => 1, 'sale_unit_id' => 0, 'net_unit_price' => 1500, 'discount' => 0,
            'tax_rate' => 0, 'tax' => 0, 'total' => 1500,
        ]);
        $online = $service->attachFromRequest($web, Request::create('/', 'POST', [
            'order_source' => 'frontend',
            'service_type' => 'take_away',
        ]));
        $this->assertSame('online', $online->items()->value('lane'));
        $this->assertSame('food', $online->items()->value('department'));

        $chef = $this->user('LaneChef', 1);
        $coffeeItem = $order->items()->where('lane', 'coffee')->first();
        \App\Services\Restaurant\RestaurantAccess::forgetCounts();
        $before = (int) (\App\Services\Restaurant\RestaurantAccess::queueCounts()['coffee']['awaiting'] ?? 0);
        $service->accept($coffeeItem, $chef, null);
        \App\Services\Restaurant\RestaurantAccess::forgetCounts();
        $after = (int) (\App\Services\Restaurant\RestaurantAccess::queueCounts()['coffee']['awaiting'] ?? 0);
        $this->assertSame('preparing', $coffeeItem->fresh()->status);
        $this->assertSame($before - 1, $after);
        $this->assertEquals(20, (float) $coffee->fresh()->qty);

        $drinkItem = $order->items()->where('lane', 'bar')->first();
        $service->deny($drinkItem, $chef, 'Rejected');
        $sale->refresh();
        $this->assertEquals(3000, (float) $sale->grand_total);
        $this->assertEquals(21, (float) $drink->fresh()->qty);
        $this->assertStringContainsString('Order Logs @if', file_get_contents(resource_path('views/restaurant/logs.blade.php')));
        $queue = file_get_contents(resource_path('views/restaurant/queue.blade.php'));
        $this->assertStringContainsString('>Accept<', $queue);
        $this->assertStringContainsString('>Reject<', $queue);
        $this->assertStringNotContainsString('>Hold<', $queue);
    }

    protected function schema()
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('parent_id')->nullable();
            $table->boolean('is_active')->nullable();
            $table->timestamps();
        });
        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('code');
            $table->string('type');
            $table->string('barcode_symbology')->nullable();
            $table->integer('category_id');
            $table->integer('unit_id')->nullable();
            $table->integer('purchase_unit_id')->nullable();
            $table->integer('sale_unit_id')->nullable();
            $table->string('cost')->nullable();
            $table->string('price')->nullable();
            $table->double('qty')->nullable();
            $table->integer('tax_id')->nullable();
            $table->boolean('is_active')->nullable();
            $table->text('product_list')->nullable();
            $table->text('qty_list')->nullable();
            $table->timestamps();
        });
        Schema::create('product_warehouse', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('product_id');
            $table->integer('warehouse_id');
            $table->double('qty')->nullable();
            $table->timestamps();
        });
        Schema::create('customer_groups', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('customers', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('customer_group_id')->nullable();
            $table->string('name');
            $table->string('phone_number')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->boolean('is_active')->nullable();
            $table->timestamps();
        });
        Schema::create('warehouses', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->boolean('is_active')->nullable();
            $table->timestamps();
        });
        Schema::create('billers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('phone_number')->nullable();
            $table->boolean('is_active')->nullable();
            $table->timestamps();
        });
        Schema::create('sales', function (Blueprint $table) {
            $table->increments('id');
            $table->string('reference_no');
            $table->integer('user_id')->nullable();
            $table->integer('customer_id');
            $table->integer('warehouse_id');
            $table->integer('biller_id');
            $table->integer('item')->nullable();
            $table->double('total_qty')->nullable();
            $table->double('total_discount')->nullable();
            $table->double('total_tax')->nullable();
            $table->double('total_price')->nullable();
            $table->double('order_tax_rate')->nullable();
            $table->double('order_tax')->nullable();
            $table->double('order_discount')->nullable();
            $table->double('shipping_cost')->nullable();
            $table->double('grand_total')->nullable();
            $table->integer('sale_status')->nullable();
            $table->integer('payment_status')->nullable();
            $table->double('paid_amount')->nullable();
            $table->integer('paid_by_id')->nullable();
            $table->text('sale_note')->nullable();
            $table->text('staff_note')->nullable();
            $table->timestamps();
        });
        Schema::create('product_sales', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('sale_id');
            $table->integer('product_id');
            $table->integer('category_id')->nullable();
            $table->integer('warehouse_id')->nullable();
            $table->double('qty');
            $table->integer('sale_unit_id')->nullable();
            $table->double('net_unit_price')->nullable();
            $table->double('discount')->nullable();
            $table->double('tax_rate')->nullable();
            $table->double('tax')->nullable();
            $table->double('total')->nullable();
            $table->timestamps();
        });
        Schema::create('payments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->nullable();
            $table->integer('sale_id')->nullable();
            $table->integer('account_id')->nullable();
            $table->string('payment_reference')->nullable();
            $table->double('amount')->nullable();
            $table->double('change')->nullable();
            $table->string('paying_method')->nullable();
            $table->text('payment_note')->nullable();
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->integer('role_id')->nullable();
            $table->boolean('is_active')->nullable();
            $table->timestamps();
        });
        CustomerGroup::create(['name' => 'General']);
        Warehouse::create(['name' => 'Main', 'is_active' => 1]);
        Biller::create(['name' => 'Skyline', 'is_active' => 1]);
    }
}
