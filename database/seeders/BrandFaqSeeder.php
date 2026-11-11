<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\BrandFaq;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

/**
 * Seeds the brand_faqs table from the before joining section in
 * resources/captured/en/brand/amazon.html lines 1868-1897. The four
 * Amazon questions and answers are copied exactly from that capture.
 *
 * The table is seeded only when it is empty, so running this seeder
 * twice never creates duplicates.
 *
 * This seeder also upserts the brand detail settings that live in no
 * other seeder: the before joining title and intro (with a {brand}
 * token), the trust line texts, the offers heading intro, the nearby
 * banner, the filter labels and the three filter toggles. Each value
 * comes from the Amazon capture, and updateOrCreate is used per key
 * so the settings land even when other settings already exist. The
 * Amazon full price of 7.19 from the create offer card in the same
 * capture (lines 635-640) is set here too, when it is still empty.
 */
class BrandFaqSeeder extends Seeder
{
    /** @var array<int, array{question: string, answer: string}> */
    private const FAQS = [
        [
            'question' => 'Is it compatible with Amazon?',
            'answer' => 'Sharesub offers cost-sharing for all subscriptions that include a group plan. However, be sure to review the terms and conditions for sharing services, and avoid subscriptions from other countries: this may limit the available content or even block access to the account.',
        ],
        [
            'question' => 'How are payments handled?',
            'answer' => 'Your peer-to-peer transactions are processed in accordance with European regulations (PSD2): the identities of the organizers are verified, and payments are held for one month to allow for any refunds.',
        ],
        [
            'question' => 'What happens if a member leaves the group?',
            'answer' => 'Members are free to leave at any time, but will not receive a refund for the current month. This allows the organizer to retain a higher participation fee, and the available spots will be moved to the top of the list to fill the group quickly.',
        ],
        [
            'question' => 'Should you share your password?',
            'answer' => 'No, most of the time, services are shared via an invitation link or an email invitation. You then join the group using your own username and password to ensure the confidentiality of your data. In the few cases where this is necessary, a secure manager is made available to you. You may also sometimes use a dedicated profile, protected by a PIN.',
        ],
    ];

    /** @var array<string, string> */
    private const SETTINGS = [
        'brand_before_join_title' => 'Before joining a group {brand}',
        'brand_before_join_intro' => 'Co-subscriptions are easy to understand, but it\'s a good idea to check how they work before you get started. Here are the answers to the most frequently asked questions.',
        'brand_trust_rating_text' => '4,6/5, 5200+ reviews on Trustpilot and Google',
        'brand_trust_users_text' => '1M+ users',
        'brand_offers_intro' => 'Sign up for an available spot on a subscription plan for {brand} in your country. Each offer lists the subscription plan and the amount of your monthly payment, including all fees.',
        'brand_nearby_title' => '📍Connect to find out about nearby subscriptions',
        'brand_nearby_text' => '📣 You speak the same language 🤝 Greater trust ✅ Compatible local offers',
        'brand_filter_label' => 'Filters',
        'brand_browse_label' => 'Browse',
        'brand_toggle_access_title' => 'Immediate Access',
        'brand_toggle_access_text' => 'You will receive your login credentials immediately.',
        'brand_toggle_identity_title' => 'Verified identity',
        'brand_toggle_identity_text' => 'This user has provided additional information to ensure their identity',
        'brand_toggle_invoice_title' => 'Verified invoice',
        'brand_toggle_invoice_text' => 'This user has provided valid proof of payment.',
    ];

    public function run(): void
    {
        foreach (self::SETTINGS as $key => $value) {
            SiteSetting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        $brand = Brand::query()->where('slug', 'amazon')->first();

        if ($brand === null) {
            return;
        }

        if ($brand->full_price === null) {
            $brand->update(['full_price' => 7.19]);
        }

        if (BrandFaq::query()->exists()) {
            return;
        }

        foreach (self::FAQS as $order => $faq) {
            BrandFaq::query()->create([
                'brand_id' => $brand->id,
                'question' => $faq['question'],
                'answer' => $faq['answer'],
                'sort_order' => $order,
                'is_active' => true,
            ]);
        }
    }
}
