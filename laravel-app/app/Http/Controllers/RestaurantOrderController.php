<?php

namespace App\Http\Controllers;

use App\MealRejection;
use App\Product_Sale;
use App\RestaurantOrder;
use App\RestaurantOrderItem;
use App\RestaurantTable;
use App\Sale;
use App\User;
use App\Services\Restaurant\RestaurantAccess;
use App\Services\Restaurant\RestaurantOrderService;
use Illuminate\Http\Request;

class RestaurantOrderController extends Controller
{
    protected $orders;

    public function __construct(RestaurantOrderService $orders)
    {
        $this->orders = $orders;
    }

    public function board(Request $request)
    {
        $user = $request->user();
        if (! RestaurantAccess::canSeeOrders($user)) {
            return redirect('/')->with('not_permitted', 'Sorry! You are not allowed to access this module');
        }
        $lanes = [];
        foreach (RestaurantAccess::lanes() as $key => $label) {
            if (RestaurantAccess::seeLane($user, $key)) {
                $lanes[$key] = $label;
            }
        }
        if (! $lanes) {
            return redirect('/')->with('not_permitted', 'Sorry! You are not allowed to access this module');
        }
        reset($lanes);
        $lane = $request->query('lane', key($lanes));
        if (! isset($lanes[$lane])) {
            reset($lanes);
            $lane = key($lanes);
        }
        $tabs = [];
        foreach (RestaurantAccess::statuses() as $key => $label) {
            if (RestaurantAccess::seeStatus($user, $key)) {
                $tabs[$key] = $label;
            }
        }
        $tab = $request->query('tab', 'awaiting');
        if ($tab === 'preparing') {
            $tab = 'accepted';
        }
        if (! isset($tabs[$tab])) {
            if (isset($tabs['awaiting'])) {
                $tab = 'awaiting';
            } else {
                reset($tabs);
                $tab = key($tabs);
            }
        }
        $status = $tab === 'accepted' ? 'preparing' : $tab;
        $items = RestaurantOrderItem::with(['order.sale.customer', 'order.diningTable', 'order.waitress', 'order.placedBy', 'acceptedBy', 'productSale'])
            ->where(function ($query) use ($lane) {
                RestaurantAccess::applyLane($query, $lane);
            })
            ->where('status', $status)
            ->orderBy($tab === 'delivered' ? 'id' : 'id', $tab === 'delivered' ? 'desc' : 'asc')
            ->limit($tab === 'delivered' ? 40 : 80)
            ->get();

        if ($status === 'awaiting') {
            $this->markPopupOpened($items->pluck('id')->all(), (int) $user->id);
        }

        return view('restaurant.queue', [
            'lanes' => $lanes,
            'lane' => $lane,
            'tabs' => $tabs,
            'tab' => $tab,
            'items' => $items,
            'counts' => RestaurantAccess::queueCounts(),
            'canReject' => RestaurantAccess::can($user, 'meal-rejections'),
        ]);
    }

    public function kitchen(Request $request)
    {
        return redirect()->route('restaurant.orders', [
            'lane' => 'restaurant',
            'tab' => $request->query('tab', 'awaiting'),
        ]);
    }

    public function bar(Request $request)
    {
        return redirect()->route('restaurant.orders', [
            'lane' => 'bar',
            'tab' => $request->query('tab', 'awaiting'),
        ]);
    }

    public function ready(Request $request)
    {
        return redirect()->route('restaurant.orders', [
            'lane' => 'bar',
            'tab' => 'ready',
        ]);
    }

    public function accept(Request $request, $item)
    {
        $row = $this->item($item);
        if ($deny = $this->denyOrder($request, $row, 'add')) {
            return $deny;
        }
        try {
            $this->orders->accept($row, $request->user(), $request->input('minutes'));
        } catch (\InvalidArgumentException $e) {
            return back()->with('not_permitted', $e->getMessage());
        }

        return back()->with('message', 'Accepted. The item is now being prepared.');
    }

