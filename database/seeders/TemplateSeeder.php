<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Currency;
use App\Models\Template;
use Illuminate\Database\Seeder;

/**
 * Seeds the templates table from the saving calculator :brands JSON in
 * resources/captured/en/index.html line 2427. Every row is the lowest
 * price template of one of the 13 calculator brands. Nothing here is
 * invented: name, slug, seats, prices and helper texts all come from
 * that JSON.
 *
 * The table is seeded only when it is empty, so running this seeder
 * twice never creates duplicates.
 *
 * Each template is linked to the Brand row with the same slug. Three
 * calculator slugs differ from the Brand slugs and are mapped in
 * SLUG_ALIASES: nordvpn (nord-vpn), office-365 (microsoft) and
 * purevpn (pure-vpn), so all 13 calculator brands seed.
 * brands get a template row.
 *
 * The prices row holds the retail price: prices[0].price is the full
 * retail price (for example 7.99 for Audible) while
 * current_country_price_with_fees is the shared seat price (1.85).
 * The row currency comes from the currencies table by its ISO numeric
 * id; every calculator brand uses the default currency in the capture.
 *
 * Em and en dashes in the captured helper texts (Netflix) are
 * replaced with a plain hyphen and spaces, as elsewhere in the
 * seeders, so no long dash ever lands in the database.
 *
 * The 8 Amazon templates from the captured get-templates response
 * are upserted on every seed (seedAmazonTemplates), because the
 * calculator only carries the cheapest one per brand.
 */
class TemplateSeeder extends Seeder
{
    /** Calculator slugs that differ from the Brand slugs. */
    private const SLUG_ALIASES = [
        'nordvpn' => 'nord-vpn',
        'office-365' => 'microsoft',
        'purevpn' => 'pure-vpn',
    ];

