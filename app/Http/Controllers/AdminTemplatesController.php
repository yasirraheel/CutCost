<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RendersAdminShell;
use App\Models\Brand;
use App\Models\Template;
use Illuminate\Http\Request;

class AdminTemplatesController extends Controller
{
    use RendersAdminShell;

    public function index()
    {
        $templates = Template::with('brand')->orderBy('sort_order')->latest()->paginate(20);

        return $this->adminShell(view('admin.templates.index', compact('templates'))->render(), 'Templates', '/en/admin/templates');
    }

    public function create()
    {
        $template = new Template(['currency' => 'GBP', 'sort_order' => 0, 'is_active' => true]);
        $brands = Brand::orderBy('name')->get();

        return $this->adminShell(view('admin.templates.create', compact('template', 'brands'))->render(), 'New template', '/en/admin/templates');
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        $template = new Template($this->attributes($request, $validated));
        $template->save();

        return redirect()->route('admin.templates.index')->with('status', 'Template has been created.');
    }

    public function edit(Template $template)
    {
        $brands = Brand::orderBy('name')->get();

        return $this->adminShell(view('admin.templates.edit', compact('template', 'brands'))->render(), 'Edit template', '/en/admin/templates');
    }

    public function update(Request $request, Template $template)
    {
        $validated = $request->validate($this->rules());

        $template->fill($this->attributes($request, $validated));
        $template->save();

        return redirect()->route('admin.templates.edit', $template)->with('status', 'Template has been updated.');
    }

    public function destroy(Template $template)
    {
        $template->delete();

        return redirect()->route('admin.templates.index')->with('status', 'Template has been deleted.');
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'brand_id' => ['required', 'exists:brands,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'seats' => ['nullable', 'integer', 'min:0'],
            'shareable_seats' => ['nullable', 'integer', 'min:0'],
            'available_seats' => ['nullable', 'integer', 'min:0'],
            'price_with_fees' => ['required', 'numeric', 'min:0'],
            'retail_price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'description' => ['nullable', 'string'],
            'description_seo' => ['nullable', 'string'],
            'title_seo' => ['nullable', 'string', 'max:255'],
            'helper_create' => ['nullable', 'string'],
            'helper_join' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /** @param array<string, mixed> $validated @return array<string, mixed> */
    protected function attributes(Request $request, array $validated): array
    {
        return [
            'brand_id' => $validated['brand_id'],
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'seats' => $validated['seats'] ?? null,
            'shareable_seats' => $validated['shareable_seats'] ?? null,
            'available_seats' => $validated['available_seats'] ?? null,
            'price_with_fees' => $validated['price_with_fees'],
            'retail_price' => $validated['retail_price'] ?? null,
            'currency' => $validated['currency'],
            'description' => $validated['description'] ?? null,
            'description_seo' => $validated['description_seo'] ?? null,
            'title_seo' => $validated['title_seo'] ?? null,
            'helper_create' => $validated['helper_create'] ?? null,
            'helper_join' => $validated['helper_join'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
