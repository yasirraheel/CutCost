<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keep the admin out of the user panel. An admin who opens a
 * user-facing page is sent to the matching admin section instead,
 * the same mapping the user pages already used (dashboard,
 * my-shared-subscriptions, joined subscriptions, waiting list,
 * chat, wallet, cards, documents, my-account, my-settings):
 * /en/my-chat/subscriptions goes to /en/admin/chat, /en/my-wallet
 * to /en/admin/wallet, and so on. Pages with no matching section
 * (create-subscription, bank account, chat threads) fall back to
 * the admin dashboard. The section mapping applies to GET requests
 * only; a POST that cannot run is bounced to the admin dashboard.
 * Normal users pass through untouched, and admin management routes
 * are not affected.
 */
class RedirectAdminFromUserPanel
{
    /** @var array<string, string> User path (without .html) to admin destination. */
    private const SECTION_MAP = [
        'en/dashboard' => '/en/admin',
        'en/my-shared-subscriptions' => '/en/admin/shared-subscriptions',
        'en/joined-subscriptions' => '/en/admin/joined-subscriptions',
        'en/waiting-list' => '/en/admin/waiting-list',
        'en/my-account' => '/en/admin/account',
        'en/my-account/identity' => '/en/admin/account/identity',
        'en/my-settings' => '/en/admin/settings',
        'en/my-documents' => '/en/admin/account/identity',
        'en/my-wallet' => '/en/admin/wallet',
        'en/my-cards' => '/en/admin/cards',
        'en/my-chat/subscriptions' => '/en/admin/chat',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->isAdmin()) {
            return $next($request);
        }

        if ($request->isMethod('get')) {
            $path = (string) preg_replace('/\.html$/', '', trim($request->path(), '/'));

            if (str_starts_with($path, 'en/my-chat/')) {
                return redirect('/en/admin/chat');
            }

            if (isset(self::SECTION_MAP[$path])) {
                return redirect(self::SECTION_MAP[$path]);
            }
        }

        return redirect()->route('admin.dashboard');
    }
}
