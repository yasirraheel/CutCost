<?php

namespace Database\Seeders;

use App\Models\BankAccount;
use App\Models\ChatMessage;
use App\Models\Offer;
use App\Models\OfferMember;
use App\Models\OfferPayment;
use App\Models\PaymentMethod;
use App\Models\Payout;
use App\Models\User;
use App\Models\WalletBalance;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Demo data for the Raw Pages 2 data layer (dashboard, shared
 * subscriptions, chat, wallet, cards, bank account, documents).
 *
 * Everything here is generic demo data derived from the existing demo
 * offers and users (demo@example.com and admin@example.com). No real
 * person's name, message, amount or account detail from the captures
 * is used anywhere: member names are marked demo, chat wording is a
 * generic greeting about the subscription, the card is the test Visa
 * 4242 and the bank is "Demo Bank" with a masked reference ending
 * 1234. No full card number and no full IBAN is ever stored.
 *
 * Idempotent: every row is written with updateOrCreate or
 * firstOrCreate on a stable key (offer_payments uses a whereDate
 * lookup first, because the date cast does not match a DATE column
 * on SQLite), so re-running creates zero duplicates.
 *
 * Derivation formulas (all per currency; EUR figures come from EUR
 * rows only, never from converting another currency, because no
 * exchange rate is invented):
 *
 *   Monthly income base B = sum over the demo user's active offers in
 *   that currency of (offer.price x current co-subscriber count).
 *   The co-subscriber count is the current members seeded below,
 *   excluding the owner member.
 *
 *   pending = B
 *   available = B, available on the 9th of next month (the payout
 *   policy transfers funds on the 9th)
 *   total_earnings = B x months active, where months active is the
 *   whole months since the oldest active offer in that currency was
 *   created, minimum 1
 *
 *   payouts = one row per month for the latest three months, each on
 *   the 9th, each amount B, label 'Transfer made'
 *
 * Members are seeded only for the demo user's active offers. The
 * admin user has no offers, so the admin gets only the card and bank
 * rows (which need no offer), not members, wallet balances or
 * payouts.
 */
class RawPages2DemoSeeder extends Seeder
{
    public function run(): void
    {
        $demo = User::query()->where('email', 'demo@example.com')->first();
        if ($demo === null) {
            return;
        }

        $this->seedPaymentMethod($demo, 'Demo User');
        $this->seedBankAccount($demo);

        $admin = User::query()->where('email', 'admin@example.com')->first();
        if ($admin !== null) {
            $this->seedPaymentMethod($admin, 'Demo Admin');
            $this->seedBankAccount($admin);
        }

        $offers = Offer::query()
            ->where('user_id', $demo->id)
            ->where('status', 'active')
            ->get();

        foreach ($offers as $offer) {
            $this->seedMembers($offer, $demo);
            $this->seedChatMessages($offer, $demo);
            $this->seedOfferPayments($offer);
        }

        $this->seedWalletAndPayouts($demo, $offers);
    }

    private function seedMembers(Offer $offer, User $demo): void
    {
        $joinedAt = $offer->created_at ?? now();

        OfferMember::query()->updateOrCreate(
            ['offer_id' => $offer->id, 'username' => $demo->username ?? 'demo-user'],
            [
                'user_id' => $demo->id,
                'initials' => $this->initials($demo->first_name, $demo->last_name),
                'joined_at' => $joinedAt,
                'left_at' => null,
                'status' => OfferMember::STATUS_CURRENT,
                'invitation_email' => null,
                'invite_sent_at' => null,
            ],
        );

        // Three demo co-subscribers. Together with the owner member
        // this stays inside the offer's shareable seats.
        foreach (['a' => 'DA', 'b' => 'DB', 'c' => 'DC'] as $letter => $initials) {
            $username = 'demo-member-'.$letter;
            OfferMember::query()->updateOrCreate(
                ['offer_id' => $offer->id, 'username' => $username],
                [
                    'user_id' => null,
                    'initials' => $initials,
                    'joined_at' => $joinedAt,
                    'left_at' => null,
                    'status' => OfferMember::STATUS_CURRENT,
                    'invitation_email' => $username.'@example.com',
                    'invite_sent_at' => $joinedAt,
                ],
            );
        }

        $leftAt = now()->greaterThan($joinedAt) ? now() : $joinedAt;
        OfferMember::query()->updateOrCreate(
            ['offer_id' => $offer->id, 'username' => 'demo-member-old'],
            [
                'user_id' => null,
                'initials' => 'DO',
                'joined_at' => $joinedAt,
                'left_at' => $leftAt,
                'status' => OfferMember::STATUS_PREVIOUS,
                'invitation_email' => 'demo-member-old@example.com',
                'invite_sent_at' => $joinedAt,
            ],
        );
    }

