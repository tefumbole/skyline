<?php

namespace App\Services\Sales;

use App\Product_Sale;
use App\RestaurantOrder;
use App\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class PosDuplicateGuard
{
    /**
     * Stop a second identical POS order. A response means the caller must return it.
     *
     * @param  array<string, mixed>  $data
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|null
     */
    public function response(Request $request, array $data)
    {
        if (empty($data['pos']) || ! empty($data['draft'])) {
            return null;
        }
        $lines = $this->lines($data);
        if (! count($lines)) {
            return null;
        }

        $userId = (int) (Auth::id() ?: (isset($data['user_id']) ? $data['user_id'] : 0));
        $confirm = (string) $request->input('confirm_duplicate') === '1';
        $lock = 'pos-dup:'.$userId.':'.$this->hash($data, $lines).($confirm ? ':again' : '');
        if (! Cache::add($lock, 1, now()->addSeconds(12))) {
            return $this->busy($request);
        }
        if ($confirm || $userId < 1) {
            return null;
        }

        $sale = $this->match($data, $lines, $userId);
        if (! $sale) {
            return null;
        }
        Cache::forget($lock);

        return $this->warn($request, $sale);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, float>
     */
    protected function lines(array $data)
    {
        $ids = isset($data['product_id']) ? array_values((array) $data['product_id']) : [];
        $qtys = isset($data['qty']) ? array_values((array) $data['qty']) : [];
        $lines = [];
        foreach ($ids as $i => $id) {
            $id = (int) $id;
            if ($id < 1) {
                continue;
            }
            $qty = isset($qtys[$i]) ? (float) $qtys[$i] : 0;
            if (! isset($lines[$id])) {
                $lines[$id] = 0;
            }
            $lines[$id] += $qty;
        }
        ksort($lines);

        return $lines;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, float>  $lines
     */
    protected function hash(array $data, array $lines)
    {
        return substr(hash('sha256', json_encode([
            'customer' => (string) (isset($data['customer_id']) ? $data['customer_id'] : ''),
            'table' => (string) (isset($data['restaurant_table_id']) ? $data['restaurant_table_id'] : ''),
            'total' => (string) round((float) (isset($data['grand_total']) ? $data['grand_total'] : 0), 2),
            'lines' => $lines,
        ])), 0, 24);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, float>  $lines
     */
    protected function match(array $data, array $lines, $userId)
    {
        $grand = round((float) (isset($data['grand_total']) ? $data['grand_total'] : 0), 2);
        $query = Sale::where('user_id', $userId)
            ->where('created_at', '>=', now()->subMinutes(15))
            ->whereBetween('grand_total', [$grand - 0.5, $grand + 0.5]);
        if (! empty($data['customer_id'])) {
            $query->where('customer_id', (int) $data['customer_id']);
        }
        $sales = $query->orderByDesc('id')->limit(12)->get();
        $tableId = (int) (isset($data['restaurant_table_id']) ? $data['restaurant_table_id'] : 0);
        foreach ($sales as $sale) {
            if ($tableId && Schema::hasTable('restaurant_orders')) {
                $existing = (int) RestaurantOrder::where('sale_id', $sale->id)->value('restaurant_table_id');
                if ($existing && $existing !== $tableId) {
                    continue;
                }
            }
            if ($this->sameLines($sale->id, $lines)) {
                return $sale;
            }
        }

        return null;
    }

    /**
     * @param  array<int, float>  $lines
     */
    protected function sameLines($saleId, array $lines)
    {
        $saved = [];
        $rows = Product_Sale::where('sale_id', $saleId)->get(['product_id', 'qty']);
        foreach ($rows as $row) {
            $id = (int) $row->product_id;
            if (! isset($saved[$id])) {
                $saved[$id] = 0;
            }
            $saved[$id] += (float) $row->qty;
        }
        ksort($saved);
        if (array_keys($saved) !== array_keys($lines)) {
            return false;
        }
        foreach ($lines as $id => $qty) {
            if (abs($saved[$id] - $qty) > 0.001) {
                return false;
            }
        }

        return true;
    }

    protected function warn(Request $request, Sale $sale)
    {
        $message = 'This order was already placed as '.$sale->reference_no.'. It was not placed again.';
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'duplicate',
                'message' => $message,
                'sale_id' => (int) $sale->id,
                'reference' => (string) $sale->reference_no,
                'actions' => [
                    'invoice' => url('sales/gen_invoice/'.$sale->id),
                    'print' => url('sales/gen_invoice/'.$sale->id.'?autoprint=1'),
                ],
            ], 409);
        }

        return back()->with('duplicate_order', [
            'id' => (int) $sale->id,
            'reference' => (string) $sale->reference_no,
        ])->with('not_permitted', $message);
    }

    protected function busy(Request $request)
    {
        $message = 'This order is already being saved. Wait a moment before tapping again, and check Sales if you are not sure it went through.';
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'duplicate',
                'message' => $message,
            ], 409);
        }

        return back()->with('not_permitted', $message);
    }
}
