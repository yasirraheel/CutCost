<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RichTextEditorTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_settings_edit_page_loads_rich_text_editor_for_html_keys(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.settings.edit'));

        $response->assertOk();
        $response->assertSee('/vendor/ckeditor5/ckeditor5.js', false);
        $response->assertSee('/vendor/ckeditor5/ckeditor5.css', false);
        $response->assertSee('id="settings-share_title_html"', false);
        $response->assertSee('id="settings-share_content_html"', false);
        $response->assertSee('rich-text-editor', false);
        // Editors are created only once the DOM has been still for a
        // short while (Vue mounts on #app and replaces the nodes inside
        // it), so they bind to the final DOM nodes and edits save.
        $response->assertSee('MutationObserver', false);
        $response->assertSee('initRichTextEditors', false);
    }

    public function test_page_edit_page_loads_rich_text_editor_for_body_html(): void
    {
        $page = Page::create([
            'slug' => 'rich-text-test-page',
            'type' => 'page',
            'title' => 'Rich Text Test Page',
            'excerpt' => 'Short excerpt.',
            'body_html' => '<p>Test body.</p>',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.pages.edit', $page));

        $response->assertOk();
        $response->assertSee('/vendor/ckeditor5/ckeditor5.js', false);
        $response->assertSee('/vendor/ckeditor5/ckeditor5.css', false);
        $response->assertSee('id="body_html"', false);
        $response->assertSee('rich-text-editor', false);
        $response->assertSee('MutationObserver', false);
        $response->assertSee('<textarea style="height: auto;" class="input-sharesub" id="excerpt"', false);
    }
}
