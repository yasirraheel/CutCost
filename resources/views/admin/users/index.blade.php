<section class="dashboard-progress mb-3 p-4">
    <h3 class="dashboard-progress-title">Users</h3>
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
        @forelse ($users as $user)
            <li class="row align-items-center">
                <div class="col-1 text-right">👤</div>
                <div class="col">
                    <strong>{{ $user->name }}</strong>
                    <div class="text-muted text-sm">{{ $user->email }} · {{ ucfirst($user->role) }} · KYC: {{ str_replace('_', ' ', $user->kycSubmission?->status ?? 'not submitted') }}</div>
                </div>
                <div class="col-12 col-md-4 offset-1 offset-md-0 text-md-right">
                    <a class="btn btn-light btn-sm" href="{{ route('admin.users.edit', $user) }}">Edit</a>
                    @if (! $user->is(auth()->user()))
                        <form class="d-inline" method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Delete this user permanently?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-sharesub red outline small">Delete</button>
                        </form>
                    @endif
                </div>
            </li>
        @empty
            <li class="text-muted">No users found.</li>
        @endforelse
    </ul>
    <div class="mt-3">{{ $users->links() }}</div>
</section>
