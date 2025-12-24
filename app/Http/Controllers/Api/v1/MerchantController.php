<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Merchant;

class MerchantController extends Controller
{
    /**
     * Aktiv mağazaların ad və bio-larını qaytarır.
     */
    public function index()
    {
        $merchants = Merchant::query()
            ->where('status', 'active')
            ->get([
                'id',
                'name',
                'slug',
                'bio',
            ]);

        return response()->json([
            'merchants' => $merchants,
        ]);
    }
}
