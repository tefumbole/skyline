<?php

namespace App\Services\Restaurant;

use App\Cloud\CloudTenant;
use App\Deposit;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PublicDonateController;
use App\PublicDonation;
use App\Sale;
use App\Services\CampayCollectService;
use App\Services\CampayPayoutService;
use App\Services\Cloud\CloudTenantContext;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReceivedCollects
{
    public function settle()
    {
        if (! Cache::add('skyline.collects.settle', 1, 30)) {
            return;
        }
        $rows = app(CampayPayoutService::class)->confirmedCollections(
            date('Y-m-d', strtotime('-21 days')),
            date('Y-m-d')
        );
        $campay = app(CampayCollectService::class);
        foreach ($rows as $item) {
            $uuid = trim((string) (isset($item['reference_uuid']) ? $item['reference_uuid'] : ''));
            if ($uuid === '') {
                continue;
            }
            try {
                $body = $campay->transaction($uuid);
            } catch (\Throwable $e) {
                continue;
            }
            if (! is_array($body)) {
                continue;
            }
            $external = trim((string) (isset($body['external_reference']) ? $body['external_reference'] : ''));
            $landed = $this->landed($body, isset($item['credit']) ? $item['credit'] : 0);
            if ($landed <= 0) {
                continue;
            }
            $pattern = $external !== '' ? '/^'.preg_quote($external, '/').'$/' : null;
            $outcome = $campay->collectionOutcome($uuid, $landed, null, $pattern);
            if ($outcome['state'] !== 'paid' || empty($outcome['confirmed'])) {
                continue;
            }
            $this->applySale($external, $uuid, $landed);
            $this->applyDeposit($uuid);
            $this->applyDonation($uuid);
        }
    }

    protected function landed(array $body, $credit)
    {
        if (isset($body['app_amount']) && is_numeric($body['app_amount']) && (float) $body['app_amount'] > 0) {
            return (int) round((float) $body['app_amount']);
        }
        if (is_numeric($credit) && (float) $credit > 0) {
            return (int) round((float) $credit);
        }
        if (isset($body['amount']) && is_numeric($body['amount'])) {
            return (int) round((float) $body['amount']);
        }

        return 0;
    }

    protected function applySale($external, $reference, $landed)
    {
        $saleId = 0;
        if (preg_match('/^sale-momo-(\d+)/', (string) $external, $match)) {
            $saleId = (int) $match[1];
        } elseif (ctype_digit((string) $external)) {
            $saleId = (int) $external;
        }
        if ($saleId < 1) {
            return;
        }
        $row = DB::table('sales')->where('id', $saleId)->first();
        if (! $row || (int) $row->payment_status === 4) {
            return;
        }
        $due = max(0, (float) $row->grand_total - (float) $row->paid_amount);
        if ($due < 0.5 || abs($due - $landed) > 1) {
            return;
        }
        $this->useTenant(isset($row->cloud_tenant_id) ? $row->cloud_tenant_id : null);
        $sale = Sale::find($saleId);
        if (! $sale || RestaurantPayments::hasReference($sale->id, $reference)) {
            return;
        }
        RestaurantPayments::recordGateway($sale, $landed, $reference, $row->user_id);
    }

    protected function applyDeposit($reference)
    {
        if (! class_exists(Deposit::class)) {
            return;
        }
        $deposit = Deposit::where('campay_reference', $reference)->where('status', 0)->first();
        if ($deposit) {
            app(PaymentController::class)->settleFromGateway($deposit);
        }
    }

    protected function applyDonation($reference)
    {
        if (! class_exists(PublicDonation::class)) {
            return;
        }
        $donation = PublicDonation::where('campay_reference', $reference)->where('status', '<>', 'paid')->first();
        if ($donation) {
            app(PublicDonateController::class)->applyGateway($donation);
        }
    }

    protected function useTenant($tenantId)
    {
        if (! $tenantId) {
            return;
        }
        $tenant = CloudTenant::find($tenantId);
        if ($tenant) {
            app(CloudTenantContext::class)->set($tenant);
        }
    }
}
