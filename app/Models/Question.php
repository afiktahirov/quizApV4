<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Question extends Model
{
use HasFactory;
protected $fillable = [
'merchant_id','store_id','title','type','difficulty','is_active'
];


public function merchant(){ return $this->belongsTo(Merchant::class); }
public function store(){ return $this->belongsTo(Store::class); }
public function options(){ return $this->hasMany(QuestionOption::class); }
public function categories(){ return $this->belongsToMany(QuestionCategory::class,'question_category_question'); }
}
