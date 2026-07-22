<?php

namespace App\Providers;

use App\Filament\Auth\MerchantRegistrationResponse;
use Filament\Auth\Http\Responses\Contracts\RegistrationResponse;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Qeydiyyatdan sonra mağazanı birbaşa "Abunəliyim" səhifəsinə yönləndirir
        // (bax: App\Filament\Pages\Auth\MerchantRegister).
        $this->app->bind(RegistrationResponse::class, MerchantRegistrationResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
