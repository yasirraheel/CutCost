<?php

namespace App\Http\Controllers;

use App\Models\KycSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        if ($redirect = $this->adminRedirect($page)) {
            return $redirect;
        }

        return $this->pageResponse($page);
    }

    public function identityPage(): Response
    {
        if (Auth::user()?->isAdmin() && ! request()->is('en/admin*')) {
            return redirect('/en/admin/account/identity');
        }

        return $this->pageResponse('en/my-account.html', true);
    }

    protected function adminRedirect(string $page): ?Response
    {
        if (! Auth::user()?->isAdmin() || request()->is('en/admin*')) {
            return null;
        }

        $sections = [
            'en/dashboard.html' => 'dashboard',
            'en/my-shared-subscriptions.html' => 'shared-subscriptions',
            'en/joined-subscriptions.html' => 'joined-subscriptions',
            'en/waiting-list.html' => 'waiting-list',
            'en/my-wallet.html' => 'wallet',
            'en/my-cards.html' => 'cards',
            'en/my-account.html' => 'account',
            'en/my-settings.html' => 'settings',
            'en/my-chat/subscriptions.html' => 'chat',
        ];

        if (! isset($sections[$page])) {
            return null;
        }

        $section = $sections[$page];

        return redirect($section === 'dashboard' ? '/en/admin' : '/en/admin/'.$section);
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

    protected function pageResponse(string $page, bool $identityPage = false): Response
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
        $html = $this->applyAccountTabs($html, $identityPage);
        if ($identityPage) {
            $html = $this->applyIdentityPage($html);
        } elseif ($page === 'en/my-settings.html') {
            $html = $this->applySettingsData($html);
        } else {
            $html = $this->applyAccountData($html, $page);
        }

        return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    protected function applyAccountTabs(string $html, bool $identityPage): string
    {
        $accountClass = 'col-6 col-md-4 p-2 tab-navigation'.($identityPage ? '' : ' current');
        $identityClass = 'col-6 col-md-4 p-2 tab-navigation'.($identityPage ? ' current' : '');

        $html = preg_replace(
            '/<a href="\/en\/my-account\.html" class="col-6 col-md-4 p-2 tab-navigation">/',
            '<a href="/en/my-account/identity" class="'.$identityClass.'">',
            $html,
            1,
        ) ?? $html;
        $html = preg_replace(
            '/<a href="\/en\/dashboard\.html" class="col-6 col-md-4 p-2 tab-navigation current">/',
            '<a href="/en/my-account.html" class="'.$accountClass.'">',
            $html,
            1,
        ) ?? $html;
        $html = str_replace(
            'href="/en/my-account.html">Identity &amp; Verification',
            'href="/en/my-account/identity">Identity &amp; Verification',
            $html,
        );
        $html = str_replace(
            'href="/en/my-account.html" class="navi_dash-link active "',
            'href="/en/my-account/identity" class="navi_dash-link active "',
            $html,
        );
        $html = str_replace(
            'href="/en/my-account.html" class="dash-sheet-link active"',
            'href="/en/my-account/identity" class="dash-sheet-link active"',
            $html,
        );

        return $html;
    }

    protected function applyIdentityPage(string $html): string
    {
        $submission = Auth::user()->kycSubmission;
        $html = $this->applyKycStatusNotice($html);
        // Keep validation errors visible, but do not duplicate the template's
        // identity notice with a second success flash after submission.
        $message = session('errors')?->first('kyc') ?: session('errors')?->first();

        $status = $submission?->status ?? KycSubmission::STATUS_NOT_SUBMITTED;
        $form = $this->identityForm($submission, $message);
        $html = preg_replace(
            '/<form\b[^>]*\bclass="stripe-create"[^>]*>.*?<\/form>/is',
            $form,
            $html,
            1,
        ) ?? $html;

        return $html;
    }

    protected function applyKycStatusNotice(string $html): string
    {
        $submission = Auth::user()->kycSubmission;
        $status = $submission?->status ?? KycSubmission::STATUS_NOT_SUBMITTED;
        $toast = match ($status) {
            KycSubmission::STATUS_UNDER_REVIEW => 'Your identity documents are under review.',
            KycSubmission::STATUS_APPROVED => 'Your identity has been approved.',
            KycSubmission::STATUS_REJECTED => 'Your identity verification was rejected. Please submit new documents.',
            default => 'Please complete your identity verification.',
        };
        if ($submission?->admin_note && $status === KycSubmission::STATUS_REJECTED) {
            $toast .= ' Reason: '.e($submission->admin_note);
        }

        $html = str_replace(
            'Please complete your identity first (my account information form)',
            $toast,
            $html,
        );
        if ($status === KycSubmission::STATUS_APPROVED) {
            $html = preg_replace(
                '/(<div class="sharesub-flash sharesub-flash--warning" role="alert")/',
                '$1 style="display:none"',
                $html,
                1,
            ) ?? $html;
        }

        return $html;
    }

    protected function identityForm(?KycSubmission $submission, ?string $message): string
    {
        $status = $submission?->status ?? KycSubmission::STATUS_NOT_SUBMITTED;
        $statusLabel = match ($status) {
            KycSubmission::STATUS_UNDER_REVIEW => 'Under review',
            KycSubmission::STATUS_APPROVED => 'Approved',
            KycSubmission::STATUS_REJECTED => 'Rejected',
            default => 'Not submitted',
        };
        $statusClass = $status === KycSubmission::STATUS_APPROVED ? 'alert-success' : ($status === KycSubmission::STATUS_REJECTED ? 'alert-danger' : 'alert-info');
        $html = '<div class="p1-bold mb-2">Identity verification</div>'
            .'<div class="alert '.$statusClass.' mb-3" role="status"><strong>Status: '.e($statusLabel).'</strong>';
        if ($status === KycSubmission::STATUS_UNDER_REVIEW) {
            $html .= '<br>Your CNIC documents have been submitted. An administrator will review them.';
        } elseif ($status === KycSubmission::STATUS_APPROVED) {
            $html .= '<br>Your identity has been verified successfully.';
        } elseif ($status === KycSubmission::STATUS_REJECTED && $submission?->admin_note) {
            $html .= '<br>'.e($submission->admin_note);
        }
        $html .= '</div>';

        if ($message) {
            $html .= '<div class="alert alert-danger mb-3" role="alert">'.e($message).'</div>';
        }

        if (in_array($status, [KycSubmission::STATUS_APPROVED, KycSubmission::STATUS_UNDER_REVIEW], true)) {
            return '<div class="kyc-upload-panel">'.$html.'</div>';
        }

        return '<form class="kyc-upload-form" method="POST" action="/en/my-account/identity" enctype="multipart/form-data">'
            .'<input type="hidden" name="_token" value="'.csrf_token().'">'
            .$html
            .'<p class="mb-3">Upload the front and back of your CNIC. Accepted files: JPG, PNG or PDF, maximum 5 MB each.</p>'
            .'<div class="form-group"><label for="cnic_front">CNIC front</label><input type="file" name="cnic_front" id="cnic_front" class="input-sharesub" accept="image/jpeg,image/png,application/pdf" required></div>'
            .'<div class="form-group"><label for="cnic_back">CNIC back</label><input type="file" name="cnic_back" id="cnic_back" class="input-sharesub" accept="image/jpeg,image/png,application/pdf" required></div>'
            .'<button type="submit" class="btn-sharesub bleu mx-auto my-2 d-block">Submit for review</button>'
            .'</form>';
    }

    protected function applySettingsData(string $html): string
    {
        $user = Auth::user();

        $html = str_replace(
            '<form method="POST" action="#security">',
            '<form method="POST" action="/en/my-settings/security">',
            $html,
        );
        $html = str_replace(
            '<form method="POST" action="#settings">',
            '<form method="POST" action="/en/my-settings/preferences">',
            $html,
        );

        $html = preg_replace_callback(
            '/(<input\b(?=[^>]*\bname="referral_code")[^>]*\bvalue=")[^"]*(")/i',
            fn (array $match): string => $match[1].e((string) ($user->referral_code ?? '')).$match[2],
            $html,
            1,
        ) ?? $html;

        $language = (string) ($user->language ?: 'en');
        $html = preg_replace_callback(
            '/(<select\b[^>]*\bname="lang"[^>]*>.*?<\/select>)/is',
            function (array $matches) use ($language): string {
                $select = preg_replace('/\sselected\b/i', '', $matches[1]) ?? $matches[1];
                $value = preg_quote($language, '/');

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

        $html = $this->setSettingsCheckbox($html, 'payoutLowAmount', (bool) $user->payout_low_amount);
        $html = $this->setSettingsCheckbox($html, 'payoutConvertCurrency', (bool) $user->payout_convert_currency);

        $devices = DB::table('sessions')
            ->where('user_id', $user->id)
            ->orderByDesc('last_activity')
            ->get();
        $deviceMarkup = $this->trustedDevicesMarkup($devices, session()->getId());
        $html = str_replace(
            '<p class="text-muted">No device registered.</p>',
            $deviceMarkup,
            $html,
        );

        $deleteForm = '<form method="POST" action="/en/my-settings/delete" class="settings-delete-form">'
            .'<input type="hidden" name="_token" value="'.csrf_token().'">'
            .'<div class="p1-bold mt-4 mb-2">Account deletion</div>'
            .'<p>To delete your account, you must have cancelled all subscriptions and stopped all sharing. Deletion is definitive and entails loss of access to your data and the impossibility of obtaining refunds or transfers</p>'
            .'<p>To permanently delete your account, type "Delete" and confirm:</p>'
            .'<div class="form-row align-items-center">'
            .'<div class="col"><input type="text" name="confirmation" class="input-sharesub" placeholder="Delete" required></div>'
            .'<div class="col-auto"><button type="submit" class="btn-sharesub red outline delete-account-button">Delete my account</button></div>'
            .'</div>'
            .'</form>';
        $html = preg_replace(
            '/<delete-account\b.*?<\/delete-account>/is',
            $deleteForm,
            $html,
            1,
        ) ?? $html;

        $errors = session('errors');
        $flash = '';
        if ($errors && $errors->any()) {
            $flash = '<div class="alert alert-danger mb-3" role="alert">'.e($errors->first()).'</div>';
        } elseif (is_string(session('status'))) {
            $flash = '<div class="alert alert-success mb-3" role="status">'.e(session('status')).'</div>';
        }

        if ($flash !== '') {
            $tab = (string) session('settings_tab', 'security');
            $action = match ($tab) {
                'preferences' => '/en/my-settings/preferences',
                'delete' => '/en/my-settings/delete',
                default => '/en/my-settings/security',
            };
            $html = preg_replace(
                '/(<form\b[^>]*\baction="'.preg_quote($action, '/').'"[^>]*>)/i',
                '$1'.$flash,
                $html,
                1,
            ) ?? $html;
        }

        return $html;
    }

    protected function setSettingsCheckbox(string $html, string $id, bool $checked): string
    {
        return preg_replace_callback(
            '/<input\b[^>]*\bid="'.preg_quote($id, '/').'"[^>]*>/i',
            static function (array $matches) use ($checked): string {
                $tag = preg_replace('/\schecked\b/i', '', $matches[0]) ?? $matches[0];
                if (! $checked) {
                    return $tag;
                }

                return preg_replace('/\s*\/>$/', ' checked />', $tag)
                    ?? preg_replace('/>$/', ' checked>', $tag)
                    ?? $tag;
            },
            $html,
            1,
        ) ?? $html;
    }

    protected function trustedDevicesMarkup(object $devices, string $currentSessionId): string
    {
        if ($devices->isEmpty()) {
            return '<p class="text-muted">No device registered.</p>';
        }

        $html = '<div class="settings-trusted-devices">';
        foreach ($devices as $device) {
            $isCurrent = (string) $device->id === $currentSessionId;
            $lastActive = date('Y-m-d H:i', (int) $device->last_activity);
            $label = $isCurrent ? 'This device' : 'Signed-in device';
            $details = $label.' · '.((string) ($device->ip_address ?: 'Unknown IP')).' · Last active '.$lastActive;
            $html .= '<div class="d-flex align-items-center justify-content-between mb-2">'
                .'<span class="text-muted">'.e($details).'</span>';
            if (! $isCurrent) {
                $html .= '<form method="POST" action="/en/my-settings/device/revoke" class="ml-2">'
                    .'<input type="hidden" name="_token" value="'.csrf_token().'">'
                    .'<input type="hidden" name="session_id" value="'.e((string) $device->id).'">'
                    .'<button type="submit" class="btn-sharesub red outline small">Revoke</button>'
                    .'</form>';
            }
            $html .= '</div>';
        }

        return $html.'</div>';
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

        $html = $this->applyKycStatusNotice($html);
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
