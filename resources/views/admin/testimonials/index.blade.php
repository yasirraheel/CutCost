<section class="dashboard-progress mb-3 p-4">
    <div class="d-flex justify-content-between align-items-center">
        <h3 class="dashboard-progress-title mb-0">Testimonials</h3>
        <a class="btn-sharesub bleu" style="width: auto;" href="{{ route('admin.testimonials.create') }}">Add testimonial</a>
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
        @forelse ($testimonials as $testimonial)
            <li class="row align-items-center">
                <div class="col-1 text-right">@if ($testimonial->avatar_path)<img src="{{ str_starts_with($testimonial->avatar_path, '/') || str_starts_with($testimonial->avatar_path, 'http') ? asset($testimonial->avatar_path) : asset('storage/'.$testimonial->avatar_path) }}" alt="" style="max-height: 40px; max-width: 60px; margin-right: 8px; vertical-align: middle;">@else💬@endif</div>
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
