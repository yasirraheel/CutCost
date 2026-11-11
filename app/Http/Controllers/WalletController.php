<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\KycSubmission;
use App\Models\SiteSetting;
use App\Models\WalletBalance;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class WalletController extends Controller
{
    /**
     * My wallet: the balances grid, payout policy paragraph, verify
     * banner and payout history, all rendered from the database.
     *
     * The grid renders one line per existing wallet_balances row, GBP
     * first then EUR, with the metric label under the last line of each
     * column. No figure is converted or invented: EUR rows are absent
     * in the dev demo, so the demo renders GBP lines only. That is a
     * recorded gap: EUR lines appear only once EUR balances exist.
     *
     * VERIFY BANNER ASSUMPTION: the two wallet captures likely come
     * from different account states, so the banner condition cannot be
     * proven from them. It shows when identity is not approved OR no
     * bank account is connected, and hides when both are true.
     */
    public function show(Request $request)
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return redirect('/en/admin/wallet');
        }

        $balances = $user->walletBalances()->orderBy('id')->get();

        $metrics = [];
        foreach ([
            WalletBalance::METRIC_PENDING => 'Pending Amount',
            WalletBalance::METRIC_AVAILABLE => 'Available for transfer on %s',
            WalletBalance::METRIC_TOTAL_EARNINGS => 'Total earnings',
        ] as $metric => $label) {
            $rows = $balances->where('metric', $metric);
            if ($rows->isEmpty()) {
                continue;
            }

            $lines = $this->orderedCurrencyLines($rows);
            $availableOn = $rows->first()?->available_on;

            $metrics[] = [
                'metric' => $metric,
                'label' => $metric === WalletBalance::METRIC_AVAILABLE && $availableOn !== null
                    ? sprintf($label, $availableOn->format('d/m'))
                    : ($metric === WalletBalance::METRIC_AVAILABLE
                        ? 'Available for transfer'
                        : $label),
                'lines' => $lines,
            ];
        }

        $years = $user->payouts()
            ->orderByDesc('paid_at')
            ->get(['paid_at'])
            ->map(fn ($payout) => (int) $payout->paid_at->format('Y'))
            ->unique()
            ->sortDesc()
            ->values();

        $year = (int) $request->query('year', $years->first() ?? now()->year);

        $payouts = $user->payouts()
            ->whereYear('paid_at', $year)
            ->orderByDesc('paid_at')
            ->get();

        $identityApproved = $user->kycSubmission?->status === KycSubmission::STATUS_APPROVED;
        $hasBankAccount = $user->bankAccounts()->exists();

        $payoutRows = $payouts->map(function ($payout) {
            return [
                'label' => (string) $payout->label,
                'date' => $payout->paid_at->format('Y/m/d'),
                'amount' => $this->formatAmount((float) $payout->amount, (string) $payout->currency),
            ];
        })->all();

        return view('money.wallet', [
            'title' => 'Wallet - Sharesub',
            'pageTitle' => 'Wallet',
            'activeNav' => 'wallet',
            'activeGroup' => 'payments',
            'user' => $user,
            'metrics' => $metrics,
            'policyHtml' => $this->policyHtml(),
            'verifyBanner' => $this->setting('wallet_policy_2'),
            'showVerifyBanner' => ! $identityApproved || ! $hasBankAccount,
            'years' => $years,
            'year' => $year,
            'payoutRows' => $payoutRows,
        ]);
    }

    /**
     * @return array<int, array{currency: string, amount: string}>
     */
    private function orderedCurrencyLines(Collection $rows): array
    {
        $order = ['GBP' => 0, 'EUR' => 1];

        $sorted = $rows->sortBy(function ($row) use ($order) {
            $currency = strtoupper((string) $row->currency);

            return [$order[$currency] ?? 2, $currency];
        })->values();

        return $sorted->map(function ($row) {
            $currency = strtoupper((string) $row->currency);

            return [
                'currency' => $currency,
                'amount' => $this->formatAmount((float) $row->amount, $currency),
            ];
        })->all();
    }

    private function formatAmount(float $amount, string $currency): string
    {
        $symbol = Currency::symbolFor($currency) ?? '';
        $number = number_format($amount, 2, '.', '');
        $number = rtrim(rtrim($number, '0'), '.');

        return $symbol.$number;
    }

    /**
     * Policy paragraph: the wallet_policy_1 setting, escaped, with the
     * transfer timing phrase bolded as in the capture. Falls back to
     * the captured paragraph when the setting is missing, so the page
     * never renders empty.
     */
    private function policyHtml(): string
    {
        $policy = $this->setting('wallet_policy_1')
            ?? 'Available funds are automatically transferred to your account on the 9th of each month, as soon as your balance exceeds the minimum of €10. Transfers may take 3 to 5 days to reach your account, depending on your bank.';

        $rendered = e($policy);
        $phrase = e('are automatically transferred to your account on the 9th of each month');

        if (str_contains($rendered, $phrase)) {
            $rendered = preg_replace(
                '/'.preg_quote($phrase, '/').'/',
                '<b>$0</b>',
                $rendered,
                1,
            ) ?? $rendered;
        }

        return $rendered;
    }

    private function setting(string $key): ?string
    {
        $value = SiteSetting::where('key', $key)->value('value');

        return $value === null || $value === '' ? null : (string) $value;
    }
}
