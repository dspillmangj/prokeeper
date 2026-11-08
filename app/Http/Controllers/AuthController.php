<?php

namespace App\Http\Controllers;

use App\Mail\TwoFactorCodeMail;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');
        }

        // Check for 2FA
        if ($user->two_factor_email_enabled) {
            $code = sprintf('%06d', random_int(100000, 999999));
            $user->update([
                'two_factor_code' => Hash::make($code),
                'two_factor_expires_at' => now()->addMinutes(10),
            ]);

            $request->session()->put('login.2fa.user_id', $user->id);
            $request->session()->put('login.2fa.remember', $request->boolean('remember'));

            try {
                Mail::to($user->email)->send(new TwoFactorCodeMail($code));
            } catch (\Throwable $e) {
                report($e);
            }

            return redirect()->route('auth.2fa.show');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function show2fa(Request $request)
    {
        if (! $request->session()->has('login.2fa.user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor');
    }

    public function verify2fa(Request $request)
    {
        $userId = $request->session()->get('login.2fa.user_id');
        if (! $userId) {
            return redirect()->route('login');
        }

        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = User::find($userId);
        if (! $user || ! $user->two_factor_code || ! $user->two_factor_expires_at) {
            return redirect()->route('login')->withErrors(['email' => 'Session expired. Please sign in again.']);
        }

        if (now()->isAfter($user->two_factor_expires_at)) {
            return back()->withErrors(['code' => 'Security code has expired. Please request a new one.']);
        }

        if (! Hash::check($request->code, $user->two_factor_code)) {
            return back()->withErrors(['code' => 'Invalid security code. Please check your email.']);
        }

        // Clear 2FA code
        $user->update([
            'two_factor_code' => null,
            'two_factor_expires_at' => null,
        ]);

        $remember = $request->session()->pull('login.2fa.remember', false);
        $request->session()->forget('login.2fa.user_id');

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function resend2fa(Request $request)
    {
        $userId = $request->session()->get('login.2fa.user_id');
        if (! $userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);
        if (! $user) {
            return redirect()->route('login');
        }

        $code = sprintf('%06d', random_int(100000, 999999));
        $user->update([
            'two_factor_code' => Hash::make($code),
            'two_factor_expires_at' => now()->addMinutes(10),
        ]);

        try {
            Mail::to($user->email)->send(new TwoFactorCodeMail($code));
        } catch (\Throwable $e) {
            report($e);
        }

        return back()->with('status', 'A new security code has been sent to your email.');
    }

    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'organization_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $orgName = ! empty($validated['organization_name'])
            ? $validated['organization_name']
            : ($validated['name']."'s Organization");

        $org = Organization::create([
            'name' => $orgName,
            'slug' => Str::slug($orgName).'-'.Str::random(4),
            'plan' => 'free',
            'seats' => 1,
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'organization_id' => $org->id,
            'role' => 'admin',
            'plan' => 'free',
        ]);

        $org->update(['billing_user_id' => $user->id]);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    // Password Reset
    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    public function showResetPassword(Request $request, ?string $token = null)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    // Email Verification
    public function showVerifyNotice(Request $request)
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect()->route('dashboard')
            : view('auth.verify-email');
    }

    public function verifyEmail(EmailVerificationRequest $request)
    {
        $request->fulfill();

        return redirect()->route('dashboard')->with('verified', true);
    }

    public function resendVerificationEmail(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }
}
