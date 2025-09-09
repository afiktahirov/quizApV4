<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class CouponRedemption extends Model
{
use HasFactory;
public $timestamps = false; // redeemed_at
protected $fillable = ['coupon_id','store_id','cashier_user_id','redeemed_at','pos_reference'];
protected $casts = ['redeemed_at' => 'datetime'];


public function coupon(){ return $this->belongsTo(Coupon::class); }
public function store(){ return $this->belongsTo(Store::class); }
}
