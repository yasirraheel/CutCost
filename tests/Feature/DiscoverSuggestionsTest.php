<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscoverSuggestionsTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create(['role' => 'user']);
    }

    private function makeBrand(array $overrides = []): Brand
    {
        return Brand::create(array_merge([
            'name' => 'ZzTest',
            'slug' => 'zztest',
            'price' => 3.21,
            'currency' => 'GBP',
            'currency_symbol' => '£',
            'background' => '#FF8900',
            'sort_order' => 0,
            'is_active' => true,
        ], $overrides));
    }

    private function makeTemplate(Brand $brand, array $overrides = []): Template
    {
        return Template::create(array_merge([
            'brand_id' => $brand->id,
            'name' => 'ZzTest Plan',
            'slug' => 'zztest-plan',
            'seats' => 6,
            'shareable_seats' => 5,
            'available_seats' => 5,
            'cost_sharing' => 1,
            'credentials_type' => 0,
            'frequency' => 2,
            'price_with_fees' => 4.95,
            'retail_price' => 21.99,
            'currency' => 'GBP',
            'sort_order' => 0,
            'is_active' => true,
        ], $overrides));
    }

    private function discoverCards(string $html): array
    {
        preg_match_all(
            '/<div class="subscriptions-suggestion-item-name">([^<]*)<\/div>.*?<span class="subscriptions-suggestion-item-price">([^<]*)<\/span>/s',
            $html,
            $matches,
            PREG_SET_ORDER,
        );

        $cards = [];
        foreach ($matches as $match) {
            $cards[trim($match[1])] = trim($match[2]);
        }

        return $cards;
    }

    public function test_discover_card_shows_lowest_active_template_price(): void
    {
        $brand = $this->makeBrand();
        $this->makeTemplate($brand, ['slug' => 'zztest-expensive', 'price_with_fees' => 9.99]);
        $this->makeTemplate($brand, ['slug' => 'zztest-cheapest', 'price_with_fees' => 2.50]);

        $response = $this->actingAs($this->user())->get('/en/my-shared-subscriptions');

        $response->assertStatus(200);
        $cards = $this->discoverCards($response->getContent());
        $this->assertSame(['ZzTest' => '£2.5'], $cards);
        $this->assertMatchesRegularExpression(
            '/<span class="subscriptions-suggestion-item-price">£2\.5<\/span>\s*<span class="subscriptions-suggestion-item-frequency">\/ month<\/span>/',
            $response->getContent(),
        );
        $response->assertDontSee('£9.99');
    }

    public function test_discover_card_price_uses_template_currency_symbol(): void
    {
        $brand = $this->makeBrand(['currency' => 'USD', 'currency_symbol' => '$']);
        $this->makeTemplate($brand, ['currency' => 'USD', 'price_with_fees' => 7.20]);

        $response = $this->actingAs($this->user())->get('/en/my-shared-subscriptions');

        $response->assertStatus(200);
        $this->assertSame(['ZzTest' => '$7.2'], $this->discoverCards($response->getContent()));
    }

    public function test_discover_skips_brand_without_active_template(): void
    {
        $priced = $this->makeBrand(['name' => 'ZzPriced', 'slug' => 'zzpriced', 'sort_order' => 0]);
        $this->makeTemplate($priced, ['slug' => 'zzpriced-plan', 'price_with_fees' => 1.85]);

        $unpriced = $this->makeBrand(['name' => 'ZzUnpriced', 'slug' => 'zzunpriced', 'sort_order' => 1]);
        $this->makeTemplate($unpriced, ['slug' => 'zzunpriced-plan', 'price_with_fees' => 5.00, 'is_active' => false]);

        $response = $this->actingAs($this->user())->get('/en/my-shared-subscriptions');

        $response->assertStatus(200);
        $this->assertSame(['ZzPriced' => '£1.85'], $this->discoverCards($response->getContent()));
        $response->assertDontSee('ZzUnpriced');
        $response->assertDontSee('£5');
    }

    public function test_discover_skips_inactive_brand(): void
    {
        $this->makeBrand(['name' => 'ZzHidden', 'slug' => 'zzhidden', 'is_active' => false]);
        $brand = $this->makeBrand(['name' => 'ZzShown', 'slug' => 'zzshown', 'sort_order' => 1]);
        $this->makeTemplate($brand, ['slug' => 'zzshown-plan', 'price_with_fees' => 3.42]);

        $response = $this->actingAs($this->user())->get('/en/my-shared-subscriptions');

        $response->assertStatus(200);
        $this->assertSame(['ZzShown' => '£3.42'], $this->discoverCards($response->getContent()));
        $response->assertDontSee('ZzHidden');
    }

    public function test_discover_keeps_captured_cards_when_nothing_seeded(): void
    {
        $response = $this->actingAs($this->user())->get('/en/my-shared-subscriptions');

        $response->assertStatus(200);
        $this->assertSame([
            'Nintendo' => '£1.35',
            'Hypnoledge' => '£9.06',
            'Streamz' => '£4.49',
        ], array_slice($this->discoverCards($response->getContent()), 0, 3));
    }

    public function test_discover_card_links_to_brand_page(): void
    {
        $brand = $this->makeBrand();
        $this->makeTemplate($brand, ['slug' => 'zztest-plan', 'price_with_fees' => 4.95]);

        $response = $this->actingAs($this->user())->get('/en/my-shared-subscriptions');

        $response->assertStatus(200);
        $response->assertSee('<a href="/en/brand/zztest" class="subscriptions-suggestion-item-button"', false);
        $response->assertDontSee('<a href="#offline" class="subscriptions-suggestion-item-button"', false);
    }
}
