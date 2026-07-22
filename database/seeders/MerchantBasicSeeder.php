<?php

namespace Database\Seeders;

use App\Models\{Merchant, Store, User, Question, QuestionOption, QuestionCategory, Quiz, QuizCategory, Plan, QuizRewardTier};
use App\Services\SubscriptionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MerchantBasicSeeder extends Seeder
{
    public function run(): void
    {
        // super admin AdminSeeder-də yaradılır

        // ---------- DEMO MERCHANT (aktiv abunə ilə) ----------
        $merchant = Merchant::query()->updateOrCreate(
            ['slug' => 'demo-restoran'],
            [
                'name'                 => 'Demo Restoran',
                'status'               => 'active',
                'bio'                  => 'Demo restoran hesabı',
                'subscription_ends_at' => now()->addMonth(),
                'coupon_discount_type' => 'percent',
                'coupon_value'         => 10,
                'coupon_ttl_hours'     => 48,
            ],
        );

        // Demo merchant-a Standart paket təyin et (gəlir ledger-i də yaranır) — idempotent
        $standardPlan = Plan::where('slug', 'standard')->first();
        if ($standardPlan && ! $merchant->subscriptions()->where('status', 'active')->exists()) {
            app(SubscriptionService::class)->grant($merchant, $standardPlan, 1, null, 'Seed abunəliyi');
        }

        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name'        => 'Merchant Admin',
                'password'    => Hash::make('password'),
                'merchant_id' => $merchant->id,
                'role'        => User::ROLE_MERCHANT_ADMIN,
            ],
        );

        User::updateOrCreate(
            ['email' => 'cashier@example.com'],
            [
                'name'        => 'Cashier',
                'password'    => Hash::make('password'),
                'merchant_id' => $merchant->id,
                'role'        => User::ROLE_CASHIER,
            ],
        );

        $store = Store::firstOrCreate(
            ['merchant_id' => $merchant->id, 'slug' => 'merkez-filial'],
            ['name' => 'Mərkəz filialı', 'status' => 'active'],
        );

        // ---------- QLOBAL SUAL BAZASI (super admin-in hazır bazası) ----------
        $catGeneral = QuestionCategory::firstOrCreate(
            ['merchant_id' => null, 'slug' => 'umumi'],
            ['name' => 'Ümumi', 'status' => 'active'],
        );

        $catFood = QuestionCategory::firstOrCreate(
            ['merchant_id' => null, 'slug' => 'yemek'],
            ['name' => 'Yemək və içki', 'status' => 'active'],
        );

        $globalQuestions = [
            ['Pizzanın vətəni hansı ölkədir?', ['Türkiyə', 'İtaliya', 'Fransa', 'ABŞ'], 1, $catFood],
            ['Espresso nə ilə hazırlanır?', ['Çay yarpağı', 'Kahve dənəsi', 'Kakao', 'Süd'], 1, $catFood],
            ['Sushi hansı mətbəxə aiddir?', ['Çin', 'Koreya', 'Yaponiya', 'Tayland'], 2, $catFood],
            ['Bir stəkan suda neçə kalori var?', ['0', '50', '100', '10'], 0, $catGeneral],
            ['Dünyanın ən çox istehlak olunan içkisi (sudan sonra)?', ['Kofe', 'Çay', 'Kola', 'Şirə'], 1, $catGeneral],
            ['Balın kristallaşması nəyi göstərir?', ['Xarab olub', 'Təbiidir', 'Saxtadır', 'Su qatılıb'], 1, $catGeneral],
        ];

        $globalIds = [];
        foreach ($globalQuestions as [$title, $opts, $correct, $cat]) {
            $q = Question::firstOrCreate(
                ['merchant_id' => null, 'title->az' => $title],
                [
                    'merchant_id' => null,
                    'title'       => ['az' => $title, 'en' => $title, 'ru' => $title],
                    'type'        => 'mcq',
                    'is_active'   => true,
                ],
            );

            if ($q->options()->count() === 0) {
                foreach ($opts as $i => $text) {
                    QuestionOption::create([
                        'question_id' => $q->id,
                        'option_text' => ['az' => $text, 'en' => $text, 'ru' => $text],
                        'is_correct'  => $i === $correct,
                        'position'    => $i + 1,
                    ]);
                }
            }

            $q->questionCategories()->syncWithoutDetaching([$cat->id]);
            $globalIds[] = $q->id;
        }

        // ---------- MERCHANT-IN ÖZ SUALLARI ----------
        $ownQuestions = [
            ['Restoranımızın ən məşhur yeməyi hansıdır?', ['Kabab', 'Plov', 'Dolma', 'Burger'], 0],
            ['Restoranımız hansı ildə açılıb?', ['2015', '2018', '2020', '2022'], 1],
        ];

        $ownIds = [];
        foreach ($ownQuestions as [$title, $opts, $correct]) {
            $q = Question::firstOrCreate(
                ['merchant_id' => $merchant->id, 'title->az' => $title],
                [
                    'merchant_id' => $merchant->id,
                    'title'       => ['az' => $title, 'en' => $title, 'ru' => $title],
                    'type'        => 'mcq',
                    'is_active'   => true,
                ],
            );

            if ($q->options()->count() === 0) {
                foreach ($opts as $i => $text) {
                    QuestionOption::create([
                        'question_id' => $q->id,
                        'option_text' => ['az' => $text, 'en' => $text, 'ru' => $text],
                        'is_correct'  => $i === $correct,
                        'position'    => $i + 1,
                    ]);
                }
            }

            $ownIds[] = $q->id;
        }

        // ---------- KAMPANİYA ----------
        $quizCat = QuizCategory::firstOrCreate(
            ['slug' => 'welcome-promo'],
            ['name' => 'Welcome Promo', 'status' => 'active'],
        );

        $quiz = Quiz::firstOrCreate(
            ['merchant_id' => $merchant->id, 'title' => 'Giriş Kampaniyası'],
            [
                'store_id'           => $store->id,
                'quiz_category_id'   => $quizCat->id,
                'total_questions'    => 5,
                'pass_threshold_pct' => 60,
                'status'             => 'active',
            ],
        );

        $allIds = array_merge($globalIds, $ownIds);
        $quiz->questions()->syncWithoutDetaching(
            collect($allIds)->mapWithKeys(fn ($id) => [$id => ['weight' => 1]])->toArray()
        );

        // ---------- NÜMUNƏ ENDİRİM PİLLƏLƏRİ ----------
        // Kampaniya default olaraq 'flat' rejimdədir; admin 'tiered'-ə keçsə bu pillələr işə düşür.
        $tiers = [
            ['min_correct' => 3, 'discount_type' => 'percent', 'value' => 5,  'position' => 1],
            ['min_correct' => 4, 'discount_type' => 'percent', 'value' => 10, 'position' => 2],
            ['min_correct' => 5, 'discount_type' => 'percent', 'value' => 15, 'position' => 3],
        ];
        foreach ($tiers as $tier) {
            QuizRewardTier::updateOrCreate(
                ['quiz_id' => $quiz->id, 'min_correct' => $tier['min_correct']],
                $tier,
            );
        }
    }
}
