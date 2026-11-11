<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RendersAdminShell;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

class AdminSiteSettingsController extends Controller
{
    use RendersAdminShell;

    /** @return array<string, array{label: string, type: string}> */
    public static function keys(): array
    {
        return [
            'hero_highlight' => ['label' => 'Hero highlight word', 'type' => 'text'],
            'hero_text' => ['label' => 'Hero middle text', 'type' => 'text'],
            'hero_underline' => ['label' => 'Hero underlined word', 'type' => 'text'],
            'hero_search_placeholder' => ['label' => 'Hero search placeholder', 'type' => 'text'],
            'share_subtitle' => ['label' => 'Share section subtitle', 'type' => 'text'],
            'share_title_html' => ['label' => 'Share section title HTML', 'type' => 'textarea'],
            'share_card_title' => ['label' => 'Share card title', 'type' => 'text'],
            'share_card_text' => ['label' => 'Share card text', 'type' => 'textarea'],
            'share_card_button_label' => ['label' => 'Share card button label', 'type' => 'text'],
            'subscribe_card_title' => ['label' => 'Subscribe card title', 'type' => 'text'],
            'subscribe_card_text' => ['label' => 'Subscribe card text', 'type' => 'textarea'],
            'subscribe_card_button_label' => ['label' => 'Subscribe card button label', 'type' => 'text'],
            'share_content_html' => ['label' => 'Share content HTML', 'type' => 'textarea'],
            'footer_note' => ['label' => 'Footer note', 'type' => 'textarea'],
            'features_title' => ['label' => 'Features title', 'type' => 'text'],
        ];
    }

    public function edit()
    {
        $keys = self::keys();
        $settings = SiteSetting::whereIn('key', array_keys($keys))->pluck('value', 'key');

        return $this->adminShell(view('admin.settings.edit', compact('keys', 'settings'))->render(), 'Site settings', '/en/admin/content/settings');
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'settings' => ['nullable', 'array'],
            'settings.*' => ['nullable', 'string'],
        ]);

        foreach ($validated['settings'] ?? [] as $key => $value) {
            if (! array_key_exists($key, self::keys())) {
                continue;
            }

            SiteSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        return redirect()->route('admin.settings.edit')->with('status', 'Site settings have been updated.');
    }
}
