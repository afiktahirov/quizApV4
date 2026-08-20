<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerPlan;
use App\Models\Merchant;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Services\CustomerSubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * İstifadəçi abunəlik qapısı — config/subscriptions.php → customer.gate:
 *   'claim' → oynamaq sərbəst, kupon üçün abunəlik lazımdır
 *   'play'  → sessiyanı başlatmaq üçün abunəlik lazımdır
 */
class CustomerSubscriptionGateTest extends TestCase
{
    use RefreshDatabase;

    private Merchant $merchant;
    private Quiz $quiz;

    protected function setUp(): void
    {
        parent::setUp();

        $this->merchant = Merchant::create([
            'name' => 'M', 'slug' => 'm-' . uniqid(), 'status' => 'active',
            'coupon_discount_type' => 'percent', 'coupon_value' => 10, 'coupon_ttl_hours' => 48,
        ]);

        $this->quiz = Quiz::create([
            'merchant_id' => $this->merchant->id,
            'title' => ['az' => 'Q', 'en' => 'Q', 'ru' => 'Q'],
            'total_questions' => 1, 'pass_threshold_pct' => 50,
            'status' => 'active', 'reward_mode' => 'flat',
        ]);

        $question = Question::create([
            'merchant_id' => $this->merchant->id,
            'title' => ['az' => 'Sual'], 'type' => 'mcq', 'is_active' => true,
        ]);
        QuestionOption::create(['question_id' => $question->id, 'option_text' => ['az' => 'Düz'], 'is_correct' => true, 'position' => 1]);
        QuestionOption::create(['question_id' => $question->id, 'option_text' => ['az' => 'Səhv'], 'is_correct' => false, 'position' => 2]);
        $this->quiz->questions()->attach($question->id, ['weight' => 1]);
    }

    private function customer(): Customer
    {
        return Customer::create([
            'name' => 'Test', 'phone' => '99450' . random_int(1000000, 9999999), 'password' => Hash::make('secret'),
        ]);
    }

    private function plan(float $price = 4.99): CustomerPlan
    {
        return CustomerPlan::create([
            'name' => 'Standart', 'slug' => 'standart-' . uniqid(),
            'price' => $price, 'currency' => 'AZN', 'billing_period' => 'monthly', 'is_active' => true,
        ]);
    }

    /** Sessiyanı başladıb düz cavabla bitirir, submit cavabını qaytarır */
    private function playThrough(Customer $customer): array
    {
        $start = $this->actingAs($customer, 'customer')
            ->postJson('/api/v1/quiz-sessions', [
                'merchant_id' => $this->merchant->id,
                'quiz_id'     => $this->quiz->id,
            ])->assertCreated()->json();

        $answers = collect($start['questions'])->map(fn ($q) => [
            'question_id' => $q['id'],
            'option_id'   => Question::find($q['id'])->options()->where('is_correct', true)->first()->id,
        ])->all();

        return $this->actingAs($customer, 'customer')
            ->postJson("/api/v1/quiz-sessions/{$start['session_id']}/answers", ['answers' => $answers])
            ->assertOk()->json();
    }

    public function test_claim_gate_blocks_coupon_without_subscription(): void
    {
        config(['subscriptions.customer.enabled' => true, 'subscriptions.customer.gate' => 'claim']);

        $result = $this->playThrough($this->customer());

        $this->assertNull($result['coupon']);
        $this->assertTrue($result['requires_subscription']);
        $this->assertNotNull($result['reward_preview']);
    }

    public function test_claim_gate_issues_coupon_for_subscribed_customer(): void
    {
        config(['subscriptions.customer.enabled' => true, 'subscriptions.customer.gate' => 'claim']);

        $customer = $this->customer();
        app(CustomerSubscriptionService::class)->grant($customer, $this->plan(), 1);

        $result = $this->playThrough($customer->fresh());

        $this->assertNotNull($result['coupon']);
        $this->assertFalse($result['requires_subscription']);
    }

    public function test_play_gate_blocks_session_start_without_subscription(): void
    {
        config(['subscriptions.customer.enabled' => true, 'subscriptions.customer.gate' => 'play']);

        $this->actingAs($this->customer(), 'customer')
            ->postJson('/api/v1/quiz-sessions', [
                'merchant_id' => $this->merchant->id,
                'quiz_id'     => $this->quiz->id,
            ])
            ->assertStatus(402)
            ->assertJsonPath('code', 'subscription_required');
    }

    public function test_gate_off_keeps_guest_flow_untouched(): void
    {
        config(['subscriptions.customer.enabled' => false]);

        $this->postJson('/api/v1/quiz-sessions', [
            'merchant_id' => $this->merchant->id,
            'quiz_id'     => $this->quiz->id,
        ])->assertCreated();
    }

    public function test_customer_plans_endpoint_is_public(): void
    {
        $this->plan();

        $this->getJson('/api/v1/customer-plans')
            ->assertOk()
            ->assertJsonCount(1, 'plans')
            ->assertJsonPath('plans.0.name', 'Standart');
    }

    public function test_free_trial_plan_activates_without_payment(): void
    {
        $customer = $this->customer();

        $trial = CustomerPlan::create([
            'name' => 'Sınaq', 'slug' => 'trial-' . uniqid(), 'price' => 0,
            'currency' => 'AZN', 'billing_period' => 'trial', 'trial_days' => 7, 'is_active' => true,
        ]);

        $this->actingAs($customer, 'customer')
            ->postJson('/api/v1/customer/subscription', ['plan_id' => $trial->id])
            ->assertOk()
            ->assertJsonPath('status', 'activated')
            ->assertJsonPath('subscription.is_active', true);

        $this->assertTrue($customer->fresh()->hasActiveSubscription());
    }
}
