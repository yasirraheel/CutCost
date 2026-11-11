<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RendersAdminShell;
use App\Models\FooterLinkGroup;
use Illuminate\Http\Request;

class AdminFooterLinkGroupsController extends Controller
{
    use RendersAdminShell;

    public function index()
    {
        $groups = FooterLinkGroup::withCount('footerLinks')->orderBy('sort_order')->latest()->paginate(20);

        return $this->adminShell(view('admin.footer-groups.index', compact('groups'))->render(), 'Footer links', '/en/admin/footer-groups');
    }

    public function create()
    {
        $group = new FooterLinkGroup;

        return $this->adminShell(view('admin.footer-groups.create', compact('group'))->render(), 'New link group', '/en/admin/footer-groups');
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        FooterLinkGroup::create($this->attributes($validated));

        return redirect()->route('admin.footer-groups.index')->with('status', 'Link group has been created.');
    }

    public function show(FooterLinkGroup $group)
    {
        $group->load(['footerLinks' => fn ($query) => $query->orderBy('sort_order')->latest()]);

        return $this->adminShell(view('admin.footer-groups.show', compact('group'))->render(), 'Link group', '/en/admin/footer-groups');
    }

    public function edit(FooterLinkGroup $group)
    {
        return $this->adminShell(view('admin.footer-groups.edit', compact('group'))->render(), 'Edit link group', '/en/admin/footer-groups');
    }

    public function update(Request $request, FooterLinkGroup $group)
    {
        $validated = $request->validate($this->rules());

        $group->fill($this->attributes($validated));
        $group->save();

        return redirect()->route('admin.footer-groups.edit', $group)->with('status', 'Link group has been updated.');
    }

    public function destroy(FooterLinkGroup $group)
    {
        $group->delete();

        return redirect()->route('admin.footer-groups.index')->with('status', 'Link group has been deleted.');
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer'],
        ];
    }

    /** @param array<string, mixed> $validated @return array<string, mixed> */
    protected function attributes(array $validated): array
    {
        return [
            'title' => $validated['title'],
            'sort_order' => $validated['sort_order'] ?? 0,
        ];
    }
}
