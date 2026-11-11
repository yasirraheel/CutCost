<section class="dashboard-progress mb-3 p-4">
    <div class="d-flex justify-content-between align-items-center">
        <h3 class="dashboard-progress-title mb-0">Footer link groups</h3>
        <a class="btn-sharesub bleu" style="width: auto;" href="{{ route('admin.footer-groups.create') }}">Add link group</a>
    </div>
    <div class="progress mt-3" style="height: 10px;">
        <div class="progress-bar bg-green" role="progressbar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
    </div>
    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
    @endif
    <ul class="dashboard-profile-steps mt-3">
        @forelse ($groups as $group)
            <li class="row align-items-center">
                <div class="col-1 text-right">🔗</div>
                <div class="col">
                    <strong>{{ $group->title }}</strong>
                    <div class="text-muted text-sm">{{ $group->footer_links_count }} link(s) · Order {{ $group->sort_order }}</div>
                </div>
                <div class="col-12 col-md-4 offset-1 offset-md-0 text-md-right">
                    @include('admin.partials.row-actions', ['editUrl' => route('admin.footer-groups.edit', $group), 'deleteUrl' => route('admin.footer-groups.destroy', $group), 'deleteConfirm' => 'Delete this link group and all its links permanently?', 'extraLinks' => [['url' => route('admin.footer-groups.show', $group), 'label' => 'View links']]])
                </div>
            </li>
        @empty
            <li class="text-muted">No link groups found.</li>
        @endforelse
    </ul>
    <div class="mt-3">{{ $groups->links() }}</div>
</section>
