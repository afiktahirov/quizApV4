<?php
namespace App\Services;

use App\Models\Coupon;
use App\Models\QuizSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CouponService
{
    /**
     * Tamamlanmış sessiya üçün kupon yaradır (idempotent — eyni sessiyaya
     * ikinci dəfə çağırılsa mövcud kuponu qaytarır).
     *
     * Endirim kampaniyanın rejimindən asılıdır:
     *  - flat   : keçid faizini keçibsə, merchant-ın sabit kuponu verilir
     *  - tiered : düzgün cavab sayına uyğun ən yüksək pillənin endirimi verilir
     * Uyğun endirim yoxdursa null qaytarır.
     */
    public function issueForSession(QuizSession $session): ?Coupon
    {
        if ($session->coupon) {
            return $session->coupon;
        }

        $reward = $this->resolveReward($session);
        if ($reward === null) {
            return null;
        }

        $merchant = $session->merchant;
        $expires  = now()->addHours((int) ($merchant->coupon_ttl_hours ?? 48));

        $code      = $this->generateCode();
        $signature = hash_hmac(
            'sha256',
            $code . '|' . $session->merchant_id . '|' . $session->id . '|' . $expires->timestamp,
            config('app.key')
        );

        return Coupon::create([
            'code'            => $code,
            'merchant_id'     => $session->merchant_id,
            'store_id'        => $session->store_id,
            'quiz_session_id' => $session->id,
            'discount_type'   => $reward['discount_type'],
            'value'           => $reward['value'],
            'expires_at'      => $expires,
            'status'          => 'active',
            'signature'       => $signature,
            'qr_payload'      => url('/c/' . $code . '?sig=' . $signature),
        ]);
    }

    /** Köhnə ad — geriyə uyğunluq üçün saxlanılır. */
    public function issueForPassedSession(QuizSession $session): ?Coupon
    {
        return $this->issueForSession($session);
    }

    /**
     * Sessiyanın qazanacağı endirimi kupon yaratmadan qaytarır
     * (qonaq axınında "qeydiyyatdan keç, bunu qazan" göstərmək üçün).
     */
    public function previewReward(QuizSession $session): ?array
    {
        return $this->resolveReward($session);
    }

    /**
     * Sessiyaya uyğun endirimi (növ + dəyər) müəyyən edir.
     * @return array{discount_type:string, value:float|int|string}|null
     */
    protected function resolveReward(QuizSession $session): ?array
    {
        $quiz = $session->quiz;

        if ($quiz && $quiz->reward_mode === 'tiered') {
            $correct = (int) ($session->correct_count ?? 0);

            // rewardTiers min_correct-ə görə azalan sıradadır — çatılan ən yüksək pillə
            $tier = $quiz->rewardTiers->first(fn ($t) => $correct >= $t->min_correct);

            if (! $tier) {
                return null;
            }

            return ['discount_type' => $tier->discount_type, 'value' => $tier->value];
        }

        // flat rejim — keçid tələb olunur
        if (! $session->is_passed) {
            return null;
        }

        $merchant = $session->merchant;

        return [
            'discount_type' => $merchant->coupon_discount_type ?? 'percent',
            'value'         => $merchant->coupon_value ?? 10,
        ];
    }

    /**
     * Kassir kuponu istifadə edir. Kupon yalnız öz merchant-ının
     * kassiri tərəfindən, aktiv statusda və müddəti bitməmiş istifadə oluna bilər.
     */
    public function redeem(Coupon $coupon, User $cashier, ?int $storeId = null, ?string $posRef = null): Coupon
    {
        return DB::transaction(function () use ($coupon, $cashier, $storeId, $posRef) {
            // sərt kilid — eyni kuponun paralel istifadəsinin qarşısını alır
            $coupon = Coupon::whereKey($coupon->id)->lockForUpdate()->firstOrFail();

            if (! $cashier->is_admin && $cashier->merchant_id !== $coupon->merchant_id) {
                throw ValidationException::withMessages(['code' => 'Bu kupon sizin müəssisəyə aid deyil.']);
            }

            if ($coupon->status !== 'active') {
                throw ValidationException::withMessages(['code' => 'Kupon artıq istifadə olunub və ya ləğv edilib.']);
            }

            if (now()->greaterThan($coupon->expires_at)) {
                $coupon->update(['status' => 'expired']);
                throw ValidationException::withMessages(['code' => 'Kuponun müddəti bitib.']);
            }

            $coupon->update(['status' => 'redeemed']);
            $coupon->redemptions()->create([
                'store_id'        => $storeId,
                'cashier_user_id' => $cashier->id,
                'redeemed_at'     => now(),
                'pos_reference'   => $posRef,
            ]);

            return $coupon->fresh();
        });
    }

    protected function generateCode(): string
    {
        // Oxunaqlı format: QZ-XXXX-XXXX (0/O, 1/I qarışıqlığı olmayan əlifba)
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $part = fn (int $n) => implode('', array_map(
                fn () => $alphabet[random_int(0, strlen($alphabet) - 1)],
                range(1, $n)
            ));
            $code = 'QZ-' . $part(4) . '-' . $part(4);
        } while (Coupon::where('code', $code)->exists());

        return $code;
    }
}
