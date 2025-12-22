<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class QuestionCategory extends Model
{
use HasFactory;
protected $fillable = ['merchant_id','name','slug','status'];


    public function questions()
    {
        return $this->belongsToMany(
            Question::class,
            'question_category_question',
            'question_category_id', // bu modelin FK-si pivotda
            'question_id'           // qarşı tərəfin FK-si pivotda
        );       // pivotda timestamps varsa
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

}
