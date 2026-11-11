<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * User side of the support chat: the bottom-right widget talks to
 * these endpoints. One conversation per user with the admin, stored
 * on the existing chat_messages table (see the 000022 migration):
 * a support row has support_user_id set and offer_id/member_id null,
 * so it never mixes with the member-to-member threads. There is no
 * websocket in this app, so the widget polls these endpoints: every
 * 5 seconds while the panel is open, and the unread count every
 * 30 seconds for the launcher badge. That is polling, not realtime.
 *
 * Bodies stay PLAINTEXT in v1, like the rest of chat_messages (see
 * the ChatMessage model): the API field is named `crypted` to match
 * the member chat shape, but it carries the plaintext body. No
 * encryption is invented here and no key material is stored.
 */
class SupportChatController extends Controller
{
    /**
     * The logged-in user's support thread, oldest first, GET
     * /en/support-chat/messages. Fetching marks the admin's replies
     * read, so the badge clears when the user opens the widget.
     */
    public function messages(): JsonResponse
    {
        $messages = $this->threadQuery()->orderBy('id')->get();

        $this->threadQuery()
            ->where('is_read', false)
            ->where(function (Builder $query): void {
                $query->whereNull('sender_id')->orWhere('sender_id', '!=', Auth::id());
            })
            ->update(['is_read' => true, 'is_new' => false]);

        return response()->json([
            'done' => true,
            'messages' => $messages->map(fn (ChatMessage $message): array => $this->messageShape($message))->all(),
            'timestamp' => now()->timestamp,
        ]);
    }

    /**
     * Send a message to admin support, POST /en/support-chat/message.
     * The client posts a `message` field; the stored message is
     * returned in the thread shape with a 201 status.
     */
    public function send(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $message = ChatMessage::forceCreate([
            'offer_id' => null,
            'member_id' => null,
            'support_user_id' => Auth::id(),
            'sender_id' => Auth::id(),
            'body' => $data['message'],
            'is_new' => true,
            'is_read' => false,
            'is_notified' => false,
            'is_moderated' => false,
            'waiting_moderation' => 0,
            'admin_override' => false,
        ]);

        return response()->json($this->messageShape($message), 201);
    }

    /**
     * Unread admin replies, GET /en/support-chat/unread. The body is
     * the count as plain text, empty when nothing is unread, the same
     * convention as the member chat unread endpoint.
     */
    public function unread(): Response
    {
        $count = $this->threadQuery()
            ->where('is_new', true)
            ->where(function (Builder $query): void {
                $query->whereNull('sender_id')->orWhere('sender_id', '!=', Auth::id());
            })
            ->count();

        return response(
            $count > 0 ? (string) $count : '',
            200,
            ['Content-Type' => 'text/plain; charset=UTF-8'],
        );
    }

    /**
     * Only this user's support rows. The support_user_id match is
     * what keeps one user from ever reading another user's thread:
     * there is no thread id in the URL to guess.
     */
    protected function threadQuery(): Builder
    {
        return ChatMessage::query()
            ->where('support_user_id', Auth::id())
            ->whereNull('offer_id')
            ->whereNull('member_id');
    }

    /**
     * One message in the member chat latest-messages shape, so the
     * widget renders with the same bubble code as the member chat.
     * `crypted` carries the plaintext body in v1. `is_mine` is true
     * for messages the logged-in user sent; an admin reply is the
     * other side.
     */
    protected function messageShape(ChatMessage $message): array
    {
        $isMine = $message->sender_id !== null && (int) $message->sender_id === (int) Auth::id();

        return [
            'id' => (int) $message->id,
            'crypted' => (string) $message->body,
            'from' => $message->sender_id !== null ? (int) $message->sender_id : 0,
            'to' => $isMine ? 0 : (int) Auth::id(),
            'timestamp' => $message->created_at !== null ? $message->created_at->timestamp : now()->timestamp,
            'is_mine' => $isMine,
            'new' => (bool) $message->is_new,
            'read' => (bool) $message->is_read,
            'notified' => (bool) $message->is_notified,
            'is_moderated' => (bool) $message->is_moderated,
            'waiting_moderation' => (int) $message->waiting_moderation,
            'admin_override' => (bool) $message->admin_override,
        ];
    }
}
