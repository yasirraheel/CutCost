<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RendersAdminShell;
use App\Models\Brand;
use App\Models\Currency;
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
            'cta1_title' => ['label' => 'CTA block 1 title', 'type' => 'text'],
            'cta1_text' => ['label' => 'CTA block 1 text', 'type' => 'textarea'],
            'cta1_button_label' => ['label' => 'CTA block 1 button label', 'type' => 'text'],
            'cta2_title' => ['label' => 'CTA block 2 title', 'type' => 'text'],
            'cta2_text' => ['label' => 'CTA block 2 text', 'type' => 'textarea'],
            'cta2_button_label' => ['label' => 'CTA block 2 button label', 'type' => 'text'],
            'hero_rating_line' => ['label' => 'Hero rating line', 'type' => 'text'],
            'hero_create_account_label' => ['label' => 'Hero create account label', 'type' => 'text'],
            'testimonials_heading' => ['label' => 'Testimonials heading', 'type' => 'text'],
            'testimonials_subheading' => ['label' => 'Testimonials subheading', 'type' => 'text'],
            'faq_section_title' => ['label' => 'FAQ section title', 'type' => 'text'],
            'press_section_title' => ['label' => 'Press section title', 'type' => 'text'],
            'features_read_more_label' => ['label' => 'Features read more label', 'type' => 'text'],
            'catalogue_see_all_label' => ['label' => 'Catalogue see all label', 'type' => 'text'],
            'catalogue_services_count' => ['label' => 'Catalogue services count', 'type' => 'text'],
            'house_title_prefix' => ['label' => 'House title prefix', 'type' => 'text'],
            'footer_tagline' => ['label' => 'Footer tagline (use {heart} for the heart)', 'type' => 'text'],
            'footer_copyright' => ['label' => 'Footer copyright', 'type' => 'text'],
            'footer_social_tiktok' => ['label' => 'Footer social TikTok URL', 'type' => 'text'],
            'footer_social_youtube' => ['label' => 'Footer social YouTube URL', 'type' => 'text'],
            'footer_social_linkedin' => ['label' => 'Footer social LinkedIn URL', 'type' => 'text'],
            'footer_social_instagram' => ['label' => 'Footer social Instagram URL', 'type' => 'text'],
            'footer_social_facebook' => ['label' => 'Footer social Facebook URL', 'type' => 'text'],
            'footer_social_x' => ['label' => 'Footer social X URL', 'type' => 'text'],
            'nav_share_label' => ['label' => 'Nav share label', 'type' => 'text'],
            'nav_subscriptions_label' => ['label' => 'Nav subscriptions label', 'type' => 'text'],
            'nav_blog_label' => ['label' => 'Nav blog label', 'type' => 'text'],
            'nav_register_label' => ['label' => 'Nav register label', 'type' => 'text'],
            'nav_login_label' => ['label' => 'Nav login label', 'type' => 'text'],
            'nav_menu_label' => ['label' => 'Nav menu label', 'type' => 'text'],
            'nav_propose_label' => ['label' => 'Nav propose label', 'type' => 'text'],
            'nav_browse_label' => ['label' => 'Nav browse label', 'type' => 'text'],
            'nav_help_label' => ['label' => 'Nav help label', 'type' => 'text'],
            'locales_json' => ['label' => 'Locales JSON', 'type' => 'textarea'],
            'default_locale' => ['label' => 'Default locale', 'type' => 'locale'],
            'fallback_locale' => ['label' => 'Fallback locale', 'type' => 'locale'],
            'seo_home_title' => ['label' => 'SEO home title', 'type' => 'text'],
            'seo_home_description' => ['label' => 'SEO home description', 'type' => 'textarea'],
            'seo_catalogue_title' => ['label' => 'SEO catalogue title', 'type' => 'text'],
            'seo_catalogue_description' => ['label' => 'SEO catalogue description', 'type' => 'textarea'],
            'seo_house_description' => ['label' => 'SEO house description', 'type' => 'textarea'],
            'jsonld_app_name' => ['label' => 'JSON-LD app name', 'type' => 'text'],
            'jsonld_rating_value' => ['label' => 'JSON-LD rating value', 'type' => 'text'],
            'jsonld_rating_count' => ['label' => 'JSON-LD rating count', 'type' => 'text'],
            'calculator_avg_yearly_savings' => ['label' => 'Calculator average yearly savings', 'type' => 'text'],
            'calculator_default_brand_slug' => ['label' => 'Calculator default brand slug', 'type' => 'brand_slug'],
            'default_currency_code' => ['label' => 'Default currency code', 'type' => 'currency'],
            'how_title' => ['label' => 'How it works title', 'type' => 'text'],
            'how_section_title' => ['label' => 'How it works section title', 'type' => 'text'],
            'how_share_label' => ['label' => 'How it works share label', 'type' => 'text'],
            'how_seat_label' => ['label' => 'How it works seat label', 'type' => 'text'],
            'dashboard_cta_title' => ['label' => 'Dashboard CTA title', 'type' => 'text'],
            'dashboard_checklist_1_title' => ['label' => 'Dashboard checklist 1 title', 'type' => 'text'],
            'dashboard_checklist_1_text' => ['label' => 'Dashboard checklist 1 text', 'type' => 'text'],
            'dashboard_checklist_2_title' => ['label' => 'Dashboard checklist 2 title', 'type' => 'text'],
            'dashboard_checklist_2_text' => ['label' => 'Dashboard checklist 2 text', 'type' => 'text'],
            'dashboard_checklist_3_title' => ['label' => 'Dashboard checklist 3 title', 'type' => 'text'],
            'dashboard_checklist_3_text' => ['label' => 'Dashboard checklist 3 text', 'type' => 'text'],
            'shared_empty_text' => ['label' => 'Shared subscriptions empty text', 'type' => 'textarea'],
            'joined_empty_text' => ['label' => 'Joined subscriptions empty text', 'type' => 'textarea'],
            'waiting_help_1_title' => ['label' => 'Waiting help 1 title', 'type' => 'text'],
            'waiting_help_1_text' => ['label' => 'Waiting help 1 text', 'type' => 'textarea'],
            'waiting_help_2_title' => ['label' => 'Waiting help 2 title', 'type' => 'text'],
            'waiting_help_2_text' => ['label' => 'Waiting help 2 text', 'type' => 'textarea'],
            'waiting_help_3_title' => ['label' => 'Waiting help 3 title', 'type' => 'text'],
            'waiting_help_3_text' => ['label' => 'Waiting help 3 text', 'type' => 'textarea'],
            'wallet_policy_1' => ['label' => 'Wallet policy 1', 'type' => 'textarea'],
            'wallet_policy_2' => ['label' => 'Wallet policy 2', 'type' => 'textarea'],
            'chat_empty_title' => ['label' => 'Chat empty title', 'type' => 'text'],
            'chat_empty_text' => ['label' => 'Chat empty text', 'type' => 'textarea'],
        ];
    }

    public function edit()
    {
        $keys = self::keys();
        $settings = SiteSetting::whereIn('key', array_keys($keys))->pluck('value', 'key');

        $currencies = Currency::ordered()->get();

        $brands = Brand::where('is_active', true)->orderBy('name')->get(['slug', 'name']);

        // Locale options come from the locales_json setting itself: that is
        // the set the app already defines for the language dropdown.
        $locales = [];
        $localesJson = $settings['locales_json'] ?? null;
        if (is_string($localesJson)) {
            $decoded = json_decode($localesJson, true);
            if (is_array($decoded)) {
                foreach ($decoded as $locale) {
                    if (is_array($locale) && isset($locale['code']) && is_string($locale['code']) && $locale['code'] !== '') {
                        $locales[] = ['code' => $locale['code'], 'label' => (string) ($locale['label'] ?? $locale['code'])];
                    }
                }
            }
        }

        return $this->adminShell(view('admin.settings.edit', compact('keys', 'settings', 'currencies', 'brands', 'locales'))->render(), 'Site settings', '/en/admin/content/settings');
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

            // The default currency must name a currency row; anything else
            // would make every price fallback silently wrong.
            if ($key === 'default_currency_code' && $value !== null && trim((string) $value) !== ''
                && Currency::findByCode((string) $value) === null) {
                return back()->withErrors(['settings.default_currency_code' => 'Default currency code must be a currency in the Currencies list.'])->withInput();
            }

            SiteSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        return redirect()->route('admin.settings.edit')->with('status', 'Site settings have been updated.');
    }
}
