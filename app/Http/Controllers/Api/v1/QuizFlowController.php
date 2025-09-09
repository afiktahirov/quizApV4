<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\{Quiz, QuizSession, Question, QuestionOption, Store};
use App\Services\{QuizService, CouponService};
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QuizFlowController extends Controller
{
    public function __construct(protected QuizService $quizService, protected CouponService $couponService) {}

    public function start(Request $req)
    {
        $data = $req->validate([
            'store_slug' => ['required', 'string'],
            'quiz_id' => ['required', 'integer', 'exists:quizzes,id'],
        ]);
        $store = Store::where('slug', $data['store_slug'])->firstOrFail();
        $quiz = Quiz::where('id', $data['quiz_id'])->where('merchant_id', $store->merchant_id)->where('status', 'active')->firstOrFail();

        $session = $this->quizService->startSession($quiz, merchantId: $store->merchant_id, storeId: $store->id, userId: auth()->id(), ip: $req->ip(), device: (string) $req->header('X-Device-Fingerprint'));

        // İlk sualların id-ləri – sıranı gizli saxlayın, UI bir-bir çəksin
        $questionIds = $quiz->questions()->inRandomOrder()->take($quiz->total_questions)->pluck('questions.id');
        $req->session()->put('qs:' . $session->id, $questionIds->values()->all());

        return response()->json([
            'session_id' => $session->id,
            'total_questions' => $quiz->total_questions,
        ]);
    }

    public function answer(QuizSession $session, Request $req)
    {
        $this->authorizeSession($session);
        $queue = $req->session()->get('qs:' . $session->id, []);
        abort_unless(count($queue) > 0, 404, 'Sual qalmayıb.');
        $questionId = (int) array_shift($queue);
        $req->session()->put('qs:' . $session->id, $queue);

        $question = Question::with('options')->findOrFail($questionId);
        $data = $req->validate([
            'selected_option_id' => ['nullable', 'integer', Rule::exists('question_options', 'id')->where('question_id', $question->id)],
        ]);
        $option = $data['selected_option_id'] ? $question->options->firstWhere('id', (int) $data['selected_option_id']) : null;

        $answer = $this->quizService->submitAnswer($session, $question, $option);

        return response()->json([
            'question_id' => $question->id,
            'is_correct' => (bool) $answer->is_correct,
            'remaining' => count($queue),
        ]);
    }

    public function finish(QuizSession $session)
    {
        $this->authorizeSession($session);
        $result = $this->quizService->finish($session);
        $coupon = null;
        if ($result['passed']) {
            $coupon = $this->couponService->issueForPassedSession($session);
        }
        return response()->json([
            'result' => $result,
            'coupon' => $coupon?->only(['code', 'expires_at', 'discount_type', 'value', 'qr_payload', 'status']),
        ]);
    }

    protected function authorizeSession(QuizSession $session): void
    {
        // Sadə tenant yoxlaması; istəsən JWT/OTP ilə fərdiləşdir.
        // Burada əlavə anti-fraud (IP/device) yoxlaması da edilə bilər.
    }
}
