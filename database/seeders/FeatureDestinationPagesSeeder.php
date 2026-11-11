<?php

namespace Database\Seeders;

use App\Models\Feature;
use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Gives the four homepage feature blocks (seeded by ContentSeeder from
 * resources/captured/en/index.html, features section) a real working
 * "Read More" destination, and creates the two destination pages that
 * have no captured CMS page of their own.
 *
 * Nothing here is invented:
 * - Legal Service points at the Terms and Conditions of Use and Sale
 *   page (/cms/en/legal/tos/): the block's own wording conditions
 *   legality on complying with "the service's terms of use", and the
 *   Terms page is the captured page that discusses legally shareable
 *   subscriptions. The Legal information page (/cms/en/legal/) is only
 *   the publisher/host legal notice.
 * - Buyer Protection Program already points at its captured page.
 * - "No fees when you share." and "Secure data" have no captured
 *   destination page anywhere under resources/captured/cms/en, so each
 *   destination page's content is the block's own captured paragraph,
 *   the same source, not new copy.
 *
 * The seeder is safe to re-run: pages are created only when their slug
 * is missing (an admin-edited page is never overwritten), and a feature
 * link is only filled in when it is currently empty (an admin-set link
 * is never overwritten).
 */
class FeatureDestinationPagesSeeder extends Seeder
{
    /** @var array<string, string> Feature title => Read More destination URL. */
    private const DESTINATIONS = [
        'Legal Service' => '/cms/en/legal/tos/',
        'Buyer Protection Program' => '/cms/en/buyer-protection-program/',
        'No fees when you share.' => '/cms/en/no-fees-when-you-share/',
        'Secure data' => '/cms/en/secure-data/',
    ];

    /** @var array<int, array{slug: string, title: string, body: string}> */
    private const PAGES = [
        [
            'slug' => 'no-fees-when-you-share',
            'title' => 'No fees when you share.',
            'body' => 'No fees to withdraw the money you\'ve raised: transfers to your bank account are free and are processed automatically as soon as you reach a small minimum amount. There are no fees for refunds, nor are there any listing fees for sellers.',
        ],
        [
            'slug' => 'secure-data',
            'title' => 'Secure data',
            'body' => 'If you need to share confidential data such as IDs, passwords, license numbers or other information, Sharesub is the right solution to protect this data. Your exchanges are transmitted and stored with strong encryption (AES 256) to ensure your privacy.',
        ],
    ];

    public function run(): void
    {
        $order = (int) (Page::query()->max('sort_order') ?? -1) + 1;

        foreach (self::PAGES as $entry) {
            if (Page::query()->where('slug', $entry['slug'])->exists()) {
                continue;
            }

            Page::query()->create([
                'slug' => $entry['slug'],
                'type' => 'page',
                'title' => $entry['title'],
                'excerpt' => $entry['body'],
                // Same shape as PageSeeder bodies: the heading lives
                // inside the post-content region, so the body carries
                // its own h1 (the captured template's heading markup).
                // Without it the page would render with no heading at
                // all, because renderDbPage replaces the whole
                // post-content region with this body.
                'body_html' => '<h1 class="title large bordered">'.e($entry['title']).'</h1>'."\n".'<p>'.$entry['body'].'</p>',
                'image_path' => null,
                'sort_order' => $order,
                'is_active' => true,
            ]);
            $order++;
        }

        foreach (self::DESTINATIONS as $title => $url) {
            Feature::query()
                ->where('title', $title)
                ->whereNull('link_url')
                ->update(['link_url' => $url]);
        }
    }
}
