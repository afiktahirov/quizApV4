<?php

namespace App\Http\Controllers;

use App\Services\Payments\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Bankın ödəniş səhifəsindən (HPP) qayıdış nöqtəsi. Brauzerdən gələn parametrlərə
 * etibar edilmir — PaymentService::handleReturn() daxilində status serverdən-serverə yenidən yoxlanılır.
 */
class PaymentReturnController extends Controller
{
    public function __invoke(Request $request, string $provider, PaymentService $service): RedirectResponse
    {
        $externalOrderId = $request->query('id') ?? $request->query('ID');

        if (! $externalOrderId) {
            return redirect('/abuneliyim?payment=error');
        }

        try {
            $payment = $service->handleReturn($provider, (string) $externalOrderId);
        } catch (Throwable $e) {
            Log::warning('Ödəniş qayıdışı işlənə bilmədi', [
                'provider' => $provider,
                'external_order_id' => $externalOrderId,
                'error' => $e->getMessage(),
            ]);

            return redirect('/abuneliyim?payment=error');
        }

        return redirect('/abuneliyim?payment=' . ($payment->isPaid() ? 'success' : 'failed'));
    }
}
