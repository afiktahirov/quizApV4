<?php
namespace App\Services;

use App\Models\{Coupon, QuizSession};
use Illuminate\Support\Facades\DB;

class CouponService
{
    public function issueForPassedSession(QuizSession $session): ?Coupon
    {
        if (!$session->is_passed || $session->coupon) {
            return $session->coupon;
        } // idempotent

        $ttlHours = data_get($session->merchant->settings, 'coupon_ttl_hours', 48);
        $expires = now()->addHours($ttlHours);

        $code = $this->generateCode();
        $signature = hash_hmac('sha256', $code . '|' . $session->merchant_id . '|' . $session->store_id . '|' . $expires->timestamp, config('app.key'));

        return Coupon::create([
            'code' => $code,
            'merchant_id' => $session->merchant_id,
            'store_id' => $session->store_id,
            'quiz_session_id' => $session->id,
            'discount_type' => 'percent',
            'value' => 10, // default 10%
            'expires_at' => $expires,
            'status' => 'active',
            'signature' => $signature,
            'qr_payload' => url('/c/' . $code . '?sig=' . $signature),
        ]);
    }

    public function redeem(Coupon $coupon, int $storeId, int $cashierUserId, ?string $posRef = null): Coupon
    {
        return DB::transaction(function () use ($coupon, $storeId, $cashierUserId, $posRef) {
            if ($coupon->status !== 'active' || now()->greaterThan($coupon->expires_at)) {
                abort(422, 'Kupon aktiv deyil və ya müddəti bitib.');
            }
            $coupon->update(['status' => 'redeemed']);
            $coupon->redemptions()->create([
                'store_id' => $storeId,
                'cashier_user_id' => $cashierUserId,
                'redeemed_at' => now(),
                'pos_reference' => $posRef,
            ]);
            return $coupon->fresh();
        });
    }

    protected function generateCode(int $length = 10): string
    {
        // Sadə base32-stil kod (samitisiz), qısa və kassada rahat oxunan
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $res = '';
        for ($i = 0; $i < $length; $i++) {
            $res .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return substr(chunk_split($res, 4, '-'), 0, $length + intdiv($length - 1, 4));
    }
}
