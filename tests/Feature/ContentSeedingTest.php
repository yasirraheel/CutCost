<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Faq;
use App\Models\Feature;
use App\Models\FooterLink;
use App\Models\FooterLinkGroup;
use App\Models\HowItWorksStep;
use App\Models\PressItem;
use App\Models\SiteSetting;
use App\Models\Testimonial;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentSeedingTest extends TestCase
{
    use RefreshDatabase;

    private array $expectedPrices = [
        'Amazon' => '1.85',
        'Crunchyroll' => '3.42',
        'Deezer' => '4.59',
        'Disney' => '1.89',
        'Netflix' => '11.05',
        'Nord VPN' => '2.03',
        'Microsoft' => '2.88',
        'Pure VPN' => '2.03',
        'Spotify' => '4.95',
        'Youtube' => '4.05',
        'Paramount' => '4.50',
        'DAZN' => '22.58',
        'HBO' => '5.04',
    ];

    private array $expectedSettings = [
        'hero_highlight',
        'hero_text',
        'hero_underline',
        'hero_search_placeholder',
        'share_subtitle',
        'share_title_html',
        'share_card_title',
        'share_card_text',
        'share_card_button_label',
        'subscribe_card_title',
        'subscribe_card_text',
        'subscribe_card_button_label',
        'share_content_html',
        'footer_note',
        'features_title',
        'cta1_title',
        'cta1_text',
        'cta1_button_label',
        'cta2_title',
        'cta2_text',
        'cta2_button_label',
        'hero_rating_line',
        'hero_create_account_label',
        'testimonials_heading',
        'testimonials_subheading',
        'faq_section_title',
        'press_section_title',
        'features_read_more_label',
        'how_title',
        'how_section_title',
        'how_share_label',
        'how_seat_label',
        'catalogue_see_all_label',
        'catalogue_services_count',
        'house_title_prefix',
        'footer_tagline',
        'footer_copyright',
        'footer_social_tiktok',
        'footer_social_youtube',
        'footer_social_linkedin',
        'footer_social_instagram',
        'footer_social_facebook',
        'footer_social_x',
        'nav_share_label',
        'nav_subscriptions_label',
        'nav_blog_label',
        'nav_register_label',
        'nav_login_label',
        'nav_menu_label',
        'nav_propose_label',
        'nav_browse_label',
        'nav_help_label',
        'locales_json',
        'default_locale',
        'fallback_locale',
        'seo_home_title',
        'seo_home_description',
        'seo_catalogue_title',
        'seo_catalogue_description',
        'seo_house_description',
        'jsonld_app_name',
        'jsonld_rating_value',
        'jsonld_rating_count',
        'calculator_avg_yearly_savings',
        'calculator_default_brand_slug',
        'dashboard_cta_title',
        'dashboard_checklist_1_title',
        'dashboard_checklist_1_text',
        'dashboard_checklist_2_title',
        'dashboard_checklist_2_text',
        'dashboard_checklist_3_title',
        'dashboard_checklist_3_text',
        'shared_empty_text',
        'joined_empty_text',
        'waiting_help_1_title',
        'waiting_help_1_text',
        'waiting_help_2_title',
        'waiting_help_3_title',
        'wallet_policy_1',
        'wallet_policy_2',
        'chat_empty_title',
        'chat_empty_text',
    ];

    public function test_seeding_twice_creates_no_duplicates(): void
    {
        $this->seed(ContentSeeder::class);
        $this->seed(ContentSeeder::class);

        $this->assertSame(13, Brand::query()->count());
        $this->assertSame(10, Category::query()->count());
        $this->assertSame(8, Faq::query()->count());
        $this->assertSame(4, Testimonial::query()->count());
        $this->assertSame(8, HowItWorksStep::query()->count());
        $this->assertSame(4, Feature::query()->count());
        $this->assertSame(12, PressItem::query()->count());
        $this->assertSame(3, FooterLinkGroup::query()->count());
        $this->assertSame(11, FooterLink::query()->count());
        $this->assertSame(82, SiteSetting::query()->count());

        $this->assertSame(13, Brand::query()->distinct()->count('slug'));
        $this->assertSame(10, Category::query()->distinct()->count('slug'));
        $this->assertSame(82, SiteSetting::query()->distinct()->count('key'));
    }

    public function test_brands_have_the_exact_homepage_prices(): void
    {
        $this->seed(ContentSeeder::class);

        foreach ($this->expectedPrices as $name => $price) {
            $brand = Brand::query()->where('name', $name)->first();
            $this->assertNotNull($brand, "Brand {$name} was not seeded.");
            $this->assertSame($price, number_format((float) $brand->price, 2));
            $this->assertSame('GBP', $brand->currency);
            $this->assertSame('£', $brand->currency_symbol);
        }
    }

    public function test_brand_participation_and_backgrounds_are_seeded(): void
    {
        $this->seed(ContentSeeder::class);

        $this->assertSame(77, Brand::query()->where('name', 'Amazon')->first()->discount_percent);
        $this->assertSame(71, Brand::query()->where('name', 'Netflix')->first()->discount_percent);
        $this->assertSame('#FF8900', Brand::query()->where('name', 'Amazon')->first()->background);
        $this->assertSame('#d90b15', Brand::query()->where('name', 'Netflix')->first()->background);
        $this->assertNotEmpty(Brand::query()->where('name', 'Deezer')->first()->icon_svg);
        $this->assertStringContainsString('linear-gradient', Brand::query()->where('name', 'Deezer')->first()->background);
    }

    public function test_brands_are_linked_to_categories_from_the_catalogue(): void
    {
        $this->seed(ContentSeeder::class);

        $this->assertSame('music', Brand::query()->where('name', 'Spotify')->first()->category->slug);
        $this->assertSame('video', Brand::query()->where('name', 'Disney')->first()->category->slug);
        $this->assertSame('security', Brand::query()->where('name', 'Nord VPN')->first()->category->slug);
        $this->assertSame('house', Brand::query()->where('name', 'Amazon')->first()->category->slug);
        $this->assertSame('office', Brand::query()->where('name', 'Microsoft')->first()->category->slug);
        $this->assertNull(Brand::query()->where('name', 'Netflix')->first()->category_id);
        $this->assertNull(Brand::query()->where('name', 'Paramount')->first()->category_id);
        $this->assertNull(Brand::query()->where('name', 'DAZN')->first()->category_id);
    }

    public function test_settings_keys_exist_with_captured_values(): void
    {
        $this->seed(ContentSeeder::class);

        foreach ($this->expectedSettings as $key) {
            $this->assertNotNull(SiteSetting::query()->where('key', $key)->first(), "Setting {$key} was not seeded.");
        }

        $this->assertSame('Share', SiteSetting::query()->where('key', 'hero_highlight')->first()->value);
        $this->assertSame('the cost of your', SiteSetting::query()->where('key', 'hero_text')->first()->value);
        $this->assertSame('subscriptions', SiteSetting::query()->where('key', 'hero_underline')->first()->value);
        $this->assertSame('Search for a subscription…', SiteSetting::query()->where('key', 'hero_search_placeholder')->first()->value);
        $this->assertSame('Get started now', SiteSetting::query()->where('key', 'share_subtitle')->first()->value);
        $this->assertSame('Share', SiteSetting::query()->where('key', 'share_card_title')->first()->value);
        $this->assertSame('Browse subscriptions', SiteSetting::query()->where('key', 'subscribe_card_button_label')->first()->value);
        $this->assertSame('Enjoy exclusive advantages', SiteSetting::query()->where('key', 'features_title')->first()->value);
        $this->assertStringStartsWith('Sharesub helps you manage payments', SiteSetting::query()->where('key', 'footer_note')->first()->value);
    }

    public function test_faqs_testimonials_steps_features_and_press_are_seeded(): void
    {
        $this->seed(ContentSeeder::class);

        $this->assertTrue(Faq::query()->where('question', 'How does subscription sharing work?')->exists());
        $this->assertTrue(Testimonial::query()->where('author_name', 'Jonathan G.')->exists());
        $this->assertSame(5, Testimonial::query()->where('author_name', 'Jonathan G.')->first()->rating);
        $this->assertTrue(HowItWorksStep::query()->where('title', 'I suggest')->exists());
        $this->assertTrue(Feature::query()->where('title', 'Legal Service')->exists());
        $this->assertSame('/img/dashboard/features/service-legal.svg', Feature::query()->where('title', 'Legal Service')->first()->icon_path);
        $this->assertTrue(PressItem::query()->where('outlet_name', 'Les Echos')->exists());
        $this->assertSame('/img/dashboard/press/les-echos.svg', PressItem::query()->where('outlet_name', 'Les Echos')->first()->logo_path);
        $this->assertSame(3, FooterLinkGroup::query()->where('title', 'Online Help pages')->first()->footerLinks()->count());
        $this->assertSame(8, FooterLinkGroup::query()->where('title', 'More about us')->first()->footerLinks()->count());
        $this->assertSame(0, FooterLinkGroup::query()->where('title', 'Hot topics')->first()->footerLinks()->count());
    }
}
