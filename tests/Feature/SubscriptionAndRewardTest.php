<?php

namespace Tests\Feature;

use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Quiz;
use App\Models\QuizRewardTier;
use App\Models\QuizSession;
use App\Services\CouponService;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionAndRewardTest extends TestCase
{
    use RefreshDatabase;

    private function makeMerchant(array $attrs = []): Merchant
    {
        return Merchant::create(array_merge([
            'name'                 => 'Test M',
            'slug'                 => 'test-m-' . uniqid(),
            'status'               => 'active',
            'coupon_discount_type' => 'percent',
            'coupon_value'         => 10,
            'coupon_ttl_hours'     => 48,
        ], $attrs));
    }

    private function makeSession(Quiz $quiz, int $correct, bool $passed): QuizSession
    {
        return QuizSession::create([
            'merchant_id'   => $quiz->merchant_id,
            'quiz_id'       => $quiz->id,
            'question_ids'  => [1, 2, 3, 4, 5],
            'started_at'    => now(),
            'finished_at'   => now(),
            'score_pct'     => $correct * 20,
            'correct_count' => $correct,
            'is_passed'     => $passed,
        ]);
    }

    public function test_tiered_reward_picks_highest_reached_tier(): void
    {
        $m    = $this->makeMerchant();
        $quiz = Quiz::create([
            'merchant_id' => $m->id, 'title' => 'Q', 'total_questions' => 5,
            'pass_threshold_pct' => 60, 'status' => 'active', 'reward_mode' => 'tiered',
        ]);
        QuizRewardTier::create(['quiz_id' => $quiz->id, 'min_correct' => 3, 'discount_type' => 'percent', 'value' => 5]);
        QuizRewardTier::create(['quiz_id' => $quiz->id, 'min_correct' => 5, 'discount_type' => 'percent', 'value' => 15]);

        $svc = app(CouponService::class);

        // 4 düz → çatılan ən yüksək pillə min_correct=3 → 5%
        $coupon = $svc->issueForSession($this->makeSession($quiz, 4, true));
        $this->assertNotNull($coupon);
        $this->assertEquals('5.00', $coupon->value);

        // 5 düz → 15%
        $coupon2 = $svc->issueForSession($this->makeSession($quiz, 5, true));
        $this->assertEquals('15.00', $coupon2->value);

        // 2 düz → heç bir pilləyə çatmır → kupon yoxdur
        $this->assertNull($svc->issueForSession($this->makeSession($quiz, 2, false)));
    }

    public function test_flat_reward_requires_pass_and_uses_merchant_value(): void
    {
        $m    = $this->makeMerchant(['coupon_value' => 20]);
        $quiz = Quiz::create([
            'merchant_id' => $m->id, 'title' => 'Q', 'total_questions' => 5,
            'pass_threshold_pct' => 60, 'status' => 'active', 'reward_mode' => 'flat',
        ]);
        $svc = app(CouponService::class);

        $this->assertNull($svc->issueForSession($this->makeSession($quiz, 2, false)));

        $coupon = $svc->issueForSession($this->makeSession($quiz, 4, true));
        $this->assertNotNull($coupon);
        $this->assertEquals('20.00', $coupon->value);
    }

    public function test_issue_is_idempotent_per_session(): void
    {
        $m    = $this->makeMerchant();
        $quiz = Quiz::create([
            'merchant_id' => $m->id, 'title' => 'Q', 'total_questions' => 5,
            'pass_threshold_pct' => 60, 'status' => 'active', 'reward_mode' => 'flat',
        ]);
        $svc     = app(CouponService::class);
        $session = $this->makeSession($quiz, 4, true);

        $a = $svc->issueForSession($session);
        $b = $svc->issueForSession($session->fresh());

        $this->assertEquals($a->id, $b->id);
    }

    public function test_plan_limit_blocks_further_creation(): void
    {
        $plan = Plan::create([
            'name' => 'Solo', 'slug' => 'solo', 'price' => 10, 'currency' => 'AZN',
            'billing_period' => 'monthly', 'max_quizzes' => 1,
        ]);
        $m = $this->makeMerchant(['plan_id' => $plan->id]);

        $this->assertTrue($m->canAdd('quizzes'));

        Quiz::create([
            'merchant_id' => $m->id, 'title' => 'Q1', 'total_questions' => 5,
            'pass_threshold_pct' => 60, 'status' => 'active',
        ]);

        $this->assertFalse($m->fresh()->canAdd('quizzes'));
    }

    public function test_no_plan_means_unlimited(): void
    {
        $m = $this->makeMerchant(); // plan_id yoxdur
        $this->assertTrue($m->canAdd('quizzes'));
        $this->assertNull($m->planLimit('quizzes'));
    }

    public function test_grant_extends_subscription_and_logs_revenue(): void
    {
        $plan = Plan::create([
            'name' => 'Std', 'slug' => 'std', 'price' => 60, 'currency' => 'AZN',
            'billing_period' => 'monthly',
        ]);
        $m = $this->makeMerchant(['subscription_ends_at' => null]);

        $sub = app(SubscriptionService::class)->grant($m, $plan, 2);

        $m->refresh();
        $this->assertEquals($plan->id, $m->plan_id);
        $this->assertTrue($m->subscription_ends_at->greaterThan(now()->addMonth()));
        $this->assertEquals('120.00', $sub->amount);          // 60 * 2 dövr
        $this->assertEquals(1, $m->subscriptions()->count());
        $this->assertTrue($m->isSubscribed());
    }

    public function test_block_and_unblock(): void
    {
        $m   = $this->makeMerchant();
        $svc = app(SubscriptionService::class);

        $svc->block($m);
        $this->assertEquals('inactive', $m->fresh()->status);
        $this->assertFalse($m->fresh()->isSubscribed());

        $svc->unblock($m);
        $this->assertEquals('active', $m->fresh()->status);
    }

    public function test_request_upgrade_creates_pending_request(): void
    {
        $plan = Plan::create([
            'name' => 'Pro', 'slug' => 'pro', 'price' => 50, 'currency' => 'AZN',
            'billing_period' => 'monthly',
        ]);
        $m   = $this->makeMerchant();
        $svc = app(SubscriptionService::class);

        $request = $svc->requestUpgrade($m, $plan, 3);

        $this->assertEquals('pending', $request->status);
        $this->assertEquals('150.00', $request->amount); // 50 * 3 dövr
        $this->assertEquals(1, $m->subscriptionRequests()->count());
    }

    public function test_cannot_request_upgrade_while_a_request_is_pending(): void
    {
        $plan = Plan::create([
            'name' => 'Pro', 'slug' => 'pro', 'price' => 50, 'currency' => 'AZN',
            'billing_period' => 'monthly',
        ]);
        $m   = $this->makeMerchant();
        $svc = app(SubscriptionService::class);

        $svc->requestUpgrade($m, $plan, 1);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $svc->requestUpgrade($m, $plan, 1);
    }

    public function test_approve_request_grants_subscription(): void
    {
        $plan = Plan::create([
            'name' => 'Pro', 'slug' => 'pro', 'price' => 50, 'currency' => 'AZN',
            'billing_period' => 'monthly',
        ]);
        $m       = $this->makeMerchant(['subscription_ends_at' => null]);
        $admin   = \App\Models\User::create([
            'name' => 'Admin', 'email' => 'admin-' . uniqid() . '@test.com',
            'password' => 'x', 'role' => \App\Models\User::ROLE_SUPER_ADMIN,
        ]);
        $svc     = app(SubscriptionService::class);
        $request = $svc->requestUpgrade($m, $plan, 1);

        $svc->approve($request, $admin);

        $this->assertEquals('approved', $request->fresh()->status);
        $this->assertEquals($admin->id, $request->fresh()->reviewed_by);
        $this->assertEquals($plan->id, $m->fresh()->plan_id);
        $this->assertTrue($m->fresh()->isSubscribed());
    }

    public function test_reject_request_does_not_change_merchant_plan(): void
    {
        $plan = Plan::create([
            'name' => 'Pro', 'slug' => 'pro', 'price' => 50, 'currency' => 'AZN',
            'billing_period' => 'monthly',
        ]);
        $m       = $this->makeMerchant();
        $admin   = \App\Models\User::create([
            'name' => 'Admin', 'email' => 'admin-' . uniqid() . '@test.com',
            'password' => 'x', 'role' => \App\Models\User::ROLE_SUPER_ADMIN,
        ]);
        $svc     = app(SubscriptionService::class);
        $request = $svc->requestUpgrade($m, $plan, 1);

        $svc->reject($request, $admin, 'test səbəbi');

        $this->assertEquals('rejected', $request->fresh()->status);
        $this->assertNull($m->fresh()->plan_id);
    }

    public function test_cancel_request_allows_new_request(): void
    {
        $plan = Plan::create([
            'name' => 'Pro', 'slug' => 'pro', 'price' => 50, 'currency' => 'AZN',
            'billing_period' => 'monthly',
        ]);
        $m       = $this->makeMerchant();
        $svc     = app(SubscriptionService::class);
        $request = $svc->requestUpgrade($m, $plan, 1);

        $svc->cancelRequest($request);
        $this->assertEquals('cancelled', $request->fresh()->status);

        // artıq gözləyən sorğu yoxdur, yenisi yaradıla bilər
        $second = $svc->requestUpgrade($m, $plan, 2);
        $this->assertEquals('pending', $second->status);
    }
}
