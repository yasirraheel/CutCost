<section class="dashboard-progress mb-3 p-4">
    <h3 class="dashboard-progress-title">Testimonials</h3>
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
        <a class="btn-sharesub bleu" href="{{ route('admin.testimonials.create') }}">Add testimonial</a>
    </div>
    <ul class="dashboard-profile-steps mt-3">
        @forelse ($testimonials as $testimonial)
            <li class="row align-items-center">
                <div class="col-1 text-right">💬</div>
                <div class="col">
                    <strong>{{ $testimonial->author_name }}</strong>
                    <div class="text-muted text-sm">{{ $testimonial->role_text }} · {{ $testimonial->rating ? $testimonial->rating.'/5' : 'No rating' }} · {{ $testimonial->is_active ? 'Active' : 'Inactive' }}</div>
                    <div class="text-muted text-sm">{{ Str::limit($testimonial->quote, 80) }}</div>
                </div>
                <div class="col-12 col-md-4 offset-1 offset-md-0 text-md-right">
                    @include('admin.partials.row-actions', ['editUrl' => route('admin.testimonials.edit', $testimonial), 'deleteUrl' => route('admin.testimonials.destroy', $testimonial), 'deleteConfirm' => 'Delete this testimonial permanently?'])
                </div>
            </li>
        @empty
            <li class="text-muted">No testimonials found.</li>
        @endforelse
    </ul>
    <div class="mt-3">{{ $testimonials->links() }}</div>
</section>
