<?php

namespace Tests\Feature;

use App\Models\Feature;
use App\Models\Page;
use App\Models\User;
use Database\Seeders\ContentSeeder;
use Database\Seeders\FeatureDestinationPagesSeeder;
use Database\Seeders\PageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Item 5 (legal pages manageable from admin) and item 6 (the four
 * homepage feature blocks dynamic and live with working Read More
 * destinations).
 *
 * The homepage blocks already render from the features table through
 * CapturedPageController::replaceFeatures and are managed by the admin
 * Features CRUD; what was missing was the destination data: three of
 * the four Read More links were dead (#offline) and two destination
 * pages did not exist anywhere.
 */
class LegalAndFeaturePagesTest extends TestCase
{
    use RefreshDatabase;

    private function seedAll(): void
    {
        $this->seed(ContentSeeder::class);
        $this->seed(PageSeeder::class);
        $this->seed(FeatureDestinationPagesSeeder::class);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function updatePagePayload(Page $page, string $title, string $body): array
    {
        return [
            'slug' => $page->slug,
            'type' => $page->type,
            'title' => $title,
            'excerpt' => $page->excerpt ?? '',
            'body_html' => $body,
            'sort_order' => $page->sort_order ?? 0,
            'is_active' => '1',
        ];
    }

    public function test_destination_seeder_creates_the_two_missing_pages_from_captured_text(): void
    {
        $this->seed(ContentSeeder::class);
        $this->seed(PageSeeder::class);

        $this->assertSame(6, Page::count());

        $this->seed(FeatureDestinationPagesSeeder::class);

        $this->assertSame(8, Page::count());

        $noFees = Page::where('slug', 'no-fees-when-you-share')->firstOrFail();
        $this->assertSame('No fees when you share.', $noFees->title);
        $this->assertSame('page', $noFees->type);
        $this->assertTrue($noFees->is_active);
        $this->assertStringContainsString(
            'No fees to withdraw the money you\'ve raised: transfers to your bank account are free',
            $noFees->body_html,
        );
        $this->assertStringContainsString(
            'There are no fees for refunds, nor are there any listing fees for sellers.',
            $noFees->body_html,
        );
        $this->assertStringContainsString('<h1 class="title large bordered">No fees when you share.</h1>', $noFees->body_html);

        $secure = Page::where('slug', 'secure-data')->firstOrFail();
        $this->assertSame('Secure data', $secure->title);
        $this->assertStringContainsString(
            'Your exchanges are transmitted and stored with strong encryption (AES 256) to ensure your privacy.',
            $secure->body_html,
        );
        $this->assertStringContainsString('<h1 class="title large bordered">Secure data</h1>', $secure->body_html);

        $this->get('/cms/en/no-fees-when-you-share')->assertOk()
            ->assertSee('<h1 class="title large bordered">No fees when you share.</h1>', false);
        $this->get('/cms/en/secure-data')->assertOk()
            ->assertSee('<h1 class="title large bordered">Secure data</h1>', false);
    }

    public function test_destination_seeder_fills_all_four_feature_read_more_links(): void
    {
        $this->seedAll();

        $this->assertSame('/cms/en/legal/tos/', Feature::where('title', 'Legal Service')->firstOrFail()->link_url);
        $this->assertSame('/cms/en/buyer-protection-program/', Feature::where('title', 'Buyer Protection Program')->firstOrFail()->link_url);
        $this->assertSame('/cms/en/no-fees-when-you-share/', Feature::where('title', 'No fees when you share.')->firstOrFail()->link_url);
        $this->assertSame('/cms/en/secure-data/', Feature::where('title', 'Secure data')->firstOrFail()->link_url);
    }

    public function test_destination_seeder_never_overwrites_admin_edits(): void
    {
        $this->seed(ContentSeeder::class);
        $this->seed(PageSeeder::class);
        $this->seed(FeatureDestinationPagesSeeder::class);

        Feature::where('title', 'Legal Service')->update(['link_url' => '/cms/en/legal/']);
        Page::where('slug', 'secure-data')->update(['body_html' => '<p>Admin edited secure data body.</p>']);

        $this->seed(FeatureDestinationPagesSeeder::class);

        $this->assertSame('/cms/en/legal/', Feature::where('title', 'Legal Service')->firstOrFail()->link_url);
        $this->assertSame('<p>Admin edited secure data body.</p>', Page::where('slug', 'secure-data')->firstOrFail()->body_html);
        $this->assertSame(8, Page::count());
    }

    public function test_homepage_feature_blocks_render_from_the_database(): void
    {
        $this->seedAll();

        $feature = Feature::where('title', 'Secure data')->firstOrFail();
        $feature->update([
            'title' => 'Secure data edited',
            'text' => 'Edited secure data paragraph for testing.',
        ]);

        $response = $this->get('/en');

        $response->assertOk();
        $response->assertSee('Secure data edited');
        $response->assertSee('Edited secure data paragraph for testing.');
        $response->assertSee('Legal Service');
        $response->assertSee('Buyer Protection Program');
        $response->assertSee('No fees when you share.');
    }

    public function test_every_feature_read_more_link_resolves_to_a_working_page(): void
    {
        $this->seedAll();

        foreach (Feature::orderBy('sort_order')->get() as $feature) {
            $this->assertNotEmpty($feature->link_url, "Feature {$feature->title} has no Read More destination.");
            $this->assertNotSame('#offline', $feature->link_url);

            $this->get($feature->link_url)->assertOk();
        }

        $homepage = $this->get('/en')->assertOk();
        foreach (Feature::orderBy('sort_order')->get() as $feature) {
            $homepage->assertSee('href="'.$feature->link_url.'"', false);
        }
    }

    public function test_feature_link_is_editable_from_the_admin_features_crud(): void
    {
        $this->seedAll();
        $this->actingAs($this->admin());

        $feature = Feature::where('title', 'Legal Service')->firstOrFail();

        $update = $this->put(route('admin.features.update', $feature), [
            'title' => 'Legal Service',
            'text' => $feature->text,
            'link_url' => '/cms/en/legal/',
            'sort_order' => $feature->sort_order,
            'is_active' => '1',
        ]);
        $update->assertRedirect(route('admin.features.edit', $feature));

        $this->assertSame('/cms/en/legal/', $feature->fresh()->link_url);
        $this->get('/en')->assertOk()->assertSee('href="/cms/en/legal/"', false);
    }

    /**
     * Every legal page, including the nested Terms slug legal/tos:
     * renders on the frontend and survives a full admin edit round
     * trip (title and body changed in the admin, then shown live).
     */
    public function test_each_legal_page_renders_and_is_editable_in_the_admin(): void
    {
        $this->seedAll();
        $this->actingAs($this->admin());

        $slugs = [
            'legal' => '/cms/en/legal',
            'legal/tos' => '/cms/en/legal/tos',
            'buyer-protection-program' => '/cms/en/buyer-protection-program',
            'no-fees-when-you-share' => '/cms/en/no-fees-when-you-share',
            'secure-data' => '/cms/en/secure-data',
        ];

        foreach ($slugs as $slug => $url) {
            $page = Page::where('slug', $slug)->firstOrFail();

            $this->get($url)->assertOk()->assertSee($page->title);

            $editedTitle = 'Edited title for '.$slug;
            $update = $this->put(
                route('admin.pages.update', $page),
                $this->updatePagePayload($page, $editedTitle, '<p>Edited body for '.$slug.'.</p>'),
            );
            $update->assertRedirect(route('admin.pages.edit', $page));

            $this->assertDatabaseHas('pages', ['slug' => $slug, 'title' => $editedTitle]);
            $this->get($url)->assertOk()->assertSee($editedTitle);
            $this->get($url)->assertSee('Edited body for '.$slug.'.');
        }
    }

    public function test_legal_pages_appear_in_the_admin_pages_list(): void
    {
        $this->seedAll();
        $this->actingAs($this->admin());

        $response = $this->get(route('admin.pages.index'));

        $response->assertOk();
        foreach (['legal', 'legal/tos', 'buyer-protection-program', 'no-fees-when-you-share', 'secure-data'] as $slug) {
            $response->assertSee(Page::where('slug', $slug)->firstOrFail()->title);
        }
    }
}
