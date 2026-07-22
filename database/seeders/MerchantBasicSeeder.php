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
            [$this->t('Pizzanın vətəni hansı ölkədir?', 'Which country is pizza originally from?', 'Из какой страны родом пицца?'),
                [$this->t('Türkiyə', 'Turkey', 'Турция'), $this->t('İtaliya', 'Italy', 'Италия'), $this->t('Fransa', 'France', 'Франция'), $this->t('ABŞ', 'USA', 'США')], 1, $catFood],
            [$this->t('Espresso nə ilə hazırlanır?', 'What is espresso made from?', 'Из чего готовят эспрессо?'),
                [$this->t('Çay yarpağı', 'Tea leaves', 'Чайный лист'), $this->t('Kahve dənəsi', 'Coffee beans', 'Кофейные зёрна'), $this->same('Kakao'), $this->t('Süd', 'Milk', 'Молоко')], 1, $catFood],
            [$this->t('Sushi hansı mətbəxə aiddir?', 'Which cuisine does sushi belong to?', 'К какой кухне относится суши?'),
                [$this->t('Çin', 'Chinese', 'Китайская'), $this->t('Koreya', 'Korean', 'Корейская'), $this->t('Yaponiya', 'Japanese', 'Японская'), $this->t('Tayland', 'Thai', 'Тайская')], 2, $catFood],
            [$this->t('Bir stəkan suda neçə kalori var?', 'How many calories are in a glass of water?', 'Сколько калорий в стакане воды?'),
                [$this->same('0'), $this->same('50'), $this->same('100'), $this->same('10')], 0, $catGeneral],
            [$this->t('Dünyanın ən çox istehlak olunan içkisi (sudan sonra)?', 'What is the world\'s most consumed drink after water?', 'Какой напиток самый популярный в мире после воды?'),
                [$this->t('Kofe', 'Coffee', 'Кофе'), $this->t('Çay', 'Tea', 'Чай'), $this->t('Kola', 'Cola', 'Кола'), $this->t('Şirə', 'Juice', 'Сок')], 1, $catGeneral],
            [$this->t('Balın kristallaşması nəyi göstərir?', 'What does honey crystallizing indicate?', 'О чём говорит кристаллизация мёда?'),
                [$this->t('Xarab olub', 'It has spoiled', 'Испортился'), $this->t('Təbiidir', 'It\'s natural', 'Это естественно'), $this->t('Saxtadır', 'It\'s fake', 'Подделка'), $this->t('Su qatılıb', 'Water was added', 'Добавлена вода')], 1, $catGeneral],
            [$this->t('Şokoladın əsas xammalı hansıdır?', 'What is the main raw material of chocolate?', 'Из чего в основном делают шоколад?'),
                [$this->t('Kakao paxlası', 'Cocoa bean', 'Какао-боб'), $this->t('Qarğıdalı', 'Corn', 'Кукуруза'), $this->t('Şəkər qamışı', 'Sugar cane', 'Сахарный тростник'), $this->t('Fındıq', 'Hazelnut', 'Фундук')], 0, $catFood],
            [$this->t('Fransız mətbəxinin məşhur şirniyyatı hansıdır?', 'Which is a famous French pastry?', 'Какой десерт знаменит во французской кухне?'),
                [$this->same('Baklava'), $this->same('Croissant'), $this->same('Napoleon'), $this->same('Cheesecake')], 1, $catFood],
            [$this->t('Hansı ədviyyat "ədviyyatların kralı" adlanır?', 'Which spice is called the "king of spices"?', 'Какую специю называют «королём специй»?'),
                [$this->t('Darçın', 'Cinnamon', 'Корица'), $this->t('Zəfəran', 'Saffron', 'Шафран'), $this->t('İstiot', 'Pepper', 'Перец'), $this->t('Zəncəfil', 'Ginger', 'Имбирь')], 1, $catFood],
            [$this->t('İtalyan mətbəxinin əsas xəmir yeməyi hansıdır?', 'What is the main pasta-based Italian dish?', 'Какое блюдо — основа итальянской кухни из теста?'),
                [$this->same('Risotto'), $this->same('Pasta'), $this->same('Paella'), $this->same('Tapas')], 1, $catFood],
            [$this->t('Azərbaycanın paytaxtı hansı şəhərdir?', 'What is the capital of Azerbaijan?', 'Какая столица Азербайджана?'),
                [$this->t('Gəncə', 'Ganja', 'Гянджа'), $this->t('Bakı', 'Baku', 'Баку'), $this->t('Sumqayıt', 'Sumgayit', 'Сумгаит'), $this->t('Şəki', 'Sheki', 'Шеки')], 1, $catGeneral],
            [$this->t('Bir ildə neçə ay var?', 'How many months are there in a year?', 'Сколько месяцев в году?'),
                [$this->same('10'), $this->same('12'), $this->same('13'), $this->same('11')], 1, $catGeneral],
            [$this->t('Ən böyük okean hansıdır?', 'Which is the largest ocean?', 'Какой океан самый большой?'),
                [$this->t('Atlantik', 'Atlantic', 'Атлантический'), $this->t('Hind', 'Indian', 'Индийский'), $this->t('Sakit (Pasifik)', 'Pacific', 'Тихий'), $this->t('Şimal Buzlu', 'Arctic', 'Северный Ледовитый')], 2, $catGeneral],
            [$this->t('Yetkin insan bədənində neçə sümük var?', 'How many bones does an adult human have?', 'Сколько костей у взрослого человека?'),
                [$this->same('186'), $this->same('206'), $this->same('150'), $this->same('300')], 1, $catGeneral],
            [$this->t('Göy qurşağında neçə əsas rəng var?', 'How many main colors are in a rainbow?', 'Сколько основных цветов в радуге?'),
                [$this->same('5'), $this->same('6'), $this->same('7'), $this->same('8')], 2, $catGeneral],
        ];

        $globalIds = [];
        foreach ($globalQuestions as [$title, $opts, $correct, $cat]) {
            $q = Question::firstOrCreate(
                ['merchant_id' => null, 'title->az' => $title['az']],
                [
                    'merchant_id' => null,
                    'title'       => $title,
                    'type'        => 'mcq',
                    'is_active'   => true,
                ],
            );

            if ($q->options()->count() === 0) {
                foreach ($opts as $i => $opt) {
                    QuestionOption::create([
                        'question_id' => $q->id,
                        'option_text' => $opt,
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
            [$this->t('Restoranımızın ən məşhur yeməyi hansıdır?', 'What is our restaurant\'s most famous dish?', 'Какое блюдо самое популярное в нашем ресторане?'),
                [$this->same('Kabab'), $this->same('Plov'), $this->same('Dolma'), $this->same('Burger')], 0],
            [$this->t('Restoranımız hansı ildə açılıb?', 'In which year did our restaurant open?', 'В каком году открылся наш ресторан?'),
                [$this->same('2015'), $this->same('2018'), $this->same('2020'), $this->same('2022')], 1],
        ];

        $ownIds = [];
        foreach ($ownQuestions as [$title, $opts, $correct]) {
            $q = Question::firstOrCreate(
                ['merchant_id' => $merchant->id, 'title->az' => $title['az']],
                [
                    'merchant_id' => $merchant->id,
                    'title'       => $title,
                    'type'        => 'mcq',
                    'is_active'   => true,
                ],
            );

            if ($q->options()->count() === 0) {
                foreach ($opts as $i => $opt) {
                    QuestionOption::create([
                        'question_id' => $q->id,
                        'option_text' => $opt,
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

    /** az/en/ru mətn xəritəsi qurur. */
    private function t(string $az, string $en, string $ru): array
    {
        return ['az' => $az, 'en' => $en, 'ru' => $ru];
    }

    /** Hər üç dildə eyni qalan mətn (rəqəm, marka adı və s.) üçün. */
    private function same(string $value): array
    {
        return ['az' => $value, 'en' => $value, 'ru' => $value];
    }
}
