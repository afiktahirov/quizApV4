<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Http\Resources\MerchantResource;

class MerchantController extends Controller
{
    /**
     * Abunəliyi aktiv olan müəssisələrin siyahısı.
     */
    public function index()
    {
        $merchants = Merchant::query()
            ->subscribed()
            ->get(['id', 'name', 'slug', 'bio', 'photo', 'address', 'latitude', 'longitude']);

        return response()->json([
            'merchants' => MerchantResource::collection($merchants),
        ]);
    }

    /**
     * Bir müəssisənin detalı + aktiv kampaniyaları.
     */
    public function show(int $id)
    {
        $merchant = Merchant::subscribed()
            ->with(['quizzes' => fn ($q) => $q->where('status', 'active')->with('rewardTiers')])
            ->findOrFail($id);

        return response()->json([
            'merchant' => new MerchantResource($merchant),
            'quizzes'  => $merchant->quizzes->map(fn ($quiz) => [
                'id'                    => $quiz->id,
                'title'                 => $quiz->title,
                'total_questions'       => $quiz->total_questions,
                'pass_threshold_pct'    => $quiz->pass_threshold_pct,
                'time_per_question_sec' => $quiz->time_per_question_sec,
                'reward_mode'           => $quiz->reward_mode,
                'reward_tiers'          => $quiz->reward_mode === 'tiered'
                    ? $quiz->rewardTiers->sortBy('min_correct')->values()->map(fn ($t) => [
                        'min_correct'   => $t->min_correct,
                        'discount_type' => $t->discount_type,
                        'value'         => $t->value,
                    ])
                    : [],
                'flat_reward'           => $quiz->reward_mode === 'flat'
                    ? [
                        'discount_type' => $merchant->coupon_discount_type ?? 'percent',
                        'value'         => $merchant->coupon_value ?? 10,
                    ]
                    : null,
            ])->values(),
        ]);
    }
}
