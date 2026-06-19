{{--
    PHASE I.2A STEP 2 — Embedded Chat Widget
    =========================================
    ASN dapat melakukan Live Chat langsung dari gaspul_api
    tanpa berpindah ke esaraku_helpdesk.

    Arsitektur:
    - Widget JS fetch langsung ke esaraku_helpdesk/api/chat/*
    - Auth: session cookie e-saraku-helpdesk-session (same-origin: http://localhost)
    - CSRF: XSRF-TOKEN cookie dari /api/chat/init (bootstrap sekali saat pertama buka)
    - Polling: interval 5 detik (bukan long-poll — menghindari blokir PHP thread gaspul_api)
    - Tidak ada redirect browser ke helpdesk
    - Dipanggil dari: helpdesk-floating-button.blade.php

    Security:
    - Semua pesan di-render via textContent (BUKAN innerHTML)
    - CSRF header dikirim di setiap POST
    - credentials: 'include' selalu aktif
--}}

@auth
@if(auth()->user()->role === 'ASN')

{{-- ═══════════════════════════════════════════════════════════════════════════
     WIDGET CONTAINER
     ═══════════════════════════════════════════════════════════════════════════ --}}
<div
    id="helpdeskChatWidget"
    role="dialog"
    aria-modal="true"
    aria-label="Live Chat e_SARAku Helpdesk"
    style="
        display: none;
        position: fixed;
        bottom: 108px;
        right: 24px;
        z-index: 9998;
        width: 360px;
        max-width: calc(100vw - 32px);
        height: 520px;
        max-height: calc(100vh - 140px);
        background: #ffffff;
        border-radius: 20px;
        box-shadow: 0 24px 64px rgba(0,0,0,0.18), 0 4px 16px rgba(37,99,235,0.12);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        transform: translateY(20px) scale(0.96);
        opacity: 0;
        transition: transform 0.22s cubic-bezier(.34,1.56,.64,1), opacity 0.18s ease;
        pointer-events: none;
    "
>

    {{-- ── HEADER ─────────────────────────────────────────────────────────── --}}
    <div style="
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        padding: 14px 16px;
        display: flex;
        align-items: center;
        gap: 10px;
        flex-shrink: 0;
    ">
        {{-- Avatar / Logo --}}
        <div style="
            width: 36px; height: 36px; border-radius: 50%;
            background: rgba(255,255,255,0.18);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        ">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" fill="white"/>
            </svg>
        </div>

        {{-- Title + status --}}
        <div style="flex:1; min-width:0;">
            <div style="font-size:0.875rem; font-weight:700; color:#fff; line-height:1.2;">
                <span id="widgetTitleText">e_SARAku Helpdesk</span><span id="widgetTitleUnread" style="display:none;"></span>
            </div>
            <div id="widgetStatusLine" style="font-size:0.68rem; color:rgba(255,255,255,0.75); margin-top:1px;">
                Menghubungkan...
            </div>
        </div>

        {{-- Minimize --}}
        <button
            onclick="helpdeskChatMinimize()"
            aria-label="Minimize chat"
            style="
                background:rgba(255,255,255,0.12); border:none; cursor:pointer;
                width:28px; height:28px; border-radius:6px;
                display:flex; align-items:center; justify-content:center;
                color:rgba(255,255,255,0.85); transition:background 0.15s;
            "
            onmouseenter="this.style.background='rgba(255,255,255,0.22)'"
            onmouseleave="this.style.background='rgba(255,255,255,0.12)'"
        >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
                <line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
        </button>

        {{-- Close --}}
        <button
            onclick="helpdeskChatClose()"
            aria-label="Tutup chat"
            style="
                background:rgba(255,255,255,0.12); border:none; cursor:pointer;
                width:28px; height:28px; border-radius:6px;
                display:flex; align-items:center; justify-content:center;
                color:rgba(255,255,255,0.85); transition:background 0.15s;
            "
            onmouseenter="this.style.background='rgba(255,255,255,0.22)'"
            onmouseleave="this.style.background='rgba(255,255,255,0.12)'"
        >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <line x1="18" y1="6" x2="6" y2="18"/>
                <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>
    </div>

    {{-- ── STATE: LOADING ─────────────────────────────────────────────────── --}}
    <div id="widgetStateLoading" style="
        flex:1; display:flex; flex-direction:column;
        align-items:center; justify-content:center; gap:12px;
        padding:24px;
    ">
        <svg style="animation:widgetSpin 0.8s linear infinite;"
             width="28" height="28" viewBox="0 0 24 24" fill="none"
             stroke="#2563eb" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
            <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
        </svg>
        <p style="font-size:0.8rem; color:#6b7280; margin:0;">Memuat...</p>
    </div>

    {{-- ── STATE: NO CONVERSATION ──────────────────────────────────────────── --}}
    <div id="widgetStateEmpty" style="
        display:none; flex:1; flex-direction:column;
        align-items:center; justify-content:center;
        padding:32px 24px; text-align:center; gap:16px;
    ">
        <div style="
            width:64px; height:64px; border-radius:50%;
            background:#dbeafe; display:flex;
            align-items:center; justify-content:center;
        ">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none"
                 stroke="#2563eb" stroke-width="1.5" stroke-linecap="round"
                 stroke-linejoin="round" aria-hidden="true">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
        </div>
        <div>
            <p style="font-size:0.9rem; font-weight:600; color:#111827; margin:0 0 6px;">
                Halo, ada yang bisa kami bantu?
            </p>
            <p style="font-size:0.78rem; color:#6b7280; margin:0;">
                Mulai percakapan dan operator kami akan segera merespons.
            </p>
        </div>
        <button
            id="widgetBtnStart"
            onclick="helpdeskChatStartConversation()"
            style="
                background:linear-gradient(135deg,#2563eb,#1d4ed8);
                color:white; border:none; cursor:pointer;
                padding:10px 24px; border-radius:12px;
                font-size:0.875rem; font-weight:600;
                transition:opacity 0.15s, box-shadow 0.15s;
                box-shadow:0 4px 12px rgba(37,99,235,0.35);
            "
            onmouseenter="this.style.opacity='0.9'"
            onmouseleave="this.style.opacity='1'"
        >
            Mulai Percakapan
        </button>
        <div id="widgetStartError" style="
            display:none; font-size:0.75rem; color:#dc2626;
            background:#fef2f2; border:1px solid #fecaca;
            border-radius:8px; padding:8px 12px; width:100%;
        "></div>
    </div>

    {{-- ── STATE: ACTIVE CONVERSATION ─────────────────────────────────────── --}}
    <div id="widgetStateActive" style="
        display:none; flex:1; flex-direction:column; min-height:0;
    ">
        {{-- Message list --}}
        <div
            id="widgetMessageList"
            style="
                flex:1; overflow-y:auto; padding:12px 14px;
                display:flex; flex-direction:column; gap:8px;
                scroll-behavior:smooth;
            "
        >
            {{-- Pesan diisi oleh JS --}}
        </div>

        {{-- Load older messages --}}
        <div id="widgetLoadMore" style="
            display:none; text-align:center;
            padding:6px 0 0;
        ">
            <button
                onclick="helpdeskChatLoadMore()"
                style="
                    font-size:0.72rem; color:#2563eb;
                    background:none; border:none; cursor:pointer;
                    text-decoration:underline; padding:4px 8px;
                "
            >Muat Pesan Lama</button>
        </div>

        {{-- Conversation closed notice --}}
        <div id="widgetClosedNotice" style="
            display:none; background:#f9fafb; border-top:1px solid #e5e7eb;
            padding:10px 14px; text-align:center;
            font-size:0.75rem; color:#6b7280;
        ">
            Percakapan telah ditutup. &nbsp;
            <button
                onclick="helpdeskChatNewConversation()"
                style="color:#2563eb; background:none; border:none; cursor:pointer; font-size:0.75rem; text-decoration:underline;"
            >Mulai baru</button>
        </div>

        {{-- Error banner --}}
        <div id="widgetErrorBanner" style="
            display:none; background:#fef2f2; border-top:1px solid #fecaca;
            padding:8px 14px; text-align:center;
        ">
            <span id="widgetErrorText" style="font-size:0.75rem; color:#dc2626;"></span>
        </div>

        {{-- Input footer --}}
        <div
            id="widgetInputArea"
            style="
                border-top:1px solid #e5e7eb; padding:10px 12px;
                display:flex; align-items:flex-end; gap:8px;
                background:#fff; flex-shrink:0;
            "
        >
            <textarea
                id="widgetTextarea"
                placeholder="Ketik pesan... (Enter kirim, Shift+Enter baris baru)"
                rows="1"
                style="
                    flex:1; resize:none; border:1px solid #d1d5db;
                    border-radius:12px; padding:8px 12px;
                    font-size:0.8rem; line-height:1.5;
                    font-family:inherit; outline:none;
                    max-height:96px; overflow-y:auto;
                    transition:border-color 0.15s;
                "
                onfocus="this.style.borderColor='#2563eb'"
                onblur="this.style.borderColor='#d1d5db'"
                oninput="helpdeskChatAutoResize(this)"
                onkeydown="helpdeskChatKeydown(event)"
                aria-label="Pesan chat"
            ></textarea>
            <button
                id="widgetSendBtn"
                onclick="helpdeskChatSend()"
                aria-label="Kirim pesan"
                style="
                    width:36px; height:36px; border-radius:50%;
                    background:linear-gradient(135deg,#2563eb,#1d4ed8);
                    border:none; cursor:pointer;
                    display:flex; align-items:center; justify-content:center;
                    flex-shrink:0; transition:opacity 0.15s, box-shadow 0.15s;
                    box-shadow:0 2px 8px rgba(37,99,235,0.35);
                "
                onmouseenter="this.style.opacity='0.88'"
                onmouseleave="this.style.opacity='1'"
            >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                     stroke="white" stroke-width="2.5" stroke-linecap="round"
                     stroke-linejoin="round" aria-hidden="true">
                    <line x1="22" y1="2" x2="11" y2="13"/>
                    <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- ── STATE: ERROR ─────────────────────────────────────────────────────── --}}
    <div id="widgetStateError" style="
        display:none; flex:1; flex-direction:column;
        align-items:center; justify-content:center;
        padding:32px 24px; text-align:center; gap:12px;
    ">
        <div style="
            width:52px; height:52px; border-radius:50%;
            background:#fef2f2; display:flex;
            align-items:center; justify-content:center;
        ">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none"
                 stroke="#dc2626" stroke-width="2" stroke-linecap="round"
                 stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
        </div>
        <p id="widgetErrorMainMsg" style="font-size:0.85rem; color:#374151; margin:0;"></p>
        <button
            onclick="helpdeskChatRetry()"
            style="
                font-size:0.8rem; color:#2563eb;
                background:none; border:1px solid #bfdbfe;
                border-radius:8px; padding:6px 16px; cursor:pointer;
                transition:background 0.15s;
            "
            onmouseenter="this.style.background='#eff6ff'"
            onmouseleave="this.style.background='none'"
        >Coba Lagi</button>
    </div>

</div>{{-- #helpdeskChatWidget --}}


{{-- ═══════════════════════════════════════════════════════════════════════════
     JAVASCRIPT
     ═══════════════════════════════════════════════════════════════════════════ --}}
<script>
(function () {
    'use strict';

    // ── Config ────────────────────────────────────────────────────────────────
    // HD_BASE: URL helpdesk dilihat dari browser (same-origin: http://localhost)
    // Diambil dari config('services.helpdesk.url') — BUKAN env() langsung, supaya
    // tetap benar setelah `php artisan config:cache` (Phase I.2A-B).
    var HD_BASE    = '{{ rtrim(config("services.helpdesk.url"), "/") }}';
    var POLL_MS    = 5000;       // interval polling saat widget terbuka
    var MAX_RENDER = 100;        // maksimum pesan yang dirender

    var EP = {
        init:     HD_BASE + '/api/chat/init',
        active:   HD_BASE + '/api/chat/conversations/active',
        create:   HD_BASE + '/api/chat/conversations',
        messages: HD_BASE + '/api/chat/conversations/{id}/messages',
        send:     HD_BASE + '/api/chat/conversations/{id}/messages',
        poll:     HD_BASE + '/api/chat/conversations/{id}/poll',
        unread:   HD_BASE + '/api/chat/unread-count',
        close:    HD_BASE + '/api/chat/conversations/{id}/close',
        read:     HD_BASE + '/api/chat/conversations/{id}/messages/read',
    };
    var UNREAD_POLL_MS = 30000; // Step 7: ≤1 request/30s saat widget tertutup

    // ── SSO silent bootstrap — dipanggil hanya jika sesi helpdesk belum ada ────
    // Tidak menggunakan window.location (tidak ada redirect browser). fetch()
    // mengikuti redirect /sso/login secara internal dan tetap menyimpan cookie
    // sesi yang di-set oleh respons redirect tersebut (same-origin: http://localhost).
    var TOKEN_ENDPOINT_WIDGET = '{{ url("/api/helpdesk-token") }}';
    var CSRF_TOKEN_WIDGET     = document.querySelector('meta[name="csrf-token"]').content;
    var _ssoAttempted         = false;

    function silentSso() {
        return fetch(TOKEN_ENDPOINT_WIDGET, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN_WIDGET,
                'Accept':       'application/json',
                'Content-Type': 'application/json',
            },
        })
        .then(function (res) {
            if (!res.ok) throw new Error('token_failed');
            return res.json();
        })
        .then(function (data) {
            if (!data.token || !data.helpdesk_url) throw new Error('token_invalid');
            var ssoUrl = data.helpdesk_url.replace(/\/$/, '')
                + '/sso/login'
                + '?token=' + encodeURIComponent(data.token)
                + '&return_to=' + encodeURIComponent('/chat');

            // fetch dengan redirect:'follow' (default) — browser menyimpan cookie
            // Set-Cookie dari setiap hop redirect tanpa mengubah window.location.
            return fetch(ssoUrl, { credentials: 'include' });
        });
    }

    // ── State ─────────────────────────────────────────────────────────────────
    var _open            = false;
    var _bootstrapped    = false;
    var _csrfToken       = '';
    var _conversation    = null;    // {id, status, operator, subject}
    var _messages        = [];      // [{id, sender_type, body, created_at}]
    var _seenIds         = new Set();
    var _lastMsgId       = 0;
    var _pollTimer       = null;
    var _totalMessages   = 0;
    var _currentPage     = 1;
    var _sending         = false;
    var _starting        = false;
    var _csrfRetried     = false;

    // ── Unread / notification state (Step 3) ────────────────────────────────────
    var _unreadCount        = 0;
    var _notifiedThisCycle  = false;

    // ── DOM refs ──────────────────────────────────────────────────────────────
    var widget       = document.getElementById('helpdeskChatWidget');
    var titleUnread  = document.getElementById('widgetTitleUnread');
    var stLoading    = document.getElementById('widgetStateLoading');
    var stEmpty      = document.getElementById('widgetStateEmpty');
    var stActive     = document.getElementById('widgetStateActive');
    var stError      = document.getElementById('widgetStateError');
    var msgList      = document.getElementById('widgetMessageList');
    var textarea     = document.getElementById('widgetTextarea');
    var statusLine   = document.getElementById('widgetStatusLine');
    var errorBanner  = document.getElementById('widgetErrorBanner');
    var errorText    = document.getElementById('widgetErrorText');
    var closedNotice = document.getElementById('widgetClosedNotice');
    var inputArea    = document.getElementById('widgetInputArea');
    var loadMoreBtn  = document.getElementById('widgetLoadMore');
    var startErrDiv  = document.getElementById('widgetStartError');
    var errorMainMsg = document.getElementById('widgetErrorMainMsg');

    // ── Expose public API (dipanggil dari helpdesk-floating-button) ───────────
    window.helpdeskChatOpen  = openWidget;
    window.helpdeskChatClose = closeWidget;
    window.helpdeskChatMinimize = minimizeWidget;

    // ── Expose handlers (dipanggil dari HTML onclick) ─────────────────────────
    window.helpdeskChatStartConversation = startConversation;
    window.helpdeskChatSend              = sendMessage;
    window.helpdeskChatKeydown           = keydownHandler;
    window.helpdeskChatAutoResize        = autoResize;
    window.helpdeskChatRetry             = retryInit;
    window.helpdeskChatLoadMore          = loadMore;
    window.helpdeskChatNewConversation   = newConversation;

    // =========================================================================
    // OPEN / CLOSE
    // =========================================================================

    function openWidget() {
        if (_open) return;
        _open = true;

        widget.style.display       = 'flex';
        widget.style.pointerEvents = 'all';

        // Animate in
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                widget.style.transform = 'translateY(0) scale(1)';
                widget.style.opacity   = '1';
            });
        });

        // Focus textarea jika sudah ada conversation aktif
        if (_conversation && _conversation.status !== 'closed') {
            setTimeout(function () { textarea && textarea.focus(); }, 250);
        }

        // Bootstrap + load (hanya sekali, atau jika belum pernah)
        if (!_bootstrapped) {
            showState('loading');
            doInit();
        } else if (_conversation) {
            showState('active');
            startPolling();
            markConversationRead(); // Step 4: refresh + reset unread saat widget dibuka
        } else {
            showState('empty');
        }
    }

    function closeWidget() {
        if (!_open) return;

        widget.style.transform  = 'translateY(20px) scale(0.96)';
        widget.style.opacity    = '0';

        setTimeout(function () {
            widget.style.display       = 'none';
            widget.style.pointerEvents = 'none';
            _open = false;
            stopPolling();
        }, 200);
    }

    function minimizeWidget() {
        closeWidget();
    }

    // =========================================================================
    // UNREAD COUNTER & NOTIFICATIONS (Step 3)
    // =========================================================================

    // Dipanggil dari helpdesk-floating-button.blade.php setiap 30 detik
    // (count, increased) — increased = true jika count bertambah dibanding cycle sebelumnya
    window.helpdeskChatOnUnreadUpdate = function (count, increased) {
        _unreadCount = count;
        updateTitleBadge(count);

        // Deteksi pesan baru HANYA saat widget tertutup — sesuai Step 5
        if (!_open && increased && count > 0) {
            pulseFabBadge();
            maybeNotify(count);
        }

        // Reset notifikasi flag begitu unread balik ke 0 (percakapan baru dibaca)
        if (count === 0) {
            _notifiedThisCycle = false;
        }
    };

    function updateTitleBadge(count) {
        if (!titleUnread) return;
        if (count > 0) {
            titleUnread.textContent = ' (' + (count > 99 ? '99+' : count) + ')';
            titleUnread.style.display = 'inline';
        } else {
            titleUnread.style.display = 'none';
        }
    }

    function pulseFabBadge() {
        var badge = document.getElementById('helpdeskUnreadBadge');
        if (!badge) return;
        badge.classList.remove('helpdesk-badge-pulse');
        // Restart animasi meski sudah pernah dipicu sebelumnya
        void badge.offsetWidth;
        badge.classList.add('helpdesk-badge-pulse');
    }

    // Step 6 — Browser notification, opsional, tidak boleh minta permission otomatis,
    // maksimal 1 notifikasi per polling cycle (dijaga oleh _notifiedThisCycle).
    function maybeNotify(count) {
        if (_notifiedThisCycle) return;
        if (typeof Notification === 'undefined') return;
        if (Notification.permission !== 'granted') return;

        try {
            var n = new Notification('e_SARAku Helpdesk', {
                body: count === 1
                    ? 'Anda memiliki 1 pesan baru dari operator.'
                    : 'Anda memiliki ' + count + ' pesan baru dari operator.',
                tag: 'helpdesk-chat-unread', // tag sama → browser otomatis ganti, tidak menumpuk
            });
            n.onclick = function () {
                window.focus();
                helpdeskChatOpen();
                n.close();
            };
        } catch (e) { /* Notification gagal dibuat — tidak kritikal */ }

        _notifiedThisCycle = true;
    }

    // Dipanggil saat widget dibuka dan ada conversation aktif — tandai pesan terbaca
    function markConversationRead() {
        if (!_conversation) return;
        fetch(EP.read.replace('{id}', _conversation.id), {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Accept':       'application/json',
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': _csrfToken,
            },
        })
        .then(function () {
            // Reset badge seketika — counter kembali 0 tanpa menunggu polling 30s berikutnya
            _unreadCount = 0;
            updateTitleBadge(0);
            _notifiedThisCycle = false;
            if (typeof window.helpdeskSetUnreadBadge === 'function') {
                window.helpdeskSetUnreadBadge(0);
            }
        })
        .catch(function () { /* silent — tidak kritikal untuk UX chat */ });
    }

    // =========================================================================
    // INIT — bootstrap CSRF → load active conversation
    // =========================================================================

    function doInit() {
        // Step 1: GET /api/chat/init — dapatkan CSRF token dari response body.
        // Tidak menggunakan readCookie('XSRF-TOKEN') karena cookie cross-origin
        // tidak bisa dibaca via document.cookie dari domain yang berbeda.
        fetch(EP.init, { credentials: 'include' })
            .then(function (res) {
                if (!res.ok) throw { status: res.status };
                return res.json();
            })
            .then(function (data) {
                _bootstrapped = true;
                _csrfToken    = data.csrf_token || '';
                return loadActiveConversation();
            })
            .catch(function (err) {
                showMainError(mapError(err.status || 0));
            });
    }

    function retryInit() {
        showState('loading');
        _bootstrapped  = false;
        _ssoAttempted  = false;
        doInit();
    }

    // =========================================================================
    // ACTIVE CONVERSATION
    // =========================================================================

    function loadActiveConversation() {
        return fetch(EP.active, {
            credentials: 'include',
            headers: { 'Accept': 'application/json' },
        })
        .then(function (res) {
            if (res.status === 401) throw { status: 401 };
            if (!res.ok) throw { status: res.status };
            return res.json();
        })
        .then(function (data) {
            if (data.conversation) {
                _conversation = data.conversation;
                setStatusLine(_conversation);
                showState('active');
                return loadMessages(1);
            } else {
                // Tidak ada conversation aktif — auto-create, tanpa interaksi tambahan ASN
                return autoCreateConversation();
            }
        })
        .catch(function (err) {
            if (err.status === 401 && !_ssoAttempted) {
                // Sesi helpdesk belum terbentuk — lakukan SSO silent sekali, lalu retry
                _ssoAttempted = true;
                return silentSso()
                    .then(function () { return loadActiveConversation(); })
                    .catch(function () {
                        showMainError(mapError(401));
                    });
            } else if (err.status === 401) {
                showMainError(mapError(401));
            } else {
                showMainError(mapError(err.status || 0));
            }
        });
    }

    // =========================================================================
    // MESSAGES
    // =========================================================================

    function loadMessages(page) {
        if (!_conversation) return Promise.resolve();
        page = page || 1;
        var url = EP.messages.replace('{id}', _conversation.id) + '?page=' + page;

        return fetch(url, {
            credentials: 'include',
            headers: { 'Accept': 'application/json' },
        })
        .then(function (res) {
            if (!res.ok) throw { status: res.status };
            return res.json();
        })
        .then(function (data) {
            var msgs = data.data || data.messages || [];
            var total = data.total || msgs.length;
            _totalMessages = total;
            _currentPage   = page;

            if (page === 1) {
                _seenIds  = new Set();
                _messages = [];
                clearMessageList();
            }

            // Ambil MAX_RENDER pesan terbaru
            var toRender = msgs.slice(-MAX_RENDER);
            for (var i = 0; i < toRender.length; i++) {
                addMessage(toRender[i], false);
            }

            // Tombol "Muat Pesan Lama" jika ada lebih
            if (_messages.length >= MAX_RENDER && total > MAX_RENDER) {
                loadMoreBtn.style.display = 'block';
            } else {
                loadMoreBtn.style.display = 'none';
            }

            // Sapaan welcome — hanya dirender lokal (tidak disimpan ke DB), sekali
            // setelah conversation baru otomatis dibuat oleh autoCreateConversation().
            if (_autoCreatedWelcome) {
                _autoCreatedWelcome = false;
                renderMessage({
                    id: 'welcome-' + _conversation.id,
                    type: 'system',
                    sender_type: 'system',
                    body: 'Terima kasih telah menghubungi Helpdesk e_SARAku. Silakan tuliskan pertanyaan atau kendala Anda.',
                    created_at: new Date().toISOString(),
                }, false);
            }

            scrollToBottom();

            // Status conversation
            if (_conversation.status === 'closed') {
                showClosedState();
            } else {
                startPolling();
                if (page === 1 && _open) markConversationRead(); // Step 4: load awal saat widget terbuka
            }
        })
        .catch(function (err) {
            showBannerError(mapError(err.status || 0));
        });
    }

    function loadMore() {
        loadMessages(_currentPage + 1);
    }

    // =========================================================================
    // START CONVERSATION
    // =========================================================================

    // UX Fix — Auto Create Conversation: dipanggil otomatis dari loadActiveConversation()
    // saat ASN tidak punya conversation aktif. Tidak ada interaksi tambahan (tanpa tombol).
    var _autoCreatedWelcome = false;

    function autoCreateConversation() {
        showState('loading');
        return createConversationRequest()
            .then(function () {
                _autoCreatedWelcome = true;
                return loadActiveConversation();
            })
            .catch(function (err) {
                showMainError(mapError(err.status || 0));
            });
    }

    function startConversation() {
        if (_starting) return;
        _starting = true;

        var btn = document.getElementById('widgetBtnStart');
        if (btn) { btn.disabled = true; btn.style.opacity = '0.6'; }
        startErrDiv.style.display = 'none';

        createConversationRequest()
            .then(function () {
                _autoCreatedWelcome = true;
                return loadActiveConversation();
            })
            .catch(function (err) {
                var msg = mapError(err.status || 0);
                startErrDiv.textContent = msg;
                startErrDiv.style.display = 'block';
            })
            .finally(function () {
                _starting = false;
                if (btn) { btn.disabled = false; btn.style.opacity = '1'; }
            });
    }

    // Request mentah POST /conversations — dipakai baik oleh auto-create maupun
    // tombol manual "Mulai baru" di closed state.
    function createConversationRequest() {
        return fetch(EP.create, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Accept':        'application/json',
                'Content-Type':  'application/json',
                'X-XSRF-TOKEN':  _csrfToken,
            },
            body: JSON.stringify({
                subject: 'Bantuan e_SARAku',
                message: 'Saya membutuhkan bantuan terkait e_SARAku.',
            }),
        })
        .then(function (res) {
            // 409 = sudah ada conversation aktif — bukan error, lanjut load existing
            if (res.status === 409) return null;
            if (!res.ok) throw { status: res.status };
            return res.json();
        });
    }

    function newConversation() {
        _conversation = null;
        _messages     = [];
        _seenIds      = new Set();
        _lastMsgId    = 0;
        clearMessageList();
        closedNotice.style.display = 'none';
        autoCreateConversation();
    }

    // =========================================================================
    // SEND MESSAGE
    // =========================================================================

    function sendMessage() {
        if (!textarea || _sending || !_conversation) return;
        var body = textarea.value.trim();
        if (!body) return;

        _sending = true;
        textarea.value = '';
        autoResize(textarea);

        var url = EP.send.replace('{id}', _conversation.id);

        // Optimistic render
        var tempId = 'temp-' + Date.now();
        renderMessage({ id: tempId, sender_type: 'user', body: body, created_at: new Date().toISOString() }, true);
        scrollToBottom();

        fetch(url, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Accept':       'application/json',
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': _csrfToken,
            },
            body: JSON.stringify({ body: body }),
        })
        .then(function (res) {
            if (!res.ok) throw { status: res.status };
            return res.json();
        })
        .then(function (data) {
            // Hapus pesan temp, tambahkan pesan server
            var tempEl = document.getElementById('msg-' + tempId);
            if (tempEl) tempEl.remove();
            _seenIds.delete(tempId);

            _csrfRetried = false;
            if (data.message) addMessage(data.message, true);
            scrollToBottom();
        })
        .catch(function (err) {
            // Hapus temp message
            var tempEl = document.getElementById('msg-' + tempId);
            if (tempEl) tempEl.remove();
            _seenIds.delete(tempId);

            if (err.status === 419 && !_csrfRetried) {
                // Token expired — refresh sekali lalu kirim ulang
                _csrfRetried = true;
                _sending     = false;
                textarea.value = body;
                autoResize(textarea);
                refreshCsrfAndRetry(function () { sendMessage(); });
                return;
            }

            // Rollback: kembalikan teks ke textarea
            textarea.value = body;
            autoResize(textarea);
            _csrfRetried = false;
            showBannerError(mapError(err.status || 0));
        })
        .finally(function () {
            _sending = false;
        });
    }

    function keydownHandler(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    }

    function autoResize(el) {
        el.style.height = 'auto';
        el.style.height = Math.min(el.scrollHeight, 96) + 'px';
    }

    // =========================================================================
    // POLLING (interval, bukan long-poll)
    // =========================================================================

    function startPolling() {
        if (_pollTimer) return;
        if (!_conversation || _conversation.status === 'closed') return;

        _pollTimer = setInterval(function () {
            if (!_open || !_conversation || _conversation.status === 'closed') {
                stopPolling();
                return;
            }
            pollMessages();
        }, POLL_MS);
    }

    function stopPolling() {
        if (_pollTimer) {
            clearInterval(_pollTimer);
            _pollTimer = null;
        }
    }

    function pollMessages() {
        if (!_conversation) return;
        var url = EP.poll.replace('{id}', _conversation.id) + '?since=' + _lastMsgId;

        fetch(url, {
            credentials: 'include',
            headers: { 'Accept': 'application/json' },
        })
        .then(function (res) {
            if (res.status === 401 || res.status === 403) {
                stopPolling();
                return null;
            }
            if (!res.ok) return null;
            return res.json();
        })
        .then(function (data) {
            if (!data) return;

            var msgs = data.messages || [];
            var added = false;
            for (var i = 0; i < msgs.length; i++) {
                if (addMessage(msgs[i], true)) added = true;
            }
            if (added) {
                scrollToBottom();
                // Widget sedang terbuka saat pesan baru tiba — langsung tandai terbaca
                if (_open) markConversationRead();
            }

            if (data.conversation_status === 'closed' || data.conversation_status === 'closed') {
                if (_conversation) _conversation.status = 'closed';
                showClosedState();
                stopPolling();
            } else if (data.conversation_status) {
                if (_conversation) _conversation.status = data.conversation_status;
            }
            if (data.operator && _conversation) {
                _conversation.operator = data.operator;
                setStatusLine(_conversation);
            }
        })
        .catch(function () { /* silent — poll error tidak perlu UI feedback */ });
    }

    // =========================================================================
    // RENDER MESSAGES
    // =========================================================================

    function addMessage(msg, scroll) {
        if (_seenIds.has(msg.id)) return false;
        _seenIds.add(msg.id);
        _messages.push(msg);

        if (msg.id && typeof msg.id === 'number' && msg.id > _lastMsgId) {
            _lastMsgId = msg.id;
        }

        renderMessage(msg, false);
        return true;
    }

    function clearMessageList() {
        while (msgList.firstChild) msgList.removeChild(msgList.firstChild);
    }

    function renderMessage(msg, isOptimistic) {
        var isUser   = msg.sender_type === 'user' || msg.sender_type === 'asn';
        var isSystem = msg.sender_type === 'system' || msg.type === 'system';

        var wrapper = document.createElement('div');
        wrapper.id  = 'msg-' + msg.id;

        if (isSystem) {
            // System message — centered, italic
            wrapper.style.cssText = 'text-align:center; padding:2px 0;';
            var sysSpan = document.createElement('span');
            sysSpan.style.cssText = 'font-size:0.7rem; color:#9ca3af; font-style:italic;';
            sysSpan.textContent = msg.body || '';
            wrapper.appendChild(sysSpan);

        } else if (isUser) {
            // Pesan dari ASN — kanan
            wrapper.style.cssText = 'display:flex; justify-content:flex-end;';
            var bubble = document.createElement('div');
            bubble.style.cssText = [
                'max-width:78%; background:linear-gradient(135deg,#2563eb,#1d4ed8);',
                'color:white; border-radius:16px 16px 4px 16px;',
                'padding:8px 12px; word-break:break-word;',
                isOptimistic ? 'opacity:0.7;' : '',
            ].join('');

            var bodyEl = document.createElement('p');
            bodyEl.style.cssText = 'font-size:0.8rem; line-height:1.5; margin:0;';
            bodyEl.textContent = msg.body || '';   // ← textContent, BUKAN innerHTML

            var timeEl = document.createElement('p');
            timeEl.style.cssText = 'font-size:0.62rem; color:rgba(255,255,255,0.65); margin:3px 0 0; text-align:right;';
            timeEl.textContent = formatTime(msg.created_at);

            bubble.appendChild(bodyEl);
            bubble.appendChild(timeEl);
            wrapper.appendChild(bubble);

        } else {
            // Pesan dari operator — kiri
            wrapper.style.cssText = 'display:flex; justify-content:flex-start; gap:8px; align-items:flex-end;';

            var avatar = document.createElement('div');
            avatar.style.cssText = [
                'width:26px; height:26px; border-radius:50%;',
                'background:#e0e7ff; display:flex; align-items:center;',
                'justify-content:center; flex-shrink:0;',
            ].join('');
            avatar.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#4f46e5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>';
            // avatar innerHTML hanya SVG statis — bukan user content

            var opBubble = document.createElement('div');
            opBubble.style.cssText = [
                'max-width:78%; background:#f3f4f6;',
                'color:#111827; border-radius:16px 16px 16px 4px;',
                'padding:8px 12px; word-break:break-word;',
            ].join('');

            var opBody = document.createElement('p');
            opBody.style.cssText = 'font-size:0.8rem; line-height:1.5; margin:0;';
            opBody.textContent = msg.body || '';   // ← textContent, BUKAN innerHTML

            var opTime = document.createElement('p');
            opTime.style.cssText = 'font-size:0.62rem; color:#9ca3af; margin:3px 0 0;';
            opTime.textContent = formatTime(msg.created_at);

            opBubble.appendChild(opBody);
            opBubble.appendChild(opTime);
            wrapper.appendChild(avatar);
            wrapper.appendChild(opBubble);
        }

        msgList.appendChild(wrapper);
    }

    // =========================================================================
    // UI STATE MANAGEMENT
    // =========================================================================

    function showState(state) {
        stLoading.style.display = 'none';
        stEmpty.style.display   = 'none';
        stActive.style.display  = 'none';
        stError.style.display   = 'none';

        if (state === 'loading') stLoading.style.display = 'flex';
        if (state === 'empty')   stEmpty.style.display   = 'flex';
        if (state === 'active')  stActive.style.display  = 'flex';
        if (state === 'error')   stError.style.display   = 'flex';
    }

    function showMainError(msg) {
        errorMainMsg.textContent = msg;
        showState('error');
    }

    function showBannerError(msg) {
        errorText.textContent        = msg;
        errorBanner.style.display    = 'block';
        setTimeout(function () {
            errorBanner.style.display = 'none';
        }, 5000);
    }

    function showClosedState() {
        inputArea.style.display    = 'none';
        closedNotice.style.display = 'block';
        setStatusLineText('Percakapan ditutup');
    }

    function setStatusLine(conv) {
        if (!conv) { setStatusLineText('Siap membantu Anda'); return; }
        if (conv.status === 'closed') { setStatusLineText('Percakapan ditutup'); return; }
        if (conv.operator) {
            setStatusLineText('Operator: ' + conv.operator.name);
        } else if (conv.status === 'waiting') {
            setStatusLineText('Menunggu operator...');
        } else {
            setStatusLineText('Aktif');
        }
    }

    function setStatusLineText(txt) {
        if (statusLine) statusLine.textContent = txt;
    }

    function scrollToBottom() {
        if (msgList) {
            msgList.scrollTop = msgList.scrollHeight;
        }
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    function readCookie(name) {
        var v = document.cookie.match('(^|;) ?' + name + '=([^;]*)(;|$)');
        return v ? decodeURIComponent(v[2]) : '';
    }

    function formatTime(iso) {
        if (!iso) return '';
        try {
            var d = new Date(iso);
            return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        } catch (e) { return ''; }
    }

    function mapError(httpStatus) {
        if (httpStatus === 401) return 'Silakan login ulang ke helpdesk.';
        if (httpStatus === 403) return 'Akses ditolak.';
        if (httpStatus === 419) return 'Sesi berakhir, memperbarui token...';
        if (httpStatus >= 500)  return 'Server helpdesk bermasalah. Coba beberapa saat lagi.';
        if (httpStatus === 0)   return 'Tidak dapat terhubung ke helpdesk.';
        return 'Terjadi kesalahan (' + httpStatus + ').';
    }

    // Jika ada 419 saat kirim pesan — refresh token lalu kirim ulang sekali
    function refreshCsrfAndRetry(callback) {
        fetch(EP.init, { credentials: 'include' })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                _csrfToken = data.csrf_token || '';
                callback();
            })
            .catch(function () {
                showBannerError('Sesi berakhir. Muat ulang halaman.');
            });
    }

    // ── Escape Esc key ────────────────────────────────────────────────────────
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && _open) closeWidget();
    });

})();
</script>

