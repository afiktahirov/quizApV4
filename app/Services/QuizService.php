<?php
namespace App\Services;

use App\Models\{Quiz, QuizSession, Question, Answer, QuestionOption};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class QuizService
{
    public function startSession(Quiz $quiz, int $merchantId, int $storeId, ?int $userId, string $ip = null, string $device = null): QuizSession
    {
        return QuizSession::create([
            'merchant_id' => $merchantId,
            'store_id' => $storeId,
            'quiz_id' => $quiz->id,
            'user_id' => $userId,
            'started_at' => now(),
            'ip' => $ip,
            'device_fingerprint' => $device,
            'channel' => 'qr',
        ]);
    }

    public function submitAnswer(QuizSession $session, Question $question, ?QuestionOption $selected): Answer
    {
        $isCorrect = $selected?->is_correct ?? false;
        return Answer::create([
            'quiz_session_id' => $session->id,
            'question_id' => $question->id,
            'selected_option_id' => $selected?->id,
            'is_correct' => $isCorrect,
            'answered_at' => now(),
        ]);
    }

    public function finish(QuizSession $session): array
    {
        $total = $session->answers()->count();
        $correct = $session->answers()->where('is_correct', true)->count();
        $score = $total > 0 ? (int) floor(($correct / $total) * 100) : 0;

        $session->update([
            'finished_at' => now(),
            'score_pct' => $score,
            'is_passed' => $score >= $session->quiz->pass_threshold_pct,
        ]);

        return ['total' => $total, 'correct' => $correct, 'score_pct' => $score, 'passed' => $session->is_passed];
    }
}
