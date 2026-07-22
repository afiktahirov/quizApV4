<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = ['merchant_id', 'provider', 'external_token_id', 'card_mask'];

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }
}
