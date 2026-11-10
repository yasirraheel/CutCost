<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CapturedPageController extends Controller
{
    /**
     * Pages that came from the supplied static capture and are now protected
     * by Laravel's session guard.
     *
     * @var array<string, string>
     */
    public const PROTECTED_PAGES = [
        'en/dashboard.html' => 'Dashboard',
        'en/my-shared-subscriptions.html' => 'Shared subscriptions',
        'en/joined-subscriptions.html' => 'Joined subscriptions',
        'en/waiting-list.html' => 'Waiting list',
        'en/my-wallet.html' => 'Wallet',
        'en/my-cards.html' => 'Means of payment',
        'en/my-account.html' => 'Identity and verification',
        'en/my-settings.html' => 'Preferences',
        'en/my-chat/subscriptions.html' => 'Chat',
    ];

    public function show(string $page): Response
    {
        return $this->pageResponse($page);
    }

    public function authPage(string $type, Request $request): Response
    {
        $page = $type === 'register' ? 'en/register.html' : 'en/userlogin.html';
        $html = $this->readPage($page);

        $html = $this->replaceCsrfTokens($html);
        $html = str_replace(
            'id="logout-form" action="#offline"',
            'id="logout-form" action="/en/logout"',
            $html,
        );

        $return = $request->query('return');
        if (is_string($return) && str_starts_with($return, '/en/')) {
            $hiddenReturn = '<input type="hidden" name="return" value="'.e($return).'">';
            $html = preg_replace(
                '/(<form\b[^>]*\bid="(?:loginForm|registerForm)"[^>]*>)/i',
                '$1'.$hiddenReturn,
                $html,
                1,
            ) ?? $html;
        }

        $email = old('email');
        if ($email !== null) {
            $html = preg_replace(
                '/(name="email"[^>]*\bvalue=")[^"]*(")/i',
                '$1'.e($email).'$2',
                $html,
                1,
            ) ?? $html;
        }

        $errors = session('errors');
        if ($errors && $errors->any()) {
            $message = e($errors->first());
            $html = preg_replace(
                '/(<form\b[^>]*\bid="(?:loginForm|registerForm)"[^>]*>)/i',
                '$1<div class="alert alert-danger mb-3" role="alert">'.$message.'</div>',
                $html,
                1,
            ) ?? $html;
        }

        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    public function fallback(string $path): Response
    {
        $path = trim($path, '/');

        foreach ([$path, $path.'.html', $path.'/index.html'] as $candidate) {
            if (isset(self::PROTECTED_PAGES[$candidate]) && ! Auth::check()) {
                return redirect()->guest('/en/userlogin.html?return='.urlencode('/'.$path));
            }

            if ($this->pageExists($candidate)) {
                return $this->pageResponse($candidate);
            }
        }

        abort(404);
    }

    protected function pageResponse(string $page): Response
    {
        $html = $this->readPage($page);
        $html = $this->replaceCsrfTokens($html);
        $html = str_replace(
            'id="logout-form" action="#offline"',
            'id="logout-form" action="/en/logout"',
            $html,
        );

        $html = $this->applyAuthenticatedNavigation($html, $page);
        $html = $this->applyAuthenticatedUserData($html);
        $html = $this->applyAccountData($html, $page);

        return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    protected function applyAuthenticatedUserData(string $html): string
    {
        if (! Auth::check()) {
            return $html;
        }

        $user = Auth::user();
        $nameParts = preg_split('/\s+/', trim((string) $user->name), 2) ?: [];
        $firstName = (string) ($user->first_name ?: ($nameParts[0] ?? ''));
        $fullName = trim((string) ($user->first_name ?: '').' '.(string) ($user->last_name ?: ''));
        $fullName = $fullName !== '' ? $fullName : (string) $user->name;
        $publicName = (string) ($user->username ?: $fullName ?: Str::before((string) $user->email, '@'));

        $javascriptValues = [
            'email' => (string) $user->email,
            'fullname' => $publicName,
        ];

        foreach ($javascriptValues as $key => $value) {
            $escapedKey = preg_quote($key, '/');
            $encodedValue = json_encode($value, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
            $html = preg_replace_callback(
                '/(window\.sharesubVars\[\''.$escapedKey.'\'\]\s*=\s*)\'[^\']*\'/i',
                static fn (array $match): string => $match[1].$encodedValue,
                $html,
            ) ?? $html;
        }

        if ($firstName !== '') {
            $html = preg_replace_callback(
                '/(<h1\b[^>]*\bclass="dashboard-header-title"[^>]*>\s*Hello,\s*<span\b[^>]*\bclass="text-green"[^>]*>)[^<]*(<\/span>\s*<\/h1>)/i',
                static fn (array $match): string => $match[1].e($firstName).$match[2],
                $html,
            ) ?? $html;
        }

        return $html;
    }

    protected function applyAccountData(string $html, string $page): string
    {
        if ($page !== 'en/my-account.html' || ! Auth::check()) {
            return $html;
        }

        $user = Auth::user();
        $nameParts = preg_split('/\s+/', trim((string) $user->name), 2) ?: [];
        $values = [
            'firstname' => $user->first_name ?: ($nameParts[0] ?? ''),
            'lastname' => $user->last_name ?: ($nameParts[1] ?? ''),
            'username' => $user->username,
            'birthdate' => $user->birthdate?->format('Y-m-d') ?? $user->birthdate,
            'email' => $user->email,
            'phone' => $user->phone,
            'intl-phone' => $user->intl_phone,
            'address1' => $user->address1,
            'address2' => $user->address2,
            'city' => $user->city,
            'zip' => $user->zip,
            'state' => $user->state,
        ];

        $html = preg_replace(
            '/(<form\b[^>]*\bclass="stripe-create"[^>]*)(>)/i',
            '$1 action="/en/my-account.html"$2',
            $html,
            1,
        ) ?? $html;

        foreach ($values as $field => $value) {
            $escapedField = preg_quote($field, '/');
            $escapedValue = e((string) ($value ?? ''));
            $html = preg_replace(
                '/(<input\b(?=[^>]*\bname="'.$escapedField.'")[^>]*\bvalue=")[^"]*(")/i',
                '${1}'.$escapedValue.'${2}',
                $html,
                1,
            ) ?? $html;
        }

        $country = strtoupper((string) ($user->country ?? ''));
        $html = preg_replace_callback(
            '/(<select\b[^>]*\bname="country"[^>]*>.*?<\/select>)/is',
            function (array $matches) use ($country): string {
                $select = preg_replace('/\sselected\b/i', '', $matches[1]) ?? $matches[1];
                $value = preg_quote($country !== '' ? $country : '#offline', '/');

                return preg_replace(
                    '/(<option\b[^>]*\bvalue="'.$value.'"[^>]*)>/i',
                    '$1 selected>',
                    $select,
                    1,
                ) ?? $select;
            },
            $html,
            1,
        ) ?? $html;

        $message = '';
        $errors = session('errors');
        if ($errors && $errors->any()) {
            $message = '<div class="alert alert-danger mb-3" role="alert">'.e($errors->first()).'</div>';
        } elseif (is_string(session('status'))) {
            $message = '<div class="alert alert-success mb-3" role="status">'.e(session('status')).'</div>';
        }

        if ($message !== '') {
            $html = preg_replace(
                '/(<form\b[^>]*\bclass="stripe-create"[^>]*>)/i',
                '$1'.$message,
                $html,
                1,
            ) ?? $html;
        }

        return $html;
    }

    protected function applyAuthenticatedNavigation(string $html, string $page): string
    {
        $homePages = [
            'index.html',
            'en/index.html',
            'en/subscriptions.html',
            'en/subscriptions/house.html',
            'en/brand/amazon.html',
        ];

        if (! Auth::check() || ! in_array($page, $homePages, true)) {
            return $html;
        }

        $dashboard = $this->readPage('en/dashboard.html');
        if (! preg_match(
            '/<ul class="navbar-nav ml-auto">(.*?)<li class="nav-item d-flex">\s*<div class="dropdown lang-dropdown/s',
            $dashboard,
            $dashboardMatch,
        )) {
            return $html;
        }

        $shareButton = <<<'HTML'
<li class="nav-item">
    <a class="btn-sharesub green" href="/en/register.html">Share my subscription</a>
</li>
HTML;
        $authenticatedItems = $shareButton.$dashboardMatch[1];

        $html = preg_replace_callback(
            '/(<ul class="navbar-nav ml-auto">).*?(<li class="nav-item d-flex">\s*<div class="dropdown lang-dropdown)/s',
            fn (array $current): string => $current[1].$authenticatedItems.$current[2],
            $html,
            1,
        ) ?? $html;

        if (! str_contains($html, 'id="logout-form"')) {
            $logoutForm = '<form id="logout-form" action="/en/logout" method="POST" style="display:none;">'
                .'<input type="hidden" name="_token" value="'.csrf_token().'">'
                .'</form>';
            $html = str_replace('</body>', $logoutForm.'</body>', $html);
        }

        return $html;
    }

    protected function readPage(string $page): string
    {
        if (! $this->pageExists($page)) {
            abort(404);
        }

        return file_get_contents(resource_path('captured/'.ltrim($page, '/')));
    }

    protected function pageExists(string $page): bool
    {
        $relative = ltrim(str_replace(['..', '\\'], '', $page), '/');

        return is_file(resource_path('captured/'.$relative));
    }

    protected function replaceCsrfTokens(string $html): string
    {
        return preg_replace(
            '/(name="_token"\s+value=")[^"]*(")/i',
            '$1'.csrf_token().'$2',
            $html,
        ) ?? $html;
    }
}
