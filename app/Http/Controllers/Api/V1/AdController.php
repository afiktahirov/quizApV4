<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use Illuminate\Http\Request;

class AdController extends Controller
{
    /**
     * Aktiv reklamlar (tarix aralığına düşən). merchant_id ilə filter etmək olar.
     */
    public function index(Request $request)
    {
        $request->validate(['merchant_id' => 'nullable|integer']);

        $ads = Ad::query()
            ->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->when($request->merchant_id, fn ($q, $id) => $q->where('merchant_id', $id))
            ->latest()
            ->get();

        return response()->json([
            'ads' => $ads->map(fn ($ad) => [
                'id'          => $ad->id,
                'merchant_id' => $ad->merchant_id,
                'title'       => $ad->title,
                'image'       => $ad->image_path ? asset('storage/' . $ad->image_path) : null,
                'content'     => $ad->content,
                'starts_at'   => $ad->starts_at,
                'ends_at'     => $ad->ends_at,
            ])->values(),
        ]);
    }
}
