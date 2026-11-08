<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SecuritySettingsController extends Controller
{
    public function show()
    {
        $user = Auth::user();
        $passkeys = $user->passkeys()->orderBy('created_at', 'desc')->get();

        return view('settings.security', [
            'user' => $user,
            'passkeys' => $passkeys,
        ]);
    }

    public function toggleTwoFactor(Request $request)
    {
        $user = Auth::user();
        $enabled = $request->boolean('two_factor_email_enabled');

        $user->update([
            'two_factor_email_enabled' => $enabled,
            'two_factor_code' => null,
            'two_factor_expires_at' => null,
        ]);

        return back()->with('status', $enabled ? 'Two-factor email authentication enabled.' : 'Two-factor authentication disabled.');
    }
}

