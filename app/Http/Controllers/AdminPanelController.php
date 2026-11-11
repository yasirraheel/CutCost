<?php

namespace App\Http\Controllers;

use App\Models\KycSubmission;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminPanelController extends Controller
{
    /** @var array<string, string> */
    private const PAGES = [
        'dashboard' => 'en/dashboard.html',
        'shared-subscriptions' => 'en/my-shared-subscriptions.html',
        'joined-subscriptions' => 'en/joined-subscriptions.html',
        'waiting-list' => 'en/waiting-list.html',
        'wallet' => 'en/my-wallet.html',
        'cards' => 'en/my-cards.html',
        'account' => 'en/my-account.html',
        'settings' => 'en/my-settings.html',
        'chat' => 'en/my-chat/subscriptions.html',
    ];

    public function show(string $section = 'dashboard'): Response
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        abort_unless(isset(self::PAGES[$section]), 404);

        return $this->adminResponse(
            app(CapturedPageController::class)->show(self::PAGES[$section]),
            $section,
        );
    }

    public function identity(): Response
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        return $this->adminResponse(
            app(KycController::class)->show(),
        );
    }

    protected function adminResponse(Response $response, ?string $section = null): Response
    {
        $html = (string) $response->getContent();
        if ($section === 'dashboard') {
            $html = $this->adminDashboardContent($html);
        }

        $html = preg_replace(
            '/(<h1\b[^>]*\bclass="dashboard-header-title"[^>]*>\s*)Hello,\s*/i',
            '$1Admin panel · ',
            $html,
            1,
        ) ?? $html;
        $html = preg_replace(
            '/(<div class="dashboard-header-actions-title">\s*)Dashboard(\s*<\/div>)/i',
            '$1Admin dashboard$2',
            $html,
            1,
        ) ?? $html;

        $links = [
            'href="/en/dashboard"' => 'href="/en/admin"',
            'href="/en/my-shared-subscriptions"' => 'href="/en/admin/shared-subscriptions"',
            'href="/en/joined-subscriptions"' => 'href="/en/admin/joined-subscriptions"',
            'href="/en/waiting-list"' => 'href="/en/admin/waiting-list"',
            'href="/en/my-wallet"' => 'href="/en/admin/wallet"',
            'href="/en/my-cards"' => 'href="/en/admin/cards"',
            'href="/en/my-account/identity"' => 'href="/en/admin/account/identity"',
            'href="/en/my-account"' => 'href="/en/admin/account"',
            'href="/en/my-settings"' => 'href="/en/admin/settings"',
            'href="/en/my-chat/subscriptions"' => 'href="/en/admin/chat"',
        ];
        $html = str_replace(array_keys($links), array_values($links), $html);

        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    protected function adminDashboardContent(string $html): string
    {
        $shareStart = strpos($html, '<div class="share-section dashboard mb-4 p-4">');
        $revenuesStart = strpos($html, '<div class="dashboard-progress dashboard-revenues-expenses mb-4 p-4">');
        $paymentsStart = strpos($html, '<div class="dashboard-progress dashboard-payments mb-4 p-4">');

        if ($shareStart === false || $revenuesStart === false || $paymentsStart === false || $revenuesStart <= $shareStart || $paymentsStart <= $revenuesStart) {
            return $html;
        }

        $adminCards = <<<'HTML'
<div class="share-section dashboard mb-4 p-4">
    <h3 class="share-section-title cta-dashboard">Admin <u>overview</u></h3>
    <div class="row">
        <div class="col-12 col-md-6">
            <div class="share-card green">
                <img src="/img/dashboard/share/proposer.svg" class="icon" alt="">
                <h2 class="title">KYC reviews</h2>
                <p class="content">Review identity documents submitted by users and approve or reject them.</p>
                <a href="/en/admin/kyc" class="btn-sharesub dark big">Open KYC reviews</a>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="share-card blue">
                <img src="/img/dashboard/share/participer.svg" class="icon" alt="">
                <h2 class="title">Subscription activity</h2>
                <p class="content">Review the shared and joined subscription areas from the admin panel.</p>
                <a href="/en/admin/shared-subscriptions" class="btn-sharesub dark big">Open subscriptions</a>
            </div>
        </div>
    </div>
</div>

<div class="dashboard-progress mb-3 p-4">
    <h3 class="dashboard-progress-title">Admin actions</h3>
    <div class="progress mt-3" style="height: 10px;">
        <div class="progress-bar bg-green" role="progressbar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
    </div>
    <ul class="dashboard-profile-steps mt-3">
        <li class="row align-items-center">
            <div class="col-1 text-right">🪪</div>
            <div class="col">Review identity requests<div class="text-muted text-sm">Process pending KYC submissions</div></div>
            <div class="col-12 col-md-4 offset-1 offset-md-0 text-md-right"><a class="btn btn-light btn-sm" href="/en/admin/kyc">Open</a></div>
        </li>
        <li class="row align-items-center">
            <div class="col-1 text-right">👥</div>
            <div class="col">Review subscription activity<div class="text-muted text-sm">Open shared and joined subscription pages</div></div>
            <div class="col-12 col-md-4 offset-1 offset-md-0 text-md-right"><a class="btn btn-light btn-sm" href="/en/admin/shared-subscriptions">Open</a></div>
        </li>
        <li class="row align-items-center">
            <div class="col-1 text-right">⚙️</div>
            <div class="col">Manage admin settings<div class="text-muted text-sm">Update the signed-in administrator account</div></div>
            <div class="col-12 col-md-4 offset-1 offset-md-0 text-md-right"><a class="btn btn-light btn-sm" href="/en/admin/settings">Open</a></div>
        </li>
    </ul>
</div>
HTML;

        $platformSection = substr($html, $revenuesStart, $paymentsStart - $revenuesStart);
        $html = substr($html, 0, $shareStart).$platformSection.$adminCards.substr($html, $paymentsStart);
        $pending = KycSubmission::where('status', KycSubmission::STATUS_UNDER_REVIEW)->count();
        $approved = KycSubmission::where('status', KycSubmission::STATUS_APPROVED)->count();
        $users = User::count();
        $values = [$pending, $approved, $users];
        $valueIndex = 0;
        $html = preg_replace_callback(
            '/<span class="stat-value(?: text-green| text-blue)?">€0<\/span>/',
            static function (array $match) use (&$valueIndex, $values): string {
                $value = $values[$valueIndex] ?? 0;
                $valueIndex++;

                return '<span class="stat-value">'.e((string) $value).'</span>';
            },
            $html,
            3,
        ) ?? $html;
        $html = str_replace(
            [
                'Income & expenses',
                'Monthly Savings',
                'Refund coming this month',
                'My monthly expenses',
                'See my wallet →',
                'See my expenses →',
                'Payments',
                "You've spent nothing",
                'Savings in sight',
            ],
            [
                'Platform overview',
                'Pending KYC reviews',
                'Approved identities',
                'Registered users',
                'Review KYC →',
                'Open subscriptions →',
                'Admin activity',
                'No payment tasks pending',
                'Platform is ready',
            ],
            $html,
        );

        return $html;
    }
}
