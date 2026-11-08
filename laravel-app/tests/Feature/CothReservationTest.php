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
        $this->artisan('migrate', [
            '--path' => 'database/migrations/2026_10_28_120000_coth_visuals_and_chairs.php',
            '--force' => true,
        ]);
        $this->artisan('migrate', [
            '--path' => 'database/migrations/2026_11_07_184800_add_coth_multi_day_stays.php',
            '--force' => true,
        ]);
    }

    public function test_two_days_take_five_percent_and_hold_both_dates()
    {
        $table = CothTable::where('code', 'YARD-T4-01')->first();
        $service = app(CothReservationService::class);
        $guest = $this->guest('full');
        $guest['nights'] = 2;
        $reservation = $service->hold($table, '2026-12-24', $guest);

        $this->assertSame(2, (int) $reservation->nights);
        $this->assertSame(5, (int) $reservation->stay_discount_percent);
        $this->assertSame(800000, (int) $reservation->list_price);
        $this->assertSame(40000, (int) $reservation->discount_amount);
        $this->assertSame(760000, (int) $reservation->final_price);
        $this->assertSame('held', CothSlot::where('coth_table_id', $table->id)->whereDate('event_date', '2026-12-24')->value('status'));
        $this->assertSame('held', CothSlot::where('coth_table_id', $table->id)->whereDate('event_date', '2026-12-25')->value('status'));
        $this->assertSame('open', CothSlot::where('coth_table_id', $table->id)->whereDate('event_date', '2026-12-26')->value('status'));
    }

    public function test_stay_discounts_step_up_to_eight_days()
    {
        $pricing = app(CothPricing::class);
        $this->assertSame(0, $pricing->stayPercent(1));
        $this->assertSame(5, $pricing->stayPercent(2));
        $this->assertSame(10, $pricing->stayPercent(3));
        $this->assertSame(15, $pricing->stayPercent(4));
        $this->assertSame(20, $pricing->stayPercent(5));
        $this->assertSame(25, $pricing->stayPercent(6));
        $this->assertSame(30, $pricing->stayPercent(7));
        $this->assertSame(35, $pricing->stayPercent(8));
    }

    public function test_inventory_matches_the_gray_and_teal_arrangement()
    {
        $this->assertSame(43, CothTable::count());
        $this->assertSame(251, (int) CothTable::sum('capacity'));
        $this->assertSame(22, CothTable::where('seating_style', 'couch')->count());
        $this->assertSame(12, CothTable::where('code', 'like', 'HALL-%')->where('seating_style', 'couch')->count());
        $this->assertSame(5, CothTable::where('code', 'like', 'YARD-T6-%')->where('seating_style', 'high_table')->count());
        $this->assertSame(1, CothTable::where('code', 'YARD-T6-01')->where('seating_style', 'couch')->count());
        $this->assertSame(0, CothTable::where('code', 'like', 'POOL-T4-%')->where('seating_style', 'couch')->count());
        $this->assertSame(1, CothTable::where('code', 'POOL-T6-01')->where('seating_style', 'couch')->count());
        $this->assertSame(600000, (int) CothTable::where('code', 'HALL-T8-01')->value('price'));
        $this->assertSame(43 * 8, CothSlot::count());
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

    public function test_a_longer_stay_blocks_later_days_and_confirm_books_each_one()
    {
        $table = CothTable::where('code', 'POOL-T2-01')->first();
        $service = app(CothReservationService::class);
        $guest = $this->guest('full');
        $guest['nights'] = 3;
        $reservation = $service->hold($table, '2026-12-24', $guest);

        $this->assertSame(['2026-12-24', '2026-12-25', '2026-12-26'], $service->stayDates($reservation));
        $blocked = false;
        try {
            $service->hold($table, '2026-12-25', $this->guest());
        } catch (\InvalidArgumentException $e) {
            $blocked = strpos($e->getMessage(), 'not available') !== false;
        }
        $this->assertTrue($blocked);

        $this->assertSame('confirmed', $service->applyGateway($reservation, true, (int) $reservation->booking_total, 'pay-stay', 'momo'));
        foreach (['2026-12-24', '2026-12-25', '2026-12-26'] as $day) {
            $this->assertSame('booked', CothSlot::where('coth_table_id', $table->id)->whereDate('event_date', $day)->value('status'));
        }
        $this->assertSame('open', CothSlot::where('coth_table_id', $table->id)->whereDate('event_date', '2026-12-27')->value('status'));
        $this->assertSame('24 Dec, 25 Dec, 26 Dec 2026 · 3 open days · 10% off', $reservation->fresh()->stayLabel());
    }

    public function test_an_expired_stay_reopens_every_day()
    {
        $table = CothTable::where('code', 'POOL-T2-02')->first();
        $service = app(CothReservationService::class);
        $guest = $this->guest();
        $guest['nights'] = 2;
        $held = $service->hold($table, '2026-12-24', $guest);
        $held->hold_expires_at = now()->subMinute();
        $held->save();
        $service->releaseExpired();
        $held->refresh();

        $this->assertSame('expired', $held->status);
        $this->assertSame('open', CothSlot::where('coth_table_id', $table->id)->whereDate('event_date', '2026-12-24')->value('status'));
        $this->assertSame('open', CothSlot::where('coth_table_id', $table->id)->whereDate('event_date', '2026-12-25')->value('status'));
    }

    public function test_five_open_days_skip_the_closure_and_discount_chairs()
    {
        $table = CothTable::where('code', 'YARD-T4-02')->first();
        $service = app(CothReservationService::class);
        $choices = $service->stayChoices($table, '2026-12-24');
        $this->assertSame(8, count($choices));
        $this->assertSame(['28 Dec', '29 Dec'], $choices[4]['closed']);
        $guest = $this->guest('deposit');
        $guest['nights'] = 5;
        $guest['extra_chairs'] = 1;
        $reservation = $service->hold($table, '2026-12-24', $guest);

        $this->assertSame(
            ['2026-12-24', '2026-12-25', '2026-12-26', '2026-12-27', '2026-12-30'],
            $service->stayDates($reservation)
        );
        $this->assertSame(2000000, (int) $reservation->list_price);
        $this->assertSame(400000, (int) $reservation->discount_amount);
        $this->assertSame(1600000, (int) $reservation->final_price);
        $this->assertSame(200000, (int) $reservation->extra_chairs_amount);
        $this->assertSame(1800000, (int) $reservation->booking_total);
        $this->assertSame(360000, (int) $reservation->deposit_due);
        $this->assertSame(360000, (int) $reservation->amount_due);
        $label = $reservation->stayLabel();
        $this->assertNotFalse(strpos($label, '5 open days'));
        $this->assertNotFalse(strpos($label, '20% off'));
        $this->assertNotFalse(strpos($label, '24 Dec'));
        $this->assertNotFalse(strpos($label, '30 Dec 2026'));
        $this->assertFalse(strpos($label, '28 Dec'));
    }

    public function test_a_taken_day_stops_the_stay_before_it()
    {
        $table = CothTable::where('code', 'HALL-T4-01')->first();
        $service = app(CothReservationService::class);
        $service->hold($table, '2026-12-26', $this->guest());
        $choices = $service->stayChoices($table, '2026-12-24');

        $this->assertSame([1, 2], array_map(function ($row) {
            return $row['nights'];
        }, $choices));
    }

    public function test_extra_chairs_cannot_exceed_the_tightest_day()
    {
        $table = CothTable::where('code', 'YARD-T4-03')->first();
        \App\CothExtraChairInventory::create([
            'event_date' => '2026-12-25',
            'total_capacity' => 1,
            'held' => 1,
            'booked' => 0,
        ]);
        $service = app(CothReservationService::class);
        $choices = $service->stayChoices($table, '2026-12-24');
        $this->assertSame(0, $choices[1]['chairs']);

        $guest = $this->guest();
        $guest['nights'] = 2;
        $guest['extra_chairs'] = 1;
        $blocked = false;
        try {
            $service->hold($table, '2026-12-24', $guest);
        } catch (\InvalidArgumentException $e) {
            $blocked = strpos($e->getMessage(), '25 Dec 2026') !== false;
        }
        $this->assertTrue($blocked);
        $this->assertSame('open', CothSlot::where('coth_table_id', $table->id)->whereDate('event_date', '2026-12-24')->value('status'));
        $this->assertSame('open', CothSlot::where('coth_table_id', $table->id)->whereDate('event_date', '2026-12-25')->value('status'));
    }

    public function test_different_tables_on_chosen_days_are_one_payment()
    {
        $pool = CothTable::where('code', 'POOL-T2-01')->first();
        $hall = CothTable::where('code', 'HALL-T4-01')->first();
        $service = app(CothReservationService::class);
        $reservation = $service->holdPlan([
            ['table' => $pool, 'date' => '2026-12-24', 'chairs' => 0],
            ['table' => $hall, 'date' => '2026-12-31', 'chairs' => 0],
        ], $this->guest('full'));

        $this->assertSame(2, (int) $reservation->nights);
        $this->assertSame(5, (int) $reservation->stay_discount_percent);
        $this->assertSame(450000, (int) $reservation->list_price);
        $this->assertSame(22500, (int) $reservation->discount_amount);
        $this->assertSame(427500, (int) $reservation->booking_total);
        $this->assertSame('held', CothSlot::where('coth_table_id', $pool->id)->whereDate('event_date', '2026-12-24')->value('status'));
        $this->assertSame('held', CothSlot::where('coth_table_id', $hall->id)->whereDate('event_date', '2026-12-31')->value('status'));
        $this->assertSame('open', CothSlot::where('coth_table_id', $pool->id)->whereDate('event_date', '2026-12-25')->value('status'));
        $this->assertSame('open', CothSlot::where('coth_table_id', $hall->id)->whereDate('event_date', '2026-12-24')->value('status'));

        $this->assertSame('confirmed', $service->applyGateway($reservation, true, 427500, 'plan-pay', 'card'));
        $this->assertSame('booked', CothSlot::where('coth_table_id', $pool->id)->whereDate('event_date', '2026-12-24')->value('status'));
        $this->assertSame('booked', CothSlot::where('coth_table_id', $hall->id)->whereDate('event_date', '2026-12-31')->value('status'));
        $this->assertNotFalse(strpos($reservation->fresh()->stayLabel(), '31 Dec 2026'));
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
