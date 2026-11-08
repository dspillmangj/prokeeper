<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Two-Factor Verification - ProKeeper</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&family=jetbrains-mono:500,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased bg-slate-950 text-slate-100 flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <div class="text-center mb-8">
            <a href="/" class="inline-block mb-4">
                <img src="{{ asset('images/icon-white.svg') }}" alt="ProKeeper" class="h-12 w-auto mx-auto">
            </a>
            <h1 class="text-2xl font-extrabold tracking-tight text-white">Security Verification</h1>
            <p class="text-sm text-slate-400 mt-1">We sent a 6-digit verification code to your email</p>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-8 shadow-2xl">
            @if (session('status'))
                <div class="mb-6 p-4 rounded-xl bg-emerald-950/80 border border-emerald-800 text-emerald-300 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 p-4 rounded-xl bg-rose-950/80 border border-rose-800 text-rose-300 text-sm">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('auth.2fa.verify') }}" class="space-y-6">
                @csrf
                <div>
                    <label for="code" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2 text-center">
                        Enter 6-Digit Code
                    </label>
                    <input type="text" id="code" name="code" required autofocus maxlength="6" pattern="[0-9]{6}" inputmode="numeric"
                           class="w-full text-center text-3xl font-mono tracking-[0.5em] px-4 py-3 rounded-xl bg-slate-950 border border-slate-700 text-sky-400 placeholder-slate-600 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                           placeholder="000000">
                </div>

                <button type="submit" class="w-full py-3 px-4 rounded-xl bg-blue-600 hover:bg-blue-500 font-semibold text-white text-sm transition shadow-lg shadow-blue-600/30 flex items-center justify-center">
                    Verify Code
                </button>
            </form>

            <div class="mt-6 pt-6 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400">
                <form method="POST" action="{{ route('auth.2fa.resend') }}">
                    @csrf
                    <button type="submit" class="text-blue-400 hover:text-blue-300 font-medium transition">
                        Resend Code
                    </button>
                </form>

                <a href="{{ route('login') }}" class="text-slate-500 hover:text-slate-400 transition">
                    Back to Sign In
                </a>
            </div>
        </div>
    </div>
</body>
</html>

