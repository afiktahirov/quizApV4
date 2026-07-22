<?php

namespace App\Filament\Auth;

use Filament\Auth\Http\Responses\Contracts\RegistrationResponse as Responsable;

/**
 * Qeydiyyatdan sonra mağazanı birbaşa "Abunəliyim" səhifəsinə yönləndirir —
 * paket (pulsuz sınaq daxil) seçilməyənə qədər panelin qalan hissəsi görünmür.
 */
class MerchantRegistrationResponse implements Responsable
{
    public function toResponse($request)
    {
        return redirect('/abuneliyim');
    }
}
