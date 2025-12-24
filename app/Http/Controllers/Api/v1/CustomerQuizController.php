<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Quiz;
use App\Models\QuizQuestionMap;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\QuizSession;
use App\Models\Answer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CustomerQuizController extends Controller
{
    /**
     * REGISTER – customers cədvəlində qeydiyyat + token
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'phone'    => 'required|string|max:50|unique:customers,phone',
            'password' => 'required|string|min:4',
        ]);

        $customer = Customer::create([
            'name'     => $data['name'],
            'phone'    => $data['phone'],
            'password' => Hash::make($data['password']),
        ]);

        $token = $customer->createToken('customer-api')->plainTextToken;

        return response()->json([
            'customer' => [
                'id'    => $customer->id,
                'name'  => $customer->name,
                'phone' => $customer->phone,
            ],
            'token' => $token,
        ], 201);
    }

    /**
     * LOGIN – customers cədvəlindən
     */
    public function login(Request $request)
    {
        $data = $request->validate([
            'phone'    => 'required|string',
            'password' => 'required|string',
        ]);

        $customer = Customer::where('phone', $data['phone'])->first();

        if (! $customer || ! Hash::check($data['password'], $customer->password)) {
            return response()->json([
                'message' => 'Telefon və ya şifrə yanlışdır',
            ], 422);
        }

        $token = $customer->createToken('customer-api')->plainTextToken;

        return response()->json([
            'customer' => [
                'id'    => $customer->id,
                'name'  => $customer->name,
                'phone' => $customer->phone,
            ],
            'token' => $token,
        ]);
    }

    /**
     * QUIZ GÖSTƏR – hələ public saxlayıram (istəsən auth:customer edərsən)
     */
    public function showQuiz(Request $request)
    {
        $request->validate([
            'merchant_id' => 'required|integer',
            'quiz_id'     => 'required|integer',
            'channel'     => 'nullable|string',
        ]);

        $quiz = Quiz::where('id', $request->quiz_id)
            ->where('merchant_id', $request->merchant_id)
            ->where('status', 'active')
            ->firstOrFail();

        $questionIds = QuizQuestionMap::where('quiz_id', $quiz->id)
            ->pluck('question_id');

        $questions = Question::whereIn('id', $questionIds)->get();

        $questions = $questions->map(function ($q) {
            $options = QuestionOption::where('question_id', $q->id)->get(['id', 'option_text']);

            return [
                'id'      => $q->id,
                'title'   => $q->title,
                'type'    => $q->type,
                'options' => $options,
            ];
        });

        return response()->json([
            'quiz' => [
                'id'                    => $quiz->id,
                'title'                 => $quiz->title,
                'time_per_question_sec' => $quiz->time_per_question_sec,
                'total_questions'       => $quiz->total_questions,
                'pass_threshold_pct'    => $quiz->pass_threshold_pct,
            ],
            'questions' => $questions,
        ]);
    }

    /**
     * QUIZ BAŞLAT – burda artıq auth:customer işləyir
     */
//    public function startQuiz(Request $request)
//    {
//        /** @var \App\Models\Customer $customer */
//        $customer = $request->user('customer'); // 👈 customer guard-dan gəlir
//
//        $data = $request->validate([
//            'merchant_id'        => 'required|integer',
//            'quiz_id'            => 'required|integer',
//            'channel'            => 'nullable|string',
//            'device_fingerprint' => 'nullable|string',
//            'ip'                 => 'nullable|ip',
//            'store_id'           => 'nullable|integer',
//        ]);
//
//        $session = QuizSession::create([
//            'customer_id'        => $customer->id,
//            'merchant_id'        => $data['merchant_id'],
//            'quiz_id'            => $data['quiz_id'],
//            'store_id'           => $data['store_id'] ?? null,
//            'channel'            => $data['channel'] ?? 'qr',
//            'device_fingerprint' => $data['device_fingerprint'] ?? null,
//            'ip'                 => $data['ip'] ?? $request->ip(),
//            'started_at'         => now(),
//        ]);
//
//        return response()->json([
//            'session_id' => $session->id,
//        ]);
//    }

    public function startQuiz(Request $request)
    {
        /** @var \App\Models\Customer $customer */
        $customer = $request->user('customer'); // customer guard

        $data = $request->validate([
            'merchant_id'        => 'required|integer',
            'quiz_id'            => 'required|integer',
            'channel'            => 'nullable|string',
            'device_fingerprint' => 'nullable|string',
            'ip'                 => 'nullable|ip',
            'store_id'           => 'nullable|integer',
        ]);

        // 1) Sessiyanı yarat
        $session = QuizSession::create([
            'customer_id'        => $customer->id,
            'merchant_id'        => $data['merchant_id'],
            'quiz_id'            => $data['quiz_id'],
            'store_id'           => $data['store_id'] ?? null,
            'channel'            => $data['channel'] ?? 'qr',
            'device_fingerprint' => $data['device_fingerprint'] ?? null,
            'ip'                 => $data['ip'] ?? $request->ip(),
            'started_at'         => now(),
        ]);

        // 2) Quiz + ona bağlı suallar + hər sualın option-ları
        $quiz = Quiz::with(['questions.options'])
            ->where('id', $data['quiz_id'])
            ->where('status', 'active')
            ->firstOrFail();

        // 3) Front üçün rahat struktur hazırla
        $questions = $quiz->questions->map(function ($q) {
            return [
                'id'      => $q->id,
                'title'   => $q->title,   // JSON-dursa frontda dilə görə açacaqsan
                'type'    => $q->type,
                'options' => $q->options->map(function ($opt) {
                    return [
                        'id'   => $opt->id,
                        'text' => $opt->option_text,
                    ];
                })->values(),
            ];
        });

        return response()->json([
            'session_id' => $session->id,
            'quiz' => [
                'id'                    => $quiz->id,
                'title'                 => $quiz->title,
                'time_per_question_sec' => $quiz->time_per_question_sec,
                'total_questions'       => $quiz->total_questions,
                'pass_threshold_pct'    => $quiz->pass_threshold_pct,
            ],
            'questions' => $questions,
        ]);
    }
    /**
     * CAVABLAR + NƏTİCƏ
     */
    public function submitAnswers(Request $request, QuizSession $session)
    {
        /** @var \App\Models\Customer $customer */
        $customer = $request->user('customer');

        if ($session->customer_id !== $customer->id) {
            return response()->json(['message' => 'Bu sessiya sizə aid deyil'], 403);
        }

        $data = $request->validate([
            'answers'                 => 'required|array',
            'answers.*.question_id'   => 'required|integer',
            'answers.*.option_id'     => 'required|integer',
        ]);

        DB::transaction(function () use ($session, $data) {

            Answer::where('quiz_session_id', $session->id)->delete();

            $correctCount = 0;
            $total = count($data['answers']);

            foreach ($data['answers'] as $item) {
                $option = QuestionOption::findOrFail($item['option_id']);

                $isCorrect = (bool) $option->is_correct;
                if ($isCorrect) {
                    $correctCount++;
                }

                Answer::create([
                    'quiz_session_id' => $session->id,
                    'question_id'     => $item['question_id'],
                    'option_id'       => $item['option_id'],
                    'is_correct'      => $isCorrect,
                ]);
            }

            $scorePct = $total > 0 ? round($correctCount * 100 / $total) : 0;

            $quiz = $session->quiz; // QuizSession modelində quiz() relation olacaq
            $isPassed = $scorePct >= $quiz->pass_threshold_pct;

            $session->update([
                'score_pct'   => $scorePct,
                'is_passed'   => $isPassed,
                'finished_at' => now(),
            ]);
        });

        $session->refresh();

        return response()->json([
            'score_pct' => $session->score_pct,
            'is_passed' => (bool) $session->is_passed,
        ]);
    }

    public function result(Request $request, QuizSession $session)
    {
        /** @var \App\Models\Customer $customer */
        $customer = $request->user('customer');

        if ($session->customer_id !== $customer->id) {
            return response()->json(['message' => 'Bu sessiya sizə aid deyil'], 403);
        }

        return response()->json([
            'id'         => $session->id,
            'score_pct'  => $session->score_pct,
            'is_passed'  => (bool) $session->is_passed,
            'started_at' => $session->started_at,
            'finished_at'=> $session->finished_at,
        ]);
    }
}
