{{-- One member row in the Subscribers column. The row for the logged-in
user carries the me class, as in the capture. No online class is
rendered: presence has no data source (spec gap G10), so the flag is
left out rather than invented. --}}
<div class="chat-column-item" linkable>
    <div class="chat-column-item-icon avatar{{ (int) $member->user_id === (int) auth()->id() ? ' me' : '' }}">
        {{ $member->initials }}
    </div>
    <div class="chat-column-item-content">
        <a href="/en/my-chat/subscription/{{ $member->id }}/messages" class="chat-column-item-content-name" linkable-target>{{ $member->username }}</a>
    </div>
</div>