    /**
     * One paid row per current co-subscriber (the owner member is not a
     * payer) for the current month and the two months before, each the
     * offer's per-subscriber price in the offer's own currency, marked
     * paid on the 9th of the month, the same day the payout policy
     * transfers funds. Months before a member joined get no row.
     */
    private function seedOfferPayments(Offer $offer): void
    {
        $members = OfferMember::query()
            ->where('offer_id', $offer->id)
            ->where('status', OfferMember::STATUS_CURRENT)
            ->where(function ($query) use ($offer): void {
                $query->whereNull('user_id')->orWhere('user_id', '!=', $offer->user_id);
            })
            ->get();

        foreach ($members as $member) {
            $joined = $member->joined_at ?? $offer->created_at ?? now();

            foreach ([0, 1, 2] as $monthsBack) {
                $month = now()->startOfMonth()->subMonths($monthsBack);
                if ($month->lt($joined->copy()->startOfMonth())) {
                    continue;
                }

                // Matched with whereDate, not updateOrCreate on the
                // month key: the model casts month to a date, and the
                // cast serialises to a datetime string, which does not
                // match a DATE column on SQLite and would insert a
                // duplicate on every run (same pitfall as payouts).
                $row = OfferPayment::query()
                    ->where('offer_id', $offer->id)
                    ->where('member_id', $member->id)
                    ->whereDate('month', $month->toDateString())
                    ->first();
                $values = [
                    'status' => OfferPayment::STATUS_PAID,
                    'paid_at' => $month->copy()->addDays(8)->toDateString(),
                    'amount' => round((float) ($offer->price ?? 0), 2),
                    'currency' => $offer->currency ?: 'GBP',
                ];
                if ($row === null) {
                    OfferPayment::query()->create(array_merge([
                        'offer_id' => $offer->id,
                        'member_id' => $member->id,
                        'month' => $month->toDateString(),
                    ], $values));
                } else {
                    $row->update($values);
                }
            }
        }
    }

    private function seedChatMessages(Offer $offer, User $demo): void
    {
        $thread = OfferMember::query()
            ->where('offer_id', $offer->id)
            ->where('username', 'demo-member-a')
            ->first();
        if ($thread === null) {
            return;
        }

        $messages = [
            [
                'sender_id' => null,
                'body' => 'Hi, thanks for sharing this subscription with me.',
                'is_new' => false,
                'is_read' => true,
                'created_at' => now()->subDays(2),
            ],
            [
                'sender_id' => $demo->id,
                'body' => 'Hi, welcome to the group. I will send the login details here.',
                'is_new' => false,
                'is_read' => true,
                'created_at' => now()->subDays(2)->addHour(),
            ],
            [
                'sender_id' => null,
                'body' => 'Great, thank you. Let me know once it is sent.',
                'is_new' => true,
                'is_read' => false,
                'created_at' => now()->subDay(),
            ],
        ];

        foreach ($messages as $message) {
            ChatMessage::query()->firstOrCreate(
                [
                    'offer_id' => $offer->id,
                    'member_id' => $thread->id,
                    'sender_id' => $message['sender_id'],
                    'body' => $message['body'],
                ],
                [
                    'is_new' => $message['is_new'],
                    'is_read' => $message['is_read'],
                    'created_at' => $message['created_at'],
                    'updated_at' => $message['created_at'],
                ],
            );
        }
    }

