<?php

namespace App\Http\Controllers;

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
        );
    }

    public function identity(): Response
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        return $this->adminResponse(
            app(KycController::class)->show(),
        );
    }

    protected function adminResponse(Response $response): Response
    {
        $html = (string) $response->getContent();
        $html = preg_replace(
            '/(<h1\b[^>]*\bclass="dashboard-header-title"[^>]*>\s*)Hello,\s*/i',
            '$1Admin panel · ',
            $html,
            1,
        ) ?? $html;

        $links = [
            'href="/en/dashboard.html"' => 'href="/en/admin"',
            'href="/en/dashboard"' => 'href="/en/admin"',
            'href="/en/my-shared-subscriptions.html"' => 'href="/en/admin/shared-subscriptions"',
            'href="/en/my-shared-subscriptions"' => 'href="/en/admin/shared-subscriptions"',
            'href="/en/joined-subscriptions.html"' => 'href="/en/admin/joined-subscriptions"',
            'href="/en/joined-subscriptions"' => 'href="/en/admin/joined-subscriptions"',
            'href="/en/waiting-list.html"' => 'href="/en/admin/waiting-list"',
            'href="/en/waiting-list"' => 'href="/en/admin/waiting-list"',
            'href="/en/my-wallet.html"' => 'href="/en/admin/wallet"',
            'href="/en/my-wallet"' => 'href="/en/admin/wallet"',
            'href="/en/my-cards.html"' => 'href="/en/admin/cards"',
            'href="/en/my-cards"' => 'href="/en/admin/cards"',
            'href="/en/my-account/identity"' => 'href="/en/admin/account/identity"',
            'href="/en/my-account.html"' => 'href="/en/admin/account"',
            'href="/en/my-account"' => 'href="/en/admin/account"',
            'href="/en/my-settings.html"' => 'href="/en/admin/settings"',
            'href="/en/my-settings"' => 'href="/en/admin/settings"',
            'href="/en/my-chat/subscriptions.html"' => 'href="/en/admin/chat"',
            'href="/en/my-chat/subscriptions"' => 'href="/en/admin/chat"',
        ];
        $html = str_replace(array_keys($links), array_values($links), $html);

        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }
}
