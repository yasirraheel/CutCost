{{-- Messages fragment, injected into the captured chat shell. Two-column
layout from the capture: the current subscription row on the left and
the member row on the right, then the thread. --}}

{{-- DELIBERATE DEVIATION (spec G12/G13): the rendered thread is
JS-rendered and its source was not in the capture set, so v1 renders
it with a local component that reads the latest-messages endpoint.
The captured <sharesub-chat> element is NOT rendered here on
purpose: the bundled public/js/app.js globally registers that exact
component, and it decrypts each message body with an RSA private key
(JSEncrypt). No key material is invented or copied into this build,
so with no keys the real component renders every message as "false"
(verified live). The endpoint URLs therefore live on the plain thread
container below as data attributes, in the same names as the captured
element, so the real component can be restored once the encryption
decision lands. TODO: when keys exist, render <sharesub-chat> with
the key attributes and drop this local component; re-check bubble
layout, timestamps, read ticks and the composer against it. The
composer placeholder, the send arrow and the empty-thread text below
are assumptions, not captured copy. --}}
<div class="row">
    <div class="col-12 col-lg-6">
        <div class="chat-column">
            <div class="chat-column-header">
                <span>Chat</span>
                <span>></span>
                <span>Subscription</span>
            </div>
            <div class="chat-column-items">
                @include('chat.partials.conversation-row', [
                    'offer' => $offer,
                    'subscribers' => $offer->members()->where('status', 'current')->where(function ($query) { $query->whereNull('user_id')->orWhere('user_id', '!=', auth()->id()); })->count(),
                    'detailMemberId' => $member->id,
                    'active' => false,
                    'hideOnMobile' => true,
                ])
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
                @include('chat.partials.member-row', ['member' => $member])
            </div>
        </div>
    </div>
    <div class="chat-wrapper" data-chat-thread
        data-send-message-url="/en/my-chat/subscription/{{ $member->id }}/message"
        data-latest-messages-url="/en/my-chat/subscription/{{ $member->id }}/messages/latest"
        data-read-url="/en/my-chat/subscription/{{ $member->id }}/read"
        data-moderate-url="/en/my-chat/subscription/{{ $member->id }}/moderate"
        data-sms-issue-url="/en/joined-subscriptions/{{ $offerHash }}/dispute/sms"
        data-dest-name="{{ $member->username }}"
        data-template-name="{{ $offer->title ?: ($offer->template?->name ?? '') }}"
        data-chunk-length="100">
        <div class="chat-messages">
            <div class="chat-messages-list" data-chat-messages></div>
        </div>
        <div class="chat-wrapper-form">
            <form data-chat-form>
                <textarea name="message" rows="1" placeholder="Write a message" aria-label="Write a message"></textarea>
                <button type="submit" class="btn btn-sharesub blue" aria-label="Send">
                    <svg width="16" height="14" viewBox="0 0 16 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 11.586C8.60954 11.9765 8.60953 12.6096 9 13C9.39046 13.3905 10.0235 13.3905 10.414 13L15.7069 7.70714C16.0974 7.31661 16.0974 6.68345 15.7069 6.29292L10.414 1.00003C10.0235 0.609564 9.39047 0.609564 9 1.00003C8.60953 1.39049 8.60954 2.02356 9 2.41403L12.586 6.00003H1C0.447715 6.00003 0 6.44774 0 7.00003C0 7.55231 0.447715 8.00003 1 8.00003H12.586L9 11.586Z" fill="currentColor"/></svg>
                </button>
            </form>
        </div>
    </div>
