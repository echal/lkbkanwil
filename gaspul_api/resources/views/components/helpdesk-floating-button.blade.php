{{--
    Helpdesk Floating Button — Hanya tampil untuk role ASN yang sudah login.
    Klik tombol membuka modal Pusat Bantuan e_SARAku dengan 3 opsi:
    FAQ, Tiket Saya, Mulai Live Chat.

    Phase I.2 COMPLETE:
    - Setiap tombol memanggil POST /api/helpdesk-token untuk mendapatkan token SSO
    - Token digunakan untuk redirect ke helpdesk via /sso/login?token=xxx&return_to=xxx
    - Token lama dihapus sebelum token baru dibuat (anti-accumulation)
    - Semua navigasi ke helpdesk melewati SSO — tidak ada login kedua
--}}

@auth
@if(auth()->user()->role === 'ASN')

{{-- =====================================================================
     FLOATING BUTTON
     ===================================================================== --}}
<button
    id="helpdeskFabBtn"
    onclick="bukaModalBantuan()"
    title="Pusat Bantuan e_SARAku"
    aria-label="Buka Pusat Bantuan"
    style="
        position: fixed;
        bottom: 24px;
        right: 24px;
        z-index: 9999;
        width: 72px;
        height: 72px;
        border-radius: 50%;
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 8px 24px rgba(37, 99, 235, 0.45), 0 2px 8px rgba(0,0,0,0.18);
        transition: transform 0.18s ease, box-shadow 0.18s ease;
    "
    onmouseenter="this.style.transform='scale(1.10)'; this.style.boxShadow='0 12px 32px rgba(37,99,235,0.55), 0 4px 12px rgba(0,0,0,0.22)';"
    onmouseleave="this.style.transform='scale(1)';   this.style.boxShadow='0 8px 24px rgba(37,99,235,0.45), 0 2px 8px rgba(0,0,0,0.18)';"
>
    {{-- Chat bubble icon --}}
    <svg width="34" height="34" viewBox="0 0 24 24" fill="none"
         xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"
              fill="white" opacity="0.95"/>
        <circle cx="8"  cy="11" r="1.1" fill="#2563eb"/>
        <circle cx="12" cy="11" r="1.1" fill="#2563eb"/>
        <circle cx="16" cy="11" r="1.1" fill="#2563eb"/>
    </svg>

    {{-- Unread badge — disembunyikan secara default, diisi oleh JS --}}
    <span
        id="helpdeskUnreadBadge"
        aria-hidden="true"
        style="
            display: none;
            position: absolute;
            top: -4px;
            right: -4px;
            min-width: 22px;
            height: 22px;
            padding: 0 5px;
            border-radius: 11px;
            background: #dc2626;
            color: #fff;
            font-size: 0.7rem;
            font-weight: 700;
            line-height: 22px;
            text-align: center;
            box-shadow: 0 0 0 2px #fff;
        "
    >0</span>
</button>


{{-- =====================================================================
     MODAL PUSAT BANTUAN
     ===================================================================== --}}
<div
    id="helpdeskModal"
    role="dialog"
    aria-modal="true"
    aria-labelledby="helpdeskModalTitle"
    style="
        display: none;
        position: fixed;
        inset: 0;
        z-index: 10000;
        align-items: flex-end;
        justify-content: flex-end;
        padding: 0 24px 108px 24px;
        pointer-events: none;
    "
