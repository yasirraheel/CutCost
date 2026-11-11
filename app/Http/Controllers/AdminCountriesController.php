<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RendersAdminShell;
use App\Models\Country;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminCountriesController extends Controller
{
    use RendersAdminShell;

    public function index()
    {
        $countries = Country::orderBy('sort_order')->orderBy('name')->paginate(20);

        return $this->adminShell(view('admin.countries.index', compact('countries'))->render(), 'Countries', '/en/admin/countries');
    }

    public function create()
    {
        $country = new Country(['is_active' => true, 'currency' => Currency::defaultCode()]);
        $currencies = Currency::ordered()->get();

        return $this->adminShell(view('admin.countries.create', compact('country', 'currencies'))->render(), 'New country', '/en/admin/countries');
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        Country::create($this->attributes($request, $validated));

        return redirect()->route('admin.countries.index')->with('status', 'Country has been created.');
    }

    public function edit(Country $country)
    {
        $currencies = Currency::ordered()->get();

        return $this->adminShell(view('admin.countries.edit', compact('country', 'currencies'))->render(), 'Edit country', '/en/admin/countries');
    }

    public function update(Request $request, Country $country)
    {
        $validated = $request->validate($this->rules($country));

        $country->fill($this->attributes($request, $validated));
        $country->save();

        return redirect()->route('admin.countries.edit', $country)->with('status', 'Country has been updated.');
    }

    public function destroy(Country $country)
    {
        $country->delete();

        return redirect()->route('admin.countries.index')->with('status', 'Country has been deleted.');
    }

    /** @return array<string, mixed> */
    protected function rules(?Country $country = null): array
    {
        return [
            'code' => ['required', 'string', 'size:2', Rule::unique('countries', 'code')->ignore($country?->id)],
            'name' => ['required', 'string', 'max:255'],
            'fee_flat' => ['nullable', 'numeric'],
            'fee_percent' => ['nullable', 'numeric'],
            'currency' => ['nullable', 'string', 'size:3', 'exists:currencies,code'],
            'postal_regex' => ['nullable', 'string', 'max:255'],
            'postal_example' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /** @param array<string, mixed> $validated @return array<string, mixed> */
    protected function attributes(Request $request, array $validated): array
    {
        return [
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'fee_flat' => $validated['fee_flat'] ?? 0,
            'fee_percent' => $validated['fee_percent'] ?? 0,
            'currency' => strtoupper($validated['currency'] ?? Currency::defaultCode()),
            'postal_regex' => $validated['postal_regex'] ?? null,
            'postal_example' => $validated['postal_example'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
