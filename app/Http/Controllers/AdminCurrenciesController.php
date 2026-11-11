<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RendersAdminShell;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminCurrenciesController extends Controller
{
    use RendersAdminShell;

    public function index()
    {
        $currencies = Currency::ordered()->paginate(20);

        return $this->adminShell(view('admin.currencies.index', compact('currencies'))->render(), 'Currencies', '/en/admin/currencies');
    }

    public function create()
    {
        $currency = new Currency([
            'is_active' => true,
            'is_available_in_wizard' => true,
            'always_offered_in_wizard' => false,
        ]);

        return $this->adminShell(view('admin.currencies.create', compact('currency'))->render(), 'New currency', '/en/admin/currencies');
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        Currency::create($this->attributes($request, $validated));

        return redirect()->route('admin.currencies.index')->with('status', 'Currency has been created.');
    }

    public function edit(Currency $currency)
    {
        return $this->adminShell(view('admin.currencies.edit', compact('currency'))->render(), 'Edit currency', '/en/admin/currencies');
    }

    public function update(Request $request, Currency $currency)
    {
        $validated = $request->validate($this->rules($currency));

        $currency->fill($this->attributes($request, $validated));
        $currency->save();

        return redirect()->route('admin.currencies.edit', $currency)->with('status', 'Currency has been updated.');
    }

    public function destroy(Currency $currency)
    {
        $currency->delete();

        return redirect()->route('admin.currencies.index')->with('status', 'Currency has been deleted.');
    }

    /** @return array<string, mixed> */
    protected function rules(?Currency $currency = null): array
    {
        return [
            'code' => ['required', 'string', 'size:3', Rule::unique('currencies', 'code')->ignore($currency?->id)],
            'symbol' => ['required', 'string', 'max:5'],
            'name' => ['required', 'string', 'max:255'],
            'iso_numeric' => ['required', 'integer', 'min:0', Rule::unique('currencies', 'iso_numeric')->ignore($currency?->id)],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
            'is_available_in_wizard' => ['nullable', 'boolean'],
            'always_offered_in_wizard' => ['nullable', 'boolean'],
        ];
    }

    /** @param array<string, mixed> $validated @return array<string, mixed> */
    protected function attributes(Request $request, array $validated): array
    {
        return [
            'code' => strtoupper($validated['code']),
            'symbol' => $validated['symbol'],
            'name' => $validated['name'],
            'iso_numeric' => $validated['iso_numeric'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
            'is_available_in_wizard' => $request->boolean('is_available_in_wizard'),
            'always_offered_in_wizard' => $request->boolean('always_offered_in_wizard'),
        ];
    }
}
