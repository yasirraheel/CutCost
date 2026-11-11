<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RendersAdminShell;
use App\Models\Brand;
use App\Models\BrandFaq;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminBrandFaqsController extends Controller
{
    use RendersAdminShell;

    public function index()
    {
        $faqs = BrandFaq::with('brand')->orderBy('sort_order')->latest()->paginate(20);

        return $this->adminShell(view('admin.brand-faqs.index', compact('faqs'))->render(), 'Brand FAQs', '/en/admin/brand-faqs');
    }

    public function create()
    {
        $faq = new BrandFaq(['sort_order' => 0, 'is_active' => true]);
        $brands = Brand::orderBy('name')->get();

        return $this->adminShell(view('admin.brand-faqs.create', compact('faq', 'brands'))->render(), 'New brand FAQ', '/en/admin/brand-faqs');
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        $faq = new BrandFaq($this->attributes($request, $validated));
        $faq->save();

        return redirect()->route('admin.brand-faqs.index')->with('status', 'Brand FAQ has been created.');
    }

    public function edit(BrandFaq $brandFaq)
    {
        $faq = $brandFaq;
        $brands = Brand::orderBy('name')->get();

        return $this->adminShell(view('admin.brand-faqs.edit', compact('faq', 'brands'))->render(), 'Edit brand FAQ', '/en/admin/brand-faqs');
    }

    public function update(Request $request, BrandFaq $brandFaq)
    {
        $validated = $request->validate($this->rules($brandFaq));

        $brandFaq->fill($this->attributes($request, $validated));
        $brandFaq->save();

        return redirect()->route('admin.brand-faqs.edit', $brandFaq)->with('status', 'Brand FAQ has been updated.');
    }

    public function destroy(BrandFaq $brandFaq)
    {
        $brandFaq->delete();

        return redirect()->route('admin.brand-faqs.index')->with('status', 'Brand FAQ has been deleted.');
    }

    /** @return array<string, mixed> */
    protected function rules(?BrandFaq $faq = null): array
    {
        // Dropdown options are the live brand ids, plus the FAQ's own
        // stored brand when it is no longer listed, so an old row can
        // still be opened and saved without silently resetting it.
        $brandIds = Brand::orderBy('id')->pluck('id')->map(fn ($id) => (string) $id)->all();
        if ($faq !== null && $faq->brand_id !== null) {
            $brandIds[] = (string) $faq->brand_id;
        }

        return [
            'brand_id' => ['required', Rule::in($brandIds)],
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /** @param array<string, mixed> $validated @return array<string, mixed> */
    protected function attributes(Request $request, array $validated): array
    {
        return [
            'brand_id' => $validated['brand_id'],
            'question' => $validated['question'],
            'answer' => $validated['answer'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
