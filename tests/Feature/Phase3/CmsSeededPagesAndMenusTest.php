<?php

namespace Tests\Feature\Phase3;

use Database\Seeders\CmsDefaultPagesSeeder;
use Database\Seeders\SiteBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsSeededPagesAndMenusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SiteBootstrapSeeder::class);
        $this->seed(CmsDefaultPagesSeeder::class);
    }

    public function test_all_seeded_cms_pages_render_server_side_with_dynamic_menus(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Explore the Karakoram, Valleys, Heritage &amp; Coast of Pakistan', false)
            ->assertSee('Gilgit-Baltistan')
            ->assertSee('Azad Jammu &amp; Kashmir', false)
            ->assertSee('Islamabad Capital Territory')
            ->assertSee('Driver Always Included')
            ->assertSee('Request Quote')
            ->assertDontSee('$0');

        $this->get('/about')->assertOk()->assertSee('End-to-End Accountability on Every Journey');
        $this->get('/contact')->assertOk()->assertSee('Request a Custom Pakistan Tour Quotation');
        $this->get('/terms')->assertOk()->assertSee('Terms &amp; Conditions', false);
        $this->get('/privacy')->assertOk()->assertSee('Privacy Policy');
        $this->get('/faqs')->assertOk()->assertSee('Is a driver included in company vehicle rental rates?');
    }

    public function test_public_contact_form_validates_consent_and_accepts_valid_inquiry(): void
    {
        $this->from('/contact')
            ->post(route('cms.contact.submit'), [
                'name' => 'Amina Raza',
                'email' => 'amina@example.com',
                'message' => 'We would like a 7-day Hunza tour quote for 4 travelers.',
            ])
            ->assertRedirect('/contact')
            ->assertSessionHasErrors('consent');

        $this->from('/contact')
            ->post(route('cms.contact.submit'), [
                'name' => 'Amina Raza',
                'email' => 'amina@example.com',
                'phone' => '+923001112233',
                'preferred_dates' => 'July 2027',
                'message' => 'We would like a 7-day Hunza tour quote for 4 travelers with a Prado SUV.',
                'consent' => '1',
            ])
            ->assertRedirect('/contact')
            ->assertSessionHas('contact_status');
    }
}
