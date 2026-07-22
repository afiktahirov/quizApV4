<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Question extends Model
{
use HasFactory;
protected $fillable = [
'merchant_id','title','type','difficulty','is_active'
];

protected $casts =[
    'title'=>'array',
    'is_active'=>'boolean'
];

/** Qlobal sual bazasına aiddir? (merchant_id yoxdursa, hazır bazadandır) */
public function isGlobal(): bool
{
    return $this->merchant_id === null;
}


public function getTitleAzAttribute(): string
{
        $t = $this->title;
        return is_array($t) ? ($t['az'] ?? '') : (string) $t;
}


public function merchant(){ return $this->belongsTo(Merchant::class); }
public function options(){ return $this->hasMany(QuestionOption::class); }

    public function questionCategories()
    {
        return $this->belongsToMany(
            QuestionCategory::class,
            'question_category_question',
            'question_id',           // bu modelin FK-si pivotda
            'question_category_id'   // qarşı tərəfin FK-si pivotda
        );
    }

    public function quizzes()
    {
        return $this->belongsToMany(Quiz::class, 'quiz_question_maps', 'question_id', 'quiz_id')
            ->withPivot(['weight']);
    }

}
