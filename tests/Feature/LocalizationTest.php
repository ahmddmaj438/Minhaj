<?php

namespace Tests\Feature;

use Tests\TestCase;

class LocalizationTest extends TestCase
{
    public function test_language_switcher_stores_locale_and_redirects_to_current_page(): void
    {
        $response = $this->post('/language', [
            'locale' => 'ar',
            'redirect_to' => '/login',
        ]);

        $response
            ->assertRedirect('/login')
            ->assertSessionHas('locale', 'ar');
    }

    public function test_arabic_locale_renders_rtl_layout(): void
    {
        $response = $this
            ->withSession(['locale' => 'ar'])
            ->get('/login');

        $response
            ->assertOk()
            ->assertSee('lang="ar"', false)
            ->assertSee('dir="rtl"', false)
            ->assertSee('تسجيل الدخول', false);
    }
}