>
    {{-- Backdrop --}}
    <div
        id="helpdeskModalBackdrop"
        onclick="tutupModalBantuan()"
        style="
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.25);
            pointer-events: all;
        "
    ></div>

    {{-- Panel --}}
    <div
        id="helpdeskModalPanel"
        style="
            position: relative;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.18), 0 4px 16px rgba(37,99,235,0.12);
            width: 100%;
            max-width: 320px;
            padding: 28px 24px 24px;
            pointer-events: all;
            transform: translateY(16px) scale(0.97);
            opacity: 0;
            transition: transform 0.22s cubic-bezier(.34,1.56,.64,1), opacity 0.18s ease;
        "
    >
        {{-- Header --}}
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div style="
                    width:42px; height:42px; border-radius:50%;
                    background:linear-gradient(135deg,#2563eb,#1d4ed8);
                    display:flex; align-items:center; justify-content:center;
                    flex-shrink:0;
                ">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"
                              fill="white"/>
                    </svg>
                </div>
                <div>
                    <div id="helpdeskModalTitle"
                         style="font-size:0.95rem; font-weight:700; color:#111827; line-height:1.3;">
                        Pusat Bantuan e_SARAku
                    </div>
                    <div style="font-size:0.72rem; color:#6b7280; margin-top:1px;">
                        Kami siap membantu Anda
                    </div>
                </div>
            </div>
            <button
                onclick="tutupModalBantuan()"
                aria-label="Tutup"
                style="
                    background:none; border:none; cursor:pointer;
                    color:#9ca3af; padding:4px; border-radius:6px;
                    display:flex; align-items:center; justify-content:center;
                    transition: color 0.15s;
                "
                onmouseenter="this.style.color='#374151';"
                onmouseleave="this.style.color='#9ca3af';"
            >
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        {{-- Divider --}}
        <div style="height:1px; background:#f3f4f6; margin-bottom:18px;"></div>

        {{-- Loading state (shown during token fetch) --}}
        <div id="helpdeskLoadingState" style="display:none; text-align:center; padding:16px 0;">
            <svg style="display:inline; animation:helpdeskSpin 0.8s linear infinite;"
                 width="24" height="24" viewBox="0 0 24 24" fill="none"
                 stroke="#2563eb" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
                <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
            </svg>
            <p style="font-size:0.8rem; color:#6b7280; margin-top:8px;">Memuat...</p>
        </div>

        {{-- Menu Items --}}
        <div id="helpdeskMenuItems" style="display:flex; flex-direction:column; gap:10px;">

            {{-- FAQ --}}
            <button
                onclick="bukaHelpdesk('/faq')"
                style="
                    display:flex; align-items:center; gap:14px;
                    padding:14px 16px; border-radius:14px;
                    background:#f8fafc; border:1px solid #e5e7eb;
                    text-decoration:none; color:inherit; cursor:pointer;
                    transition: background 0.15s, border-color 0.15s, box-shadow 0.15s;
                    width:100%; text-align:left;
                "
                onmouseenter="this.style.background='#eff6ff'; this.style.borderColor='#bfdbfe'; this.style.boxShadow='0 2px 8px rgba(37,99,235,0.10)';"
                onmouseleave="this.style.background='#f8fafc'; this.style.borderColor='#e5e7eb'; this.style.boxShadow='none';"
            >
                <div style="
                    width:40px; height:40px; border-radius:12px;
                    background:#dbeafe; display:flex; align-items:center;
                    justify-content:center; flex-shrink:0;
                ">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                         stroke="#2563eb" stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                        <line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                </div>
                <div>
                    <div style="font-size:0.875rem; font-weight:600; color:#111827;">FAQ</div>
                    <div style="font-size:0.72rem; color:#6b7280; margin-top:1px;">
                        Pertanyaan yang sering ditanyakan
                    </div>
                </div>
                <svg style="margin-left:auto; flex-shrink:0;" width="16" height="16"
                     viewBox="0 0 24 24" fill="none" stroke="#d1d5db"
                     stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polyline points="9 18 15 12 9 6"/>
                </svg>
            </button>

            {{-- Tiket Saya --}}
            <button
                onclick="bukaHelpdesk('/asn/tickets')"
                style="
                    display:flex; align-items:center; gap:14px;
                    padding:14px 16px; border-radius:14px;
                    background:#f8fafc; border:1px solid #e5e7eb;
                    text-decoration:none; color:inherit; cursor:pointer;
                    transition: background 0.15s, border-color 0.15s, box-shadow 0.15s;
                    width:100%; text-align:left;
                "
                onmouseenter="this.style.background='#eff6ff'; this.style.borderColor='#bfdbfe'; this.style.boxShadow='0 2px 8px rgba(37,99,235,0.10)';"
                onmouseleave="this.style.background='#f8fafc'; this.style.borderColor='#e5e7eb'; this.style.boxShadow='none';"
            >
                <div style="
                    width:40px; height:40px; border-radius:12px;
                    background:#dbeafe; display:flex; align-items:center;
                    justify-content:center; flex-shrink:0;
                ">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                         stroke="#2563eb" stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round" aria-hidden="true">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="9" y1="13" x2="15" y2="13"/>
                        <line x1="9" y1="17" x2="11" y2="17"/>
                    </svg>
                </div>
                <div>
                    <div style="font-size:0.875rem; font-weight:600; color:#111827;">Tiket Saya</div>
                    <div style="font-size:0.72rem; color:#6b7280; margin-top:1px;">
                        Lihat dan pantau status tiket
                    </div>
                </div>
                <svg style="margin-left:auto; flex-shrink:0;" width="16" height="16"
                     viewBox="0 0 24 24" fill="none" stroke="#d1d5db"
                     stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polyline points="9 18 15 12 9 6"/>
                </svg>
            </button>

            {{-- Mulai Live Chat — membuka embedded widget, bukan redirect ke helpdesk --}}
            <button
                onclick="tutupModalBantuan(); helpdeskChatOpen();"
                style="
                    display:flex; align-items:center; gap:14px;
                    padding:14px 16px; border-radius:14px;
                    background:linear-gradient(135deg,#2563eb 0%,#1d4ed8 100%);
                    border:1px solid transparent;
                    text-decoration:none; color:inherit; cursor:pointer;
                    transition: opacity 0.15s, box-shadow 0.15s;
                    width:100%; text-align:left;
                "
                onmouseenter="this.style.opacity='0.9'; this.style.boxShadow='0 4px 16px rgba(37,99,235,0.40)';"
                onmouseleave="this.style.opacity='1';   this.style.boxShadow='none';"
            >
                <div style="
                    width:40px; height:40px; border-radius:12px;
                    background:rgba(255,255,255,0.18); display:flex;
                    align-items:center; justify-content:center; flex-shrink:0;
                ">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                         stroke="white" stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round" aria-hidden="true">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                </div>
                <div>
                    <div style="font-size:0.875rem; font-weight:600; color:white;">Mulai Live Chat</div>
                    <div style="font-size:0.72rem; color:rgba(255,255,255,0.75); margin-top:1px;">
                        Hubungi operator secara langsung
                    </div>
                </div>
                <svg style="margin-left:auto; flex-shrink:0;" width="16" height="16"
                     viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.6)"
                     stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polyline points="9 18 15 12 9 6"/>
                </svg>
            </button>

        </div>

        {{-- Error state --}}
        <div id="helpdeskErrorState" style="display:none; margin-top:12px; padding:10px 14px; background:#fef2f2; border:1px solid #fecaca; border-radius:10px;">
            <p style="font-size:0.8rem; color:#dc2626; margin:0;" id="helpdeskErrorMsg">
                Gagal terhubung ke Pusat Bantuan. Coba lagi.
            </p>
        </div>

        {{-- Footer --}}
        <div style="margin-top:16px; text-align:center;">
            <span style="font-size:0.68rem; color:#d1d5db;">
                e-SARAku &bull; Kemenag Sulawesi Barat
            </span>
        </div>
    </div>
</div>


{{-- =====================================================================
     JAVASCRIPT
     ===================================================================== --}}
<script>
(function () {
    'use strict';

    var _isOpen    = false;
    var _isLoading = false;
    var modal      = document.getElementById('helpdeskModal');
    var panel      = document.getElementById('helpdeskModalPanel');
    var menuItems  = document.getElementById('helpdeskMenuItems');
    var loadingEl  = document.getElementById('helpdeskLoadingState');
    var errorEl    = document.getElementById('helpdeskErrorState');
    var errorMsg   = document.getElementById('helpdeskErrorMsg');
    var badgeEl    = document.getElementById('helpdeskUnreadBadge');

    // ── Config — injected from Laravel (never expose token here) ─────────────
    var CSRF_TOKEN   = document.querySelector('meta[name="csrf-token"]').content;
    var TOKEN_ENDPOINT = '{{ url("/api/helpdesk-token") }}';
    var HD_BASE_FAB     = '{{ rtrim(config("services.helpdesk.url"), "/") }}';
    var UNREAD_ENDPOINT = HD_BASE_FAB + '/api/chat/unread-count';
    var UNREAD_POLL_MS  = 30000;

    // ── Unread badge polling — hanya saat ASN login, 1 request / 30s ─────────
    var _unreadPollTimer = null;
    var _lastUnreadCount = 0;

    function setBadge(count) {
        if (!badgeEl) return;
        if (count > 0) {
            badgeEl.textContent = count > 99 ? '99+' : String(count);
            badgeEl.style.display = 'block';
        } else {
            badgeEl.style.display = 'none';
        }
        _lastUnreadCount = count;
    }

    window.helpdeskGetUnreadCount = function () {
        return _lastUnreadCount;
    };

    // Dipanggil oleh widget saat conversation langsung ditandai terbaca —
    // reset badge seketika tanpa menunggu polling 30 detik berikutnya.
    window.helpdeskSetUnreadBadge = function (count) {
        setBadge(count);
    };

    function pollUnreadCount() {
        fetch(UNREAD_ENDPOINT, {
            credentials: 'include',
            headers: { 'Accept': 'application/json' },
        })
        .then(function (res) {
            if (!res.ok) throw new Error('unread fetch failed');
            return res.json();
        })
        .then(function (data) {
            var count = data.unread_messages || 0;
            var increased = count > _lastUnreadCount;
            setBadge(count);

            // Beritahu widget (jika sudah dimuat) agar bisa update header + pulse
            if (typeof window.helpdeskChatOnUnreadUpdate === 'function') {
                window.helpdeskChatOnUnreadUpdate(count, increased);
            }
        })
        .catch(function () { /* silent — badge tidak kritikal */ });
    }

    function startUnreadPolling() {
        if (_unreadPollTimer) return;
        pollUnreadCount(); // langsung cek sekali saat load
        _unreadPollTimer = setInterval(pollUnreadCount, UNREAD_POLL_MS);
    }

    startUnreadPolling();

    window.bukaModalBantuan = function () {
        if (_isOpen) return;
        _isOpen = true;

        // Reset state
        showMenu();

        modal.style.display       = 'flex';
        modal.style.pointerEvents = 'all';

        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                panel.style.transform = 'translateY(0) scale(1)';
                panel.style.opacity   = '1';
            });
        });
    };

    window.tutupModalBantuan = function () {
        if (!_isOpen) return;

        panel.style.transform = 'translateY(16px) scale(0.97)';
        panel.style.opacity   = '0';

        setTimeout(function () {
            modal.style.display       = 'none';
            modal.style.pointerEvents = 'none';
            _isOpen    = false;
            _isLoading = false;
            showMenu();
        }, 200);
    };

    /**
     * bukaHelpdesk(returnTo)
     *
     * 1. POST /api/helpdesk-token (with CSRF + Bearer session cookie)
     * 2. Receive { token, helpdesk_url }
     * 3. Redirect to helpdesk_url/sso/login?token=xxx&return_to=returnTo
     *
     * @param {string} returnTo  Relative helpdesk path (/faq, /asn/tickets, /asn/chat)
     */
    window.bukaHelpdesk = function (returnTo) {
        if (_isLoading) return;
        _isLoading = true;

        showLoading();

        fetch(TOKEN_ENDPOINT, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN':  CSRF_TOKEN,
                'Accept':        'application/json',
                'Content-Type':  'application/json',
            },
            credentials: 'same-origin',
        })
        .then(function (res) {
            if (!res.ok) {
                return res.json().then(function (body) {
                    throw new Error(body.message || 'Gagal mendapatkan token SSO.');
                });
            }
            return res.json();
        })
        .then(function (data) {
            if (!data.token || !data.helpdesk_url) {
                throw new Error('Respons token tidak valid.');
            }

            // Build SSO URL — return_to is a relative path, safe to append
            var ssoUrl = data.helpdesk_url.replace(/\/$/, '')
                + '/sso/login'
                + '?token=' + encodeURIComponent(data.token)
                + '&return_to=' + encodeURIComponent(returnTo);

            tutupModalBantuan();
            window.location.href = ssoUrl;
        })
        .catch(function (err) {
            _isLoading = false;
            showError(err.message || 'Gagal terhubung ke Pusat Bantuan. Coba lagi.');
        });
    };

    // ── UI helpers ────────────────────────────────────────────────────────────

    function showMenu() {
        menuItems.style.display  = 'flex';
        loadingEl.style.display  = 'none';
        errorEl.style.display    = 'none';
    }

    function showLoading() {
        menuItems.style.display  = 'none';
        loadingEl.style.display  = 'block';
        errorEl.style.display    = 'none';
    }

    function showError(msg) {
        menuItems.style.display  = 'flex';
        loadingEl.style.display  = 'none';
        errorEl.style.display    = 'block';
        errorMsg.textContent     = msg;
    }

    // Tutup dengan Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') window.tutupModalBantuan();
    });
})();
</script>

{{-- Spinner keyframe --}}
<style>
@keyframes helpdeskSpin {
    from { transform: rotate(0deg); }
    to   { transform: rotate(360deg); }
}
</style>

@endif
@endauth
