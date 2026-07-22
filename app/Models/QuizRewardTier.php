<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizRewardTier extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_id', 'min_correct', 'discount_type', 'value', 'position',
    ];

    protected $casts = [
        'min_correct' => 'integer',
        'value'       => 'decimal:2',
        'position'    => 'integer',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }
}
