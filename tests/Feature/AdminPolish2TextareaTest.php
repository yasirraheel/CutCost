<?php

namespace Tests\Feature;

use App\Models\Faq;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPolish2TextareaTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /** @return int|null */
    protected function rowsFor(string $content, string $id): ?int
    {
        if (preg_match('/id="'.preg_quote($id, '/').'"[^>]*rows="(\d+)"/', $content, $matches) !== 1) {
            return null;
        }

        return (int) $matches[1];
    }

    public function test_settings_long_value_textarea_gets_enough_rows(): void
    {
        // Ten short lines: 10 wrapped lines + 1 = 11 rows.
        SiteSetting::updateOrCreate(['key' => 'seo_house_description'], ['value' => implode("\n", array_fill(0, 10, 'Short line here.'))]);
        // One 500-char line: ceil(500 / 80) = 7 wrapped lines + 1 = 8 rows.
        SiteSetting::updateOrCreate(['key' => 'seo_home_description'], ['value' => str_repeat('x', 500)]);
        // One very long line is capped at 24 rows instead of growing forever.
        SiteSetting::updateOrCreate(['key' => 'footer_note'], ['value' => str_repeat('word ', 500)]);

        $response = $this->actingAs($this->admin())->get(route('admin.settings.edit'));

        $response->assertOk();
        $content = $response->getContent();
        $this->assertSame(11, $this->rowsFor($content, 'settings-seo_house_description'));
        $this->assertSame(8, $this->rowsFor($content, 'settings-seo_home_description'));
        $this->assertSame(24, $this->rowsFor($content, 'settings-footer_note'));
    }

    public function test_settings_empty_value_textarea_has_at_least_six_rows(): void
    {
        // waiting_help_3_text is not seeded anywhere, so it renders empty.
        $this->assertDatabaseMissing('site_settings', ['key' => 'waiting_help_3_text']);

        $response = $this->actingAs($this->admin())->get(route('admin.settings.edit'));

        $response->assertOk();
        $content = $response->getContent();
        $rows = $this->rowsFor($content, 'settings-waiting_help_3_text');
        $this->assertNotNull($rows);
        $this->assertGreaterThanOrEqual(6, $rows);
        // No admin textarea on the page is left at the old fixed small size.
        $this->assertStringNotContainsString('rows="3"', $content);
        $this->assertStringNotContainsString('rows="2"', $content);
        // Rows alone cannot grow the box: the captured .input-sharesub
        // rule fixes height at 56px, so every textarea lets its rows
        // decide the height with an inline height of auto.
        $this->assertStringContainsString('<textarea style="height: auto;" class="input-sharesub" id="settings-waiting_help_3_text"', $content);
        $this->assertStringContainsString('<textarea style="height: auto;" class="input-sharesub" id="settings-seo_house_description"', $content);
    }

    public function test_settings_ckeditor_fields_keep_rich_text_editor_class_and_partial(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.settings.edit'));

        $response->assertOk();
        $response->assertSee('rich-text-editor', false);
        $response->assertSee('/vendor/ckeditor5/ckeditor5.js', false);
        $response->assertSee('/vendor/ckeditor5/ckeditor5.css', false);
        $response->assertSee('id="settings-share_title_html"', false);
        $response->assertSee('id="settings-share_content_html"', false);
        $content = $response->getContent();
        $rows = $this->rowsFor($content, 'settings-share_title_html');
        $this->assertNotNull($rows);
        $this->assertGreaterThanOrEqual(6, $rows);
        $this->assertStringContainsString('class="input-sharesub rich-text-editor"', $content);
    }

    public function test_saving_a_long_settings_value_still_works(): void
    {
        $longValue = implode("\n", array_fill(0, 10, 'Short line here.'));

        $this->actingAs($this->admin());

        $response = $this->put(route('admin.settings.update'), [
            'settings' => ['seo_house_description' => $longValue],
        ]);

        $response->assertRedirect(route('admin.settings.edit'));
        $this->assertDatabaseHas('site_settings', ['key' => 'seo_house_description', 'value' => $longValue]);
    }

    public function test_faq_answer_textarea_grows_with_its_value_and_is_six_rows_when_empty(): void
    {
        $faq = Faq::create([
            'question' => 'Long answer question?',
            'answer' => implode("\n", array_fill(0, 8, 'Answer line.')),
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin());

        $edit = $this->get(route('admin.faqs.edit', $faq));
        $edit->assertOk();
        // 8 lines + 1 = 9 rows.
        $this->assertSame(9, $this->rowsFor($edit->getContent(), 'answer'));

        $create = $this->get(route('admin.faqs.create'));
        $create->assertOk();
        $this->assertSame(6, $this->rowsFor($create->getContent(), 'answer'));
    }

    public function test_page_form_textareas_are_sized_with_body_html_keeping_its_twelve_row_floor(): void
    {
        $this->actingAs($this->admin());

        $create = $this->get(route('admin.pages.create'));
        $create->assertOk();
        $content = $create->getContent();
        $this->assertSame(6, $this->rowsFor($content, 'excerpt'));
        $this->assertSame(12, $this->rowsFor($content, 'body_html'));
        $this->assertStringContainsString('class="input-sharesub rich-text-editor"', $content);

        $page = Page::create([
            'slug' => 'textarea-test-page',
            'type' => 'page',
            'title' => 'Textarea Test Page',
            'excerpt' => 'Short excerpt.',
            'body_html' => implode("\n", array_fill(0, 20, '<p>Body line.</p>')),
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $edit = $this->get(route('admin.pages.edit', $page));
        $edit->assertOk();
        // 20 lines + 1 = 21 rows, under the 24 cap.
        $this->assertSame(21, $this->rowsFor($edit->getContent(), 'body_html'));
    }
}
