@extends('layouts.app')

@section('title', 'Subscription & Billing')

@section('content')
<div class="max-w-5xl mx-auto space-y-8">
    <div>
        <h1 class="text-2xl font-bold text-white tracking-tight">Billing & Plans</h1>
        <p class="text-sm text-slate-400 mt-1">Manage your ProKeeper subscription, team seats, and invoices.</p>
    </div>

    <!-- Current Usage Card -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="p-5 rounded-xl bg-slate-950/70 border border-slate-800/80">
                <div class="text-xs uppercase font-bold tracking-wider text-slate-400">Current Plan</div>
                <div class="mt-2 text-2xl font-extrabold text-white flex items-center gap-2">
                    @if ($effectivePlan === 'enterprise')
                        <span class="text-purple-400">Pro Enterprise</span>
                    @elseif ($effectivePlan === 'pro')
                        <span class="text-sky-400">Pro ($3/mo)</span>
                    @else
                        <span class="text-slate-200">Free Tier</span>
                    @endif
                </div>
                <div class="mt-1 text-xs text-slate-500">
                    @if ($effectivePlan === 'free')
                        Standard single-user personal access
                    @elseif ($effectivePlan === 'pro')
                        Unlimited rosters & public live streaming
                    @else
                        Department-wide managed billing
                    @endif
                </div>
            </div>

            <div class="p-5 rounded-xl bg-slate-950/70 border border-slate-800/80">
                <div class="text-xs uppercase font-bold tracking-wider text-slate-400">Saved Rosters</div>
                <div class="mt-2 text-2xl font-extrabold text-white">
                    {{ $rosterCount }} <span class="text-sm font-semibold text-slate-500">/ {{ $rosterLimit ?? 'Unlimited' }}</span>
                </div>
                <div class="mt-1 text-xs text-slate-500">
                    @if ($rosterLimit)
                        {{ max(0, $rosterLimit - $rosterCount) }} roster slots remaining
                    @else
                        Unlimited rosters unlocked
                    @endif
                </div>
            </div>

            <div class="p-5 rounded-xl bg-slate-950/70 border border-slate-800/80">
                <div class="text-xs uppercase font-bold tracking-wider text-slate-400">Live Viewer Sharing</div>
                <div class="mt-2 text-2xl font-extrabold text-white">
                    @if ($effectivePlan === 'free')
                        <span class="text-amber-400 text-lg">Owner Only</span>
                    @else
                        <span class="text-emerald-400 text-lg">Public & QR Codes</span>
                    @endif
                </div>
                <div class="mt-1 text-xs text-slate-500">
                    @if ($effectivePlan === 'free')
                        Spectator view requires Pro upgrade
                    @else
                        Unlimited spectators & coach QR codes
                    @endif
                </div>
            </div>
        </div>

        @if ($user->subscribed('default') || ($user->organization && $user->organization->billing_user_id === $user->id && $user->subscribed('enterprise')))
            <div class="mt-6 pt-6 border-t border-slate-800 flex items-center justify-between">
                <span class="text-xs text-slate-400">Stripe Customer Billing Portal available to update payment methods or view invoices.</span>
                <a href="{{ route('billing.portal') }}" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold transition border border-slate-700">
                    Manage Invoices & Cards &rarr;
                </a>
            </div>
        @endif
    </div>

    <!-- Pricing Options Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Free Plan -->
        <div class="bg-slate-900 border {{ $effectivePlan === 'free' ? 'border-sky-500/50 ring-1 ring-sky-500/30' : 'border-slate-800' }} rounded-2xl p-6 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-bold text-white">Free</h3>
                    @if ($effectivePlan === 'free')
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-800 text-slate-300 border border-slate-700">Active</span>
                    @endif
                </div>
                <div class="mt-3 text-3xl font-extrabold text-white">$0 <span class="text-xs font-normal text-slate-400">/ forever</span></div>
                <p class="mt-2 text-xs text-slate-400">Essential scoring for individual scorekeepers.</p>

                <ul class="mt-6 space-y-2.5 text-xs text-slate-300">
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Up to 2 Saved Team Rosters
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Full Operator Keyboard HUD
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Official NCAA PDF Scorebooks
                    </li>
                    <li class="flex items-center gap-2 text-slate-500">
                        <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        Private single-viewer mode only
                    </li>
                </ul>
            </div>

            <div class="mt-8 pt-4 border-t border-slate-800/80">
                <button type="button" disabled class="w-full py-2.5 rounded-xl bg-slate-800/50 text-slate-500 font-semibold text-xs text-center cursor-not-allowed">
                    {{ $effectivePlan === 'free' ? 'Current Plan' : 'Free Tier' }}
                </button>
            </div>
        </div>

        <!-- Pro Plan -->
        <div class="bg-slate-900 border {{ $effectivePlan === 'pro' ? 'border-sky-500 ring-2 ring-sky-500/40' : 'border-slate-800' }} rounded-2xl p-6 flex flex-col justify-between relative shadow-xl">
            @if ($effectivePlan !== 'pro' && $effectivePlan !== 'enterprise')
                <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-0.5 rounded-full bg-sky-500 text-white font-black text-[10px] uppercase tracking-wider shadow">
                    Recommended
                </div>
            @endif

            <div>
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-bold text-white">Pro</h3>
                    @if ($effectivePlan === 'pro')
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-sky-500/20 text-sky-300 border border-sky-500/40">Active</span>
                    @endif
                </div>
                <div class="mt-3 text-3xl font-extrabold text-white">$3 <span class="text-xs font-normal text-slate-400">/ user / mo</span></div>
                <p class="mt-2 text-xs text-slate-400">For competitive coaches and athletic programs.</p>

                <ul class="mt-6 space-y-2.5 text-xs text-slate-300">
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <strong>Unlimited</strong> Saved Team Rosters
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Public Live View Spectator Codes
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Secure QR Sharing for Scorebook & Stats
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Email-based Roster & Game Sharing
                    </li>
                </ul>
            </div>

            <div class="mt-8 pt-4 border-t border-slate-800/80">
                @if ($effectivePlan === 'pro')
                    <a href="{{ route('billing.portal') }}" class="block w-full py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-semibold text-xs text-center transition">
                        Manage Pro Subscription
                    </a>
                @elseif ($effectivePlan === 'enterprise')
                    <button type="button" disabled class="w-full py-2.5 rounded-xl bg-slate-800/50 text-slate-500 font-semibold text-xs text-center cursor-not-allowed">
                        Included in Enterprise
                    </button>
                @else
                    <form method="POST" action="{{ route('billing.subscribe.pro') }}">
                        @csrf
                        <button type="submit" class="w-full py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs text-center transition shadow-lg shadow-blue-600/30">
                            Upgrade to Pro ($3/mo)
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <!-- Pro Enterprise Plan -->
        <div class="bg-slate-900 border {{ $effectivePlan === 'enterprise' ? 'border-purple-500 ring-2 ring-purple-500/40' : 'border-slate-800' }} rounded-2xl p-6 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-bold text-white">Pro Enterprise</h3>
                    @if ($effectivePlan === 'enterprise')
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-purple-500/20 text-purple-300 border border-purple-500/40">Active</span>
                    @endif
                </div>
                <div class="mt-3 text-3xl font-extrabold text-white">$3 <span class="text-xs font-normal text-slate-400">/ seat / mo</span></div>
                <p class="mt-2 text-xs text-slate-400">Centralized billing for the whole organization.</p>

                <ul class="mt-6 space-y-2.5 text-xs text-slate-300">
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        All Pro Features for <strong>All Members</strong>
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Centralized Single-Account Billing
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Auto-adjusting seat quantity ({{ $seatCount }} active)
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Instant Org-Wide Roster & Game Access
                    </li>
                </ul>
            </div>

            <div class="mt-8 pt-4 border-t border-slate-800/80">
                @if ($effectivePlan === 'enterprise')
                    <a href="{{ route('billing.portal') }}" class="block w-full py-2.5 rounded-xl bg-purple-600/30 hover:bg-purple-600/50 text-purple-200 border border-purple-500/40 font-semibold text-xs text-center transition">
                        Manage Enterprise Seats
                    </a>
                @elseif ($isOrgAdmin)
                    <form method="POST" action="{{ route('billing.subscribe.enterprise') }}">
                        @csrf
                        <button type="submit" class="w-full py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-semibold text-xs text-center transition shadow-lg shadow-purple-600/30">
                            Upgrade Org to Enterprise
                        </button>
                    </form>
                @else
                    <button type="button" disabled class="w-full py-2.5 rounded-xl bg-slate-800/50 text-slate-500 font-semibold text-xs text-center cursor-not-allowed">
                        Contact Org Admin to Upgrade
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

