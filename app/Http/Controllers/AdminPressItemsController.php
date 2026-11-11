<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RendersAdminShell;
use App\Models\PressItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminPressItemsController extends Controller
{
    use RendersAdminShell;

    public function index()
    {
        $pressItems = PressItem::orderBy('sort_order')->latest()->paginate(20);

        return $this->adminShell(view('admin.press.index', compact('pressItems'))->render(), 'Press', '/en/admin/press');
    }

    public function create()
    {
        $pressItem = new PressItem(['is_active' => true]);

        return $this->adminShell(view('admin.press.create', compact('pressItem'))->render(), 'New press item', '/en/admin/press');
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        $pressItem = new PressItem($this->attributes($request, $validated));
        if ($request->hasFile('logo')) {
            $pressItem->logo_path = $request->file('logo')->store('press', 'public');
        }
        $pressItem->save();

        return redirect()->route('admin.press.index')->with('status', 'Press item has been created.');
    }

    public function edit(PressItem $pressItem)
    {
        return $this->adminShell(view('admin.press.edit', compact('pressItem'))->render(), 'Edit press item', '/en/admin/press');
    }

    public function update(Request $request, PressItem $pressItem)
    {
        $validated = $request->validate($this->rules());

        if ($request->hasFile('logo')) {
            if ($pressItem->logo_path) {
                Storage::disk('public')->delete($pressItem->logo_path);
            }
            $pressItem->logo_path = $request->file('logo')->store('press', 'public');
        }
        $pressItem->fill($this->attributes($request, $validated));
        $pressItem->save();

        return redirect()->route('admin.press.edit', $pressItem)->with('status', 'Press item has been updated.');
    }

    public function destroy(PressItem $pressItem)
    {
        if ($pressItem->logo_path) {
            Storage::disk('public')->delete($pressItem->logo_path);
        }
        $pressItem->delete();

        return redirect()->route('admin.press.index')->with('status', 'Press item has been deleted.');
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'outlet_name' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'logo_text' => ['nullable', 'string', 'max:255'],
            'link_url' => ['nullable', 'url', 'max:255'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /** @param array<string, mixed> $validated @return array<string, mixed> */
    protected function attributes(Request $request, array $validated): array
    {
        return [
            'outlet_name' => $validated['outlet_name'],
            'logo_text' => $validated['logo_text'] ?? null,
            'link_url' => $validated['link_url'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
