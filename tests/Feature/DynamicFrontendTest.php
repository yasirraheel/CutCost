<?php

namespace Tests\Feature;

use App\Http\Controllers\CapturedPageController;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Faq;
use App\Models\Feature;
use App\Models\FooterLink;
use App\Models\FooterLinkGroup;
use App\Models\PressItem;
use App\Models\SiteSetting;
use App\Models\Template;
use App\Models\Testimonial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class DynamicFrontendTest extends TestCase
{
    use RefreshDatabase;

    private function makeCategory(array $overrides = []): Category
    {
        return Category::create(array_merge([
            'name' => 'Music',
            'slug' => 'music',
            'sort_order' => 0,
            'is_active' => true,
        ], $overrides));
    }

    private function makeBrand(array $overrides = []): Brand
    {
        return Brand::create(array_merge([
            'name' => 'ZzTest',
            'slug' => 'zztest',
            'price' => 3.21,
            'currency' => 'GBP',
            'currency_symbol' => '£',
            'discount_percent' => 50,
            'background' => '#FF8900',
            'icon_svg' => '<svg width="24" height="24" viewBox="0 0 24 24"><path d="M1 1h22v22H1z"/></svg>',
            'is_featured' => true,
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

    private function setSetting(string $key, string $value): void
    {
        SiteSetting::create(['key' => $key, 'value' => $value]);
    }

    public function test_homepage_shows_a_featured_brand_name_and_price(): void
    {
        $this->makeCategory();
        $this->makeBrand();

        $response = $this->get('/en');

        $response->assertStatus(200);
        $response->assertSee('ZzTest');
        $response->assertSee('£3.21');
        $response->assertSee('Participate in -50 %');
    }

    public function test_homepage_does_not_show_an_inactive_brand(): void
    {
        $this->makeCategory();
        $this->makeBrand();
        $this->makeBrand([
            'name' => 'ZzHidden',
            'slug' => 'zzhidden',
            'price' => 123.45,
            'is_active' => false,
        ]);

        $response = $this->get('/en');

        $response->assertStatus(200);
        $response->assertSee('ZzTest');
        $response->assertDontSee('ZzHidden');
        $response->assertDontSee('£123.45');
    }

    public function test_calculator_brands_json_contains_frequency_for_every_template(): void
    {
        $brandA = $this->makeBrand(['name' => 'ZzFreq', 'slug' => 'zzfreq']);
        $this->makeTemplate($brandA, ['frequency' => 2, 'cost_sharing' => 1, 'credentials_type' => 4]);
        $brandB = $this->makeBrand(['name' => 'ZzFreqTwo', 'slug' => 'zzfreqtwo', 'sort_order' => 1]);
        $this->makeTemplate($brandB, ['slug' => 'zzfreqtwo-plan', 'frequency' => 2, 'cost_sharing' => 1, 'credentials_type' => 0]);

        $response = $this->get('/en');

        $response->assertStatus(200);
        $this->assertSame(1, preg_match("/:brands='([^']*)'/", (string) $response->getContent(), $matches));
        $brands = json_decode($matches[1], true);
        $this->assertIsArray($brands);
        $this->assertCount(2, $brands);

        foreach ($brands as $brand) {
            $template = $brand['lowest_price_template'];
            $this->assertArrayHasKey('frequency', $template);
            $this->assertSame(2, $template['frequency']);
            $this->assertArrayHasKey('cost_sharing', $template);
            $this->assertSame(1, $template['cost_sharing']);
            $this->assertArrayHasKey('credentials_type', $template);
        }
    }

    public function test_homepage_keeps_the_captured_carousel_when_no_featured_brands_exist(): void
    {
        $response = $this->get('/en');

        $response->assertStatus(200);
        $response->assertSee('Amazon');
        $response->assertSee('£1.85');
    }

    public function test_changing_a_setting_changes_the_hero_text_and_search_placeholder(): void
    {
        $this->setSetting('hero_highlight', 'ZzSplit');
        $this->setSetting('hero_text', 'the cost of each');
        $this->setSetting('hero_underline', 'ZzPlan');
        $this->setSetting('hero_search_placeholder', 'ZzSearch here');

        $response = $this->get('/en');

        $response->assertStatus(200);
        $response->assertSee('ZzSplit');
        $response->assertSee('the cost of each');
        $response->assertSee('ZzPlan');
        $response->assertSee('placeholder="ZzSearch here"', false);
    }

    public function test_brand_detail_renders_a_known_brand(): void
    {
        $this->makeBrand([
            'name' => 'Spotify',
            'slug' => 'spotify',
            'price' => 4.95,
            'background' => '#2ED755',
            'tagline' => 'ZzTagline',
            'short_description' => 'ZzShortDescription',
        ]);

        $response = app(CapturedPageController::class)->showBrand('spotify');

        $this->assertSame(200, $response->getStatusCode());
        $content = (string) $response->getContent();
        $this->assertStringContainsString('Spotify', $content);
        $this->assertStringContainsString('£4.95', $content);
        $this->assertStringContainsString('ZzShortDescription', $content);
        $this->assertStringContainsString('<title>Share my Spotify subscription on Sharesub', $content);
    }

    public function test_amazon_brand_page_shows_the_database_price(): void
    {
        $this->makeBrand([
            'name' => 'Amazon',
            'slug' => 'amazon',
            'price' => 1.85,
            'background' => '#FF8900',
        ]);

        $response = $this->get('/en/brand/amazon');

        $response->assertStatus(200);
        $response->assertSee('Amazon');
        $response->assertSee('£1.85');
    }

    public function test_brand_detail_404s_for_unknown_slugs(): void
    {
        $this->get('/en/brand/no-such-brand')->assertStatus(404);

        $this->expectException(NotFoundHttpException::class);
        app(CapturedPageController::class)->showBrand('no-such-brand');
    }

    public function test_brand_detail_404s_for_inactive_slugs(): void
    {
        $this->makeBrand([
            'name' => 'ZzInactive',
            'slug' => 'zzinactive',
            'is_active' => false,
        ]);

        $this->expectException(NotFoundHttpException::class);
        app(CapturedPageController::class)->showBrand('zzinactive');
    }

    public function test_catalogue_renders_category_brands(): void
    {
        $category = $this->makeCategory();
        $this->makeBrand([
            'name' => 'ZzCat',
            'slug' => 'zzcat',
            'price' => 2.22,
            'category_id' => $category->id,
            'is_featured' => false,
        ]);

        $response = $this->get('/en/subscriptions');

        $response->assertStatus(200);
        $response->assertSee('ZzCat');
        $response->assertSee('£2.22');
    }

    public function test_house_page_renders_the_house_category_brands(): void
    {
        $category = $this->makeCategory(['name' => 'House', 'slug' => 'house']);
        $this->makeBrand([
            'name' => 'ZzHouse',
            'slug' => 'zzhouse',
            'price' => 5.55,
            'category_id' => $category->id,
            'is_featured' => false,
        ]);

        $response = $this->get('/en/subscriptions/house');

        $response->assertStatus(200);
        $response->assertSee('ZzHouse');
        $response->assertSee('£5.55');
    }

    public function test_homepage_renders_faqs_testimonials_press_features_share_and_footer(): void
    {
        Faq::create([
            'question' => 'ZzFaqQuestion',
            'answer' => 'ZzFaqAnswer',
            'sort_order' => 0,
            'is_active' => true,
        ]);
        Testimonial::create([
            'author_name' => 'ZzAuthor',
            'role_text' => 'ZzRole',
            'quote' => 'ZzQuote',
            'rating' => 4,
            'sort_order' => 0,
            'is_active' => true,
        ]);
        PressItem::create([
            'outlet_name' => 'ZzPress',
            'logo_text' => 'ZzPressText',
            'sort_order' => 0,
            'is_active' => true,
        ]);
        Feature::create([
            'title' => 'ZzFeature',
            'text' => 'ZzFeatureText',
            'sort_order' => 0,
            'is_active' => true,
        ]);
        $group = FooterLinkGroup::create(['title' => 'ZzGroup', 'sort_order' => 0]);
        FooterLink::create([
            'footer_link_group_id' => $group->id,
            'label' => 'ZzLink',
            'url' => '/en/subscriptions',
            'sort_order' => 0,
            'is_active' => true,
        ]);
        $this->setSetting('features_title', 'ZzFeaturesTitle');
        $this->setSetting('footer_note', 'ZzFooterNote');
        $this->setSetting('share_subtitle', 'ZzSubtitle');
        $this->setSetting('share_title_html', '<u>ZzShare</u> now');
        $this->setSetting('share_card_title', 'ZzShareCard');
        $this->setSetting('share_card_text', 'ZzShareText');
        $this->setSetting('share_card_button_label', 'ZzShareButton');
        $this->setSetting('subscribe_card_title', 'ZzSubCard');
        $this->setSetting('subscribe_card_text', 'ZzSubText');
        $this->setSetting('subscribe_card_button_label', 'ZzSubButton');
        $this->setSetting('share_content_html', '<p>ZzShareContent</p>');

        $response = $this->get('/en');

        $response->assertStatus(200);
        $response->assertSee('ZzFaqQuestion');
        $response->assertSee('ZzFaqAnswer');
        $response->assertSee('ZzAuthor');
        $response->assertSee('ZzQuote');
        $response->assertSee('ZzRole');
        $response->assertSee('ZzPressText');
        $response->assertSee('ZzFeature');
        $response->assertSee('ZzFeatureText');
        $response->assertSee('ZzFeaturesTitle');
        $response->assertSee('ZzFooterNote');
        $response->assertSee('ZzSubtitle');
        $response->assertSee('<u>ZzShare</u> now', false);
        $response->assertSee('ZzShareCard');
        $response->assertSee('ZzShareText');
        $response->assertSee('ZzShareButton');
        $response->assertSee('ZzSubCard');
        $response->assertSee('ZzSubText');
        $response->assertSee('ZzSubButton');
        $response->assertSee('ZzShareContent');
        $response->assertSee('ZzGroup');
        $response->assertSee('ZzLink');
    }

    public function test_homepage_keeps_captured_sections_when_the_database_is_empty(): void
    {
        $response = $this->get('/en');

        $response->assertStatus(200);
        $response->assertSee('Frequently asked questions');
        $response->assertSee('Enjoy exclusive advantages');
        $response->assertSee('Get started now');
        $response->assertSee('Online Help pages');
    }
}
