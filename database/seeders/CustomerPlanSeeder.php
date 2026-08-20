<?php

namespace Database\Seeders;

use App\Models\CustomerPlan;
use Illuminate\Database\Seeder;

/**
 * Başlanğıc üçün nümunə istifadəçi paketləri.
 * php artisan db:seed --class=CustomerPlanSeeder
 */
class CustomerPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name'                  => 'Sınaq',
                'slug'                  => 'trial',
                'price'                 => 0,
                'billing_period'        => 'trial',
                'trial_days'            => 7,
                'max_quizzes_per_day'   => 2,
                'max_coupons_per_month' => 3,
                'description'           => '7 gün pulsuz sınayın.',
                'features'              => ['Gündə 2 quiz', 'Ayda 3 kupon', '7 gün pulsuz'],
                'sort_order'            => 0,
            ],
            [
                'name'                  => 'Standart',
                'slug'                  => 'standart',
                'price'                 => 4.99,
                'billing_period'        => 'monthly',
                'max_quizzes_per_day'   => 5,
                'max_coupons_per_month' => 10,
                'description'           => 'Gündəlik istifadə üçün ideal paket.',
                'features'              => ['Gündə 5 quiz', 'Ayda 10 kupon', 'Bütün mağazalar'],
                'sort_order'            => 1,
            ],
            [
                'name'                  => 'Premium',
                'slug'                  => 'premium',
                'price'                 => 9.99,
                'billing_period'        => 'monthly',
                'max_quizzes_per_day'   => null,
                'max_coupons_per_month' => null,
                'description'           => 'Limitsiz oyun və kupon.',
                'features'              => ['Limitsiz quiz', 'Limitsiz kupon', 'Prioritet dəstək'],
                'sort_order'            => 2,
            ],
        ];

        foreach ($plans as $plan) {
            CustomerPlan::updateOrCreate(['slug' => $plan['slug']], $plan + ['currency' => 'AZN', 'is_active' => true]);
        }
    }
}
