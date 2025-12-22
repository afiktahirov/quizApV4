<?php

namespace Database\Seeders;

use App\Models\{Merchant, Store, User, Question, QuestionOption, QuestionCategory, Quiz, QuizCategory};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MerchantBasicSeeder extends Seeder
{
    public function run(): void
    {
        $merchant = Merchant::query()->firstOrCreate(
            [
                'slug' => 'default-merchant',
            ],
            [
                'name' => 'Default Merchant',
                'status' => 'active',
                'settings' => ['pass_threshold_pct' => 70, 'coupon_ttl_hours' => 48],
            ],
        );

        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Merchant Admin',
                'password' => Hash::make('password'),
                'merchant_id' => $merchant->id,
                'role' => 'merchant_admin',
            ],
        );

        User::updateOrCreate(
            ['email' => 'cashier@example.com'],
            [
                'name' => 'Cashier',
                'password' => Hash::make('password'),
                'merchant_id' => $merchant->id,
                'role' => 'cashier',
            ],
        );

        $store = Store::firstOrCreate(
            [
                'merchant_id' => $merchant->id,
                'slug' => 'central-store',
            ],
            [
                'name' => 'Central Store',
                'status' => 'active',
            ],
        );

        // Kateqoriyalar
        $catMenu = QuestionCategory::firstOrCreate(
            [
                'merchant_id' => $merchant->id,
                'slug' => 'menu',
            ],
            ['name' => 'Menyu', 'status' => 'active'],
        );

        $catBrand = QuestionCategory::firstOrCreate(
            [
                'merchant_id' => $merchant->id,
                'slug' => 'brand',
            ],
            ['name' => 'Brend Tarixi', 'status' => 'active'],
        );

        $questions = collect([['Burgerin əsas inqrediyenti nədir?', ['Dana əti', 'Toyuq', 'Balıq', 'Tərəvəz kotleti'], 0], ['Kartof fri necə bişirilir?', ['Qızardılır', 'Qaynadılır', 'Buxarda bişirilir', 'Sobada qurudulur'], 0], ['Cola hansı kateqoriyaya daxildir?', ['Sərinləşdirici', 'Süd', 'Şirə', 'Su'], 0], ['Pizza mənşə ölkəsi?', ['Türkiyə', 'İtaliya', 'Fransa', 'ABŞ'], 1], ['Sosların saxlanma yeri?', ['Otaq temperaturu', 'Soyuducu', 'Dondurucu', 'Balkon'], 1], ['Brendimizin yaranma ili?', ['1990', '2001', '2010', '2016'], 3]])->map(function ($row) use ($merchant, $store, $catMenu, $catBrand) {
            [$title, $opts, $correct] = $row;
            $q = Question::create([
                'merchant_id' => $merchant->id,
                'store_id' => null,
                'title' => $title,
                'type' => 'mcq',
                'is_active' => true,
            ]);
            foreach ($opts as $i => $text) {
                QuestionOption::create([
                    'question_id' => $q->id,
                    'option_text' => $text,
                    'is_correct' => $i === $correct,
                    'position' => $i + 1,
                ]);
            }
            $q->categories()->sync([$catMenu->id, $catBrand->id]);
            return $q;
        });

        $quizCat = QuizCategory::firstOrCreate(
            [
                'merchant_id' => $merchant->id,
                'slug' => 'welcome-promo',
            ],
            ['name' => 'Welcome Promo', 'status' => 'active'],
        );

        $quiz = Quiz::create([
            'merchant_id' => $merchant->id,
            'store_id' => $store->id,
            'quiz_category_id' => $quizCat->id,
            'title' => 'Giriş Kampaniyası',
            'total_questions' => 5,
            'pass_threshold_pct' => 60,
            'status' => 'active',
        ]);

        $quiz->questions()->sync($questions->pluck('id')->take(5)->mapWithKeys(fn($id) => [$id => ['weight' => 1]])->toArray());
    }
}
