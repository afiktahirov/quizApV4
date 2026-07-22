<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\UiText;

class UiTextController extends Controller
{
    /**
     * Frontend-in statik mətnləri (3 dildə). Admin paneldən idarə olunur.
     */
    public function index()
    {
        return response()->json([
            'texts' => UiText::map(),
        ]);
    }
}
