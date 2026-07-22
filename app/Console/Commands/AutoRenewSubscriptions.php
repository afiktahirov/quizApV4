<?php

namespace App\Console\Commands;

use App\Models\Merchant;
use App\Services\Payments\PaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class AutoRenewSubscriptions extends Command
{
    protected $signature = 'subscriptions:auto-renew';

    protected $description = 'Avtomatik yenilənməyə açıq və bitmə vaxtı yaxınlaşan mağazaları yadda saxlanılan kartla yeniləyir';

    public function handle(PaymentService $payments): int
    {
        $merchants = Merchant::query()
            ->where('auto_renew', true)
            ->whereNotNull('plan_id')
            ->whereNotNull('subscription_ends_at')
            ->where('subscription_ends_at', '<=', now()->addDay())
            ->whereHas('paymentMethods')
            ->get();

        $this->info("Yenilənəcək mağaza sayı: {$merchants->count()}");

        foreach ($merchants as $merchant) {
            try {
                $payment = $payments->chargeForRenewal($merchant);

                $this->line("Mağaza #{$merchant->id} ({$merchant->name}): " . ($payment->isPaid() ? 'uğurla yeniləndi' : 'ödəniş rədd edildi — ' . $payment->status));
            } catch (Throwable $e) {
                $this->error("Mağaza #{$merchant->id} ({$merchant->name}): xəta — {$e->getMessage()}");

                Log::error('Avtomatik abunəlik yenilənməsi uğursuz oldu', [
                    'merchant_id' => $merchant->id,
                    'error'       => $e->getMessage(),
                ]);
            }
        }

        return self::SUCCESS;
    }
}
