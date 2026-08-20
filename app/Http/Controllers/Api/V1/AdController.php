<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use Illuminate\Http\Request;

class AdController extends Controller
{
    /**
     * Göstərilməli reklamlar. merchant_id verilsə yalnız həmin mağazanın,
     * verilməsə bütün aktiv reklamlar qaytarılır (ana səhifə üçün).
     *
     * Mətnlər {az,en,ru} obyekti kimi gedir — dili front seçir (bax: src/i18n).
     */
    public function index(Request $request)
    {
        $data = $request->validate([
            'merchant_id' => 'nullable|integer',
            'limit'       => 'nullable|integer|min:1|max:50',
        ]);

        $ads = Ad::query()
            ->visible()
            // Abunəliyi bitmiş mağazanın reklamı göstərilmir
            ->where(fn ($q) => $q->whereNull('merchant_id')
                ->orWhereHas('merchant', fn ($m) => $m->subscribed()))
            ->when($data['merchant_id'] ?? null, fn ($q, $id) => $q->where('merchant_id', $id))
            ->with('merchant:id,name,slug')
            ->latest()
            ->limit($data['limit'] ?? 20)
            ->get();

        return response()->json([
            'ads' => $ads->map(fn (Ad $ad) => [
                'id'          => $ad->id,
                'merchant_id' => $ad->merchant_id,
                'merchant'    => $ad->merchant?->only(['id', 'name', 'slug']),
                'title'       => $ad->title,
                'image'       => $ad->imageUrl(),
                'content'     => $ad->content,
                'starts_at'   => $ad->starts_at,
                'ends_at'     => $ad->ends_at,
            ])->values(),
        ]);
    }
}
