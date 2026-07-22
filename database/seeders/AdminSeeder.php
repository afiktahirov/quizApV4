<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Super admin istifadəçisini yaradır (idempotent).
     * Dəyərləri .env-dən oxuyur, yoxdursa default-lardan istifadə edir.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'superadmin@quizapp.test')],
            [
                'name'        => env('ADMIN_NAME', 'Super Admin'),
                'password'    => Hash::make(env('ADMIN_PASSWORD', 'password')),
                'merchant_id' => null,
                'role'        => User::ROLE_SUPER_ADMIN,
            ],
        );
    }
}
