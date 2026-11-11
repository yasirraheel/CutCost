<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\Offer;
use App\Models\OfferMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Chat list, chat detail, messages view and the chat JSON endpoints.
 *
 * Layout source: the Raw Pages 2 chat spec. The pages render inside the
 * captured chat shell (resources/captured/en/my-chat/subscriptions.html)
 * with the dynamic chat region injected from Blade, so the shared chrome
 * stays exactly as captured. The chat list route was moved out of
 * CapturedPageController here; that controller is not touched.
 *
 * URL scheme (task decision, not the captured shape): threads are keyed
 * by the offer_members row id, because the demo offers carry no hash.
 * Detail: /en/my-chat/subscription/{memberId}; messages view and all
 * endpoints live under /en/my-chat/subscription/{memberId}/. The
 * captured hash based shapes (/en/my-chat/subscription/{hash} and
 * /en/my-chat/subscription/{hash}/{memberId}/messages) are the real
 * site's URLs, and both are served here: a non-numeric detail
 * identifier is an offer hash (stored, or the derived placeholder
 * from offerHash), resolving to the offer's first thread member, and
 * the hash/member messages shape is served by messagesByOffer. A
 * numeric detail identifier is a member id only, because member ids
 * and offer ids share the integer space and cannot be told apart.
 *
 * ChatMessage bodies stay PLAINTEXT in v1 (see the ChatMessage model):
 * the latest-messages API shape names the body field `crypted`, but no
 * encryption is invented here, so v1 returns the plaintext body in
 * that field. Real encryption is an open follow-up needing a key
 * decision. No key material is ever rendered on these pages.
 *
 * The messages view deliberately does NOT render a <sharesub-chat>
 * element in v1: the bundled public/js/app.js globally registers
 * that Vue component, and it RSA-decrypts every message body with a
 * private key. With no keys it renders every message as "false", and
 * rendering it alongside the local thread component duplicates the
 * thread. The local component (resources/views/chat/messages.blade.php)
 * renders the thread instead, with the endpoint URLs as data
 * attributes in the captured names, until the key decision lands.
 */
