<div class="min-h-screen bg-slate-950 text-slate-100 font-sans print:bg-white print:text-black print:min-h-0" wire:poll.5000ms>
    <!-- Screen-Only Action & Control Navigation Bar -->
    <header class="sticky top-0 z-40 bg-slate-900/95 backdrop-blur border-b border-slate-800 px-4 py-3 print:hidden shadow-lg">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="flex items-center space-x-3">
                <a href="{{ route('games.operator', $game->uuid) }}" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold transition flex items-center gap-1.5 border border-slate-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    <span>Operator Console</span>
                </a>
                <div class="h-4 w-px bg-slate-700"></div>
                <div class="flex items-center space-x-2">
                    <span class="px-2 py-0.5 rounded bg-amber-500/20 text-amber-300 font-mono text-[11px] font-black border border-amber-500/40">
                        NCAA OFFICIAL SCOREBOOK
                    </span>
                    <span class="text-xs text-slate-400 font-mono hidden md:inline">GAME CODE: {{ $game->access_code }}</span>
                </div>
            </div>

            <!-- Fast Sheet Navigation & Export Actions -->
            <div class="flex items-center space-x-2">
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

    <!-- Main Sheets Container -->
    <main class="max-w-[1100px] mx-auto py-6 px-4 space-y-10 print:max-w-none print:p-0 print:m-0 print:space-y-0">

        <!-- ========================================== -->
        <!-- SHEET 1: HOME TEAM NCAA SCOREBOOK PAGE     -->
        <!-- ========================================== -->
        <section id="sheet-home" class="ncaa-page-sheet bg-white text-black p-6 rounded-xl shadow-2xl border border-slate-300 font-sans print:rounded-none print:shadow-none print:border-none print:p-4 print:m-0">
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
                <span>Auto-Breaks on Print</span>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- SHEET 2: VISITING TEAM NCAA SCOREBOOK PAGE -->
        <!-- ========================================== -->
        <section id="sheet-away" class="ncaa-page-sheet bg-white text-black p-6 rounded-xl shadow-2xl border border-slate-300 font-sans print:rounded-none print:shadow-none print:border-none print:p-4 print:m-0">
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
            body {
                background: #ffffff !important;
                color: #000000 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .ncaa-page-sheet {
                page-break-after: always !important;
                break-after: page !important;
                min-height: 100vh !important;
                box-sizing: border-box !important;
                padding: 0.25in !important;
                margin: 0 !important;
                border: none !important;
                box-shadow: none !important;
            }
            .ncaa-page-sheet:last-of-type {
                page-break-after: avoid !important;
                break-after: avoid !important;
            }
            @page {
                size: letter portrait;
                margin: 0.35in;
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
