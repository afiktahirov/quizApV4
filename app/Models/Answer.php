<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Answer extends Model
{
use HasFactory;
public $timestamps = false; // answered_at
protected $fillable = ['quiz_session_id','question_id','selected_option_id','is_correct','answered_at'];
protected $casts = ['answered_at' => 'datetime','is_correct'=>'boolean'];


public function session(){ return $this->belongsTo(QuizSession::class,'quiz_session_id'); }
public function question(){ return $this->belongsTo(Question::class); }
public function option(){ return $this->belongsTo(QuestionOption::class,'selected_option_id'); }
}
