<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Dalam Pemeliharaan – eSARAKu</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #0f766e 0%, #134e4a 40%, #0c4a6e 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            position: relative;
            overflow-x: hidden;
        }

        /* Decorative circles background */
        body::before {
            content: '';
            position: fixed;
            top: -120px; left: -120px;
            width: 400px; height: 400px;
            border-radius: 50%;
            background: rgba(255,255,255,0.04);
            pointer-events: none;
        }
        body::after {
            content: '';
            position: fixed;
            bottom: -100px; right: -100px;
            width: 350px; height: 350px;
            border-radius: 50%;
            background: rgba(255,255,255,0.04);
            pointer-events: none;
        }

        /* Card */
        .card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.3);
            max-width: 680px;
            width: 100%;
            overflow: hidden;
            position: relative;
            z-index: 1;
        }

        /* Card header strip */
        .card-header {
            background: linear-gradient(90deg, #0f766e, #0d9488);
            padding: 8px 0;
            text-align: center;
        }
        .card-header span {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 2px;
            color: rgba(255,255,255,0.85);
            text-transform: uppercase;
        }

        .card-body {
            padding: 40px 48px 36px;
            text-align: center;
        }

        /* Logo area */
        .logo-wrap {
            margin-bottom: 24px;
        }
        .logo-icon {
            width: 72px; height: 72px;
            background: linear-gradient(135deg, #0f766e, #134e4a);
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
            box-shadow: 0 8px 24px rgba(15,118,110,0.35);
        }
        .logo-icon svg { width: 36px; height: 36px; color: white; }
        .logo-name {
            font-size: 22px;
            font-weight: 800;
            color: #0f766e;
            letter-spacing: -0.5px;
        }
        .logo-name span { color: #134e4a; }
        .logo-sub {
            font-size: 11px;
            color: #6b7280;
            font-weight: 400;
            margin-top: 2px;
            letter-spacing: 0.3px;
        }

        /* Gear animation */
        .gear-wrap {
            margin: 4px 0 20px;
            display: flex;
            justify-content: center;
            gap: 8px;
            align-items: center;
        }
        .gear {
            font-size: 28px;
            display: inline-block;
            animation: spin-cw 3s linear infinite;
        }
        .gear.ccw { animation: spin-ccw 2s linear infinite; font-size: 20px; }
        @keyframes spin-cw  { to { transform: rotate(360deg); } }
        @keyframes spin-ccw { to { transform: rotate(-360deg); } }

        /* Title */
        h1 {
            font-size: 18px;
            font-weight: 700;
            color: #111827;
            line-height: 1.4;
            margin-bottom: 12px;
            letter-spacing: -0.3px;
        }

        /* Description */
        .desc {
            font-size: 13.5px;
            color: #6b7280;
            line-height: 1.7;
            margin-bottom: 28px;
            font-weight: 400;
        }

        /* Time info box */
        .time-box {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-around;
            gap: 12px;
            flex-wrap: wrap;
        }
        .time-item { text-align: center; }
        .time-label {
            font-size: 10.5px;
            color: #6b7280;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .time-value {
            font-size: 15px;
            font-weight: 700;
            color: #0f766e;
        }
        .time-divider {
            width: 1px;
            background: #bbf7d0;
            align-self: stretch;
        }

        /* Countdown */
        .countdown-label {
            font-size: 12px;
            color: #9ca3af;
            margin-bottom: 14px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        .countdown {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-bottom: 28px;
            flex-wrap: wrap;
        }
        .countdown-item {
            background: linear-gradient(135deg, #0f766e, #0d9488);
            border-radius: 12px;
            padding: 14px 18px;
            min-width: 76px;
            text-align: center;
            box-shadow: 0 4px 14px rgba(15,118,110,0.25);
        }
        .countdown-num {
            font-size: 28px;
            font-weight: 800;
            color: #ffffff;
            line-height: 1;
            display: block;
            font-variant-numeric: tabular-nums;
        }
        .countdown-unit {
            font-size: 10px;
            color: rgba(255,255,255,0.75);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-top: 4px;
            display: block;
        }
        .countdown-sep {
            font-size: 24px;
            font-weight: 700;
            color: #0f766e;
            align-self: center;
            margin-top: -10px;
        }
        #countdown-done {
            display: none;
            background: #dcfce7;
            border: 1px solid #86efac;
            border-radius: 10px;
            padding: 12px 20px;
            color: #166534;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 28px;
        }

        /* Progress bar */
        .progress-label {
            display: flex;
            justify-content: space-between;
            font-size: 11.5px;
            color: #9ca3af;
            font-weight: 500;
            margin-bottom: 8px;
        }
        .progress-wrap {
            background: #e5e7eb;
            border-radius: 999px;
            height: 8px;
            overflow: hidden;
            margin-bottom: 28px;
        }
        .progress-bar {
            height: 100%;
            border-radius: 999px;
            background: linear-gradient(90deg, #0f766e, #34d399, #0f766e);
            background-size: 200% 100%;
            animation: progress-move 2.5s ease-in-out infinite;
            width: 65%;
        }
        @keyframes progress-move {
            0%   { background-position: 0% 0%; }
            50%  { background-position: 100% 0%; }
            100% { background-position: 0% 0%; }
        }

        /* Loading dots */
        .loading-wrap {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
        }
        .dots {
            display: flex;
            gap: 6px;
        }
        .dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: #0f766e;
            animation: dot-bounce 1.2s infinite ease-in-out;
        }
        .dot:nth-child(1) { animation-delay: 0s; }
        .dot:nth-child(2) { animation-delay: 0.2s; }
        .dot:nth-child(3) { animation-delay: 0.4s; }
        .dot:nth-child(4) { animation-delay: 0.6s; }
        @keyframes dot-bounce {
            0%, 80%, 100% { transform: scale(0.6); opacity: 0.4; }
            40%           { transform: scale(1.2); opacity: 1; }
        }
        .loading-text {
            font-size: 12px;
            color: #9ca3af;
            font-weight: 400;
            font-style: italic;
        }

        /* Footer */
        footer {
            margin-top: 24px;
            text-align: center;
            color: rgba(255,255,255,0.55);
            font-size: 11.5px;
            line-height: 1.7;
            z-index: 1;
        }
        footer strong { color: rgba(255,255,255,0.8); font-weight: 600; }

        /* Responsive */
        @media (max-width: 480px) {
            .card-body { padding: 32px 24px 28px; }
            h1 { font-size: 15px; }
            .countdown-item { min-width: 62px; padding: 10px 12px; }
            .countdown-num { font-size: 22px; }
            .time-box { flex-direction: column; align-items: center; }
            .time-divider { display: none; }
        }
    </style>
</head>
<body>

    <div class="card">
        <!-- Header strip -->
        <div class="card-header">
            <span>🔧 &nbsp; Maintenance Mode &nbsp; 🔧</span>
        </div>

        <div class="card-body">

            <!-- Logo -->
            <div class="logo-wrap">
                <div class="logo-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                    </svg>
                </div>
                <div class="logo-name">e<span>SARA</span>Ku</div>
                <div class="logo-sub">Elektronik Sistem Akuntabilitas KinerjaKu</div>
            </div>

            <!-- Gear animation -->
            <div class="gear-wrap">
                <span class="gear">⚙️</span>
                <span class="gear ccw">⚙️</span>
                <span class="gear">⚙️</span>
            </div>

            <!-- Title -->
            <h1>SISTEM eSARAKu SEDANG DALAM PEMELIHARAAN</h1>

            <!-- Description -->
            <p class="desc">
                Saat ini sistem eSARAKu sedang dalam proses pemeliharaan dan peningkatan layanan
                untuk memberikan pengalaman yang lebih baik bagi seluruh ASN pengguna.
            </p>

            <!-- Time info -->
            <div class="time-box">
                <div class="time-item">
                    <div class="time-label">Maintenance Dimulai</div>
                    <div class="time-value">13.30 WITA</div>
                </div>
                <div class="time-divider"></div>
                <div class="time-item">
                    <div class="time-label">Estimasi Selesai</div>
                    <div class="time-value">14.00 WITA</div>
                </div>
                <div class="time-divider"></div>
                <div class="time-item">
                    <div class="time-label">Status</div>
                    <div class="time-value">🔄 On Progress</div>
                </div>
            </div>

            <!-- Countdown -->
            <div class="countdown-label">Sistem akan kembali aktif dalam</div>
            <div class="countdown" id="countdown-wrap">
                <div class="countdown-item">
                    <span class="countdown-num" id="cd-hours">--</span>
                    <span class="countdown-unit">Jam</span>
                </div>
                <div class="countdown-sep">:</div>
                <div class="countdown-item">
                    <span class="countdown-num" id="cd-minutes">--</span>
                    <span class="countdown-unit">Menit</span>
                </div>
                <div class="countdown-sep">:</div>
                <div class="countdown-item">
                    <span class="countdown-num" id="cd-seconds">--</span>
                    <span class="countdown-unit">Detik</span>
                </div>
            </div>
            <div id="countdown-done">✅ Sistem seharusnya sudah kembali aktif. Silakan muat ulang halaman.</div>

            <!-- Progress bar -->
            <div class="progress-label">
                <span>Proses Pemeliharaan</span>
                <span>Sedang Berjalan...</span>
            </div>
            <div class="progress-wrap">
                <div class="progress-bar"></div>
            </div>

            <!-- Loading dots -->
            <div class="loading-wrap">
                <div class="dots">
                    <div class="dot"></div>
                    <div class="dot"></div>
                    <div class="dot"></div>
                    <div class="dot"></div>
                </div>
                <div class="loading-text">Mohon menunggu, sistem sedang ditingkatkan...</div>
            </div>

        </div>
    </div>

    <!-- Footer -->
    <footer>
        <strong>© eSARAKu – Elektronik Sistem Akuntabilitas KinerjaKu</strong><br>
        Kantor Wilayah Kementerian Agama Provinsi Sulawesi Barat
    </footer>

    <script>
        // Target: 13:00 WITA hari ini (UTC+8)
        function getTargetTime() {
            const now = new Date();
            // WITA = UTC+8
            const witaOffset = 8 * 60; // menit
            const localOffset = now.getTimezoneOffset(); // menit (negatif utk timur)
            const witaNow = new Date(now.getTime() + (witaOffset + localOffset) * 60000);

            const target = new Date(witaNow);
            target.setHours(14, 0, 0, 0);

            // Jika sudah lewat 14:00 WITA hari ini, target besok
            if (witaNow >= target) {
                target.setDate(target.getDate() + 1);
            }

            return target;
        }

        function pad(n) { return String(n).padStart(2, '0'); }

        function updateCountdown() {
            const now = new Date();
            const witaOffset = 8 * 60;
            const localOffset = now.getTimezoneOffset();
            const witaNow = new Date(now.getTime() + (witaOffset + localOffset) * 60000);

            const target = getTargetTime();
            const diff = target - witaNow;

            if (diff <= 0) {
                document.getElementById('countdown-wrap').style.display = 'none';
                document.getElementById('countdown-done').style.display = 'block';
                return;
            }

            const hours   = Math.floor(diff / 3600000);
            const minutes = Math.floor((diff % 3600000) / 60000);
            const seconds = Math.floor((diff % 60000) / 1000);

            document.getElementById('cd-hours').textContent   = pad(hours);
            document.getElementById('cd-minutes').textContent = pad(minutes);
            document.getElementById('cd-seconds').textContent = pad(seconds);
        }

        updateCountdown();
        setInterval(updateCountdown, 1000);
    </script>

</body>
</html>
