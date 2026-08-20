<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Aktiv abunəliyi olmayan müştəriyə qapalı marşrutlar üçün.
 * Front 402 cavabını görüb istifadəçini abunəlik səhifəsinə yönləndirir.
 */
class EnsureCustomerSubscribed
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('subscriptions.customer.enabled')) {
            return $next($request);
        }

        $customer = $request->user('customer');

        if (! $customer || ! $customer->hasActiveSubscription()) {
            return response()->json([
                'message' => 'Bu bölmə üçün aktiv abunəlik lazımdır.',
                'code'    => 'subscription_required',
            ], 402);
        }

        return $next($request);
    }
}
