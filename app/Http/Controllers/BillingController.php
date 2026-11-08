<?php

namespace App\Http\Controllers;

use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BillingController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $org = $user->organization;

        $rosterCount = $org ? Team::where('organization_id', $org->id)->count() : 0;
        $rosterLimit = $user->isPro() ? null : 2;

        $effectivePlan = $user->getEffectivePlan();
        $isOrgAdmin = $user->isAdmin();
        $seatCount = $org ? max(1, $org->users()->count()) : 1;

        return view('billing.index', [
            'user' => $user,
            'org' => $org,
            'rosterCount' => $rosterCount,
            'rosterLimit' => $rosterLimit,
            'effectivePlan' => $effectivePlan,
            'isOrgAdmin' => $isOrgAdmin,
            'seatCount' => $seatCount,
        ]);
    }

    public function subscribePro(Request $request)
    {
        $user = $request->user();
        $priceId = config('services.stripe.pro_price_id', env('STRIPE_PRO_PRICE_ID'));

        if (! $priceId) {
            return back()->with('error', 'Pro subscription is not currently available.');
        }

        try {
            return $user->newSubscription('default', $priceId)
                ->checkout([
                    'success_url' => route('billing.success').'?session_id={CHECKOUT_SESSION_ID}&plan=pro',
                    'cancel_url' => route('billing.index'),
                ]);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Unable to start Stripe checkout: '.$e->getMessage());
        }
    }

    public function subscribeEnterprise(Request $request)
    {
        $user = $request->user();
        $org = $user->organization;

        if (! $user->isAdmin()) {
            return back()->with('error', 'Only organization administrators can purchase Pro Enterprise.');
        }

        $priceId = config('services.stripe.enterprise_price_id', env('STRIPE_ENTERPRISE_PRICE_ID'));
        if (! $priceId) {
            return back()->with('error', 'Enterprise subscription is not currently available.');
        }

        $seatCount = $org ? max(1, $org->users()->count()) : 1;

        try {
            return $user->newSubscription('enterprise', $priceId)
                ->quantity($seatCount)
                ->checkout([
                    'success_url' => route('billing.success').'?session_id={CHECKOUT_SESSION_ID}&plan=enterprise',
                    'cancel_url' => route('billing.index'),
                ]);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Unable to start Enterprise checkout: '.$e->getMessage());
        }
    }

    public function portal(Request $request)
    {
        $user = $request->user();

        try {
            return $user->redirectToBillingPortal(route('billing.index'));
        } catch (\Throwable $e) {
            return back()->with('error', 'No billing portal available: '.$e->getMessage());
        }
    }

    public function success(Request $request)
    {
        $user = $request->user();
        $plan = $request->query('plan');

        if ($plan === 'enterprise' && $user->organization) {
            $user->organization->update([
                'plan' => 'enterprise',
                'billing_user_id' => $user->id,
            ]);
            $user->update(['plan' => 'pro']);
        } elseif ($plan === 'pro') {
            $user->update(['plan' => 'pro']);
        }

        return redirect()->route('billing.index')->with('success', 'Thank you! Your subscription is now active.');
    }
}

