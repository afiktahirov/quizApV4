<?php

namespace Database\Seeders;

use App\Models\Ad;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\MerchantSubscription;
use App\Models\Plan;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\QuizCategory;
use App\Models\QuizRewardTier;
use App\Models\QuizSession;
use App\Models\Store;
use App\Models\UiText;
use App\Models\User;
use App\Services\CouponService;
use App\Services\SubscriptionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Admin panelin bütün ekranlarını (Merchants, Stores, Users, Questions,
 * Quizzes, QuizSessions, Coupons, Plans/Subscriptions, Ads, UiTexts)
 * dolduran, real işgüzarlığa bənzəyən demo data.
 *
 * Təkrar işlədilə bilər: mövcud qeydlər üstünə yazılmır (updateOrCreate/
 * firstOrCreate + "artıq sessiya varsa keç" məntiqi).
 */
class DemoDataSeeder extends Seeder
{
    private SubscriptionService $subscriptions;

    private CouponService $coupons;

    /** @var Collection<int, Customer> */
    private Collection $customers;

    public function run(): void
    {
        $this->subscriptions = app(SubscriptionService::class);
        $this->coupons       = app(CouponService::class);

        // Bu seeder-in asıl olduğu baza data (idempotent — təkrar çağırmaq təhlükəsizdir)
        $this->call([
            AdminSeeder::class,
            PlanSeeder::class,
            MerchantBasicSeeder::class,
        ]);

        $this->seedUiTexts();
        $globalCategoryIds = $this->seedGlobalQuestionPools();
        $quizCategoryIds   = $this->seedQuizCategories();
        $this->customers   = $this->seedCustomers();

        $configs = $this->merchantConfigs($quizCategoryIds);

        foreach ($configs as $config) {
            $this->buildMerchant($config, $globalCategoryIds);
        }

        $this->extendDemoRestoran();
    }

    // ------------------------------------------------------------------
    // Sayt mətnləri
    // ------------------------------------------------------------------

    private function seedUiTexts(): void
    {
        $rows = [
            ['nav.stores', 'nav', 'Mağazalar', 'Stores', 'Магазины'],
            ['nav.coupons', 'nav', 'Kuponlarım', 'My Coupons', 'Мои купоны'],
            ['nav.login', 'nav', 'Giriş', 'Login', 'Вход'],
            ['nav.register', 'nav', 'Qeydiyyat', 'Register', 'Регистрация'],
            ['nav.logout', 'nav', 'Çıxış', 'Logout', 'Выход'],
            ['home.title', 'home', 'Ən yaxşı endirimləri kəşf et', 'Discover the best deals', 'Откройте лучшие предложения'],
            ['home.subtitle', 'home', 'Sevimli məkanlarında quiz oyna, endirim qazan', 'Play quizzes at your favorite places and earn discounts', 'Играйте в квизы в любимых заведениях и получайте скидки'],
            ['store.play_button', 'store', 'Quizə başla', 'Start the quiz', 'Начать квиз'],
            ['store.no_quiz', 'store', 'Hazırda aktiv kampaniya yoxdur', 'No active campaign right now', 'Сейчас нет активной кампании'],
            ['play.start', 'play', 'Başla', 'Start', 'Начать'],
            ['play.time_left', 'play', 'Qalan vaxt', 'Time left', 'Осталось времени'],
            ['play.next', 'play', 'Növbəti', 'Next', 'Далее'],
            ['play.finish', 'play', 'Bitir', 'Finish', 'Завершить'],
            ['play.result_title', 'play', 'Nəticən', 'Your result', 'Ваш результат'],
            ['coupons.empty', 'coupons', 'Hələ kuponun yoxdur', 'You have no coupons yet', 'У вас пока нет купонов'],
            ['coupons.expires', 'coupons', 'Bitmə tarixi', 'Expires', 'Истекает'],
            ['auth.login_title', 'auth', 'Hesabına daxil ol', 'Log in to your account', 'Войдите в аккаунт'],
            ['auth.register_title', 'auth', 'Yeni hesab yarat', 'Create a new account', 'Создать аккаунт'],
            ['auth.phone', 'auth', 'Telefon nömrəsi', 'Phone number', 'Номер телефона'],
            ['validation.required', 'validation', 'Bu xana mütləq doldurulmalıdır', 'This field is required', 'Это поле обязательно'],
            ['validation.phone_invalid', 'validation', 'Telefon nömrəsi düzgün deyil', 'Invalid phone number', 'Неверный номер телефона'],
            ['errors.generic', 'errors', 'Xəta baş verdi, yenidən cəhd edin', 'Something went wrong, please try again', 'Произошла ошибка, попробуйте снова'],
            ['errors.not_found', 'errors', 'Tapılmadı', 'Not found', 'Не найдено'],
            ['discount.you_won', 'discount', 'Təbrik edirik, {discount} endirim qazandınız!', 'Congratulations, you won a {discount} discount!', 'Поздравляем, вы выиграли скидку {discount}!'],
            ['discount.claim_button', 'discount', 'Kuponu al', 'Claim coupon', 'Получить купон'],
        ];

        foreach ($rows as [$key, $group, $az, $en, $ru]) {
            UiText::updateOrCreate(
                ['key' => $key],
                ['group' => $group, 'value' => ['az' => $az, 'en' => $en, 'ru' => $ru]],
            );
        }
    }

    // ------------------------------------------------------------------
    // Qlobal sual bankı (sahə üzrə) + kampaniya kateqoriyaları
    // ------------------------------------------------------------------

