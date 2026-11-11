<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use Database\Seeders\PageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PagesTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_page_seeder_extracts_all_six_cms_pages(): void
    {
        $this->seed(PageSeeder::class);

        $this->assertSame(6, Page::count());
        $this->assertSame(
            ['index', 'team', 'legal', 'legal/tos', 'buyer-protection-program', 'duolingo-family-plan-cost-current-discounts-on-duolingo-super-max'],
            Page::orderBy('sort_order')->pluck('slug')->all(),
        );

        foreach (Page::all() as $page) {
            $this->assertNotSame('', $page->title, "Page {$page->slug} has no title.");
            $this->assertNotSame('', $page->body_html, "Page {$page->slug} has no body.");
            $this->assertNotNull($page->excerpt, "Page {$page->slug} has no excerpt.");
        }

        $this->assertSame('post', Page::where('slug', 'duolingo-family-plan-cost-current-discounts-on-duolingo-super-max')->firstOrFail()->type);
        $this->assertSame('page', Page::where('slug', 'team')->firstOrFail()->type);
    }

    public function test_cms_article_page_renders_seeded_title_from_db(): void
    {
        $this->seed(PageSeeder::class);

        $page = Page::where('slug', 'duolingo-family-plan-cost-current-discounts-on-duolingo-super-max')->firstOrFail();
        $page->update(['title' => 'Seeded Duolingo Title From Database']);

        $this->get('/cms/en/duolingo-family-plan-cost-current-discounts-on-duolingo-super-max')
            ->assertOk()
            ->assertSee('Seeded Duolingo Title From Database');
    }

    public function test_blog_index_lists_seeded_post(): void
    {
        $this->seed(PageSeeder::class);

        Page::create([
            'slug' => 'seeded-blog-post',
            'type' => 'post',
            'title' => 'Seeded Blog Post Title',
            'excerpt' => 'A seeded post for the blog index.',
            'body_html' => '<p>Seeded body.</p>',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        $this->get('/cms/en')
            ->assertOk()
            ->assertSee('Seeded Blog Post Title')
            ->assertSee('Duolingo Family Plan');
    }

    public function test_unknown_cms_slug_still_404s(): void
    {
        $this->seed(PageSeeder::class);

        $this->get('/cms/en/this-page-does-not-exist')->assertNotFound();
    }

    public function test_guest_cannot_reach_admin_pages_routes(): void
    {
        $this->get(route('admin.pages.index'))->assertRedirect('/en/userlogin');
        $this->get(route('admin.pages.create'))->assertRedirect('/en/userlogin');
        $this->post(route('admin.pages.store'), [
            'slug' => 'guest-page',
            'type' => 'page',
            'title' => 'Guest Page',
            'body_html' => '<p>Guest body.</p>',
        ])->assertRedirect('/en/userlogin');
    }

    public function test_admin_page_crud_happy_path(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        $create = $this->post(route('admin.pages.store'), [
            'slug' => 'test-page',
            'type' => 'page',
            'title' => 'Test Page',
            'excerpt' => 'A short excerpt.',
            'body_html' => '<p>Test body.</p>',
            'sort_order' => 1,
            'is_active' => '1',
            'image' => UploadedFile::fake()->createWithContent('page.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8Xw8AAwMCAO+ip1sAAAAASUVORK5CYII=')),
        ]);
        $create->assertRedirect(route('admin.pages.index'));
        $this->assertDatabaseHas('pages', ['slug' => 'test-page', 'title' => 'Test Page']);

        $page = Page::where('slug', 'test-page')->firstOrFail();
        $this->assertNotNull($page->image_path);
        Storage::disk('public')->assertExists($page->image_path);

        $this->get(route('admin.pages.index'))->assertOk()->assertSee('Test Page');

        $update = $this->put(route('admin.pages.update', $page), [
            'slug' => 'test-page',
            'type' => 'page',
            'title' => 'Test Page Updated',
            'excerpt' => 'A short excerpt.',
            'body_html' => '<p>Updated body.</p>',
            'sort_order' => 1,
            'is_active' => '1',
        ]);
        $update->assertRedirect(route('admin.pages.edit', $page));
        $this->assertDatabaseHas('pages', ['slug' => 'test-page', 'title' => 'Test Page Updated']);

        $delete = $this->delete(route('admin.pages.destroy', $page));
        $delete->assertRedirect(route('admin.pages.index'));
        $this->assertDatabaseMissing('pages', ['slug' => 'test-page']);
        Storage::disk('public')->assertMissing($page->image_path);
    }
}
