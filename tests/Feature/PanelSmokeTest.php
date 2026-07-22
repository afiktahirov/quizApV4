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
        // qalanları uğurla açılmalıdır. 'merchants' merchant_admin-ə açıqdır
        // (öz mağazasının ünvan/profil məlumatını özü idarə edir).
        $adminOnly = ['customers', 'quiz-categories', 'question-categories', 'plans'];

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
}
