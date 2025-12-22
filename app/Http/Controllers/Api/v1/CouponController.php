<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\{Coupon, Store, User};
use App\Services\CouponService;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function __construct(protected CouponService $service) {}

    public function show(string $code)
    {
        $coupon = Coupon::where('code', $code)->firstOrFail();
        return response()->json($coupon->only(['code', 'status', 'expires_at', 'discount_type', 'value']));
    }

    public function redeem(Request $req, string $code)
    {
        $data = $req->validate([
            'store_id' => ['required', 'integer', 'exists:stores,id'],
            'cashier_user_id' => ['required', 'integer', 'exists:users,id'],
            'pos_reference' => ['nullable', 'string'],
        ]);
        $coupon = Coupon::where('code', $code)->firstOrFail();
        $coupon = $this->service->redeem($coupon, $data['store_id'], $data['cashier_user_id'], $data['pos_reference'] ?? null);
        return response()->json(['status' => $coupon->status, 'redeemed_at' => optional($coupon->redemptions()->latest('redeemed_at')->first())->redeemed_at]);
    }
}
