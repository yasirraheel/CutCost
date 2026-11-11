<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RendersAdminShell;
use App\Models\Feature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminFeaturesController extends Controller
{
    use RendersAdminShell;

    public function index()
    {
        $features = Feature::orderBy('sort_order')->latest()->paginate(20);

        return $this->adminShell(view('admin.features.index', compact('features'))->render(), 'Features', '/en/admin/features');
    }

    public function create()
    {
        $feature = new Feature(['is_active' => true]);

        return $this->adminShell(view('admin.features.create', compact('feature'))->render(), 'New feature', '/en/admin/features');
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        $feature = new Feature($this->attributes($request, $validated));
        if ($request->hasFile('icon')) {
            $feature->icon_path = $request->file('icon')->store('features', 'public');
        }
        $feature->save();

        return redirect()->route('admin.features.index')->with('status', 'Feature has been created.');
    }

    public function edit(Feature $feature)
    {
        return $this->adminShell(view('admin.features.edit', compact('feature'))->render(), 'Edit feature', '/en/admin/features');
    }

    public function update(Request $request, Feature $feature)
    {
        $validated = $request->validate($this->rules());

        $oldIconPath = $feature->icon_path;
        $feature->fill($this->attributes($request, $validated));
        if ($request->hasFile('icon')) {
            if ($oldIconPath && ! str_starts_with($oldIconPath, '/img/')) {
                Storage::disk('public')->delete($oldIconPath);
            }
            $feature->icon_path = $request->file('icon')->store('features', 'public');
        }
        $feature->save();

        return redirect()->route('admin.features.edit', $feature)->with('status', 'Feature has been updated.');
    }

    public function destroy(Feature $feature)
    {
        if ($feature->icon_path && ! str_starts_with($feature->icon_path, '/img/')) {
            Storage::disk('public')->delete($feature->icon_path);
        }
        $feature->delete();

        return redirect()->route('admin.features.index')->with('status', 'Feature has been deleted.');
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'text' => ['nullable', 'string'],
            'icon_path' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'icon_svg' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /** @param array<string, mixed> $validated @return array<string, mixed> */
    protected function attributes(Request $request, array $validated): array
    {
        return [
            'title' => $validated['title'],
            'text' => $validated['text'] ?? null,
            'icon_path' => $validated['icon_path'] ?? null,
            'icon_svg' => $validated['icon_svg'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
