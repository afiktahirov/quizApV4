<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class QuestionOption extends Model
{
use HasFactory;
public $timestamps = true;
protected $fillable = ['question_id','option_text','is_correct','position'];

protected  $casts = ['option_text'=>'array'];

public function question(){ return $this->belongsTo(Question::class); }
}
