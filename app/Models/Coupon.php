<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Coupon extends Model
{
use HasFactory;
protected $fillable = [
'code','merchant_id','store_id','quiz_session_id','discount_type','value',
'expires_at','status','signature','qr_payload'
];
protected $casts = ['expires_at' => 'datetime'];


public function session(){ return $this->belongsTo(QuizSession::class,'quiz_session_id'); }
public function merchant(){ return $this->belongsTo(Merchant::class); }
public function store(){ return $this->belongsTo(Store::class); }
public function redemptions(){ return $this->hasMany(CouponRedemption::class); }
}
