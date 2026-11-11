<section class="dashboard-progress mb-3 p-4">
    <h3 class="dashboard-progress-title">{{ $group->title }}</h3>
    <div class="progress mt-3" style="height: 10px;">
        <div class="progress-bar bg-green" role="progressbar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
    </div>
    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
    @endif
    <div class="mt-3">
        <a class="btn-sharesub bleu" href="{{ route('admin.footer-links.create', $group) }}">Add footer link</a>
        <a class="btn btn-light btn-sm" href="{{ route('admin.footer-groups.edit', $group) }}">Edit group</a>
    </div>
    <ul class="dashboard-profile-steps mt-3">
        @forelse ($group->footerLinks as $link)
            <li class="row align-items-center">
                <div class="col-1 text-right">🔗</div>
                <div class="col">
                    <strong>{{ $link->label }}</strong>
                    <div class="text-muted text-sm">{{ $link->url }} · Order {{ $link->sort_order }} · {{ $link->is_active ? 'Active' : 'Inactive' }}</div>
                </div>
                <div class="col-12 col-md-4 offset-1 offset-md-0 text-md-right">
                    @include('admin.partials.row-actions', ['editUrl' => route('admin.footer-links.edit', [$group, $link]), 'deleteUrl' => route('admin.footer-links.destroy', [$group, $link]), 'deleteConfirm' => 'Delete this footer link permanently?'])
                </div>
            </li>
        @empty
            <li class="text-muted">No links in this group yet.</li>
        @endforelse
    </ul>
    <div class="mt-3"><a href="{{ route('admin.footer-groups.index') }}">Back to link groups</a></div>
</section>
