{{-- Chat list fragment, injected into the captured chat shell. Two-column
layout from the capture: the conversation list on the left, an empty
right column on desktop. --}}
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
                        'active' => false,
                        'hideOnMobile' => false,
                    ])
                @endforeach
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-6 d-none d-lg-block ">
    </div>
</div>