    public function estimate(Request $request, $item)
    {
        $row = $this->item($item);
        if ($deny = $this->denyOrder($request, $row, 'edit')) {
            return $deny;
        }
        try {
            $this->orders->reviseEstimate($row, $request->user(), $request->input('minutes'));
        } catch (\InvalidArgumentException $e) {
            return back()->with('not_permitted', $e->getMessage());
        }

        return back()->with('message', 'Preparation time updated.');
    }

    public function markReady(Request $request, $item)
    {
        $row = $this->item($item);
        if ($deny = $this->denyOrder($request, $row, 'edit')) {
            return $deny;
        }
        try {
            $this->orders->markReady($row, $request->user());
        } catch (\InvalidArgumentException $e) {
            return back()->with('not_permitted', $e->getMessage());
        }

        return back()->with('message', 'Marked ready for delivery.');
    }

    public function deliver(Request $request, $item)
    {
        $row = $this->item($item);
        if ($deny = $this->denyOrder($request, $row, 'edit')) {
            return $deny;
        }
        try {
            $this->orders->deliver($row, $request->user());
        } catch (\InvalidArgumentException $e) {
            return back()->with('not_permitted', $e->getMessage());
        }

        return back()->with('message', 'Marked served / delivered.');
    }

    public function reject(Request $request, $item)
    {
        if (! RestaurantAccess::can($request->user(), 'meal-rejections')) {
            return redirect('/')->with('not_permitted', 'Sorry! You are not allowed to access this module');
        }
        $row = $this->item($item);
        try {
            $this->orders->reject(
                $row,
                $request->user(),
                $request->input('qty'),
                $request->input('reason'),
                $request->input('resolution')
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('not_permitted', $e->getMessage());
        }

        return back()->with('message', 'Rejection recorded. Use Return Sale if a refund or price change is authorised.');
    }

    public function deny(Request $request, $item)
    {
        $row = $this->item($item);
        if ($deny = $this->denyOrder($request, $row, 'delete')) {
            return $deny;
        }
        try {
            $this->orders->deny($row, $request->user(), $request->input('reason'));
        } catch (\InvalidArgumentException $e) {
            return back()->with('not_permitted', $e->getMessage());
        }

        return back()->with('message', 'Rejected. That line is off the amount due.');
    }

    public function openAlert(Request $request)
    {
        $user = $request->user();
        if (! $user || ! RestaurantAccess::canSeeOrders($user)) {
            return response()->json(['ok' => false], 403);
        }
        $ids = [];
        foreach ((array) $request->input('ids', []) as $id) {
            $id = (string) $id;
            if (preg_match('/^ord-(\d+)$/', $id, $match)) {
                $ids[] = (int) $match[1];
            } elseif (ctype_digit($id)) {
                $ids[] = (int) $id;
            }
        }
        $this->markPopupOpened($ids, (int) $user->id);

        return response()->json(['ok' => true]);
    }

    public function alerts(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['items' => [], 'summary' => [], 'pending' => 0]);
        }
        $items = [];
        $summary = [];
        if (RestaurantAccess::installed() && RestaurantAccess::canSeeOrders($user) && RestaurantAccess::seeStatus($user, 'awaiting')) {
            $lanes = [];
            foreach (array_keys(RestaurantAccess::lanes()) as $lane) {
                if (RestaurantAccess::seeLane($user, $lane)) {
                    $lanes[] = $lane;
                }
            }
            if ($lanes) {
                $rows = RestaurantOrderItem::with(['order.sale', 'order.placedBy', 'order.waitress'])
                    ->where('status', 'awaiting')
                    ->when(\Schema::hasColumn('restaurant_order_items', 'popup_opened_at'), function ($query) {
                        $query->whereNull('popup_opened_at');
                    })
                    ->where(function ($query) use ($lanes) {
                        foreach ($lanes as $lane) {
                            $query->orWhere(function ($match) use ($lane) {
                                RestaurantAccess::applyLane($match, $lane);
                            });
                        }
                    })
                    ->orderBy('id', 'desc')
                    ->limit(20)
                    ->get();
                foreach ($rows as $row) {
                    $sale = $row->order ? $row->order->sale : null;
                    $lane = $row->lane ?: ($row->department === 'drinks' ? 'bar' : 'restaurant');
                    if ($lane === 'coffee') {
                        $lane = 'bar';
                    }
                    if ($lane === 'online') {
                        $lane = $row->department === 'drinks' ? 'bar' : 'restaurant';
                    }
                    $placer = optional(optional($row->order)->placedBy)->name
                        ?: optional(optional($row->order)->waitress)->name;
                    $items[] = [
                        'id' => 'ord-'.(int) $row->id,
                        'name' => $row->product_name,
                        'qty' => $row->qty,
                        'status' => 'awaiting',
                        'lane' => $lane,
                        'lane_label' => RestaurantAccess::lanes()[$lane] ?? $lane,
                        'ref' => $sale ? $sale->reference_no : '',
                        'placed_by' => $placer ?: '',
                        'href' => route('restaurant.orders', ['lane' => $lane, 'tab' => 'awaiting']),
                    ];
                }
                $counts = RestaurantAccess::queueCounts();
                foreach ($lanes as $lane) {
                    $awaiting = (int) ($counts[$lane]['awaiting'] ?? 0);
                    if ($awaiting < 1) {
                        continue;
                    }
                    $summary[] = [
                        'lane' => $lane,
                        'label' => RestaurantAccess::lanes()[$lane] ?? $lane,
                        'awaiting' => $awaiting,
                        'total' => $awaiting,
                        'href' => route('restaurant.orders', ['lane' => $lane, 'tab' => 'awaiting']),
                    ];
                }
            }
        }

