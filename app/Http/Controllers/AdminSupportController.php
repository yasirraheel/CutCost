<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RendersAdminShell;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin side of the support chat: the support inbox inside the
 * existing admin area. Inbox rows are one per user who has written
 * to support, newest activity first, with the last message and the
 * unread count. The thread view shows that user's conversation and
 * a plain reply form; the admin side refreshes on navigation, it
 * does not poll. Replies are stored on the same chat_messages table
 * as the user's messages (see the 000022 migration), with the admin
 * as sender_id, so the user sees the reply in the widget on the
 * next poll.
 */
class AdminSupportController extends Controller
{
    use RendersAdminShell;

    public function index(): Response
    {
        $rows = ChatMessage::query()
            ->selectRaw('support_user_id, MAX(id) AS last_id, MAX(created_at) AS last_at, COUNT(*) AS total')
            ->whereNotNull('support_user_id')
            ->whereNull('offer_id')
            ->whereNull('member_id')
            ->groupBy('support_user_id')
            ->orderByDesc('last_at')
            ->get();

        $threads = $rows->map(function ($row): array {
            $user = User::find($row->support_user_id);
            $last = ChatMessage::find($row->last_id);
            $unread = ChatMessage::query()
                ->where('support_user_id', $row->support_user_id)
                ->whereNull('offer_id')
                ->whereNull('member_id')
                ->where('is_new', true)
                ->where(function ($query) use ($row): void {
                    $query->where('sender_id', (int) $row->support_user_id);
                })
                ->count();

            return [
                'user' => $user,
                'last' => $last,
                'total' => (int) $row->total,
                'unread' => $unread,
                'last_at' => $row->last_at,
            ];
        })->filter(fn (array $thread): bool => $thread['user'] !== null)->values();

        return $this->adminShell(
            view('admin.support.index', ['threads' => $threads])->render(),
            'Support',
            '/en/admin/support',
        );
    }

    public function show(User $user): Response
    {
        $messages = ChatMessage::query()
            ->with('sender')
            ->where('support_user_id', $user->id)
            ->whereNull('offer_id')
            ->whereNull('member_id')
            ->orderBy('id')
            ->get();

        // Opening the thread marks the user's messages read.
        ChatMessage::query()
            ->where('support_user_id', $user->id)
            ->whereNull('offer_id')
            ->whereNull('member_id')
            ->where('sender_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'is_new' => false]);

        return $this->adminShell(
            view('admin.support.show', ['supportUser' => $user, 'messages' => $messages])->render(),
            'Support',
            '/en/admin/support',
        );
    }

    public function reply(User $user, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        ChatMessage::forceCreate([
            'offer_id' => null,
            'member_id' => null,
            'support_user_id' => $user->id,
            'sender_id' => Auth::id(),
            'body' => $data['message'],
            'is_new' => true,
            'is_read' => false,
            'is_notified' => false,
            'is_moderated' => false,
            'waiting_moderation' => 0,
            'admin_override' => false,
        ]);

        return redirect()->route('admin.support.show', ['user' => $user->id])
            ->with('status', 'Reply sent.');
    }
}
