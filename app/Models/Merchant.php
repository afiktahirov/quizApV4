<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Merchant extends Model
{
use HasFactory;
    protected $fillable = [
        'name','slug','status','bio','photo',
        'latitude','longitude','geojson',
    ];
protected $casts = [
    'settings' => 'array',
    'geojson' => 'array'
];

    protected function location(): Attribute
    {
        return Attribute::make(
            get: fn ($value, array $attributes) => [
                'lat'    => $attributes['latitude'] ?? null,
                'lng'    => $attributes['longitude'] ?? null,
                'geojson' => $this->geojson ?? null,
            ],
            
            set: fn (?array $value) => [
                'latitude'  => $value['lat'] ?? null,
                'longitude' => $value['lng'] ?? null,
                'geojson'   => $value['geojson'] ?? null,
            ],
        );
    }

public function users(){ return $this->hasMany(User::class); }
public function quizzes(){return $this->belongsToMany(Quiz::class, 'merchant_quiz');}

    
}
