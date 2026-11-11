<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RendersAdminShell;
use App\Models\Faq;
use Illuminate\Http\Request;

class AdminFaqsController extends Controller
{
    use RendersAdminShell;

    public function index()
    {
        $faqs = Faq::orderBy('sort_order')->latest()->paginate(20);

        return $this->adminShell(view('admin.faqs.index', compact('faqs'))->render(), 'FAQs', '/en/admin/faqs');
    }

    public function create()
    {
        $faq = new Faq(['is_active' => true]);

        return $this->adminShell(view('admin.faqs.create', compact('faq'))->render(), 'New FAQ', '/en/admin/faqs');
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        Faq::create($this->attributes($request, $validated));

        return redirect()->route('admin.faqs.index')->with('status', 'FAQ has been created.');
    }

    public function edit(Faq $faq)
    {
        return $this->adminShell(view('admin.faqs.edit', compact('faq'))->render(), 'Edit FAQ', '/en/admin/faqs');
    }

    public function update(Request $request, Faq $faq)
    {
        $validated = $request->validate($this->rules());

        $faq->fill($this->attributes($request, $validated));
        $faq->save();

        return redirect()->route('admin.faqs.edit', $faq)->with('status', 'FAQ has been updated.');
    }

    public function destroy(Faq $faq)
    {
        $faq->delete();

        return redirect()->route('admin.faqs.index')->with('status', 'FAQ has been deleted.');
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
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
            'question' => $validated['question'],
            'answer' => $validated['answer'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
