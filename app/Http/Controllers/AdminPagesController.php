<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RendersAdminShell;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AdminPagesController extends Controller
{
    use RendersAdminShell;

    public function index()
    {
        $pages = Page::orderBy('sort_order')->latest()->paginate(20);

        return $this->adminShell(view('admin.pages.index', compact('pages'))->render(), 'Pages', '/en/admin/pages');
    }

    public function create()
    {
        $page = new Page(['type' => 'page', 'is_active' => true]);
        $types = $this->types();

        return $this->adminShell(view('admin.pages.create', compact('page', 'types'))->render(), 'New page', '/en/admin/pages');
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        $page = new Page($this->attributes($request, $validated));
        if ($request->hasFile('image')) {
            $page->image_path = $request->file('image')->store('pages', 'public');
        }
        $page->save();

        return redirect()->route('admin.pages.index')->with('status', 'Page has been created.');
    }

    public function edit(Page $page)
    {
        $types = $this->types();

        return $this->adminShell(view('admin.pages.edit', compact('page', 'types'))->render(), 'Edit page', '/en/admin/pages');
    }

    public function update(Request $request, Page $page)
    {
        $validated = $request->validate($this->rules($page));

        if ($request->hasFile('image')) {
            if ($page->image_path) {
                Storage::disk('public')->delete($page->image_path);
            }
            $page->image_path = $request->file('image')->store('pages', 'public');
        }
        $page->fill($this->attributes($request, $validated));
        $page->save();

        return redirect()->route('admin.pages.edit', $page)->with('status', 'Page has been updated.');
    }

    public function destroy(Page $page)
    {
        if ($page->image_path) {
            Storage::disk('public')->delete($page->image_path);
        }
        $page->delete();

        return redirect()->route('admin.pages.index')->with('status', 'Page has been deleted.');
    }

    /** Page types the app defines, value => label. */
    protected function types(): array
    {
        return [
            'page' => 'Page',
            'post' => 'Post',
        ];
    }

    /** @return array<string, mixed> */
    protected function rules(?Page $page = null): array
    {
        // Dropdown options are the app-defined types, plus the page's
        // own stored value when it is no longer in the set, so an old
        // row can still be saved without silently resetting it.
        $types = array_keys($this->types());
        if ($page !== null && filled($page->type)) {
            $types[] = $page->type;
        }

        return [
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9-]+(?:\/[a-z0-9-]+)*$/', Rule::unique('pages', 'slug')->ignore($page?->id)],
            'type' => ['required', 'string', Rule::in($types)],
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string'],
            'body_html' => ['required', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /** @param array<string, mixed> $validated @return array<string, mixed> */
    protected function attributes(Request $request, array $validated): array
    {
        return [
            'slug' => $validated['slug'],
            'type' => $validated['type'],
            'title' => $validated['title'],
            'excerpt' => $validated['excerpt'] ?? null,
            'body_html' => $validated['body_html'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