    /** @return array<string,int> slug => question_category_id */
    private function seedGlobalQuestionPools(): array
    {
        $pools = [
            'idman-saglamlik' => [
                'name'      => 'İdman və Sağlamlıq',
                'questions' => [
                    [$this->t('Futbol matçında bir komanda neçə oyunçu ilə oyuna başlayır?', 'How many players does a football team start a match with?', 'Со сколькими игроками команда начинает футбольный матч?'),
                        [$this->same('10'), $this->same('11'), $this->same('12'), $this->same('9')], 1],
                    [$this->t('İnsan bədənində ən böyük əzələ hansıdır?', 'What is the largest muscle in the human body?', 'Какая самая большая мышца в теле человека?'),
                        [$this->t('Bald əzələsi', 'Calf', 'Икроножная'), $this->t('Bud əzələsi', 'Thigh (quads)', 'Бедренная'), $this->same('Bisep'), $this->t('Trapesiya', 'Trapezius', 'Трапеция')], 1],
                    [$this->t('Gündəlik tövsiyə olunan orta su qəbulu təxminən neçə litrdir?', 'What is the recommended daily water intake approximately?', 'Сколько воды рекомендуется пить в день примерно?'),
                        [$this->same('0.5 L'), $this->same('1 L'), $this->same('2-2.5 L'), $this->same('5 L')], 2],
                    [$this->t('Olimpiya Oyunları neçə ildən bir keçirilir?', 'Every how many years are the Olympic Games held?', 'Через сколько лет проводятся Олимпийские игры?'),
                        [$this->same('2'), $this->same('3'), $this->same('4'), $this->same('5')], 2],
                    [$this->t('Basketbolda adi atışdan topun səbətə düşməsi neçə xal verir?', 'How many points does a regular basketball shot score?', 'Сколько очков даёт обычный бросок в баскетболе?'),
                        [$this->same('1'), $this->same('2'), $this->same('3'), $this->same('4')], 1],
                    [$this->t('İnsan ürəyi bədənin hansı tərəfinə yaxın yerləşir?', 'Which side of the body is the human heart closer to?', 'К какой стороне тела ближе расположено сердце?'),
                        [$this->t('Sağ tərəfə', 'Right side', 'Правая сторона'), $this->t('Sol tərəfə', 'Left side', 'Левая сторона'), $this->t('Tam mərkəzə', 'Exact center', 'Центр'), $this->t('Kürəyə', 'Back', 'Спина')], 1],
                    [$this->t('Marafon qaçışının məsafəsi təxminən neçə km-dir?', 'The marathon distance is approximately how many km?', 'Какова примерная дистанция марафона в км?'),
                        [$this->same('21 km'), $this->same('32 km'), $this->same('42 km'), $this->same('50 km')], 2],
                    [$this->t('Yoga hansı ölkədə yaranıb?', 'In which country did yoga originate?', 'В какой стране зародилась йога?'),
                        [$this->t('Çin', 'China', 'Китай'), $this->t('Hindistan', 'India', 'Индия'), $this->t('Yaponiya', 'Japan', 'Япония'), $this->same('Tibet')], 1],
                ],
            ],
            'texnologiya' => [
                'name'      => 'Texnologiya',
                'questions' => [
                    [$this->t('İlk kommersiya smartfonu hansı illərdə buraxılıb?', 'In which years was the first commercial smartphone released?', 'В каких годах вышел первый коммерческий смартфон?'),
                        [$this->same('1983'), $this->same('1992-93'), $this->same('2000'), $this->same('2007')], 1],
                    [$this->t('USB abbreviaturası nəyi bildirir?', 'What does the USB abbreviation stand for?', 'Что означает аббревиатура USB?'),
                        [$this->same('Universal Serial Bus'), $this->same('United System Board'), $this->same('Unified Signal Base'), $this->same('Universal System Bus')], 0],
                    [$this->t('Wi-Fi hansı əlaqə növüdür?', 'What type of connection is Wi-Fi?', 'К какому типу связи относится Wi-Fi?'),
                        [$this->t('Kabelli', 'Wired', 'Проводная'), $this->t('Simsiz', 'Wireless', 'Беспроводная'), $this->t('Optik', 'Optical', 'Оптическая'), $this->t('Peyk', 'Satellite', 'Спутниковая')], 1],
                    [$this->t('HTTP abbreviaturası nəyi bildirir?', 'What does the HTTP abbreviation stand for?', 'Что означает аббревиатура HTTP?'),
                        [$this->same('HyperText Transfer Protocol'), $this->same('High Tech Transfer Process'), $this->same('Home Terminal Text Protocol'), $this->same('Hyper Terminal Path')], 0],
                    [$this->t('Bluetooth adı haradan götürülüb?', 'Where does the name Bluetooth come from?', 'Откуда произошло название Bluetooth?'),
                        [$this->t('Bir kompaniyadan', 'A company', 'Компании'), $this->t('Kraldan', 'A king', 'Короля'), $this->t('Rəngdən', 'A color', 'Цвета'), $this->t('Termindən', 'A term', 'Термина')], 1],
                    [$this->t('Bir baytda neçə bit var?', 'How many bits are in a byte?', 'Сколько бит в одном байте?'),
                        [$this->same('4'), $this->same('8'), $this->same('16'), $this->same('32')], 1],
                    [$this->t('Süni intellektin ingiliscə abbreviaturası hansıdır?', 'What is the English abbreviation for artificial intelligence?', 'Какая английская аббревиатура у искусственного интеллекта?'),
                        [$this->same('AI'), $this->same('IT'), $this->same('ML'), $this->same('VR')], 0],
                    [$this->t('SSD sərt diskə (HDD) nisbətən nə ilə fərqlənir?', 'How does an SSD differ from a hard disk (HDD)?', 'Чем SSD отличается от жёсткого диска (HDD)?'),
                        [$this->t('Daha yavaşdır', 'Slower', 'Медленнее'), $this->t('Sürətli, hərəkətsiz', 'Fast, no moving parts', 'Быстрый, без частей'), $this->t('Bahalı və yavaş', 'Expensive & slow', 'Дорогой и медленный'), $this->t('Fərqi yoxdur', 'No difference', 'Нет разницы')], 1],
                ],
            ],
            'gozellik' => [
                'name'      => 'Gözəllik',
                'questions' => [
                    [$this->t('Dırnaqların orta böyümə sürəti ayda təxminən neçə mm-dir?', 'What is the average nail growth rate per month in mm?', 'Какова средняя скорость роста ногтей в мм за месяц?'),
                        [$this->same('1 mm'), $this->same('3 mm'), $this->same('10 mm'), $this->same('20 mm')], 1],
                    [$this->t('Saçın əsas zülalı hansıdır?', 'What is the main protein in hair?', 'Какой основной белок в волосах?'),
                        [$this->same('Kollagen'), $this->same('Keratin'), $this->same('Elastin'), $this->same('Melanin')], 1],
                    [$this->t('Günəşdən qorunma kremi hansı abbreviaturayla işarələnir?', 'What abbreviation marks sunscreen cream?', 'Какой аббревиатурой обозначается солнцезащитный крем?'),
                        [$this->same('UV'), $this->same('SPF'), $this->same('PH'), $this->same('UVX')], 1],
                    [$this->t('Ətirlərin əsas tərkib hissəsi hansıdır?', 'What is the main component of perfumes?', 'Какой основной компонент духов?'),
                        [$this->t('Su', 'Water', 'Вода'), $this->t('Alkoqol və ətir yağları', 'Alcohol & fragrance oils', 'Спирт и масла'), $this->t('Duz', 'Salt', 'Соль'), $this->t('Şəkər', 'Sugar', 'Сахар')], 1],
                    [$this->t('Cild əsasən neçə əsas tipə bölünür?', 'How many main skin types are there?', 'На сколько основных типов делится кожа?'),
                        [$this->same('2'), $this->same('3'), $this->same('4'), $this->same('5')], 2],
                    [$this->t('Manikürdə "fransız üslubu" nə ilə tanınır?', 'What is the "French style" manicure known for?', 'Чем известен маникюр во «французском стиле»?'),
                        [$this->t('Qırmızı lak', 'Red polish', 'Красный лак'), $this->t('Ağ ucluq', 'White tip', 'Белый кончик'), $this->t('Qara naxış', 'Black pattern', 'Чёрный узор'), $this->t('Qızılı bəzək', 'Gold decor', 'Золотой декор')], 1],
                    [$this->t('Kollagen dərinin hansı xüsusiyyətinə görə vacibdir?', 'Which skin property makes collagen important?', 'За какое свойство кожи отвечает коллаген?'),
                        [$this->t('Rənginə', 'Its color', 'Цвет'), $this->t('Elastikliyinə', 'Its elasticity', 'Эластичность'), $this->t('Qoxusuna', 'Its scent', 'Запах'), $this->t('İsti saxlamasına', 'Heat retention', 'Теплоудержание')], 1],
                    [$this->t('Dırnaq ətrafındakı incə dəriyə nə deyilir?', 'What is the thin skin around the nail called?', 'Как называется тонкая кожа вокруг ногтя?'),
                        [$this->same('Kutikula'), $this->same('Epidermis'), $this->same('Follikul'), $this->same('Dermis')], 0],
                ],
            ],
        ];

        $ids = [];
        foreach ($pools as $slug => $pool) {
            $category = QuestionCategory::firstOrCreate(
                ['merchant_id' => null, 'slug' => $slug],
                ['name' => $pool['name'], 'status' => 'active'],
            );

            foreach ($pool['questions'] as [$title, $opts, $correct]) {
                $q = $this->question(null, $title, $opts, $correct);
                $q->questionCategories()->syncWithoutDetaching([$category->id]);
            }

            $ids[$slug] = $category->id;
        }

        return $ids;
    }