        return response()->json([
            'items' => $items,
            'summary' => $summary,
            'pending' => array_sum(array_map(function ($row) {
                return (int) ($row['total'] ?? 0);
            }, $summary)),
        ]);
    }

    public function help(Request $request)
    {
        if (! RestaurantAccess::canSeeOrders($request->user())) {
            return redirect('/')->with('not_permitted', 'Sorry! You are not allowed to access this module');
        }
        $topic = $request->query('topic');
        $known = ['restaurant-coffee', 'restaurant', 'restaurant-bar', 'orders'];
        if (! in_array($topic, $known, true)) {
            $topic = null;
        }

        return view('restaurant.help', compact('topic'));
    }

    public function assignWaitress(Request $request, $order)
    {
        if (! RestaurantAccess::can($request->user(), 'waiter-orders') && ! RestaurantAccess::can($request->user(), 'order-logs')) {
            return redirect('/')->with('not_permitted', 'Sorry! You are not allowed to access this module');
        }
        $row = RestaurantOrder::findOrFail($order);
        $this->orders->assignWaitress($row, $request->input('waitress_user_id'), $request->user());

        return back()->with('message', 'Waitress / waiter updated.');
    }

    public function confirmation($sale)
    {
        $user = auth()->user();
        if (! RestaurantAccess::can($user, 'sales-index') && ! RestaurantAccess::can($user, 'waiter-orders') && ! RestaurantAccess::can($user, 'kitchen-orders') && ! RestaurantAccess::can($user, 'bar-orders')) {
            return redirect('/')->with('not_permitted', 'Sorry! You are not allowed to access this module');
        }
        $sale = Sale::with('customer')->findOrFail($sale);
        $order = RestaurantOrder::with(['items', 'diningTable', 'waitress'])->where('sale_id', $sale->id)->first();
        $lines = Product_Sale::where('sale_id', $sale->id)->get();
        $staff = RestaurantAccess::usersWithPermission('waiter-orders');

        return view('restaurant.confirmation', compact('sale', 'order', 'lines', 'staff'));
    }

    public function logs(Request $request)
    {
        if (! RestaurantAccess::can($request->user(), 'order-logs')) {
            return redirect('/')->with('not_permitted', 'Sorry! You are not allowed to access this module');
        }
        $query = RestaurantOrderItem::with(['order.sale.customer', 'order.diningTable', 'order.placedBy', 'order.waitress', 'acceptedBy', 'readyBy', 'deliveredBy']);
        if ($request->filled('lane') && isset(RestaurantAccess::lanes()[$request->input('lane')])) {
            $lane = $request->input('lane');
            if (! RestaurantAccess::seeLane($request->user(), $lane)) {
                return redirect('/')->with('not_permitted', 'Sorry! You are not allowed to access this module');
            }
            RestaurantAccess::applyLane($query, $lane);
        }
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->input('date'));
        }
        if (in_array($request->input('department'), ['food', 'drinks'], true)) {
            $query->where('department', $request->input('department'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('table')) {
            $table = $request->input('table');
            $query->whereHas('order', function ($q) use ($table) {
                $q->where('restaurant_table_id', $table);
            });
        }
        if ($request->filled('staff')) {
            $staff = (int) $request->input('staff');
            $query->where(function ($q) use ($staff) {
                $q->where('accepted_by', $staff)->orWhere('ready_by', $staff)->orWhere('delivered_by', $staff)
                    ->orWhereHas('order', function ($order) use ($staff) {
                        $order->where('placed_by_user_id', $staff)->orWhere('waitress_user_id', $staff);
                    });
            });
        }
        $items = $query->orderByDesc('id')->limit(300)->get();
        $rejections = MealRejection::with('user')->orderByDesc('id')->limit(100)->get();
        $tables = RestaurantTable::orderBy('name')->get();
        $staffList = collect();
        foreach (['kitchen-orders', 'bar-orders', 'waiter-orders', 'order-logs', 'sales-add'] as $permission) {
            $staffList = $staffList->merge(RestaurantAccess::usersWithPermission($permission));
        }
        $staffList = $staffList->unique('id')->sortBy('name')->values();

        return view('restaurant.logs', compact('items', 'rejections', 'tables', 'staffList'));
    }

    public function tables(Request $request)
    {
        if (! RestaurantAccess::canSeeOrders($request->user()) && ! RestaurantAccess::can($request->user(), 'sales-add')) {
            return redirect('/')->with('not_permitted', 'Sorry! You are not allowed to access this module');
        }
        $tables = RestaurantTable::query()->get()->sortBy(function ($table) {
            return preg_match('/(\d+)/', (string) $table->name, $match) ? (int) $match[1] : 100000;
        })->values();

        return view('restaurant.tables', compact('tables'));
    }

    public function storeTable(Request $request)
    {
        if (! RestaurantAccess::can($request->user(), 'order-logs')
            && ! RestaurantAccess::can($request->user(), 'sales-add')
            && ! RestaurantAccess::can($request->user(), 'waiter-orders')) {
            return redirect('/')->with('not_permitted', 'Sorry! You are not allowed to access this module');
        }
        $name = trim((string) $request->input('name'));
        if ($name === '') {
            return back()->with('not_permitted', 'Table name is required.');
        }
        $capacity = $request->filled('capacity') ? (int) $request->input('capacity') : null;
        $table = RestaurantTable::create([
            'name' => $name,
            'description' => $request->input('description') ?: null,
            'capacity' => $capacity > 0 ? $capacity : null,
            'is_active' => 1,
        ]);
        if ($request->expectsJson() || $request->ajax()) {
            $label = $table->name;
            if ($table->description) {
                $label .= ' · '.$table->description;
            }
            if ($table->capacity) {
                $label .= ' ('.$table->capacity.' persons)';
            }

            return response()->json(['id' => $table->id, 'name' => $table->name, 'label' => $label]);
        }

        return back()->with('message', 'Table added.');
    }

    protected function item($id)
    {
        return RestaurantOrderItem::with('order')->findOrFail($id);
    }

    protected function denyOrder(Request $request, RestaurantOrderItem $item, $action)
    {
        if (RestaurantAccess::canOrder($request->user(), $action, $item)) {
            return null;
        }

        return redirect('/')->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

    protected function markPopupOpened(array $ids, $userId)
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (! $ids || ! \Schema::hasTable('restaurant_order_items') || ! \Schema::hasColumn('restaurant_order_items', 'popup_opened_at')) {
            return;
        }
        \DB::table('restaurant_order_items')
            ->whereIn('id', $ids)
            ->whereNull('popup_opened_at')
            ->update([
                'popup_opened_at' => date('Y-m-d H:i:s'),
                'popup_opened_by' => $userId ?: null,
            ]);
    }
}
