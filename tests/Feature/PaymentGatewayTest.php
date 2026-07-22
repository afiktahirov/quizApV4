<?php

namespace Tests\Feature;

use App\Models\Merchant;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Plan;
use App\Models\SubscriptionRequest;
use App\Services\Payments\KapitalBank\KapitalBankGateway;
use App\Services\Payments\PaymentGatewayException;
use App\Services\Payments\PaymentGatewayManager;
use App\Services\Payments\PaymentService;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentGatewayTest extends TestCase
{
    use RefreshDatabase;

    private function makeMerchant(): Merchant
    {
        return Merchant::create([
            'name' => 'Test M', 'slug' => 'test-m-' . uniqid(), 'status' => 'active',
            'coupon_discount_type' => 'percent', 'coupon_value' => 10, 'coupon_ttl_hours' => 48,
        ]);
    }

    private function makeRequest(): SubscriptionRequest
    {
        $plan = Plan::create([
            'name' => 'Std', 'slug' => 'std-' . uniqid(), 'price' => 60, 'currency' => 'AZN',
            'billing_period' => 'monthly', 'is_active' => true,
        ]);
        $merchant = $this->makeMerchant();

        return app(SubscriptionService::class)->requestUpgrade($merchant, $plan, 2);
    }

    public function test_gateway_creates_payment_session_from_bank_response(): void
    {
        Http::fake([
            'txpgtst.kapitalbank.az/api/order' => Http::response([
                'order' => ['id' => 555, 'hppUrl' => 'https://txpgtst.kapitalbank.az/flex', 'password' => 'secret-pass', 'status' => 'Preparing'],
            ], 200),
        ]);

        $gateway = new KapitalBankGateway(config('payments.providers.kapital_bank'));
        $merchant = $this->makeMerchant();

        $session = $gateway->createPayment($merchant, 'ref-1', 120.00, 'AZN', 'Test ödəniş');

        $this->assertEquals('555', $session->externalOrderId);
        $this->assertEquals('https://txpgtst.kapitalbank.az/flex?id=555&password=secret-pass', $session->redirectUrl);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://txpgtst.kapitalbank.az/api/order'
                && $request['order']['typeRid'] === 'Order_SMS'
                && $request['order']['amount'] === '120'
                && $request['order']['currency'] === 'AZN';
        });
    }

    public function test_gateway_maps_bank_statuses_to_internal_statuses(): void
    {
        $cases = [
            'FullyPaid' => Payment::STATUS_PAID,
            'PartiallyPaid' => Payment::STATUS_PAID,
            'Declined' => Payment::STATUS_FAILED,
            'Refunded' => Payment::STATUS_REFUNDED,
            'Reversed' => Payment::STATUS_REVERSED,
            'Expired' => Payment::STATUS_EXPIRED,
            'Preparing' => Payment::STATUS_PENDING,
        ];

        // Tək closure-based fake qeydiyyatı — array formada Http::fake()-i loop içində
        // təkrar çağırmaq stub-ları üst-üstə yığır və HƏMİŞƏ ilk qeydiyyatı qaytarır.
        $currentBankStatus = null;

        Http::fake(function () use (&$currentBankStatus) {
            return Http::response(['order' => ['id' => 1, 'status' => $currentBankStatus]], 200);
        });

        $gateway = new KapitalBankGateway(config('payments.providers.kapital_bank'));

        foreach ($cases as $bankStatus => $expected) {
            $currentBankStatus = $bankStatus;

            $result = $gateway->fetchStatus('1');

            $this->assertEquals($expected, $result->status, "Bank status {$bankStatus} should map to {$expected}");
        }
    }

    public function test_initiate_creates_pending_payment_row(): void
    {
        Http::fake([
            'txpgtst.kapitalbank.az/api/order' => Http::response([
                'order' => ['id' => 777, 'hppUrl' => 'https://txpgtst.kapitalbank.az/flex', 'password' => 'pw', 'status' => 'Preparing'],
            ], 200),
        ]);

        $request = $this->makeRequest();
        $session = app(PaymentService::class)->initiate($request);

        $this->assertEquals('777', $session->externalOrderId);

        $payment = Payment::first();
        $this->assertNotNull($payment);
        $this->assertEquals(Payment::STATUS_PENDING, $payment->status);
        $this->assertEquals('kapital_bank', $payment->provider);
        $this->assertEquals($request->id, $payment->subscription_request_id);
        $this->assertEquals('120.00', $payment->amount); // 60 * 2 dövr
    }

    public function test_handle_return_marks_paid_and_auto_grants_subscription(): void
    {
        Http::fake([
            'txpgtst.kapitalbank.az/api/order' => Http::response([
                'order' => ['id' => 999, 'hppUrl' => 'https://txpgtst.kapitalbank.az/flex', 'password' => 'pw', 'status' => 'Preparing'],
            ], 200),
        ]);

        $request = $this->makeRequest();
        app(PaymentService::class)->initiate($request);

        Http::fake([
            'txpgtst.kapitalbank.az/api/order/*' => Http::response([
                'order' => ['id' => 999, 'status' => 'FullyPaid'],
            ], 200),
        ]);

        $payment = app(PaymentService::class)->handleReturn('kapital_bank', '999');

        $this->assertTrue($payment->isPaid());
        $this->assertEquals('approved', $request->fresh()->status);
        $this->assertEquals($request->plan_id, $request->merchant->fresh()->plan_id);
        $this->assertTrue($request->merchant->fresh()->isSubscribed());
    }

    public function test_handle_return_is_idempotent(): void
    {
        Http::fake([
            'txpgtst.kapitalbank.az/api/order' => Http::response([
                'order' => ['id' => 111, 'hppUrl' => 'https://txpgtst.kapitalbank.az/flex', 'password' => 'pw', 'status' => 'Preparing'],
            ], 200),
        ]);

        $request = $this->makeRequest();
        app(PaymentService::class)->initiate($request);

        Http::fake([
            'txpgtst.kapitalbank.az/api/order/*' => Http::response([
                'order' => ['id' => 111, 'status' => 'FullyPaid'],
            ], 200),
        ]);

        app(PaymentService::class)->handleReturn('kapital_bank', '111');
        $subEndsAtAfterFirst = $request->merchant->fresh()->subscription_ends_at;

        // İkinci çağırış artıq "paid" olduğu üçün təkrar prosessə getməməli (təkrar grant yoxdur)
        app(PaymentService::class)->handleReturn('kapital_bank', '111');
        $subEndsAtAfterSecond = $request->merchant->fresh()->subscription_ends_at;

        $this->assertTrue($subEndsAtAfterFirst->equalTo($subEndsAtAfterSecond));
        $this->assertEquals(1, $request->merchant->fresh()->subscriptions()->count());
    }

    public function test_payment_return_route_redirects_with_success_flag(): void
    {
        Http::fake([
            'txpgtst.kapitalbank.az/api/order' => Http::response([
                'order' => ['id' => 222, 'hppUrl' => 'https://txpgtst.kapitalbank.az/flex', 'password' => 'pw', 'status' => 'Preparing'],
            ], 200),
        ]);

        $request = $this->makeRequest();
        app(PaymentService::class)->initiate($request);

        Http::fake([
            'txpgtst.kapitalbank.az/api/order/*' => Http::response([
                'order' => ['id' => 222, 'status' => 'FullyPaid'],
            ], 200),
        ]);

        $response = $this->get('/payments/kapital_bank/return?id=222');

        $response->assertRedirect('/abuneliyim?payment=success');
    }

    public function test_gateway_manager_resolves_configured_provider(): void
    {
        $gateway = app(PaymentGatewayManager::class)->gateway('kapital_bank');

        $this->assertInstanceOf(KapitalBankGateway::class, $gateway);
    }

    public function test_save_card_flow_captures_stored_token_on_successful_payment(): void
    {
        Http::fake([
            'txpgtst.kapitalbank.az/api/order' => Http::response([
                'order' => ['id' => 333, 'hppUrl' => 'https://txpgtst.kapitalbank.az/flex', 'password' => 'pw', 'status' => 'Preparing'],
            ], 200),
        ]);

        $request = $this->makeRequest();
        app(PaymentService::class)->initiate($request, saveCard: true);

        $payment = Payment::first();
        $this->assertTrue((bool) $payment->save_card);

        Http::fake([
            'txpgtst.kapitalbank.az/api/order/*' => Http::response([
                'order' => ['id' => 333, 'status' => 'FullyPaid', 'srcToken' => ['storedId' => 'tok-abc', 'displayName' => '**** 1778']],
            ], 200),
        ]);

        app(PaymentService::class)->handleReturn('kapital_bank', '333');

        $method = PaymentMethod::first();
        $this->assertNotNull($method);
        $this->assertEquals($request->merchant_id, $method->merchant_id);
        $this->assertEquals('tok-abc', $method->external_token_id);
        $this->assertEquals('**** 1778', $method->card_mask);
    }

    private function makeMerchantWithSavedCard(): Merchant
    {
        $plan = Plan::create([
            'name' => 'Std', 'slug' => 'std-' . uniqid(), 'price' => 60, 'currency' => 'AZN',
            'billing_period' => 'monthly', 'is_active' => true,
        ]);

        $merchant = $this->makeMerchant();
        $merchant->update(['plan_id' => $plan->id, 'auto_renew' => true]);

        PaymentMethod::create([
            'merchant_id' => $merchant->id,
            'provider' => 'kapital_bank',
            'external_token_id' => 'tok-xyz',
            'card_mask' => '**** 1234',
        ]);

        return $merchant->fresh();
    }

    private function fakeChargeStoredCardFlow(int $orderId, string $finalStatus): void
    {
        Http::fake([
            'txpgtst.kapitalbank.az/api/order' => Http::response(['order' => ['id' => $orderId, 'password' => 'pw-renew']], 200),
            'txpgtst.kapitalbank.az/api/order/*/set-src-token*' => Http::response([], 200),
            'txpgtst.kapitalbank.az/api/order/*/exec-tran' => Http::response([], 200),
            'txpgtst.kapitalbank.az/api/order/*tranDetailLevel*' => Http::response(['order' => ['id' => $orderId, 'status' => $finalStatus]], 200),
        ]);
    }

    public function test_charge_for_renewal_pays_with_stored_card_without_redirect(): void
    {
        $merchant = $this->makeMerchantWithSavedCard();
        $endsAtBefore = $merchant->subscription_ends_at;

        $this->fakeChargeStoredCardFlow(444, 'FullyPaid');

        $payment = app(PaymentService::class)->chargeForRenewal($merchant);

        $this->assertTrue($payment->isPaid());
        $this->assertEquals('444', $payment->external_order_id);
        $this->assertEquals('approved', $payment->subscriptionRequest->status);
        $this->assertTrue($merchant->fresh()->subscription_ends_at->greaterThan($endsAtBefore ?? now()));

        Http::assertSent(fn ($request) => str_contains($request->url(), 'set-src-token')
            && $request['token']['storedId'] === 'tok-xyz');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'exec-tran')
            && ($request['tran']['conditions']['cofUsage'] ?? null) === 'Recurring');
    }

    public function test_charge_for_renewal_rejects_request_when_bank_declines(): void
    {
        $merchant = $this->makeMerchantWithSavedCard();
        $planIdBefore = $merchant->plan_id;

        $this->fakeChargeStoredCardFlow(445, 'Declined');

        $payment = app(PaymentService::class)->chargeForRenewal($merchant);

        $this->assertEquals(Payment::STATUS_FAILED, $payment->status);
        $this->assertEquals('rejected', $payment->subscriptionRequest->status);
        $this->assertEquals($planIdBefore, $merchant->fresh()->plan_id);
    }

    public function test_charge_for_renewal_throws_without_stored_card(): void
    {
        $plan = Plan::create([
            'name' => 'Std', 'slug' => 'std-' . uniqid(), 'price' => 60, 'currency' => 'AZN',
            'billing_period' => 'monthly', 'is_active' => true,
        ]);
        $merchant = $this->makeMerchant();
        $merchant->update(['plan_id' => $plan->id, 'auto_renew' => true]);

        $this->expectException(PaymentGatewayException::class);

        app(PaymentService::class)->chargeForRenewal($merchant->fresh());
    }

    public function test_auto_renew_command_charges_eligible_merchants(): void
    {
        $merchant = $this->makeMerchantWithSavedCard();
        $merchant->update(['subscription_ends_at' => now()->addHours(5)]);

        $this->fakeChargeStoredCardFlow(446, 'FullyPaid');

        Artisan::call('subscriptions:auto-renew');

        $this->assertEquals(1, Payment::where('status', Payment::STATUS_PAID)->count());
        $this->assertTrue($merchant->fresh()->subscription_ends_at->greaterThan(now()->addDays(20)));
    }

    public function test_sync_pending_payments_reconciles_abandoned_payment(): void
    {
        Http::fake([
            'txpgtst.kapitalbank.az/api/order' => Http::response([
                'order' => ['id' => 447, 'hppUrl' => 'https://txpgtst.kapitalbank.az/flex', 'password' => 'pw', 'status' => 'Preparing'],
            ], 200),
        ]);

        $request = $this->makeRequest();
        app(PaymentService::class)->initiate($request);

        Payment::first()->forceFill(['created_at' => now()->subMinutes(10)])->save();

        Http::fake([
            'txpgtst.kapitalbank.az/api/order/*' => Http::response([
                'order' => ['id' => 447, 'status' => 'FullyPaid'],
            ], 200),
        ]);

        Artisan::call('payments:sync-pending');

        $this->assertTrue(Payment::first()->isPaid());
        $this->assertEquals('approved', $request->fresh()->status);
    }
}
