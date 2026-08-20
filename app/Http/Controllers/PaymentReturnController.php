<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\Payments\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Bankın ödəniş səhifəsindən (HPP) qayıdış nöqtəsi. Brauzerdən gələn parametrlərə
 * etibar edilmir — PaymentService::handleReturn() daxilində status serverdən-serverə yenidən yoxlanılır.
 *
 * Mağaza ödənişləri Filament panelinə (/abuneliyim), istifadəçi ödənişləri isə
 * React tətbiqinə (config('subscriptions.frontend_url') + /subscription) qaytarılır.
 */
class PaymentReturnController extends Controller
{
    public function __invoke(Request $request, string $provider, PaymentService $service): RedirectResponse
    {
        $externalOrderId = $request->query('id') ?? $request->query('ID');

        if (! $externalOrderId) {
            return $this->back(null, 'error');
        }

        // Qayıdış ünvanını seçmək üçün ödənişin kimə aid olduğunu əvvəlcədən müəyyən edirik.
        $known = Payment::where('provider', $provider)
            ->where('external_order_id', (string) $externalOrderId)
            ->latest()
            ->first();

        try {
            $payment = $service->handleReturn($provider, (string) $externalOrderId);
        } catch (Throwable $e) {
            Log::warning('Ödəniş qayıdışı işlənə bilmədi', [
                'provider' => $provider,
                'external_order_id' => $externalOrderId,
                'error' => $e->getMessage(),
            ]);

            return $this->back($known, 'error');
        }

        return $this->back($payment, $payment->isPaid() ? 'success' : 'failed');
    }

    protected function back(?Payment $payment, string $status): RedirectResponse
    {
        if ($payment?->isCustomerPayment()) {
            return redirect()->away(
                config('subscriptions.frontend_url') . '/subscription?payment=' . $status
            );
        }

        return redirect('/abuneliyim?payment=' . $status);
    }
}
