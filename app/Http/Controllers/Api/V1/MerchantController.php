<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Http\Resources\MerchantResource;

class MerchantController extends Controller
{
    /**
     * Aktiv mağazaların ad və bio-larını qaytarır.
     */
    public function index()
    {
        $merchants = Merchant::query()
            ->where('status', 'active')
            ->get(['id','name','slug','bio','photo']);

        return response()->json([
            'merchants' => MerchantResource::collection($merchants),
        ]);
    }
}
