<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RendersAdminShell;
use App\Models\FooterLink;
use App\Models\FooterLinkGroup;
use Illuminate\Http\Request;

class AdminFooterLinksController extends Controller
{
    use RendersAdminShell;

    public function index(FooterLinkGroup $group)
    {
        return redirect()->route('admin.footer-groups.show', $group);
    }

    public function create(FooterLinkGroup $group)
    {
        $link = new FooterLink(['footer_link_group_id' => $group->id, 'is_active' => true]);

        return $this->adminShell(view('admin.footer-links.create', compact('group', 'link'))->render(), 'New footer link', '/en/admin/footer-groups');
    }

    public function store(Request $request, FooterLinkGroup $group)
    {
        $validated = $request->validate($this->rules());

        $group->footerLinks()->create($this->attributes($request, $validated));

        return redirect()->route('admin.footer-groups.show', $group)->with('status', 'Footer link has been created.');
    }

    public function edit(FooterLinkGroup $group, FooterLink $link)
    {
        $this->checkBelongsToGroup($group, $link);

        return $this->adminShell(view('admin.footer-links.edit', compact('group', 'link'))->render(), 'Edit footer link', '/en/admin/footer-groups');
    }

    public function update(Request $request, FooterLinkGroup $group, FooterLink $link)
    {
        $this->checkBelongsToGroup($group, $link);
        $validated = $request->validate($this->rules());

        $link->fill($this->attributes($request, $validated));
        $link->save();

        return redirect()->route('admin.footer-links.edit', [$group, $link])->with('status', 'Footer link has been updated.');
    }

    public function destroy(FooterLinkGroup $group, FooterLink $link)
    {
        $this->checkBelongsToGroup($group, $link);

        $link->delete();

        return redirect()->route('admin.footer-groups.show', $group)->with('status', 'Footer link has been deleted.');
    }

    protected function checkBelongsToGroup(FooterLinkGroup $group, FooterLink $link): void
    {
        abort_unless($link->footer_link_group_id === $group->id, 404);
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:255'],
            'url' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /** @param array<string, mixed> $validated @return array<string, mixed> */
    protected function attributes(Request $request, array $validated): array
    {
        return [
            'label' => $validated['label'],
            'url' => $validated['url'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
