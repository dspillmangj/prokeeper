@extends('layouts.app')

@section('title', 'Account Security & Passkeys')

@section('content')
<div class="max-w-4xl mx-auto space-y-8">
    <div>
        <h1 class="text-2xl font-bold text-white tracking-tight">Account Security</h1>
        <p class="text-sm text-slate-400 mt-1">Manage two-factor authentication, passkeys, and login credentials.</p>
    </div>

    @if (session('status'))
        <div class="p-4 rounded-xl bg-emerald-950/80 border border-emerald-800 text-emerald-300 text-sm">
            {{ session('status') }}
        </div>
    @endif

    <!-- Passkeys Section -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-800">
            <div>
                <h2 class="text-lg font-bold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                    </svg>
                    <span>Passkeys</span>
                </h2>
                <p class="text-xs text-slate-400 mt-1">
                    Sign in instantly using Touch ID, Face ID, Windows Hello, or a hardware security key without typing a password.
                </p>
            </div>
            <div>
                <button type="button" id="register-passkey-btn" onclick="registerNewPasskey()"
                        class="px-4 py-2.5 rounded-xl bg-sky-600 hover:bg-sky-500 font-semibold text-white text-xs transition shadow flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span>Register New Passkey</span>
                </button>
            </div>
        </div>

        <div class="mt-6">
            @if ($passkeys->isEmpty())
                <div class="py-8 text-center text-slate-500 text-sm">
                    No passkeys registered yet. Click "Register New Passkey" above to add your first biometric or security key.
                </div>
            @else
                <ul class="divide-y divide-slate-800">
                    @foreach ($passkeys as $passkey)
                        <li class="py-3.5 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-slate-800 flex items-center justify-center text-sky-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-white">{{ $passkey->name ?: 'Security Key' }}</div>
                                    <div class="text-xs text-slate-500">Added {{ $passkey->created_at->diffForHumans() }}</div>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('passkey.destroy', $passkey->id) }}" onsubmit="return confirm('Remove this passkey?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs text-rose-400 hover:text-rose-300 font-medium transition px-3 py-1.5 rounded-lg hover:bg-rose-950/40">
                                    Remove
                                </button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <!-- Email Two-Factor Authentication -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-800">
            <div>
                <h2 class="text-lg font-bold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    <span>Email Two-Factor Authentication (2FA)</span>
                </h2>
                <p class="text-xs text-slate-400 mt-1">
                    When enabled, signing in with your password will require entering a 6-digit one-time code sent to {{ $user->email }}.
                </p>
            </div>

            <form method="POST" action="{{ route('settings.security.two-factor') }}">
                @csrf
                <input type="hidden" name="two_factor_email_enabled" value="{{ $user->two_factor_email_enabled ? '0' : '1' }}">
                <button type="submit"
                        class="px-4 py-2.5 rounded-xl font-semibold text-xs transition shadow {{ $user->two_factor_email_enabled ? 'bg-rose-600/80 hover:bg-rose-600 text-white' : 'bg-emerald-600 hover:bg-emerald-500 text-white' }}">
                    {{ $user->two_factor_email_enabled ? 'Disable Email 2FA' : 'Enable Email 2FA' }}
                </button>
            </form>
        </div>

        <div class="mt-4 flex items-center gap-2 text-xs">
            <span class="w-2 h-2 rounded-full {{ $user->two_factor_email_enabled ? 'bg-emerald-400' : 'bg-slate-600' }}"></span>
            <span class="{{ $user->two_factor_email_enabled ? 'text-emerald-400 font-semibold' : 'text-slate-500' }}">
                Status: {{ $user->two_factor_email_enabled ? 'Enabled & Active' : 'Disabled' }}
            </span>
        </div>
    </div>
</div>

<script>
async function registerNewPasskey() {
    if (!window.PublicKeyCredential) {
        alert('Passkeys are not supported on this browser or platform.');
        return;
    }
    const name = prompt('Give this passkey a nickname (e.g. "MacBook Touch ID", "YubiKey"):') || 'Passkey';
    const btn = document.getElementById('register-passkey-btn');
    const origText = btn.innerHTML;
    try {
        btn.innerHTML = '<span>Communicating with device...</span>';
        const optRes = await fetch('/user/passkeys/options', { credentials: 'same-origin' });
        if (!optRes.ok) throw new Error('Failed to retrieve passkey registration options');
        const options = await optRes.json();

        // Convert challenge and user.id from base64
        options.challenge = Uint8Array.from(atob(options.challenge.replace(/-/g, '+').replace(/_/g, '/')), c => c.charCodeAt(0));
        options.user.id = Uint8Array.from(atob(options.user.id.replace(/-/g, '+').replace(/_/g, '/')), c => c.charCodeAt(0));
        if (options.excludeCredentials) {
            options.excludeCredentials.forEach(cred => {
                cred.id = Uint8Array.from(atob(cred.id.replace(/-/g, '+').replace(/_/g, '/')), c => c.charCodeAt(0));
            });
        }

        const credential = await navigator.credentials.create({ publicKey: options });
        if (!credential) return;

        const b64 = buf => btoa(String.fromCharCode(...new Uint8Array(buf))).replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
        const payload = {
            name: name,
            id: credential.id,
            rawId: b64(credential.rawId),
            type: credential.type,
            response: {
                clientDataJSON: b64(credential.response.clientDataJSON),
                attestationObject: b64(credential.response.attestationObject),
                transports: credential.response.getTransports ? credential.response.getTransports() : [],
            }
        };

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const regRes = await fetch('/user/passkeys', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify(payload)
        });

        if (regRes.ok) {
            window.location.reload();
        } else {
            const err = await regRes.json();
            alert(err.message || 'Failed to save passkey.');
        }
    } catch (err) {
        console.error('Registration error:', err);
        alert('Passkey registration cancelled or failed: ' + err.message);
    } finally {
        btn.innerHTML = origText;
    }
}
</script>
@endsection

