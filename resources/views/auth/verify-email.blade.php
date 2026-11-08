<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify Email Address - ProKeeper</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased bg-slate-950 text-slate-100 flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <div class="text-center mb-8">
            <a href="/" class="inline-block mb-4">
                <img src="{{ asset('images/icon-white.svg') }}" alt="ProKeeper" class="h-12 w-auto mx-auto">
            </a>
            <h1 class="text-2xl font-extrabold tracking-tight text-white">Verify Your Email</h1>
            <p class="text-sm text-slate-400 mt-1">Please confirm your email address before creating games or rosters</p>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-8 shadow-2xl">
            @if (session('status') == 'verification-link-sent')
                <div class="mb-6 p-4 rounded-xl bg-emerald-950/80 border border-emerald-800 text-emerald-300 text-sm">
                    A fresh verification link has been sent to your email address.
                </div>
            @endif

            <p class="text-slate-300 text-sm leading-relaxed mb-6">
                Thanks for signing up! Before creating your first team roster or live game, please verify your email address by clicking on the link we just emailed to you.
            </p>

            <div class="space-y-4">
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <button type="submit" class="w-full py-3 px-4 rounded-xl bg-blue-600 hover:bg-blue-500 font-semibold text-white text-sm transition shadow-lg shadow-blue-600/30 flex items-center justify-center">
                        Resend Verification Email
                    </button>
                </form>

                <div class="flex items-center justify-between pt-2">
                    <a href="{{ route('dashboard') }}" class="text-xs text-slate-400 hover:text-slate-300 transition">
                        Back to Dashboard
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-xs text-rose-400 hover:text-rose-300 transition">
                            Sign Out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

