<?php

namespace Database\Seeders;

use App\Models\ReassuranceItem;
use Illuminate\Database\Seeder;

/**
 * Seeds the reassurance_items table from the reassurance slider in
 * resources/captured/en/brand/amazon.html lines 646-730. The four
 * items and their exact texts come from that capture.
 *
 * The table is seeded only when it is empty, so running this seeder
 * twice never creates duplicates.
 */
class ReassuranceSeeder extends Seeder
{
    /** @var array<int, array{title: string, text: string}> */
    private const ITEMS = [
        [
            'title' => 'Personal account',
            'text' => 'You join the group using an invitation link Amazon, without sharing a password.',
        ],
        [
            'title' => 'Regulated Payments',
            'text' => 'In accordance with European regulations (PSD2), through an authorized payment institution.',
        ],
        [
            'title' => 'Verified Members',
            'text' => 'Sharesub verifies subscriptions and members by reviewing supporting documents.',
        ],
        [
            'title' => 'Clear Rules',
            'text' => 'Only multi-user subscriptions are available. We do not share individual accounts.',
        ],
    ];

    public function run(): void
    {
        if (ReassuranceItem::query()->exists()) {
            return;
        }

        foreach (self::ITEMS as $order => $item) {
            ReassuranceItem::query()->create([
                'title' => $item['title'],
                'text' => $item['text'],
                'icon' => null,
                'sort_order' => $order,
                'is_active' => true,
            ]);
        }
    }
}
