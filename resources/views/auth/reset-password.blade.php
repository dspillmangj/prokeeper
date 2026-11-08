<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Set New Password - ProKeeper</title>
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
            <h1 class="text-2xl font-extrabold tracking-tight text-white">Create New Password</h1>
            <p class="text-sm text-slate-400 mt-1">Choose a secure password for your account</p>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-8 shadow-2xl">
            @if ($errors->any())
                <div class="mb-6 p-4 rounded-xl bg-rose-950/80 border border-rose-800 text-rose-300 text-sm">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div>
                    <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Email Address</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $email) }}" required autofocus
                           class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-700 text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-sm"
                           placeholder="coach@school.edu">
                </div>

                <div>
                    <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">New Password</label>
                    <input type="password" id="password" name="password" required
                           class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-700 text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-sm"
                           placeholder="Minimum 8 characters">
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Confirm New Password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required
                           class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-700 text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-sm"
                           placeholder="••••••••">
                </div>

                <button type="submit" class="w-full py-3 px-4 rounded-xl bg-blue-600 hover:bg-blue-500 font-semibold text-white text-sm transition shadow-lg shadow-blue-600/30 flex items-center justify-center">
                    Reset Password
                </button>
            </form>
        </div>
    </div>
</body>
</html>

