<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Services\CouponService;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function __construct(protected CouponService $service) {}

    /**
     * Kupon məlumatı (kassir skan edəndə göstərmək üçün).
     */
    public function show(string $code)
    {
        $coupon = Coupon::with('merchant:id,name')->where('code', $code)->firstOrFail();

        return response()->json([
            'code'          => $coupon->code,
            'status'        => $coupon->status,
            'discount_type' => $coupon->discount_type,
            'value'         => $coupon->value,
            'expires_at'    => $coupon->expires_at,
            'merchant'      => $coupon->merchant?->only(['id', 'name']),
        ]);
    }

    /**
     * Kassir kuponu istifadə edir (auth:staff tələb olunur).
     */
    public function redeem(Request $req, string $code)
    {
        $data = $req->validate([
            'store_id'      => 'nullable|integer|exists:stores,id',
            'pos_reference' => 'nullable|string|max:255',
        ]);

        /** @var \App\Models\User $cashier */
        $cashier = $req->user('staff');

        $coupon = Coupon::where('code', $code)->firstOrFail();

        // store göndərilibsə kassirin merchant-ına aid olmalıdır
        if (! empty($data['store_id']) && ! $cashier->is_admin) {
            $ok = \App\Models\Store::whereKey($data['store_id'])
                ->where('merchant_id', $cashier->merchant_id)
                ->exists();
            abort_unless($ok, 422, 'Filial sizin müəssisəyə aid deyil.');
        }

        $coupon = $this->service->redeem(
            $coupon,
            $cashier,
            $data['store_id'] ?? null,
            $data['pos_reference'] ?? null
        );

        return response()->json([
            'code'        => $coupon->code,
            'status'      => $coupon->status,
            'redeemed_at' => optional($coupon->redemptions()->latest('redeemed_at')->first())->redeemed_at,
        ]);
    }
}
