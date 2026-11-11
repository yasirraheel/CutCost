<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Offer;
use App\Models\Template;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * User-side Create Subscription wizard.
 *
 * Server-rendered version of the captured Vue wizard: one step per screen,
 * plain step navigation, and a draft that is saved to the offers table on
 * every forward step. The capture is a Vue component with a Vuex store;
 * this does not revive it, it reproduces the same steps, fields and
 * endpoints as plain forms.
 *
 * Step order, with the conditions from the capture: Country, Brand,
 * Template, Category (custom path only, no brand), Visibility, Position
 * (nearby only), Currency (no template only), Share, Additional
 * information. The guest email step is skipped: these routes are auth
 * only, so the email is the logged in user's email.
 *
 * Price math follows the capture: share 1 splits the plan price over all
 * seats, share 3 splits it over the offered seats only, any other share
 * splits it over the offered seats plus the owner. The stored offer price
 * is the per-seat price a joiner pays; the full plan price lives in
 * total_price.
 */
class CreateSubscriptionController extends Controller
{
    private const MAX_USERS = 12;

    private const TOP_COUNTRIES = ['Belgium', 'France', 'Germany', 'Italy', 'Spain', 'United Kingdom'];

    private const DEFAULT_COUNTRY_NAME = 'United Kingdom';

    private const DEFAULT_LAT = 48.859259117095;

    private const DEFAULT_LNG = 2.3515917396895;

    private const FREQUENCY_MULTIPLIERS = [1 => 52, 2 => 12, 3 => 4, 4 => 1];

    private const FREQUENCY_LABELS = [1 => '/week', 2 => '/month', 3 => '/quarter', 4 => '/year'];

    // ------------------------------------------------------------------
    // Wizard
    // ------------------------------------------------------------------

    public function show(Request $request): View
    {
        $offer = null;

        if ($request->filled('draft')) {
            $offer = Offer::where('hash', (string) $request->input('draft'))
                ->where('user_id', Auth::id())
                ->where('status', 'draft')
                ->first();
            if ($offer !== null) {
                $request->session()->put('create_offer_id', $offer->id);
            }
        }

        // Whether the session already holds a draft the user is working
        // on. When it does not, but the draft cookie still points at one,
        // the wizard offers to resume it (the original's "Continuing to
        // create" modal) instead of silently orphaning it.
        $sessionOfferId = (int) $request->session()->get('create_offer_id', 0);
        $hadWorkingOffer = $sessionOfferId > 0
            && Offer::where('id', $sessionOfferId)->where('user_id', Auth::id())->where('status', 'draft')->exists();

        if ($offer === null) {
            $offer = $this->workingOffer($request);
        }

        $this->applyDeepLink($request, $offer);

        $restoredDraft = null;
        if (! $request->filled('draft') && ! $hadWorkingOffer) {
            $cookieHash = (string) $request->cookie('draft', '');
            if ($cookieHash !== '') {
                $cookieDraft = Offer::where('hash', $cookieHash)
                    ->where('user_id', Auth::id())
                    ->where('status', 'draft')
                    ->first();
                if ($cookieDraft !== null && (int) $cookieDraft->id !== (int) $offer->id) {
                    $restoredDraft = $cookieDraft;
                }
            }
        }

        $steps = $this->steps($offer);
        $step = $this->requestedStep($request, $steps);

        return $this->renderWizard($request, $offer, $steps, $step, $restoredDraft);
    }

    public function saveStep(Request $request): RedirectResponse
    {
        $offer = $this->workingOffer($request);
        $steps = $this->steps($offer);
        $step = (string) $request->input('step', '');

        if (! isset($steps[$step])) {
            return redirect()->route('user.create-subscription');
        }

        $this->validateStep($request, $offer, $step);
        $this->applyStep($request, $offer, $step);
        $offer->save();

        $steps = $this->steps($offer->fresh() ?? $offer);
        $keys = array_keys($steps);
        $position = array_search($step, $keys, true);
        $next = $keys[min($position + 1, count($keys) - 1)];

        // Keep the draft cookie in sync, exactly as storeDraft does: the
        // returning-user "Continuing to create" modal is driven by this
        // cookie when the session holds no working offer.
        return redirect()->route('user.create-subscription', ['step' => $next])
            ->withCookie(cookie('draft', (string) $offer->hash, 30240, '/', null, $request->secure(), false));
    }

    public function publish(Request $request): RedirectResponse
    {
        $offer = $this->workingOffer($request);

        $data = $this->validatePublish($request, $offer);
        $this->applyPublish($request, $offer, $data);

        $request->session()->forget('create_offer_id');

        return redirect()->route('user.shared-subscriptions')
            ->with('status', 'Your subscription is now shared.');
    }

