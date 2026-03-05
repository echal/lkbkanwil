<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Masuk — esaraku</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --green-600: #1a7a3c;
            --green-700: #156030;
            --green-800: #0f4823;
            --gray-300:  #ced4da;
            --gray-400:  #adb5bd;
            --gray-500:  #6c757d;
            --gray-700:  #343a40;
            --gray-900:  #212529;
            --red-50:    #fff5f5;
            --red-200:   #fca5a5;
            --red-600:   #dc2626;
        }

        html, body {
            height: 100%;
            font-family: 'Inter', sans-serif;
            color: var(--gray-900);
        }

        body {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 24px 16px;
            background:
                radial-gradient(ellipse at 15% 60%, rgba(26,122,60,.08) 0%, transparent 55%),
                radial-gradient(ellipse at 85% 25%, rgba(26,122,60,.06) 0%, transparent 50%),
                #f2f5f3;
        }

        .login-wrapper {
            width: 100%;
            max-width: 420px;
            animation: fadeInUp .4s ease both;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 8px 40px rgba(0,0,0,.09), 0 2px 10px rgba(0,0,0,.05);
            overflow: hidden;
        }

        /* ── Header ───────────────────────────────────── */
        .card-header {
            padding: 40px 40px 28px;
            text-align: center;
        }

        .app-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--green-700);
            letter-spacing: -.2px;
            margin-bottom: 18px;
        }

        .logo-wrap {
            display: flex;
            justify-content: center;
        }

        .logo-wrap img {
            max-height: 120px;
            width: auto;
            object-fit: contain;
        }

        .logo-fallback {
            display: none;
            width: 78px;
            height: 78px;
            background: var(--green-600);
            border-radius: 18px;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            font-weight: 700;
            color: #fff;
            letter-spacing: -1px;
        }

        /* ── Divider ──────────────────────────────────── */
        .divider {
            height: 1px;
            background: #edf0ed;
            margin: 0 40px;
        }

        /* ── Body ─────────────────────────────────────── */
        .card-body {
            padding: 30px 40px 38px;
        }

        /* ── Alert ────────────────────────────────────── */
        .alert-error {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: var(--red-50);
            border: 1px solid var(--red-200);
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 22px;
            font-size: .83rem;
            color: var(--red-600);
        }

        .alert-error svg { flex-shrink: 0; margin-top: 1px; }

        /* ── Form ─────────────────────────────────────── */
        .form-group { margin-bottom: 18px; }

        .form-label {
            display: block;
            font-size: .81rem;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 7px;
            letter-spacing: .1px;
        }

        .input-wrap { position: relative; }

        .input-icon {
            position: absolute;
            top: 50%;
            left: 13px;
            transform: translateY(-50%);
            color: var(--gray-400);
            pointer-events: none;
            display: flex;
        }

        .form-control {
            width: 100%;
            padding: 11px 14px 11px 40px;
            border: 1.5px solid var(--gray-300);
            border-radius: 10px;
            font-size: .89rem;
            font-family: 'Inter', sans-serif;
            color: var(--gray-900);
            background: #fff;
            outline: none;
            transition: border-color .16s, box-shadow .16s;
            appearance: none;
        }

        .form-control::placeholder { color: transparent; }

        .form-control:focus {
            border-color: var(--green-600);
            box-shadow: 0 0 0 3.5px rgba(26,122,60,.12);
        }

        .form-control.is-error { border-color: var(--red-600); }
        .form-control.is-error:focus { box-shadow: 0 0 0 3.5px rgba(220,38,38,.11); }

        .field-error {
            margin-top: 5px;
            font-size: .77rem;
            color: var(--red-600);
        }

        /* password toggle */
        .pwd-toggle {
            position: absolute;
            top: 50%;
            right: 12px;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--gray-400);
            display: flex;
            padding: 3px;
            transition: color .15s;
        }

        .pwd-toggle:hover { color: var(--gray-700); }

        /* ── Submit ───────────────────────────────────── */
        .btn-submit {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 13px 24px;
            margin-top: 26px;
            background: var(--green-600);
            color: #fff;
            font-size: .9rem;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            letter-spacing: .3px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: background .16s, box-shadow .16s, transform .08s;
            box-shadow: 0 2px 10px rgba(26,122,60,.28);
        }

        .btn-submit:hover {
            background: var(--green-700);
            box-shadow: 0 4px 16px rgba(26,122,60,.36);
        }

        .btn-submit:active {
            background: var(--green-800);
            transform: translateY(1px);
            box-shadow: 0 1px 5px rgba(26,122,60,.2);
        }

        /* ── Footer ───────────────────────────────────── */
        .page-footer {
            margin-top: 20px;
            text-align: center;
            font-size: .74rem;
            color: var(--gray-400);
        }

        /* ── Responsive ───────────────────────────────── */
        @media (max-width: 480px) {
            .card-header { padding: 32px 28px 24px; }
            .card-body   { padding: 24px 28px 30px; }
            .divider     { margin: 0 28px; }
            .app-title   { font-size: 1.18rem; }
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="card">

        {{-- Header: Judul dulu, logo di bawah --}}
        <div class="card-header">
            <h1 class="app-title">Selamat Datang</h1>
            <div class="logo-wrap">
                <img src="/images/logo/esaraku-logo.png"
                     alt="Logo esaraku"
                     onerror="this.style.display='none'; document.querySelector('.logo-fallback').style.display='flex';">
                <div class="logo-fallback">es</div>
            </div>
        </div>

        <div class="divider"></div>

        {{-- Body --}}
        <div class="card-body">

            @if(session('error'))
            <div class="alert-error">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <circle cx="12" cy="16" r=".5" fill="currentColor"/>
                </svg>
                <span>{{ session('error') }}</span>
            </div>
            @endif

            <form method="POST" action="{{ route('login.submit') }}">
                @csrf

                {{-- Email --}}
                <div class="form-group">
                    <label class="form-label" for="email">Alamat Email</label>
                    <div class="input-wrap">
                        <span class="input-icon">
                            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </span>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            autocomplete="email"
                            class="form-control @error('email') is-error @enderror"
                        >
                    </div>
                    @error('email')
                    <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password --}}
                <div class="form-group">
                    <label class="form-label" for="password">Kata Sandi</label>
                    <div class="input-wrap">
                        <span class="input-icon">
                            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </span>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            class="form-control @error('password') is-error @enderror"
                            style="padding-right: 40px;"
                        >
                        <button type="button" class="pwd-toggle" id="togglePwd" aria-label="Tampilkan kata sandi">
                            <svg id="eye-on" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg id="eye-off" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" style="display:none">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                    @error('password')
                    <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="btn-submit">
                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                    </svg>
                    Masuk
                </button>
            </form>

        </div>
    </div>

    <p class="page-footer">© 2026 Data & Sistem Informasi</p>
</div>

<script>
    const toggleBtn = document.getElementById('togglePwd');
    const pwdInput  = document.getElementById('password');
    const eyeOn     = document.getElementById('eye-on');
    const eyeOff    = document.getElementById('eye-off');

    toggleBtn.addEventListener('click', function () {
        const show = pwdInput.type === 'password';
        pwdInput.type        = show ? 'text' : 'password';
        eyeOn.style.display  = show ? 'none' : '';
        eyeOff.style.display = show ? ''     : 'none';
    });
</script>

</body>
</html>