class ChatController extends CapturedPageController
{
    public function index(): Response
    {
        if ($redirect = $this->adminRedirect('en/my-chat/subscriptions.html')) {
            return $redirect;
        }

        $conversations = $this->conversations();
        $html = $this->chatShell();

        if ($conversations === []) {
            // Zero conversations: keep the captured empty state, whose
            // links the shared chrome pass already fixed off #offline.
            return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
        }

        $fragment = view('chat.list', ['conversations' => $conversations])->render();

        return response($this->injectChatContent($html, $fragment, ''))->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function detail(string $member): Response
    {
        if ($redirect = $this->adminRedirect('en/my-chat/subscriptions.html')) {
            return $redirect;
        }

        // Accepts a member id, an offer id, or a stored offer hash: the
        // shared list and the shared-subscription detail link here with
        // the offer identifier, see resolveThreadMember.
        $member = $this->resolveThreadMember($member);
        $offer = $member->offer;
        $conversations = $this->conversations();
        $members = $this->currentMembers($offer);
        $html = $this->chatShell();
        $html = $this->injectHeaderActions($html, $this->detailHeaderActions($offer));

        $fragment = view('chat.detail', [
            'offer' => $offer,
            'member' => $member,
            'conversations' => $conversations,
            'members' => $members,
        ])->render();

        return response($this->injectChatContent($html, $fragment, ''))->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function messages(int $memberId): Response
    {
        if ($redirect = $this->adminRedirect('en/my-chat/subscriptions.html')) {
            return $redirect;
        }

        $member = $this->resolveMember($memberId);
        $offer = $member->offer;
        $html = $this->chatShell();
        $html = $this->injectHeaderActions($html, $this->messagesHeaderActions($offer, $member));

        $fragment = view('chat.messages', [
            'offer' => $offer,
            'member' => $member,
            'offerHash' => $this->offerHash($offer),
        ])->render();

        return response($this->injectChatContent($html, $fragment, 'chat--conversation'))->header('Content-Type', 'text/html; charset=UTF-8');
    }

    /**
     * Messages view at the captured Contact URL shape,
     * /en/my-chat/subscription/{offerIdentifier}/{memberId}/messages.
     * The offer identifier is informational in v1: the thread is keyed
     * by the member id, and messages() enforces that the thread's
     * offer belongs to the logged-in user.
     */
    public function messagesByOffer(string $identifier, int $member): Response
    {
        return $this->messages($member);
    }

    /**
     * Unread polling endpoint, GET /en/my-chat/unread.
     *
     * Response shape decision (spec gap G6/G7, resolved from the
     * bundled public/js/app.js, not from the capture alone): the
     * polling code appends the response body to the navbar chat item
     * and parses an integer out of its text to set the mobile tabbar
     * badge, so the body is the unread count as plain text, and empty
     * when there is nothing unread. That matches the captured empty
     * response when no messages are unread. The count is the number
     * of new messages from other parties across the logged-in user's
     * offers. Which element the count lands in is the navbar chat
     * item, per the same code.
     */
    public function unread(): Response
    {
        $count = ChatMessage::query()
            ->where('is_new', true)
            ->where(function ($query): void {
                $query->whereNull('sender_id')->orWhere('sender_id', '!=', Auth::id());
            })
            ->whereHas('offer', function ($query): void {
                $query->where('user_id', Auth::id());
            })
            ->count();

        return response(
            $count > 0 ? (string) $count : '',
            200,
            ['Content-Type' => 'text/plain; charset=UTF-8'],
        );
    }

    /**
     * Latest messages for a thread, GET /en/my-chat/subscription/{memberId}/messages/latest.
     *
     * Returns the captured latest-messages JSON shape: done, messages
     * newest first, has_older_messages, old_messages, a server
     * timestamp and an input_timestamp echo. The captured endpoint
     * lived at /en/my-shared-subscriptions/{hash}/messages/latest/{memberId};
     * this member-id URL is the task's re-pointing of it, see the class
     * comment. Pagination of older messages is unobserved in the
     * capture (spec G17): v1 returns the latest 100 and reports
     * has_older_messages only when more exist.
     */
    public function latest(int $memberId, Request $request): JsonResponse
    {
        $member = $this->resolveMember($memberId);
        $total = $member->chatMessages()->count();
        $messages = $member->chatMessages()->orderByDesc('id')->limit(100)->get();
        // The real component polls with `timestamp` (its last poll
        // time); the v1 local component passes `input_timestamp`.
        // Both are accepted and echoed back as input_timestamp.
        $input = $request->query('input_timestamp', $request->query('timestamp'));

        return response()->json([
            'done' => true,
            'messages' => $messages->map(fn (ChatMessage $message): array => $this->messageShape($message, $member))->all(),
            'has_older_messages' => $total > 100,
            'old_messages' => false,
            'timestamp' => now()->timestamp,
            'input_timestamp' => is_numeric($input) ? (int) $input : null,
        ]);
    }

    /**
     * Send a message, POST /en/my-chat/subscription/{memberId}/message.
     *
     * Assumed shape (spec G19: the send request and response are
     * unobserved, no capture exists): the client posts a `message`
     * field, and the endpoint returns the stored message in the
     * latest-messages shape with a 201 status.
     */
    public function send(int $memberId, Request $request): JsonResponse
    {
        $member = $this->resolveMember($memberId);
        $data = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $message = ChatMessage::create([
            'offer_id' => $member->offer_id,
            'member_id' => $member->id,
            'sender_id' => Auth::id(),
            'body' => $data['message'],
            'is_new' => true,
            'is_read' => false,
            'is_notified' => false,
            'is_moderated' => false,
            'waiting_moderation' => 0,
            'admin_override' => false,
        ]);

        return response()->json($this->messageShape($message, $member), 201);
    }

    /**
     * Mark a thread read, POST /en/my-chat/subscription/{memberId}/read.
     *
     * The bundled app.js shows the real component marks messages one
     * by one, posting FormData with the message `id`. That shape is
     * accepted here: with an `id` only that message in this thread is
     * marked (and only when it was not sent by the logged-in user);
     * without one the whole thread is marked, which is what the v1
     * local component asks for. Returns the number marked.
     */
    public function read(int $memberId, Request $request): JsonResponse
    {
        $member = $this->resolveMember($memberId);

        $query = ChatMessage::query()
            ->where('member_id', $member->id)
            ->where(function ($sub): void {
                $sub->whereNull('sender_id')->orWhere('sender_id', '!=', Auth::id());
            })
            ->where('is_read', false);

        $messageId = $request->input('id');
        if ($messageId !== null) {
            $query->where('id', (int) $messageId);
        }

        $count = $query->update(['is_read' => true, 'is_new' => false]);

        return response()->json(['done' => true, 'read' => $count]);
    }

    /**
     * Flag a message for moderation, POST /en/my-chat/subscription/{memberId}/moderate.
     *
     * Assumed behaviour (spec G19): the capture names the moderate URL
     * but not its request, response or effect. Flagging sets
     * waiting_moderation on that message in this thread; nothing is
     * hidden or deleted by the flag itself.
     */
    public function moderate(int $memberId, Request $request): JsonResponse
    {
        $member = $this->resolveMember($memberId);
        // The real component posts FormData with `id`; the v1 local
        // component posts JSON with `message_id`. Both are accepted.
        $data = $request->validate([
            'message_id' => ['nullable', 'integer', 'required_without:id'],
            'id' => ['nullable', 'integer', 'required_without:message_id'],
        ]);

        $message = ChatMessage::query()
            ->where('member_id', $member->id)
            ->findOrFail($data['message_id'] ?? $data['id']);
        $message->update(['waiting_moderation' => 1]);

        return response()->json([
            'done' => true,
            'message' => $this->messageShape($message->fresh(), $member),
        ]);
    }

    /**
     * One message in the captured latest-messages shape.
     *
     * `crypted` carries the plaintext body in v1 (see the class
     * comment). `from` and `to` are the sender and recipient ids:
     * a user id when the sender is the logged-in user, otherwise the
     * member id for a co-subscriber message. The capture omits the
     * values, so this is an assumption, recorded here.
     */
    protected function messageShape(ChatMessage $message, OfferMember $member): array
    {
        $isMine = $message->sender_id !== null && (int) $message->sender_id === (int) Auth::id();

        return [
            'id' => (int) $message->id,
            'crypted' => (string) $message->body,
            'from' => $message->sender_id !== null ? (int) $message->sender_id : (int) $member->id,
            'to' => $isMine ? (int) $member->id : (int) Auth::id(),
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

    /**
     * The member row id resolves the thread, and the thread's offer
     * must belong to the logged-in user. Anything else is a 404, so
     * one user can never open another user's conversation.
     */
    protected function resolveMember(int $memberId): OfferMember
    {
        $member = $this->findOwnedMember($memberId);

        if ($member === null) {
            abort(404);
        }

        return $member;
    }

    protected function findOwnedMember(int $memberId): ?OfferMember
    {
        $member = OfferMember::query()->with('offer')->find($memberId);

        if ($member === null || $member->offer === null || (int) $member->offer->user_id !== (int) Auth::id()) {
            return null;
        }

        return $member;
    }

    /**
     * Resolve a chat detail identifier. A numeric identifier is a
     * member id only: member ids and offer ids share the small
     * integer space, so a numeric value can never be read as an offer
     * id without risking the wrong thread. A non-numeric identifier
     * is an offer hash: stored first, then the derived placeholder
     * from offerHash (scanned over the logged-in user's offers).
     * An offer identifier resolves to the offer's first thread
     * member, using the same pick as conversations(). The offer must
     * belong to the logged-in user, and an offer with no current
     * members is a 404.
     */
    protected function resolveThreadMember(string $identifier): OfferMember
    {
        if (ctype_digit($identifier)) {
            return $this->resolveMember((int) $identifier);
        }

        $offer = Offer::query()->where('user_id', Auth::id())->where('hash', $identifier)->first();

        if ($offer === null) {
            $offer = Offer::query()->where('user_id', Auth::id())->get()
                ->first(fn (Offer $candidate): bool => $this->offerHash($candidate) === $identifier);
        }

        if ($offer !== null) {
            $member = $this->firstDetailMember($offer);

            if ($member !== null) {
                return $member;
            }
        }

        abort(404);
    }

    protected function firstDetailMember(Offer $offer): ?OfferMember
    {
        $members = $offer->members()->where('status', OfferMember::STATUS_CURRENT)->orderBy('id')->get();
        $messageMemberIds = $offer->chatMessages()->pluck('member_id')->unique();
        $withMessages = $members->first(fn (OfferMember $member): bool => $messageMemberIds->contains($member->id));
        $coSubscribers = $members->reject(fn (OfferMember $member): bool => (int) $member->user_id === (int) Auth::id());

        return $withMessages ?? $coSubscribers->first() ?? $members->first();
    }

    /**
     * One conversation per active offer owned by the logged-in user
     * that has at least one current member. Sorted by latest message
     * first (spec gap G1: sort order is not visible in the capture,
     * so latest activity first is the choice, with offers that have
     * no messages after them, then by offer id).
     *
     * Per row: the subscriber count is the current co-subscriber
     * count, excluding the owner member, rendered as "N subscribers"
     * (the capture shows that exact line, including "1 subscribers").
     * The detail link uses the first current member that has messages,
     * else the first current co-subscriber, else the owner member.
     */
    protected function conversations(): array
    {
        $offers = Offer::query()
            ->where('user_id', Auth::id())
            ->where('status', 'active')
            ->whereHas('members', function ($query): void {
                $query->where('status', OfferMember::STATUS_CURRENT);
            })
            ->with([
                'members' => function ($query): void {
                    $query->where('status', OfferMember::STATUS_CURRENT)->orderBy('id');
                },
                'chatMessages' => function ($query): void {
                    $query->orderByDesc('created_at')->orderByDesc('id');
                },
            ])
            ->get();

        $rows = [];

        foreach ($offers as $offer) {
            $members = $offer->members;
            $coSubscribers = $members->reject(fn (OfferMember $member): bool => (int) $member->user_id === (int) Auth::id());
            $withMessages = $members->first(fn (OfferMember $member): bool => $offer->chatMessages->contains('member_id', $member->id));
            $detailMember = $withMessages
                ?? $coSubscribers->first()
                ?? $members->first();

            if ($detailMember === null) {
                continue;
            }

            $rows[] = [
                'offer' => $offer,
                'subscribers' => $coSubscribers->count(),
                'detail_member_id' => $detailMember->id,
                'last_message_at' => $offer->chatMessages->first()?->created_at,
            ];
        }

        usort($rows, static function (array $a, array $b): int {
            $aAt = $a['last_message_at'];
            $bAt = $b['last_message_at'];

            if ($aAt !== null && $bAt !== null) {
                return $bAt <=> $aAt;
            }

            if ($aAt !== null) {
                return -1;
            }

            if ($bAt !== null) {
                return 1;
            }

            return $b['offer']->id <=> $a['offer']->id;
        });

        return $rows;
    }

    /**
     * Current members of an offer, owner member first, then the rest
     * by join order. Previous members are not listed (the capture
     * shows the Subscribers column as current members only).
     */
    protected function currentMembers(Offer $offer): mixed
    {
        return $offer->members()
            ->where('status', OfferMember::STATUS_CURRENT)
            ->orderByRaw('(user_id = ?) DESC', [Auth::id()])
            ->orderBy('id')
            ->get();
    }

    protected function offerTitle(Offer $offer): string
    {
        return (string) ($offer->title ?: $offer->template?->name ?: $offer->name ?: '');
    }

    protected function detailHeaderActions(Offer $offer): array
    {
        return [
            'left' => [
                'href' => '/en/my-chat/subscriptions',
                'label' => 'View all conversations',
            ],
            'right' => [
                'href' => '/en/my-shared-subscriptions/'.$this->offerHash($offer),
                'title' => 'View the subscription',
            ],
        ];
    }

    protected function messagesHeaderActions(Offer $offer, OfferMember $member): array
    {
        return [
            'left' => [
                'href' => '/en/my-chat/subscription/'.$member->id,
                'label' => 'Back',
            ],
            'right' => [
                'href' => '/en/my-shared-subscriptions/'.$this->offerHash($offer),
                'title' => 'View the subscription',
            ],
        ];
    }

    /**
     * The captured chat shell with the shared captured-page pipeline
     * applied, plus the chat fixes: the unread polling URL enabled
     * (D2), the support link to the help site (D4) and the bank
     * account links to the bank account page (D5).
     */
    protected function chatShell(): string
    {
        $html = $this->readPage('en/my-chat/subscriptions.html');
        $html = $this->replaceCsrfTokens($html);
        $html = str_replace(
            'id="logout-form" action="#offline"',
            'id="logout-form" action="/en/logout"',
            $html,
        );
        $html = $this->applyAuthenticatedNavigation($html, 'en/my-chat/subscriptions.html');
        $html = $this->applyAuthenticatedUserData($html);
        $html = $this->applyAccountTabs($html, false);
        $html = $this->applyAccountData($html, 'en/my-chat/subscriptions.html');
        $html = $this->applyGlobalContent($html, 'en/my-chat/subscriptions.html');
        $html = $this->replaceSavingCalculator($html);
        $html = $this->normalizePageLinks($html);
        $html = $this->fixChatLinks($html);
        $html = str_replace(
            'window.check_chat_url = null;',
            'window.check_chat_url = "/en/my-chat/unread";',
            $html,
        );

        return $html;
    }

    protected function fixChatLinks(string $html): string
    {
        $html = str_replace(
            '<a href="#offline" target="_blank" rel="noopener" class="dash-sheet-link" data-open-support>',
            '<a href="https://help.sharesub.com/" target="_blank" rel="noopener" class="dash-sheet-link" data-open-support>',
            $html,
        );

        $html = preg_replace_callback(
            '/<a\b[^>]*href="#offline"[^>]*>.*?<\/a>/is',
            static function (array $match): string {
                $anchor = $match[0];
                $text = trim((string) preg_replace('/\s+/', ' ', strip_tags($anchor)));

                if (strtolower($text) !== 'connected bank account') {
                    return $anchor;
                }

                return str_replace('href="#offline"', 'href="/en/my-bank-account"', $anchor);
            },
            $html,
        ) ?? $html;

        return $html;
    }

    /**
     * Replace the captured empty-state block inside the chat div with
     * the rendered fragment, and set the chat div class for the page.
     */
    protected function injectChatContent(string $html, string $fragment, string $chatClass): string
    {
        $html = str_replace(
            '<div class="chat   chat--noconv ">',
            '<div class="chat  '.$chatClass.' ">',
            $html,
        );

        $html = preg_replace_callback(
            '/<div class="subscriptions-actions chat-noconv mb-3 p-4">.*?<\/div>(\s*)<\/div>/s',
            static fn (array $match): string => $fragment.$match[1].'</div>',
            $html,
            1,
        ) ?? $html;

        return $html;
    }

    /**
     * Fill the empty header action slots of the captured shell: a back
     * link on the left and the subscription icon link on the right.
     * The icons are the captured back arrow and info icons.
     */
    protected function injectHeaderActions(string $html, array $actions): string
    {
        $left = '<a href="'.e($actions['left']['href']).'" class="dashboard-header-actions-back">'
            .self::BACK_ARROW_SVG."\n        ".$actions['left']['label'].'    </a>';
        $right = '<a href="'.e($actions['right']['href']).'" class="dashboard-header-icon" title="'.e($actions['right']['title']).'" data-toggle="tooltip">'
            .self::INFO_ICON_SVG."\n    </a>";

        $html = preg_replace(
            '/(<div class="dashboard-header-actions-left">)\s*(<\/div>)/',
            '$1'.$left.'$2',
            $html,
            1,
        ) ?? $html;

        $html = preg_replace(
            '/(<div class="dashboard-header-actions-right">)\s*(<\/div>)/',
            '$1'.$right.'$2',
            $html,
            1,
        ) ?? $html;

        return $html;
    }

    /**
     * Back arrow, copied from the captured markup.
     */
    private const BACK_ARROW_SVG = '<svg width="16" height="14" viewBox="0 0 16 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 11.586C8.60954 11.9765 8.60953 12.6096 9 13C9.39046 13.3905 10.0235 13.3905 10.414 13L15.7069 7.70714C16.0974 7.31661 16.0974 6.68345 15.7069 6.29292L10.414 1.00003C10.0235 0.609564 9.39047 0.609564 9 1.00003C8.60953 1.39049 8.60954 2.02356 9 2.41403L12.586 6.00003H1C0.447715 6.00003 0 6.44774 0 7.00003C0 7.55231 0.447715 8.00003 1 8.00003H12.586L9 11.586Z" fill="currentColor"/></svg>';

    /**
     * Info icon, copied from the captured markup.
     */
    private const INFO_ICON_SVG = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 22.5C17.799 22.5 22.5 17.799 22.5 12C22.5 6.20101 17.799 1.5 12 1.5C6.20101 1.5 1.5 6.20101 1.5 12C1.5 17.799 6.20101 22.5 12 22.5Z" fill="currentColor"/><path d="M11 11H13V16.5H11V11Z" fill="white"/><path d="M12 9.5C12.6904 9.5 13.25 8.94036 13.25 8.25C13.25 7.55964 12.6904 7 12 7C11.3096 7 10.75 7.55964 10.75 8.25C10.75 8.94036 11.3096 9.5 12 9.5Z" fill="white"/></svg>';

    /**
     * Row icon, copied from the captured markup. The capture shows a
     * different icon per row and nothing states what picks it, so v1
     * uses this one captured icon for every row (spec gap: icon source
     * unknown).
     */
    public const ROW_ICON_SVG = '<svg class=":class" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M12 17C12.5304 17 13.0391 16.7893 13.4142 16.4142C13.7893 16.0391 14 15.5304 14 15C14 14.4696 13.7893 13.9609 13.4142 13.5858C13.0391 13.2107 12.5304 13 12 13C11.4696 13 10.9609 13.2107 10.5858 13.5858C10.2107 13.9609 10 14.4696 10 15C10 15.5304 10.2107 16.0391 10.5858 16.4142C10.9609 16.7893 11.4696 17 12 17ZM18 8C18.5304 8 19.0391 8.21071 19.4142 8.58579C19.7893 8.96086 20 9.46957 20 10V20C20 20.5304 19.7893 21.0391 19.4142 21.4142C19.0391 21.7893 18.5304 22 18 22H6C5.46957 22 4.96086 21.7893 4.58579 21.4142C4.21071 21.0391 4 20.5304 4 20V10C4 9.46957 4.21071 8.96086 4.58579 8.58579C4.96086 8.21071 5.46957 8 6 8H7V6C7 4.67392 7.52678 3.40215 8.46447 2.46447C9.40215 1.52678 10.6739 1 12 1C12.6566 1 13.3068 1.12933 13.9134 1.3806C14.52 1.63188 15.0712 2.00017 15.5355 2.46447C15.9998 2.92876 16.3681 3.47995 16.6194 4.08658C16.8707 4.69321 17 5.34339 17 6V8H18ZM12 3C11.2043 3 10.4413 3.31607 9.87868 3.87868C9.31607 4.44129 9 5.20435 9 6V8H15V6C15 5.20435 14.6839 4.44129 14.1213 3.87868C13.5587 3.31607 12.7956 3 12 3Z" /></svg>';
}
