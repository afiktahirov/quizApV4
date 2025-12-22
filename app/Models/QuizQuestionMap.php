<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizQuestionMap extends Model
{
    //
    public $timestamps = false; // cədvəldə timestamp yoxdur
    protected $fillable = ['quiz_id','question_id','weight'];


    public function quiz()     { return $this->belongsTo(Quiz::class); }
    public function question() { return $this->belongsTo(Question::class); }
}
