<?php

namespace App\Http\Controllers;

use App\Category;
use App\Product;
use App\Product_Sale;
use App\RestaurantOrder;
use App\RestaurantTable;
use App\Sale;
use App\Services\Restaurant\RestaurantOrderService;
use Illuminate\Http\Request;

class RestaurantMenuController extends Controller
{
    public function show()
    {
        $sections = [];
        $parents = Category::whereNull('parent_id')->whereIn('name', ['Restaurant', 'Bar'])->get()->sortBy(function ($category) {
            return $category->name === 'Restaurant' ? 0 : 1;
        });
        foreach ($parents as $parent) {
            $blocks = [];
            $own = Product::where('is_active', true)->where('category_id', $parent->id)->orderBy('id')->get();
            if ($own->isNotEmpty()) {
                $blocks[] = ['title' => $parent->name, 'products' => $own];
            }
            foreach (Category::where('parent_id', $parent->id)->orderBy('id')->get() as $child) {
                $blocks[] = [
                    'title' => $child->name,
                    'products' => Product::where('is_active', true)->where('category_id', $child->id)->orderBy('id')->get(),
                ];
            }
            if ($blocks) {
                $sections[] = ['title' => $parent->name, 'blocks' => $blocks];
            }
        }
        $tables = RestaurantTable::where('is_active', 1)->orderBy('name')->get();
        $token = bin2hex(random_bytes(16));

        return view('restaurant.menu', compact('sections', 'tables', 'token'));
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
        foreach ($productIds as $i => $productId) {
            $items[] = [
                'product_id' => $productId,
                'qty' => isset($qtys[$i]) ? $qtys[$i] : 0,
            ];
        }

        try {
            $sale = $orders->placePublic([
                'name' => $request->input('name'),
                'phone' => $request->input('phone'),
                'country' => $request->input('country'),
                'service_type' => $request->input('service_type'),
                'restaurant_table_id' => $request->input('restaurant_table_id'),
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

    public function note(Request $request, $sale, $token)
    {
        $sale = Sale::findOrFail($sale);
        $order = RestaurantOrder::where('sale_id', $sale->id)->firstOrFail();
        if (! hash_equals((string) $order->token, (string) $token)) {
            abort(404);
        }

        $note = trim((string) $request->input('customer_comment', ''));
        if (strlen($note) > 500) {
            return back()->with('not_permitted', 'Keep the note under 500 characters.');
        }

        $order->customer_comment = $note !== '' ? $note : null;
        $order->save();
        $sale->sale_note = $order->customer_comment;
        $sale->save();

        $redirect = $request->input('next') === 'pay'
            ? redirect(\App\Support\SalePayQr::payUrl($sale))
            : redirect()->route('restaurant.public.confirm', [
                'sale' => $sale->id,
                'token' => $order->token,
            ]);

        if ($request->input('next') === 'pay' && $note === '') {
            return $redirect;
        }

        return $redirect->with('message', $note !== '' ? 'Note saved.' : 'Note cleared.');
    }
}
