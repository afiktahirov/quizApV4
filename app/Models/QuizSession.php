<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizSession extends Model
{
    use HasFactory;
    public $timestamps = false; // started_at/finished_at sahələri istifadə olunur
    protected $fillable = ['merchant_id', 'store_id', 'quiz_id', 'user_id', 'started_at', 'finished_at', 'score_pct', 'is_passed', 'ip', 'device_fingerprint', 'channel'];
    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'is_passed' => 'boolean',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }
    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }
    public function store()
    {
        return $this->belongsTo(Store::class);
    }
    public function answers()
    {
        return $this->hasMany(Answer::class);
    }
    public function coupon()
    {
        return $this->hasOne(Coupon::class);
    }
}
