<?php

namespace Tests\Feature;

use App\CothReservation;
use App\CothSlot;
use App\CothTable;
use App\Services\BeyondAuthService;
use App\Services\Coth\CothPricing;
use App\Services\Coth\CothReservationService;
use Carbon\Carbon;
use Tests\TestCase;

class CothReservationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', [
            '--path' => 'database/migrations/2026_10_27_180000_create_coth_reservations.php',
            '--force' => true,
        ]);
        $this->artisan('migrate', [
            '--path' => 'database/migrations/2026_07_10_235500_create_be_auth_tables.php',
            '--force' => true,
        ]);
    }

    public function test_inventory_is_42_tables_and_237_seats()
    {
        $this->assertSame(42, CothTable::count());
        $this->assertSame(237, (int) CothTable::sum('capacity'));
        $this->assertSame(4, CothTable::where('seating_style', 'couch')->count());
        $this->assertSame(2, CothTable::where('code', 'like', 'HALL-T6-%')->where('seating_style', 'couch')->count());
        $this->assertSame(1, CothTable::where('code', 'like', 'POOL-T4-%')->where('seating_style', 'couch')->count());
        $this->assertSame(1, CothTable::where('code', 'POOL-T6-01')->where('seating_style', 'couch')->count());
        $this->assertSame(42 * 8, CothSlot::count());
    }

    public function test_discount_depends_on_the_event_date_not_the_booking_day()
    {
        $pricing = app(CothPricing::class);
        $settings = app(CothReservationService::class)->settings();
        Carbon::setTestNow(Carbon::parse('2026-10-01'));
        $discounted = $pricing->quote(500000, '2026-12-26', $settings);
        $regular = $pricing->quote(500000, '2026-12-24', $settings);
        $after = $pricing->quote(500000, '2026-12-31', $settings);
        Carbon::setTestNow();

        $this->assertSame(0, $discounted['discount_amount']);
        $this->assertSame(500000, $discounted['final_price']);
        $this->assertSame(100000, $discounted['deposit_due']);
        $this->assertSame(0, $regular['discount_amount']);
        $this->assertSame(500000, $regular['final_price']);
        $this->assertSame(100000, $regular['deposit_due']);
        $this->assertSame(0, $after['discount_amount']);
        foreach (['2026-12-26', '2026-12-27', '2026-12-30'] as $date) {
            $this->assertSame(0, $pricing->quote(500000, $date, $settings)['discount_amount']);
        }
    }

    public function test_a_table_can_be_held_on_two_dates_but_not_twice_on_one_date()
    {
        $table = CothTable::where('code', 'YARD-T4-01')->first();
        $service = app(CothReservationService::class);
        $first = $service->hold($table, '2026-12-24', $this->guest());
        $second = $service->hold($table, '2026-12-25', $this->guest());
        $this->assertNotSame($first->coth_slot_id, $second->coth_slot_id);

        $this->expectException(\InvalidArgumentException::class);
        $service->hold($table, '2026-12-24', $this->guest());
    }

    public function test_failed_payment_does_not_confirm_and_expired_payment_is_flagged()
    {
        $table = CothTable::where('code', 'YARD-T4-01')->first();
        $service = app(CothReservationService::class);
        $held = $service->hold($table, '2026-12-24', $this->guest());
        $this->assertSame('failed', $service->applyGateway($held, false, 0, 'fail-1', 'momo'));
        $held->refresh();
        $this->assertSame('held', $held->status);
        $this->assertSame('failed', $held->payment_status);

        $held->hold_expires_at = now()->subMinute();
        $held->save();
        $service->releaseExpired();
        $held->refresh();
        $this->assertSame('expired', $held->status);
        $this->assertSame('open', CothSlot::find($held->coth_slot_id)->status);

        $this->assertSame('flagged', $service->applyGateway($held, true, (int) $held->amount_due, 'late-1', 'momo'));
        $held->refresh();
        $this->assertSame('expired', $held->status);
        $this->assertSame('needs_reconciliation', $held->payment_status);
        $this->assertSame('open', CothSlot::find($held->coth_slot_id)->status);
    }

    public function test_confirmed_price_snapshot_survives_a_later_price_change()
    {
        $table = CothTable::where('code', 'YARD-T10-01')->first();
        $service = app(CothReservationService::class);
        $reservation = $service->hold($table, '2026-12-26', $this->guest('full'));
        $this->assertSame(0, (int) $reservation->discount_amount);
        $this->assertSame(1000000, (int) $reservation->final_price);
        $this->assertSame(200000, (int) $reservation->deposit_due);

        $table->price = 1000;
        $table->save();
        $this->assertSame('confirmed', $service->applyGateway($reservation, true, 1000000, 'pay-1', 'momo'));
        $saved = CothReservation::find($reservation->id);
        $this->assertSame(1000000, (int) $saved->list_price);
        $this->assertSame(1000000, (int) $saved->final_price);
        $this->assertSame('booked', CothSlot::find($saved->coth_slot_id)->status);
    }

    public function test_guest_review_otp_does_not_verify_a_reservation()
    {
        $auth = app(BeyondAuthService::class);
        $made = $auth->createOtp('675321739', 'guest_review');
        $wrong = $auth->verifyOtp('675321739', $made['code'], 'coth_reservation');
        $this->assertFalse($wrong['success']);
        $right = $auth->verifyOtp('675321739', $made['code'], 'guest_review');
        $this->assertTrue($right['success']);
    }

    protected function guest($pay = 'deposit')
    {
        return [
            'name' => 'Amina',
            'phone' => '675321739',
            'email' => 'amina@example.com',
            'pay_choice' => $pay,
            'terms' => 1,
        ];
    }
}
