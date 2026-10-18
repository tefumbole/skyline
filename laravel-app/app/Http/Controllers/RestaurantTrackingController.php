<?php

namespace App\Http\Controllers;

use App\OrderReview;
use App\RestaurantOrder;
use App\Sale;
use App\Support\SalePayQr;
use Illuminate\Http\Request;

class RestaurantTrackingController extends Controller
{
    public function show($sale, $token)
    {
        $order = $this->order($sale, $token);
        $saleModel = Sale::with('customer')->findOrFail($sale);
        $payUrl = SalePayQr::payUrl($saleModel);

        return view('restaurant.track', [
            'order' => $order,
            'sale' => $saleModel,
            'payUrl' => $payUrl,
            'review' => OrderReview::where('sale_id', $saleModel->id)->first(),
        ]);
    }

    public function review($sale, $token)
    {
        $order = $this->order($sale, $token);
        $saleModel = Sale::findOrFail($sale);
        $existing = OrderReview::where('sale_id', $saleModel->id)->first();

        return view('restaurant.review', [
            'order' => $order,
            'sale' => $saleModel,
            'existing' => $existing,
        ]);
    }

    public function storeReview(Request $request, $sale, $token)
    {
        $order = $this->order($sale, $token);
        $saleModel = Sale::findOrFail($sale);
        if (OrderReview::where('sale_id', $saleModel->id)->exists()) {
            return redirect()->route('restaurant.review', [$saleModel->id, $order->token])
                ->with('message', 'This order has already been reviewed.');
        }
        $data = $request->validate([
            'guest_name' => 'required|string|max:191',
            'country' => 'required|string|max:191',
            'delivery_time' => 'required|integer|min:1|max:5',
            'food_quality' => 'required|integer|min:1|max:5',
            'environment' => 'required|integer|min:1|max:5',
            'services' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);
        $data['sale_id'] = $saleModel->id;
        OrderReview::create($data);

        return redirect()->route('restaurant.review', [$saleModel->id, $order->token])
            ->with('message', 'Thank you. Your review has been saved.');
    }

    protected function order($sale, $token)
    {
        $order = RestaurantOrder::with(['items', 'diningTable'])->where('sale_id', $sale)->firstOrFail();
        if (! hash_equals($order->token, (string) $token)) {
            abort(404);
        }

        return $order;
    }
}