    /**
     * The 8 Amazon templates from the captured get-templates API
     * response (Raw Pages zip (3), www.sharesub.com/en/get-templates.html).
     * Prices are the United Kingdom (country_id 237, GBP 826) rows,
     * which is what the wizard shows for the default UK flow; they
     * match Nabila's screenshots exactly (users max and prices).
     * Nothing here is invented.
     *
     * @var array<int, array<string, mixed>>
     */
    private const AMAZON_TEMPLATES = [
        [
            'slug' => 'amazon-prime',
            'name' => 'Amazon Prime Delivery',
            'seats' => 2,
            'shareable_seats' => 2,
            'available_seats' => 1,
            'credentials_type' => 5,
            'frequency' => 2,
            'cost_sharing' => 1,
            'retail_price' => 6.99,
            'description' => 'Amazon Prime is a subscription membership to Amazon that offers customers premium services for a yearly or monthly fee. Amazon Prime provides discounts on shipping, Twitch Prime, Amazon Drive and thirty minutes early access to Lightning Deals for a yearly subscription fee. However, a shared amazon subscription does NOT give access to Amazon Prime Video.',
            'description_seo' => 'Amazon Prime is a subscription membership to Amazon that offers customers premium services for a yearly or monthly fee. Amazon Prime provides discounts on shipping, Twitch Prime, Amazon Drive and thirty minutes early access to Lightning Deals for a yearly subscription fee. However, a shared amazon subscription does NOT give access to Amazon Prime Video.',
            'title_seo' => 'Share an Amazon Prime account - Sharesub',
            'helper_create' => 'Vous avez besoin de l’adresse mail de votre co-abonné pour lui partager Amazon Prime. You can check out <a href="https://www.sharesub.com/cms/en/share-your-amazon-prime-account-with-a-loved-one/">our dedicated tutorial</a>.',
            'helper_join' => 'The owner needs your email address to send you an invitation. Send it in the chat (blue message icon above).',
        ],
        [
            'slug' => 'amazon-music-unlimited',
            'name' => 'Amazon Music Unlimited Family Plan',
            'seats' => 6,
            'shareable_seats' => 6,
            'available_seats' => 5,
            'credentials_type' => 3,
            'frequency' => 2,
            'cost_sharing' => 1,
            'retail_price' => 17.99,
            'description' => 'Amazon Music Unlimited Family is a subscription that offers access to a catalog of 90 million songs. Sharing allows up to 6 users and therefore a very reasonable price per person. Please note, all group members must reside in the same country.',
            'description_seo' => 'Amazon Music Unlimited Family is a subscription that offers access to a catalog of 70 million songs. Sharing allows up to 6 users and therefore a very reasonable price per person. Please note, all group members must reside in the same country.',
            'title_seo' => 'Share Amazon Music Unlimited',
            'helper_create' => '',
            'helper_join' => '',
        ],
        [
            'slug' => 'amazon-prime-video',
            'name' => 'Amazon Prime Video - with ads',
            'seats' => 3,
            'shareable_seats' => 2,
            'available_seats' => 2,
            'credentials_type' => 5,
            'frequency' => 2,
            'cost_sharing' => 1,
            'retail_price' => 8.99,
            'description' => 'Amazon Prime Video is a digital video-on-demand platform that lets you watch many popular movies and series, including Amazon Original series. These include: The Boys, The Summer I Turned Pretty, etc. One of the cheapest players in the SVOD market, especially considering the quality of the services offered.',
            'description_seo' => 'Amazon Prime Video is a digital video-on-demand platform that lets you watch many popular movies and series, including Amazon Original series. These include: The Boys, The Summer I Turned Pretty, etc. One of the cheapest players in the SVOD market, especially considering the quality of the services offered.',
            'title_seo' => 'Share an Amazon Prime Video account - Sharesub',
            'helper_create' => 'Enter the email address and password to log in to Amazon Prime Video in the fields below. This information will be communicated to your co-subscriber once he has paid for the subscription, so that he does not have to wait. So you won\'t have to do anything anymore. Also specify whether they must create a profile (you must guarantee one profile per person). Important precaution: to avoid Amazon purchases with your account, activate the PIN code and purchase protection! See this article: <a href="https://www.sharesub.com/cms/fr/securiser-son-subscription-amazon-prime-video/">Secure your Amazon share</a> (you can do this after finalizing this sharing)',
            'helper_join' => 'The user has provided you with the email and password for the account. Use them to log into <a href="https://primevideo.com/" target="_blank">primevideo.com</a> and create a profile with your first name so the owner knows it\'s you. If you don\'t have a free space, ask the organizer which one to rename in the chat (blue chat icon above).',
        ],
        [
            'slug' => 'audible',
            'name' => 'Audible',
            'seats' => 10,
            'shareable_seats' => 10,
            'available_seats' => 9,
            'credentials_type' => 0,
            'frequency' => 2,
            'cost_sharing' => 1,
            'retail_price' => 7.99,
            'description' => 'Listen to an unlimited selection of Audible Original audiobooks, creations & podcasts and children\'s stories. Each month, choose the audio book that suits you from over 500,000 titles, including over 15,000 in French! With Audible Studios, you\'ll also find exclusive audiobooks only available at Audible, and books in 38 different languages. The latest novels, classics, coaching books or language methods... Listen to a book while jogging, let our narrators and actors tell you a bedtime story, or keep your mind occupied while doing housework!',
            'description_seo' => 'Listen to an unlimited selection of Audible Original audiobooks, creations & podcasts and children\'s stories. Each month, choose the audio book that suits you from over 500,000 titles, including over 15,000 in French! With Audible Studios, you\'ll also find exclusive audiobooks only available at Audible, and books in 38 different languages. The latest novels, classics, coaching books or language methods... Listen to a book while jogging, let our narrators and actors tell you a bedtime story, or keep your mind occupied while doing housework!',
            'title_seo' => 'Share my Audible account on Sharesub',
            'helper_create' => 'Enter your Audible login details in the fields below. This information will be sent to your co-subscriber once they have paid for the subscription, so they don\'t have to wait. So there\'s nothing more for you to do.',
            'helper_join' => 'The user has given you the email address and password for the account.',
        ],
        [
            'slug' => 'prime-video-sans-pub',
            'name' => 'Amazon Prime Video - without Ads',
            'seats' => 3,
            'shareable_seats' => 2,
            'available_seats' => 2,
            'credentials_type' => 0,
            'frequency' => 2,
            'cost_sharing' => 1,
            'retail_price' => 10.98,
            'description' => 'Amazon Prime Video ad-free offers unlimited access to a vast library of award-winning films, TV series and original programmes. Immerse yourself in a world of uninterrupted entertainment, with impeccable streaming quality and the ability to download your favourite content for the ultimate offline experience. Enjoy total immersion in your favourite stories, with no commercial interruptions, and live every moment to the full.',
            'description_seo' => 'Amazon Prime Video ad-free offers unlimited access to a vast library of award-winning films, TV series and original programmes. Immerse yourself in a world of uninterrupted entertainment, with impeccable streaming quality and the ability to download your favourite content for the ultimate offline experience. Enjoy total immersion in your favourite stories, with no commercial interruptions, and live every moment to the full.',
            'title_seo' => 'Share your ad-free Amazon Prime Video subscription on Sharesub',
            'helper_create' => '',
            'helper_join' => '',
        ],
        [
            'slug' => 'amazon-prime-paramount-plus',
            'name' => 'Amazon Prime + Paramount Plus',
            'seats' => 2,
            'shareable_seats' => 1,
            'available_seats' => 1,
            'credentials_type' => 2,
            'frequency' => 2,
            'cost_sharing' => 1,
            'retail_price' => 15.98,
            'description' => 'Watch more with less! The Amazon Prime Video + Paramount+ bundle combines the libraries of both services in a single subscription.',
            'description_seo' => 'Watch more with less! The Amazon Prime Video + Paramount+ bundle combines the libraries of both services in a single subscription.',
            'title_seo' => 'Share your Amazon Prime + Paramount Plus account on Sharesub',
            'helper_create' => 'We recommend <a href="https://www.sharesub.com/cms/fr/comment-partager-son-abonnement-prime-video/">the sharing method detailed in this article</a>. Alternatively, enter the email address and password to log in to Amazon Prime Video in the fields below. This information will be sent to your co-subscriber once they\'ve paid for the subscription, so they don\'t have to wait. This way, you won\'t have to do anything else. Also specify whether they need to create a profile (you must guarantee one profile per person). A few precautions to take: remember to activate double authentication and don\'t leave a payment card on record. Remember to check <a href="https://www.sharesub.com/cms/fr/partager-ses-abonnements-de-maniere-securisee/">our article</a> explaining how to share subscriptions securely.',
            'helper_join' => 'The user has given you the email address and password for the account. Use them to log on to netflix.comhttps://primevideo.com/ and create a profile with your first name so that the owner knows it\'s you. If you don\'t have a free place, ask the organiser which one to rename in the chat room (blue message icon above).',
        ],
        [
            'slug' => 'amazon-prime-video-pass-warner',
            'name' => 'Amazon Prime Video Pass Warner',
            'seats' => 3,
            'shareable_seats' => 3,
            'available_seats' => 2,
            'credentials_type' => 5,
            'frequency' => 2,
            'cost_sharing' => 1,
            'retail_price' => 14.47,
            'description' => 'Warner Pass on Amazon Prime Video is a subscription option that gives users access to a wide selection of content from the Warner Bros. Discovery channels, including HBO, Warner TV, Adult Swim, Cartoon Network, Discovery Channel and more. Available as a paid option for Prime subscribers, this pass gives access to popular series such as Game of Thrones, The Last of Us, Euphoria and Succession, as well as a variety of documentaries, films and cartoons. It allows you to watch this content on-demand, often in its original version and in high definition.',
            'description_seo' => 'Warner Pass on Amazon Prime Video is a subscription option that gives users access to a wide selection of content from the Warner Bros. Discovery channels, including HBO, Warner TV, Adult Swim, Cartoon Network, Discovery Channel and more. Available as a paid option for Prime subscribers, this pass gives access to popular series such as Game of Thrones, The Last of Us, Euphoria and Succession, as well as a variety of documentaries, films and cartoons. It allows you to watch this content on-demand, often in its original version and in high definition.',
            'title_seo' => 'Share my Amazon Prime Video Pass Warner',
            'helper_create' => '',
            'helper_join' => '',
        ],
        [
            'slug' => 'amazon-prime-video-pass-paramount-plus',
            'name' => 'Amazon Prime Video Pass Paramount Plus',
            'seats' => 3,
            'shareable_seats' => 3,
            'available_seats' => 2,
            'credentials_type' => 5,
            'frequency' => 2,
            'cost_sharing' => 1,
            'retail_price' => 12.76,
            'description' => 'The Paramount+ Pass on Amazon Prime Video offers privileged access to a large catalogue of iconic films and series from Paramount Studios, as well as captivating original productions. From major franchises such as Star Trek, Halo and Yellowstone, to drama series from Showtime, cult comedies from Comedy Central and children\'s programming from Nickelodeon, this pass is packed with content to suit all tastes. Whether you\'re a fan of fast-paced thrillers, family sagas, ambitious science fiction or children\'s entertainment, Paramount+ immerses you in a rich, varied and exciting universe, which you can discover directly from your Prime Video interface.',
            'description_seo' => 'The Paramount+ Pass on Amazon Prime Video offers privileged access to a large catalogue of iconic films and series from Paramount Studios, as well as captivating original productions. From major franchises such as Star Trek, Halo and Yellowstone, to drama series from Showtime, cult comedies from Comedy Central and children\'s programming from Nickelodeon, this pass is packed with content to suit all tastes. Whether you\'re a fan of fast-paced thrillers, family sagas, ambitious science fiction or children\'s entertainment, Paramount+ immerses you in a rich, varied and exciting universe, which you can discover directly from your Prime Video interface.',
            'title_seo' => 'Share your Amazon Prime Video Pass Paramount Plus subscription on Sharesub',
            'helper_create' => '',
            'helper_join' => '',
        ],
    ];

