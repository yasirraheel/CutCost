<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RendersAdminShell;
use App\Models\Brand;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AdminBrandsController extends Controller
{
    use RendersAdminShell;

    public function index()
    {
        $brands = Brand::with('category')->orderBy('sort_order')->latest()->paginate(20);

        return $this->adminShell(view('admin.brands.index', compact('brands'))->render(), 'Brands', '/en/admin/brands');
    }

    public function create()
    {
        $brand = new Brand(['currency' => 'GBP', 'currency_symbol' => '£', 'is_featured' => true, 'is_active' => true]);
        $categories = Category::orderBy('name')->get();

        return $this->adminShell(view('admin.brands.create', compact('brand', 'categories'))->render(), 'New brand', '/en/admin/brands');
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        $brand = new Brand($this->attributes($request, $validated));
        if ($request->hasFile('logo')) {
            $brand->logo_path = $request->file('logo')->store('brands', 'public');
        }
        $brand->save();

        return redirect()->route('admin.brands.index')->with('status', 'Brand has been created.');
    }

    public function edit(Brand $brand)
    {
        $categories = Category::orderBy('name')->get();

        return $this->adminShell(view('admin.brands.edit', compact('brand', 'categories'))->render(), 'Edit brand', '/en/admin/brands');
    }

    public function update(Request $request, Brand $brand)
    {
        $validated = $request->validate($this->rules($brand));

        if ($request->hasFile('logo')) {
            if ($brand->logo_path) {
                Storage::disk('public')->delete($brand->logo_path);
            }
            $brand->logo_path = $request->file('logo')->store('brands', 'public');
        }
        $brand->fill($this->attributes($request, $validated));
        $brand->save();

        return redirect()->route('admin.brands.edit', $brand)->with('status', 'Brand has been updated.');
    }

    public function destroy(Brand $brand)
    {
        if ($brand->logo_path) {
            Storage::disk('public')->delete($brand->logo_path);
        }
        $brand->delete();

        return redirect()->route('admin.brands.index')->with('status', 'Brand has been deleted.');
    }

    /** @return array<string, mixed> */
    protected function rules(?Brand $brand = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('brands', 'slug')->ignore($brand?->id)],
            'price' => ['required', 'numeric', 'min:0'],
            'full_price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'currency_symbol' => ['nullable', 'string', 'max:5'],
            'discount_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'background' => ['nullable', 'string', 'max:255'],
            'icon_svg' => ['nullable', 'string'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'short_description' => ['nullable', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'is_featured' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /** @param array<string, mixed> $validated @return array<string, mixed> */
    protected function attributes(Request $request, array $validated): array
    {
        return [
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'price' => $validated['price'],
            'full_price' => $validated['full_price'] ?? null,
            'currency' => $validated['currency'] ?? 'GBP',
            'currency_symbol' => $validated['currency_symbol'] ?? '£',
            'discount_percent' => $validated['discount_percent'] ?? null,
            'background' => $validated['background'] ?? null,
            'icon_svg' => $validated['icon_svg'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'short_description' => $validated['short_description'] ?? null,
            'tagline' => $validated['tagline'] ?? null,
            'is_featured' => $request->boolean('is_featured'),
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
