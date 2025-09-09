<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    use HasFactory;
    protected $fillable = ['merchant_id', 'store_id', 'quiz_category_id', 'title', 'total_questions', 'pass_threshold_pct', 'time_per_question_sec', 'status'];

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }
    public function store()
    {
        return $this->belongsTo(Store::class);
    }
    public function category()
    {
        return $this->belongsTo(QuizCategory::class, 'quiz_category_id');
    }
    public function questions()
    {
        return $this->belongsToMany(Question::class, 'quiz_question_maps')->withPivot('weight');
    }
    public function sessions()
    {
        return $this->hasMany(QuizSession::class);
    }
}
