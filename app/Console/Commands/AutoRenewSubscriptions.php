<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Merchant;
use App\Services\Payments\PaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class AutoRenewSubscriptions extends Command
{
    protected $signature = 'subscriptions:auto-renew';

    protected $description = 'Avtomatik yenilənməyə açıq və müddəti yaxınlaşan mağaza/istifadəçi abunəliklərini yadda saxlanılan kartla yeniləyir';

    public function handle(PaymentService $payments): int
    {
        $this->renewMerchants($payments);
        $this->renewCustomers($payments);

        return self::SUCCESS;
    }

    protected function renewMerchants(PaymentService $payments): void
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
    }

    protected function renewCustomers(PaymentService $payments): void
    {
        $customers = Customer::query()
            ->where('auto_renew', true)
            ->whereNotNull('customer_plan_id')
            ->whereNotNull('subscription_ends_at')
            ->where('subscription_ends_at', '<=', now()->addDay())
            ->whereHas('paymentMethods')
            ->get();

        $this->info("Yenilənəcək istifadəçi sayı: {$customers->count()}");

        foreach ($customers as $customer) {
            try {
                $payment = $payments->chargeCustomerRenewal($customer);

                $this->line("İstifadəçi #{$customer->id} ({$customer->phone}): " . ($payment->isPaid() ? 'uğurla yeniləndi' : 'ödəniş rədd edildi — ' . $payment->status));
            } catch (Throwable $e) {
                $this->error("İstifadəçi #{$customer->id} ({$customer->phone}): xəta — {$e->getMessage()}");

                Log::error('Avtomatik istifadəçi abunəliyi yenilənməsi uğursuz oldu', [
                    'customer_id' => $customer->id,
                    'error'       => $e->getMessage(),
                ]);
            }
        }
    }
}