    public function run(): void
    {
        $this->seedCalculatorTemplates();
        $this->seedAmazonTemplates();
    }

    private function seedCalculatorTemplates(): void
    {
        if (Template::query()->exists()) {
            return;
        }

        $brands = $this->calculatorBrands();

        if ($brands === null) {
            return;
        }

        $order = 0;
        foreach ($brands as $entry) {
            $slug = self::SLUG_ALIASES[(string) ($entry['slug'] ?? '')] ?? (string) ($entry['slug'] ?? '');
            $brand = Brand::query()->where('slug', $slug)->first();

            if ($brand === null) {
                continue;
            }

            $template = $entry['lowest_price_template'] ?? null;

            if (! is_array($template)) {
                continue;
            }

            $priceRow = $template['prices'][0] ?? null;

            Template::query()->create([
                'brand_id' => $brand->id,
                'name' => $this->cleanText((string) ($template['name'] ?? '')),
                'slug' => $this->cleanText((string) ($template['slug'] ?? '')),
                'seats' => isset($template['seats']) ? (int) $template['seats'] : null,
                'shareable_seats' => isset($template['shareable_seats']) ? (int) $template['shareable_seats'] : null,
                'available_seats' => isset($template['available_seats']) ? (int) $template['available_seats'] : null,
                'price_with_fees' => (float) ($template['current_country_price_with_fees'] ?? 0),
                'retail_price' => isset($priceRow['price']) && $priceRow['price'] !== null ? (float) $priceRow['price'] : null,
                'currency' => Currency::findByIsoNumeric((int) ($priceRow['currency_id'] ?? 0))?->code ?? Currency::defaultCode(),
                'description' => $this->cleanText((string) ($template['description'] ?? '')) ?: null,
                'description_seo' => $this->cleanText((string) ($template['description_seo'] ?? '')) ?: null,
                'title_seo' => $this->cleanText((string) ($template['title_seo'] ?? '')) ?: null,
                'helper_create' => $this->cleanHtml((string) ($template['helper_create'] ?? '')) ?: null,
                'helper_join' => $this->cleanHtml((string) ($template['helper_join'] ?? '')) ?: null,
                'sort_order' => $order,
                'is_active' => true,
            ]);
            $order++;
        }
    }

