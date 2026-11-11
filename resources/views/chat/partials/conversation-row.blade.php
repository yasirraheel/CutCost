{{-- One conversation row in the chat list. Markup mirrors the captured
chat-column-item: a green avatar icon, the subscription title linking
to the detail page, the subscriber count line, and an Open conversation
link that goes straight to the thread. The Open conversation link is
not in the capture; it was requested for this build. --}}
<div class="chat-column-item{{ $active ? ' active' : '' }}{{ $hideOnMobile ? ' d-none d-lg-flex' : '' }}" linkable>
    <div class="chat-column-item-icon avatar green">
        {!! \App\Http\Controllers\ChatController::ROW_ICON_SVG !!}
    </div>
    <div class="chat-column-item-content">
        <a href="/en/my-chat/subscription/{{ $detailMemberId }}" class="chat-column-item-content-name" linkable-target>
            {{ $offer->title ?: ($offer->template?->name ?? $offer->name) }}
        </a>
        <div class="chat-column-item-content-author">
            {{ $subscribers }} subscribers </div>
        <a href="/en/my-chat/subscription/{{ $detailMemberId }}/messages">Open conversation</a>
    </div>
</div>
