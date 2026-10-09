<div>
    @if (!$game)
        <!-- Game Access Code Prompt Screen for Official Scorebook -->
        <div class="min-h-[85vh] flex items-center justify-center p-4">
            <div class="max-w-md w-full bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl space-y-6">
                <div class="text-center space-y-2">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-amber-500/20 border border-amber-500/30 text-amber-400 mb-2">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h1 class="text-2xl font-black tracking-tight text-white">ProKeeper Scorebook</h1>
                    <p class="text-xs text-slate-400">Enter a 6-digit game access code to load the official scoresheet, running score, and signatures.</p>
                </div>

                @if ($errorMessage)
                    <div class="p-3 rounded-xl bg-rose-950/80 border border-rose-800 text-rose-300 text-xs font-semibold text-center animate-fade-in">
                        {{ $errorMessage }}
                    </div>
                @endif

                <form wire:submit.prevent="submitCode" class="space-y-4">
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1.5 text-center">
                            Game Access Code
                        </label>
                        <input type="text"
                               wire:model="inputCode"
                               maxlength="10"
                               placeholder="e.g. BSK001"
                               class="w-full text-center tracking-widest text-2xl font-mono font-black uppercase py-3.5 px-4 rounded-2xl bg-slate-950 border border-slate-700 text-white placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 shadow-inner">
                    </div>

                    <button type="submit"
                            class="w-full py-3.5 px-4 rounded-2xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-sm tracking-wide transition shadow-lg shadow-amber-500/30 cursor-pointer">
                        Open Scorebook &rarr;
                    </button>
                </form>
            </div>
        </div>
    @else
        <div class="min-h-screen bg-slate-950 text-slate-100 font-sans print:bg-white print:text-black print:min-h-0" wire:poll.5000ms>
            <!-- Screen-Only Action & Control Navigation Bar -->
            <header class="sticky top-0 z-40 bg-slate-900/95 backdrop-blur border-b border-slate-800 px-4 py-3 print:hidden shadow-lg">
                <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="flex items-center space-x-3">
                        <a href="{{ route('public.scorebook.prompt') }}" class="px-2.5 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-bold transition flex items-center gap-1 border border-slate-700">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                            <span>Change Game</span>
                        </a>
                        <div class="h-4 w-px bg-slate-700"></div>
                        <div class="flex items-center space-x-2">
                            <span class="px-2 py-0.5 rounded bg-amber-500/20 text-amber-300 font-mono text-[11px] font-black border border-amber-500/40">
                                PROKEEPER OFFICIAL SCOREBOOK
                            </span>
                            <span class="text-xs text-slate-400 font-mono hidden md:inline">GAME CODE: {{ $game->access_code }}</span>
                        </div>
                    </div>

            <!-- Fast Sheet Navigation & Export Actions -->
            @php
                $signatures = $game->settings['signatures'] ?? [];
                $signedCount = count(array_filter(['official_scorer', 'referee', 'umpire1', 'umpire2'], fn($k) => !empty($signatures[$k]['data'])));
            @endphp
            <div class="flex items-center space-x-2">
                <button wire:click="openSignatureModal('official_scorer')" class="px-3 py-1.5 rounded-lg bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/40 text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    <span>✍️ Signatures ({{ $signedCount }}/4)</span>
                </button>
                <a href="#sheet-home" class="px-3 py-1.5 rounded-lg bg-blue-900/40 hover:bg-blue-900/60 text-blue-300 border border-blue-600/40 text-xs font-bold transition">
                    Home Sheet (Pg 1)
                </a>
                <a href="#sheet-away" class="px-3 py-1.5 rounded-lg bg-rose-900/40 hover:bg-rose-900/60 text-rose-300 border border-rose-600/40 text-xs font-bold transition">
                    Visiting Sheet (Pg 2)
                </a>
                <button onclick="window.print()" class="px-4 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-lg shadow-emerald-600/20">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    <span>Print Scorebook</span>
                </button>
                <a href="{{ route('games.pdf', $game->access_code) }}" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700 text-xs font-bold transition">
                    Download PDF
                </a>
            </div>
        </div>
    </header>

    @include('livewire.partials.ncaa-signature-modal')

    <!-- Main Sheets Container -->
    <main class="max-w-[1240px] mx-auto py-6 px-4 space-y-10 print:max-w-none print:p-0 print:m-0 print:space-y-0">

        <!-- ========================================== -->
        <!-- SHEET 1: HOME TEAM PROKEEPER SCOREBOOK PAGE -->
        <!-- ========================================== -->
        <section id="sheet-home" class="ncaa-page-sheet bg-white text-black p-6 rounded-xl shadow-2xl border border-slate-300 font-sans print:rounded-none print:shadow-none print:border-none print:p-0 print:m-0">
            @include('livewire.partials.ncaa-team-sheet', [
                'team' => $home,
                'oppTeam' => $away,
                'isHome' => true,
                'game' => $game,
                'pageNumber' => 1,
                'sheetTitle' => 'HOME TEAM SCOREBOOK PAGE',
            ])
        </section>

        <!-- Page Break Indicator in Screen View -->
        <div class="text-center print:hidden">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-slate-800 border border-slate-700 text-slate-400 text-xs font-mono uppercase tracking-widest">
                <span>Page 1 / Page 2 Boundary</span>
                <span class="text-slate-500">&bull;</span>
                <span>Auto-Breaks on Print (2 Landscape Pages Total)</span>
            </div>
        </div>

        <!-- ============================================== -->
        <!-- SHEET 2: VISITING TEAM PROKEEPER SCOREBOOK PAGE -->
        <!-- ============================================== -->
        <section id="sheet-away" class="ncaa-page-sheet bg-white text-black p-6 rounded-xl shadow-2xl border border-slate-300 font-sans print:rounded-none print:shadow-none print:border-none print:p-0 print:m-0">
            @include('livewire.partials.ncaa-team-sheet', [
                'team' => $away,
                'oppTeam' => $home,
                'isHome' => false,
                'game' => $game,
                'pageNumber' => 2,
                'sheetTitle' => 'VISITING TEAM SCOREBOOK PAGE',
            ])
        </section>
    </main>

    <!-- Scorebook Print & Sheet Styling -->
    <style>
        @media print {
            @page {
                size: letter landscape;
                margin: 0.25in;
            }
            html, body {
                background: #ffffff !important;
                color: #000000 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }
            .ncaa-page-sheet {
                page-break-after: always !important;
                break-after: page !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                box-sizing: border-box !important;
                padding: 0 !important;
                margin: 0 !important;
                border: none !important;
                box-shadow: none !important;
                width: 100% !important;
            }
            .ncaa-page-sheet:last-of-type {
                page-break-after: avoid !important;
                break-after: avoid !important;
            }
        }

        .ncaa-table th, .ncaa-table td {
            border: 1px solid #000000;
        }

        .ncaa-border {
            border: 1px solid #000000;
        }

        .ncaa-border-thick {
            border: 2px solid #000000;
        }
    </style>
</div>
@endif
</div>
