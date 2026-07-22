<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\MerchantBasicSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $this->seed(\Database\Seeders\PlanSeeder::class);
        $this->seed(MerchantBasicSeeder::class);
    }

    public static function resourcePages(): array
    {
        return [
            ['quizzes'],
            ['questions'],
            ['question-categories'],
            ['quiz-categories'],
            ['quiz-sessions'],
            ['merchants'],
            ['users'],
            ['customers'],
            ['ads'],
            ['stores'],
            ['coupons'],
            ['plans'],
            ['subscription-requests'],
        ];
    }

    /** @dataProvider resourcePages */
    public function test_super_admin_can_open_resource_pages(string $slug): void
    {
        $admin = User::where('email', 'superadmin@quizapp.test')->firstOrFail();

        $this->actingAs($admin)
            ->get('/' . $slug)
            ->assertSuccessful();
    }

    /** @dataProvider resourcePages */
    public function test_merchant_admin_resource_pages(string $slug): void
    {
        $user = User::where('email', 'admin@example.com')->firstOrFail();

        $response = $this->actingAs($user)->get('/' . $slug);

        // super_admin-only səhifələr merchant üçün 403 qaytarmalıdır,
        // qalanları uğurla açılmalıdır. Merchant öz mağazasını "Mağazam" (magazam)
        // səhifəsindən idarə edir, "Mağazalar" resursu isə yalnız super admin-ə açıqdır.
        $adminOnly = ['merchants', 'customers', 'quiz-categories', 'question-categories', 'plans', 'subscription-requests'];

        if (in_array($slug, $adminOnly, true)) {
            $response->assertForbidden();
        } else {
            $response->assertSuccessful();
        }
    }

    public function test_cashier_can_open_coupons(): void
    {
        $user = User::where('email', 'cashier@example.com')->firstOrFail();

        $this->actingAs($user)->get('/coupons')->assertSuccessful();
    }

    public function test_merchant_admin_can_open_own_store_page(): void
    {
        $user = User::where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($user)->get('/magazam')->assertSuccessful();
    }

    public function test_super_admin_cannot_open_merchant_store_page(): void
    {
        $admin = User::where('email', 'superadmin@quizapp.test')->firstOrFail();

        $this->actingAs($admin)->get('/magazam')->assertForbidden();
    }

    public function test_cashier_cannot_open_merchant_store_page(): void
    {
        $user = User::where('email', 'cashier@example.com')->firstOrFail();

        $this->actingAs($user)->get('/magazam')->assertForbidden();
    }

    public function test_merchant_admin_can_open_own_subscription_page(): void
    {
        $user = User::where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($user)->get('/abuneliyim')->assertSuccessful();
    }

    public function test_super_admin_cannot_open_merchant_subscription_page(): void
    {
        $admin = User::where('email', 'superadmin@quizapp.test')->firstOrFail();

        $this->actingAs($admin)->get('/abuneliyim')->assertForbidden();
    }

    public function test_cashier_cannot_open_merchant_subscription_page(): void
    {
        $user = User::where('email', 'cashier@example.com')->firstOrFail();

        $this->actingAs($user)->get('/abuneliyim')->assertForbidden();
    }
}
