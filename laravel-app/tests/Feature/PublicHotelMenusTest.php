<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicHotelMenusTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    public function test_public_header_pages_render()
    {
        $this->artisan('migrate', [
            '--path' => 'database/migrations/2026_10_23_100000_create_guest_reviews_and_membership.php',
            '--force' => true,
        ]);

        $home = $this->get('/');
        $home->assertStatus(200);
        $home->assertSee('Become a Member');
        $home->assertSee('Reviews');
        $home->assertSee('Chill on the Hill');
        $home->assertDontSee('Apply Now');

        $this->get('/register-now')->assertStatus(200)->assertSee('Become a Member')->assertSee('sky-menus');
        $this->get('/reviews')->assertStatus(200)->assertSee('Add a review')->assertSee('Chill on the Hill');
        $this->get('/services')->assertStatus(200)->assertSee('Restaurant')->assertSee('Coffee')->assertSee('sky-bar');
        $this->get('/services/restaurant')->assertStatus(200)->assertSee('Choose quantities');

        $png = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';
        $this->post('/register-now', [
            'plan_choice' => 'promo',
            'full_name' => 'Amina Hotel',
            'phone' => '675321739',
            'id_type' => 'national_id',
            'id_number' => 'ID123456',
            'id_expires_on' => '2030-01-01',
            'nationality' => 'Cameroon',
            'id_photo_data' => $png,
            'selfie_data' => $png,
            'accept_agreement' => '1',
            'signature' => $png,
        ])->assertRedirect();
        $this->assertSame(1, \Illuminate\Support\Facades\DB::table('membership_applications')->count());
        $this->assertEquals(0, (float) \Illuminate\Support\Facades\DB::table('membership_applications')->value('fee'));
    }
}
