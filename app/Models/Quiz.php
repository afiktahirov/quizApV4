<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    protected $fillable = [
        'merchant_id','store_id','quiz_category_id','title',
        'total_questions','pass_threshold_pct','time_per_question_sec','status','merchant_id'
    ];

    public function merchants()
    {
        return $this->belongsToMany(Merchant::class, 'merchant_quiz');
    }

    public function store()    { return $this->belongsTo(Store::class); }

    public function category()
    {
        return $this->belongsTo(QuizCategory::class, 'quiz_category_id');
    }

    // BelongsToMany suallar (pivot: quiz_question_maps)
    public function questions()
    {
        return $this->belongsToMany(Question::class, 'quiz_question_maps', 'quiz_id', 'question_id')
            ->withPivot(['weight']);  // pivot sahəsi
        // ->withTimestamps();     // yalnız pivot cədvəldə created_at/updated_at varsa
    }

    // Pivot modelindən istifadə etmək istəyirsənsə (opsional)
    public function questionMaps()
    {
        return $this->hasMany(QuizQuestionMap::class, 'quiz_id');
    }

    public function sessions()
    {
        return $this->hasMany(QuizSession::class);
    }
}
