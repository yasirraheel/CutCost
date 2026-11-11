<section class="dashboard-progress mb-3 p-4">
    <h3 class="dashboard-progress-title">Brand FAQs</h3>
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
        <a class="btn-sharesub bleu" href="{{ route('admin.brand-faqs.create') }}">Add brand FAQ</a>
    </div>
    <ul class="dashboard-profile-steps mt-3">
        @forelse ($faqs as $faq)
            <li class="row align-items-center">
                <div class="col-1 text-right">❓</div>
                <div class="col">
                    <strong>{{ $faq->question }}</strong>
                    <div class="text-muted text-sm">{{ $faq->brand?->name ?? 'No brand' }} · {{ Str::limit($faq->answer, 80) }} · {{ $faq->is_active ? 'Active' : 'Inactive' }}</div>
                </div>
                <div class="col-12 col-md-4 offset-1 offset-md-0 text-md-right">
                    @include('admin.partials.row-actions', ['editUrl' => route('admin.brand-faqs.edit', $faq), 'deleteUrl' => route('admin.brand-faqs.destroy', $faq), 'deleteConfirm' => 'Delete this brand FAQ permanently?'])
                </div>
            </li>
        @empty
            <li class="text-muted">No brand FAQs found.</li>
        @endforelse
    </ul>
    <div class="mt-3">{{ $faqs->links() }}</div>
</section>
