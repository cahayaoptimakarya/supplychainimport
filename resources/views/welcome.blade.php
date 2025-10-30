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
        <div class="relative min-h-screen flex items-center justify-center p-8">
            <!-- Silver silhouette background -->
            <div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-10" style="position:absolute; inset:0; z-index:-1; pointer-events:none;">
                <div class="absolute inset-0" style="position:absolute; inset:0; background: linear-gradient(135deg, #f4f5f7 0%, #d7d9dd 50%, #c9cdd1 100%);"></div>
                <div class="absolute inset-0 opacity-70" style="position:absolute; inset:0; background-image:
                        radial-gradient(1200px 600px at -10% 10%, rgba(255,255,255,0.55) 0%, rgba(255,255,255,0) 60%),
                        radial-gradient(900px 500px at 110% 0%, rgba(180,180,185,0.35) 0%, rgba(180,180,185,0) 60%),
                        radial-gradient(800px 500px at 50% 110%, rgba(210,210,215,0.45) 0%, rgba(210,210,215,0) 60%);
                "></div>
            </div>

            <main class="w-full max-w-3xl backdrop-blur-md bg-white/70 border border-white/40 shadow-2xl rounded-3xl overflow-hidden" style="
                width:min(100%, 880px);
                background: rgba(255,255,255,0.78);
                backdrop-filter: blur(8px);
                -webkit-backdrop-filter: blur(8px);
                border-radius: 24px;
                border: 1px solid rgba(255,255,255,0.5);
                box-shadow: 0 20px 60px rgba(0,0,0,.15);
            ">
                <div class="px-10 py-12" style="padding:28px;">
                    <h1 class="text-3xl sm:text-4xl font-semibold tracking-tight text-gray-900" style="margin:0 0 8px 0; font-size:28px; font-weight:600; color:#111827;">
                        {{ config('app.name', 'Aplikasi') }}
                    </h1>
                    <p class="mt-2 text-gray-600 max-w-prose" style="margin:6px 0 0 0; color:#4b5563;">
                        Solusi rantai pasok Anda dengan tampilan mewah, elegan, dan simpel.
                    </p>

                    <div class="mt-8 flex flex-wrap gap-3" style="margin-top:16px; display:flex; gap:12px; flex-wrap:wrap;">
                        @if (Route::has('login'))
                            <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-lg bg-gray-900 px-5 py-2.5 text-white shadow hover:bg-black/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-800" style="
                                display:inline-flex; align-items:center; justify-content:center;
                                background:#111; color:#fff; padding:10px 16px; border-radius:10px; text-decoration:none;
                                box-shadow:0 6px 18px rgba(0,0,0,.15);
                            ">
                                Masuk
                            </a>
                        @endif
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-lg bg-white/80 px-5 py-2.5 text-gray-900 border border-gray-300 shadow hover:bg-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-400" style="
                                display:inline-flex; align-items:center; justify-content:center;
                                background:rgba(255,255,255,.9); color:#111; padding:10px 16px; border-radius:10px; text-decoration:none;
                                border:1px solid #d1d5db; box-shadow:0 6px 18px rgba(0,0,0,.08);
                            ">
                                Daftar
                            </a>
                        @endif
                    </div>
                </div>
            </main>
        </div>
        @endif
    </body>
 </html>

