<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AdController;
use App\Http\Controllers\Api\V1\CouponController;
use App\Http\Controllers\Api\V1\CustomerQuizController;
use App\Http\Controllers\Api\V1\CustomerSubscriptionController;
use App\Http\Controllers\Api\V1\MerchantController;
use App\Http\Controllers\Api\V1\StaffAuthController;
use App\Http\Controllers\Api\V1\UiTextController;

Route::prefix('v1')->group(function () {

    // PUBLIC — müəssisələr, kampaniyalar, reklamlar
    Route::get('merchants', [MerchantController::class, 'index']);
    Route::get('merchants/{id}', [MerchantController::class, 'show']);
    Route::get('quizzes', [CustomerQuizController::class, 'merchantQuizzes']);
    Route::get('quiz', [CustomerQuizController::class, 'showQuiz']);
    Route::get('ads', [AdController::class, 'index']);
    Route::get('ui-texts', [UiTextController::class, 'index']); // front statik mətnləri (3 dil)
    Route::get('coupons/{code}', [CouponController::class, 'show']);

    // PUBLIC — istifadəçi abunəlik paketləri (qiymət səhifəsi login tələb etmir)
    Route::get('customer-plans', [CustomerSubscriptionController::class, 'plans']);

    // CUSTOMER AUTH
    Route::post('customer/register', [CustomerQuizController::class, 'register']);
    Route::post('customer/login',    [CustomerQuizController::class, 'login']);

    // QUIZ OYNAMAQ — login tələb olunmur (QR axını).
    // Token göndərilsə customer sessiyaya yazılır; göndərilməsə guest_token verilir.
    // Qonaq sessiyanı yalnız guest_token ilə idarə edə bilir; kupon isə yalnız
    // qeydiyyatdan sonra (claim) verilir.
    // Abunəlik tələbi config/subscriptions.php-dəki "gate" ayarından asılıdır.
    Route::post('quiz-sessions', [CustomerQuizController::class, 'startQuiz']);
    Route::post('quiz-sessions/{session}/answers', [CustomerQuizController::class, 'submitAnswers']);
    Route::get('quiz-sessions/{session}', [CustomerQuizController::class, 'result']);

    Route::middleware('auth:customer')->group(function () {
        // Qonaq sessiyasını hesaba bağla + kuponu al
        Route::post('quiz-sessions/{session}/claim', [CustomerQuizController::class, 'claimSession']);
        Route::get('customer/coupons', [CustomerQuizController::class, 'myCoupons']);

        // İSTİFADƏÇİ ABUNƏLİYİ
        Route::get('customer/subscription',                 [CustomerSubscriptionController::class, 'show']);
        Route::post('customer/subscription',                [CustomerSubscriptionController::class, 'subscribe']);
        Route::post('customer/subscription/pay',            [CustomerSubscriptionController::class, 'pay']);
        Route::post('customer/subscription/cancel-request', [CustomerSubscriptionController::class, 'cancelRequest']);
        Route::post('customer/subscription/auto-renew',     [CustomerSubscriptionController::class, 'toggleAutoRenew']);
        Route::delete('customer/subscription/card',         [CustomerSubscriptionController::class, 'removeCard']);
        Route::get('customer/subscription/history',         [CustomerSubscriptionController::class, 'history']);
    });

    // STAFF (kassir / merchant admin) — kupon oxutma
    Route::post('staff/login', [StaffAuthController::class, 'login']);
    Route::middleware('auth:staff')->group(function () {
        Route::post('coupons/{code}/redeem', [CouponController::class, 'redeem']);
    });
});
