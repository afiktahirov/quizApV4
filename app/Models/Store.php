<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Store extends Model
{
use HasFactory;
protected $fillable = ['merchant_id','name','slug','address','status'];


public function merchant(){ return $this->belongsTo(Merchant::class); }
public function quizzes(){ return $this->hasMany(Quiz::class); }
}
