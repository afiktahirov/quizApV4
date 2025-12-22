<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class QuizCategory extends Model
{
use HasFactory;
protected $fillable = ['merchant_id','name','slug','status'];


public function quizzes(){ return $this->hasMany(Quiz::class,'quiz_category_id'); }
    public function merchant() { return $this->belongsTo(Merchant::class); }

}
