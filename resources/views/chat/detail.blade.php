{{-- Chat detail fragment, injected into the captured chat shell.
Two-column layout from the capture: the conversation list on the left
with the current offer active and the other rows hidden on mobile, and
the Subscribers member list on the right. The header Back and
subscription icon links are injected by the controller. --}}
<div class="row">
    <div class="col-12 col-lg-6">
        <div class="chat-column">
            <div class="chat-column-header">
                <span>Chat</span>
                <span>></span>
                <span>Subscription</span>
            </div>
            <div class="chat-column-items">
                @foreach ($conversations as $conversation)
                    @include('chat.partials.conversation-row', [
                        'offer' => $conversation['offer'],
                        'subscribers' => $conversation['subscribers'],
                        'detailMemberId' => $conversation['detail_member_id'],
                        'active' => $conversation['offer']->id === $offer->id,
                        'hideOnMobile' => $conversation['offer']->id !== $offer->id,
                    ])
                @endforeach
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="chat-column">
            <div class="chat-column-header d-none d-lg-block">
                <span>Chat</span>
                <span>></span>
                <span>Subscribers</span>
            </div>
            <div class="chat-column-items tight">
                @foreach ($members as $memberRow)
                    @include('chat.partials.member-row', ['member' => $memberRow])
                @endforeach
            </div>
        </div>
    </div>
</div>