</div>
<script>
(function () {
    var root = document.querySelector('[data-chat-thread]');
    if (!root) {
        return;
    }
    var latestUrl = root.getAttribute('data-latest-messages-url');
    var sendUrl = root.getAttribute('data-send-message-url');
    var readUrl = root.getAttribute('data-read-url');
    var moderateUrl = root.getAttribute('data-moderate-url');
    var lastTimestamp = null;
    var lastSignature = '';

    // Nodes are re-queried on every use, never cached: the bundled
    // app.js mounts a Vue instance on #app after this script runs, and
    // that mount replaces the DOM nodes, which drops any listener
    // attached to the pre-mount form. The submit handler therefore
    // lives on the document (event delegation), and render fetches the
    // list element fresh each time.

    function csrfToken() {
        var input = document.querySelector('#logout-form input[name="_token"]');
        if (input && input.value) {
            return input.value;
        }
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function timeOf(timestamp) {
        var date = new Date(timestamp * 1000);
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    function dayOf(timestamp) {
        var date = new Date(timestamp * 1000);
        return date.toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });
    }

    function dayKey(timestamp) {
        var date = new Date(timestamp * 1000);
        return date.getFullYear() + '-' + date.getMonth() + '-' + date.getDate();
    }

    function checkDots(read) {
        var check = document.createElement('span');
        check.className = 'chat-message-check' + (read ? '' : ' is-sent');
        check.title = read ? 'Read' : 'Sent';
        for (var i = 0; i < 2; i++) {
            var dot = document.createElement('span');
            dot.className = 'chat-message-check-dot';
            dot.innerHTML = '<svg width="8" height="8" viewBox="0 0 8 8" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 4.5L3 6.5L7 2" stroke="currentColor" stroke-width="1.2" fill="none"/></svg>';
            check.appendChild(dot);
        }
        return check;
    }

    function flagButton(message) {
        var flag = document.createElement('button');
        flag.type = 'button';
        flag.className = 'chat-message-flag cursor-pointer';
        flag.title = 'Report this message';
        flag.setAttribute('aria-label', 'Report this message');
        flag.setAttribute('data-flag-message-id', message.id);
        if (message.waiting_moderation) {
            flag.classList.add('is-flagged');
            flag.textContent = 'Flagged';
        } else {
            flag.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 21V4M5 4H19L16 9L19 14H5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        }
        return flag;
    }

    // Delegated like the submit handler: the buttons are re-created on
    // every render, and the Vue mount replaces the DOM nodes.
    document.addEventListener('click', function (event) {
        var flag = event.target && event.target.closest ? event.target.closest('[data-flag-message-id]') : null;
        if (!flag || flag.classList.contains('is-flagged')) {
            return;
        }
        fetch(moderateUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
            body: JSON.stringify({ message_id: parseInt(flag.getAttribute('data-flag-message-id'), 10) })
        }).then(function (response) {
            if (response.ok) {
                flag.classList.add('is-flagged');
                flag.textContent = 'Flagged';
            }
        });
    });

    function messageEl(message) {
        var wrap = document.createElement('div');
        wrap.className = 'chat-message ' + (message.is_mine ? 'me' : 'other')
            + (message.is_moderated ? ' moderated' : '')
            + (message.admin_override ? ' moderated-admin' : '');
        var content = document.createElement('div');
        content.className = 'chat-message-content';
        var text = document.createElement('div');
        text.className = 'chat-message-text';
        // The API field is named crypted, but v1 returns the plaintext
        // body there; no decryption is done here.
        text.textContent = message.crypted;
        content.appendChild(text);
        var meta = document.createElement('div');
        meta.className = 'chat-message-meta';
        var time = document.createElement('span');
        time.className = 'chat-message-time';
        time.textContent = timeOf(message.timestamp);
        meta.appendChild(time);
        if (message.is_mine) {
            meta.appendChild(checkDots(message.read));
        }
        content.appendChild(meta);
        wrap.appendChild(content);
        if (!message.is_mine) {
            wrap.appendChild(flagButton(message));
        }
        return wrap;
    }

    function threadList() {
        var current = document.querySelector('[data-chat-thread]');
        return current ? current.querySelector('[data-chat-messages]') : null;
    }

    function render(messages) {
        var list = threadList();
        if (!list) {
            return;
        }
        list.innerHTML = '';
        if (!messages.length) {
            var empty = document.createElement('div');
            empty.className = 'chat-empty';
            empty.textContent = 'No messages yet.';
            list.appendChild(empty);
            return;
        }
        var ordered = messages.slice().sort(function (a, b) { return a.id - b.id; });
        var currentDay = null;
        ordered.forEach(function (message) {
            var key = dayKey(message.timestamp);
            if (key !== currentDay) {
                currentDay = key;
                var day = document.createElement('div');
                day.className = 'chat-day';
                var label = document.createElement('span');
                label.textContent = dayOf(message.timestamp);
                day.appendChild(label);
                list.appendChild(day);
            }
            list.appendChild(messageEl(message));
        });
    }

    function fetchLatest() {
        var url = latestUrl;
        if (lastTimestamp !== null) {
            url += (url.indexOf('?') === -1 ? '?' : '&') + 'input_timestamp=' + lastTimestamp;
        }
        return fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (!data || !data.done) {
                    return;
                }
                lastTimestamp = data.timestamp;
                var signature = (data.messages || []).map(function (m) {
                    return m.id + ':' + m.waiting_moderation + ':' + (m.read ? 1 : 0);
                }).join(',');
                if (signature !== lastSignature) {
                    lastSignature = signature;
                    render(data.messages || []);
                }
                var hasNew = (data.messages || []).some(function (m) { return m.new && !m.is_mine; });
                if (hasNew) {
                    fetch(readUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
                        body: JSON.stringify({})
                    });
                }
            });
    }

    document.addEventListener('submit', function (event) {
        var form = event.target && event.target.closest ? event.target.closest('[data-chat-form]') : null;
        if (!form) {
            return;
        }
        event.preventDefault();
        var textarea = form.querySelector('textarea');
        var value = textarea.value.trim();
        if (!value) {
            return;
        }
        textarea.value = '';
        fetch(sendUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
            body: JSON.stringify({ message: value })
        }).then(function (response) {
            if (response.ok) {
                lastSignature = '';
                fetchLatest();
            } else {
                textarea.value = value;
            }
        });
    });

    fetchLatest();
    setInterval(fetchLatest, 15000);
})();
</script>
