<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'slug' => 'basic', 'name' => 'Başlanğıc', 'price' => 29.99,
                'billing_period' => 'monthly', 'sort_order' => 1,
                'max_quizzes' => 1, 'max_questions' => 20, 'max_stores' => 1, 'max_ads' => 2,
                'description' => 'Kiçik obyektlər üçün başlanğıc paket.',
            ],
            [
                'slug' => 'standard', 'name' => 'Standart', 'price' => 59.99,
                'billing_period' => 'monthly', 'sort_order' => 2,
                'max_quizzes' => 5, 'max_questions' => 100, 'max_stores' => 3, 'max_ads' => 10,
                'description' => 'Orta ölçülü müəssisələr üçün.',
            ],
            [
                'slug' => 'premium', 'name' => 'Premium', 'price' => 129.99,
                'billing_period' => 'monthly', 'sort_order' => 3,
                'max_quizzes' => null, 'max_questions' => null, 'max_stores' => null, 'max_ads' => null,
                'description' => 'Limitsiz — böyük şəbəkələr üçün.',
            ],
        ];

        foreach ($plans as $p) {
            Plan::updateOrCreate(['slug' => $p['slug']], $p + ['currency' => 'AZN', 'is_active' => true]);
        }
    }
}
