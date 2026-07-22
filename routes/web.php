<?php

use App\Http\Controllers\PaymentReturnController;
use Illuminate\Support\Facades\Route;

Route::get('/payments/{provider}/return', PaymentReturnController::class)->name('payments.return');

//Route::get('/', function () {
//    return redirect('/admin');
//});