{{-- Spinner keyframe (hanya jika belum didefinisikan dari floating button) --}}
<style>
@keyframes widgetSpin {
    from { transform: rotate(0deg); }
    to   { transform: rotate(360deg); }
}
#helpdeskChatWidget {
    /* Pastikan widget tidak ter-clipped oleh parent overflow:hidden */
    contain: none;
}
/* Mobile: widget full-width di layar kecil */
@media (max-width: 480px) {
    #helpdeskChatWidget {
        right: 8px !important;
        left: 8px !important;
        width: auto !important;
        max-width: none !important;
        bottom: 100px !important;
    }
}

/* Pulse ringan untuk badge unread saat ada pesan baru & widget tertutup */
@keyframes helpdeskFabPulse {
    0%   { transform: scale(1);    box-shadow: 0 0 0 2px #fff, 0 0 0 0 rgba(220,38,38,0.55); }
    70%  { transform: scale(1.12); box-shadow: 0 0 0 2px #fff, 0 0 0 6px rgba(220,38,38,0); }
    100% { transform: scale(1);    box-shadow: 0 0 0 2px #fff, 0 0 0 0 rgba(220,38,38,0); }
}
#helpdeskUnreadBadge.helpdesk-badge-pulse {
    animation: helpdeskFabPulse 0.9s ease-out 2;
}
</style>

@endif
@endauth
