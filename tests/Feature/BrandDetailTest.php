<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\BrandFaq;
use App\Models\Category;
use App\Models\Page;
use App\Models\ReassuranceItem;
use App\Models\SiteSetting;
use App\Models\User;
use Database\Seeders\BrandFaqSeeder;
use Database\Seeders\ReassuranceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandDetailTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    protected function makeBrand(string $name = 'Amazon', string $slug = 'amazon', array $overrides = []): Brand
    {
        return Brand::create(array_merge([
            'name' => $name,
            'slug' => $slug,
            'price' => 1.85,
            'currency' => 'GBP',
            'currency_symbol' => '£',
            'is_featured' => true,
            'sort_order' => 0,
            'is_active' => true,
        ], $overrides));
    }

    public function test_full_price_renders_on_the_brand_page(): void
    {
        $this->makeBrand('Amazon', 'amazon', ['full_price' => 9.99]);

        $response = $this->get('/en/brand/amazon');

        $response->assertStatus(200);
        $response->assertSee('£9.99');
        $response->assertDontSee('£7.19');
    }

    public function test_brand_page_without_full_price_keeps_the_captured_price(): void
    {
        $this->makeBrand();

        $response = $this->get('/en/brand/amazon');

        $response->assertStatus(200);
        $response->assertSee('£7.19');
    }

    public function test_reassurance_items_render_from_db(): void
    {
        $this->makeBrand();

        $this->seed(ReassuranceSeeder::class);

        $this->assertSame(4, ReassuranceItem::count());

        $response = $this->get('/en/brand/amazon');

        $response->assertStatus(200);
        $response->assertSee('Personal account');
        $response->assertSee('Verified Members');
        $response->assertSee('You join the group using an invitation link Amazon, without sharing a password.');
        $response->assertSee('Only multi-user subscriptions are available. We do not share individual accounts.');
    }

    public function test_reassurance_item_for_another_brand_names_that_brand(): void
    {
        $this->makeBrand('Netflix', 'netflix');

        ReassuranceItem::create([
            'title' => 'Test Reassurance Title',
            'text' => 'You join the group using an invitation link Amazon, without sharing a password.',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $response = $this->get('/en/brand/netflix');

        $response->assertStatus(200);
        $response->assertSee('Test Reassurance Title');
        $response->assertSee('You join the group using an invitation link Netflix, without sharing a password.');
        $response->assertDontSee('Personal account');
    }

    public function test_brand_faq_seeder_extracts_the_four_amazon_faqs_and_settings(): void
    {
        $this->makeBrand();

        $this->seed(BrandFaqSeeder::class);

        $this->assertSame(4, BrandFaq::count());
        $this->assertSame('Is it compatible with Amazon?', BrandFaq::orderBy('sort_order')->first()->question);
        $this->assertSame('Should you share your password?', BrandFaq::orderBy('sort_order')->get()->last()->question);
        $this->assertSame(7.19, (float) Brand::where('slug', 'amazon')->first()->full_price);
        $this->assertSame('Before joining a group {brand}', SiteSetting::where('key', 'brand_before_join_title')->first()->value);
        $this->assertSame('4,6/5, 5200+ reviews on Trustpilot and Google', SiteSetting::where('key', 'brand_trust_rating_text')->first()->value);
        $this->assertSame('1M+ users', SiteSetting::where('key', 'brand_trust_users_text')->first()->value);
        $this->assertSame('Immediate Access', SiteSetting::where('key', 'brand_toggle_access_title')->first()->value);
    }

    public function test_brand_faqs_render_from_db(): void
    {
        $brand = $this->makeBrand();

        SiteSetting::create(['key' => 'brand_before_join_title', 'value' => 'Custom Before Join {brand}']);
        SiteSetting::create(['key' => 'brand_before_join_intro', 'value' => 'Custom intro text.']);

        BrandFaq::create([
            'brand_id' => $brand->id,
            'question' => 'Test question here?',
            'answer' => 'Test answer here.',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $response = $this->get('/en/brand/amazon');

        $response->assertStatus(200);
        $response->assertSee('Custom Before Join Amazon');
        $response->assertSee('Custom intro text.');
        $response->assertSee('Test question here?');
        $response->assertSee('Test answer here.');
        $response->assertDontSee('Is it compatible with Amazon?');
    }

    public function test_sidebar_renders_from_db(): void
    {
        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $this->makeBrand('Amazon', 'amazon', ['category_id' => $category->id]);
        $this->makeBrand('Test Brand Two', 'test-brand-two', ['category_id' => $category->id, 'price' => 2.00]);

        $response = $this->get('/en/brand/amazon');

        $response->assertStatus(200);
        $response->assertSee('Test Category');
        $response->assertSee('Test Brand Two');
        $response->assertSee('Brands > Test Category', false);
        $response->assertDontSee('Well-being');
        $response->assertDontSee('Qobuz');
    }

    public function test_blog_block_hidden_with_no_matching_post(): void
    {
        $this->makeBrand();

        $response = $this->get('/en/brand/amazon');

        $response->assertStatus(200);
        $response->assertDontSee('Blog posts about Amazon');
    }

    public function test_blog_block_renders_a_matching_post(): void
    {
        $this->makeBrand();

        Page::create([
            'slug' => 'amazon-prime-tips',
            'type' => 'post',
            'title' => 'Amazon Prime tips and tricks',
            'excerpt' => 'Tips for Amazon Prime.',
            'body_html' => '<p>Tips for Amazon Prime.</p>',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $response = $this->get('/en/brand/amazon');

        $response->assertStatus(200);
        $response->assertSee('Blog posts about Amazon');
        $response->assertSee('Amazon Prime tips and tricks');
    }

    public function test_trust_line_and_nearby_banner_render_from_settings(): void
    {
        $this->makeBrand();

        SiteSetting::create(['key' => 'brand_trust_rating_text', 'value' => '4,9/5, 9999+ reviews on Trustpilot and Google']);
        SiteSetting::create(['key' => 'brand_trust_users_text', 'value' => '2M+ users']);
        SiteSetting::create(['key' => 'brand_nearby_title', 'value' => 'Test nearby title']);
        SiteSetting::create(['key' => 'brand_nearby_text', 'value' => 'Test nearby text']);

        $response = $this->get('/en/brand/amazon');

        $response->assertStatus(200);
        $response->assertSee('4,9/5');
        $response->assertSee('9999+ reviews on Trustpilot and Google');
        $response->assertSee('2M+ users');
        $response->assertSee('Test nearby title');
        $response->assertSee('Test nearby text');
    }

    public function test_guest_blocked_on_the_new_admin_routes(): void
    {
        $this->get(route('admin.templates.index'))->assertRedirect('/en/userlogin');
        $this->get(route('admin.reassurance.index'))->assertRedirect('/en/userlogin');
        $this->get(route('admin.brand-faqs.index'))->assertRedirect('/en/userlogin');
        $this->post(route('admin.reassurance.store'), [])->assertRedirect('/en/userlogin');
        $this->post(route('admin.brand-faqs.store'), [])->assertRedirect('/en/userlogin');
    }

    public function test_admin_reassurance_and_brand_faq_crud_happy_path(): void
    {
        $brand = $this->makeBrand();
        $this->actingAs($this->admin());

        $createItem = $this->post(route('admin.reassurance.store'), [
            'title' => 'Test Reassurance Item',
            'text' => 'Test reassurance text.',
            'sort_order' => 1,
            'is_active' => '1',
        ]);
        $createItem->assertRedirect(route('admin.reassurance.index'));
        $this->assertDatabaseHas('reassurance_items', ['title' => 'Test Reassurance Item']);

        $item = ReassuranceItem::where('title', 'Test Reassurance Item')->firstOrFail();
        $this->delete(route('admin.reassurance.destroy', $item))->assertRedirect(route('admin.reassurance.index'));
        $this->assertDatabaseMissing('reassurance_items', ['title' => 'Test Reassurance Item']);

        $createFaq = $this->post(route('admin.brand-faqs.store'), [
            'brand_id' => $brand->id,
            'question' => 'Test FAQ question?',
            'answer' => 'Test FAQ answer.',
            'sort_order' => 1,
            'is_active' => '1',
        ]);
        $createFaq->assertRedirect(route('admin.brand-faqs.index'));
        $this->assertDatabaseHas('brand_faqs', ['question' => 'Test FAQ question?']);

        $faq = BrandFaq::where('question', 'Test FAQ question?')->firstOrFail();
        $this->delete(route('admin.brand-faqs.destroy', $faq))->assertRedirect(route('admin.brand-faqs.index'));
        $this->assertDatabaseMissing('brand_faqs', ['question' => 'Test FAQ question?']);
    }
}
