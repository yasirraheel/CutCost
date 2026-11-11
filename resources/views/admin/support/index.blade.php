{{-- Support inbox: one row per user who has written to support, newest activity first. --}}
<section class="dashboard-progress mb-3 p-4">
    <div class="d-flex justify-content-between align-items-center">
        <h3 class="dashboard-progress-title mb-0">Support</h3>
    </div>
    @if (session('status'))
        <div class="alert alert-success mt-3" role="status">{{ session('status') }}</div>
    @endif
    <ul class="dashboard-profile-steps mt-3">
        @forelse ($threads as $thread)
            <li class="row align-items-center">
                <div class="col">
                    <strong>{{ $thread['user']->first_name ?? $thread['user']->name }} {{ $thread['user']->last_name ?? '' }}</strong>
                    @if ($thread['unread'] > 0)
                        <span class="dash-sheet-pill">{{ $thread['unread'] }} new</span>
                    @endif
                    <div class="text-muted text-sm">{{ $thread['user']->email }} · {{ $thread['total'] }} message{{ $thread['total'] === 1 ? '' : 's' }}</div>
                    @if ($thread['last'])
                        <div class="text-muted text-sm">{{ \Illuminate\Support\Str::limit($thread['last']->body, 120) }}</div>
                        <div class="text-muted text-sm">{{ $thread['last']->created_at?->format('d M Y H:i') }}</div>
                    @endif
                </div>
                <div class="col-12 col-md-3 text-md-right">
                    <a class="btn-sharesub bleu" style="width: auto;" href="{{ route('admin.support.show', $thread['user']) }}">Open</a>
                </div>
            </li>
        @empty
            <li class="text-muted">No support messages yet.</li>
        @endforelse
    </ul>
</section>
