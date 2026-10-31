<?php

namespace Tests\Unit;

use App\CothSetting;
use App\CothTable;
use App\Services\Assistant\AssistantToolRegistry;
use App\Services\Assistant\AssistantToolSelector;
use App\Services\Assistant\BeyondAssistantSystemPromptBuilder;
use App\Services\Assistant\SkylineGuestGuide;
use App\Services\Coth\CothCatalog;
use App\Services\Coth\CothPricing;
use Tests\TestCase;

class SkylineGuestGuideTest extends TestCase
{
    public function test_discounted_night_uses_the_existing_pricing_service()
    {
        $settings = new CothSetting();
        $settings->discount_percent = 20;
        $settings->deposit_percent = 20;
        $settings->discount_dates = json_encode(['2026-12-27']);
        $settings->discount_includes_addons = 0;
        $table = new CothTable();
        $table->capacity = 6;
        $table->extra_chair_rate = null;
        $table->max_extra_chairs = 0;

        $quote = app(CothPricing::class)->quote(600000, '2026-12-27', $settings, $table, 2);

        $this->assertSame(120000, $quote['discount_amount']);
        $this->assertSame(480000, $quote['final_price']);
        $this->assertSame(75000, $quote['extra_chair_rate']);
        $this->assertSame(150000, $quote['extra_chairs_amount']);
        $this->assertSame(630000, $quote['booking_total']);
        $this->assertSame(126000, $quote['deposit_due']);
    }

    public function test_a_night_outside_the_discount_list_keeps_the_table_price()
    {
        $settings = new CothSetting();
        $settings->discount_percent = 20;
        $settings->deposit_percent = 20;
        $settings->discount_dates = json_encode(['2026-12-27']);
        $settings->discount_includes_addons = 0;
        $table = new CothTable();
        $table->capacity = 6;

        $quote = app(CothPricing::class)->quote(600000, '2026-12-24', $settings, $table, 0);

        $this->assertSame(0, $quote['discount_amount']);
        $this->assertSame(600000, $quote['booking_total']);
        $this->assertSame(120000, $quote['deposit_due']);
    }

    public function test_event_dates_skip_28_and_29_december()
    {
        $dates = app(CothCatalog::class)->eventDates();

        $this->assertContains('2026-12-24', $dates);
        $this->assertContains('2027-01-02', $dates);
        $this->assertNotContains('2026-12-28', $dates);
        $this->assertNotContains('2026-12-29', $dates);
    }

    public function test_public_links_stay_on_guest_pages()
    {
        $guide = app(SkylineGuestGuide::class);
        $links = $guide->safeLinks([
            ['label' => 'View Tables', 'route' => 'coth.tables', 'query' => ['date' => '2026-12-27']],
            ['label' => 'Pay', 'route' => 'coth.payment', 'query' => ['token' => 'secret']],
            ['label' => 'Admin', 'route' => 'admin.home', 'query' => []],
        ]);

        $this->assertCount(1, $links);
        $this->assertSame('View Tables', $links[0]['label']);
        $this->assertStringContainsString('/chill-on-the-hill/tables?date=2026-12-27', $links[0]['url']);
        $this->assertStringNotContainsString('payment', $links[0]['url']);
    }

    public function test_closed_night_is_rejected()
    {
        $result = app(SkylineGuestGuide::class)->eventDate('28 December 2026');

        $this->assertSame('outside_event', $result['error']);
    }

    public function test_skyline_tools_are_read_only_and_offered()
    {
        $registry = app(AssistantToolRegistry::class);
        foreach (['search_menu_products', 'quote_coth_table', 'get_payment_methods', 'get_service_links'] as $name) {
            $tool = $registry->get($name);
            $this->assertNotNull($tool);
            $this->assertFalse($tool['write']);
        }
        $offered = app(AssistantToolSelector::class)->selectNames([], []);
        $this->assertContains('search_coth_tables', $offered);
        $this->assertContains('get_coth_event', $offered);
    }

    public function test_instructions_keep_the_existing_rules_and_add_skyline_rules()
    {
        $prompt = app(BeyondAssistantSystemPromptBuilder::class)->build();

        $this->assertStringContainsString('Do not introduce yourself', $prompt);
        $this->assertStringContainsString('You are Mbole AI, Skyline Hotel’s customer assistant', $prompt);
        $this->assertStringContainsString('Do not create quotations', $prompt);
        $this->assertStringContainsString('Respond in the customer’s language', $prompt);
        $this->assertStringContainsString('13:00', $prompt);
        $this->assertStringContainsString('Do not say the tables are unavailable', $prompt);
        $this->assertStringContainsString('Premium — Garden', $prompt);
    }

    public function test_dates_are_read_without_one_required_format()
    {
        $guide = app(SkylineGuestGuide::class);

        $this->assertSame('2026-12-24', $guide->eventDate('24th December')['date']);
        $this->assertSame('2026-12-24', $guide->eventDate('24/12/2026')['date']);
        $this->assertSame('2026-12-24', $guide->eventDate('Christmas Eve')['date']);
        $this->assertSame('2026-12-26', $guide->eventDate('the 26th')['date']);
        $this->assertSame('2027-01-01', $guide->eventDate('1 Jan')['date']);
    }

    public function test_a_table_list_without_a_night_is_not_reported_unavailable()
    {
        $menu = app(SkylineGuestGuide::class)->publishedMenu();

        $this->assertTrue($menu['success']);
        $this->assertStringContainsString('Do not say the tables are unavailable', $menu['instruction']);
        $this->assertStringContainsString('can be booked', $menu['message']);
        $this->assertNotEmpty($menu['sections']);
        $this->assertNotEmpty($menu['links']);
    }
}
