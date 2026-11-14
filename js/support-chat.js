/**
 * Bottom-right "chat with admin support" widget.
 *
 * What the capture has: the real site's launcher was a third-party
 * Intercom embed, which is not in the captured pages or in the
 * bundled app.js (app.js only sums window.intercomUnread into a
 * badge). No fake third-party integration is built here: this file
 * builds a local panel with the captured chat classes and talks to
 * our own support endpoints instead.
 *
 * The panel is built by this script and appended to document.body,
 * outside the #app element the bundled app.js mounts Vue on, so the
 * Vue mount never removes it. The captured "Contact Sharesub Support"
 * links (data-open-support) open the panel too, through a delegated
 * click handler on the document.
 *
 * There is no websocket in this app: the panel polls
 * /en/support-chat/messages every 5 seconds while open, and the
 * launcher badge polls /en/support-chat/unread every 30 seconds.
 * That is polling, not realtime.
 */
(function () {
    var MESSAGES_URL = '/en/support-chat/messages';
    var SEND_URL = '/en/support-chat/message';
    var UNREAD_URL = '/en/support-chat/unread';
    var POLL_MS = 5000;
    var BADGE_MS = 30000;

    if (document.querySelector('[data-support-chat-root]')) {
        return;
    }

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

    // Positioning is an inline style, not a new class or stylesheet:
    // no captured class puts a button bottom-right. Everything else
    // reuses captured classes.
    var root = document.createElement('div');
    root.setAttribute('data-support-chat-root', '');
    root.innerHTML =
        '<button type="button" data-support-chat-toggle class="btn-sharesub blue" aria-label="Chat with admin support" aria-expanded="false"'
        + ' style="position:fixed;right:20px;bottom:20px;z-index:1050;border-radius:50%;width:56px;height:56px;padding:0;display:flex;align-items:center;justify-content:center;">'
        + '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 22.5C17.799 22.5 22.5 17.799 22.5 12C22.5 6.20101 17.799 1.5 12 1.5C6.20101 1.5 1.5 6.20101 1.5 12C1.5 17.799 6.20101 22.5 12 22.5Z" fill="currentColor"/><path d="M11 11H13V16.5H11V11Z" fill="white"/><path d="M12 9.5C12.6904 9.5 13.25 8.94036 13.25 8.25C13.25 7.55964 12.6904 7 12 7C11.3096 7 10.75 7.55964 10.75 8.25C10.75 8.94036 11.3096 9.5 12 9.5Z" fill="white"/></svg>'
        + '<span data-support-chat-badge hidden style="position:absolute;top:-4px;right:-4px;min-width:20px;height:20px;border-radius:10px;background:#e5484d;color:#fff;font-size:11px;line-height:20px;text-align:center;padding:0 5px;"></span>'
        + '</button>'
        + '<div data-support-chat-panel hidden class="chat-wrapper" role="dialog" aria-label="Chat with admin support"'
        + ' style="position:fixed;right:20px;bottom:88px;z-index:1050;width:360px;max-width:calc(100vw - 40px);">'
        + '<div class="chat-column-header"><span>Chat with admin support</span>'
        + '<button type="button" data-support-chat-close class="btn btn-light btn-sm" aria-label="Close chat">Close</button></div>'
        + '<div class="chat-messages"><div class="chat-messages-list" data-support-chat-messages></div></div>'
        + '<div class="chat-wrapper-form"><form data-support-chat-form>'
        + '<textarea name="message" rows="1" placeholder="Write a message" aria-label="Write a message"></textarea>'
        + '<button type="submit" class="btn btn-sharesub blue" aria-label="Send">'
        + '<svg width="16" height="14" viewBox="0 0 16 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 11.586C8.60954 11.9765 8.60953 12.6096 9 13C9.39046 13.3905 10.0235 13.3905 10.414 13L15.7069 7.70714C16.0974 7.31661 16.0974 6.68345 15.7069 6.29292L10.414 1.00003C10.0235 0.609564 9.39047 0.609564 9 1.00003C8.60953 1.39049 8.60954 2.02356 9 2.41403L12.586 6.00003H1C0.447715 6.00003 0 6.44774 0 7.00003C0 7.55231 0.447715 8.00003 1 8.00003H12.586L9 11.586Z" fill="currentColor"/></svg>'
        + '</button></form></div></div>';
    document.body.appendChild(root);

    var pollTimer = null;
    var lastSignature = '';

    function panel() {
        return document.querySelector('[data-support-chat-panel]');
    }

    function list() {
        var current = panel();
        return current ? current.querySelector('[data-support-chat-messages]') : null;
    }

    function isOpen() {
        var current = panel();
        return current ? !current.hidden : false;
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

    function messageEl(message) {
        var wrap = document.createElement('div');
        wrap.className = 'chat-message ' + (message.is_mine ? 'me' : 'other');
        var content = document.createElement('div');
        content.className = 'chat-message-content';
        var text = document.createElement('div');
        text.className = 'chat-message-text';
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
        return wrap;
    }

    function render(messages) {
        var target = list();
        if (!target) {
            return;
        }
        target.innerHTML = '';
        if (!messages.length) {
            var empty = document.createElement('div');
            empty.className = 'chat-empty';
            empty.textContent = 'No messages yet. Write to admin support below.';
            target.appendChild(empty);
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
                target.appendChild(day);
            }
            target.appendChild(messageEl(message));
        });
        target.scrollTop = target.scrollHeight;
    }

    function renderError() {
        var target = list();
        if (!target) {
            return;
        }
        target.innerHTML = '';
        var empty = document.createElement('div');
        empty.className = 'chat-empty';
        empty.textContent = 'Could not load messages. Try again.';
        target.appendChild(empty);
    }

    function fetchMessages() {
        return fetch(MESSAGES_URL, { headers: { Accept: 'application/json' } })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('messages failed');
                }
                return response.json();
            })
            .then(function (data) {
                if (!data || !data.done) {
                    return;
                }
                var signature = (data.messages || []).map(function (m) {
                    return m.id + ':' + (m.read ? 1 : 0);
                }).join(',');
                if (signature !== lastSignature) {
                    lastSignature = signature;
                    render(data.messages || []);
                }
                setBadge(0);
            })
            .catch(renderError);
    }

    function setBadge(count) {
        var badge = document.querySelector('[data-support-chat-badge]');
        if (!badge) {
            return;
        }
        if (count > 0) {
            badge.hidden = false;
            badge.textContent = String(count);
        } else {
            badge.hidden = true;
            badge.textContent = '';
        }
    }

    function fetchUnread() {
        return fetch(UNREAD_URL)
            .then(function (response) { return response.text(); })
            .then(function (text) {
                var count = parseInt(text, 10);
                setBadge(isNaN(count) ? 0 : count);
            })
            .catch(function () { /* badge stays as it is */ });
    }

    function openPanel() {
        var current = panel();
        if (!current) {
            return;
        }
        current.hidden = false;
        var toggle = document.querySelector('[data-support-chat-toggle]');
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'true');
        }
        lastSignature = '';
        fetchMessages();
        if (pollTimer === null) {
            pollTimer = setInterval(fetchMessages, POLL_MS);
        }
    }

    function closePanel() {
        var current = panel();
        if (!current) {
            return;
        }
        current.hidden = true;
        var toggle = document.querySelector('[data-support-chat-toggle]');
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
        }
        if (pollTimer !== null) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
    }

    document.addEventListener('click', function (event) {
        var target = event.target && event.target.closest ? event.target.closest('[data-support-chat-toggle], [data-support-chat-close], [data-open-support]') : null;
        if (!target) {
            return;
        }
        if (target.hasAttribute('data-open-support')) {
            // The captured support link points at #offline (or the help
            // site in the chat shell): open the local panel instead.
            event.preventDefault();
            openPanel();
            return;
        }
        if (target.hasAttribute('data-support-chat-close')) {
            closePanel();
            return;
        }
        if (isOpen()) {
            closePanel();
        } else {
            openPanel();
        }
    });

    // Delegated submit: the form lives outside #app, but delegation
    // keeps this working even if the page scripts re-render around it.
    document.addEventListener('submit', function (event) {
        var form = event.target && event.target.closest ? event.target.closest('[data-support-chat-form]') : null;
        if (!form) {
            return;
        }
        event.preventDefault();
        var textarea = form.querySelector('textarea[name="message"]');
        var text = textarea ? textarea.value.trim() : '';
        if (!text) {
            return;
        }
        var button = form.querySelector('button[type="submit"]');
        if (button) {
            button.disabled = true;
        }
        fetch(SEND_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken(), Accept: 'application/json' },
            body: JSON.stringify({ message: text })
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('send failed');
                }
                return response.json();
            })
            .then(function () {
                if (textarea) {
                    textarea.value = '';
                }
                fetchMessages();
            })
            .catch(function () {
                if (textarea) {
                    textarea.value = text;
                }
            })
            .finally(function () {
                if (button) {
                    button.disabled = false;
                }
            });
    });

    fetchUnread();
    setInterval(fetchUnread, BADGE_MS);
})();
