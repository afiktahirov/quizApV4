<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\v1\CustomerQuizController;
use App\Http\Controllers\Api\v1\MerchantController;


Route::prefix('v1')->group(function () {

    Route::get('merchants', [MerchantController::class, 'index']);

    // CUSTOMER AUTH
    Route::post('customer/register', [CustomerQuizController::class, 'register']);
    Route::post('customer/login',    [CustomerQuizController::class, 'login']);

    // Quiz məlumatı (public ola bilər)
    Route::get('quiz', [CustomerQuizController::class, 'showQuiz']);

    // Quiz cavablamaq üçün mütləq customer login olmalıdır
    Route::middleware('auth:customer')->group(function () {
        Route::post('quiz-sessions', [CustomerQuizController::class, 'startQuiz']);
        Route::post('quiz-sessions/{session}/answers', [CustomerQuizController::class, 'submitAnswers']);
        Route::get('quiz-sessions/{session}', [CustomerQuizController::class, 'result']);
    });
});
