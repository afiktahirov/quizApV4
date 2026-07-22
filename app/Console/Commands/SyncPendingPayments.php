<?php

namespace App\Console\Commands;

use App\Services\Payments\PaymentService;
use Illuminate\Console\Command;

class SyncPendingPayments extends Command
{
    protected $signature = 'payments:sync-pending';

    protected $description = 'Bank redirect-i baş vermədiyi (müştəri səhifəni bağladığı) hallar üçün "pending" ödənişləri serverdən-serverə yenidən yoxlayır';

    public function handle(PaymentService $payments): int
    {
        $checked = $payments->syncPending();

        $this->info("{$checked} ödəniş yoxlanıldı.");

        return self::SUCCESS;
    }
}