    /**
     * @param  Collection<int, Offer>  $offers
     */
    private function seedWalletAndPayouts(User $demo, Collection $offers): void
    {
        $byCurrency = $offers->groupBy(fn (Offer $offer) => $offer->currency ?: 'GBP');

        foreach ($byCurrency as $currency => $currencyOffers) {
            $base = 0.0;
            $oldest = null;
            foreach ($currencyOffers as $offer) {
                $coSubscribers = OfferMember::query()
                    ->where('offer_id', $offer->id)
                    ->where('status', OfferMember::STATUS_CURRENT)
                    ->where(function ($query) use ($demo) {
                        $query->whereNull('user_id')->orWhere('user_id', '!=', $demo->id);
                    })
                    ->count();
                $base += (float) ($offer->price ?? 0) * $coSubscribers;

                if ($oldest === null || ($offer->created_at !== null && $offer->created_at->lt($oldest))) {
                    $oldest = $offer->created_at;
                }
            }

            $base = round($base, 2);
            $monthsActive = $oldest !== null ? max(1, (int) $oldest->diffInMonths(now())) : 1;
            $availableOn = now()->startOfMonth()->addMonth()->addDays(8);

            WalletBalance::query()->updateOrCreate(
                ['user_id' => $demo->id, 'metric' => WalletBalance::METRIC_PENDING, 'currency' => $currency],
                ['amount' => $base, 'available_on' => null],
            );
            WalletBalance::query()->updateOrCreate(
                ['user_id' => $demo->id, 'metric' => WalletBalance::METRIC_AVAILABLE, 'currency' => $currency],
                ['amount' => $base, 'available_on' => $availableOn],
            );
            WalletBalance::query()->updateOrCreate(
                ['user_id' => $demo->id, 'metric' => WalletBalance::METRIC_TOTAL_EARNINGS, 'currency' => $currency],
                ['amount' => round($base * $monthsActive, 2), 'available_on' => null],
            );

            // One payout per month for the latest three months, each on
            // the 9th, each the monthly income base.
            $latest = now()->startOfMonth()->addDays(8);
            if ($latest->isFuture()) {
                $latest = $latest->subMonth();
            }
            foreach ([0, 1, 2] as $monthsBack) {
                $paidAt = $latest->copy()->subMonths($monthsBack);
                // Matched with whereDate, not updateOrCreate on the
                // paid_at key: the model casts paid_at to a date, and
                // the cast serialises to a datetime string, which does
                // not match a DATE column on SQLite and would insert
                // a duplicate on every run.
                $row = Payout::query()
                    ->where('user_id', $demo->id)
                    ->where('currency', $currency)
                    ->whereDate('paid_at', $paidAt->toDateString())
                    ->first();
                if ($row === null) {
                    Payout::query()->create([
                        'user_id' => $demo->id,
                        'label' => 'Transfer made',
                        'paid_at' => $paidAt->toDateString(),
                        'amount' => $base,
                        'currency' => $currency,
                    ]);
                } else {
                    $row->update(['label' => 'Transfer made', 'amount' => $base]);
                }
            }
        }
    }

    private function seedPaymentMethod(User $user, string $holderName): void
    {
        PaymentMethod::query()->updateOrCreate(
            ['user_id' => $user->id, 'last4' => '4242'],
            [
                'brand' => 'Visa',
                'exp_month' => 12,
                'exp_year' => 2028,
                'holder_name' => $holderName,
                'is_main' => true,
                'stripe_ref' => null,
            ],
        );
    }

    private function seedBankAccount(User $user): void
    {
        BankAccount::query()->updateOrCreate(
            ['user_id' => $user->id, 'currency' => 'GBP'],
            [
                'bank_name' => 'Demo Bank',
                'masked_reference' => '****1234',
                'stripe_ref' => null,
            ],
        );
    }

    private function initials(?string $firstName, ?string $lastName): string
    {
        $first = mb_strtoupper(mb_substr($firstName ?? 'D', 0, 1));
        $last = mb_strtoupper(mb_substr($lastName ?? 'U', 0, 1));

        return $first.$last;
    }
}
