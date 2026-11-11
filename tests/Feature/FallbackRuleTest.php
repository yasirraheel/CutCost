<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\BrandFaq;
use App\Models\Category;
use App\Models\Country;
use App\Models\Faq;
use App\Models\Feature;
use App\Models\HowItWorksStep;
use App\Models\Offer;
use App\Models\Page;
use App\Models\PressItem;
use App\Models\Template;
use App\Models\Testimonial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The fallback rule: a section falls back to the captured HTML only
 * when its table has no rows at all. When rows exist but none are
 * active, the section renders empty instead, so deactivated content
 * never comes back as captured HTML.
 */
class FallbackRuleTest extends TestCase
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

    public function test_homepage_slider_empty_when_all_featured_brands_inactive(): void
    {
        $this->makeBrand(['name' => 'ZzOne', 'slug' => 'zzone', 'is_active' => false]);
        $this->makeBrand(['name' => 'ZzTwo', 'slug' => 'zztwo', 'sort_order' => 1, 'is_active' => false]);

        $response = $this->get('/en');

        $response->assertStatus(200);
        $this->assertSame(0, substr_count((string) $response->getContent(), 'hero-slides-item splide__slide'));
        $response->assertDontSee('ZzOne');
        $response->assertDontSee('ZzTwo');
    }

    public function test_homepage_slider_shows_only_the_reactivated_brand(): void
    {
        $one = $this->makeBrand(['name' => 'ZzOne', 'slug' => 'zzone', 'is_active' => false]);
        $this->makeBrand(['name' => 'ZzTwo', 'slug' => 'zztwo', 'sort_order' => 1, 'is_active' => false]);

        $one->update(['is_active' => true]);

        $response = $this->get('/en');

        $response->assertStatus(200);
        $this->assertSame(1, substr_count((string) $response->getContent(), 'hero-slides-item splide__slide'));
        $response->assertSee('ZzOne');
        $response->assertDontSee('ZzTwo');
    }

    public function test_homepage_faqs_empty_when_all_inactive(): void
    {
        Faq::create(['question' => 'ZzFaqOne', 'answer' => 'ZzAnswerOne', 'sort_order' => 0, 'is_active' => false]);
        Faq::create(['question' => 'ZzFaqTwo', 'answer' => 'ZzAnswerTwo', 'sort_order' => 1, 'is_active' => false]);

        $response = $this->get('/en');

        $response->assertStatus(200);
        $this->assertSame(0, substr_count((string) $response->getContent(), '<section class="faq-item active"'));
        $response->assertDontSee('ZzFaqOne');
        $response->assertDontSee('ZzFaqTwo');
        $response->assertDontSee('FAQPage');
    }

    public function test_homepage_testimonials_empty_when_all_inactive(): void
    {
        Testimonial::create(['author_name' => 'ZzAuthorOne', 'role_text' => 'ZzRole', 'quote' => 'ZzQuoteOne', 'rating' => 5, 'sort_order' => 0, 'is_active' => false]);
        Testimonial::create(['author_name' => 'ZzAuthorTwo', 'role_text' => 'ZzRole', 'quote' => 'ZzQuoteTwo', 'rating' => 4, 'sort_order' => 1, 'is_active' => false]);

        $response = $this->get('/en');

        $response->assertStatus(200);
        $this->assertSame(0, substr_count((string) $response->getContent(), '<div class="testimonials-item splide__slide">'));
        $response->assertDontSee('ZzQuoteOne');
        $response->assertDontSee('ZzQuoteTwo');
    }

    public function test_homepage_press_empty_when_all_inactive(): void
    {
        PressItem::create(['outlet_name' => 'ZzPressOne', 'logo_text' => 'ZzPressTextOne', 'sort_order' => 0, 'is_active' => false]);
        PressItem::create(['outlet_name' => 'ZzPressTwo', 'logo_text' => 'ZzPressTextTwo', 'sort_order' => 1, 'is_active' => false]);

        $response = $this->get('/en');

        $response->assertStatus(200);
        $this->assertSame(0, substr_count((string) $response->getContent(), '<div class="press-item splide__slide">'));
        $response->assertDontSee('ZzPressTextOne');
        $response->assertDontSee('ZzPressTextTwo');
    }

    public function test_homepage_features_empty_when_all_inactive(): void
    {
        Feature::create(['title' => 'ZzFeatureOne', 'text' => 'ZzFeatureTextOne', 'sort_order' => 0, 'is_active' => false]);

        $response = $this->get('/en');

        $response->assertStatus(200);
        $this->assertSame(0, substr_count((string) $response->getContent(), 'feature-section-item col-12'));
        $response->assertDontSee('ZzFeatureOne');
    }

    public function test_homepage_how_it_works_empty_when_all_steps_inactive(): void
    {
        HowItWorksStep::create(['title' => 'ZzShareStep', 'text' => 'ZzShareText', 'audience' => 'share', 'sort_order' => 0, 'is_active' => false]);
        HowItWorksStep::create(['title' => 'ZzSeatStep', 'text' => 'ZzSeatText', 'audience' => 'seat', 'sort_order' => 0, 'is_active' => false]);

        $response = $this->get('/en');

        $response->assertStatus(200);
        $this->assertSame(0, substr_count((string) $response->getContent(), 'data-mockup="'));
        $response->assertDontSee('ZzShareStep');
        $response->assertDontSee('ZzSeatStep');
    }

    public function test_calculator_renders_empty_brands_when_no_template_is_active(): void
    {
        $brand = $this->makeBrand();
        $this->makeTemplate($brand, ['is_active' => false]);

        $response = $this->get('/en');

        $response->assertStatus(200);
        $this->assertStringContainsString(":brands='[]'", (string) $response->getContent());
    }

    public function test_hero_countries_empty_when_all_inactive(): void
    {
        Country::create(['code' => 'GB', 'name' => 'ZzCountry', 'fee_flat' => 0, 'fee_percent' => 0, 'currency' => 'GBP', 'sort_order' => 0, 'is_active' => false]);

        $response = $this->get('/en');

        $response->assertStatus(200);
        $content = (string) $response->getContent();
        $this->assertStringContainsString(":countries='[]'", $content);
        $this->assertStringContainsString(":current-country='null'", $content);
        $response->assertDontSee('ZzCountry');
    }

    public function test_catalogue_hides_a_category_when_all_its_brands_are_inactive(): void
    {
        $category = $this->makeCategory();
        $this->makeBrand(['name' => 'ZzCatHidden', 'slug' => 'zzcathidden', 'category_id' => $category->id, 'is_featured' => false, 'is_active' => false]);

        $response = $this->get('/en/subscriptions');

        $response->assertStatus(200);
        $this->assertSame(9, substr_count((string) $response->getContent(), '<div class="category-card">'));
        $response->assertDontSee('ZzCatHidden');
    }

    public function test_house_page_renders_empty_grid_when_its_brands_are_inactive(): void
    {
        $category = $this->makeCategory(['name' => 'House', 'slug' => 'house']);
        $this->makeBrand(['name' => 'ZzHouseHidden', 'slug' => 'zzhousehidden', 'category_id' => $category->id, 'is_featured' => false, 'is_active' => false]);

        $response = $this->get('/en/subscriptions/house');

        $response->assertStatus(200);
        $this->assertSame(0, substr_count((string) $response->getContent(), '<div class="brand-card-title"'));
        $response->assertDontSee('ZzHouseHidden');
    }

    public function test_house_page_renders_empty_grid_when_the_house_category_is_inactive(): void
    {
        $this->makeCategory(['name' => 'House', 'slug' => 'house', 'is_active' => false]);

        $response = $this->get('/en/subscriptions/house');

        $response->assertStatus(200);
        $this->assertSame(0, substr_count((string) $response->getContent(), '<div class="brand-card-title"'));
    }

    public function test_brand_detail_offers_list_empty_when_all_offers_are_inactive(): void
    {
        $brand = $this->makeBrand(['name' => 'Amazon', 'slug' => 'amazon', 'price' => 1.85]);
        Offer::create([
            'brand_id' => $brand->id,
            'title' => 'Amazon Prime Video',
            'name' => 'Amazon Prime Video',
            'seller_name' => 'ZzSeller',
            'price' => 1.85,
            'currency' => 'GBP',
            'status' => 'active',
            'sort_order' => 0,
            'is_active' => false,
        ]);

        $response = $this->get('/en/brand/amazon');

        $response->assertStatus(200);
        $response->assertDontSee('ZzSeller');
        $response->assertDontSee('socheatalim');
        $response->assertDontSee('data-randomize');
    }

    public function test_brand_detail_faq_section_hidden_when_all_brand_faqs_are_inactive(): void
    {
        $brand = $this->makeBrand(['name' => 'Amazon', 'slug' => 'amazon', 'price' => 1.85]);
        BrandFaq::create([
            'brand_id' => $brand->id,
            'question' => 'ZzBrandFaqQuestion',
            'answer' => 'ZzBrandFaqAnswer',
            'sort_order' => 0,
            'is_active' => false,
        ]);

        $response = $this->get('/en/brand/amazon');

        $response->assertStatus(200);
        $this->assertSame(0, substr_count((string) $response->getContent(), '<section class="brand-before-join">'));
        $response->assertDontSee('ZzBrandFaqQuestion');
    }

    public function test_blog_index_empty_when_all_posts_are_inactive(): void
    {
        Page::create([
            'slug' => 'zz-inactive-post',
            'type' => 'post',
            'title' => 'ZzInactivePostTitle',
            'excerpt' => 'ZzInactivePostExcerpt',
            'body_html' => '<p>ZzInactivePostBody.</p>',
            'sort_order' => 0,
            'is_active' => false,
        ]);

        $response = $this->get('/cms/en');

        $response->assertStatus(200);
        $this->assertSame(0, substr_count((string) $response->getContent(), '<article class="default grid-50'));
        $response->assertDontSee('ZzInactivePostTitle');
    }

    public function test_cms_page_404s_when_deactivated(): void
    {
        Page::create([
            'slug' => 'team',
            'type' => 'page',
            'title' => 'ZzTeamTitle',
            'excerpt' => 'ZzTeamExcerpt',
            'body_html' => '<p>ZzTeamBody.</p>',
            'sort_order' => 0,
            'is_active' => false,
        ]);

        $this->get('/cms/en/team')->assertNotFound();
    }

    public function test_cms_page_renders_when_active(): void
    {
        Page::create([
            'slug' => 'team',
            'type' => 'page',
            'title' => 'ZzTeamTitle',
            'excerpt' => 'ZzTeamExcerpt',
            'body_html' => '<p>ZzTeamBody.</p>',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $this->get('/cms/en/team')->assertOk()->assertSee('ZzTeamTitle');
    }
}
