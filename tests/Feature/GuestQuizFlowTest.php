<?php

namespace Tests\Feature;

use App\Models\Merchant;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestQuizFlowTest extends TestCase
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
            'merchant_id' => $this->merchant->id, 'title' => 'Q', 'total_questions' => 2,
            'pass_threshold_pct' => 50, 'status' => 'active', 'reward_mode' => 'flat',
        ]);

        for ($i = 0; $i < 2; $i++) {
            $q = Question::create([
                'merchant_id' => $this->merchant->id,
                'title'       => ['az' => "Sual {$i}"],
                'type'        => 'mcq',
                'is_active'   => true,
            ]);
            QuestionOption::create(['question_id' => $q->id, 'option_text' => ['az' => 'Düz'], 'is_correct' => true, 'position' => 1]);
            QuestionOption::create(['question_id' => $q->id, 'option_text' => ['az' => 'Səhv'], 'is_correct' => false, 'position' => 2]);
            $this->quiz->questions()->attach($q->id, ['weight' => 1]);
        }
    }

    private function correctAnswersFor(array $questions): array
    {
        return collect($questions)->map(fn ($q) => [
            'question_id' => $q['id'],
            'option_id'   => Question::find($q['id'])->options()->where('is_correct', true)->first()->id,
        ])->all();
    }

    public function test_guest_can_start_and_finish_quiz_but_gets_no_coupon(): void
    {
        // Qonaq (token yoxdur) sessiya başlada bilir
        $start = $this->postJson('/api/v1/quiz-sessions', [
            'merchant_id' => $this->merchant->id,
            'quiz_id'     => $this->quiz->id,
        ])->assertCreated()->json();

        $this->assertNotNull($start['guest_token']);
        $this->assertNotEmpty($start['questions']);

        // Cavabları guest_token ilə göndərir
        $submit = $this->postJson("/api/v1/quiz-sessions/{$start['session_id']}/answers", [
            'guest_token' => $start['guest_token'],
            'answers'     => $this->correctAnswersFor($start['questions']),
        ])->assertOk()->json();

        $this->assertTrue($submit['is_passed']);
        $this->assertNull($submit['coupon']);                       // qonaq kupon almır
        $this->assertTrue($submit['requires_registration']);        // qeydiyyat tələbi
        $this->assertEquals('percent', $submit['reward_preview']['discount_type']);
    }

    public function test_guest_cannot_submit_without_token(): void
    {
        $start = $this->postJson('/api/v1/quiz-sessions', [
            'merchant_id' => $this->merchant->id,
            'quiz_id'     => $this->quiz->id,
        ])->assertCreated()->json();

        $this->postJson("/api/v1/quiz-sessions/{$start['session_id']}/answers", [
            'answers' => $this->correctAnswersFor($start['questions']),
        ])->assertForbidden();
    }

    public function test_guest_claims_session_after_register_and_receives_coupon(): void
    {
        $start = $this->postJson('/api/v1/quiz-sessions', [
            'merchant_id' => $this->merchant->id,
            'quiz_id'     => $this->quiz->id,
        ])->json();

        $this->postJson("/api/v1/quiz-sessions/{$start['session_id']}/answers", [
            'guest_token' => $start['guest_token'],
            'answers'     => $this->correctAnswersFor($start['questions']),
        ])->assertOk();

        // Qeydiyyat
        $reg = $this->postJson('/api/v1/customer/register', [
            'name' => 'Test', 'phone' => '0501234567', 'password' => 'secret1',
        ])->assertCreated()->json();

        // Claim → kupon
        $claim = $this->withToken($reg['token'])
            ->postJson("/api/v1/quiz-sessions/{$start['session_id']}/claim", [
                'guest_token' => $start['guest_token'],
            ])->assertOk()->json();

        $this->assertNotNull($claim['coupon']);
        $this->assertEquals('10.00', $claim['coupon']['value']);

        // İkinci claim idempotentdir — eyni kupon
        $claim2 = $this->withToken($reg['token'])
            ->postJson("/api/v1/quiz-sessions/{$start['session_id']}/claim", [
                'guest_token' => $start['guest_token'],
            ])->assertOk()->json();

        $this->assertEquals($claim['coupon']['code'], $claim2['coupon']['code']);
    }

    public function test_claim_with_wrong_token_fails(): void
    {
        $start = $this->postJson('/api/v1/quiz-sessions', [
            'merchant_id' => $this->merchant->id,
            'quiz_id'     => $this->quiz->id,
        ])->json();

        $reg = $this->postJson('/api/v1/customer/register', [
            'name' => 'T2', 'phone' => '0501234568', 'password' => 'secret1',
        ])->json();

        $this->withToken($reg['token'])
            ->postJson("/api/v1/quiz-sessions/{$start['session_id']}/claim", [
                'guest_token' => 'yanlis-token',
            ])->assertForbidden();
    }

    public function test_another_customer_cannot_claim_already_claimed_session(): void
    {
        $start = $this->postJson('/api/v1/quiz-sessions', [
            'merchant_id' => $this->merchant->id, 'quiz_id' => $this->quiz->id,
        ])->json();

        $this->postJson("/api/v1/quiz-sessions/{$start['session_id']}/answers", [
            'guest_token' => $start['guest_token'],
            'answers'     => $this->correctAnswersFor($start['questions']),
        ]);

        $a = $this->postJson('/api/v1/customer/register', ['name' => 'A', 'phone' => '0501111111', 'password' => 'secret1'])->json();
        $b = $this->postJson('/api/v1/customer/register', ['name' => 'B', 'phone' => '0502222222', 'password' => 'secret1'])->json();

        $this->withToken($a['token'])
            ->postJson("/api/v1/quiz-sessions/{$start['session_id']}/claim", ['guest_token' => $start['guest_token']])
            ->assertOk();

        // test prosesində guard cache-lənir — real həyatda hər sorğu təzədir
        auth('customer')->forgetUser();

        $this->withToken($b['token'])
            ->postJson("/api/v1/quiz-sessions/{$start['session_id']}/claim", ['guest_token' => $start['guest_token']])
            ->assertStatus(409);
    }

    public function test_logged_in_customer_flow_still_issues_coupon_directly(): void
    {
        $reg = $this->postJson('/api/v1/customer/register', [
            'name' => 'C', 'phone' => '0503333333', 'password' => 'secret1',
        ])->json();

        $start = $this->withToken($reg['token'])->postJson('/api/v1/quiz-sessions', [
            'merchant_id' => $this->merchant->id, 'quiz_id' => $this->quiz->id,
        ])->assertCreated()->json();

        $this->assertNull($start['guest_token']); // login sessiyasında guest token yoxdur

        $submit = $this->withToken($reg['token'])
            ->postJson("/api/v1/quiz-sessions/{$start['session_id']}/answers", [
                'answers' => $this->correctAnswersFor($start['questions']),
            ])->assertOk()->json();

        $this->assertNotNull($submit['coupon']); // dərhal kupon
        $this->assertFalse($submit['requires_registration']);
    }

    public function test_quiz_payload_contains_reward_info(): void
    {
        $start = $this->postJson('/api/v1/quiz-sessions', [
            'merchant_id' => $this->merchant->id, 'quiz_id' => $this->quiz->id,
        ])->json();

        $this->assertEquals('flat', $start['quiz']['reward_mode']);
        $this->assertEquals('percent', $start['quiz']['flat_reward']['discount_type']);
    }
}
