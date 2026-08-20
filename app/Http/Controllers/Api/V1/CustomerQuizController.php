<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Quiz;
use App\Models\QuestionOption;
use App\Models\QuizSession;
use App\Models\Answer;
use App\Services\CouponService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CustomerQuizController extends Controller
{
    public function __construct(protected CouponService $couponService) {}

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

        return response()->json([
            'customer' => $this->customerPayload($customer),
            'token'    => $customer->createToken('customer-api')->plainTextToken,
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

        return response()->json([
            'customer' => $this->customerPayload($customer),
            'token'    => $customer->createToken('customer-api')->plainTextToken,
        ]);
    }

    /**
     * QUIZ GÖSTƏR – kampaniya haqqında ümumi məlumat (public).
     * Suallar sessiya başladıqda (startQuiz) verilir.
     */
    public function showQuiz(Request $request)
    {
        $request->validate([
            'merchant_id' => 'required|integer',
            'quiz_id'     => 'required|integer',
        ]);

        $merchant = Merchant::subscribed()->findOrFail($request->merchant_id);
        $quiz     = $this->findActiveQuiz($merchant, (int) $request->quiz_id);

        return response()->json([
            'quiz' => $this->quizPayload($quiz),
        ]);
    }

    /** Merchant-ın aktiv kampaniyalarının siyahısı (public) */
    public function merchantQuizzes(Request $request)
    {
        $request->validate(['merchant_id' => 'required|integer']);

        $merchant = Merchant::subscribed()->findOrFail($request->merchant_id);

        $quizzes = $merchant->quizzes()
            ->where('status', 'active')
            ->get()
            ->map(fn ($q) => $this->quizPayload($q))
            ->values();

        return response()->json(['quizzes' => $quizzes]);
    }

    /**
     * QUIZ BAŞLAT – sessiya yaradılır, suallar random seçilib sessiyada saxlanılır.
     *
     * Login tələb olunmur (QR axını): token göndərilibsə customer sessiyaya yazılır,
     * göndərilməyibsə qonaq sessiyası yaradılır və guest_token qaytarılır —
     * qonaq sonda qeydiyyatdan keçib sessiyanı claim edərək kupon qazanır.
     */
    public function startQuiz(Request $request)
    {
        /** @var \App\Models\Customer|null $customer */
        $customer = auth('customer')->user();

        $data = $request->validate([
            'merchant_id'        => 'required|integer',
            'quiz_id'            => 'required|integer',
            'channel'            => 'nullable|string|max:50',
            'device_fingerprint' => 'nullable|string|max:255',
            'store_id'           => 'nullable|integer|exists:stores,id',
        ]);

        // Abunəlik qapısı "play" rejimindədirsə oynamaq üçün aktiv abunəlik lazımdır
        if ($this->subscriptionGate() === 'play') {
            if (! $customer) {
                return $this->subscriptionRequired('Quiz oynamaq üçün daxil olub abunə olmalısınız.');
            }

            if (! $customer->hasActiveSubscription()) {
                return $this->subscriptionRequired('Quiz oynamaq üçün aktiv abunəliyiniz olmalıdır.');
            }
        }

        // Paket limiti: gündəlik quiz sayı
        if ($customer && $this->subscriptionGate() !== 'off' && ! $customer->canPlayMoreToday()) {
            return response()->json([
                'message' => 'Paketinizin gündəlik quiz limitinə çatmısınız. Sabah yenidən cəhd edin.',
                'code'    => 'daily_limit_reached',
            ], 429);
        }

        $merchant = Merchant::subscribed()->findOrFail($data['merchant_id']);
        $quiz     = $this->findActiveQuiz($merchant, (int) $data['quiz_id']);

        // store göndərilibsə həmin merchant-a aid olmalıdır
        if (! empty($data['store_id'])) {
            $storeOk = $merchant->stores()->whereKey($data['store_id'])->exists();
            abort_unless($storeOk, 422, 'Filial bu müəssisəyə aid deyil.');
        }

        // Sualları random seç (total_questions qədər), option-larla birlikdə
        $questions = $quiz->questions()
            ->where('is_active', true)
            ->with(['options' => fn ($q) => $q->orderBy('position')->orderBy('id')])
            ->inRandomOrder()
            ->take($quiz->total_questions)
            ->get();

        abort_if($questions->isEmpty(), 422, 'Bu kampaniyada hələ sual yoxdur.');

        $guestToken = $customer ? null : Str::random(48);

        $session = QuizSession::create([
            'customer_id'        => $customer?->id,
            'guest_token'        => $guestToken,
            'merchant_id'        => $merchant->id,
            'quiz_id'            => $quiz->id,
            'store_id'           => $data['store_id'] ?? null,
            'channel'            => $data['channel'] ?? 'qr',
            'device_fingerprint' => $data['device_fingerprint'] ?? null,
            'ip'                 => $request->ip(),
            'question_ids'       => $questions->pluck('id')->values()->all(),
            'started_at'         => now(),
        ]);

        return response()->json([
            'session_id'  => $session->id,
            'guest_token' => $guestToken, // null => login-lə oynanılır
            'quiz'        => $this->quizPayload($quiz),
            'questions'   => $questions->map(fn ($q) => [
                'id'      => $q->id,
                'title'   => $q->title, // JSON tərcümə obyekti — front dilə görə seçir
                'type'    => $q->type,
                'options' => $q->options->map(fn ($opt) => [
                    'id'   => $opt->id,
                    'text' => $opt->option_text,
                ])->values(),
            ])->values(),
        ], 201);
    }

    /**
     * Sessiyaya sahiblik yoxlaması: login olmuş customer VƏ YA düzgün guest_token.
     */
    protected function authorizeSession(Request $request, QuizSession $session): void
    {
        if ($session->customer_id !== null) {
            $customer = auth('customer')->user();
            abort_if(! $customer || $session->customer_id !== $customer->id, 403, 'Bu sessiya sizə aid deyil');
            return;
        }

        $token = (string) ($request->input('guest_token') ?: $request->header('X-Guest-Token'));
        abort_if($token === '' || ! hash_equals((string) $session->guest_token, $token), 403, 'Bu sessiya sizə aid deyil');
    }

    /**
     * CAVABLAR + NƏTİCƏ + (keçərsə) KUPON
     */
    public function submitAnswers(Request $request, QuizSession $session)
    {
        $this->authorizeSession($request, $session);

        if ($session->finished_at !== null) {
            return response()->json(['message' => 'Bu sessiya artıq tamamlanıb'], 409);
        }

        $data = $request->validate([
            'answers'               => 'required|array|min:1',
            'answers.*.question_id' => 'required|integer',
            'answers.*.option_id'   => 'nullable|integer',
        ]);

        // Yalnız bu sessiyada verilmiş suallar qəbul olunur
        $allowedIds = collect($session->question_ids ?? [])->map(fn ($id) => (int) $id);
        abort_if($allowedIds->isEmpty(), 422, 'Sessiyanın sual siyahısı tapılmadı.');

        $optionIds = collect($data['answers'])->pluck('option_id')->filter()->unique();
        $options   = QuestionOption::whereIn('id', $optionIds)->get()->keyBy('id');

        $result = DB::transaction(function () use ($session, $data, $allowedIds, $options) {
            $correctCount = 0;
            $seen = [];

            foreach ($data['answers'] as $item) {
                $questionId = (int) $item['question_id'];

                // sual bu sessiyaya aiddirmi + təkrar cavab yoxdurmu
                if (! $allowedIds->contains($questionId) || isset($seen[$questionId])) {
                    continue;
                }
                $seen[$questionId] = true;

                $option = isset($item['option_id']) ? $options->get((int) $item['option_id']) : null;

                // option başqa suala aiddirsə, cavab yanlış sayılır
                if ($option && $option->question_id !== $questionId) {
                    $option = null;
                }

                $isCorrect = (bool) ($option?->is_correct ?? false);
                if ($isCorrect) {
                    $correctCount++;
                }

                Answer::create([
                    'quiz_session_id'    => $session->id,
                    'question_id'        => $questionId,
                    'selected_option_id' => $option?->id,
                    'is_correct'         => $isCorrect,
                    'answered_at'        => now(),
                ]);
            }

            // Bal sessiyada verilən BÜTÜN sualların sayına görə hesablanır —
            // yalnız bildiyi sualları göndərməklə balı şişirtmək mümkün deyil.
            $total    = $allowedIds->count();
            $scorePct = (int) round($correctCount * 100 / $total);
            $isPassed = $scorePct >= $session->quiz->pass_threshold_pct;

            $session->update([
                'score_pct'     => $scorePct,
                'correct_count' => $correctCount,
                'is_passed'     => $isPassed,
                'finished_at'   => now(),
            ]);

            return ['correct' => $correctCount, 'total' => $total];
        });

        $session->refresh();

        // Kupon yalnız qeydiyyatlı VƏ aktiv abunəliyi olan müştəriyə verilir.
        // Qonaq üçün endirim potensialı hesablanır, kupon isə claim mərhələsində yaradılır.
        $isGuest    = $session->customer_id === null;
        $customer   = $isGuest ? null : $session->customer;
        $canEarn    = $this->canEarnCoupon($customer);
        $coupon     = (! $isGuest && $canEarn) ? $this->couponService->issueForSession($session) : null;
        $preview    = $this->couponService->previewReward($session);

        return response()->json([
            'score_pct'              => $session->score_pct,
            'is_passed'              => (bool) $session->is_passed,
            'correct'                => $result['correct'],
            'total'                  => $result['total'],
            'coupon'                 => $coupon ? $this->couponPayload($coupon) : null,
            'requires_registration'  => $isGuest && $preview !== null,
            // Login olub, kupona haqq qazanıb, amma abunəliyi yoxdursa
            'requires_subscription'  => ! $isGuest && ! $canEarn && $preview !== null,
            'reward_preview'         => $preview,
        ]);
    }

    /**
     * QONAQ SESSİYASINI SAHİBLƏN — qeydiyyatdan keçmiş müştəri guest_token ilə
     * öz qonaq sessiyasını hesabına bağlayır və (haqq edibsə) kuponunu alır.
     */
    public function claimSession(Request $request, QuizSession $session)
    {
        /** @var \App\Models\Customer $customer */
        $customer = $request->user('customer');

        // Sessiya artıq hesaba bağlıdırsa token tələb olunmur (abunə olduqdan sonra
        // kuponu təkrar tələb etmək üçün eyni endpoint istifadə olunur).
        $data = $request->validate(['guest_token' => 'nullable|string']);

        if ($session->customer_id !== null) {
            // artıq sahiblənib — eyni müştəridirsə idempotent davran
            abort_unless($session->customer_id === $customer->id, 409, 'Bu sessiya artıq başqa hesaba bağlanıb.');
        } else {
            abort_unless(
                $session->guest_token !== null
                    && filled($data['guest_token'] ?? null)
                    && hash_equals($session->guest_token, (string) $data['guest_token']),
                403,
                'Sessiya tapılmadı və ya token yanlışdır.'
            );
            $session->update(['customer_id' => $customer->id]);
        }

        abort_if($session->finished_at === null, 422, 'Sessiya hələ tamamlanmayıb.');

        // Kupon almaq üçün aktiv abunəlik lazımdır (config/subscriptions.php → gate).
        // Sessiya artıq hesaba bağlanıb, ona görə abunə olduqdan sonra yenidən claim edilə bilər.
        if (! $this->canEarnCoupon($customer)) {
            return $this->subscriptionRequired(
                $customer->hasActiveSubscription()
                    ? 'Paketinizin aylıq kupon limitinə çatmısınız.'
                    : 'Kuponu əldə etmək üçün aktiv abunəliyiniz olmalıdır.'
            );
        }

        $session->refresh();
        $coupon = $this->couponService->issueForSession($session);

        return response()->json([
            'score_pct' => $session->score_pct,
            'is_passed' => (bool) $session->is_passed,
            'coupon'    => $coupon ? $this->couponPayload($coupon) : null,
        ]);
    }

    public function result(Request $request, QuizSession $session)
    {
        $this->authorizeSession($request, $session);

        $coupon = $session->coupon;

        return response()->json([
            'id'          => $session->id,
            'score_pct'   => $session->score_pct,
            'is_passed'   => (bool) $session->is_passed,
            'started_at'  => $session->started_at,
            'finished_at' => $session->finished_at,
            'coupon'      => $coupon ? $this->couponPayload($coupon) : null,
        ]);
    }

    /** Müştərinin öz kuponları */
    public function myCoupons(Request $request)
    {
        /** @var \App\Models\Customer $customer */
        $customer = $request->user('customer');

        $coupons = Coupon::whereHas('session', fn ($q) => $q->where('customer_id', $customer->id))
            ->with('merchant:id,name,slug')
            ->latest()
            ->get();

        return response()->json([
            'coupons' => $coupons->map(fn ($c) => $this->couponPayload($c) + [
                'merchant' => $c->merchant?->only(['id', 'name', 'slug']),
            ])->values(),
        ]);
    }

    protected function findActiveQuiz(Merchant $merchant, int $quizId): Quiz
    {
        return Quiz::where('id', $quizId)
            ->where('merchant_id', $merchant->id)
            ->where('status', 'active')
            ->firstOrFail();
    }

    protected function quizPayload(Quiz $quiz): array
    {
        return [
            'id'                    => $quiz->id,
            'title'                 => $quiz->title,
            'time_per_question_sec' => $quiz->time_per_question_sec,
            'total_questions'       => $quiz->total_questions,
            'pass_threshold_pct'    => $quiz->pass_threshold_pct,
            'reward_mode'           => $quiz->reward_mode,
            // Pilləli rejimdə front "3 düz = 5%..." göstərə bilsin
            'reward_tiers'          => $quiz->reward_mode === 'tiered'
                ? $quiz->rewardTiers->sortBy('min_correct')->values()->map(fn ($t) => [
                    'min_correct'   => $t->min_correct,
                    'discount_type' => $t->discount_type,
                    'value'         => $t->value,
                ])
                : [],
            // Flat rejimdə mükafat merchant ayarlarından gəlir
            'flat_reward'           => $quiz->reward_mode === 'flat'
                ? [
                    'discount_type' => $quiz->merchant->coupon_discount_type ?? 'percent',
                    'value'         => $quiz->merchant->coupon_value ?? 10,
                ]
                : null,
        ];
    }

    protected function couponPayload(Coupon $coupon): array
    {
        return [
            'code'          => $coupon->code,
            'discount_type' => $coupon->discount_type,
            'value'         => $coupon->value,
            'status'        => $coupon->status,
            'expires_at'    => $coupon->expires_at,
            'qr_payload'    => $coupon->qr_payload,
        ];
    }

    protected function customerPayload(Customer $customer): array
    {
        return [
            'id'    => $customer->id,
            'name'  => $customer->name,
            'phone' => $customer->phone,
            // Front girişdən dərhal sonra abunəlik vəziyyətini bilsin
            'subscription' => [
                'is_active'            => $customer->hasActiveSubscription(),
                'required'             => (bool) config('subscriptions.customer.enabled'),
                'gate'                 => $this->subscriptionGate(),
                'plan_name'            => $customer->plan?->name,
                'subscription_ends_at' => $customer->subscription_ends_at,
            ],
        ];
    }

    /* ==================== ABUNƏLİK QAPISI ==================== */

    /** Aktiv qapı rejimi: 'off' | 'claim' | 'play' (bax: config/subscriptions.php) */
    protected function subscriptionGate(): string
    {
        if (! config('subscriptions.customer.enabled')) {
            return 'off';
        }

        return (string) config('subscriptions.customer.gate', 'claim');
    }

    /** Müştəri kupon qazana bilər? (abunəlik + aylıq limit) */
    protected function canEarnCoupon(?Customer $customer): bool
    {
        if ($this->subscriptionGate() === 'off') {
            return true;
        }

        return $customer !== null
            && $customer->hasActiveSubscription()
            && $customer->canEarnMoreCoupons();
    }

    /** Front bu cavabı görüb istifadəçini abunəlik səhifəsinə yönləndirir. */
    protected function subscriptionRequired(string $message)
    {
        return response()->json([
            'message' => $message,
            'code'    => 'subscription_required',
        ], 402);
    }
}
