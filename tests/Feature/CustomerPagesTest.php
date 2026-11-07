<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_pages_render_successfully(): void
    {
        foreach (['home', 'ketersediaan-jadwal', 'booking', 'cek-status'] as $routeName) {
            $this->get(route($routeName))->assertOk();
        }
    }

    public function test_customer_pages_do_not_render_login_button(): void
    {
        foreach (['home', 'ketersediaan-jadwal', 'booking', 'cek-status'] as $routeName) {
            $this->get(route($routeName))
                ->assertOk()
                ->assertDontSee('class="btn-login"');
        }
    }

    public function test_customer_navigation_keeps_all_public_links(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Beranda')
            ->assertSee('Jadwal')
            ->assertSee('Booking')
            ->assertSee('Cek Status');
    }

    public function test_login_page_renders_successfully(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Masuk');
    }
}
