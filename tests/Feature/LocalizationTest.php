<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_language_can_be_switched_with_clean_get_route_and_cookie(): void
    {
        $response = $this->get('/lang/ar?redirect_to=/login');

        $response
            ->assertRedirect('/login')
            ->assertSessionHas('locale', 'ar')
            ->assertCookie('locale', 'ar');

        $this
            ->withCookie('locale', 'ar')
            ->get('/login')
            ->assertOk()
            ->assertSee('lang="ar"', false)
            ->assertSee('dir="rtl"', false);

        $this
            ->withSession(['locale' => 'ar'])
            ->get('/lang/en?redirect_to=/login')
            ->assertRedirect('/login')
            ->assertSessionHas('locale', 'en')
            ->assertCookie('locale', 'en');
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

    public function test_english_locale_renders_ltr_layout_without_arabic_labels(): void
    {
        $this
            ->withSession(['locale' => 'en'])
            ->get('/login')
            ->assertOk()
            ->assertSee('lang="en"', false)
            ->assertSee('dir="ltr"', false)
            ->assertSee('Log in')
            ->assertDontSee('تسجيل الدخول', false);
    }

    public function test_validation_messages_follow_selected_locale(): void
    {
        $this
            ->withSession(['locale' => 'ar'])
            ->from('/register')
            ->post('/register', [])
            ->assertRedirect('/register')
            ->assertSessionHasErrors([
                'name' => 'هذا الحقل مطلوب.',
                'email' => 'هذا الحقل مطلوب.',
                'password' => 'هذا الحقل مطلوب.',
            ]);

        $this
            ->withSession(['locale' => 'en'])
            ->from('/register')
            ->post('/register', [])
            ->assertRedirect('/register')
            ->assertSessionHasErrors([
                'name' => 'The name field is required.',
            ]);
    }

    public function test_reports_use_arabic_labels_when_locale_is_arabic(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->withSession(['locale' => 'ar'])
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('التقارير', false)
            ->assertSee('تقرير ملخص الاختبارات', false)
            ->assertSee('تصفية', false)
            ->assertDontSee('Reports')
            ->assertDontSee('Filter');
    }
}