    public function edit(Request $request, Offer $offer): View
    {
        $this->authorizeOwner($offer);

        $request->session()->put('create_offer_id', $offer->id);

        $steps = $this->steps($offer);
        $step = $this->requestedStep($request, $steps);

        return $this->renderWizard($request, $offer, $steps, $step);
    }

    public function update(Request $request, Offer $offer): RedirectResponse
    {
        $this->authorizeOwner($offer);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string'],
            'share' => ['required', 'integer', 'in:1,3'],
            'friends' => ['required', 'integer', 'min:1', 'max:'.self::MAX_USERS],
            'total_seats' => ['required', 'integer', 'min:2', 'max:'.self::MAX_USERS],
            'total_price' => ['required', 'numeric', 'min:0.01'],
            'frequency' => ['required', 'integer', 'in:1,2,3,4'],
            'visibility' => ['required', 'integer', 'in:0,1,2'],
            'currency' => ['required', 'string', 'size:3', 'exists:currencies,code'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        if ((int) $validated['visibility'] === 2 && ($validated['lat'] === null || $validated['lng'] === null)) {
            return back()->withErrors(['lat' => 'A position is required for a nearby subscription.'])->withInput();
        }

        $validated['title'] = $validated['name'];
        $validated['seats_available'] = $validated['friends'];
        $validated['seats_total'] = $validated['total_seats'];
        $validated['price'] = $this->eachPays(
            (int) $validated['share'],
            (float) $validated['total_price'],
            (int) $validated['friends'],
            (int) $validated['total_seats'],
        );

        $offer->fill($validated);
        $offer->save();

        return redirect()->route('user.shared-subscriptions')
            ->with('status', 'Your subscription was updated.');
    }

    public function destroy(Request $request, Offer $offer): RedirectResponse
    {
        $this->authorizeOwner($offer);

        $offer->delete();

        if ((int) $request->session()->get('create_offer_id') === (int) $offer->id) {
            $request->session()->forget('create_offer_id');
        }

        // The draft modal's Cancel asks to start fresh in the wizard
        // instead of leaving for the subscriptions list.
        $response = $request->input('redirect') === 'create'
            ? redirect()->route('user.create-subscription')
            : redirect()->route('user.shared-subscriptions');

        return $response->with('status', 'Your subscription was deleted.')
            ->withoutCookie('draft');
    }

    // ------------------------------------------------------------------
    // JSON endpoints, same shapes as the capture
    // ------------------------------------------------------------------

    public function getBrands(Request $request): JsonResponse
    {
        $brands = Brand::where('is_active', true)
            ->when($request->filled('search'), function ($query) use ($request): void {
                $query->where('name', 'like', '%'.(string) $request->input('search').'%');
            })
            ->orderBy('sort_order')->orderBy('id')->get();

        return response()->json([
            'done' => true,
            'brands' => $brands->map(fn (Brand $brand): array => $this->brandPayload($brand))->all(),
        ]);
    }

    public function getTemplates(Request $request): JsonResponse
    {
        $query = Template::where('is_active', true)->orderBy('sort_order')->orderBy('id');

        if ($request->filled('brand_id')) {
            $query->where('brand_id', (int) $request->input('brand_id'));
        }

        if ($request->filled('category_id')) {
            $query->whereHas('brand', function ($brandQuery) use ($request): void {
                $brandQuery->where('category_id', (int) $request->input('category_id'));
            });
        }

        $countryId = $request->filled('country_id') ? (int) $request->input('country_id') : 237;

        return response()->json([
            'done' => true,
            'templates' => $query->get()->map(fn (Template $template): array => $this->templatePayload($template, $countryId))->all(),
        ]);
    }

    public function checkBrand(Request $request): JsonResponse
    {
        $request->validate(['brand_id' => ['required', 'exists:brands,id']]);

        $brand = Brand::find((int) $request->input('brand_id'));
        $allow = $brand !== null && (bool) $brand->is_active;

        $response = ['allow' => $allow];

        if ($allow && $request->boolean('return_brand')) {
            $response['brand'] = $this->brandPayload($brand);
        }

        return response()->json($response);
    }

    public function storeDraft(Request $request): JsonResponse
    {
        $offer = null;

        if ($request->filled('hash')) {
            $offer = Offer::where('hash', (string) $request->input('hash'))
                ->where('user_id', Auth::id())
                ->where('status', 'draft')
                ->first();
        }

        if ($offer === null) {
            $offer = $this->newDraft();
        }

        $this->applyDraftPayload($request, $offer);
        $offer->save();

        $response = response()->json(['done' => true, 'hash' => $offer->hash]);

        if ($request->input('hash') === null || $request->input('hash') === '') {
            $response->withCookie(cookie('draft', (string) $offer->hash, 30240, '/', null, $request->secure(), false));
        }

        return $response;
    }

    public function checkDraft(Request $request): JsonResponse
    {
        $hash = (string) ($request->input('hash') ?? $request->cookie('draft') ?? '');

        $draft = null;
        if ($hash !== '') {
            $draft = Offer::where('hash', $hash)
                ->where('user_id', Auth::id())
                ->where('status', 'draft')
                ->first();
        }

        return response()->json([
            'done' => true,
            'draft' => $draft === null ? null : $this->draftPayload($draft),
        ]);
    }

    // ------------------------------------------------------------------
    // Wizard internals
    // ------------------------------------------------------------------

    /** @return array<string, string> Ordered step key to label. */
    protected function steps(Offer $offer): array
    {
        $steps = [
            'country' => 'Country',
            'brand' => 'Brand',
            'template' => 'Plan',
        ];

        if ($offer->brand_id === null) {
            $steps['category'] = 'Category';
        }

        $steps['visibility'] = 'Visibility';

        if ((int) $offer->visibility === 2) {
            $steps['position'] = 'Position';
        }

        if ($offer->template_id === null) {
            $steps['currency'] = 'Currency';
        }

        $steps['share'] = 'Share';
        $steps['additional'] = 'Additional information';

        return $steps;
    }

    protected function requestedStep(Request $request, array $steps): string
    {
        $step = (string) $request->input('step', '');

        if ($step !== '' && isset($steps[$step])) {
            return $step;
        }

        return array_key_first($steps);
    }

    protected function renderWizard(Request $request, Offer $offer, array $steps, string $step, ?Offer $draft = null): View
    {
        $keys = array_keys($steps);
        $position = array_search($step, $keys, true);
        $previous = $position > 0 ? $keys[$position - 1] : null;

        return view('create-subscription.wizard', [
            'offer' => $offer,
            'draft' => $draft,
            'steps' => $steps,
            'step' => $step,
            'stepIndex' => $position,
            'previousStep' => $previous,
            'progress' => (int) round(100 * ($position + 1) / count($steps)),
            'countries' => $this->countryOptions($request),
            'brands' => $this->brandOptions($request),
            'templates' => $this->templateOptions($offer),
            'categories' => Category::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get(),
            'currencies' => $this->currencyOptions($offer),
            'currencySymbol' => Currency::symbolFor($offer->currency) ?? Currency::defaultSymbol() ?? '',
            'currencySymbols' => Currency::optionsForSelect(),
            'shareMath' => $this->shareMath($offer),
            'maxUsers' => $this->maxUsersFor($offer),
            'defaultLat' => self::DEFAULT_LAT,
            'defaultLng' => self::DEFAULT_LNG,
        ]);
    }

    protected function countryOptions(Request $request): mixed
    {
        $countries = Country::where('is_active', true)->orderBy('name')->get();

        if ($request->filled('search')) {
            $search = mb_strtolower((string) $request->input('search'));
            $countries = $countries->filter(fn (Country $country): bool => str_contains(mb_strtolower($country->name), $search));
        }

        $top = $countries->filter(fn (Country $country): bool => in_array($country->name, self::TOP_COUNTRIES, true))
            ->sortBy(fn (Country $country): int => array_search($country->name, self::TOP_COUNTRIES, true));
        $rest = $countries->reject(fn (Country $country): bool => in_array($country->name, self::TOP_COUNTRIES, true));

        return $top->concat($rest)->values();
    }

    protected function brandOptions(Request $request): mixed
    {
        return Brand::where('is_active', true)
            ->when($request->filled('search'), function ($query) use ($request): void {
                $query->where('name', 'like', '%'.(string) $request->input('search').'%');
            })
            ->orderBy('sort_order')->orderBy('id')->get();
    }

    protected function templateOptions(Offer $offer): mixed
    {
        if ($offer->brand_id === null) {
            return collect();
        }

        return Template::where('brand_id', $offer->brand_id)
            ->where('is_active', true)
            ->orderBy('sort_order')->orderBy('id')->get();
    }

    /**
     * Wizard currency options, from the currencies table (data, not
     * literals). The offered list is the capture's intended rule: the
     * selected country's currency first, then every currency flagged
     * always_offered_in_wizard (EUR). A country currency is offered only
     * when its row exists, is active and is available in the wizard; a
     * country whose currency is not among the seeded rows (e.g. XPF) is
     * offered the always-offered rows only. USD 840 is not seeded, so it
     * can never be offered, matching the capture where the [978, 840]
     * base list filters 840 out against the 21-row prop.
     *
     * @return array<int, array{code: string, symbol: string, name: string}>
     */
    protected function currencyOptions(Offer $offer): array
    {
        $options = [];

        $codes = [];
        $countryCode = strtoupper((string) ($offer->country?->currency ?? ''));
        if ($countryCode !== '') {
            $codes[] = $countryCode;
        }
        foreach (Currency::query()->active()->alwaysOffered()->ordered()->get() as $always) {
            $codes[] = $always->code;
        }

        foreach (array_unique($codes) as $code) {
            $row = Currency::findByCode($code);
            if ($row === null || ! $row->is_active || ! $row->is_available_in_wizard) {
                continue;
            }

            $options[$code] = ['code' => $row->code, 'symbol' => $row->symbol, 'name' => $row->name];
        }

        return array_values($options);
    }

    protected function maxUsersFor(Offer $offer): int
    {
        if ($offer->template !== null && $offer->template->shareable_seats !== null) {
            return min(self::MAX_USERS, (int) $offer->template->shareable_seats);
        }

        return self::MAX_USERS;
    }

    /** @return array<string, float> */
    protected function shareMath(Offer $offer): array
    {
        $total = (float) ($offer->total_price ?? 0);
        $friends = max(1, (int) ($offer->friends ?? 1));
        $totalSeats = max(1, (int) ($offer->total_seats ?? $friends));
        $share = (int) ($offer->share ?? 1);
        $frequency = (int) ($offer->frequency ?? 2);

        // Display math follows the capture exactly: the per-seat price is
        // the plain division (no early rounding); every amount is rounded
        // only when formatted for display, like the original's Uo()
        // formatter. (Verified against the original's own screenshot:
        // £6.99, 1 friend, 2 seats shows each £3.5, receive £3.5, net
        // £3.5, save £41.94.) The stored join price still uses the
        // rounded eachPays() below.
        $divisor = $share === 1
            ? max(1, $totalSeats)
            : ($share === 3 ? max(1, $friends) : max(1, $friends + 1));
        $eachPays = $total > 0 ? $total / $divisor : 0.0;
        $ownerPrice = $eachPays * $friends;

        return [
            'total' => round($total, 2),
            'eachPays' => $eachPays,
            'ownerPrice' => $ownerPrice,
            'netCost' => max(0, $total - $ownerPrice),
            'annualSaving' => $ownerPrice * (self::FREQUENCY_MULTIPLIERS[$frequency] ?? 12),
        ];
    }

    protected function eachPays(int $share, float $total, int $friends, int $totalSeats): float
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

    protected function workingOffer(Request $request): Offer
    {
        $id = (int) $request->session()->get('create_offer_id', 0);

        if ($id > 0) {
            $offer = Offer::where('id', $id)->where('user_id', Auth::id())->where('status', 'draft')->first();
            if ($offer !== null) {
                return $offer;
            }

            $request->session()->forget('create_offer_id');
        }

        $offer = $this->newDraft();
        $request->session()->put('create_offer_id', $offer->id);

        return $offer;
    }

    protected function newDraft(): Offer
    {
        $user = Auth::user();

        return Offer::create([
            'user_id' => $user->id,
            'status' => 'draft',
            'hash' => $this->newHash(),
            'title' => '',
            'price' => 0,
            'currency' => Currency::defaultCode(),
            'share' => 1,
            'friends' => 3,
            'total_seats' => 3,
            'frequency' => 2,
            'email' => $user->email,
            'seller_name' => $user->name,
            'is_active' => false,
        ]);
    }

    protected function newHash(): string
    {
        return substr(bin2hex(random_bytes(4)), 0, 7);
    }

    /**
     * Deep link ?template=<slug>&country=<slug>: preselect the plan and the
     * country, and skip the Brand and Plan steps, as long as the brand
     * allows creation. Falls back to the normal wizard when either lookup
     * fails.
     */
    protected function applyDeepLink(Request $request, Offer $offer): void
    {
        if ($offer->template_id !== null || $offer->brand_id !== null) {
            return;
        }

        if (! $request->filled('template')) {
            return;
        }

        $template = Template::where('slug', (string) $request->input('template'))
            ->where('is_active', true)
            ->first();

        if ($template === null || $template->brand === null || ! $template->brand->is_active) {
            return;
        }

        if ($request->filled('country')) {
            $country = Country::where('is_active', true)->get()
                ->first(fn (Country $row): bool => Str::slug($row->name) === (string) $request->input('country'));
            if ($country !== null) {
                $offer->country_id = $country->id;
            }
        }

        $offer->brand_id = $template->brand_id;
        $offer->custom_price = false;
        $offer->template_id = $template->id;
        $this->applyTemplate($offer, $template);
        $offer->save();
    }

    protected function applyTemplate(Offer $offer, Template $template): void
    {
        $offer->name = $template->name;
        $offer->title = $template->name;
        $offer->description = $template->description;
        $offer->friends = $template->available_seats ?? $template->shareable_seats ?? 1;
        $offer->total_seats = $template->seats ?? $offer->friends;
        $offer->seats_available = $offer->friends;
        $offer->seats_total = $offer->total_seats;
        $offer->share = $template->cost_sharing ?? 1;
        $offer->frequency = $template->frequency ?? 2;
        $offer->visibility = 1;
        $offer->currency = $template->currency ?: Currency::defaultCode();
        $offer->total_price = $template->retail_price ?? $template->price_with_fees ?? 0;
        $offer->price = $this->eachPays(
            (int) $offer->share,
            (float) $offer->total_price,
            (int) $offer->friends,
            (int) $offer->total_seats,
        );
    }

    protected function validateStep(Request $request, Offer $offer, string $step): void
    {
        $maxUsers = $this->maxUsersFor($offer);

        match ($step) {
            'country' => $request->validate(['country_id' => ['required', 'exists:countries,id']]),
            'brand' => $request->validate(['brand_id' => ['nullable', 'exists:brands,id']]),
            'template' => $offer->brand_id !== null
                ? $request->validate(['template_id' => ['nullable', 'exists:templates,id']])
                : $request->validate([
                    'name' => ['required', 'string', 'max:80'],
                    'description' => ['nullable', 'string'],
                ]),
            'category' => $request->validate(['category_id' => ['required', 'exists:categories,id']]),
            'visibility' => $request->validate([
                'visibility' => ['required', 'integer', 'in:0,1,2'],
            ]),
            'position' => $request->validate([
                'lat' => ['required', 'numeric', 'between:-90,90'],
                'lng' => ['required', 'numeric', 'between:-180,180'],
            ]),
            'currency' => $request->validate(['currency' => ['required', 'string', 'size:3', 'exists:currencies,code']]),
            'share' => $request->validate($this->shareRules($offer, $maxUsers)),
            default => null,
        };

        if ($step === 'visibility' && (int) $request->input('visibility') === 0 && ! $request->boolean('confirm_private')) {
            throw ValidationException::withMessages(['confirm_private' => 'Confirm the private choice to continue.']);
        }

        if ($step === 'brand' && $request->filled('brand_id')) {
            $brand = Brand::find((int) $request->input('brand_id'));
            if ($brand === null || ! $brand->is_active) {
                throw ValidationException::withMessages(['brand_id' => 'This brand cannot be shared yet.']);
            }
        }

        if ($step === 'template' && $offer->brand_id !== null && $request->filled('template_id')) {
            $template = Template::where('id', (int) $request->input('template_id'))
                ->where('brand_id', $offer->brand_id)
                ->where('is_active', true)
                ->first();
            if ($template === null) {
                throw ValidationException::withMessages(['template_id' => 'This plan does not belong to the selected brand.']);
            }
        }
    }

    /** @return array<string, mixed> */
    protected function shareRules(Offer $offer, int $maxUsers): array
    {
        $rules = [
            'friends' => ['required', 'integer', 'min:1', 'max:'.$maxUsers],
            'exclude' => ['nullable', 'boolean'],
        ];

        if ($offer->template_id === null) {
            $rules['total_price'] = ['required', 'numeric', 'min:0.01'];
            $rules['total_seats'] = ['required', 'integer', 'min:2', 'max:'.$maxUsers];
        }

        return $rules;
    }

    protected function applyStep(Request $request, Offer $offer, string $step): void
    {
        match ($step) {
            'country' => $offer->country_id = (int) $request->input('country_id'),
            'brand' => $this->applyBrandStep($request, $offer),
            'template' => $this->applyTemplateStep($request, $offer),
            'category' => $offer->category_id = (int) $request->input('category_id'),
            'visibility' => $offer->visibility = (int) $request->input('visibility'),
            'position' => $this->applyPositionStep($request, $offer),
            'currency' => $offer->currency = strtoupper((string) $request->input('currency')),
            'share' => $this->applyShareStep($request, $offer),
            default => null,
        };
    }

    protected function applyBrandStep(Request $request, Offer $offer): void
    {
        if ($request->filled('brand_id')) {
            $offer->brand_id = (int) $request->input('brand_id');
            $offer->custom_price = false;
        } else {
            $offer->brand_id = null;
            $offer->custom_price = true;
        }

        $offer->template_id = null;
    }

    protected function applyTemplateStep(Request $request, Offer $offer): void
    {
        if ($offer->brand_id !== null) {
            if ($request->filled('template_id')) {
                $template = Template::find((int) $request->input('template_id'));
                if ($template !== null) {
                    $offer->template_id = $template->id;
                    $this->applyTemplate($offer, $template);
                }
            } else {
                $offer->template_id = null;
                $offer->visibility = 0;
            }

            return;
        }

        $offer->name = (string) $request->input('name');
        $offer->title = (string) $request->input('name');
        $offer->description = $request->input('description');
    }

    protected function applyPositionStep(Request $request, Offer $offer): void
    {
        $offer->lat = (float) $request->input('lat');
        $offer->lng = (float) $request->input('lng');
    }

    protected function applyShareStep(Request $request, Offer $offer): void
    {
        if ($offer->template_id === null) {
            $offer->total_price = (float) $request->input('total_price');
            $offer->total_seats = (int) $request->input('total_seats');
        }

        $offer->friends = (int) $request->input('friends');
        // The capture clamps the offered seats to the total seats.
        if ($offer->total_seats !== null && $offer->friends > (int) $offer->total_seats) {
            $offer->friends = (int) $offer->total_seats;
        }
        $offer->share = $request->boolean('exclude') ? 3 : ($offer->template?->cost_sharing ?? 1);
        $offer->seats_available = $offer->friends;
        $offer->seats_total = $offer->total_seats;
        $offer->price = $this->eachPays(
            (int) $offer->share,
            (float) $offer->total_price,
            (int) $offer->friends,
            (int) ($offer->total_seats ?? $offer->friends),
        );
    }

    /** @return array<string, mixed> */
    protected function validatePublish(Request $request, Offer $offer): array
    {
        $maxUsers = $this->maxUsersFor($offer);
        $credentialsType = $offer->template?->credentials_type ?? 0;
        $hasCredentials = $credentialsType !== 0;

        $rules = [
            'accept_tos' => ['required', 'accepted'],
            'credentials_email' => ['nullable', 'email'],
            'credentials_login' => ['nullable', 'string'],
            'credentials_password' => ['nullable', 'string'],
            'credentials_link' => ['nullable', 'url'],
            'credentials_address' => ['nullable', 'string'],
            'secured_mode' => ['nullable', 'in:shared,individual'],
            'secured_description_shared' => ['nullable', 'string'],
            'secured_description_individual' => ['nullable', 'array'],
            'secured_description_individual.*' => ['nullable', 'string'],
        ];

        if ($credentialsType === 1) {
            $rules['credentials_login'] = ['required', 'string'];
            $rules['credentials_password'] = ['required', 'string'];
        } elseif ($credentialsType === 2 || $credentialsType === 6) {
            $rules['credentials_email'] = ['required', 'email'];
            $rules['credentials_password'] = ['required', 'string'];
        } elseif ($credentialsType === 3) {
            $rules['credentials_link'] = ['required', 'url'];
        } elseif ($credentialsType === 4) {
            $rules['credentials_link'] = ['required', 'url'];
            $rules['credentials_address'] = ['required', 'string'];
        }

        if (! $hasCredentials) {
            $rules['secured_mode'] = ['required', 'in:shared,individual'];
            $rules['secured_description_shared'] = ['nullable', 'string', 'min:10'];
            $rules['secured_description_individual'] = ['nullable', 'array', 'size:'.max(1, (int) ($offer->friends ?? 1))];
            $rules['secured_description_individual.*'] = ['nullable', 'string', 'min:10'];
        }

        $data = $request->validate($rules);

        $errors = [];
        if ($offer->country_id === null) {
            $errors['country_id'] = 'Choose a country first.';
        }
        if ($offer->name === null || trim($offer->name) === '') {
            $errors['name'] = 'Give your subscription a name first.';
        }
        if ($offer->brand_id === null && $offer->category_id === null) {
            $errors['category_id'] = 'Choose a category first.';
        }
        if ($offer->visibility === null) {
            $errors['visibility'] = 'Choose who can see your subscription first.';
        }
        if ((int) $offer->visibility === 2 && ($offer->lat === null || $offer->lng === null)) {
            $errors['lat'] = 'Add a position for a nearby subscription.';
        }
        if ($offer->currency === null || $offer->currency === '' || Currency::findByCode($offer->currency) === null) {
            $errors['currency'] = 'Choose a currency first.';
        }
        if ($offer->friends === null || (int) $offer->friends < 1 || (int) $offer->friends > $maxUsers) {
            $errors['friends'] = 'Choose how many seats you offer.';
        }
        if ($offer->total_seats === null || (int) $offer->total_seats < 2 || (int) $offer->total_seats > $maxUsers) {
            $errors['total_seats'] = 'Choose the total number of seats.';
        }
        if ($offer->total_price === null || (float) $offer->total_price <= 0) {
            $errors['total_price'] = 'Set a total price first.';
        }

        if (! $hasCredentials) {
            if (($data['secured_mode'] ?? null) === 'shared' && mb_strlen(trim((string) ($data['secured_description_shared'] ?? ''))) < 10) {
                $errors['secured_description_shared'] = 'Describe how you share the subscription, at least 10 characters.';
            }
            if (($data['secured_mode'] ?? null) === 'individual') {
                $individual = (array) ($data['secured_description_individual'] ?? []);
                if (count($individual) !== max(1, (int) ($offer->friends ?? 1))) {
                    $errors['secured_description_individual'] = 'Add one description per offered seat.';
                }
                foreach ($individual as $index => $value) {
                    if (mb_strlen(trim((string) $value)) < 10) {
                        $errors['secured_description_individual.'.$index] = 'Each description needs at least 10 characters.';
                    }
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $data;
    }

    /** @param array<string, mixed> $data */
    protected function applyPublish(Request $request, Offer $offer, array $data): void
    {
        $credentialsType = $offer->template?->credentials_type ?? 0;
        $credentials = [];

        if ($credentialsType === 1) {
            $credentials = [$data['credentials_login'], $data['credentials_password']];
        } elseif ($credentialsType === 2 || $credentialsType === 6) {
            $credentials = [$data['credentials_email'], $data['credentials_password']];
        } elseif ($credentialsType === 3) {
            $credentials = [$data['credentials_link']];
        } elseif ($credentialsType === 4) {
            $credentials = [$data['credentials_link'], $data['credentials_address']];
        }

        $secured = [];
        if ($credentials === []) {
            if (($data['secured_mode'] ?? null) === 'individual') {
                $secured = array_values(array_map(
                    static fn ($value): string => trim((string) $value),
                    (array) ($data['secured_description_individual'] ?? []),
                ));
            } else {
                $secured = [trim((string) ($data['secured_description_shared'] ?? ''))];
            }
        }

        $offer->credentials = $credentials === [] ? null : $credentials;
        $offer->secured_description = $secured === [] ? null : $secured;
        $offer->title = (string) $offer->name;
        $offer->email = $request->user()->email;
        $offer->seller_name = $request->user()->name;
        $offer->seats_available = $offer->friends;
        $offer->seats_total = $offer->total_seats;
        $offer->price = $this->eachPays(
            (int) ($offer->share ?? 1),
            (float) $offer->total_price,
            (int) $offer->friends,
            (int) $offer->total_seats,
        );
        $offer->status = 'active';
        $offer->is_active = true;
        $offer->save();
    }

    // ------------------------------------------------------------------
    // Payload shapes, matching the capture
    // ------------------------------------------------------------------

    /** @return array<string, mixed> */
    protected function brandPayload(Brand $brand): array
    {
        return [
            'id' => (int) $brand->id,
            'name' => (string) $brand->name,
            'description_seo' => (string) ($brand->short_description ?? ''),
            'title_seo' => 'Share my '.(string) $brand->name.' subscription on Sharesub',
            'html_reinsurance' => null,
        ];
    }

    /** @return array<string, mixed> */
    protected function templatePayload(Template $template, int $countryId): array
    {
        // The currency id comes from the currencies table (the template
        // currency, falling back to the default currency row when the
        // template names no row), never from a hardcoded map.
        $currencyId = Currency::isoNumericForPayload($template->currency);
        $price = $template->retail_price !== null
            ? number_format((float) $template->retail_price, 2, '.', '')
            : ($template->price_with_fees !== null ? number_format((float) $template->price_with_fees, 2, '.', '') : '0.00');

        return [
            'id' => (int) $template->id,
            'brand_id' => (int) $template->brand_id,
            'cost_sharing' => (int) ($template->cost_sharing ?? 1),
            'seats' => $template->seats !== null ? (int) $template->seats : null,
            'shareable_seats' => $template->shareable_seats !== null ? (int) $template->shareable_seats : null,
            'credentials_type' => (int) ($template->credentials_type ?? 0),
            'frequency' => (int) ($template->frequency ?? 2),
            'status' => $template->is_active ? 1 : 0,
            'available_seats' => $template->available_seats !== null ? (int) $template->available_seats : null,
            'name' => (string) $template->name,
            'slug' => (string) $template->slug,
            'description' => (string) ($template->description ?? ''),
            'description_seo' => (string) ($template->description_seo ?? ''),
            'title_seo' => (string) ($template->title_seo ?? ''),
            'helper_create' => (string) ($template->helper_create ?? ''),
            'helper_join' => (string) ($template->helper_join ?? ''),
            'prices' => [[
                'template_id' => (int) $template->id,
                'country_id' => $countryId,
                'price' => $price,
                'retail_price' => $template->retail_price !== null ? number_format((float) $template->retail_price, 2, '.', '') : null,
                'currency_id' => $currencyId,
                'fee_boost' => null,
                'seats' => null,
                'shareable_seats' => null,
                'available_seats' => null,
                'visibility' => 7,
            ]],
        ];
    }

    /** @return array<string, mixed> */
    protected function draftPayload(Offer $offer): array
    {
        return [
            'hash' => (string) $offer->hash,
            'country_id' => $offer->country_id !== null ? (int) $offer->country_id : null,
            'brand_id' => $offer->brand_id !== null ? (int) $offer->brand_id : null,
            'template_id' => $offer->template_id !== null ? (int) $offer->template_id : null,
            'category_id' => $offer->category_id !== null ? (int) $offer->category_id : null,
            'public' => $offer->visibility !== null ? (int) $offer->visibility : null,
            'lat' => $offer->lat !== null ? (float) $offer->lat : null,
            'long' => $offer->lng !== null ? (float) $offer->lng : null,
            'email' => $offer->email,
            'seats' => $offer->friends !== null ? (int) $offer->friends : null,
            'total_seats' => $offer->total_seats !== null ? (int) $offer->total_seats : null,
            'friends' => $offer->friends !== null ? (int) $offer->friends : null,
            'share' => (int) ($offer->share ?? 1),
            'price' => $offer->price !== null ? (float) $offer->price : null,
            'total_price' => $offer->total_price !== null ? (float) $offer->total_price : null,
            'custom_price' => (bool) $offer->custom_price,
            'frequency' => (int) ($offer->frequency ?? 2),
            'currency_id' => Currency::isoNumericForPayload($offer->currency),
            'currency' => $offer->currency,
            'name' => $offer->name,
            'description' => $offer->description,
        ];
    }

    protected function applyDraftPayload(Request $request, Offer $offer): void
    {
        $emptyToNull = static fn ($value) => $value === '' || $value === null ? null : $value;

        if ($request->has('brand_id')) {
            $offer->brand_id = $emptyToNull($request->input('brand_id')) !== null ? (int) $request->input('brand_id') : null;
        }
        if ($request->has('template_id')) {
            $offer->template_id = $emptyToNull($request->input('template_id')) !== null ? (int) $request->input('template_id') : null;
        }
        if ($request->has('category_id')) {
            $offer->category_id = $emptyToNull($request->input('category_id')) !== null ? (int) $request->input('category_id') : null;
        }
        if ($request->has('country_id')) {
            $offer->country_id = $emptyToNull($request->input('country_id')) !== null ? (int) $request->input('country_id') : null;
        }
        if ($request->has('currency_id')) {
            $currencyId = $emptyToNull($request->input('currency_id'));
            // A posted id with no currency row (e.g. 840/USD, which the
            // capture's prop does not contain) leaves the currency
            // unchanged, the same as the capture's filter behaviour.
            $currency = $currencyId !== null ? Currency::findByIsoNumeric((int) $currencyId) : null;
            if ($currency !== null) {
                $offer->currency = $currency->code;
            }
        }
        if ($request->has('name')) {
            $offer->name = $emptyToNull($request->input('name'));
            if ($offer->name !== null) {
                $offer->title = $offer->name;
            }
        }
        if ($request->has('description')) {
            $offer->description = $emptyToNull($request->input('description'));
        }
        if ($request->has('share')) {
            $offer->share = (int) $request->input('share');
        }
        if ($request->has('friends')) {
            $offer->friends = (int) $request->input('friends');
        }
        if ($request->has('total_seats')) {
            $offer->total_seats = (int) $request->input('total_seats');
        }
        if ($request->has('custom_price')) {
            $offer->custom_price = (bool) $request->input('custom_price');
        }
        if ($request->has('frequency')) {
            $offer->frequency = (int) $request->input('frequency');
        }
        if ($request->has('lat')) {
            $offer->lat = $emptyToNull($request->input('lat')) !== null ? (float) $request->input('lat') : null;
        }
        if ($request->has('long')) {
            $offer->lng = $emptyToNull($request->input('long')) !== null ? (float) $request->input('long') : null;
        }
        if ($request->has('email')) {
            $email = (string) $request->input('email');
            $offer->email = $email === '' || $email === 'null' || $email === 'undefined' ? null : $email;
        }
        if ($request->has('public')) {
            $offer->visibility = $emptyToNull($request->input('public')) !== null ? (int) $request->input('public') : null;
        }

        // The draft posts the owner receive amount as price. Recover the
        // full plan price from it with the same share math the Share step
        // uses, so the stored per-seat price stays the join price.
        if ($request->has('price') && $request->input('price') !== '' && $request->input('price') !== null) {
            $ownerPrice = (float) $request->input('price');
            $friends = (int) ($offer->friends ?? 0);
            $totalSeats = (int) ($offer->total_seats ?? 0);
            if ($friends > 0 && $totalSeats > 0) {
                $eachPays = round($ownerPrice / $friends, 2);
                $share = (int) ($offer->share ?? 1);
                $total = $share === 1
                    ? $eachPays * $totalSeats
                    : ($share === 3 ? $eachPays * $friends : $eachPays * ($friends + 1));
                $offer->price = $eachPays;
                $offer->total_price = round($total, 2);
            }
        }
        if ($request->has('total_price') && $request->input('total_price') !== '' && $request->input('total_price') !== null) {
            $offer->total_price = (float) $request->input('total_price');
            $offer->price = $this->eachPays(
                (int) ($offer->share ?? 1),
                (float) $offer->total_price,
                max(1, (int) ($offer->friends ?? 1)),
                max(1, (int) ($offer->total_seats ?? 1)),
            );
        }
    }

    protected function authorizeOwner(Offer $offer): void
    {
        abort_unless(
            $offer->user_id !== null && (int) $offer->user_id === (int) Auth::id(),
            403,
        );
    }
}
