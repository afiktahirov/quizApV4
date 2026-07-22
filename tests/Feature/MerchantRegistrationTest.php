<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\MerchantRegister;
use App\Filament\Pages\MySubscription;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MerchantRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_page_renders(): void
    {
        $this->get('/register')->assertSuccessful();
    }

    public function test_merchant_can_self_register_and_gets_redirected_to_subscription_page(): void
    {
        $email = 'elvin-' . uniqid() . '@example.com';

        Livewire::test(MerchantRegister::class)
            ->fillForm([
                'merchant_name' => 'Test Restoranı',
                'address' => 'Bakı',
                'bio' => 'Test bio',
                'name' => 'Elvin',
                'email' => $email,
                'password' => 'Sifr12345!',
                'passwordConfirmation' => 'Sifr12345!',
            ])
            ->call('register')
            ->assertRedirect('/abuneliyim');

        $merchant = Merchant::where('name', 'Test Restoranı')->first();
        $this->assertNotNull($merchant);
        $this->assertNull($merchant->plan_id);

        $user = User::where('email', $email)->first();
        $this->assertNotNull($user);
        $this->assertEquals('merchant_admin', $user->role);
        $this->assertEquals($merchant->id, $user->merchant_id);
        $this->assertAuthenticatedAs($user);
    }

    public function test_newly_registered_merchant_sees_no_functional_resources_until_plan_chosen(): void
    {
        $email = 'sahib-' . uniqid() . '@example.com';

        Livewire::test(MerchantRegister::class)
            ->fillForm([
                'merchant_name' => 'Başqa Mağaza',
                'name' => 'Sahib',
                'email' => $email,
                'password' => 'Sifr12345!',
                'passwordConfirmation' => 'Sifr12345!',
            ])
            ->call('register');

        $user = User::where('email', $email)->first();

        $this->actingAs($user)->get('/quizzes')->assertForbidden();
        $this->actingAs($user)->get('/coupons')->assertForbidden();
        $this->actingAs($user)->get('/abuneliyim')->assertSuccessful();
        $this->actingAs($user)->get('/magazam')->assertSuccessful();
    }

    public function test_merchant_can_activate_free_trial_plan_without_payment(): void
    {
        $trial = Plan::create([
            'name' => '14 Günlük Sınaq', 'slug' => 'trial-14', 'price' => 0, 'currency' => 'AZN',
            'billing_period' => 'trial', 'trial_days' => 14, 'is_active' => true,
        ]);

        $email = 'aygun-' . uniqid() . '@example.com';

        Livewire::test(MerchantRegister::class)
            ->fillForm([
                'merchant_name' => 'Sınaq Mağazası',
                'name' => 'Aygün',
                'email' => $email,
                'password' => 'Sifr12345!',
                'passwordConfirmation' => 'Sifr12345!',
            ])
            ->call('register');

        $user = User::where('email', $email)->first();

        Livewire::actingAs($user)
            ->test(MySubscription::class)
            ->callAction('requestUpgrade', data: ['plan_id' => $trial->id, 'periods' => 1, 'save_card' => false]);

        $merchant = $user->merchant->fresh();
        $this->assertEquals($trial->id, $merchant->plan_id);
        $this->assertTrue($merchant->subscription_ends_at->between(now()->addDays(13), now()->addDays(15)));
        $this->assertTrue($merchant->isSubscribed());

        $this->actingAs($user)->get('/quizzes')->assertSuccessful();
    }
}
