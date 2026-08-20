<?php

namespace Tests\Feature;

use App\Models\Merchant;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\UiText;
use App\Support\Translatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Front 3 dili özü seçir — backend hər çoxdilli sahəni {az,en,ru} obyekti kimi
 * qaytarmalıdır (bax: src/i18n/index.js → translateValue).
 */
class TranslatableContentTest extends TestCase
{
    use RefreshDatabase;

    private function makeMerchant(): Merchant
    {
        return Merchant::create([
            'name'   => 'M',
            'slug'   => 'm-' . uniqid(),
            'status' => 'active',
            'bio'    => ['az' => 'Haqqımızda', 'en' => 'About us', 'ru' => 'О нас'],
            'coupon_discount_type' => 'percent',
            'coupon_value' => 10,
        ]);
    }

    public function test_merchant_bio_is_returned_in_three_languages(): void
    {
        $merchant = $this->makeMerchant();

        $data = $this->getJson('/api/v1/merchants/' . $merchant->id)->assertOk()->json();

        $this->assertSame(
            ['az' => 'Haqqımızda', 'en' => 'About us', 'ru' => 'О нас'],
            $data['merchant']['bio'],
        );
    }

    public function test_quiz_question_and_option_are_returned_in_three_languages(): void
    {
        $merchant = $this->makeMerchant();

        $quiz = Quiz::create([
            'merchant_id'        => $merchant->id,
            'title'              => ['az' => 'Yay Quizi', 'en' => 'Summer Quiz', 'ru' => 'Летний квиз'],
            'total_questions'    => 1,
            'pass_threshold_pct' => 50,
            'status'             => 'active',
            'reward_mode'        => 'flat',
        ]);

        $question = Question::create([
            'merchant_id' => $merchant->id,
            'title'       => ['az' => 'Neçə?', 'en' => 'How many?', 'ru' => 'Сколько?'],
            'type'        => 'mcq',
            'is_active'   => true,
        ]);

        QuestionOption::create([
            'question_id' => $question->id,
            'option_text' => ['az' => 'İki', 'en' => 'Two', 'ru' => 'Два'],
            'is_correct'  => true,
            'position'    => 1,
        ]);

        $quiz->questions()->attach($question->id, ['weight' => 1]);

        $start = $this->postJson('/api/v1/quiz-sessions', [
            'merchant_id' => $merchant->id,
            'quiz_id'     => $quiz->id,
        ])->assertCreated()->json();

        $this->assertSame('Summer Quiz', $start['quiz']['title']['en']);
        $this->assertSame('Летний квиз', $start['quiz']['title']['ru']);
        $this->assertSame('How many?', $start['questions'][0]['title']['en']);
        $this->assertSame('Два', $start['questions'][0]['options'][0]['text']['ru']);
    }

    public function test_ui_texts_endpoint_returns_key_to_language_map(): void
    {
        UiText::create([
            'key'   => 'play.start',
            'group' => 'play',
            'value' => ['az' => 'Başla', 'en' => 'Start', 'ru' => 'Начать'],
        ]);

        $texts = $this->getJson('/api/v1/ui-texts')->assertOk()->json('texts');

        $this->assertSame('Start', $texts['play.start']['en']);
        $this->assertSame('Начать', $texts['play.start']['ru']);
    }

    public function test_translatable_helper_falls_back_when_language_is_missing(): void
    {
        $value = ['az' => 'Yalnız az', 'en' => '', 'ru' => null];

        $this->assertSame('Yalnız az', Translatable::text($value, 'en'));
        $this->assertSame('Yalnız az', Translatable::text($value, 'az'));
        // Tərcümə olunmayan (sadə mətn) sahələr olduğu kimi qaytarılır
        $this->assertSame('Düz mətn', Translatable::text('Düz mətn', 'ru'));
        $this->assertSame('', Translatable::text(null, 'ru'));
    }
}
