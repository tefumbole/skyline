<?php

namespace App\Http\Controllers;

use App\Category;
use App\Product;
use App\Product_Sale;
use App\RestaurantOrder;
use App\RestaurantTable;
use App\Sale;
use App\Services\Restaurant\RestaurantDepartment;
use App\Services\Restaurant\RestaurantOrderService;
use Illuminate\Http\Request;

class RestaurantMenuController extends Controller
{
    public function show()
    {
        $groups = ['food' => [], 'drinks' => []];
        $products = Product::where('is_active', true)->orderBy('name')->get();
        foreach ($products as $product) {
            $department = RestaurantDepartment::forCategoryId($product->category_id);
            if ($department) {
                $groups[$department][] = $product;
            }
        }
        $tables = RestaurantTable::where('is_active', 1)->orderBy('name')->get();
        $token = bin2hex(random_bytes(16));

        return view('restaurant.menu', compact('groups', 'tables', 'token'));
    }

    public function checkout(Request $request, RestaurantOrderService $orders)
    {
        $request->validate([
            'name' => 'required|string|max:191',
            'phone' => 'required|string|max:30',
            'service_type' => 'required|in:dine_in,take_away',
            'checkout_token' => 'required|string|max:64',
        ]);
        if ($request->input('service_type') === 'dine_in' && ! $request->input('restaurant_table_id')) {
            return back()->withInput()->with('not_permitted', 'Choose a table for dine in.');
        }

        $items = [];
        $productIds = (array) $request->input('product_id', []);
        $qtys = (array) $request->input('qty', []);
        $comments = (array) $request->input('item_comment', []);
        foreach ($productIds as $i => $productId) {
            $items[] = [
                'product_id' => $productId,
                'qty' => isset($qtys[$i]) ? $qtys[$i] : 0,
                'comment' => isset($comments[$i]) ? $comments[$i] : null,
            ];
        }

        try {
            $sale = $orders->placePublic([
                'name' => $request->input('name'),
                'phone' => $request->input('phone'),
                'country' => $request->input('country'),
                'service_type' => $request->input('service_type'),
                'restaurant_table_id' => $request->input('restaurant_table_id'),
                'customer_comment' => $request->input('customer_comment'),
                'checkout_token' => $request->input('checkout_token'),
                'items' => $items,
            ]);
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('not_permitted', $e->getMessage());
        } catch (\Throwable $e) {
            \Log::error('restaurant checkout: '.$e->getMessage());

            return back()->withInput()->with('not_permitted', 'The order could not be placed. Please try again.');
        }

        if (! $sale) {
            return back()->withInput()->with('not_permitted', 'The order could not be placed.');
        }

        return redirect()->route('restaurant.public.confirm', [
            'sale' => $sale->id,
            'token' => optional($sale->restaurantOrder)->token,
        ]);
    }

    public function confirmation($sale, $token)
    {
        $sale = Sale::with('customer')->findOrFail($sale);
        $order = RestaurantOrder::with(['items', 'diningTable'])->where('sale_id', $sale->id)->firstOrFail();
        if (! hash_equals($order->token, (string) $token)) {
            abort(404);
        }
        $lines = Product_Sale::where('sale_id', $sale->id)->get();

        return view('restaurant.confirmation', [
            'sale' => $sale,
            'order' => $order,
            'lines' => $lines,
            'staff' => collect(),
            'public' => true,
        ]);
    }
}
