<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id', 'store_id', 'quiz_category_id', 'title',
        'total_questions', 'pass_threshold_pct', 'time_per_question_sec', 'status',
        'reward_mode',
    ];

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

    // Suallar (pivot: quiz_question_maps)
    public function questions()
    {
        return $this->belongsToMany(Question::class, 'quiz_question_maps', 'quiz_id', 'question_id')
            ->withPivot(['weight']);
    }

    public function questionMaps()
    {
        return $this->hasMany(QuizQuestionMap::class, 'quiz_id');
    }

    public function sessions()
    {
        return $this->hasMany(QuizSession::class);
    }

    // Pilləli endirim pillələri (yüksək min_correct öndə)
    public function rewardTiers()
    {
        return $this->hasMany(QuizRewardTier::class)->orderByDesc('min_correct');
    }
}
