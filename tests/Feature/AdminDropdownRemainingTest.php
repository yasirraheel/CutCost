<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\BrandFaq;
use App\Models\HowItWorksStep;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDropdownRemainingTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    protected function makeBrand(string $name, string $slug): Brand
    {
        return Brand::create([
            'name' => $name,
            'slug' => $slug,
            'price' => 1.85,
            'currency' => 'GBP',
            'currency_symbol' => '£',
            'is_featured' => true,
            'sort_order' => 0,
            'is_active' => true,
        ]);
    }

    public function test_brand_faq_edit_renders_brand_dropdown_from_database(): void
    {
        $brand = $this->makeBrand('Amazon', 'amazon');
        $faq = BrandFaq::create([
            'brand_id' => $brand->id,
            'question' => 'Is it compatible?',
            'answer' => 'Yes.',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.brand-faqs.edit', $faq));

        $response->assertOk();
        $response->assertSee('<select class="input-sharesub" id="brand_id" name="brand_id"', false);
        $response->assertSee('<option value="'.$brand->id.'" selected>Amazon</option>', false);
    }

    public function test_brand_faq_update_from_dropdown_persists_brand(): void
    {
        $amazon = $this->makeBrand('Amazon', 'amazon');
        $netflix = $this->makeBrand('Netflix', 'netflix');
        $faq = BrandFaq::create([
            'brand_id' => $amazon->id,
            'question' => 'Is it compatible?',
            'answer' => 'Yes.',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin())->put(route('admin.brand-faqs.update', $faq), [
            'brand_id' => $netflix->id,
            'question' => 'Is it compatible?',
            'answer' => 'Yes.',
            'sort_order' => 0,
        ]);

        $response->assertRedirect(route('admin.brand-faqs.edit', $faq));
        $this->assertSame($netflix->id, $faq->fresh()->brand_id);
    }

    public function test_how_it_works_audience_dropdown_comes_from_controller(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.how-it-works.create'));

        $response->assertOk();
        $response->assertSee('<select class="input-sharesub" id="audience" name="audience">', false);
        $response->assertSee('<option value="share" selected>I want to share</option>', false);
        $response->assertSee('<option value="seat"', false);
        $response->assertSee('I want a seat', false);
    }

    public function test_how_it_works_audience_saved_from_dropdown(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.how-it-works.store'), [
            'title' => 'Find a seat',
            'text' => 'Join a group.',
            'audience' => 'seat',
            'sort_order' => 0,
        ]);

        $response->assertRedirect(route('admin.how-it-works.index'));
        $this->assertDatabaseHas('how_it_works_steps', ['title' => 'Find a seat', 'audience' => 'seat']);
    }

    public function test_pages_type_dropdown_comes_from_controller(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.pages.create'));

        $response->assertOk();
        $response->assertSee('<select class="input-sharesub" id="type" name="type" required>', false);
        $response->assertSee('<option value="page" selected>Page</option>', false);
        $response->assertSee('<option value="post"', false);
        $response->assertSee('>Post</option>', false);
    }

    public function test_pages_type_saved_from_dropdown(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.pages.store'), [
            'slug' => 'hello-post',
            'type' => 'post',
            'title' => 'Hello post',
            'body_html' => '<p>Hello</p>',
            'sort_order' => 0,
        ]);

        $response->assertRedirect(route('admin.pages.index'));
        $this->assertDatabaseHas('pages', ['slug' => 'hello-post', 'type' => 'post']);
    }

    public function test_pages_type_rejected_outside_the_dropdown_set(): void
    {
        $page = Page::create([
            'slug' => 'hello-page',
            'type' => 'page',
            'title' => 'Hello page',
            'body_html' => '<p>Hello</p>',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin())->put(route('admin.pages.update', $page), [
            'slug' => 'hello-page',
            'type' => 'article',
            'title' => 'Hello page',
            'body_html' => '<p>Hello</p>',
            'sort_order' => 0,
        ]);

        $response->assertSessionHasErrors('type');
        $this->assertSame('page', $page->fresh()->type);
    }
}
