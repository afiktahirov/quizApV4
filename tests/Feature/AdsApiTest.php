<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\Merchant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin paneldə yaradılan reklamların tətbiqdə görünməsi.
 * Yalnız status=active VƏ tarix aralığına düşən reklamlar qaytarılır.
 */
class AdsApiTest extends TestCase
{
    use RefreshDatabase;

    private function merchant(string $status = 'active'): Merchant
    {
        return Merchant::create([
            'name' => 'M', 'slug' => 'm-' . uniqid(), 'status' => $status,
            'coupon_discount_type' => 'percent', 'coupon_value' => 10,
        ]);
    }

    private function ad(array $attributes = []): Ad
    {
        return Ad::create(array_merge([
            'title'   => ['az' => 'Reklam', 'en' => 'Ad', 'ru' => 'Реклама'],
            'content' => ['az' => '<p>Mətn</p>', 'en' => '<p>Text</p>', 'ru' => '<p>Текст</p>'],
            'status'  => 'active',
        ], $attributes));
    }

    public function test_active_ad_is_returned_with_three_languages(): void
    {
        $this->ad(['merchant_id' => $this->merchant()->id]);

        $ads = $this->getJson('/api/v1/ads')->assertOk()->json('ads');

        $this->assertCount(1, $ads);
        $this->assertSame('Ad', $ads[0]['title']['en']);
        $this->assertSame('<p>Текст</p>', $ads[0]['content']['ru']);
    }

    public function test_inactive_and_out_of_range_ads_are_hidden(): void
    {
        $merchant = $this->merchant();

        $this->ad(['merchant_id' => $merchant->id, 'status' => 'inactive']);
        $this->ad(['merchant_id' => $merchant->id, 'starts_at' => now()->addDays(3)]);
        $this->ad(['merchant_id' => $merchant->id, 'ends_at' => now()->subDay()]);

        $this->getJson('/api/v1/ads')->assertOk()->assertJsonCount(0, 'ads');
    }

    public function test_ads_can_be_filtered_by_merchant(): void
    {
        $mine  = $this->merchant();
        $other = $this->merchant();

        $this->ad(['merchant_id' => $mine->id]);
        $this->ad(['merchant_id' => $other->id]);

        $this->getJson('/api/v1/ads?merchant_id=' . $mine->id)
            ->assertOk()
            ->assertJsonCount(1, 'ads')
            ->assertJsonPath('ads.0.merchant_id', $mine->id);
    }

    public function test_ads_of_unsubscribed_merchant_are_hidden(): void
    {
        $this->ad(['merchant_id' => $this->merchant('inactive')->id]);

        $this->getJson('/api/v1/ads')->assertOk()->assertJsonCount(0, 'ads');
    }

    public function test_global_ad_without_merchant_is_returned(): void
    {
        $this->ad(['merchant_id' => null]);

        $this->getJson('/api/v1/ads')
            ->assertOk()
            ->assertJsonCount(1, 'ads')
            ->assertJsonPath('ads.0.merchant', null);
    }

    public function test_merchant_banner_is_exposed_in_api(): void
    {
        $merchant = $this->merchant();
        $merchant->update(['photo' => 'merchants/logo.png', 'banner' => 'merchants/banners/hero.jpg']);

        $data = $this->getJson('/api/v1/merchants/' . $merchant->id)->assertOk()->json('merchant');

        $this->assertStringEndsWith('/storage/merchants/logo.png', $data['photo']);
        $this->assertStringEndsWith('/storage/merchants/banners/hero.jpg', $data['banner']);
    }
}
