<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\FooterLink;
use App\Models\FooterLinkGroup;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The header and footer go through one shared rendering path: the
 * captured pages, the auth pages and the create subscription wizard
 * all use the same nav and footer markup with the same database
 * replacements. The CMS pages keep their own theme and are not part
 * of this path.
 */
class SiteChromeTest extends TestCase
{
    use RefreshDatabase;

    private function setSetting(string $key, string $value): void
    {
        SiteSetting::create(['key' => $key, 'value' => $value]);
    }

    private function makeBrand(): Brand
    {
        return Brand::create([
            'name' => 'Amazon',
            'slug' => 'amazon',
            'price' => 1.85,
            'currency' => 'GBP',
            'currency_symbol' => '£',
            'is_featured' => true,
            'sort_order' => 0,
            'is_active' => true,
        ]);
    }

    private function footerHtml(string $html): string
    {
        preg_match('/<footer\b[^>]*>.*?<\/footer>/s', $html, $match);

        return $match[0] ?? '';
    }

    private function navHtml(string $html): string
    {
        preg_match('/<nav\b[^>]*sharesub-navbar[^>]*>.*?<\/nav>/s', $html, $match);

        return $match[0] ?? '';
    }

    private function plainText(string $html): string
    {
        return trim((string) preg_replace('/\s+/', ' ', strip_tags($html)));
    }

    public function test_homepage_catalogue_and_house_share_the_same_footer_links(): void
    {
        $group = FooterLinkGroup::create(['title' => 'Online Help pages', 'sort_order' => 0]);
        FooterLink::create([
            'footer_link_group_id' => $group->id,
            'label' => 'Contact us',
            'url' => '/cms/en/team/',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $footers = [];
        foreach (['/en', '/en/subscriptions', '/en/subscriptions/house'] as $url) {
            $footers[] = $this->footerHtml($this->get($url)->assertOk()->getContent());
        }

        $this->assertNotSame('', $footers[0]);
        // The database driven middle columns are identical on all three
        // pages. The right hand column is each page's own capture: the
        // house capture carries an Account Sharing Guide link while the
        // homepage and catalogue captures have a bare Hot topics title,
        // so the full footers are not expected to be identical.
        $this->assertSame($this->middleFooterHtml($footers[0]), $this->middleFooterHtml($footers[1]));
        $this->assertSame($this->middleFooterHtml($footers[0]), $this->middleFooterHtml($footers[2]));
        $this->assertStringContainsString('Contact us', $footers[0]);
        $this->assertStringContainsString('Contact us', $footers[1]);
        $this->assertStringContainsString('Contact us', $footers[2]);
        $this->assertStringContainsString('Account Sharing Guide', $footers[2]);
        $this->assertStringContainsString('Hot topics', $footers[0]);
    }

    private function middleFooterHtml(string $footer): string
    {
        preg_match('/<div class="col-12 col-md-4 col-lg-6">\s*<div class="row">.*?<\/div>\s*<\/div>/s', $footer, $match);

        return $match[0] ?? '';
    }

    public function test_wizard_has_no_site_header_or_footer_like_the_original_create_page(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        $wizard = $this->get('/en/create-subscription')->assertOk()->getContent();

        // The original create page is just the wizard: no site header or
        // footer (verified in the captured create-subscription.html, which
        // has no header/footer markup at all).
        $this->assertStringNotContainsString('sharesub-navbar', $wizard);
        $this->assertStringNotContainsString('sharesub-footer', $wizard);
        $this->assertStringContainsString('navbar-create-sub', $wizard);
        $this->assertStringContainsString('Create your shared subscription', $wizard);
    }

    public function test_brand_page_keeps_its_captured_footer_columns(): void
    {
        $this->makeBrand();
        $group = FooterLinkGroup::create(['title' => 'ZZZ Test Group', 'sort_order' => 0]);
        FooterLink::create([
            'footer_link_group_id' => $group->id,
            'label' => 'ZZZ Test Link',
            'url' => '/cms/en/team/',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $brand = $this->get('/en/brand/amazon')->assertOk()->getContent();
        $footer = $this->footerHtml($brand);

        $this->assertStringContainsString('Can you share your Netflix account?', $footer);
        $this->assertStringNotContainsString('ZZZ Test Group', $footer);
        $this->assertStringNotContainsString('ZZZ Test Link', $footer);
    }

    public function test_login_page_uses_the_shared_nav_labels(): void
    {
        $this->setSetting('nav_subscriptions_label', 'Chrome Subscriptions Label');

        $html = $this->get('/en/userlogin')->assertOk()->getContent();

        $this->assertStringContainsString('Chrome Subscriptions Label', $html);
    }

    public function test_cms_pages_keep_their_own_header_and_footer(): void
    {
        $html = $this->get('/cms/en')->assertOk()->getContent();

        $this->assertStringNotContainsString('sharesub-navbar', $html);
        $this->assertStringNotContainsString('sharesub-footer', $html);
    }
}
