<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RendersAdminShell;
use App\Models\Brand;
use App\Models\Currency;
use App\Models\Offer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminOffersController extends Controller
{
    use RendersAdminShell;

    public function index()
    {
        $offers = Offer::with('brand')->orderBy('sort_order')->latest()->paginate(20);

        return $this->adminShell(view('admin.offers.index', compact('offers'))->render(), 'Offers', '/en/admin/offers');
    }

    public function create()
    {
        $offer = new Offer(['currency' => Currency::defaultCode(), 'sort_order' => 0, 'is_active' => true]);
        $brands = Brand::orderBy('name')->get();
        $currencies = Currency::ordered()->get();

        return $this->adminShell(view('admin.offers.create', compact('offer', 'brands', 'currencies'))->render(), 'New offer', '/en/admin/offers');
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        $offer = new Offer($this->attributes($request, $validated));
        // An offer created in the admin panel is a published join offer,
        // not a user draft, so it starts active.
        $offer->status = 'active';
        $offer->save();

        return redirect()->route('admin.offers.index')->with('status', 'Offer has been created.');
    }

    public function edit(Offer $offer)
    {
        $brands = Brand::orderBy('name')->get();
        $currencies = Currency::ordered()->get();

        return $this->adminShell(view('admin.offers.edit', compact('offer', 'brands', 'currencies'))->render(), 'Edit offer', '/en/admin/offers');
    }

    public function update(Request $request, Offer $offer)
    {
        $validated = $request->validate($this->rules($offer));

        $offer->fill($this->attributes($request, $validated));
        $offer->save();

        return redirect()->route('admin.offers.edit', $offer)->with('status', 'Offer has been updated.');
    }

    /**
     * Approve a pending offer: it goes live as an active offer. The
     * original captures show no approval step for shared
     * subscriptions, so this follows the app's existing KYC
     * approve/reject pattern and the user's own words.
     */
    public function approve(Offer $offer)
    {
        $offer->status = Offer::STATUS_ACTIVE;
        $offer->save();

        return redirect()->route('admin.offers.index')->with('status', 'Offer has been approved.');
    }

    /**
     * Reject a pending offer: it stays visible as rejected in the
     * admin list and in the user's shared list, it does not disappear.
     */
    public function reject(Offer $offer)
    {
        $offer->status = Offer::STATUS_REJECTED;
        $offer->save();

        return redirect()->route('admin.offers.index')->with('status', 'Offer has been rejected.');
    }

    public function destroy(Offer $offer)
    {
        $offer->delete();

        return redirect()->route('admin.offers.index')->with('status', 'Offer has been deleted.');
    }

    /** @return array<string, mixed> */
    protected function rules(?Offer $offer = null): array
    {
        // Dropdown options are the live table values, plus the offer's own
        // stored value when it is no longer in the table, so an old row can
        // still be opened and saved without silently resetting it.
        $brandIds = Brand::orderBy('id')->pluck('id')->map(fn ($id) => (string) $id)->all();
        if ($offer !== null && $offer->brand_id !== null) {
            $brandIds[] = (string) $offer->brand_id;
        }

        $currencyCodes = Currency::ordered()->pluck('code')->map(fn ($code) => strtoupper((string) $code))->all();
        if ($offer !== null && filled($offer->currency)) {
            $currencyCodes[] = strtoupper($offer->currency);
        }

        return [
            'brand_id' => ['required', Rule::in($brandIds)],
            'title' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3', Rule::in($currencyCodes)],
            'seats_total' => ['nullable', 'integer', 'min:0'],
            'seats_available' => ['nullable', 'integer', 'min:0'],
            'seller_name' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /** @param array<string, mixed> $validated @return array<string, mixed> */
    protected function attributes(Request $request, array $validated): array
    {
        return [
            'brand_id' => $validated['brand_id'],
            'title' => $validated['title'],
            'price' => $validated['price'],
            'currency' => strtoupper($validated['currency']),
            'seats_total' => $validated['seats_total'] ?? null,
            'seats_available' => $validated['seats_available'] ?? null,
            'seller_name' => $validated['seller_name'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
