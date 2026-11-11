<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RendersAdminShell;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminCategoriesController extends Controller
{
    use RendersAdminShell;

    public function index()
    {
        $categories = Category::withCount('brands')->orderBy('sort_order')->latest()->paginate(20);

        return $this->adminShell(view('admin.categories.index', compact('categories'))->render(), 'Categories', '/en/admin/categories');
    }

    public function create()
    {
        $category = new Category(['is_active' => true]);

        return $this->adminShell(view('admin.categories.create', compact('category'))->render(), 'New category', '/en/admin/categories');
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        Category::create($this->attributes($request, $validated));

        return redirect()->route('admin.categories.index')->with('status', 'Category has been created.');
    }

    public function edit(Category $category)
    {
        return $this->adminShell(view('admin.categories.edit', compact('category'))->render(), 'Edit category', '/en/admin/categories');
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate($this->rules($category));

        $category->fill($this->attributes($request, $validated));
        $category->save();

        return redirect()->route('admin.categories.edit', $category)->with('status', 'Category has been updated.');
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return redirect()->route('admin.categories.index')->with('status', 'Category has been deleted.');
    }

    /** @return array<string, mixed> */
    protected function rules(?Category $category = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('categories', 'slug')->ignore($category?->id)],
            'icon_svg' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
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
            'icon_svg' => $validated['icon_svg'] ?? null,
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
