<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign In - ProKeeper</title>
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
            <h1 class="text-2xl font-extrabold tracking-tight text-white">Sign in to ProKeeper</h1>
            <p class="text-sm text-slate-400 mt-1">High-speed athletic scorekeeping & live stats</p>
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

            <!-- Passkey Instant Sign-In Button -->
            <button type="button" id="passkey-btn" onclick="startPasskeyLogin()"
                    class="w-full py-3 px-4 mb-6 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 font-semibold text-white text-sm transition flex items-center justify-center gap-2.5 shadow-sm">
                <svg class="w-5 h-5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                </svg>
                <span>Sign in with Passkey</span>
            </button>

            <div class="relative flex items-center justify-center mb-6">
                <div class="border-t border-slate-800 w-full"></div>
                <div class="bg-slate-900 px-3 text-xs uppercase tracking-wider text-slate-500 font-semibold absolute">or with password</div>
            </div>

            <form method="POST" action="{{ route('login.post') }}" class="space-y-5">
                @csrf
                <div>
                    <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Email Address</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                           class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-700 text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-sm"
                           placeholder="coach@school.edu">
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-400">Password</label>
                        <a href="{{ route('password.request') }}" class="text-xs text-blue-400 hover:text-blue-300 transition">Forgot password?</a>
                    </div>
                    <input type="password" id="password" name="password" required
                           class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-700 text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-sm"
                           placeholder="••••••••">
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center text-sm text-slate-400">
                        <input type="checkbox" name="remember" class="rounded bg-slate-950 border-slate-700 text-blue-600 focus:ring-blue-500 mr-2">
                        Remember me
                    </label>
                </div>

                <button type="submit" class="w-full py-3 px-4 rounded-xl bg-blue-600 hover:bg-blue-500 font-semibold text-white text-sm transition shadow-lg shadow-blue-600/30 flex items-center justify-center">
                    Sign In
                </button>
            </form>

            <div class="mt-6 text-center text-sm text-slate-400">
                Don't have an account?
                <a href="{{ route('register') }}" class="text-blue-400 hover:text-blue-300 font-medium">Create account</a>
            </div>
        </div>

        <div class="mt-6 text-center text-xs text-slate-500">
            <a href="{{ route('public.live.prompt') }}" class="text-slate-400 hover:text-slate-300">Looking for a game? Enter Game Code &rarr;</a>
        </div>
    </div>

    <script>
    async function startPasskeyLogin() {
        if (!window.PublicKeyCredential) {
            alert('Passkeys are not supported on this browser or device.');
            return;
        }
        const btn = document.getElementById('passkey-btn');
        const origText = btn.innerHTML;
        try {
            btn.innerHTML = '<span>Connecting to authenticator...</span>';
            const optRes = await fetch('/passkeys/login/options', { credentials: 'same-origin' });
            if (!optRes.ok) throw new Error('Failed to fetch passkey challenge');
            const options = await optRes.json();

            // Decode challenge
            options.challenge = Uint8Array.from(atob(options.challenge.replace(/-/g, '+').replace(/_/g, '/')), c => c.charCodeAt(0));
            if (options.allowCredentials) {
                options.allowCredentials.forEach(cred => {
                    cred.id = Uint8Array.from(atob(cred.id.replace(/-/g, '+').replace(/_/g, '/')), c => c.charCodeAt(0));
                });
            }

            const assertion = await navigator.credentials.get({ publicKey: options });
            if (!assertion) return;

            // Base64url encode response
            const b64 = buf => btoa(String.fromCharCode(...new Uint8Array(buf))).replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
            const body = {
                id: assertion.id,
                rawId: b64(assertion.rawId),
                type: assertion.type,
                response: {
                    authenticatorData: b64(assertion.response.authenticatorData),
                    clientDataJSON: b64(assertion.response.clientDataJSON),
                    signature: b64(assertion.response.signature),
                    userHandle: assertion.response.userHandle ? b64(assertion.response.userHandle) : null,
                }
            };

            const csrfToken = '{{ csrf_token() }}';
            const verifyRes = await fetch('/passkeys/login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify(body)
            });

            if (verifyRes.ok) {
                window.location.href = '{{ route("dashboard") }}';
            } else {
                const err = await verifyRes.json();
                alert(err.message || 'Passkey verification failed.');
            }
        } catch (err) {
            console.error('Passkey error:', err);
            alert('Could not authenticate with Passkey: ' + err.message);
        } finally {
            btn.innerHTML = origText;
        }
    }
    </script>
</body>
</html>
