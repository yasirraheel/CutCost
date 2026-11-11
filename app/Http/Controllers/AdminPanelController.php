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

        $usersNavigation = <<<'HTML'
<li class="navi_dash-item ">
    <a class="navi_dash-link" href="/en/admin/users">
        <span class="navi_dash-icon">
            <svg width="30" height="30" viewBox="0 0 30 30" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M15 5C16.3261 5 17.5979 5.52678 18.5355 6.46447C19.4732 7.40215 20 8.67392 20 10C20 11.3261 19.4732 12.5979 18.5355 13.5355C17.5979 14.4732 16.3261 15 15 15C13.6739 15 12.4021 14.4732 11.4645 13.5355C10.5268 12.5979 10 11.3261 10 10C10 8.67392 10.5268 7.40215 11.4645 6.46447C12.4021 5.52678 13.6739 5 15 5V5ZM15 17.5C20.525 17.5 25 19.7375 25 22.5V25H5V22.5C5 19.7375 9.475 17.5 15 17.5Z" />
            </svg>
        </span>
        <span class="navi_dash-text">Users</span>
    </a>
</li>
HTML;
        $moduleNavigation = <<<'HTML'
<li class="navi_dash-item ">
    <a class="navi_dash-link" href="/en/admin/brands">
        <span class="navi_dash-icon">
            <svg width="30" height="30" viewBox="0 0 30 30" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M4 4h12l10 10-12 12L4 16V4z" />
            </svg>
        </span>
        <span class="navi_dash-text">Brands</span>
    </a>
</li>
<li class="navi_dash-item ">
    <a class="navi_dash-link" href="/en/admin/categories">
        <span class="navi_dash-icon">
            <svg width="30" height="30" viewBox="0 0 30 30" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <rect x="4" y="4" width="9" height="9" rx="1" />
                <rect x="17" y="4" width="9" height="9" rx="1" />
                <rect x="4" y="17" width="9" height="9" rx="1" />
                <rect x="17" y="17" width="9" height="9" rx="1" />
            </svg>
        </span>
        <span class="navi_dash-text">Categories</span>
    </a>
</li>
<li class="navi_dash-item ">
    <a class="navi_dash-link" href="/en/admin/content/settings">
        <span class="navi_dash-icon">
            <svg width="30" height="30" viewBox="0 0 30 30" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <rect x="4" y="7" width="22" height="2" />
                <rect x="4" y="14" width="22" height="2" />
                <rect x="4" y="21" width="22" height="2" />
                <circle cx="10" cy="8" r="3" />
                <circle cx="20" cy="15" r="3" />
                <circle cx="12" cy="22" r="3" />
            </svg>
        </span>
        <span class="navi_dash-text">Site Settings</span>
    </a>
</li>
<li class="navi_dash-item ">
    <a class="navi_dash-link" href="/en/admin/how-it-works">
        <span class="navi_dash-icon">
            <svg width="30" height="30" viewBox="0 0 30 30" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <rect x="4" y="20" width="7" height="6" />
                <rect x="11" y="14" width="7" height="12" />
                <rect x="18" y="8" width="8" height="18" />
            </svg>
        </span>
        <span class="navi_dash-text">How It Works</span>
    </a>
</li>
<li class="navi_dash-item ">
    <a class="navi_dash-link" href="/en/admin/faqs">
        <span class="navi_dash-icon">
            <svg width="30" height="30" viewBox="0 0 30 30" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path fill-rule="evenodd" d="M6 4h18v22H6V4zm4 6v2h10v-2H10v-2zm0 5v2h10v-2H10v-2zm0 5v2h6v-2h-6v-2z" />
            </svg>
        </span>
        <span class="navi_dash-text">FAQs</span>
    </a>
</li>
<li class="navi_dash-item ">
    <a class="navi_dash-link" href="/en/admin/testimonials">
        <span class="navi_dash-icon">
            <svg width="30" height="30" viewBox="0 0 30 30" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M6 6h8v8c0 4-2 6-5 7l-1-2c1.5-.5 2.5-1.5 2.5-3H6V6zm12 0h8v8c0 4-2 6-5 7l-1-2c1.5-.5 2.5-1.5 2.5-3h-4.5V6z" />
            </svg>
        </span>
        <span class="navi_dash-text">Testimonials</span>
    </a>
</li>
<li class="navi_dash-item ">
    <a class="navi_dash-link" href="/en/admin/press">
        <span class="navi_dash-icon">
            <svg width="30" height="30" viewBox="0 0 30 30" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path fill-rule="evenodd" d="M4 5h22v20H4V5zm4 4v2h14V9H8zm0 5v2h14v-2H8zm0 5v2h8v-2H8z" />
            </svg>
        </span>
        <span class="navi_dash-text">Press</span>
    </a>
</li>
<li class="navi_dash-item ">
    <a class="navi_dash-link" href="/en/admin/footer-groups">
        <span class="navi_dash-icon">
            <svg width="30" height="30" viewBox="0 0 30 30" fill="none" stroke="currentColor" stroke-width="3" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <circle cx="10" cy="15" r="6" />
                <circle cx="20" cy="15" r="6" />
            </svg>
        </span>
        <span class="navi_dash-text">Footer Links</span>
    </a>
</li>
<li class="navi_dash-item ">
    <a class="navi_dash-link" href="/en/admin/features">
        <span class="navi_dash-icon">
            <svg width="30" height="30" viewBox="0 0 30 30" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M15 4l3.2 6.9 7.3.8-5.5 5 1.5 7.3L15 20.4 8.5 24 10 16.7 4.5 11.7l7.3-.8L15 4z" />
            </svg>
        </span>
        <span class="navi_dash-text">Features</span>
    </a>
</li>
HTML;
        $html = preg_replace(
            '/(<li class="navi_dash-item ">\s*<a class="navi_dash-link  " href="\/en\/admin\/account">)/i',
            $usersNavigation.$moduleNavigation.'$1',
            $html,
            1,
        ) ?? $html;

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
<div class="share-section dashboard mb-4 p-4 mt-4">
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
        <li class="row align-items-center">
            <div class="col-1 text-right">👤</div>
            <div class="col">Manage users<div class="text-muted text-sm">View, edit, promote or remove user accounts</div></div>
            <div class="col-12 col-md-4 offset-1 offset-md-0 text-md-right"><a class="btn btn-light btn-sm" href="/en/admin/users">Open</a></div>
        </li>
    </ul>
</div>
HTML;

        $platformSection = substr($html, $revenuesStart, $paymentsStart - $revenuesStart);
        $discoverSection = $this->discoverSection();
        $html = substr($html, 0, $shareStart).$platformSection.$discoverSection.$adminCards.substr($html, $paymentsStart);
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

    protected function discoverSection(): string
    {
        $source = file_get_contents(resource_path('captured/en/my-shared-subscriptions.html'));

        if ($source === false) {
            return '';
        }

        $start = strpos($source, '<div class="subscriptions-suggestions">');
        if ($start === false) {
            return '';
        }

        preg_match_all('/<\/?div\b[^>]*>/i', $source, $tags, PREG_OFFSET_CAPTURE, $start);
        $depth = 0;
        foreach ($tags[0] as [$tag, $offset]) {
            $depth += str_starts_with(strtolower($tag), '</div') ? -1 : 1;
            if ($depth === 0) {
                $section = substr($source, $start, $offset + strlen($tag) - $start);

                return str_replace(
                    '<div class="subscriptions-suggestions">',
                    '<div class="subscriptions-suggestions mt-4">',
                    $section,
                );
            }
        }

        return '';
    }
}
