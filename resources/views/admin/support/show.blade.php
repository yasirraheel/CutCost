{{-- Support thread: the user's conversation with the admin, rendered with the captured chat bubble classes. Replies are a plain form post; the page refreshes after sending. --}}
<section class="dashboard-progress mb-3 p-4">
    <div class="d-flex justify-content-between align-items-center">
        <h3 class="dashboard-progress-title mb-0">{{ $supportUser->first_name ?? $supportUser->name }} {{ $supportUser->last_name ?? '' }}</h3>
        <a class="btn btn-light btn-sm" href="{{ route('admin.support.index') }}">Back to inbox</a>
    </div>
    <div class="text-muted text-sm mt-2">{{ $supportUser->email }}</div>
    @if (session('status'))
        <div class="alert alert-success mt-3" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger mt-3" role="alert">{{ $errors->first() }}</div>
    @endif
    <div class="chat-wrapper mt-3">
        <div class="chat-messages">
            <div class="chat-messages-list">
                @forelse ($messages as $message)
                    @php($isMine = (int) $message->sender_id === (int) auth()->id())
                    <div class="chat-message {{ $isMine ? 'me' : 'other' }}">
                        <div class="chat-message-content">
                            <div class="chat-message-text">{{ $message->body }}</div>
                            <div class="chat-message-meta">
                                <span>{{ $isMine ? 'You' : ($supportUser->first_name ?? $supportUser->name) }}</span>
                                <span class="chat-message-time">{{ $message->created_at?->format('d M Y H:i') }}</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="chat-empty">No messages yet.</div>
                @endforelse
            </div>
        </div>
        <div class="chat-wrapper-form">
            <form method="POST" action="{{ route('admin.support.reply', $supportUser) }}">
                @csrf
                <textarea name="message" rows="1" placeholder="Write a reply" aria-label="Write a reply" required></textarea>
                <button type="submit" class="btn btn-sharesub blue" aria-label="Send">
                    <svg width="16" height="14" viewBox="0 0 16 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 11.586C8.60954 11.9765 8.60953 12.6096 9 13C9.39046 13.3905 10.0235 13.3905 10.414 13L15.7069 7.70714C16.0974 7.31661 16.0974 6.68345 15.7069 6.29292L10.414 1.00003C10.0235 0.609564 9.39047 0.609564 9 1.00003C8.60953 1.39049 8.60954 2.02356 9 2.41403L12.586 6.00003H1C0.447715 6.00003 0 6.44774 0 7.00003C0 7.55231 0.447715 8.00003 1 8.00003H12.586L9 11.586Z" fill="currentColor"/></svg>
                </button>
            </form>
        </div>
    </div>
</section>
