<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Merchant extends Model
{
use HasFactory;
protected $fillable = ['name','slug','status','settings'];
protected $casts = ['settings' => 'array'];


public function users(){ return $this->hasMany(User::class); }
public function quizzes(){return $this->belongsToMany(Quiz::class, 'merchant_quiz');}

    
}
