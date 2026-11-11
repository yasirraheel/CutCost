<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RendersBrandDetail;
use App\Http\Controllers\Concerns\RendersSiteChrome;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Faq;
use App\Models\Feature;
use App\Models\FooterLinkGroup;
use App\Models\HowItWorksStep;
use App\Models\KycSubmission;
use App\Models\Offer;
use App\Models\OfferMember;
use App\Models\OfferPayment;
use App\Models\Payout;
use App\Models\PressItem;
use App\Models\SiteSetting;
use App\Models\Template;
use App\Models\Testimonial;
use App\Models\WalletBalance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CapturedPageController extends Controller
{
    use RendersBrandDetail;
    use RendersSiteChrome;

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
        if (! is_string($return) || ! str_starts_with($return, '/en/')) {
            $intended = session('url.intended');
            if (is_string($intended) && $intended !== '') {
                $intendedPath = parse_url($intended, PHP_URL_PATH) ?? '';
                $intendedQuery = parse_url($intended, PHP_URL_QUERY) ?? '';
                if (is_string($intendedPath) && str_starts_with($intendedPath, '/en/')) {
                    $return = $intendedQuery !== '' ? $intendedPath.'?'.$intendedQuery : $intendedPath;
                }
            }
        }
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

        $html = $this->applySiteChrome($html, $page);
        $html = $this->normalizePageLinks($html);

        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    public function fallback(string $path): Response
    {
        $path = trim($path, '/');

        if (str_ends_with($path, '.html')) {
            return redirect('/'.substr($path, 0, -5), 301);
        }

        foreach ([$path, $path.'.html', $path.'/index.html'] as $candidate) {
            if (isset(self::PROTECTED_PAGES[$candidate]) && ! Auth::check()) {
                return redirect()->guest('/en/userlogin?return='.urlencode('/'.$path));
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

        $html = $this->applyGlobalContent($html, $page);
        $html = $this->applyDynamicContent($html, $page);
        $html = $this->replaceSavingCalculator($html);

        $html = $this->normalizePageLinks($html);

        return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    /**
     * Arrow icon used inside catalogue and grid brand cards, copied from
     * the captured markup.
     */
    private const CATALOGUE_ARROW_SVG = '<svg width="16" height="14" viewBox="0 0 16 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 11.586C8.60954 11.9765 8.60953 12.6096 9 13C9.39046 13.3905 10.0235 13.3905 10.414 13L15.7069 7.70714C16.0974 7.31661 16.0974 6.68345 15.7069 6.29292L10.414 1.00003C10.0235 0.609564 9.39047 0.609564 9 1.00003C8.60953 1.39049 8.60954 2.02356 9 2.41403L12.586 6.00003H1C0.447715 6.00003 0 6.44774 0 7.00003C0 7.55231 0.447715 8.00003 1 8.00003H12.586L9 11.586Z" fill="currentColor"/></svg>';

    /**
     * Arrow icon used inside the Discover suggestion cards, copied from
     * the captured markup.
     */
    private const SUGGESTION_ARROW_SVG = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 16.586C11.6095 16.9764 11.6095 17.6095 12 18C12.3905 18.3904 13.0235 18.3904 13.414 18L18.7069 12.7071C19.0974 12.3166 19.0974 11.6834 18.7069 11.2929L13.414 5.99997C13.0235 5.6095 12.3905 5.6095 12 5.99997C11.6095 6.39043 11.6095 7.0235 12 7.41397L15.586 11H4C3.44772 11 3 11.4477 3 12C3 12.5523 3.44772 13 4 13H15.586L12 16.586Z" fill="currentColor"/></svg>';

    /**
     * Chat icon used in the shared list row actions and the detail page
     * header, copied from the captured markup.
     */
    private const CHAT_SVG = '<svg class=" " width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"> <path fill-rule="evenodd" clip-rule="evenodd" d="M22.4315 11.3763C22.4315 16.5547 17.6335 20.7526 11.7158 20.7526C10.6544 20.754 9.5975 20.6162 8.57202 20.3427C7.78977 20.7392 5.99354 21.5 2.9717 21.9956C2.70381 22.0385 2.50021 21.7599 2.60602 21.5107C3.0802 20.3909 3.50883 18.8988 3.63742 17.5378C1.99657 15.8903 1 13.7338 1 11.3763C1 6.1979 5.79798 2 11.7158 2C17.6335 2 22.4315 6.1979 22.4315 11.3763ZM7.69735 11.3763C7.69735 11.7315 7.55623 12.0722 7.30503 12.3234C7.05383 12.5746 6.71313 12.7158 6.35788 12.7158C6.00263 12.7158 5.66193 12.5746 5.41073 12.3234C5.15953 12.0722 5.01841 11.7315 5.01841 11.3763C5.01841 11.021 5.15953 10.6803 5.41073 10.4291C5.66193 10.1779 6.00263 10.0368 6.35788 10.0368C6.71313 10.0368 7.05383 10.1779 7.30503 10.4291C7.55623 10.6803 7.69735 11.021 7.69735 11.3763ZM13.0552 11.3763C13.0552 11.7315 12.9141 12.0722 12.6629 12.3234C12.4117 12.5746 12.071 12.7158 11.7158 12.7158C11.3605 12.7158 11.0198 12.5746 10.7686 12.3234C10.5174 12.0722 10.3763 11.7315 10.3763 11.3763C10.3763 11.021 10.5174 10.6803 10.7686 10.4291C11.0198 10.1779 11.3605 10.0368 11.7158 10.0368C12.071 10.0368 12.4117 10.1779 12.6629 10.4291C12.9141 10.6803 13.0552 11.021 13.0552 11.3763ZM17.0736 12.7158C17.4289 12.7158 17.7696 12.5746 18.0208 12.3234C18.272 12.0722 18.4131 11.7315 18.4131 11.3763C18.4131 11.021 18.272 10.6803 18.0208 10.4291C17.7696 10.1779 17.4289 10.0368 17.0736 10.0368C16.7184 10.0368 16.3777 10.1779 16.1265 10.4291C15.8753 10.6803 15.7342 11.021 15.7342 11.3763C15.7342 11.7315 15.8753 12.0722 16.1265 12.3234C16.3777 12.5746 16.7184 12.7158 17.0736 12.7158Z" fill="currentColor"/> </svg>';

    /**
     * Seat icon path used in the shared list remaining-seats row, copied
     * from the captured markup.
     */
    private const SEAT_USER_PATH = 'M15 5C16.3261 5 17.5979 5.52678 18.5355 6.46447C19.4732 7.40215 20 8.67392 20 10C20 11.3261 19.4732 12.5979 18.5355 13.5355C17.5979 14.4732 16.3261 15 15 15C13.6739 15 12.4021 14.4732 11.4645 13.5355C10.5268 12.5979 10 11.3261 10 10C10 8.67392 10.5268 7.40215 11.4645 6.46447C12.4021 5.52678 13.6739 5 15 5V5ZM15 17.5C20.525 17.5 25 19.7375 25 22.5V25H5V22.5C5 19.7375 9.475 17.5 15 17.5Z';

    /**
     * Render the brand detail page for any active brand, using the Amazon
     * capture as the template. Unknown or inactive slugs get a 404.
     * The coordinator wires GET /en/brand/{slug} to this method, before
     * the fallback route, so the existing /en/brand/amazon route keeps
     * working through show() and pageResponse().
     */
    public function showBrand(string $slug): Response
    {
        $brand = $this->findActiveBrand($slug);

        if ($brand === null) {
            abort(404);
        }

        $html = $this->readPage('en/brand/amazon.html');
        $html = $this->replaceCsrfTokens($html);
        $html = str_replace(
            'id="logout-form" action="#offline"',
            'id="logout-form" action="/en/logout"',
            $html,
        );
        $html = $this->applyAuthenticatedNavigation($html, 'en/brand/amazon.html');
        $html = $this->applyAuthenticatedUserData($html);
        $html = $this->applyGlobalContent($html, 'en/brand/amazon.html');
        $html = $this->applyBrandDetail($html, $brand);
        $html = $this->renderBrandDetailExtras($html, $brand);
        $html = $this->normalizePageLinks($html);

        return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    /**
     * Shared-subscription detail page (Raw Pages 2, spec section 3).
     * The offer is resolved by hash, falling back to the id for offers
     * seeded outside the wizard without a hash, and only for the
     * logged-in owner. The ?date=YYYY-MM-01 query picks the Payments
     * tab month; the #payments-tab anchor opens that tab on load
     * through the captured tab script.
     */
    public function showSharedOffer(string $identifier, Request $request): Response
    {
        $offer = $this->findOwnedSharedOffer($identifier);

        if ($offer === null) {
            abort(404);
        }

        $html = $this->renderSharedOfferDetail($offer, $this->sharedOfferDetailMonth($request));

        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    /**
     * Save the Login details tab: the sharing-method radios and the
     * shared or per-subscriber textareas, plus any stored credential
     * fields rendered on the page. Writes offers.secured_description
     * in the wizard's shape (one shared text, or one text per seat)
     * and offers.credentials as the ordered array the wizard stores.
     */
    public function saveSharedOfferCredentials(string $identifier, Request $request): RedirectResponse
    {
        $offer = $this->findOwnedSharedOffer($identifier);

        if ($offer === null) {
            abort(404);
        }

        $data = $request->validate([
            'secured_description_sharing_method' => ['required', 'in:0,1'],
            'individual_secured_description' => ['nullable', 'string'],
            'shared_secured_description' => ['nullable', 'array'],
            'shared_secured_description.*' => ['nullable', 'string'],
            'credentials' => ['nullable', 'array'],
            'credentials.*' => ['nullable', 'string'],
        ]);

        if ((string) $data['secured_description_sharing_method'] === '1') {
            $secured = array_values(array_filter(
                array_map(static fn ($value): string => trim((string) $value), (array) ($data['shared_secured_description'] ?? [])),
                static fn (string $value): bool => $value !== '',
            ));
        } else {
            $text = trim((string) ($data['individual_secured_description'] ?? ''));
            $secured = $text === '' ? [] : [$text];
        }

        $offer->secured_description = $secured === [] ? null : $secured;

        if (array_key_exists('credentials', $data)) {
            $credentials = array_values(array_filter(
                array_map(static fn ($value): string => trim((string) $value), (array) $data['credentials']),
                static fn (string $value): bool => $value !== '',
            ));
            $offer->credentials = $credentials === [] ? null : $credentials;
        }

        $offer->save();

        return redirect()->to($this->sharedOfferUrl($offer).'#identities-tab')
            ->with('status', 'Login details saved.');
    }

    /**
     * Mark a member's invitation email as sent (Raw Pages 2, spec 3.1
     * item 6). Sets invite_sent_at to now; the row then renders the
     * sent check instead of the button.
     */
    public function markEmailInviteSent(string $identifier, int $member): RedirectResponse
    {
        $offer = $this->findOwnedSharedOffer($identifier);

        if ($offer === null) {
            abort(404);
        }

        $row = OfferMember::where('offer_id', $offer->id)->where('id', $member)->first();

        if ($row === null) {
            abort(404);
        }

        $row->invite_sent_at = now();
        $row->save();

        return redirect()->to($this->sharedOfferUrl($offer).'#subscribers-tab')
            ->with('status', 'Invitation marked as sent.');
    }

    /**
     * Migrate a shared offer to another plan of the same brand (Raw
     * Pages 2, spec 3.1 item 8). The can-migrate flag is always allowed
     * in v1: no migration history exists to enforce the quarterly or
     * last-7-days rules from the copy, which is a recorded gap (G-T3).
     * The offer takes the new template's name, seats, price and
     * currency, mirroring the wizard's applyTemplate.
     */
    public function migrateSharedOffer(string $identifier, Request $request): RedirectResponse
    {
        $offer = $this->findOwnedSharedOffer($identifier);

        if ($offer === null) {
            abort(404);
        }

        $data = $request->validate([
            'template_id' => ['required', 'integer', 'exists:templates,id'],
        ]);

        $template = Template::where('id', $data['template_id'])
            ->where('brand_id', $offer->brand_id)
            ->where('is_active', true)
            ->first();

        if ($template === null) {
            return redirect()->to($this->sharedOfferUrl($offer).'#handler-tab')
                ->withErrors(['template_id' => 'Choose a plan from the same brand.']);
        }

        $offer->template_id = $template->id;
        $offer->name = $template->name;
        $offer->title = $template->name;
        $offer->description = $template->description;
        $offer->friends = $template->available_seats ?? $template->shareable_seats ?? $offer->friends;
        $offer->total_seats = $template->seats ?? $offer->total_seats;
        $offer->seats_available = $offer->friends;
        $offer->seats_total = $offer->total_seats;
        $offer->share = $template->cost_sharing ?? $offer->share ?? 1;
        $offer->frequency = $template->frequency ?? $offer->frequency;
        $offer->currency = $template->currency ?: $offer->currency;
        $offer->total_price = $template->retail_price ?? $template->price_with_fees ?? $offer->total_price;
        $offer->price = $this->migratedEachPays(
            (int) ($offer->share ?? 1),
            (float) ($offer->total_price ?? 0),
            (int) ($offer->friends ?? 1),
            (int) ($offer->total_seats ?? $offer->friends ?? 1),
        );
        $offer->save();

        return redirect()->to($this->sharedOfferUrl($offer).'#handler-tab')
            ->with('status', 'Subscription plan changed.');
    }

    /**
     * End a shared subscription (Raw Pages 2, spec 3.1 item 8). The
     * owner must type Delete, exactly as the capture instructs. The
     * offer becomes terminated (status terminated, inactive) and is
     * hidden from the shared list until the terminated toggle is
     * checked; its detail page stays reachable.
     */
    public function endSharedOffer(string $identifier, Request $request): RedirectResponse
    {
        $offer = $this->findOwnedSharedOffer($identifier);

        if ($offer === null) {
            abort(404);
        }

        $data = $request->validate([
            'confirmation' => ['required', 'string'],
        ]);

        if (trim((string) $data['confirmation']) !== 'Delete') {
            return redirect()->to($this->sharedOfferUrl($offer).'#handler-tab')
                ->withErrors(['confirmation' => 'Type "Delete" to cancel the subscription.']);
        }

        $offer->status = 'terminated';
        $offer->is_active = false;
        $offer->save();

        return redirect()->to('/en/my-shared-subscriptions')
            ->with('status', 'Subscription cancelled.');
    }

    /**
     * Offer hash for links to pages that use the captured 7-hex hash
     * shape (shared subscription detail, chat). Offers that carry no
     * stored hash get a stable derived placeholder in the same shape
     * until the data layer fills the column. The placeholder is always
     * resolved through findOwnedSharedOffer and resolveThreadMember,
     * never read back from the column.
     */
    protected function offerHash(Offer $offer): string
    {
        if (is_string($offer->hash) && $offer->hash !== '') {
            return $offer->hash;
        }

        return substr(md5('offer:'.$offer->id), 0, 7);
    }

    /**
     * Owner lookup for a detail URL identifier: a numeric identifier
     * is an offer id first (falling back to a hash, in case a hash is
     * all digits), anything else is a stored hash, then a derived
     * placeholder hash.
     */
    protected function findOwnedSharedOffer(string $identifier): ?Offer
    {
        if (! Auth::check()) {
            return null;
        }

        try {
            if (! class_exists(Offer::class) || ! Schema::hasTable('offers')) {
                return null;
            }

            $query = Offer::where('user_id', Auth::id());

            if (ctype_digit($identifier)) {
                $byId = (clone $query)->where('id', (int) $identifier)->first();

                if ($byId !== null) {
                    return $byId;
                }
            }

            $byHash = (clone $query)->where('hash', $identifier)->first();

            if ($byHash !== null) {
                return $byHash;
            }

            return $query->get()->first(fn (Offer $offer): bool => $this->offerHash($offer) === $identifier);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Payments tab month: ?date=YYYY-MM-01, first of the month, or the
     * current month when the parameter is missing or unparsable.
     */
    protected function sharedOfferDetailMonth(Request $request): Carbon
    {
        $date = $request->query('date');

        if (is_string($date) && $date !== '') {
            try {
                return Carbon::createFromFormat('Y-m-d', $date)->startOfMonth();
            } catch (\Throwable) {
                // Fall through to the current month.
            }
        }

        return now()->startOfMonth();
    }

    /**
     * Per-seat price for a migrated offer, the same division the
     * wizard's eachPays uses: share 1 divides by total seats, share 3
     * by co-subscribers, anything else by co-subscribers plus one.
     */
    protected function migratedEachPays(int $share, float $total, int $friends, int $totalSeats): float
    {
        if ($total <= 0) {
            return 0.0;
        }

        if ($share === 1) {
            return round($total / max(1, $totalSeats), 2);
        }

        if ($share === 3) {
            return round($total / max(1, $friends), 2);
        }

        return round($total / max(1, $friends + 1), 2);
    }

    /**
     * Render the detail page on the my-shared-subscriptions captured
     * shell: same header, sidebar, mobile sheet and footer, with the
     * main column replaced by the detail view, the title set to the
     * offer name, and the Back link injected into the header.
     */
    protected function renderSharedOfferDetail(mixed $offer, Carbon $month): string
    {
        $html = $this->readPage('en/my-shared-subscriptions.html');
        $html = $this->replaceCsrfTokens($html);
        $html = str_replace(
            'id="logout-form" action="#offline"',
            'id="logout-form" action="/en/logout"',
            $html,
        );

        $html = $this->applyAuthenticatedNavigation($html, 'en/my-shared-subscriptions.html');
        $html = $this->applyAuthenticatedUserData($html);
        $html = $this->applySiteChrome($html, 'en/my-shared-subscriptions.html');
        $html = $this->replaceJsonLd($html);
        $html = $this->replaceCatalogueCount($html);
        $html = $this->replaceHeroCountries($html);
        $html = $this->replaceBrandFilterSelect($html);
        $html = $this->replaceSavingCalculator($html);
        $html = $this->hideInputArrowControls($html);

        $name = $this->sharedOfferName($offer);
        $html = preg_replace(
            '/<title>[^<]*<\/title>/i',
            '<title>'.e($name).'</title>',
            $html,
            1,
        ) ?? $html;

        $backLink = '<a href="/en/my-shared-subscriptions" class="dashboard-header-actions-back">'."\n"
            .self::CATALOGUE_ARROW_SVG."\n"
            .'Back    </a>';
        $html = preg_replace(
            '/(<div class="dashboard-header-actions-left">)\s*(<\/div>)/',
            '$1'."\n".$backLink."\n".'$2',
            $html,
            1,
        ) ?? $html;

        $detail = view('user.shared-offer-detail', $this->sharedOfferDetailData($offer, $month))->render();

        $html = preg_replace_callback(
            '/(<div class="col-12 col-lg-8">\s*<div class="subscriptions">).*?(<\/div>\s*<\/div>\s*<\/div>\s*<\/div>\s*<div class="dash-sheet-backdrop)/s',
            static fn (array $matches): string => $matches[1]."\n".$detail."\n".$matches[2],
            $html,
            1,
        ) ?? $html;

        return $this->normalizePageLinks($html);
    }

    /**
     * View data for the detail page. Members are the current
     * co-subscribers (owner excluded) and the previous members; payment
     * rows are the offer_payments for the requested month. The Login
     * details tab reads the wizard's stored shapes: secured_description
     * as one shared text or one text per seat, credentials as the
     * ordered array the wizard stores, labelled by the template's
     * credentials type.
     *
     * @return array<string, mixed>
     */
    protected function sharedOfferDetailData(mixed $offer, Carbon $month): array
    {
        $brand = null;
        try {
            $brand = $offer->brand;
        } catch (\Throwable) {
            $brand = null;
        }

        $symbol = Currency::symbolFor($offer->currency ?? $brand?->currency ?? null);
        if ($symbol === null || $symbol === '') {
            $symbol = $brand !== null ? (string) ($brand->currency_symbol ?? '') : '';
        }
        if ($symbol === '') {
            $symbol = Currency::defaultSymbol() ?? '';
        }

        $currentMembers = $this->sharedOfferMembers($offer, OfferMember::STATUS_CURRENT);
        $previousMembers = $this->sharedOfferMembers($offer, OfferMember::STATUS_PREVIOUS);
        $subscriberCount = $currentMembers->count();
        $seatsTotal = max(0, (int) ($offer->friends ?? 0));

        [$incomeInt, $incomeDec] = $this->splitPriceParts($this->sharedOfferTotalPrice($offer), $symbol);

        $payments = collect();
        $paymentsTotal = $this->formatPriceAmount(0, $symbol);
        try {
            if (class_exists(OfferPayment::class) && Schema::hasTable('offer_payments')) {
                $payments = OfferPayment::with('member')
                    ->where('offer_id', $offer->id)
                    ->whereDate('month', $month->toDateString())
                    ->orderBy('member_id')
                    ->get()
                    ->map(function (OfferPayment $payment) use ($symbol): OfferPayment {
                        $payment->statusLabel = match ($payment->status) {
                            OfferPayment::STATUS_PAID => 'Paid - available end of period',
                            OfferPayment::STATUS_LATE => 'Late',
                            default => 'Pending',
                        };
                        $payment->amountFormatted = $this->formatPriceAmount((float) $payment->amount, $symbol);

                        return $payment;
                    });
                $paymentsTotal = $this->formatPriceAmount((float) $payments->sum('amount'), $symbol);
            }
        } catch (\Throwable) {
            $payments = collect();
        }

        $secured = is_array($offer->secured_description) ? array_values($offer->secured_description) : [];
        $securedMethod = count($secured) > 1 ? 1 : 0;
        $securedShared = (string) ($secured[0] ?? '');
        $securedIndividual = $secured;
        while (count($securedIndividual) < max(1, $subscriberCount)) {
            $securedIndividual[] = '';
        }

        $templates = collect();
        try {
            if ($offer->brand_id !== null && class_exists(Template::class) && Schema::hasTable('templates')) {
                $templates = Template::where('brand_id', $offer->brand_id)
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get()
                    ->map(function (Template $template) use ($symbol): Template {
                        $template->priceLabel = $this->formatPriceAmount(
                            (float) ($template->retail_price ?? $template->price_with_fees ?? 0),
                            Currency::symbolFor($template->currency ?? null) ?? $symbol,
                        );

                        return $template;
                    });
            }
        } catch (\Throwable) {
            $templates = collect();
        }

        return [
            'offer' => $offer,
            'identifier' => $this->offerHash($offer),
            'hasChatThread' => $this->sharedOfferHasChatThread($offer),
            'detailUrl' => $this->sharedOfferUrl($offer),
            'name' => $this->sharedOfferName($offer),
            'visibilityLabel' => $this->sharedOfferVisibilityLabel($offer),
            'createdOn' => $offer->created_at !== null ? $offer->created_at->format('Y/m/d') : '',
            'brandName' => $brand !== null ? (string) ($brand->name ?? '') : '',
            'incomeInt' => $incomeInt,
            'incomeDec' => $incomeDec,
            'perSubscriber' => $this->formatPriceAmount((float) ($offer->price ?? 0), $symbol),
            'subscriberCount' => $subscriberCount,
            'seatsTotal' => $seatsTotal,
            'currentMembers' => $currentMembers,
            'previousMembers' => $previousMembers,
            'month' => $month,
            'prevMonth' => $month->copy()->subMonth(),
            'nextMonth' => $month->copy()->addMonth(),
            'monthLabel' => $month->format('F Y'),
            'payments' => $payments,
            'paymentsTotal' => $paymentsTotal,
            'templates' => $templates,
            'securedMethod' => $securedMethod,
            'securedShared' => $securedShared,
            'securedIndividual' => $securedIndividual,
            'credentials' => is_array($offer->credentials) ? array_values($offer->credentials) : null,
            'credentialLabels' => $this->credentialLabelsFor($offer),
            'status' => session('status'),
        ];
    }

    /**
     * Current or previous members of an offer, excluding the owner
     * member row, oldest first.
     */
    protected function sharedOfferMembers(mixed $offer, string $status): mixed
    {
        try {
            if (! class_exists(OfferMember::class) || ! Schema::hasTable('offer_members')) {
                return collect();
            }

            return OfferMember::where('offer_id', $offer->id)
                ->where('status', $status)
                ->where(function ($query) use ($offer): void {
                    $query->whereNull('user_id')->orWhere('user_id', '!=', $offer->user_id);
                })
                ->orderBy('joined_at')
                ->orderBy('id')
                ->get();
        } catch (\Throwable) {
            return collect();
        }
    }

    /**
     * Labels for the stored credential values, from the template's
     * credentials type, the same types the wizard validates: login and
     * password, email and password, link, or link and address. An
     * offer with no template and no credentials type renders no
     * credential fields.
     *
     * @return array<int, string>
     */
    protected function credentialLabelsFor(mixed $offer): array
    {
        $type = 0;
        try {
            $type = (int) ($offer->template?->credentials_type ?? 0);
        } catch (\Throwable) {
            $type = 0;
        }

        return match ($type) {
            1 => ['Login', 'Password'],
            2, 6 => ['Email', 'Password'],
            3 => ['Link'],
            4 => ['Link', 'Address'],
            default => [],
        };
    }

    /**
     * The captured site header, taken from the homepage template so the
     * wizard layout renders the exact same markup as the captured
     * pages, with the same authenticated navigation and the same shared
     * chrome replacements. Empty when the markup cannot be extracted,
     * so the layout can keep its own header as a fallback.
     */
    public function siteHeaderHtml(): string
    {
        try {
            $html = $this->readPage('en/index.html');

            if (! preg_match('/<nav\b[^>]*class="[^"]*sharesub-navbar[^"]*"[^>]*>.*?<\/nav>/s', $html, $match)) {
                return '';
            }

            $nav = $match[0];
            $nav = $this->applyAuthenticatedNavigation($nav, 'en/index.html');
            $nav = $this->applyAuthenticatedUserData($nav);
            $nav = $this->applySiteChrome($nav, 'en/index.html');
            $nav = $this->normalizePageLinks($nav);

            return $nav;
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * The captured site footer, taken from the homepage template so the
     * wizard layout renders the exact same markup as the captured
     * pages, with the same shared chrome replacements. The hidden
     * logout form is appended for signed in users, matching the
     * captured pages. Empty when the markup cannot be extracted.
     */
    public function siteFooterHtml(): string
    {
        try {
            $html = $this->readPage('en/index.html');

            if (! preg_match('/<footer\b[^>]*>.*?<\/footer>/s', $html, $match)) {
                return '';
            }

            $footer = $match[0];
            $footer = $this->applySiteChrome($footer, 'en/index.html');
            $footer = $this->normalizePageLinks($footer);

            if (Auth::check() && ! str_contains($footer, 'id="logout-form"')) {
                $footer .= '<form id="logout-form" action="/en/logout" method="POST" style="display:none;">'
                    .'<input type="hidden" name="_token" value="'.csrf_token().'">'
                    .'</form>';
            }

            return $footer;
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * Replace captured homepage, catalogue, house and brand detail markup
     * with database content. Only the four public pages listed here are
     * ever altered, so admin dashboard pages are untouched. A section
     * keeps the captured HTML only when its table has no rows at all
     * (never seeded). When rows exist but none are active, the section
     * renders empty instead, so deactivated content never comes back
     * as captured HTML. Every setting keeps its captured piece when
     * its key is missing. A database error never breaks a public page:
     * the captured HTML is returned unchanged.
     */
    protected function applyDynamicContent(string $html, string $page): string
    {
        if (! in_array($page, ['en/index.html', 'en/subscriptions.html', 'en/subscriptions/house.html', 'en/brand/amazon.html'], true)) {
            return $html;
        }

        if (! $this->dynamicContentReady()) {
            return $html;
        }

        $original = $html;

        try {
            $html = $this->replaceHeroText($html);
            $html = $this->replaceSearchPlaceholder($html);
            $html = $this->replaceCategoryPills($html);
            $html = $this->replaceFeatures($html);

            if ($page === 'en/index.html') {
                $html = $this->replaceHeroCarousel($html);
                $html = $this->replacePressItems($html);
                $html = $this->replaceFaqs($html);
                $html = $this->replaceTestimonials($html);
                $html = $this->replaceShareSection($html);
                $html = $this->replaceFooterNote($html);
                $html = $this->replaceCtaBlocks($html);
                $html = $this->replaceHeroRating($html);
                $html = $this->replaceTestimonialsHeading($html);
                $html = $this->replaceFaqSectionTitle($html);
                $html = $this->replacePressSectionTitle($html);
                $html = $this->replaceHowItWorks($html);
            } elseif ($page === 'en/subscriptions.html') {
                $html = $this->replaceCatalogueBlocks($html);
            } elseif ($page === 'en/subscriptions/house.html') {
                $html = $this->replaceHouseCards($html);
            } elseif ($page === 'en/brand/amazon.html') {
                $html = $this->applyBrandDetail($html, $this->findActiveBrand('amazon'));
            }
        } catch (\Throwable) {
            return $original;
        }

        return $html;
    }

    /**
     * True when every content model and table the frontend reads exists.
     * Lets the public pages keep serving the captured HTML before the
     * content migration has run.
     */
    protected function dynamicContentReady(): bool
    {
        if ($this->dynamicReady !== null) {
            return $this->dynamicReady;
        }

        $this->dynamicReady = false;

        try {
            foreach ([Brand::class, Category::class, SiteSetting::class, Faq::class, Testimonial::class, PressItem::class, FooterLinkGroup::class, Feature::class] as $model) {
                if (! class_exists($model)) {
                    return false;
                }
            }

            foreach (['brands', 'categories', 'site_settings', 'faqs', 'testimonials', 'press_items', 'footer_link_groups', 'footer_links', 'features'] as $table) {
                if (! Schema::hasTable($table)) {
                    return false;
                }
            }

            $this->dynamicReady = true;
        } catch (\Throwable) {
            $this->dynamicReady = false;
        }

        return $this->dynamicReady;
    }


    protected function findActiveBrand(string $slug): mixed
    {
        if (! $this->dynamicContentReady()) {
            return null;
        }

        try {
            return Brand::where('slug', $slug)->where('is_active', true)->first();
        } catch (\Throwable) {
            return null;
        }
    }



    protected function activeCategories(): mixed
    {
        try {
            return Category::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();
        } catch (\Throwable) {
            return collect();
        }
    }

    protected function allCategoriesByName(): array
    {
        try {
            $byName = [];
            foreach (Category::orderBy('sort_order')->orderBy('id')->get() as $category) {
                $byName[strtolower(trim((string) $category->name))] = $category;
            }

            return $byName;
        } catch (\Throwable) {
            return [];
        }
    }

    protected function featuredBrands(): mixed
    {
        try {
            return Brand::where('is_active', true)->where('is_featured', true)->orderBy('sort_order')->orderBy('id')->get();
        } catch (\Throwable) {
            return collect();
        }
    }

    protected function activeBrandsForCategory(int $categoryId): mixed
    {
        try {
            return Brand::where('is_active', true)->where('category_id', $categoryId)->orderBy('sort_order')->orderBy('id')->get();
        } catch (\Throwable) {
            return collect();
        }
    }

    protected function brandUrl(mixed $brand): string
    {
        return '/en/brand/'.(string) $brand->slug.'.html';
    }

    protected function formatBrandPrice(mixed $brand): string
    {
        return $this->formatPriceAmount((float) $brand->price, $this->brandCurrencySymbol($brand));
    }

    protected function formatOfferPrice(mixed $offer, mixed $brand): string
    {
        return $this->formatPriceAmount((float) $offer->price, $this->brandCurrencySymbol($brand, $offer->currency ?? null));
    }

    /**
     * Price symbol for a brand: the currencies table by currency code
     * first, so changing a currency's symbol in admin changes every
     * rendered price; the stored brand symbol is only a fallback for a
     * code with no currency row.
     */
    protected function brandCurrencySymbol(mixed $brand, ?string $code = null): ?string
    {
        return Currency::symbolFor($code ?? $brand->currency ?? null)
            ?? Currency::symbolFor($brand->currency ?? null)
            ?? ($brand->currency_symbol ?? null);
    }

    /**
     * Replace a captured price token (any currency symbol from the
     * currencies table followed by an amount and "/month") in captured
     * copy with the computed price. The symbol alternation comes from
     * the table, so no currency literal is needed and the replacement
     * still matches the captured copy after an admin changes a symbol.
     */
    protected function replaceCapturedPrice(string $content, string $headerPrice): string
    {
        try {
            $symbols = Currency::query()->pluck('symbol')->all();
        } catch (Throwable) {
            $symbols = [];
        }

        $symbols = array_values(array_unique(array_filter(
            array_map(static fn ($symbol): string => (string) $symbol, $symbols),
            static fn (string $symbol): bool => $symbol !== '',
        )));

        if ($symbols === []) {
            return $content;
        }

        usort($symbols, static fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));
        $alternation = implode('|', array_map(static fn (string $symbol): string => preg_quote($symbol, '/'), $symbols));

        return preg_replace_callback(
            '/(?:'.$alternation.')\s?\d+(?:\.\d+)?\/month/u',
            static fn (): string => $headerPrice,
            $content,
        ) ?? $content;
    }

    protected function formatPriceAmount(float $amount, ?string $symbol): string
    {
        $number = number_format($amount, 2, '.', '');
        $number = rtrim(rtrim($number, '0'), '.');

        return (string) ($symbol ?: '').$number;
    }

    protected function brandBackground(mixed $brand): string
    {
        return (string) ($brand->background ?: '#112e45');
    }

    /**
     * Public storage URL for an uploaded file path. Paths that already
     * start with / or http are used as they are.
     */
    protected function storageUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (str_starts_with($path, '/') || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return '/storage/'.ltrim($path, '/');
    }

    /**
     * Brand logo or icon markup. logo_path wins over icon_svg. The icon
     * SVG is admin supplied inline SVG, rendered as stored, like the
     * captured markup does.
     */
    protected function brandIconHtml(mixed $brand): string
    {
        $logo = $this->storageUrl($brand->logo_path ?? null);

        if ($logo !== null) {
            return '<img src="'.e($logo).'" alt="'.e((string) $brand->name).'" loading="lazy">';
        }

        return (string) ($brand->icon_svg ?? '');
    }

    /**
     * The one shared brand card renderer, used by the homepage carousel,
     * the catalogue list and the house grid. Variants differ only in the
     * wrapping markup, which matches the capture for each place.
     */
    protected function renderBrandCard(mixed $brand, string $variant): string
    {
        $name = e((string) $brand->name);
        $url = e($this->brandUrl($brand));
        $price = e($this->formatBrandPrice($brand));

        if ($variant === 'catalogue') {
            return '<li class="category-card-item" linkable>'
                .'<a href="'.$url.'" class="category-card-item-name" linkable-target>'."\n".$name."\n".'</a>'
                .'<div class="category-card-item-price">'."\n".$price."\n".self::CATALOGUE_ARROW_SVG."\n".'</div>'
                .'</li>'."\n";
        }

        if ($variant === 'grid') {
            return '<div class="col-6 col-md-4 col-lg-3">'."\n"
                .'<div class="brand-card" linkable>'."\n"
                .'<div class="brand-card-title" style="background: '.e($this->brandBackground($brand)).'">'."\n"
                .'<a href="'.$url.'" linkable-target>'.$name.'</a>'."\n"
                .self::CATALOGUE_ARROW_SVG."\n"
                .'</div>'."\n"
                .'<div class="brand-card-wrapper">'."\n"
                .'<div class="brand-card-price">'.$price.'</div>'."\n"
                .'<div class="brand-card-frequency">/ month</div>'."\n"
                .'</div>'."\n"
                .'</div>'."\n"
                .'</div>'."\n";
        }

        $slide = '<div class="hero-slides-item splide__slide" style="background: '.e($this->brandBackground($brand)).'" linkable>'."\n"
            .'<div class="hero-slides-item-icon">'."\n"
            .$this->brandIconHtml($brand)."\n"
            .'</div>'."\n"
            .'<a href="'.$url.'" class="hero-slides-item-title" linkable-target>'.$name.'</a>'."\n"
            .'<hr class="hero-slides-item-separation">'."\n"
            .'<div class="hero-slides-item-price">'.$price.'</div>'."\n"
            .'<div class="hero-slides-item-frequency">/ month</div>'."\n";

        if ($brand->discount_percent !== null) {
            $slide .= '<div class="hero-slides-item-participation">'."\n"
                .'Participate in -'.e((string) $brand->discount_percent).' % <img src="/img/dashboard/cards/next.svg" alt="">'."\n"
                .'</div>'."\n";
        }

        return $slide.'</div>'."\n";
    }

    /**
     * Replace the saving calculator props on the homepage with templates
     * from the database, in the same shape as the capture: each brand
     * with its lowest price template, and create and listing URLs that
     * point to the internal brand page. The default brand resolves from
     * the calculator_default_brand_slug setting to that brand's first
     * template id, falling back to the first featured brand, and the
     * average yearly savings comes from the
     * calculator_avg_yearly_savings setting. The captured JSON is kept
     * only when the templates table has no rows at all. When templates
     * exist but none are active, the brands prop renders empty.
     */
    protected function replaceSavingCalculator(string $html): string
    {
        if (! str_contains($html, '<saving-calculator')) {
            return $html;
        }

        try {
            if (! class_exists(Template::class) || ! Schema::hasTable('templates')) {
                return $html;
            }

            $brands = $this->calculatorBrands();

            if ($brands->isEmpty()) {
                return $this->tableHasRows('templates')
                    ? $this->emptyCalculatorBrands($html)
                    : $html;
            }

            $payload = [];

            foreach ($brands as $brand) {
                $template = $brand->lowestTemplate;
                if ($template === null) {
                    continue;
                }

                $payload[] = $this->calculatorBrandPayload($brand, $template);
            }

            if ($payload === []) {
                return $this->tableHasRows('templates')
                    ? $this->emptyCalculatorBrands($html)
                    : $html;
            }

            $json = json_encode($payload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

            if ($json === false) {
                return $html;
            }

            $html = preg_replace_callback(
                '/:brands=\'[^\']*\'/',
                static fn (array $matches): string => ':brands=\''.$json.'\'',
                $html,
                1,
            ) ?? $html;

            $defaultTemplateId = $this->calculatorDefaultTemplateId($brands);

            if ($defaultTemplateId !== null) {
                $html = preg_replace_callback(
                    '/:default-brand=\'[^\']*\'/',
                    static fn (array $matches): string => ':default-brand=\''.$defaultTemplateId.'\'',
                    $html,
                    1,
                ) ?? $html;
            }

            $savings = $this->calculatorSetting('calculator_avg_yearly_savings');

            if ($savings !== null) {
                $html = preg_replace_callback(
                    '/:avg-yearly-savings=\'[^\']*\'/',
                    static fn (array $matches): string => ':avg-yearly-savings=\''.e($savings).'\'',
                    $html,
                    1,
                ) ?? $html;
            }
        } catch (\Throwable) {
            return $html;
        }

        return $html;
    }

    /**
     * Render the saving calculator with no brands. The Vue component
     * picks no default brand when the list is empty, so a stale
     * default brand prop is harmless and is left as captured.
     */
    protected function emptyCalculatorBrands(string $html): string
    {
        return preg_replace_callback(
            '/:brands=\'[^\']*\'/',
            static fn (array $matches): string => ':brands=\'[]\'',
            $html,
            1,
        ) ?? $html;
    }

    /**
     * Active brands that have at least one active template, each with
     * its lowest price template attached.
     */
    protected function calculatorBrands(): mixed
    {
        try {
            $brands = Brand::where('is_active', true)
                ->whereHas('templates', function ($query): void {
                    $query->where('is_active', true);
                })
                ->orderBy('sort_order')->orderBy('id')->get();

            foreach ($brands as $brand) {
                $brand->lowestTemplate = Template::where('brand_id', $brand->id)
                    ->where('is_active', true)
                    ->orderBy('price_with_fees')->orderBy('id')->first();
            }

            return $brands;
        } catch (\Throwable) {
            return collect();
        }
    }

    /** @return array<string, mixed> */
    protected function calculatorBrandPayload(mixed $brand, mixed $template): array
    {
        $brandUrl = '/en/brand/'.(string) $brand->slug.'?template='.(string) $template->slug;
        $createUrl = '/en/create-subscription?template='.(string) $template->slug.'&country=united-kingdom';
        $retail = $template->retail_price !== null ? number_format((float) $template->retail_price, 2, '.', '') : null;
        // The calculator's price row currency comes from the currencies
        // table (the template currency, falling back to the default row),
        // never a hardcoded numeric id.
        $currencyId = Currency::isoNumericForPayload($template->currency);

        return [
            'id' => (int) $brand->id,
            'name' => (string) $brand->name,
            'background' => $this->brandBackground($brand),
            'slug' => (string) $brand->slug,
            'lowest_price_template' => [
                'id' => (int) $template->id,
                'brand_id' => (int) $brand->id,
                'seats' => $template->seats !== null ? (int) $template->seats : null,
                'shareable_seats' => $template->shareable_seats !== null ? (int) $template->shareable_seats : null,
                'available_seats' => $template->available_seats !== null ? (int) $template->available_seats : null,
                // The Vue calculator reads these three fields: frequency
                // picks the period label, cost_sharing feeds the share
                // method, credentials_type drives the credentials step.
                // Frequency defaults to 2, matching the captured data.
                'cost_sharing' => (int) ($template->cost_sharing ?? 1),
                'credentials_type' => (int) ($template->credentials_type ?? 0),
                'frequency' => $template->frequency !== null ? (int) $template->frequency : 2,
                'create_url' => $createUrl,
                'listing_url' => $brandUrl,
                'current_country_price_with_fees' => (float) $template->price_with_fees,
                'name' => (string) $template->name,
                'slug' => (string) $template->slug,
                'description' => (string) ($template->description ?? ''),
                'description_seo' => (string) ($template->description_seo ?? ''),
                'title_seo' => (string) ($template->title_seo ?? ''),
                'helper_create' => (string) ($template->helper_create ?? ''),
                'helper_join' => (string) ($template->helper_join ?? ''),
                'prices' => [[
                    'template_id' => (int) $template->id,
                    'country_id' => 237,
                    'price' => $retail,
                    'retail_price' => $retail,
                    'currency_id' => $currencyId,
                    'fee_boost' => null,
                    'seats' => null,
                    'shareable_seats' => null,
                    'available_seats' => null,
                    'visibility' => 7,
                ]],
            ],
            'description_seo' => (string) ($brand->short_description ?? ''),
            'title_seo' => 'Share my '.(string) $brand->name.' subscription on Sharesub',
        ];
    }

    protected function calculatorDefaultTemplateId(mixed $brands): ?int
    {
        $slug = $this->calculatorSetting('calculator_default_brand_slug') ?? 'netflix';

        $brand = $brands->firstWhere('slug', $slug);

        if ($brand === null || $brand->lowestTemplate === null) {
            $brand = $this->featuredBrands()->first(function ($featured) use ($brands): bool {
                return $brands->contains('id', $featured->id);
            });

            if ($brand !== null) {
                $brand = $brands->firstWhere('id', $brand->id);
            }
        }

        if ($brand === null) {
            $brand = $brands->first();
        }

        return $brand !== null && $brand->lowestTemplate !== null ? (int) $brand->lowestTemplate->id : null;
    }

    protected function calculatorSetting(string $key): ?string
    {
        try {
            if (! Schema::hasTable('site_settings')) {
                return null;
            }

            $value = SiteSetting::where('key', $key)->value('value');
        } catch (\Throwable) {
            return null;
        }

        return $value === null || $value === '' ? null : (string) $value;
    }

    protected function replaceHeroText(string $html): string
    {
        $highlight = $this->dynamicSetting('hero_highlight');
        $text = $this->dynamicSetting('hero_text');
        $underline = $this->dynamicSetting('hero_underline');

        if ($highlight === null && $text === null && $underline === null) {
            return $html;
        }

        return preg_replace_callback(
            '/(<h1 class="hero-title">\s*<span>)([^<]*)(<\/span>)([^<]*)(<u>)([^<]*)(<\/u>)/s',
            static function (array $matches) use ($highlight, $text, $underline): string {
                $middle = $text !== null ? ' '.e($text).' ' : $matches[4];

                return $matches[1].e($highlight ?? $matches[2]).$matches[3].$middle.e($underline ?? $matches[6]).$matches[7];
            },
            $html,
            1,
        ) ?? $html;
    }

    /**
     * hero_search_placeholder has no placeholder attribute in the capture:
     * the hero-search component renders the input. The value is applied
     * as a placeholder attribute on the component tag, and kept out when
     * the key is missing.
     */
    protected function replaceSearchPlaceholder(string $html): string
    {
        $placeholder = $this->dynamicSetting('hero_search_placeholder');

        if ($placeholder === null) {
            return $html;
        }

        return preg_replace_callback(
            '/<hero-search\b[^>]*>/s',
            static function (array $matches) use ($placeholder): string {
                $tag = preg_replace('/\splaceholder="[^"]*"/i', '', $matches[0]) ?? $matches[0];

                return substr($tag, 0, -1).' placeholder="'.e($placeholder).'">';
            },
            $html,
        ) ?? $html;
    }

    protected function renderCategoryPill(mixed $category): string
    {
        $href = $category->slug === 'house' ? '/en/subscriptions/house.html' : '#offline';
        $pill = '<span class="hero-categories-item splide__slide" linkable>'."\n";

        if (! empty($category->icon_svg)) {
            $pill .= '<span>'."\n".(string) $category->icon_svg."\n".'</span>'."\n";
        }

        return $pill.'<a href="'.$href.'" linkable-target>'."\n".e((string) $category->name)."\n".'</a>'."\n".'</span>'."\n";
    }

    protected function replaceCategoryPills(string $html): string
    {
        if (! str_contains($html, 'hero-categories-items')) {
            return $html;
        }

        $categories = $this->activeCategories();

        if ($categories->isEmpty() && ! $this->tableHasRows('categories')) {
            return $html;
        }

        $pills = '';
        foreach ($categories as $category) {
            $pills .= $this->renderCategoryPill($category);
        }

        return preg_replace_callback(
            '/(<div class="splide__list hero-categories-items">)(.*?)(<\/div>\s*<\/div>\s*<\/div>\s*<\/div>\s*<div class="hero-search-wrapper">)/s',
            static fn (array $matches): string => $matches[1]."\n".$pills.$matches[3],
            $html,
            1,
        ) ?? $html;
    }

    protected function replaceHeroCarousel(string $html): string
    {
        $brands = $this->featuredBrands();

        if ($brands->isEmpty() && ! $this->tableHasRows('brands')) {
            return $html;
        }

        $slides = '';
        foreach ($brands as $brand) {
            $slides .= $this->renderBrandCard($brand, 'hero');
        }

        return preg_replace_callback(
            '/(<div class="splide__list hero-slides">)(.*?)(<\/div>\s*<\/div>\s*<\/div>\s*<\/div>\s*<div class="container">)/s',
            static fn (array $matches): string => $matches[1]."\n".$slides.$matches[3],
            $html,
            1,
        ) ?? $html;
    }

    protected function replacePressItems(string $html): string
    {
        if (! str_contains($html, 'press-items')) {
            return $html;
        }

        try {
            $items = PressItem::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();
        } catch (\Throwable) {
            return $html;
        }

        if ($items->isEmpty() && ! $this->tableHasRows('press_items')) {
            return $html;
        }

        $slides = '';
        foreach ($items as $item) {
            $href = ! empty($item->link_url) && $item->link_url !== '#' ? e((string) $item->link_url) : '#offline';
            $logo = $this->storageUrl($item->logo_path ?? null);
            if ($logo !== null) {
                $inner = '<img src="'.e($logo).'" loading="lazy" alt="'.e((string) $item->outlet_name).'" />';
            } elseif (! empty($item->logo_text)) {
                $inner = e((string) $item->logo_text);
            } else {
                $inner = e((string) $item->outlet_name);
            }
            $slides .= '<div class="press-item splide__slide">'."\n"
                .'<a href="'.$href.'" class="col-6 col-md-3" target="_blank">'.$inner.'</a>'."\n"
                .'</div>'."\n";
        }

        return preg_replace_callback(
            '/(<div class="splide__list press-items">)(.*?)(<!-- \/Médias -->)/s',
            static fn (array $matches): string => $matches[1]."\n".$slides.$matches[3],
            $html,
            1,
        ) ?? $html;
    }

    protected function replaceFeatures(string $html): string
    {
        if (! str_contains($html, 'feature-section-items')) {
            return $html;
        }

        try {
            $features = Feature::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();
        } catch (\Throwable) {
            return $html;
        }

        if ($features->isEmpty() && ! $this->tableHasRows('features')) {
            return $html;
        }

        $title = $this->dynamicSetting('features_title');
        if ($title !== null) {
            $html = preg_replace_callback(
                '/(<section class="feature-section" id="features-section">\s*<div class="container">\s*<h3>)[^<]*(<\/h3>)/s',
                static fn (array $matches): string => $matches[1].e($title).$matches[2],
                $html,
                1,
            ) ?? $html;
        }

        $items = '';
        foreach ($features as $index => $feature) {
            $color = $index % 2 === 0 ? 'blue' : 'green';
            $icon = '';
            if (! empty($feature->icon_svg)) {
                $icon = (string) $feature->icon_svg;
            } elseif (($iconPath = $this->storageUrl($feature->icon_path ?? null)) !== null) {
                $icon = '<img src="'.e($iconPath).'" loading="lazy">';
            }
            $link = ! empty($feature->link_url) && $feature->link_url !== '#' ? (string) $feature->link_url : '#offline';
            $readMore = $this->dynamicSetting('features_read_more_label') ?? 'Read More';
            $items .= '<div class="feature-section-item col-12 col-md-6 col-lg-3">'."\n"
                .'<div class="img-wrapper">'."\n".$icon."\n".'</div>'."\n"
                .'<div class="title">'.e((string) $feature->title).'</div>'."\n"
                .'<p class="content">'.nl2br(e((string) ($feature->text ?? ''))).'</p>'."\n"
                .'<a href="'.e($link).'" class="link text-'.$color.'">'."\n"
                .'<span>'.e($readMore).'</span>'."\n"
                .'<img src="/img/dashboard/features/next-'.$color.'.svg" alt="">'."\n"
                .'</a>'."\n"
                .'</div>'."\n";
        }

        return preg_replace_callback(
            '/(<div class="feature-section-items row">)(.*?)(<\/div>\s*<\/div>\s*<\/div>\s*<\/section>)/s',
            static fn (array $matches): string => $matches[1]."\n".$items.$matches[3],
            $html,
            1,
        ) ?? $html;
    }

    protected function replaceFaqs(string $html): string
    {
        if (! str_contains($html, 'faq-items')) {
            return $html;
        }

        try {
            $faqs = Faq::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();
        } catch (\Throwable) {
            return $html;
        }

        if ($faqs->isEmpty() && ! $this->tableHasRows('faqs')) {
            return $html;
        }

        $items = '';
        foreach ($faqs as $faq) {
            $items .= '<section class="faq-item active" toggle>'."\n"
                .'<h4 class="faq-item-title" toggle-target>'."\n"
                .e((string) $faq->question).' </h4>'."\n"
                .'<div class="faq-item-content">'."\n"
                .'<div class="p2-regular">'.nl2br(e((string) $faq->answer)).'</div>'."\n"
                .'</div>'."\n"
                .'</section>'."\n";
        }

        return preg_replace_callback(
            '/(<div class="faq-items">)(.*?)(<\/div>\s*<\/div>\s*<\/section>\s*<section class="testimonials-section")/s',
            static fn (array $matches): string => $matches[1]."\n".$items.$matches[3],
            $html,
            1,
        ) ?? $html;
    }

    protected function replaceTestimonials(string $html): string
    {
        if (! str_contains($html, 'testimonials-items')) {
            return $html;
        }

        try {
            $testimonials = Testimonial::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();
        } catch (\Throwable) {
            return $html;
        }

        if ($testimonials->isEmpty() && ! $this->tableHasRows('testimonials')) {
            return $html;
        }

        $slides = '';
        foreach ($testimonials as $testimonial) {
            $avatar = $this->storageUrl($testimonial->avatar_path ?? null) ?? '/img/dashboard/testimonials/avatar-1.png?v=0388437';
            $rating = $testimonial->rating !== null ? max(1, min(5, (int) $testimonial->rating)) : 5;
            $stars = '';
            for ($i = 0; $i < $rating; $i++) {
                $stars .= '<img src="/img/dashboard/testimonials/full-star.svg" alt="Etoile complete" loading="lazy">'."\n";
            }
            $slides .= '<div class="testimonials-item splide__slide">'."\n"
                .'<div class="testimonials-item-header">'."\n"
                .'<img src="'.e($avatar).'" class="testimonials-item-avatar" loading="lazy">'."\n"
                .'<div class="testimonials-item-user">'.e((string) $testimonial->author_name).'</div>'."\n"
                .'</div>'."\n"
                .'<p class="testimonials-item-content">'.e((string) $testimonial->quote).'</p>'."\n"
                .'<div class="testimonials-item-footer">'."\n"
                .'<div>'."\n".$stars.'</div>'."\n"
                .'<div>'.e((string) ($testimonial->role_text ?: 'since Google')).'</div>'."\n"
                .'</div>'."\n"
                .'</div>'."\n";
        }

        return preg_replace_callback(
            '/(<div class="splide__list testimonials-items">)(.*?)(<\/div>\s*<\/div>\s*<\/div>\s*<\/section>)/s',
            static fn (array $matches): string => $matches[1]."\n".$slides.$matches[3],
            $html,
            1,
        ) ?? $html;
    }

    protected function replaceShareSection(string $html): string
    {
        if (! str_contains($html, 'share-section')) {
            return $html;
        }

        $subtitle = $this->dynamicSetting('share_subtitle');
        if ($subtitle !== null) {
            $html = preg_replace_callback(
                '/(<div class="share-section-subtitle">)[^<]*(<\/div>)/',
                static fn (array $matches): string => $matches[1].e($subtitle).$matches[2],
                $html,
                1,
            ) ?? $html;
        }

        $titleHtml = $this->dynamicSetting('share_title_html');
        if ($titleHtml !== null) {
            $html = preg_replace_callback(
                '/(<h3 class="share-section-title">).*?(<\/h3>)/s',
                static fn (array $matches): string => $matches[1].$titleHtml.$matches[2],
                $html,
                1,
            ) ?? $html;
        }

        $html = $this->replaceShareCard($html, 'green', $this->dynamicSetting('share_card_title'), $this->dynamicSetting('share_card_text'), $this->dynamicSetting('share_card_button_label'));
        $html = $this->replaceShareCard($html, 'blue', $this->dynamicSetting('subscribe_card_title'), $this->dynamicSetting('subscribe_card_text'), $this->dynamicSetting('subscribe_card_button_label'));

        $contentHtml = $this->dynamicSetting('share_content_html');
        if ($contentHtml !== null) {
            $html = preg_replace_callback(
                '/(<div class="share-content col-12 col-lg-4 offset-xl-1">).*?(<\/div>\s*<\/div>\s*<\/div>\s*<\/section>)/s',
                static fn (array $matches): string => $matches[1].$contentHtml.$matches[2],
                $html,
                1,
            ) ?? $html;
        }

        return $html;
    }

    protected function replaceShareCard(string $html, string $color, ?string $title, ?string $text, ?string $buttonLabel): string
    {
        if ($title === null && $text === null && $buttonLabel === null) {
            return $html;
        }

        $card = preg_quote($color, '/');

        if ($title !== null) {
            $html = preg_replace_callback(
                '/(<div class="share-card '.$card.'">.*?<h2 class="title">)[^<]*(<\/h2>)/s',
                static fn (array $matches): string => $matches[1].e($title).$matches[2],
                $html,
                1,
            ) ?? $html;
        }

        if ($text !== null) {
            $html = preg_replace_callback(
                '/(<div class="share-card '.$card.'">.*?<p class="content">)[^<]*(<\/p>)/s',
                static fn (array $matches): string => $matches[1].e($text).$matches[2],
                $html,
                1,
            ) ?? $html;
        }

        if ($buttonLabel !== null) {
            $html = preg_replace_callback(
                '/(<div class="share-card '.$card.'">.*?<a [^>]*class="btn-sharesub dark big">)[^<]*(<\/a>)/s',
                static fn (array $matches): string => $matches[1].e($buttonLabel).$matches[2],
                $html,
                1,
            ) ?? $html;
        }

        return $html;
    }

    protected function replaceFooterNote(string $html): string
    {
        $note = $this->dynamicSetting('footer_note');

        if ($note === null) {
            return $html;
        }

        return preg_replace_callback(
            '/(<p class="hero-brand-disclaimer">).*?(<\/p>)/s',
            static fn (array $matches): string => $matches[1].e($note).$matches[2],
            $html,
            1,
        ) ?? $html;
    }

    /**
     * Global copy that applies to every captured page served through
     * pageResponse, including the protected dashboard pages. Each piece
     * keeps the captured markup when its setting or table is missing, and
     * a failure in one piece never breaks the page.
     */
    protected function applyGlobalContent(string $html, string $page): string
    {
        $original = $html;

        try {
            $html = $this->applySiteChrome($html, $page);
            $html = $this->replaceJsonLd($html);
            $html = $this->replaceCatalogueCount($html);
            $html = $this->replaceHeroCountries($html);
            $html = $this->replaceBrandFilterSelect($html);
            $html = $this->applyDashboardCopy($html, $page);
            $html = $this->hideInputArrowControls($html);
        } catch (\Throwable) {
            return $original;
        }

        return $html;
    }

    /**
     * Hide up and down arrow controls on every captured page: number
     * input spinners, and the arrow buttons on native scrollbars (which
     * show on textareas in some browsers). The replacement scrollbars
     * are thin and button free. Injected as one small style block
     * before </head> so captured pages keep their own markup untouched
     * and app.css stays unedited. Select dropdown arrows are not
     * touched, and the textarea resize grip is left alone.
     */
    protected function hideInputArrowControls(string $html): string
    {
        $style = '<style>input[type=number]::-webkit-outer-spin-button,input[type=number]::-webkit-inner-spin-button{-webkit-appearance:none;display:none;margin:0}input[type=number]{-moz-appearance:textfield;appearance:textfield}::-webkit-scrollbar-button{display:none;width:0;height:0}::-webkit-scrollbar{width:8px;height:8px}::-webkit-scrollbar-track{background:transparent}::-webkit-scrollbar-thumb{background:rgba(0,0,0,.25);border-radius:4px}*{scrollbar-width:thin;scrollbar-color:rgba(0,0,0,.25) transparent}</style>';

        return preg_replace('/<\/head>/i', $style.'</head>', $html, 1) ?? $html;
    }

    /**
     * Wrap a phrase inside a setting value with an inline tag, keeping
     * the captured emphasis for the seeded values. The value is escaped
     * first, so a phrase that is not found is left as plain text.
     */
    protected function wrapPhrase(string $escaped, string $phrase, string $tag): string
    {
        $needle = e($phrase);

        if (! str_contains($escaped, $needle)) {
            return $escaped;
        }

        return preg_replace('/'.preg_quote($needle, '/').'/', '<'.$tag.'>$0</'.$tag.'>', $escaped, 1) ?? $escaped;
    }








    /**
     * Regenerate the FAQPage JSON-LD from the faqs table and render the
     * SoftwareApplication name and rating from settings. The FAQPage
     * block is dropped when faqs exist but none are active, and kept
     * only when the faqs table has no rows at all.
     */
    protected function replaceJsonLd(string $html): string
    {
        if (str_contains($html, 'FAQPage')) {
            try {
                $faqs = Faq::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();
            } catch (\Throwable) {
                $faqs = collect();
            }

            if ($faqs->isNotEmpty()) {
                $entities = [];
                foreach ($faqs as $faq) {
                    $entities[] = [
                        '@type' => 'Question',
                        'name' => (string) $faq->question,
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => (string) $faq->answer,
                        ],
                    ];
                }

                $payload = [
                    '@context' => 'https://schema.org',
                    '@type' => 'FAQPage',
                    'mainEntity' => $entities,
                ];

                $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if ($json !== false) {
                    $block = '<script type="application/ld+json">'."\n".$json."\n    </script>";
                    $html = preg_replace(
                        '/<script type="application\/ld\+json">\s*\{.*?"@type":\s*"FAQPage".*?<\/script>/s',
                        $block,
                        $html,
                        1,
                    ) ?? $html;
                }
            } elseif ($this->tableHasRows('faqs')) {
                // Rows exist but none are active: drop the captured
                // FAQPage block instead of showing its questions.
                $html = preg_replace(
                    '/<script type="application\/ld\+json">\s*\{.*?"@type":\s*"FAQPage".*?<\/script>/s',
                    '',
                    $html,
                    1,
                ) ?? $html;
            }
        }

        if (str_contains($html, 'SoftwareApplication')) {
            $name = $this->dynamicSetting('jsonld_app_name');
            $ratingValue = $this->dynamicSetting('jsonld_rating_value');
            $ratingCount = $this->dynamicSetting('jsonld_rating_count');

            if ($name !== null || $ratingValue !== null || $ratingCount !== null) {
                // Scope to the SoftwareApplication block: the FAQPage
                // block also has a "name" field and must stay untouched.
                $html = preg_replace_callback(
                    '/<script type="application\/ld\+json">\s*\{.*?"@type":\s*"SoftwareApplication".*?<\/script>/s',
                    function (array $matches) use ($name, $ratingValue, $ratingCount): string {
                        $block = $matches[0];
                        if ($name !== null) {
                            $block = preg_replace_callback(
                                '/("name":\s*")[^"]*(")/',
                                static fn (array $inner): string => $inner[1].addslashes($name).$inner[2],
                                $block,
                                1,
                            ) ?? $block;
                        }
                        if ($ratingValue !== null) {
                            $block = preg_replace_callback(
                                '/("ratingValue":\s*")[^"]*(")/',
                                static fn (array $inner): string => $inner[1].e($ratingValue).$inner[2],
                                $block,
                                1,
                            ) ?? $block;
                        }
                        if ($ratingCount !== null) {
                            $block = preg_replace_callback(
                                '/("ratingCount":\s*")[^"]*(")/',
                                static fn (array $inner): string => $inner[1].e($ratingCount).$inner[2],
                                $block,
                                1,
                            ) ?? $block;
                        }

                        return $block;
                    },
                    $html,
                    1,
                ) ?? $html;
            }
        }

        return $html;
    }

    /**
     * The catalogue services count is the single source for the hero
     * search total-templates attribute and the FIXED_COUNT script value.
     * The saving calculator props are rendered by replaceSavingCalculator
     * and are not touched here.
     */
    protected function replaceCatalogueCount(string $html): string
    {
        $count = $this->dynamicSetting('catalogue_services_count');

        if ($count === null) {
            return $html;
        }

        $html = preg_replace(
            '/(total-templates=")[^"]*(")/',
            '${1}'.e($count).'${2}',
            $html,
        ) ?? $html;

        $html = preg_replace(
            "/(var FIXED_COUNT = ')[^']*(')/",
            '${1}'.e($count).'${2}',
            $html,
            1,
        ) ?? $html;

        return $html;
    }

    protected function replaceCtaBlocks(string $html): string
    {
        if (! str_contains($html, 'call-to-action')) {
            return $html;
        }

        $titles = [$this->dynamicSetting('cta1_title'), $this->dynamicSetting('cta2_title')];
        $texts = [$this->dynamicSetting('cta1_text'), $this->dynamicSetting('cta2_text')];
        $buttons = [$this->dynamicSetting('cta1_button_label'), $this->dynamicSetting('cta2_button_label')];
        // The "per year" saving phrase is composed from the default
        // currency symbol (currencies table) and the average yearly
        // savings amount setting (seeded 405 from the capture's
        // :avg-yearly-savings), never from a hardcoded price string.
        $savingsPhrase = (Currency::defaultSymbol() ?? '')
            .($this->calculatorSetting('calculator_avg_yearly_savings') ?? '405').' per year';
        $wraps = [
            ['Get the most out of' => 'span', $savingsPhrase => 'u'],
            ['started' => 'span'],
        ];

        if ($titles[0] === null && $texts[0] === null && $buttons[0] === null
            && $titles[1] === null && $texts[1] === null && $buttons[1] === null) {
            return $html;
        }

        $index = -1;
        $html = preg_replace_callback(
            '/<section class="call-to-action">.*?<\/section>/s',
            function (array $matches) use ($titles, $texts, $buttons, $wraps, &$index): string {
                $index++;
                if (! isset($titles[$index])) {
                    return $matches[0];
                }

                $section = $matches[0];

                if ($titles[$index] !== null) {
                    $rendered = e($titles[$index]);
                    foreach ($wraps[$index] as $phrase => $tag) {
                        $rendered = $this->wrapPhrase($rendered, $phrase, $tag);
                    }
                    $section = preg_replace_callback(
                        '/(<div class="call-to-action-title[^>]*>).*?(<\/div>)/s',
                        static fn (array $inner): string => $inner[1].$rendered.$inner[2],
                        $section,
                        1,
                    ) ?? $section;
                }

                if ($texts[$index] !== null) {
                    $text = e($texts[$index]);
                    $section = preg_replace_callback(
                        '/(<div class="call-to-action-content[^>]*>\s*<p>)[^<]*(<\/p>)/s',
                        static fn (array $inner): string => $inner[1].$text.$inner[2],
                        $section,
                        1,
                    ) ?? $section;
                }

                if ($buttons[$index] !== null) {
                    $label = e($buttons[$index]);
                    // The href is matched loosely on purpose: the share
                    // link step runs before this method and rewrites a
                    // share call to action href from #offline to the
                    // wizard, so requiring #offline here would leave the
                    // button label unreplaced on exactly those buttons.
                    $section = preg_replace_callback(
                        '/(<a href="[^"]*" class="btn-sharesub green big"[^>]*>.*?<img[^>]*>)\s*[^<]+(<\/a>)/s',
                        static fn (array $inner): string => $inner[1]."\n                    ".$label.$inner[2],
                        $section,
                        1,
                    ) ?? $section;
                }

                return $section;
            },
            $html,
        ) ?? $html;

        return $html;
    }

    protected function replaceHeroRating(string $html): string
    {
        $line = $this->dynamicSetting('hero_rating_line');

        if ($line === null) {
            return $html;
        }

        $rendered = e($line);
        $rendered = $this->wrapPhrase($rendered, '4,6/5', 'b');
        $rendered = $this->wrapPhrase($rendered, 'Google', 'u');
        $rendered = $this->wrapPhrase($rendered, 'Trustpilot', 'u');

        return preg_replace_callback(
            '/(<div class="testimonials-section-subtitle">).*?(<\/div>)/s',
            static fn (array $matches): string => $matches[1].$rendered.$matches[2],
            $html,
            1,
        ) ?? $html;
    }

    protected function replaceTestimonialsHeading(string $html): string
    {
        $heading = $this->dynamicSetting('testimonials_heading');
        if ($heading !== null) {
            $rendered = e($heading);
            $rendered = $this->wrapPhrase($rendered, '900,000', 'span');
            $rendered = $this->wrapPhrase($rendered, 'trust', 'u');
            $rendered .= ' <img src="/img/dashboard/testimonials/hearts.svg" class="hearts" loading="lazy">';
            $html = preg_replace_callback(
                '/(<section class="testimonials-section"[^>]*>.*?<h3>).*?(<\/h3>)/s',
                static fn (array $matches): string => $matches[1]."\n                            ".$rendered.$matches[2],
                $html,
                1,
            ) ?? $html;
        }

        $subheading = $this->dynamicSetting('testimonials_subheading');
        if ($subheading !== null) {
            $rendered = e($subheading);
            $rendered = $this->wrapPhrase($rendered, '4.8/5', 'b');
            $rendered = $this->wrapPhrase($rendered, 'Google', 'u');
            $rendered = $this->wrapPhrase($rendered, 'Trustpilot', 'u');
            // The subheading lives in the testimonials section, after the
            // hero rating line that replaceHeroRating already handled.
            $html = preg_replace_callback(
                '/(<section class="testimonials-section"[^>]*>.*?<div class="testimonials-section-subtitle">).*?(<\/div>)/s',
                static fn (array $matches): string => $matches[1].$rendered.$matches[2],
                $html,
                1,
            ) ?? $html;
        }

        return $html;
    }

    protected function replaceFaqSectionTitle(string $html): string
    {
        $title = $this->dynamicSetting('faq_section_title');

        if ($title === null) {
            return $html;
        }

        return preg_replace_callback(
            '/(<h3 class="faq-section-title">)[^<]*(<\/h3>)/',
            static fn (array $matches): string => $matches[1].e($title).$matches[2],
            $html,
            1,
        ) ?? $html;
    }

    protected function replacePressSectionTitle(string $html): string
    {
        $title = $this->dynamicSetting('press_section_title');

        if ($title === null) {
            return $html;
        }

        $rendered = str_replace('Read about us', 'Read<br/> about us', e($title));

        return preg_replace_callback(
            '/(<div class="press-title[^>]*>).*?(<\/div>)/s',
            static fn (array $matches): string => $matches[1]."\n                                ".$rendered.$matches[2],
            $html,
            1,
        ) ?? $html;
    }

    /**
     * Render both how-it-works step sets from the database: the share
     * set in the propose block and the seat set in the participate
     * block. Toggle labels, subtitle and section title come from
     * settings. A step with a mockup image gets the image inside its
     * matching mockup panel; steps without one keep the captured mockup.
     */
    protected function replaceHowItWorks(string $html): string
    {
        if (! str_contains($html, 'how-it-works-section')) {
            return $html;
        }

        try {
            if (! class_exists(HowItWorksStep::class) || ! Schema::hasTable('how_it_works_steps')) {
                return $html;
            }

            $shareSteps = HowItWorksStep::where('is_active', true)->where('audience', 'share')->orderBy('sort_order')->orderBy('id')->get();
            $seatSteps = HowItWorksStep::where('is_active', true)->where('audience', 'seat')->orderBy('sort_order')->orderBy('id')->get();
            $stepsSeeded = $this->tableHasRows('how_it_works_steps');
        } catch (\Throwable) {
            return $html;
        }

        $howTitle = $this->dynamicSetting('how_title');
        if ($howTitle !== null) {
            $html = preg_replace_callback(
                '/(<span class="how-it-works-section-subtitle-lead">)[^<]*(<\/span>)/',
                static fn (array $matches): string => $matches[1].e($howTitle).$matches[2],
                $html,
                1,
            ) ?? $html;
        }

        $sectionTitle = $this->dynamicSetting('how_section_title');
        if ($sectionTitle !== null) {
            $rendered = e($sectionTitle);
            $rendered = $this->wrapPhrase($rendered, 'Subscription', 'u');
            $html = preg_replace_callback(
                '/(<h2 class="how-it-works-section-title">).*?(<\/h2>)/s',
                static fn (array $matches): string => $matches[1].$rendered.$matches[2],
                $html,
                1,
            ) ?? $html;
        }

        $shareLabel = $this->dynamicSetting('how_share_label');
        if ($shareLabel !== null) {
            $html = preg_replace(
                '/(data-left=")[^"]*(")/',
                '$1'.e($shareLabel).'$2',
                $html,
                1,
            ) ?? $html;
        }

        $seatLabel = $this->dynamicSetting('how_seat_label');
        if ($seatLabel !== null) {
            $html = preg_replace(
                '/(data-right=")[^"]*(")/',
                '$1'.e($seatLabel).'$2',
                $html,
                1,
            ) ?? $html;
        }

        if ($shareSteps->isNotEmpty() || $stepsSeeded) {
            $html = $this->replaceHowItWorksSet($html, 'propose', $shareSteps);
        }

        if ($seatSteps->isNotEmpty() || $stepsSeeded) {
            $html = $this->replaceHowItWorksSet($html, 'participate', $seatSteps);
        }

        return $html;
    }

    protected function replaceHowItWorksSet(string $html, string $container, mixed $steps): string
    {
        $items = '';
        foreach ($steps as $position => $step) {
            $items .= $this->renderHowItWorksItem($step, $position + 1, $position === 0);
        }

        $html = preg_replace_callback(
            '/(<div class="how-it-works '.$container.'">\s*<div class="how-it-works-items row">\s*<div class="how-it-works-content col-12 col-md-6">)(.*?)(<\/div>\s*<div class="how-it-works-mockups)/s',
            static fn (array $matches): string => $matches[1]."\n".$items.$matches[3],
            $html,
            1,
        ) ?? $html;

        // Insert a mockup image only inside this set's own mockup panels:
        // the share and seat sets both number their panels from 1.
        $start = strpos($html, '<div class="how-it-works '.$container.'">');
        if ($start === false) {
            return $html;
        }
        $next = strpos($html, '<div class="how-it-works ', $start + 10);
        $end = $next !== false ? $next : strpos($html, '</section>', $start);
        if ($end === false) {
            return $html;
        }

        $block = substr($html, $start, $end - $start);
        foreach ($steps as $position => $step) {
            $image = $this->storageUrl($step->mockup_image_path ?? null);
            if ($image === null) {
                continue;
            }
            $mockupId = $position + 1;
            $block = preg_replace_callback(
                '/(<div class="how-it-works-mockup(?: active)?" data-mockup-id="'.$mockupId.'">)/',
                static fn (array $matches): string => $matches[1]."\n".'<img src="'.e($image).'" class="how-it-works-mockup-image" loading="lazy" alt="">',
                $block,
                1,
            ) ?? $block;
        }
        $html = substr($html, 0, $start).$block.substr($html, $end);

        return $html;
    }

    protected function renderHowItWorksItem(mixed $step, int $mockup, bool $active): string
    {
        $icon = (string) ($step->icon_svg ?? '');

        return '<div class="how-it-works-item'.($active ? ' active' : '').'" data-mockup="'.$mockup.'">'."\n"
            .'<div class="how-it-works-item-header d-flex justify-content-between align-items-center">'."\n"
            .'<div class="how-it-works-item-title d-flex align-items-center wrapper">'."\n"
            .$icon."\n"
            .'<span class="title">'.e((string) $step->title).'</span>'."\n"
            .'</div>'."\n"
            .'<span class="how-it-works-item-toggle"></span>'."\n"
            .'</div>'."\n"
            .'<p>'.e((string) ($step->text ?? '')).'</p>'."\n"
            .'</div>'."\n";
    }

    /**
     * Active countries from the database. The hero search prop keeps the
     * captured JSON shape of id, name and slug; the slug is derived from
     * the name because the countries table stores no slug column.
     */
    protected function activeCountries(): mixed
    {
        try {
            if (! class_exists(Country::class) || ! Schema::hasTable('countries')) {
                return collect();
            }

            return Country::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();
        } catch (\Throwable) {
            return collect();
        }
    }

    protected function replaceHeroCountries(string $html): string
    {
        if (! str_contains($html, ':countries=')) {
            return $html;
        }

        $countries = $this->activeCountries();

        if ($countries->isEmpty()) {
            // No active country. Keep the captured props only when the
            // table was never seeded; otherwise render an empty list
            // with no current country.
            if (! $this->tableHasRows('countries')) {
                return $html;
            }

            return preg_replace_callback(
                '/<hero-search\b[^>]*>/s',
                static function (array $matches): string {
                    $tag = preg_replace(
                        '/:countries=\'[^\']*\'/',
                        ':countries=\'[]\'',
                        $matches[0],
                        1,
                    ) ?? $matches[0];

                    return preg_replace(
                        '/:current-country=\'[^\']*\'/',
                        ':current-country=\'null\'',
                        $tag,
                        1,
                    ) ?? $tag;
                },
                $html,
            ) ?? $html;
        }

        $list = [];
        foreach ($countries as $country) {
            $list[] = [
                'id' => (int) $country->id,
                'name' => (string) $country->name,
                'slug' => Str::slug((string) $country->name),
            ];
        }

        $json = json_encode($list);
        if ($json === false) {
            return $html;
        }

        $currentJson = null;
        $current = $countries->firstWhere('code', 'GB') ?? $countries->first();
        if ($current !== null) {
            $encoded = json_encode($this->currentCountryPayload($current));
            $currentJson = $encoded !== false ? $encoded : null;
        }

        // Scope to the hero-search tags only: the saving calculator has
        // its own :current-country prop that replaceSavingCalculator
        // owns, and it must not be simplified here.
        $html = preg_replace_callback(
            '/<hero-search\b[^>]*>/s',
            function (array $matches) use ($json, $currentJson): string {
                $tag = preg_replace(
                    '/:countries=\'[^\']*\'/',
                    ':countries=\''.$json.'\'',
                    $matches[0],
                    1,
                ) ?? $matches[0];

                if ($currentJson !== null) {
                    $tag = preg_replace(
                        '/:current-country=\'[^\']*\'/',
                        ':current-country=\''.$currentJson.'\'',
                        $tag,
                        1,
                    ) ?? $tag;
                }

                return $tag;
            },
            $html,
        ) ?? $html;

        return $html;
    }

    /** @return array<string, mixed> */
    protected function currentCountryPayload(mixed $country): array
    {
        $code = strtoupper((string) ($country->currency ?: Currency::defaultCode()));
        $row = Currency::findByCode($code);
        // The nested currency object and the currency_id /
        // fallback_for_currency_id fields are what the capture's
        // :current-country prop carries (826/826 for GB). Both ids are the
        // currency row's ISO numeric id from the table.
        $isoNumeric = $row?->iso_numeric;
        $currencyPayload = [
            'id' => $isoNumeric,
            'code' => $code,
            'symbol' => $row?->symbol ?? $code,
            'name' => $row?->name,
        ];

        return [
            'id' => (int) $country->id,
            'code' => (string) $country->code,
            'name' => (string) $country->name,
            'slug' => Str::slug((string) $country->name),
            'fee_flat' => number_format((float) $country->fee_flat, 3, '.', ''),
            'fee_percent' => number_format((float) $country->fee_percent, 3, '.', ''),
            'currency_id' => $isoNumeric,
            'fallback_for_currency_id' => $isoNumeric,
            'currency' => $currencyPayload,
            'postal_code_regex' => $country->postal_regex,
            'postal_code_example' => $country->postal_example,
        ];
    }

    protected function replaceBrandFilterSelect(string $html): string
    {
        if (! str_contains($html, 'id="public_subs_country"')) {
            return $html;
        }

        $countries = $this->activeCountries();

        if ($countries->isEmpty() && ! $this->tableHasRows('countries')) {
            return $html;
        }

        $options = '';
        foreach ($countries as $country) {
            $options .= '<option value="'.e((string) $country->code).'">'."\n".e((string) $country->name)."\n".'</option>'."\n";
        }

        return preg_replace_callback(
            '/(<select name="country" class="select" id="public_subs_country">).*?(<\/select>)/s',
            static fn (array $matches): string => $matches[1]."\n".$options.$matches[2],
            $html,
            1,
        ) ?? $html;
    }

    /**
     * Rebuild the my-account country select from the countries table,
     * keeping the captured shape: a disabled Country placeholder, a
     * Frequent optgroup, then an All countries optgroup in database
     * order. The Stripe country for the French territories is FR in the
     * capture, so that mapping is kept here.
     */
    protected function replaceAccountCountrySelect(string $html): string
    {
        if (! str_contains($html, 'name="country"')) {
            return $html;
        }

        $countries = $this->activeCountries();

        if ($countries->isEmpty() && ! $this->tableHasRows('countries')) {
            return $html;
        }

        $frequent = ['BE', 'FR', 'DE', 'IT', 'ES', 'GB'];
        $frenchTerritories = ['GF', 'GP', 'MQ', 'YT', 'RE', 'MF'];

        $option = static function (mixed $country) use ($frenchTerritories): string {
            $code = (string) $country->code;
            $stripe = in_array($code, $frenchTerritories, true) ? 'FR' : $code;
            $pattern = $country->postal_regex !== null ? ' data-postal-pattern="'.e((string) $country->postal_regex).'"' : ' data-postal-pattern=""';
            $example = $country->postal_example !== null ? ' data-postal-example="'.e((string) $country->postal_example).'"' : ' data-postal-example=""';

            return '<option value="'.e($code).'" data-stripe-country="'.e($stripe).'"'.$pattern.$example.'>'.e((string) $country->name).'</option>'."\n";
        };

        $frequentOptions = '';
        $allOptions = '';
        foreach ($countries as $country) {
            if (in_array((string) $country->code, $frequent, true)) {
                $frequentOptions .= $option($country);
            } else {
                $allOptions .= $option($country);
            }
        }

        $inner = '<option disabled  value="#offline">Country</option>'."\n"
            .'<optgroup label="Frequent">'."\n".$frequentOptions.'</optgroup>'."\n"
            .'<optgroup label="All countries">'."\n".$allOptions.'</optgroup>'."\n";

        return preg_replace_callback(
            '/(<select name="country"[^>]*>).*?(<\/select>)/s',
            static fn (array $matches): string => $matches[1]."\n".$inner.$matches[2],
            $html,
            1,
        ) ?? $html;
    }

    /**
     * Dashboard and account page copy from settings. Live user data such
     * as amounts, names and lists is never touched here, and neither is
     * auth or legal copy.
     */
    protected function applyDashboardCopy(string $html, string $page): string
    {
        if ($page === 'en/dashboard.html') {
            $html = $this->hideDashboardOnboarding($html);
            $html = $this->fixDashboardDeadLinks($html);
            $html = $this->replaceDashboardCta($html);
            $html = $this->replaceDashboardChecklist($html);
            $html = $this->replaceDashboardProposeLink($html);
            $html = $this->renderDashboardStats($html);
            $html = $this->renderDashboardPayments($html);
        } elseif ($page === 'en/my-shared-subscriptions.html') {
            $html = $this->replaceCreateSubscriptionLink($html);
            $html = $this->replaceCreateSubscriptionLabel($html);
            $html = $this->replaceDiscoverTitle($html);
            $html = $this->renderMySharedOffers($html);
            $html = $this->replaceDiscoverSuggestions($html);
            $html = $this->replaceEmptyText($html, 'shared_empty_text');
        } elseif ($page === 'en/subscriptions.html') {
            $html = $this->replaceProposeSubscriptionLinks($html);
        } elseif ($page === 'en/joined-subscriptions.html') {
            $html = $this->replaceEmptyText($html, 'joined_empty_text');
        } elseif ($page === 'en/waiting-list.html') {
            $html = $this->replaceWaitingHelp($html);
        } elseif ($page === 'en/my-wallet.html') {
            $html = $this->replaceWalletPolicies($html);
        } elseif ($page === 'en/my-chat/subscriptions.html') {
            $html = $this->replaceChatEmpty($html);
        }

        return $html;
    }

    /**
     * Hide the dashboard onboarding blocks for an established user.
     *
     * ASSUMPTION (dashboard mismatch spec, section 2.4): the real
     * capture contains no CTA block and no profile checklist at all,
     * but it does not pin down the trigger, so the blocks are removed
     * from the rendered HTML when the user has at least one active
     * shared offer (offers.status = active, not draft or terminated).
     * That directly fixes the reported screenshot, matches the
     * checklist wording, and needs no new data. The blocks are
     * REMOVED, not CSS-hidden, because the real capture has no such
     * markup. A user with no active offer keeps both blocks.
     */
    protected function hideDashboardOnboarding(string $html): string
    {
        if (! $this->hasActiveSharedOffer()) {
            return $html;
        }

        $html = preg_replace(
            '/<div class="share-section dashboard mb-4 p-4">.*?(?=<div class="dashboard-progress mb-3 p-4">)/s',
            '',
            $html,
            1,
        ) ?? $html;

        return preg_replace(
            '/<div class="dashboard-progress mb-3 p-4">.*?(?=<div class="dashboard-progress dashboard-revenues-expenses)/s',
            '',
            $html,
            1,
        ) ?? $html;
    }

    protected function hasActiveSharedOffer(): bool
    {
        if (! Auth::check()) {
            return false;
        }

        try {
            if (! class_exists(Offer::class) || ! Schema::hasTable('offers')) {
                return false;
            }

            return Offer::where('user_id', Auth::id())
                ->where('status', 'active')
                ->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Point the dashboard dead links at the real destinations from
     * the real capture (dashboard mismatch spec, section 1.8): the
     * mobile sheet Connected Bank Account and Contact Sharesub
     * Support links, the tab bar Follow-up link, and the CTA Share
     * button while the onboarding blocks are shown. The checklist
     * Complete buttons are left as-is: no target is observable
     * anywhere. The checklist progress bar aria value is made
     * self-consistent with its one-third width (33.33, not 0).
     */
    protected function fixDashboardDeadLinks(string $html): string
    {
        $html = str_replace(
            '<a href="#offline" class="btn-sharesub dark big">Share</a>',
            '<a href="/en/create-subscription" class="btn-sharesub dark big">Share</a>',
            $html,
        );

        $html = preg_replace(
            '/(<a href=")#offline(" class="dash-sheet-link ">\s*<svg.*?<\/svg>\s*<span>Connected Bank Account<\/span>)/s',
            '$1/en/my-bank-account$2',
            $html,
            1,
        ) ?? $html;

        $html = str_replace(
            '<a href="#offline" target="_blank" rel="noopener" class="dash-sheet-link" data-open-support>',
            '<a href="https://help.sharesub.com/" target="_blank" rel="noopener" class="dash-sheet-link" data-open-support>',
            $html,
        );

        $html = str_replace(
            '<a href="#offline" class="dash-tabbar-item active">',
            '<a href="/en/dashboard" class="dash-tabbar-item active">',
            $html,
        );

        return str_replace(
            'style="width: 33.333333333333%" aria-valuenow="0"',
            'style="width: 33.333333333333%" aria-valuenow="33.333333333333"',
            $html,
        );
    }

    protected function replaceDashboardCta(string $html): string
    {
        $title = $this->dynamicSetting('dashboard_cta_title');

        if ($title === null) {
            return $html;
        }

        $rendered = e($title);
        $rendered = $this->wrapPhrase($rendered, 'start', 'u');

        return preg_replace_callback(
            '/(<h3 class="share-section-title cta-dashboard">).*?(<\/h3>)/s',
            static fn (array $matches): string => $matches[1].$rendered.$matches[2],
            $html,
            1,
        ) ?? $html;
    }

    protected function replaceDashboardChecklist(string $html): string
    {
        $items = [];
        for ($number = 1; $number <= 3; $number++) {
            $title = $this->dynamicSetting('dashboard_checklist_'.$number.'_title');
            $text = $this->dynamicSetting('dashboard_checklist_'.$number.'_text');
            if ($title === null && $text === null) {
                continue;
            }
            $items[$number] = [$title, $text];
        }

        if ($items === []) {
            return $html;
        }

        $position = 0;
        $html = preg_replace_callback(
            '/(<ul class="dashboard-profile-steps mt-3">)(.*?)(<\/ul>)/s',
            function (array $matches) use ($items, &$position): string {
                $list = preg_replace_callback(
                    '/<li class="row align-items-center">.*?<\/li>/s',
                    function (array $item) use ($items, &$position): string {
                        $position++;
                        if (! isset($items[$position])) {
                            return $item[0];
                        }

                        [$title, $text] = $items[$position];
                        $block = $item[0];

                        if ($title !== null) {
                            $block = preg_replace_callback(
                                '/(<div class="col">)\s*[^<]+(\s*<div class="text-muted text-sm">)/s',
                                static fn (array $inner): string => $inner[1]."\n                                        ".e($title).$inner[2],
                                $block,
                                1,
                            ) ?? $block;
                        }

                        if ($text !== null) {
                            $block = preg_replace_callback(
                                '/(<div class="text-muted text-sm">)[^<]*(<\/div>)/',
                                static fn (array $inner): string => $inner[1].e($text).$inner[2],
                                $block,
                                1,
                            ) ?? $block;
                        }

                        return $block;
                    },
                    $matches[2],
                ) ?? $matches[2];

                return $matches[1].$list.$matches[3];
            },
            $html,
            1,
        ) ?? $html;

        return $html;
    }

    protected function replaceEmptyText(string $html, string $key): string
    {
        $text = $this->dynamicSetting($key);

        if ($text === null) {
            return $html;
        }

        return preg_replace_callback(
            '/(<p class="subscriptions-items-no-item">).*?(<\/p>)/s',
            static fn (array $matches): string => $matches[1].e($text).$matches[2],
            $html,
            1,
        ) ?? $html;
    }

    /**
     * Point the Create subscription button on My shared subscriptions at
     * the wizard instead of the registration page. The captured href is
     * a guest-state link; logged in users own this page.
     */
    protected function replaceCreateSubscriptionLink(string $html): string
    {
        return str_replace(
            '<a href="/en/register.html" class="btn btn-sharesub green mb-2 mb-sm-0">',
            '<a href="/en/create-subscription" class="btn btn-sharesub green mb-2 mb-sm-0">',
            $html,
        );
    }

    /**
     * The real list page reads "Create a shared Subscription" on the
     * button (Raw Pages 2, spec S1). The base capture reads "Create
     * subscription". Text only.
     */
    protected function replaceCreateSubscriptionLabel(string $html): string
    {
        return preg_replace(
            '/Create subscription(\s*<\/a>)/',
            'Create a shared Subscription$1',
            $html,
            1,
        ) ?? $html;
    }

    /**
     * The real Discover section title reads "Discover also" (Raw Pages
     * 2, spec S11). The base capture reads "Discover". Text only; the
     * cards themselves are replaced by replaceDiscoverSuggestions().
     * The "up to reimbursed" card kind is not built: the figure's
     * derivation cannot be proved from the capture (gap G-S3).
     */
    protected function replaceDiscoverTitle(string $html): string
    {
        return preg_replace(
            '/(<div class="subscriptions-suggestions-title">\s*)Discover(\s*<\/div>)/',
            '$1Discover also$2',
            $html,
            1,
        ) ?? $html;
    }

    /**
     * Point the Propose a subscription button on the dashboard at the
     * wizard instead of the registration page. The button is the only
     * anchor on this page with this exact href and class, so no genuine
     * register link is touched. The label text is left as rendered.
     */
    protected function replaceDashboardProposeLink(string $html): string
    {
        return preg_replace(
            '/(<a href=")\/en\/register\.html(" class="btn btn-sharesub green">)/',
            '$1/en/create-subscription$2',
            $html,
            1,
        ) ?? $html;
    }

    /**
     * Fill the dashboard Income and expenses card from the wallet
     * balances (Raw Pages 2, spec D3/D4). Monthly Savings is the pending
     * monthly income, with the total earnings as the "from the
     * beginning" subvalue. The refund is NOT the pending amount: the
     * real capture shows non-zero Monthly Savings with a zero refund,
     * so the refund cannot be the same balance.
     *
     * ASSUMPTION: the refund is the available balance becoming
     * available this month. WalletController labels the available
     * metric "Available for transfer on %s" from its available_on
     * date, and the wallet policy says available funds are
     * automatically transferred on the 9th of each month, so the
     * amount whose available_on falls in the current month is the
     * money coming to the user this month. When no available balance
     * falls due this month, each available currency renders a zero
     * line, never a converted figure. One line per currency,
     * account currency first, then EUR, then the rest, matching the
     * real page's two-line refund block. A currency with no wallet
     * row renders no line: figures are never converted and no rate
     * is invented, so a missing EUR line is a recorded gap (G-D2),
     * not a zero. Monthly expenses have no data source yet, so that
     * stat is left exactly as captured (gap G-D2). My monthly
     * expenses still need a joined-subscription spend source.
     */
    protected function renderDashboardStats(string $html): string
    {
        if (! Auth::check()) {
            return $html;
        }

        try {
            if (! class_exists(WalletBalance::class) || ! Schema::hasTable('wallet_balances')) {
                return $html;
            }

            $balances = WalletBalance::where('user_id', Auth::id())->get();
        } catch (\Throwable) {
            return $html;
        }

        if ($balances->isEmpty()) {
            return $html;
        }

        $defaultCode = Currency::defaultCode() ?? '';
        $order = static fn (mixed $row): array => [
            (string) ($row->currency ?? '') === $defaultCode ? 0 : ((string) ($row->currency ?? '') === 'EUR' ? 1 : 2),
            (string) ($row->currency ?? ''),
        ];
        $pending = $balances->where('metric', WalletBalance::METRIC_PENDING)
            ->sortBy($order)->values();
        $earned = $balances->where('metric', WalletBalance::METRIC_TOTAL_EARNINGS)
            ->sortBy($order)->values();
        $available = $balances->where('metric', WalletBalance::METRIC_AVAILABLE)
            ->sortBy($order)->values();

        if ($pending->isEmpty()) {
            return $html;
        }

        $amountLine = fn (mixed $row): string => $this->formatPriceAmount(
            (float) ($row->amount ?? 0),
            Currency::symbolFor($row->currency ?? null) ?? (string) ($row->currency ?? ''),
        );

        $plainLines = $pending->map($amountLine)->all();

        // Refund coming this month: available balances whose
        // available_on falls in the current month. If none fall due
        // this month, each available currency renders a zero line.
        $refundDue = $available->filter(function (mixed $row): bool {
            $due = $row->available_on;

            return $due !== null
                && $due->greaterThanOrEqualTo(now()->startOfMonth())
                && $due->lessThanOrEqualTo(now()->endOfMonth());
        })->values();
        $greenLines = $refundDue->isNotEmpty()
            ? $refundDue->map($amountLine)->all()
            : $available->map(
                fn (mixed $row): string => $this->formatPriceAmount(
                    0,
                    Currency::symbolFor($row->currency ?? null) ?? (string) ($row->currency ?? ''),
                ),
            )->all();

        $html = preg_replace_callback(
            '/(<span class="stat-value">)[^<]*(<\/span>\s*<span class="stat-label text-muted">Monthly Savings<\/span>)/',
            static fn (array $matches): string => $matches[1]
                .implode('</span>'."\n".'<br>'."\n".'<span class="stat-value">', $plainLines)
                .$matches[2],
            $html,
            1,
        ) ?? $html;

        if ($earned->isNotEmpty()) {
            $subvalue = $earned->map(
                static fn (mixed $row): string => $amountLine($row).' from the beginning',
            )->implode('<br>');

            $html = preg_replace_callback(
                '/(<span class="stat-subvalue">)[^<]*(<\/span>)/',
                static fn (array $matches): string => $matches[1].$subvalue.$matches[2],
                $html,
                1,
            ) ?? $html;
        }

        // No available row at all: leave the captured refund value
        // rather than rendering an empty span.
        if ($greenLines !== []) {
            $html = preg_replace_callback(
                '/(<span class="stat-value text-green">)[^<]*(<\/span>\s*<span class="stat-label text-green">Refund coming this month<\/span>)/',
                static fn (array $matches): string => $matches[1]
                    .implode('</span>'."\n".'<br>'."\n".'<span class="stat-value text-green">', $greenLines)
                    .$matches[2],
                $html,
                1,
            ) ?? $html;
        }

        return $html;
    }

    /**
     * Fill the dashboard Payments card from the user's payouts and
     * scheduled payments (Raw Pages 2, spec D5). "This month" lists
     * payouts paid this month; when there is none the captured empty
     * state stays. "Coming soon" lists the pending offer_payments
     * for next month, one row per current co-subscriber, in the same
     * row format as "This month"; when there is none the captured
     * empty state stays. The markup of a non-empty row is unproven:
     * no capture shows one (gap G-D3), so this reuses the existing
     * "This month" row shape and says so.
     */
    protected function renderDashboardPayments(string $html): string
    {
        if (! Auth::check()) {
            return $html;
        }

        try {
            if (! class_exists(Payout::class) || ! Schema::hasTable('payouts')) {
                return $html;
            }

            $payouts = Payout::where('user_id', Auth::id())
                ->whereBetween('paid_at', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
                ->orderBy('paid_at')
                ->get();

            $comingSoon = collect();
            if (class_exists(OfferPayment::class) && Schema::hasTable('offer_payments')) {
                $offerIds = Offer::where('user_id', Auth::id())->pluck('id');

                if ($offerIds->isNotEmpty()) {
                    $comingSoon = OfferPayment::with('member')
                        ->whereIn('offer_id', $offerIds)
                        ->where('status', OfferPayment::STATUS_PENDING)
                        ->whereDate('month', now()->startOfMonth()->addMonth()->toDateString())
                        ->orderBy('member_id')
                        ->get();
                }
            }
        } catch (\Throwable) {
            return $html;
        }

        if ($payouts->isNotEmpty()) {
            $rows = $payouts->map(function (mixed $payout): string {
                $amount = $this->formatPriceAmount(
                    (float) ($payout->amount ?? 0),
                    Currency::symbolFor($payout->currency ?? null) ?? (string) ($payout->currency ?? ''),
                );
                $date = $payout->paid_at !== null ? $payout->paid_at->format('Y/m/d') : '';

                return '<li>'.e((string) ($payout->label ?: 'Transfer made')).' - '.$date.' - '.$amount.'</li>';
            })->implode("\n");

            $html = preg_replace_callback(
                '/(<div class="dashboard-payments-past">\s*<h4>This month<\/h4>\s*<ul>).*?(<\/ul>)/s',
                static fn (array $matches): string => $matches[1]."\n".$rows."\n".$matches[2],
                $html,
                1,
            ) ?? $html;
        }

        if ($comingSoon->isNotEmpty()) {
            $rows = $comingSoon->map(function (mixed $payment): string {
                $amount = $this->formatPriceAmount(
                    (float) ($payment->amount ?? 0),
                    Currency::symbolFor($payment->currency ?? null) ?? (string) ($payment->currency ?? ''),
                );
                $date = $payment->month !== null ? $payment->month->format('Y/m/d') : '';
                $label = $payment->member !== null && (string) ($payment->member->username ?? '') !== ''
                    ? (string) $payment->member->username
                    : 'Payment due';

                return '<li>'.e($label).' - '.$date.' - '.$amount.'</li>';
            })->implode("\n");

            $html = preg_replace_callback(
                '/(<div class="dashboard-payments-upcoming">\s*<h4>Coming soon<\/h4>\s*<ul>).*?(<\/ul>)/s',
                static fn (array $matches): string => $matches[1]."\n".$rows."\n".$matches[2],
                $html,
                1,
            ) ?? $html;
        }

        return $html;
    }

    /**
     * Point the Propose a subscription calls to action on the catalogue
     * page at the wizard instead of the registration page.
     */
    protected function replaceProposeSubscriptionLinks(string $html): string
    {
        return preg_replace(
            '/(<a href=")\/en\/register\.html(" class="btn btn-sharesub green[^"]*">)/',
            '$1/en/create-subscription$2',
            $html,
        ) ?? $html;
    }

    /**
     * List the logged in user's own offers, draft and active, inside
     * .subscriptions-items on My shared subscriptions, replacing the
     * empty state. Guests never reach this page: the route is auth only.
     * Each row links to the wizard edit page, with a delete form next
     * to it.
     */
    protected function renderMySharedOffers(string $html): string
    {
        if (! Auth::check()) {
            return $html;
        }

        try {
            if (! class_exists(Offer::class) || ! Schema::hasTable('offers')) {
                return $html;
            }

            $offers = Offer::where('user_id', Auth::id())
                ->orderByDesc('updated_at')->orderByDesc('id')->get();
        } catch (\Throwable) {
            return $html;
        }

        if ($offers->isEmpty()) {
            return $html;
        }

        $rows = '';
        foreach ($offers as $offer) {
            $rows .= $this->renderMySharedOfferRow($offer);
        }

        return preg_replace_callback(
            '/(<div class="subscriptions-items">).*?(<\/div>\s*<div class="pagination-green)/s',
            static fn (array $matches): string => $matches[1]."\n".$rows.$matches[2],
            $html,
            1,
        ) ?? $html;
    }

    /**
     * Display name for a shared offer row. A fresh wizard draft has no
     * name yet: newDraft stores title '' and leaves name null, and a
     * draft that stopped after picking a brand has no name either. The
     * old code read only name ?? title, and '' passes ?? through, so
     * the row rendered an empty <strong> and looked nameless. Fall back
     * through the template, brand and category, the same chain the
     * resume modal uses. When nothing was picked yet the row still
     * needs a name, so it shows the page's own word for the row's state
     * instead of a blank. Publish requires a name, so a non-draft row
     * never reaches the last resort.
     */
    protected function sharedOfferName(mixed $offer): string
    {
        foreach ([$offer->name ?? null, $offer->title ?? null] as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        foreach (['template', 'brand', 'category'] as $relation) {
            try {
                $related = $offer->{$relation};
            } catch (\Throwable) {
                $related = null;
            }

            if ($related !== null && trim((string) ($related->name ?? '')) !== '') {
                return trim((string) $related->name);
            }
        }

        return $offer->status === 'draft' ? 'Draft subscription' : '';
    }

    /**
     * One shared offer row in the real .subscriptions-item markup (Raw
     * Pages 2, spec S3-S8): brand avatar slot, name, Public and Created
     * on pills, seat icons with the remaining count, the total price
     * big with the per-subscriber price in the fee line, and View
     * details plus Chat actions. The old Bootstrap row with Edit and
     * Delete buttons is gone: the real page has neither.
     *
     * Terminated offers (status terminated, set by the end-subscription
     * endpoint) carry data-canceled and start hidden, so the captured
     * #showInactive toggle in public/js/app.js shows and hides them.
     * The seat icons are one per shareable seat and the first taken
     * count is coloured. The filled versus empty icon distinction is
     * not pinned down from the static capture (gap G-S4): the capture
     * rows disagree on it.
     */
    protected function renderMySharedOfferRow(mixed $offer): string
    {
        $name = $this->sharedOfferName($offer);
        $brand = null;
        try {
            $brand = $offer->brand;
        } catch (\Throwable) {
            $brand = null;
        }

        // The offer row price symbol comes from the currencies table by
        // the offer (then brand) currency code; the stored brand symbol
        // is only a fallback for a code with no currency row.
        $symbol = Currency::symbolFor($offer->currency ?? $brand?->currency ?? null);
        if ($symbol === null || $symbol === '') {
            $symbol = $brand !== null ? (string) ($brand->currency_symbol ?? '') : '';
        }
        if ($symbol === '') {
            $symbol = Currency::defaultSymbol() ?? '';
        }

        $identifier = $this->offerHash($offer);
        $total = $this->sharedOfferTotalPrice($offer);
        [$intPart, $decPart] = $this->splitPriceParts($total, $symbol);
        $perSeat = $this->formatPriceAmount((float) ($offer->price ?? 0), $symbol);

        $seatsTotal = max(0, (int) ($offer->friends ?? 0));
        $taken = $this->sharedOfferSubscriberCount($offer);
        $remaining = max(0, $seatsTotal - $taken);
        $terminated = (string) ($offer->status ?? '') === 'terminated';

        $icons = '';
        for ($seat = 0; $seat < $seatsTotal; $seat++) {
            $icons .= $this->seatUserIcon($seat < $taken);
        }

        $created = $offer->created_at !== null ? $offer->created_at->format('Y/m/d') : '';
        $glyph = $brand !== null ? $this->brandIconHtml($brand) : '';

        // No thread, no Chat button: an offer with no current members
        // (a fresh draft) has no conversation to open.
        $chatButton = $this->sharedOfferHasChatThread($offer)
            ? '<a href="/en/my-chat/subscription/'.e($identifier).'" class="subscriptions-item-button">'."\n"
                .self::CHAT_SVG."\n"
                .'Chat                    </a>'."\n"
            : '';

        return '<div class="subscriptions-item"'.($terminated ? ' data-canceled style="display: none;"' : '').'>'."\n"
            .'<div class="subscriptions-item-left">'."\n"
            .'<div class="subscriptions-item-header">'."\n"
            .'<div class="subscriptions-item-avatar" style="--sub-color: '.e($brand !== null ? $this->brandBackground($brand) : '#112e45').';">'."\n"
            .$glyph."\n"
            .'</div>'."\n"
            .'<div class="subscriptions-item-infos">'."\n"
            .'<div class="subscriptions-item-info mb-sm-2 mb-md-0">'."\n"
            .'<div class="subscriptions-item-name">'.e($name).'</div>'."\n"
            .'</div>'."\n"
            .'<div class="subscriptions-item-states">'."\n"
            .'<span class="pill pill-navy">'.e($this->sharedOfferVisibilityLabel($offer)).'</span>'."\n"
            .'<span class="pill pill-green">'."\n"
            .'Created on '.$created.'    </span>'."\n"
            .'</div>'."\n"
            .'</div>'."\n"
            .'</div>'."\n"
            .'<div class="subscriptions-item-content">'."\n"
            .'<div class="subscriptions-item-content-users">'."\n"
            .$icons
            .'</div>'."\n"
            .$remaining.' seat'.($remaining === 1 ? '' : 's').' remaining'."\n"
            .'</div>'."\n"
            .'</div>'."\n"
            .'<div class="subscriptions-item-right">'."\n"
            .'<div class="subscriptions-item-pricing">'."\n"
            .'<span class="subscriptions-item-price"><span class="t3-bold">'.$intPart.'</span>'.$decPart.'</span>'."\n"
            .'<span class="subscriptions-item-frequency">/ month</span>'."\n"
            .'</div>'."\n"
            .'<div class="subscriptions-item-fee">Each subscriber pays you <strong>'.$perSeat.' / month</strong> </div>'."\n"
            .'<div class="subscriptions-item-actions">'."\n"
            .'<a href="/en/my-shared-subscriptions/'.e($identifier).'" class="subscriptions-item-button">'."\n"
            .'View details                    </a>'."\n"
            .$chatButton
            .'</div>'."\n"
            .'</div>'."\n"
            .'</div>'."\n";
    }

    /**
     * Visibility pill text for a shared offer. The mapping comes from
     * the detail capture's update-visibility props: public 1, nearby 2,
     * private 0. A null visibility (offers seeded outside the wizard)
     * reads as Public, the wizard default.
     */
    protected function sharedOfferVisibilityLabel(mixed $offer): string
    {
        return match ((int) ($offer->visibility ?? 1)) {
            0 => 'Private',
            2 => 'Nearby',
            default => 'Public',
        };
    }

    /**
     * Detail and list URL for a shared offer. The original uses the
     * 7-hex hash; offers without a stored one use the derived
     * placeholder from offerHash, which findOwnedSharedOffer resolves.
     */
    protected function sharedOfferUrl(mixed $offer): string
    {
        return '/en/my-shared-subscriptions/'.$this->offerHash($offer);
    }

    /**
     * The offer's total monthly price: the stored total when the wizard
     * set one, otherwise the per-seat price times the seat count (the
     * share mode 1 inverse of eachPays). Demo offers seeded outside
     * the wizard carry no total price.
     */
    protected function sharedOfferTotalPrice(mixed $offer): float
    {
        $total = (float) ($offer->total_price ?? 0);

        if ($total > 0) {
            return $total;
        }

        $seats = max(1, (int) ($offer->total_seats ?? $offer->friends ?? 1));

        return round((float) ($offer->price ?? 0) * $seats, 2);
    }

    /**
     * Split a formatted price into the bold integer part (with symbol)
     * and the decimals part, the way the real rows render the big price:
     * <span class="t3-bold">£15</span>.58. The decimals part is empty
     * for a whole amount.
     *
     * @return array{0: string, 1: string}
     */
    protected function splitPriceParts(float $amount, ?string $symbol): array
    {
        $formatted = $this->formatPriceAmount($amount, $symbol);
        $symbolText = (string) ($symbol ?? '');
        $number = mb_substr($formatted, mb_strlen($symbolText));

        if (! str_contains($number, '.')) {
            return [$formatted, ''];
        }

        [$int, $dec] = explode('.', $number, 2);

        return [$symbolText.$int, '.'.$dec];
    }

    /**
     * Current co-subscribers on an offer: current members excluding the
     * owner member. The owner member row is not a subscriber.
     */
    protected function sharedOfferSubscriberCount(mixed $offer): int
    {
        try {
            if (! class_exists(OfferMember::class) || ! Schema::hasTable('offer_members')) {
                return 0;
            }

            return OfferMember::where('offer_id', $offer->id)
                ->where('status', OfferMember::STATUS_CURRENT)
                ->where(function ($query) use ($offer): void {
                    $query->whereNull('user_id')->orWhere('user_id', '!=', $offer->user_id);
                })
                ->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Whether an offer has a chat thread at all: at least one current
     * member (owner counts). Offers without one, such as a draft with
     * no members yet, render no Chat button, because the thread URL
     * would have nothing to open.
     */
    protected function sharedOfferHasChatThread(mixed $offer): bool
    {
        try {
            if (! class_exists(OfferMember::class) || ! Schema::hasTable('offer_members')) {
                return false;
            }

            return OfferMember::where('offer_id', $offer->id)
                ->where('status', OfferMember::STATUS_CURRENT)
                ->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * One seat icon for the remaining-seats row: a filled (coloured)
     * icon for a taken seat, a plain one for a free seat.
     */
    protected function seatUserIcon(bool $taken): string
    {
        return '<svg width="30" height="30" viewBox="0 0 30 30" fill="currentColor" xmlns="http://www.w3.org/2000/svg"'.($taken ? ' class="colored"' : '').'>'
            .'<path d="'.self::SEAT_USER_PATH.'" />'
            .'</svg>'."\n";
    }

    /**
     * Render the Discover cards on My shared subscriptions from the
     * database, one card per active brand that has an active template,
     * priced at that brand's lowest active template price_with_fees,
     * the same lowest-price logic the homepage calculator uses.
     *
     * The section was never replaced before, so it always showed the
     * raw capture: nine cards whose brands mostly do not exist in the
     * database at all, with prices that do not match any template, so
     * no card could show a real starting price. The captured cards are
     * kept only when the templates table has no rows at all (never
     * seeded). When templates exist but no brand has a priced active
     * template, the section renders empty instead of showing captured
     * cards with stale prices. A brand without an active template is
     * skipped, since there is no real price to show for it. The card
     * markup copies the captured "from" card, and the button links to
     * the brand page instead of the captured dead link.
     */
    protected function replaceDiscoverSuggestions(string $html): string
    {
        if (! str_contains($html, 'subscriptions-suggestions-items')) {
            return $html;
        }

        try {
            if (! class_exists(Template::class) || ! Schema::hasTable('templates')) {
                return $html;
            }

            $brands = $this->calculatorBrands();

            if ($brands->isEmpty()) {
                return $this->tableHasRows('templates') ? $this->replaceDiscoverCards($html, '') : $html;
            }

            $cards = '';
            foreach ($brands as $brand) {
                $template = $brand->lowestTemplate;
                if ($template === null || $template->price_with_fees === null) {
                    continue;
                }

                $cards .= $this->renderDiscoverSuggestionCard($brand, $template);
            }

            return $this->replaceDiscoverCards($html, $cards);
        } catch (\Throwable) {
            return $html;
        }
    }

    protected function replaceDiscoverCards(string $html, string $cards): string
    {
        return preg_replace_callback(
            '/(<div class="subscriptions-suggestions-items row">)(.*?)(<\/div>\s*<\/div>\s*<\/div>\s*<\/div>\s*<\/div>\s*<\/div>\s*<div class="dash-sheet-backdrop)/s',
            static fn (array $matches): string => $matches[1]."\n".$cards.$matches[3],
            $html,
            1,
        ) ?? $html;
    }

    protected function renderDiscoverSuggestionCard(mixed $brand, mixed $template): string
    {
        $name = e((string) $brand->name);
        $url = e($this->brandUrl($brand));
        $background = $this->brandBackground($brand);
        $price = e($this->formatPriceAmount(
            (float) $template->price_with_fees,
            $this->brandCurrencySymbol($brand, $template->currency ?? null),
        ));
        $letter = e(mb_substr((string) $brand->name, 0, 1));

        return '<div class="col-sm-6 col-12 col-md-4">'."\n"
            .'<div class="subscriptions-suggestion-item">'."\n"
            .'<div class="subscriptions-suggestion-item-top">'."\n"
            .'<div class="subscriptions-suggestion-item-circle" style="background: '.e($background).';">'."\n"
            .$letter."\n"
            .'</div>'."\n"
            .'<div class="subscriptions-suggestion-item-info">'."\n"
            .'<div class="subscriptions-suggestion-item-name">'.$name.'</div>'."\n"
            .'<div class="subscriptions-suggestion-item-description">from</div>'."\n"
            .'</div>'."\n"
            .'</div>'."\n"
            .'<div class="subscriptions-suggestion-item-pricing">'."\n"
            .'<span class="subscriptions-suggestion-item-price">'.$price.'</span>'."\n"
            .'<span class="subscriptions-suggestion-item-frequency">/ month</span>'."\n"
            .'</div>'."\n"
            .'<div class="subscriptions-suggestion-item-actions">'."\n"
            .'<a href="'.$url.'" class="subscriptions-suggestion-item-button" style="border-color: '.e($background).';color: '.e($background).';">'."\n"
            .'<span>Discover</span><span class="subscriptions-suggestion-item-arrow">'."\n"
            .self::SUGGESTION_ARROW_SVG.'</span>'."\n"
            .'</a>'."\n"
            .'</div>'."\n"
            .'</div>'."\n"
            .'</div>'."\n";
    }

    /**
     * The waiting page help entries live in the mobile dash sheet and
     * the menu: Messages with its subtitle, Contact Sharesub Support and
     * Help Center, neither of which has body text in the capture.
     */
    protected function replaceWaitingHelp(string $html): string
    {
        $title1 = $this->dynamicSetting('waiting_help_1_title');
        $text1 = $this->dynamicSetting('waiting_help_1_text');
        if ($title1 !== null) {
            $html = preg_replace_callback(
                '/(<span class="dash-sheet-title-row">\s*<span>)[^<]*(<\/span>)/',
                static fn (array $matches): string => $matches[1].e($title1).$matches[2],
                $html,
                1,
            ) ?? $html;
        }
        if ($text1 !== null) {
            $html = preg_replace_callback(
                '/(<span class="dash-sheet-subtitle">)[^<]*(<\/span>)/',
                static fn (array $matches): string => $matches[1].e($text1).$matches[2],
                $html,
                1,
            ) ?? $html;
        }

        $title2 = $this->dynamicSetting('waiting_help_2_title');
        if ($title2 !== null) {
            $html = preg_replace_callback(
                '/(<a href="#offline" target="_blank" rel="noopener" class="dash-sheet-link" data-open-support>.*?<span>)[^<]*(<\/span>)/s',
                static fn (array $matches): string => $matches[1].e($title2).$matches[2],
                $html,
                1,
            ) ?? $html;
        }

        $title3 = $this->dynamicSetting('waiting_help_3_title');
        if ($title3 !== null) {
            $html = preg_replace_callback(
                '/(<div class="menu-blog">\s*<a href="#offline" target="_blank">)[^<]*(<\/a>)/',
                static fn (array $matches): string => $matches[1].e($title3).$matches[2],
                $html,
                1,
            ) ?? $html;
        }

        return $html;
    }

    protected function replaceWalletPolicies(string $html): string
    {
        $policy1 = $this->dynamicSetting('wallet_policy_1');
        if ($policy1 !== null) {
            $rendered = e($policy1);
            $rendered = $this->wrapPhrase($rendered, 'are automatically transferred to your account on the 9th of each month', 'b');
            $html = preg_replace_callback(
                '/(<p class="p3-regular my-4">).*?(<\/p>)/s',
                static fn (array $matches): string => $matches[1]."\n                                ".$rendered.$matches[2],
                $html,
                1,
            ) ?? $html;
        }

        $policy2 = $this->dynamicSetting('wallet_policy_2');
        if ($policy2 !== null) {
            $html = preg_replace_callback(
                '/(<a href="\/en\/my-account\.html" class="text-dark bg-red-3[^>]*>)\s*[^<]+(\s*<span class="ml-auto">)/s',
                static fn (array $matches): string => $matches[1]."\n            ".e($policy2).$matches[2],
                $html,
                1,
            ) ?? $html;
        }

        return $html;
    }

    protected function replaceChatEmpty(string $html): string
    {
        $title = $this->dynamicSetting('chat_empty_title');
        if ($title !== null) {
            $html = preg_replace_callback(
                '/(<div class="chat-noconv-title">)[^<]*(<\/div>)/',
                static fn (array $matches): string => $matches[1].e($title).$matches[2],
                $html,
                1,
            ) ?? $html;
        }

        $text = $this->dynamicSetting('chat_empty_text');
        if ($text !== null) {
            $html = preg_replace_callback(
                '/(<div class="chat-noconv-text">)[^<]*(<\/div>)/',
                static fn (array $matches): string => $matches[1].e($text).$matches[2],
                $html,
                1,
            ) ?? $html;
        }

        return $html;
    }


    protected function replaceCatalogueBlocks(string $html): string
    {
        if (! str_contains($html, 'category-cards')) {
            return $html;
        }

        $byName = $this->allCategoriesByName();

        if ($byName === []) {
            return $html;
        }

        $brandsSeeded = $this->tableHasRows('brands');
        $seen = [];
        $html = preg_replace_callback(
            '/<div class="category-card">\s*<h2 class="category-card-title">\s*(<svg.*?<\/svg>)?\s*<span>([^<]+)<\/span>\s*<\/h2>\s*<div class="category-card-wrapper">\s*<p class="category-card-description">.*?<\/p>\s*<ul class="category-card-items">.*?<\/ul>\s*<a href="[^"]*" class="btn btn-sharesub blue">See all<\/a>\s*<\/div>\s*<\/div>/s',
            function (array $matches) use ($byName, &$seen, $brandsSeeded): string {
                $name = html_entity_decode(trim($matches[2]), ENT_QUOTES, 'UTF-8');
                $key = strtolower($name);
                $seen[$key] = true;

                $category = $byName[$key] ?? null;
                if ($category === null) {
                    return $matches[0];
                }

                if (! $category->is_active) {
                    return '';
                }

                $brands = $this->activeBrandsForCategory((int) $category->id);
                if ($brands->isEmpty()) {
                    // An active category with no active brands hides its
                    // card instead of showing the captured brands. The
                    // captured card stays only when no brand was ever
                    // seeded.
                    return $brandsSeeded ? '' : $matches[0];
                }

                $block = $matches[0];

                if (! empty($category->description)) {
                    $description = e((string) $category->description);
                    $block = preg_replace_callback(
                        '/<p class="category-card-description">.*?<\/p>/s',
                        static fn (array $inner): string => '<p class="category-card-description">'.$description.'</p>',
                        $block,
                        1,
                    ) ?? $block;
                }

                $seeAllLabel = $this->dynamicSetting('catalogue_see_all_label');
                if ($seeAllLabel !== null) {
                    $block = preg_replace_callback(
                        '/(<a href="[^"]*" class="btn btn-sharesub blue">)[^<]*(<\/a>)/',
                        static fn (array $inner): string => $inner[1].e($seeAllLabel).$inner[2],
                        $block,
                        1,
                    ) ?? $block;
                }

                if (! empty($category->icon_svg)) {
                    if ($matches[1] !== '') {
                        $block = str_replace($matches[1], (string) $category->icon_svg, $block);
                    } else {
                        $icon = (string) $category->icon_svg;
                        $block = preg_replace_callback(
                            '/(<h2 class="category-card-title">\s*)/',
                            static fn (array $inner): string => $inner[1].$icon,
                            $block,
                            1,
                        ) ?? $block;
                    }
                }

                $items = '';
                foreach ($brands as $brand) {
                    $items .= $this->renderBrandCard($brand, 'catalogue');
                }

                return preg_replace_callback(
                    '/<ul class="category-card-items">.*?<\/ul>/s',
                    static fn (array $inner): string => '<ul class="category-card-items">'."\n".$items.'</ul>',
                    $block,
                    1,
                ) ?? $block;
            },
            $html,
        ) ?? $html;

        $extra = '';
        foreach ($byName as $key => $category) {
            if (isset($seen[$key]) || ! $category->is_active) {
                continue;
            }

            $brands = $this->activeBrandsForCategory((int) $category->id);
            if ($brands->isEmpty()) {
                continue;
            }

            $items = '';
            foreach ($brands as $brand) {
                $items .= $this->renderBrandCard($brand, 'catalogue');
            }

            $seeAll = $category->slug === 'house' ? '/en/subscriptions/house.html' : '#offline';
            $seeAllLabel = $this->dynamicSetting('catalogue_see_all_label') ?? 'See all';
            $extra .= '<div class="category-card">'."\n"
                .'<h2 class="category-card-title">'."\n"
                .((string) ($category->icon_svg ?? ''))."\n"
                .'<span>'.e((string) $category->name).'</span>'."\n"
                .'</h2>'."\n"
                .'<div class="category-card-wrapper">'."\n"
                .(! empty($category->description) ? '<p class="category-card-description">'.e((string) $category->description).'</p>'."\n" : '')
                .'<ul class="category-card-items">'."\n".$items.'</ul>'."\n"
                .'<a href="'.$seeAll.'" class="btn btn-sharesub blue">'.e($seeAllLabel).'</a>'."\n"
                .'</div>'."\n"
                .'</div>'."\n";
        }

        if ($extra === '') {
            return $html;
        }

        return preg_replace_callback(
            '/(<\/div>\s*<div class="category-actions">)/',
            static fn (array $matches): string => $extra.$matches[1],
            $html,
            1,
        ) ?? $html;
    }

    protected function replaceHouseCards(string $html): string
    {
        if (! str_contains($html, 'brand-cards')) {
            return $html;
        }

        try {
            $category = Category::where('slug', 'house')->first();
        } catch (\Throwable) {
            return $html;
        }

        if ($category === null) {
            return $html;
        }

        $brands = $category->is_active
            ? $this->activeBrandsForCategory((int) $category->id)
            : collect();

        if ($category->is_active) {
            $housePrefix = $this->dynamicSetting('house_title_prefix') ?? 'Subscription sharing';
            $html = preg_replace_callback(
                '/(<h1 class="brand-header-title col-12"><span>)[^<]*(<\/span>)\s*[^<]+(<\/h1>)/',
                static fn (array $matches): string => $matches[1].e($housePrefix).$matches[2].' '.e((string) $category->name).$matches[3],
                $html,
                1,
            ) ?? $html;

            if (! empty($category->description)) {
                $houseDescription = e((string) $category->description);
                $html = preg_replace_callback(
                    '/(<p class="brand-content-description">).*?(<\/p>)/s',
                    static fn (array $matches): string => $matches[1].$houseDescription.$matches[2],
                    $html,
                    1,
                ) ?? $html;
            }

            if (! empty($category->icon_svg)) {
                $icon = (string) $category->icon_svg;
                $html = preg_replace_callback(
                    '/(<h2 class="brand-content-title">\s*)<svg.*?<\/svg>/s',
                    static fn (array $matches): string => $matches[1].$icon,
                    $html,
                    1,
                ) ?? $html;
            }

            $html = preg_replace_callback(
                '/(<h2 class="brand-content-title">.*?<\/svg>)\s*[^<]+(<\/h2>)/s',
                static fn (array $matches): string => $matches[1].' '.e((string) $category->name).$matches[2],
                $html,
                1,
            ) ?? $html;
        }

        $cards = '';
        foreach ($brands as $brand) {
            $cards .= $this->renderBrandCard($brand, 'grid');
        }

        return preg_replace_callback(
            '/(<div class="row brand-cards">)(.*?)(<div class="category-actions">)/s',
            static fn (array $matches): string => $matches[1]."\n".$cards.$matches[3],
            $html,
            1,
        ) ?? $html;
    }

    /**
     * Substitute one brand into the Amazon detail template. Only the
     * header, title and meta description are rewritten. When the brand
     * has active offers, the captured offer rows are replaced with
     * database rendered cards in the same markup, and the cheapest
     * active offer price drives the header join price. When offers
     * exist but none are active for the brand, the offers list renders
     * empty. The captured rows stay only when the offers table has no
     * rows at all. A set brand tagline is rendered in the detail header.
     */
    protected function applyBrandDetail(string $html, mixed $brand): string
    {
        if ($brand === null) {
            return $html;
        }

        $name = (string) $brand->name;
        $price = $this->formatBrandPrice($brand);
        $offers = $this->activeOffersForBrand((int) $brand->id);
        $headerPrice = $price;
        if ($offers->isNotEmpty()) {
            $cheapest = $offers->sortBy('price')->first();
            $headerPrice = $this->formatOfferPrice($cheapest, $brand);
        }
        $category = null;

        try {
            $category = $brand->category;
        } catch (\Throwable) {
            $category = null;
        }

        $html = preg_replace_callback(
            '/(<title>)([^<]*)(<\/title>)/s',
            static fn (array $matches): string => $matches[1].'Share my '.e($name).' subscription on Sharesub'.$matches[3],
            $html,
            1,
        ) ?? $html;

        $html = preg_replace_callback(
            '/(<meta name="description" content=")([^"]*)(")/',
            function (array $matches) use ($name, $headerPrice): string {
                $content = str_replace('Amazon', $name, $matches[2]);
                // The captured meta description carries the captured
                // Amazon price token; replace it with this brand's
                // computed header price.
                $content = $this->replaceCapturedPrice($content, $headerPrice);

                return $matches[1].e($content).$matches[3];
            },
            $html,
            1,
        ) ?? $html;

        if ($category !== null) {
            $html = preg_replace_callback(
                '/(<div class="model-header-breadcrumb">\s*<a href="#offline">)[^<]*(<\/a>)/s',
                static fn (array $matches): string => $matches[1].e((string) $category->name).$matches[2],
                $html,
                1,
            ) ?? $html;
        }

        $html = preg_replace_callback(
            '/(<div class="model-header-breadcrumb">.*?<span>&gt;<\/span>\s*<span>)[^<]*(<\/span>)/s',
            static fn (array $matches): string => $matches[1].e($name).$matches[2],
            $html,
            1,
        ) ?? $html;

        // The detail hero keeps its captured styling. The brand colour
        // is only for homepage hero slides and brand card titles; it
        // must never paint the detail hero.

        $icon = $this->brandIconHtml($brand);
        $html = preg_replace_callback(
            '/(<span class="model-header-name-brand">)\s*[^<]*(<\/span>)/',
            static function (array $matches) use ($name, $icon): string {
                $inner = $icon !== '' ? $icon.' ' : '';

                return $matches[1].' '.$inner.e($name).$matches[2];
            },
            $html,
            1,
        ) ?? $html;

        if ($brand->discount_percent !== null) {
            $html = preg_replace_callback(
                '/(<span>Save up to <strong>)[^<]*(<\/strong><\/span>)/',
                static fn (array $matches): string => $matches[1].e((string) $brand->discount_percent).'%'.$matches[2],
                $html,
                1,
            ) ?? $html;
        }

        $lead = $brand->short_description ?? $brand->tagline;
        if ($lead !== null && $lead !== '') {
            $html = preg_replace_callback(
                '/(<div class="model-header-lead" data-more="See more">).*?(<\/div>)/s',
                static fn (array $matches): string => $matches[1].e((string) $lead).$matches[2],
                $html,
                1,
            ) ?? $html;
        }

        if (! empty($brand->tagline) && (string) $brand->tagline !== (string) ($lead ?? '')) {
            // The tagline was seeded from the captured meta description,
            // so it can carry the same captured price token.
            $tagline = $this->replaceCapturedPrice((string) $brand->tagline, $headerPrice);
            $html = preg_replace_callback(
                '/(<div class="model-header-lead"[^>]*>.*?<\/div>)/s',
                static fn (array $matches): string => $matches[1]."\n".'<div class="model-header-tagline">'.e($tagline).'</div>',
                $html,
                1,
            ) ?? $html;
        }

        $html = preg_replace_callback(
            '/(<div class="model-header-offer-card model-header-offer-card--join">.*?<strong>)([^<]*)(<small>\/ month<\/small><\/strong>)/s',
            static fn (array $matches): string => $matches[1].e($headerPrice).' '.$matches[3],
            $html,
            1,
        ) ?? $html;

        $html = $this->replaceOfferCards($html, $offers, $brand);

        $html = str_replace('Amazon seats available', e($name).' seats available', $html);
        $html = str_replace('Before joining a group Amazon', 'Before joining a group '.e($name), $html);
        $html = str_replace('Blog posts about Amazon', 'Blog posts about '.e($name), $html);
        $html = str_replace('invitation link Amazon', 'invitation link '.e($name), $html);

        return $html;
    }

    protected function activeOffersForBrand(int $brandId): mixed
    {
        try {
            if (! class_exists(Offer::class) || ! Schema::hasTable('offers')) {
                return collect();
            }

            return Offer::where('brand_id', $brandId)->where('is_active', true)->where('status', 'active')->orderBy('sort_order')->orderBy('id')->get();
        } catch (\Throwable) {
            return collect();
        }
    }

    /**
     * Replace the captured offer rows inside #subscriptions-list with
     * database rendered cards. The list renders empty when offers
     * exist but none are active for this brand. The captured rows stay
     * only when the offers table has no rows at all.
     */
    protected function replaceOfferCards(string $html, mixed $offers, mixed $brand): string
    {
        if ($offers->isEmpty() && ! $this->tableHasRows('offers')) {
            return $html;
        }

        $cards = '';
        foreach ($offers as $offer) {
            $cards .= $this->renderOfferCard($offer, $brand);
        }

        return preg_replace_callback(
            '/(<div class="result subscriptions" id="subscriptions-list" data-pagination="10">).*?(<\/div>\s*<div class="text-center">)/s',
            static fn (array $matches): string => $matches[1]."\n".$cards.$matches[2],
            $html,
            1,
        ) ?? $html;
    }

    /**
     * One offer card, using the same markup and classes as the captured
     * offer rows. Badges and registration dates are not stored, so those
     * spans are left out rather than invented. The British account line
     * is part of the captured row template and is kept as captured.
     */
    protected function renderOfferCard(mixed $offer, mixed $brand): string
    {
        $seller = trim((string) ($offer->seller_name ?? ''));
        $title = e((string) $offer->title);
        // The offer card price symbol comes from the currencies table by
        // the offer (then brand) currency code; the stored brand symbol
        // is only a fallback for a code with no currency row.
        $symbol = Currency::symbolFor($offer->currency ?? $brand->currency ?? null);
        if ($symbol === null || $symbol === '') {
            $symbol = (string) ($brand->currency_symbol ?? '');
        }
        if ($symbol === '') {
            $symbol = Currency::defaultSymbol() ?? '';
        }
        $parts = explode('.', number_format((float) $offer->price, 2, '.', ''));
        $price = '<span class="join-action-header-price"><span class="t3-bold">'.e($symbol.$parts[0]).'</span>.'.$parts[1].'</span>';
        $initials = $this->sellerInitials($seller, (string) $brand->name);
        $titleAttr = $seller !== '' ? ' title="'.e($seller.' shares '.(string) $offer->title).'"' : '';

        $card = '<div class="join-content">'."\n"
            .'<div class="join-content-header">'."\n"
            .'<div class="join-content-header-left">'."\n"
            .'<div class="join-content-avatar">'.e($initials).'</div>'."\n"
            .'<div class="join-content-user">'."\n"
            .'<span class="join-content-name"'.$titleAttr.'>'.e($seller).'</span>'."\n"
            .'</div>'."\n"
            .'</div>'."\n"
            .'</div>'."\n"
            .'<hr class="join-content-sep">'."\n"
            .'<div class="row w-100 m-0">'."\n"
            .'<div class="join-content-article col-12">'."\n"
            .'<div class="join-content-description no-space">'."\n"
            .'<div class="join-content-title">'."\n"
            .'Sharing <strong>'.$title.'</strong>'."\n"
            .'</div>'."\n"
            .'<div class="join-content-details">'."\n";

        if ($offer->seats_available !== null) {
            $seats = (int) $offer->seats_available;
            $card .= '<div class="join-content-detail">'."\n"
                .'<svg width="30" height="30" viewBox="0 0 30 30" fill="currentColor" xmlns="http://www.w3.org/2000/svg" class="text-green"><path d="M15 5C16.3261 5 17.5979 5.52678 18.5355 6.46447C19.4732 7.40215 20 8.67392 20 10C20 11.3261 19.4732 12.5979 18.5355 13.5355C17.5979 14.4732 16.3261 15 15 15C13.6739 15 12.4021 14.4732 11.4645 13.5355C10.5268 12.5979 10 11.3261 10 10C10 8.67392 10.5268 7.40215 11.4645 6.46447C12.4021 5.52678 13.6739 5 15 5V5ZM15 17.5C20.525 17.5 25 19.7375 25 22.5V25H5V22.5C5 19.7375 9.475 17.5 15 17.5Z" /></svg>'."\n"
                .'<span>'.$seats.' seat'.($seats === 1 ? '' : 's').' remaining</span>'."\n"
                .'</div>'."\n";
        }

        $card .= '<div class="join-content-detail">'."\n"
            .'<span class="join-content-detail-flag"><div class="iti__flag iti__gb"></div></span>'."\n"
            .'<span>British account</span>'."\n"
            .'</div>'."\n"
            .'</div>'."\n"
            .'</div>'."\n"
            .'</div>'."\n"
            .'<div class="join-action col-12">'."\n"
            .'<div class="join-action-header">'."\n"
            .'<div class="join-action-header-pricing">'."\n"
            .$price."\n"
            .'<span class="join-action-header-frequency">/ month, fees included</span>'."\n"
            .'</div>'."\n"
            .'</div>'."\n"
            .'<div class="join-action-content">'."\n"
            .'<span class="cursor-pointer btn-sharesub blue">'."\n"
            .'Subscribe'."\n"
            .'</span>'."\n"
            .'</div>'."\n"
            .'</div>'."\n"
            .'</div>'."\n"
            .'</div>'."\n";

        return $card;
    }

    protected function sellerInitials(string $seller, string $brandName): string
    {
        $words = preg_split('/\s+/', $seller, -1, PREG_SPLIT_NO_EMPTY);
        if ($words === false || $words === []) {
            return mb_strtoupper(mb_substr($brandName, 0, 1));
        }

        if (count($words) >= 2) {
            return mb_strtoupper(mb_substr($words[0], 0, 1).mb_substr($words[1], 0, 1));
        }

        return mb_strtoupper(mb_substr($words[0], 0, 2));
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
        $html = $this->replaceAccountCountrySelect($html);
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
    <a class="btn-sharesub green" href="/en/create-subscription">Share my subscription</a>
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
            '${1}'.csrf_token().'${2}',
            $html,
        ) ?? $html;
    }

    protected function normalizePageLinks(string $html): string
    {
        return preg_replace_callback(
            '/\b(href|action)=(")?(\/(?:en|cms\/en)\/[^"\'\s>]*?)\.html((?:[?#][^"\']*)?)(")/i',
            static function (array $match): string {
                $path = $match[3];
                if (str_ends_with($path, '/index')) {
                    $path = substr($path, 0, -6);
                }

                return $match[1].'="'.$path.$match[4].'"';
            },
            $html,
        ) ?? $html;
    }
}