    /** @return array<string,int> slug => quiz_category_id */
    private function seedQuizCategories(): array
    {
        $rows = [
            'restoran-kampaniyalari'    => 'Restoran Kampaniyaları',
            'kafe-kampaniyalari'        => 'Kafe Kampaniyaları',
            'idman-kampaniyalari'       => 'İdman Kampaniyaları',
            'gozellik-kampaniyalari'    => 'Gözəllik Kampaniyaları',
            'texnologiya-kampaniyalari' => 'Texnologiya Kampaniyaları',
        ];

        $ids = [];
        foreach ($rows as $slug => $name) {
            $ids[$slug] = QuizCategory::firstOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'status' => 'active'],
            )->id;
        }

        return $ids;
    }

    // ------------------------------------------------------------------
    // Demo müştərilər (bütün merchant-lar üçün ortaq oyunçu bazası)
    // ------------------------------------------------------------------

    /** @return Collection<int, Customer> */
    private function seedCustomers(): Collection
    {
        $names = [
            'Aygün Məmmədova', 'Kamran İsmayılov', 'Nərmin Əliyeva', 'Fərid Quliyev',
            'Ləman Hüseynova', 'Vüqar Abbasov', 'Sevinc Rzayeva', 'Elşən Nəbiyev',
            'Türkan Cəfərova', 'Rüstəm Vəliyev', 'Gülnar Səfərova', 'Anar Zeynalov',
            'Aysel Tağıyeva', 'Murad Bağırov', 'Nigar Orucova', 'Emin Hacıyev',
            'Ülviyyə Sadıqova', 'Cavid Rəhimov',
        ];

        $operators = ['50', '51', '55', '70', '77', '99'];

        return collect($names)->map(function (string $name, int $i) use ($operators) {
            $phone = '+994' . $operators[$i % count($operators)] . str_pad((string) (2000000 + $i), 7, '0', STR_PAD_LEFT);
            $email = Str::lower(Str::of($name)->ascii()->replace(' ', '.')) . '@mail.test';

            return Customer::firstOrCreate(
                ['phone' => $phone],
                ['name' => $name, 'email' => $email, 'password' => Hash::make('password')],
            );
        });
    }

    // ------------------------------------------------------------------
    // Merchant konfiqurasiyaları
    // ------------------------------------------------------------------

    private function merchantConfigs(array $qc): array
    {
        return [
            // ---------------- Şirvan Kababxana ----------------
            [
                'slug' => 'shirvan-kababxana', 'name' => 'Şirvan Kababxana',
                'bio'  => 'Bakının mərkəzində ənənəvi Azərbaycan mətbəxi restoranı. Kabab, plov və milli yeməklərin ən dadlı ünvanı.',
                'coupon_discount_type' => 'percent', 'coupon_value' => 15, 'coupon_ttl_hours' => 72,
                'plan_slug' => 'standard', 'sub_end_days' => 45, 'status' => 'active',
                'history' => ['plan_name' => 'Başlanğıc', 'plan_slug' => 'basic', 'amount' => 29.99, 'months_ago_start' => 6, 'months_ago_end' => 5],
                'stores' => ['Nərimanov filialı', 'Gənclik filialı'],
                'admin' => ['name' => 'Elvin Məmmədov', 'email' => 'admin@shirvankababxana.az'],
                'cashier' => ['name' => 'Aynur Hüseynova', 'email' => 'kassir@shirvankababxana.az'],
                'category' => ['name' => 'Milli Mətbəx', 'slug' => 'milli-metbex'],
                'own_questions' => [
                    [$this->t('Şirvan Kababxana hansı ildə fəaliyyətə başlayıb?', 'In which year did Shirvan Kababxana start operating?', 'В каком году открылся ресторан Shirvan Kababxana?'),
                        [$this->same('2012'), $this->same('2016'), $this->same('2019'), $this->same('2021')], 1],
                    [$this->t('Restoranımızın ən çox sifariş edilən yeməyi hansıdır?', 'What is our restaurant\'s most ordered dish?', 'Какое блюдо у нас заказывают чаще всего?'),
                        [$this->same('Lülə kabab'), $this->same('Bozbaş'), $this->same('Dolma'), $this->same('Plov')], 0],
                    [$this->t('Saj yeməyi ənənəvi olaraq nə üzərində bişirilir?', 'What is saj dish traditionally cooked on?', 'На чём традиционно готовят блюдо садж?'),
                        [$this->t('Metal tava (saj)', 'Metal pan (saj)', 'Металлический садж'), $this->t('Fırın', 'Oven', 'Печь'), $this->t('Barbekü', 'Barbecue', 'Барбекю'), $this->t('Qazan', 'Cauldron', 'Казан')], 0],
                    [$this->t('Kabab bişirmək üçün ənənəvi olaraq nədən istifadə olunur?', 'What is traditionally used to cook kabab?', 'Что традиционно используют для приготовления кебаба?'),
                        [$this->t('Qaz sobası', 'Gas stove', 'Газовая плита'), $this->t('Kömür-mangal', 'Charcoal grill', 'Уголь-мангал'), $this->t('Elektrik', 'Electric', 'Электричество'), $this->t('Mikrodalğalı', 'Microwave', 'Микроволновка')], 1],
                    [$this->t('Bozbaş hansı mətbəxin ənənəvi yeməyidir?', 'Bozbash is a traditional dish of which cuisine?', 'Бозбаш — традиционное блюдо какой кухни?'),
                        [$this->t('Türk', 'Turkish', 'Турецкая'), $this->t('Azərbaycan', 'Azerbaijani', 'Азербайджанская'), $this->t('Gürcü', 'Georgian', 'Грузинская'), $this->t('İran', 'Iranian', 'Иранская')], 1],
                    [$this->t('Dolma adətən nə ilə doldurulur?', 'What is dolma usually stuffed with?', 'Чем обычно начиняют долму?'),
                        [$this->t('Düyü və ət', 'Rice & meat', 'Рис и мясо'), $this->t('Şəkər', 'Sugar', 'Сахар'), $this->t('Un', 'Flour', 'Мука'), $this->t('Kartof', 'Potato', 'Картофель')], 0],
                ],
                'global_pools' => ['yemek', 'umumi'],
                'quiz_category' => $qc['restoran-kampaniyalari'],
                'quizzes' => [
                    ['title' => 'Kabab Bilicisi', 'total' => 5, 'pass' => 60, 'time' => 20, 'status' => 'active', 'reward_mode' => 'flat', 'sessions' => 12],
                    ['title' => 'Milli Mətbəx Yarışı', 'total' => 4, 'pass' => 50, 'time' => 15, 'status' => 'active', 'reward_mode' => 'tiered',
                        'tiers' => [[2, 'percent', 5], [3, 'percent', 10], [4, 'percent', 20]], 'sessions' => 10],
                ],
                'ads' => [
                    ['title' => 'Cümə Axşamı Ailə Endirimi', 'status' => 'active', 'starts' => -10, 'ends' => 20],
                    ['title' => 'Yeni Saj Menyusu', 'status' => 'active', 'starts' => -2, 'ends' => null],
                ],
            ],

            // ---------------- Rəngarəng Kafe ----------------
            [
                'slug' => 'rengareng-kafe', 'name' => 'Rəngarəng Kafe',
                'bio'  => 'Şirin dadlar və məhəbbətlə hazırlanan kofe növləri ilə tanınan butik kafe.',
                'coupon_discount_type' => 'amount', 'coupon_value' => 3, 'coupon_ttl_hours' => 24,
                'plan_slug' => 'basic', 'sub_end_days' => 3, 'status' => 'active',
                'history' => null,
                'stores' => ['28 May filialı'],
                'admin' => ['name' => 'Vüsalə Quliyeva', 'email' => 'admin@rengarengkafe.az'],
                'cashier' => ['name' => 'Tural Əliyev', 'email' => 'kassir@rengarengkafe.az'],
                'category' => ['name' => 'Kafe Mədəniyyəti', 'slug' => 'kafe-medeniyyeti'],
                'own_questions' => [
                    [$this->t('Rəngarəng Kafe-nin imza içkisi hansıdır?', 'What is Rengareng Cafe\'s signature drink?', 'Какой фирменный напиток в кафе Rengareng?'),
                        [$this->same('Frappe'), $this->same('Latte macchiato'), $this->same('Qarabu qəhvə'), $this->same('Flat white')], 1],
                    [$this->t('Kafemizdə hansı şirniyyat ən populyardır?', 'Which dessert is most popular at our cafe?', 'Какой десерт самый популярный в нашем кафе?'),
                        [$this->same('Napoleon tort'), $this->same('Cheesecake'), $this->same('Tiramisu'), $this->same('Medovik')], 1],
                    [$this->t('Cappuccino-nun tərkibində hansı üç element var?', 'What three elements make up a cappuccino?', 'Из каких трёх элементов состоит капучино?'),
                        [$this->t('Espresso, süd, köpük', 'Espresso, milk, foam', 'Эспрессо, молоко, пена'), $this->t('Espresso, su, şəkər', 'Espresso, water, sugar', 'Эспрессо, вода, сахар'), $this->t('Çay, süd, bal', 'Tea, milk, honey', 'Чай, молоко, мёд'), $this->t('Kakao, süd, krem', 'Cocoa, milk, cream', 'Какао, молоко, крем')], 0],
                    [$this->t('"Latte" italyanca hansı mənanı verir?', 'What does "Latte" mean in Italian?', 'Что означает слово «Latte» по-итальянски?'),
                        [$this->t('Qəhvə', 'Coffee', 'Кофе'), $this->t('Süd', 'Milk', 'Молоко'), $this->t('Şəkər', 'Sugar', 'Сахар'), $this->t('Köpük', 'Foam', 'Пена')], 1],
                    [$this->t('Kafemizdə hansı desert daha çox soyuq təqdim olunur?', 'Which dessert at our cafe is usually served cold?', 'Какой десерт в нашем кафе обычно подают холодным?'),
                        [$this->t('İsti şokolad', 'Hot chocolate', 'Горячий шоколад'), $this->t('Dondurma', 'Ice cream', 'Мороженое'), $this->t('Pasta', 'Pastry', 'Пирожное'), $this->t('Çay', 'Tea', 'Чай')], 1],
                ],
                'global_pools' => ['yemek', 'umumi'],
                'quiz_category' => $qc['kafe-kampaniyalari'],
                'quizzes' => [
                    ['title' => 'Şirniyyat Sevgilisi', 'total' => 5, 'pass' => 70, 'time' => 15, 'status' => 'active', 'reward_mode' => 'flat', 'sessions' => 14],
                    ['title' => 'Kofe Ustası', 'total' => 4, 'pass' => 50, 'time' => 15, 'status' => 'active', 'reward_mode' => 'tiered',
                        'tiers' => [[2, 'percent', 5], [3, 'percent', 8], [4, 'percent', 15]], 'sessions' => 9],
                ],
                'ads' => [
                    ['title' => 'Səhər Kofe Kampaniyası', 'status' => 'active', 'starts' => -5, 'ends' => 10],
                    ['title' => 'Qış Şirniyyat Kolleksiyası', 'status' => 'inactive', 'starts' => -60, 'ends' => -10],
                ],
            ],

            // ---------------- FitLife İdman Zalı ----------------
            [
                'slug' => 'fitlife-idman-zali', 'name' => 'FitLife İdman Zalı',
                'bio'  => 'Müasir avadanlıqlarla təchiz olunmuş, təcrübəli məşqçi heyəti olan idman zalı.',
                'coupon_discount_type' => 'percent', 'coupon_value' => 20, 'coupon_ttl_hours' => 168,
                'plan_slug' => 'premium', 'sub_end_days' => 300, 'status' => 'active',
                'history' => null,
                'stores' => ['Yasamal filialı', 'Xətai filialı'],
                'admin' => ['name' => 'Rəşad Nəbiyev', 'email' => 'admin@fitlife.az'],
                'cashier' => ['name' => 'Kənan Orucov', 'email' => 'kassir@fitlife.az'],
                'category' => ['name' => 'İdman və Sağlamlıq (FitLife)', 'slug' => 'idman-fitlife'],
                'own_questions' => [
                    [$this->t('FitLife-da abunəlik neçə növdür?', 'How many subscription types does FitLife have?', 'Сколько видов абонемента в FitLife?'),
                        [$this->same('1'), $this->same('2'), $this->same('3'), $this->same('4')], 2],
                    [$this->t('Zalımızda hansı qrup məşğələ yoxdur?', 'Which group class is not offered at our gym?', 'Какого группового занятия нет в нашем зале?'),
                        [$this->t('Yoga', 'Yoga', 'Йога'), $this->same('CrossFit'), $this->t('Boks', 'Boxing', 'Бокс'), $this->t('Üzgüçülük', 'Swimming', 'Плавание')], 3],
                    [$this->t('Squats məşqi əsasən hansı əzələ qrupunu işlədir?', 'Which muscle group do squats mainly work?', 'Какую группу мышц в основном работают приседания?'),
                        [$this->t('Qollar', 'Arms', 'Руки'), $this->t('Ayaq və bel-omba', 'Legs & hips', 'Ноги и бёдра'), $this->t('Boyun', 'Neck', 'Шея'), $this->t('Barmaqlar', 'Fingers', 'Пальцы')], 1],
                    [$this->t('Kardio məşqinin əsas məqsədi nədir?', 'What is the main goal of cardio exercise?', 'Какова основная цель кардиотренировки?'),
                        [$this->t('Ürəyi gücləndirmək', 'Strengthen the heart', 'Укрепить сердце'), $this->t('Yalnız çəki artırmaq', 'Only gain weight', 'Только набрать вес'), $this->t('Yalnız dartışma', 'Only stretching', 'Только растяжка'), $this->t('Yuxunu artırmaq', 'Increase sleep', 'Больше спать')], 0],
                    [$this->t('Protein qidalanmada nəyə kömək edir?', 'What does protein help with in nutrition?', 'В чём помогает белок в питании?'),
                        [$this->t('Yalnız yağ yığmağa', 'Only fat storage', 'Только набор жира'), $this->t('Əzələ bərpasına', 'Muscle recovery', 'Восстановлению мышц'), $this->t('Susuzluğu yatırmağa', 'Only quenching thirst', 'Только утоляет жажду'), $this->t('Heç nəyə', 'Nothing', 'Ничему')], 1],
                ],
                'global_pools' => ['idman-saglamlik', 'umumi'],
                'quiz_category' => $qc['idman-kampaniyalari'],
                'quizzes' => [
                    ['title' => 'Fitness Bilgin', 'total' => 4, 'pass' => 50, 'time' => 20, 'status' => 'active', 'reward_mode' => 'tiered',
                        'tiers' => [[2, 'percent', 10], [3, 'percent', 15], [4, 'percent', 25]], 'sessions' => 11],
                    ['title' => 'Yeni Üzv Kampaniyası', 'total' => 5, 'pass' => 60, 'time' => 15, 'status' => 'draft', 'reward_mode' => 'flat', 'sessions' => 0],
                ],
                'ads' => [
                    ['title' => 'Yeni İl Abunəlik Endirimi', 'status' => 'active', 'starts' => -3, 'ends' => 27],
                    ['title' => 'Şəxsi Məşqçi Paketi', 'status' => 'inactive', 'starts' => null, 'ends' => null],
                ],
            ],

            // ---------------- Bella Gözəllik Salonu ----------------
            [
                'slug' => 'bella-gozellik-salonu', 'name' => 'Bella Gözəllik Salonu',
                'bio'  => 'Saç, dırnaq və dəri baxımı üzrə peşəkar gözəllik salonu.',
                'coupon_discount_type' => 'percent', 'coupon_value' => 12, 'coupon_ttl_hours' => 48,
                'plan_slug' => 'basic', 'sub_end_days' => -10, 'status' => 'inactive',
                'history' => null,
                'stores' => ['Sahil filialı'],
                'admin' => ['name' => 'Günel Abbasova', 'email' => 'admin@bellasalon.az'],
                'cashier' => ['name' => 'Röya Kərimova', 'email' => 'kassir@bellasalon.az'],
                'category' => ['name' => 'Gözəllik (Bella)', 'slug' => 'gozellik-bella'],
                'own_questions' => [
                    [$this->t('Bella Salonunda hansı xidmət yoxdur?', 'Which service is not offered at Bella Salon?', 'Какой услуги нет в салоне Bella?'),
                        [$this->t('Saç kəsimi', 'Haircut', 'Стрижка'), $this->t('Manikür', 'Manicure', 'Маникюр'), $this->t('Dişlərin ağardılması', 'Teeth whitening', 'Отбеливание зубов'), $this->t('Qaş dizaynı', 'Brow design', 'Дизайн бровей')], 2],
                    [$this->t('Salon neçənci ildən fəaliyyətdədir?', 'Since which year has the salon been operating?', 'С какого года работает салон?'),
                        [$this->same('2017'), $this->same('2019'), $this->same('2021'), $this->same('2023')], 1],
                    [$this->t('Keratin baxımı saça nə üçün edilir?', 'What is keratin treatment for hair used for?', 'Для чего делают кератиновый уход волосам?'),
                        [$this->t('Rəngini itirmək', 'Fade color', 'Обесцветить'), $this->t('Hamarlıq vermək', 'Add smoothness', 'Разгладить'), $this->t('Qısaltmaq', 'Shorten', 'Укоротить'), $this->t('Qıvırmaq', 'Curl', 'Завить')], 1],
                    [$this->t('Salon xidmətlərində "peeling" nəyə xidmət edir?', 'What is "peeling" used for in salon services?', 'Для чего служит «пилинг» в салонных услугах?'),
                        [$this->t('Saçı boyamağa', 'Dye hair', 'Покрасить волосы'), $this->t('Dəri təmizliyi', 'Skin cleansing', 'Очистка кожи'), $this->t('Dırnaq uzatmağa', 'Extend nails', 'Нарастить ногти'), $this->t('Kirpik artırmağa', 'Add lashes', 'Нарастить ресницы')], 1],
                ],
                'global_pools' => ['gozellik', 'umumi'],
                'quiz_category' => $qc['gozellik-kampaniyalari'],
                'quizzes' => [
                    ['title' => 'Yay Kampaniyası', 'total' => 4, 'pass' => 60, 'time' => 15, 'status' => 'archived', 'reward_mode' => 'flat', 'sessions' => 9],
                    ['title' => 'Gözəllik Sirləri', 'total' => 4, 'pass' => 55, 'time' => 15, 'status' => 'draft', 'reward_mode' => 'flat', 'sessions' => 0],
                ],
                'ads' => [
                    ['title' => 'Gəlin Saçı Kampaniyası', 'status' => 'inactive', 'starts' => -40, 'ends' => -5],
                ],
            ],

            // ---------------- TechMarket Elektronika ----------------
            [
                'slug' => 'techmarket-elektronika', 'name' => 'TechMarket Elektronika',
                'bio'  => 'Son texnologiya smartfon, noutbuk və məişət texnikası satışı.',
                'coupon_discount_type' => 'amount', 'coupon_value' => 25, 'coupon_ttl_hours' => 96,
                'plan_slug' => 'standard', 'sub_end_days' => 1, 'status' => 'active',
                'history' => null,
                'stores' => ['28 May Mall filialı', 'Gənclik Mall filialı'],
                'admin' => ['name' => 'Orxan Cəfərov', 'email' => 'admin@techmarket.az'],
                'cashier' => ['name' => 'Səbinə Rzayeva', 'email' => 'kassir@techmarket.az'],
                'category' => ['name' => 'Texnologiya (TechMarket)', 'slug' => 'texnologiya-techmarket'],
                'own_questions' => [
                    [$this->t('TechMarket-də hansı brend təmsil olunmur?', 'Which brand is not sold at TechMarket?', 'Какой бренд не представлен в TechMarket?'),
                        [$this->same('Samsung'), $this->same('Apple'), $this->same('Ferrari'), $this->same('Xiaomi')], 2],
                    [$this->t('Mağazamızda ən çox satılan kateqoriya hansıdır?', 'Which category sells the most in our store?', 'Какая категория товаров продаётся у нас лучше всего?'),
                        [$this->t('Smartfonlar', 'Smartphones', 'Смартфоны'), $this->t('Soyuducular', 'Refrigerators', 'Холодильники'), $this->t('Noutbuklar', 'Laptops', 'Ноутбуки'), $this->t('Qulaqlıqlar', 'Headphones', 'Наушники')], 0],
                    [$this->t('Noutbukda RAM əsasən nə üçün istifadə olunur?', 'What is RAM mainly used for in a laptop?', 'Для чего в основном используется RAM в ноутбуке?'),
                        [$this->t('Daimi saxlama', 'Permanent storage', 'Постоянное хранение'), $this->t('Qısa müddətli yaddaş', 'Short-term memory', 'Кратковременная память'), $this->t('Ekran keyfiyyəti', 'Screen quality', 'Качество экрана'), $this->t('Batareya gücü', 'Battery power', 'Заряд батареи')], 1],
                    [$this->t('Smartfonlarda "Face ID" hansı texnologiyaya əsaslanır?', 'What technology does "Face ID" rely on in smartphones?', 'На какой технологии основан «Face ID» в смартфонах?'),
                        [$this->t('Barmaq izi', 'Fingerprint', 'Отпечаток пальца'), $this->t('Üz tanıma', 'Face recognition', 'Распознавание лица'), $this->t('Səs tanıma', 'Voice recognition', 'Распознавание голоса'), $this->t('Göz tanıma', 'Eye scan', 'Сканирование глаз')], 1],
                ],
                'global_pools' => ['texnologiya', 'umumi'],
                'quiz_category' => $qc['texnologiya-kampaniyalari'],
                'quizzes' => [
                    ['title' => 'Tech Bilici', 'total' => 5, 'pass' => 60, 'time' => 20, 'status' => 'active', 'reward_mode' => 'flat', 'sessions' => 13],
                    ['title' => 'Ağıllı Ev Bilicisi', 'total' => 4, 'pass' => 50, 'time' => 20, 'status' => 'active', 'reward_mode' => 'tiered',
                        'tiers' => [[2, 'percent', 5], [3, 'percent', 10], [4, 'percent', 18]], 'sessions' => 8],
                ],
                'ads' => [
                    ['title' => 'Qara Cümə Endirimləri', 'status' => 'active', 'starts' => -1, 'ends' => 5],
                    ['title' => 'Yay Distribyusiya Aksiyası', 'status' => 'active', 'starts' => 10, 'ends' => 40],
                ],
            ],
        ];
    }

    // ------------------------------------------------------------------
    // Bir merchant-ı tam qurur: store/user/sual/kampaniya/reklam/sessiya
    // ------------------------------------------------------------------

    private function buildMerchant(array $c, array $globalCategoryIds): void
    {
        $merchant = Merchant::updateOrCreate(
            ['slug' => $c['slug']],
            [
                'name'                 => $c['name'],
                'status'               => $c['status'],
                'bio'                  => $c['bio'],
                'coupon_discount_type' => $c['coupon_discount_type'],
                'coupon_value'         => $c['coupon_value'],
                'coupon_ttl_hours'     => $c['coupon_ttl_hours'],
            ],
        );

        if (! empty($c['history'])) {
            $h = $c['history'];
            MerchantSubscription::firstOrCreate(
                ['merchant_id' => $merchant->id, 'plan_name' => $h['plan_name']],
                [
                    'plan_id'   => Plan::where('slug', $h['plan_slug'])->value('id'),
                    'amount'    => $h['amount'],
                    'currency'  => 'AZN',
                    'starts_at' => now()->subMonths($h['months_ago_start']),
                    'ends_at'   => now()->subMonths($h['months_ago_end']),
                    'status'    => 'expired',
                    'note'      => 'İlkin abunəlik (sonra paket yüksəldilib)',
                ],
            );
        }

        $this->grantAndPinSubscription($merchant, $c['plan_slug'], $c['sub_end_days'], $c['status']);

        $storeIds = collect($c['stores'])->map(
            fn (string $name) => Store::firstOrCreate(
                ['merchant_id' => $merchant->id, 'slug' => Str::slug($name) . '-' . $merchant->id],
                ['name' => $name, 'status' => 'active'],
            )->id
        )->all();

        User::updateOrCreate(
            ['email' => $c['admin']['email']],
            ['name' => $c['admin']['name'], 'password' => Hash::make('password'), 'merchant_id' => $merchant->id, 'role' => User::ROLE_MERCHANT_ADMIN],
        );
        $cashier = User::updateOrCreate(
            ['email' => $c['cashier']['email']],
            ['name' => $c['cashier']['name'], 'password' => Hash::make('password'), 'merchant_id' => $merchant->id, 'role' => User::ROLE_CASHIER],
        );

        $ownCategory = QuestionCategory::firstOrCreate(
            ['merchant_id' => $merchant->id, 'slug' => $c['category']['slug']],
            ['name' => $c['category']['name'], 'status' => 'active'],
        );

        $ownQuestionIds = [];
        foreach ($c['own_questions'] as [$title, $opts, $correct]) {
            $q = $this->question($merchant->id, $title, $opts, $correct);
            $q->questionCategories()->syncWithoutDetaching([$ownCategory->id]);
            $ownQuestionIds[] = $q->id;
        }

        $poolIds = $ownQuestionIds;
        foreach ($c['global_pools'] as $slug) {
            if (isset($globalCategoryIds[$slug])) {
                $poolIds = array_merge($poolIds, QuestionCategory::find($globalCategoryIds[$slug])->questions()->pluck('questions.id')->all());
            } else {
                // 'umumi' / 'yemek' MerchantBasicSeeder-də yaradılıb
                $cat = QuestionCategory::where('merchant_id', null)->where('slug', $slug)->first();
                if ($cat) {
                    $poolIds = array_merge($poolIds, $cat->questions()->pluck('questions.id')->all());
                }
            }
        }
        $poolIds = array_values(array_unique($poolIds));

        foreach ($c['quizzes'] as $qCfg) {
            $quiz = Quiz::firstOrCreate(
                ['merchant_id' => $merchant->id, 'title' => $qCfg['title']],
                [
                    'store_id'              => $storeIds[0] ?? null,
                    'quiz_category_id'      => $c['quiz_category'],
                    'total_questions'       => $qCfg['total'],
                    'pass_threshold_pct'    => $qCfg['pass'],
                    'time_per_question_sec' => $qCfg['time'],
                    'status'                => $qCfg['status'],
                    'reward_mode'           => $qCfg['reward_mode'],
                ],
            );

            $quiz->questions()->syncWithoutDetaching(
                collect($poolIds)->mapWithKeys(fn ($id) => [$id => ['weight' => 1]])->all()
            );

            if ($qCfg['reward_mode'] === 'tiered' && ! empty($qCfg['tiers'])) {
                foreach ($qCfg['tiers'] as $i => [$minCorrect, $type, $value]) {
                    QuizRewardTier::updateOrCreate(
                        ['quiz_id' => $quiz->id, 'min_correct' => $minCorrect],
                        ['discount_type' => $type, 'value' => $value, 'position' => $i + 1],
                    );
                }
            }

            if ($qCfg['sessions'] > 0) {
                $this->seedSessionsForQuiz($quiz->fresh(), $merchant, $storeIds, $cashier, $qCfg['sessions']);
            }
        }

        foreach ($c['ads'] as $adCfg) {
            Ad::firstOrCreate(
                ['merchant_id' => $merchant->id, 'title' => $adCfg['title']],
                [
                    'content'   => '<p>' . $adCfg['title'] . ' — ' . $merchant->name . '.</p>',
                    'status'    => $adCfg['status'],
                    'starts_at' => $adCfg['starts'] !== null ? now()->addDays($adCfg['starts']) : null,
                    'ends_at'   => $adCfg['ends'] !== null ? now()->addDays($adCfg['ends']) : null,
                ],
            );
        }
    }

    /** Mövcud "Demo Restoran" merchant-ına (MerchantBasicSeeder) sessiya/kupon tarixçəsi əlavə edir. */
    private function extendDemoRestoran(): void
    {
        $merchant = Merchant::where('slug', 'demo-restoran')->first();
        if (! $merchant) {
            return;
        }

        $quiz = Quiz::where('merchant_id', $merchant->id)->where('title', 'Giriş Kampaniyası')->first();
        if (! $quiz) {
            return;
        }

        $storeIds = Store::where('merchant_id', $merchant->id)->pluck('id')->all();
        $cashier  = User::where('merchant_id', $merchant->id)->where('role', User::ROLE_CASHIER)->first();

        $this->seedSessionsForQuiz($quiz, $merchant, $storeIds, $cashier, 10);
    }

    // ------------------------------------------------------------------
    // Kiçik köməkçilər
    // ------------------------------------------------------------------

    private function question(?int $merchantId, array $title, array $options, int $correctIndex): Question
    {
        $q = Question::firstOrCreate(
            ['merchant_id' => $merchantId, 'title->az' => $title['az']],
            [
                'merchant_id' => $merchantId,
                'title'       => $title,
                'type'        => 'mcq',
                'is_active'   => true,
            ],
        );

        if ($q->options()->count() === 0) {
            foreach ($options as $i => $opt) {
                QuestionOption::create([
                    'question_id' => $q->id,
                    'option_text' => $opt,
                    'is_correct'  => $i === $correctIndex,
                    'position'    => $i + 1,
                ]);
            }
        }

        return $q;
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

    private function grantAndPinSubscription(Merchant $merchant, string $planSlug, int $endDays, string $status): void
    {
        if ($merchant->subscriptions()->where('status', 'active')->exists()) {
            return;
        }

        $plan = Plan::where('slug', $planSlug)->first();
        if (! $plan) {
            return;
        }

        $sub    = $this->subscriptions->grant($merchant, $plan, 1, null, 'Demo abunəliyi');
        $endsAt = now()->addDays($endDays);

        $merchant->update(['subscription_ends_at' => $endsAt, 'status' => $status]);
        $sub->update(['ends_at' => $endsAt, 'status' => $status === 'active' ? 'active' : 'expired']);
    }

    /**
     * Kampaniya üçün son 30 günə yayılmış, real görünüşlü sessiya + kupon
     * tarixçəsi yaradır (yalnız kampaniyanın hələ heç sessiyası yoxdursa).
     */
    private function seedSessionsForQuiz(Quiz $quiz, Merchant $merchant, array $storeIds, ?User $cashier, int $count): void
    {
        if ($quiz->sessions()->exists()) {
            return;
        }

        $questionIds = $quiz->questions()->pluck('questions.id')->all();
        if (empty($questionIds)) {
            return;
        }

        $ttlHours = (int) ($merchant->coupon_ttl_hours ?? 48);

        for ($i = 0; $i < $count; $i++) {
            // İlk ~40% sessiya son 2 gün ərzində olsun ki, ttl-dən asılı olmayaraq
            // panelda sınamaq üçün mütləq "aktiv" statuslu kupon da qalsın.
            $isRecent = $i < (int) ceil($count * 0.4);
            $daysAgo  = $isRecent ? random_int(0, 1) : random_int(2, 29);

            $started  = now()->subDays($daysAgo)->subMinutes(random_int(0, 1439));
            $finished = $started->copy()->addMinutes(random_int(2, 8));

            $total   = max(1, (int) $quiz->total_questions);
            $correct = $this->weightedCorrectCount($total);
            $score   = (int) round($correct / $total * 100);
            $passed  = $score >= $quiz->pass_threshold_pct;

            $isGuest  = random_int(1, 100) <= 35;
            $customer = $isGuest ? null : $this->customers->random();

            $session = QuizSession::create([
                'merchant_id'        => $merchant->id,
                'store_id'           => empty($storeIds) ? null : ($storeIds[array_rand($storeIds)] ?? null),
                'quiz_id'            => $quiz->id,
                'customer_id'        => $customer?->id,
                'guest_token'        => $isGuest ? Str::random(48) : null,
                'question_ids'       => collect($questionIds)->shuffle()->take(min($total, count($questionIds)))->values()->all(),
                'started_at'         => $started,
                'finished_at'        => $finished,
                'score_pct'          => $score,
                'correct_count'      => $correct,
                'is_passed'          => $passed,
                'ip'                 => $this->fakeIp(),
                'device_fingerprint' => substr(hash('sha256', Str::random(24)), 0, 20),
                'channel'            => random_int(1, 5) === 1 ? 'web' : 'qr',
            ]);

            $coupon = $this->coupons->issueForSession($session);
            if (! $coupon) {
                continue;
            }

            $expiresAt = $finished->copy()->addHours($ttlHours);
            $isPastTtl = $expiresAt->isPast();
            $status    = random_int(1, 100) <= 55
                ? ($isPastTtl ? 'expired' : 'active')
                : 'redeemed';

            $coupon->forceFill([
                'created_at' => $finished,
                'updated_at' => $finished,
                'expires_at' => $expiresAt,
                'status'     => $status,
            ])->save();

            if ($status === 'redeemed' && $cashier) {
                $window    = max(30, $ttlHours * 60 - 30);
                $redeemedAt = $finished->copy()->addMinutes(random_int(30, $window));
                if ($redeemedAt->greaterThan($expiresAt)) {
                    $redeemedAt = $expiresAt->copy()->subMinutes(15);
                }

                $coupon->redemptions()->create([
                    'store_id'        => empty($storeIds) ? null : ($storeIds[array_rand($storeIds)] ?? null),
                    'cashier_user_id' => $cashier->id,
                    'redeemed_at'     => $redeemedAt,
                    'pos_reference'   => 'POS-' . strtoupper(Str::random(6)),
                ]);
            }
        }
    }

    /** Əksəriyyəti orta-yaxşı nəticə versin ki, tarixçə real görünsün. */
    private function weightedCorrectCount(int $total): int
    {
        $roll = random_int(1, 100);

        if ($roll <= 55) {
            return random_int((int) ceil($total * 0.7), $total);
        }

        if ($roll <= 85) {
            return random_int((int) ceil($total * 0.4), max((int) ceil($total * 0.4), $total - 1));
        }

        return random_int(0, (int) floor($total * 0.4));
    }

    private function fakeIp(): string
    {
        return '5.' . random_int(30, 250) . '.' . random_int(1, 254) . '.' . random_int(1, 254);
    }
}
