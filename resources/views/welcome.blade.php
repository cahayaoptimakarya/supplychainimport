<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Aplikasi') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            :root {
                color-scheme: dark;
            }
            body {
                margin: 0;
                font-family: 'Figtree', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                background: #010409;
                color: #f8fafc;
                min-height: 100vh;
            }
            .hero-shell {
                position: relative;
                min-height: 100vh;
                overflow: hidden;
                padding: 48px 20px 64px;
                display: flex;
                justify-content: center;
            }
            .hero-bg {
                position: absolute;
                inset: 0;
                background: radial-gradient(circle at 20% 20%, rgba(59,130,246,.25), transparent 60%),
                            radial-gradient(circle at 80% 0%, rgba(14,165,233,.3), transparent 50%),
                            radial-gradient(circle at 50% 120%, rgba(250,204,21,.2), transparent 55%),
                            #020617;
                filter: saturate(120%);
                z-index: 0;
            }
            .hero-bg::after {
                content: '';
                position: absolute;
                inset: 0;
                background: linear-gradient(180deg, rgba(2,6,23,0) 0%, rgba(2,6,23,.9) 70%);
            }
            .hero-container {
                position: relative;
                z-index: 1;
                width: min(1180px, 100%);
                display: flex;
                flex-direction: column;
                gap: 32px;
            }
            .hero-card {
                background: rgba(15,23,42,.78);
                border: 1px solid rgba(148,163,184,.25);
                border-radius: 28px;
                box-shadow: 0 30px 80px rgba(15,23,42,.6);
                padding: clamp(24px, 6vw, 48px);
                backdrop-filter: blur(15px);
            }
            .hero-header {
                display: flex;
                flex-wrap: wrap;
                justify-content: space-between;
                align-items: center;
                gap: 16px;
                border-bottom: 1px solid rgba(148,163,184,.2);
                padding-bottom: 16px;
                margin-bottom: 16px;
            }
            .hero-brand {
                display: flex;
                align-items: center;
                gap: 12px;
            }
            .hero-brand .logo {
                width: 44px;
                height: 44px;
                border-radius: 14px;
                background: linear-gradient(135deg, #38bdf8, #6366f1);
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 24px;
            }
            .hero-buttons {
                display: flex;
                flex-wrap: wrap;
                gap: 12px;
            }
            .hero-buttons a {
                border-radius: 14px;
                padding: 12px 20px;
                font-weight: 600;
                text-decoration: none;
                transition: transform .2s, box-shadow .2s, background .2s;
            }
            .btn-primary {
                background: #f8fafc;
                color: #020617;
                box-shadow: 0 12px 30px rgba(148,163,184,.35);
            }
            .btn-primary:hover {
                transform: translateY(-2px);
            }
            .btn-secondary {
                border: 1px solid rgba(148,163,184,.4);
                color: #e2e8f0;
            }
            .btn-secondary:hover {
                background: rgba(148,163,184,.1);
            }
            .hero-body {
                display: grid;
                gap: 32px;
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                align-items: stretch;
            }
            .hero-title {
                font-size: clamp(36px, 4vw, 54px);
                font-weight: 700;
                line-height: 1.2;
                margin-bottom: 16px;
            }
            .hero-subtext {
                color: #cbd5f5;
                line-height: 1.6;
                max-width: 540px;
            }
            .stats-grid {
                display: grid;
                gap: 16px;
                grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
                margin-top: 24px;
            }
            .stat-card {
                border-radius: 20px;
                padding: 16px 18px;
                background: rgba(15,23,42,.7);
                border: 1px solid rgba(148,163,184,.2);
            }
            .stat-label {
                font-size: 11px;
                letter-spacing: .3em;
                text-transform: uppercase;
                color: #94a3b8;
            }
            .stat-value {
                margin-top: 8px;
                font-size: 26px;
                font-weight: 600;
            }
            .panel {
                border-radius: 30px;
                border: 1px solid rgba(148,163,184,.2);
                background: rgba(2,6,23,.65);
                padding: 28px;
                box-shadow: inset 0 1px 0 rgba(255,255,255,.04);
            }
            .panel h3 {
                margin-top: 0;
                margin-bottom: 10px;
                font-size: 18px;
                color: #f8fafc;
            }
            .panel p {
                margin: 0;
                color: #cbd5f5;
                line-height: 1.6;
            }
            .feature-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                gap: 18px;
            }
            .feature-card {
                padding: 18px;
                border-radius: 22px;
                border: 1px solid rgba(148,163,184,.18);
                background: rgba(15,23,42,.8);
            }
            .feature-card h4 {
                margin: 0 0 8px;
                font-size: 15px;
                color: #e2e8f0;
            }
            .feature-card p {
                margin: 0;
                font-size: 13px;
                color: #cbd5f5;
                line-height: 1.5;
            }
            footer {
                margin-top: 40px;
                display: flex;
                flex-wrap: wrap;
                justify-content: space-between;
                gap: 12px;
                font-size: 12px;
                color: #94a3b8;
            }
            footer a {
                color: inherit;
                text-decoration: none;
            }
            footer a:hover {
                color: #f8fafc;
            }
        </style>
    </head>
    <body class="font-sans antialiased">
        @if (request()->boolean('plain'))
            <div style="min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f3f4f6;color:#111;padding:24px;">
                <div style="max-width:720px;background:#fff;border:1px solid #e5e7eb;border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,.08);padding:28px;">
                    <h1 style="margin:0 0 8px 0;font-size:28px;font-weight:600;">{{ config('app.name', 'Aplikasi') }}</h1>
                    <p style="margin:0 0 16px 0;color:#4b5563;">Halaman sederhana tanpa CSS build untuk debug.</p>
                    <p style="margin:0 0 14px 0;">Jika ini terlihat, masalahnya ada di asset/CSS. Coba hard refresh atau jalankan <code>npm run build</code>.</p>
                    <p style="margin:0 0 14px 0;">
                        <a href="{{ route('login') }}" style="display:inline-block;background:#111;color:#fff;padding:10px 16px;border-radius:10px;text-decoration:none;">Masuk</a>
                        <a href="{{ route('register') }}" style="display:inline-block;margin-left:8px;border:1px solid #d1d5db;padding:10px 16px;border-radius:10px;color:#111;text-decoration:none;">Daftar</a>
                    </p>
                </div>
            </div>
        @else
        <div class="hero-shell">
            <div class="hero-bg"></div>
            <div class="hero-container">
                <div class="hero-card">
                    <div class="hero-header">
                        <div class="hero-brand">
                            <div class="logo">🚢</div>
                            <div>
                                <p style="font-size:11px; letter-spacing:.35em; text-transform:uppercase; color:#7dd3fc;">Supply Chain Suite</p>
                                <h1 style="margin:2px 0 0; font-size:22px;">{{ config('app.name', 'Aplikasi') }}</h1>
                            </div>
                        </div>
                        <div class="hero-buttons">
                            @if (Route::has('login'))
                                <a href="{{ route('login') }}" class="btn-primary">Masuk</a>
                            @endif
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="btn-secondary">Daftar</a>
                            @endif
                        </div>
                    </div>

                    <div class="hero-body">
                        <div>
                            <div class="hero-title">Kendalikan pengadaan & logistik global dalam satu kanvas interaktif.</div>
                            <p class="hero-subtext">
                                Didesain untuk tim procurement modern: pantau PO, shipment, dan warehouse receipt tanpa menunggu laporan manual.
                                Insight finansial & operasional muncul realtime, siap dipresentasikan kapan saja.
                            </p>
                            <div class="stats-grid">
                                <div class="stat-card">
                                    <div class="stat-label">Lead Time</div>
                                    <div class="stat-value">-22%</div>
                                    <small style="color:#94a3b8;">Rata-rata pengurangan door to door</small>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-label">Shipments</div>
                                    <div class="stat-value">480+</div>
                                    <small style="color:#94a3b8;">Dipantau otomatis / tahun</small>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-label">Alerts</div>
                                    <div class="stat-value">24/7</div>
                                    <small style="color:#94a3b8;">Notifikasi milestone penting</small>
                                </div>
                            </div>
                        </div>
                        <div class="panel">
                            <h3>KPI Snapshot — Nov 2025</h3>
                            <p style="font-size:14px; margin-bottom:18px;">
                                Semua metrik procurement kami distream secara otomatis dengan highlight untuk potensi bottleneck.
                            </p>
                            <div style="display:flex; flex-direction:column; gap:16px;">
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <div>
                                        <p style="margin:0; font-size:12px; color:#94a3b8;">PO Fulfillment</p>
                                        <strong style="font-size:26px;">87%</strong>
                                    </div>
                                    <span style="background:rgba(34,197,94,.15); color:#86efac; padding:6px 12px; border-radius:999px; font-size:12px;">+6.4%</span>
                                </div>
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <div>
                                        <p style="margin:0; font-size:12px; color:#94a3b8;">Shipments On-Time</p>
                                        <strong style="font-size:26px;">92%</strong>
                                    </div>
                                    <span style="background:rgba(56,189,248,.15); color:#bae6fd; padding:6px 12px; border-radius:999px; font-size:12px;">+3.1%</span>
                                </div>
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <div>
                                        <p style="margin:0; font-size:12px; color:#94a3b8;">Warehouse Turnover</p>
                                        <strong style="font-size:26px;">18 hari</strong>
                                    </div>
                                    <span style="background:rgba(253,186,116,.2); color:#fed7aa; padding:6px 12px; border-radius:999px; font-size:12px;">Stabil</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="feature-grid">
                    <div class="feature-card">
                        <h4>Centralized Procurement</h4>
                        <p>Catat seluruh purchase order, perbarui fulfillment metrics secara otomatis, dan ekspor laporan hanya dengan satu klik.</p>
                    </div>
                    <div class="feature-card">
                        <h4>Shipment Visibility</h4>
                        <p>Pantau jadwal ETD/ETA, status customs, hingga delivered ke gudang tanpa mengecek spreadsheet.</p>
                    </div>
                    <div class="feature-card">
                        <h4>Warehouse Control</h4>
                        <p>Posting warehouse receipt, lakukan alokasi FIFO, dan lihat statistik penerimaan real-time.</p>
                    </div>
                </div>

                <footer>
                    <p>© {{ date('Y') }} {{ config('app.name', 'Aplikasi') }} — Supply Chain Intelligence Platform.</p>
                    <div style="display:flex; gap:10px; align-items:center;">
                        <a href="{{ route('login') }}">Login</a>
                        <span>•</span>
                        <a href="{{ route('register') }}">Register</a>
                    </div>
                </footer>
            </div>
        </div>
        @endif
    </body>
 </html>
