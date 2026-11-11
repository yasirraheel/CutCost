<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RendersAdminShell;
use App\Models\ReassuranceItem;
use Illuminate\Http\Request;

class AdminReassuranceController extends Controller
{
    use RendersAdminShell;

    public function index()
    {
        $items = ReassuranceItem::orderBy('sort_order')->latest()->paginate(20);

        return $this->adminShell(view('admin.reassurance.index', compact('items'))->render(), 'Reassurance', '/en/admin/reassurance');
    }

    public function create()
    {
        $item = new ReassuranceItem(['sort_order' => 0, 'is_active' => true]);

        return $this->adminShell(view('admin.reassurance.create', compact('item'))->render(), 'New reassurance item', '/en/admin/reassurance');
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        $item = new ReassuranceItem($this->attributes($request, $validated));
        $item->save();

        return redirect()->route('admin.reassurance.index')->with('status', 'Reassurance item has been created.');
    }

    public function edit(ReassuranceItem $reassuranceItem)
    {
        $item = $reassuranceItem;

        return $this->adminShell(view('admin.reassurance.edit', compact('item'))->render(), 'Edit reassurance item', '/en/admin/reassurance');
    }

    public function update(Request $request, ReassuranceItem $reassuranceItem)
    {
        $validated = $request->validate($this->rules());

        $reassuranceItem->fill($this->attributes($request, $validated));
        $reassuranceItem->save();

        return redirect()->route('admin.reassurance.edit', $reassuranceItem)->with('status', 'Reassurance item has been updated.');
    }

    public function destroy(ReassuranceItem $reassuranceItem)
    {
        $reassuranceItem->delete();

        return redirect()->route('admin.reassurance.index')->with('status', 'Reassurance item has been deleted.');
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'text' => ['required', 'string'],
            'icon' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /** @param array<string, mixed> $validated @return array<string, mixed> */
    protected function attributes(Request $request, array $validated): array
    {
        return [
            'title' => $validated['title'],
            'text' => $validated['text'],
            'icon' => $validated['icon'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