    /**
     * Upserts the 8 Amazon templates from the captured get-templates
     * API response, so the wizard's Subscription step lists the same
     * 8 plans as the original (the calculator only seeds the cheapest
     * one, Audible). Runs on every seed, not just an empty table, so
     * existing databases gain the 7 missing rows and the Audible row
     * gains its captured fields. The existing Audible row keeps its
     * calculator price_with_fees (1.85), the shared seat price the
     * homepage calculator shows for Amazon; the 7 new rows use their
     * retail price there, which never undercuts it. The retail prices
     * are the United Kingdom rows (ISO currency 826), resolved to a
     * currency code through the currencies table like the rest of
     * this seeder, never hardcoded.
     */
    private function seedAmazonTemplates(): void
    {
        $brand = Brand::query()->where('slug', 'amazon')->first();

        if ($brand === null) {
            return;
        }

        $currency = Currency::findByIsoNumeric(826)?->code ?? Currency::defaultCode();

        foreach (self::AMAZON_TEMPLATES as $order => $record) {
            $template = Template::query()->firstOrNew([
                'brand_id' => $brand->id,
                'slug' => $record['slug'],
            ]);

            if (! $template->exists) {
                $template->price_with_fees = $record['retail_price'];
            }

            $template->fill([
                'name' => $record['name'],
                'seats' => $record['seats'],
                'shareable_seats' => $record['shareable_seats'],
                'available_seats' => $record['available_seats'],
                'credentials_type' => $record['credentials_type'],
                'frequency' => $record['frequency'],
                'cost_sharing' => $record['cost_sharing'],
                'retail_price' => $record['retail_price'],
                'currency' => $currency,
                'description' => $this->cleanText($record['description']) ?: null,
                'description_seo' => $this->cleanText($record['description_seo']) ?: null,
                'title_seo' => $this->cleanText($record['title_seo']) ?: null,
                'helper_create' => $this->cleanHtml($record['helper_create']) ?: null,
                'helper_join' => $this->cleanHtml($record['helper_join']) ?: null,
                'sort_order' => $order,
                'is_active' => true,
            ]);
            $template->save();
        }
    }

    /** @return array<int, mixed>|null */
    private function calculatorBrands(): ?array
    {
        $path = resource_path('captured/en/index.html');

        if (! is_file($path)) {
            return null;
        }

        $html = file_get_contents($path);

        if ($html === false) {
            return null;
        }

        if (! preg_match('/\s:brands=\'(.*?)\'\s*:default-brand=/s', $html, $matches)) {
            return null;
        }

        $decoded = json_decode($matches[1], true);

        return is_array($decoded) ? $decoded : null;
    }

    private function cleanText(?string $text): string
    {
        if ($text === null) {
            return '';
        }

        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace(["\u{2014}", "\u{2013}"], ' - ', $text);
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim($text ?? '');
    }

    private function cleanHtml(?string $text): string
    {
        if ($text === null) {
            return '';
        }

        $text = str_replace(["\u{2014}", "\u{2013}"], ' - ', $text);

        return trim($text);
    }
}
