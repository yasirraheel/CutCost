<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RendersAdminShell;
use App\Models\Brand;
use App\Models\Currency;
use App\Models\Offer;
use Illuminate\Http\Request;

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
        $validated = $request->validate($this->rules());

        $offer->fill($this->attributes($request, $validated));
        $offer->save();

        return redirect()->route('admin.offers.edit', $offer)->with('status', 'Offer has been updated.');
    }

    public function destroy(Offer $offer)
    {
        $offer->delete();

        return redirect()->route('admin.offers.index')->with('status', 'Offer has been deleted.');
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'brand_id' => ['required', 'exists:brands,id'],
            'title' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3', 'exists:currencies,code'],
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
