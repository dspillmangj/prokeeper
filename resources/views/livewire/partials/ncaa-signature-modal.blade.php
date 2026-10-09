@if ($showSignatureModal)
    @php
        $officials = $game->settings['officials'] ?? [];
        $signatures = $game->settings['signatures'] ?? [];
        $rolesList = [
            'official_scorer' => ['label' => 'Official Scorer', 'badge' => 'Scorer', 'desc' => 'Primary Table Scorekeeper'],
            'referee' => ['label' => 'Lead Referee', 'badge' => 'Referee', 'desc' => 'Crew Chief / Head Official'],
            'umpire1' => ['label' => 'Umpire 1', 'badge' => 'Umpire 1', 'desc' => 'Second Floor Official'],
            'umpire2' => ['label' => 'Umpire 2', 'badge' => 'Umpire 2', 'desc' => 'Third Floor Official'],
        ];
    @endphp

    <div class="fixed inset-0 bg-slate-950/85 backdrop-blur-md z-50 flex items-center justify-center p-3 select-none animate-fade-in"
         @keydown.window.escape.prevent="$wire.closeSignatureModal()"
         wire:keydown.escape="closeSignatureModal"
         x-data="ncaaSignaturePad({
             role: '{{ $activeSignRole }}',
             name: '{{ addslashes($signerName) }}',
             initials: '{{ addslashes($signerInitials) }}',
             mode: '{{ $signatureMode }}',
             font: '{{ $typedFont }}',
             color: '{{ $signatureColor }}',
             existingData: '{{ addslashes($signatures[$activeSignRole]['data'] ?? '') }}'
         })">

        <div class="bg-slate-900 border border-slate-700 rounded-3xl max-w-2xl w-full p-5 shadow-2xl space-y-4 max-h-[94vh] flex flex-col overflow-hidden"
             @click.outside="$wire.closeSignatureModal()">

            <!-- Modal Header -->
            <div class="flex items-center justify-between pb-3 border-b border-slate-800 shrink-0">
                <div class="flex items-center space-x-2.5">
                    <div class="p-2.5 rounded-2xl bg-amber-500/20 text-amber-400 border border-amber-500/30">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-black uppercase tracking-wider text-white flex items-center gap-2">
                            <span>Official Scorebook Signature</span>
                            <span class="px-2 py-0.5 rounded-md bg-amber-500/20 text-amber-300 text-[10px] font-mono font-bold border border-amber-500/30">Official Ledger</span>
                        </h3>
                        <p class="text-[11px] text-slate-400">Draw or type your signature or initials to approve the official record</p>
                    </div>
                </div>

                <button wire:click="closeSignatureModal" type="button" class="p-1.5 rounded-xl bg-slate-800 text-slate-400 hover:text-white transition" title="Close (Esc)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Role Selector Pills -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 shrink-0">
                @foreach ($rolesList as $rk => $rInfo)
                    @php
                        $isSigned = !empty($signatures[$rk]['data']);
                        $isActive = ($activeSignRole === $rk);
                    @endphp
                    <button type="button"
                            wire:click="switchSignRole('{{ $rk }}')"
                            class="px-2.5 py-2 rounded-xl text-left border transition relative flex flex-col justify-between {{ $isActive ? 'bg-amber-500/15 border-amber-500 text-amber-300 shadow-md shadow-amber-500/10' : 'bg-slate-950/60 border-slate-800 text-slate-400 hover:text-slate-200 hover:border-slate-700' }}">
                        <div class="flex items-center justify-between w-full">
                            <span class="text-[10px] font-bold uppercase tracking-wider">{{ $rInfo['badge'] }}</span>
                            @if ($isSigned)
                                <span class="w-2 h-2 rounded-full bg-emerald-400" title="Signed"></span>
                            @else
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-600" title="Unsigned"></span>
                            @endif
                        </div>
                        <span class="text-[11px] font-bold text-white truncate mt-0.5">
                            {{ $officials[$rk] ?? ($signatures[$rk]['signer_name'] ?? 'Not set') }}
                        </span>
                    </button>
                @endforeach
            </div>

            <!-- Signer Name & Initials Form Fields -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 bg-slate-950/70 p-3 rounded-2xl border border-slate-800 shrink-0 text-xs">
                <div class="sm:col-span-2">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Official's Full Name</label>
                    <input type="text"
                           x-model="name"
                           @input="updateInitials()"
                           placeholder="e.g. Marcus Taylor"
                           class="w-full px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-100 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none font-semibold">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Initials</label>
                    <input type="text"
                           x-model="initials"
                           maxlength="4"
                           placeholder="e.g. MT"
                           class="w-full px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-100 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none uppercase font-mono font-bold text-center">
                </div>
            </div>

            <!-- Draw vs Type Mode Segmented Switcher -->
            <div class="flex items-center justify-between border-b border-slate-800 pb-2 shrink-0">
                <div class="flex items-center p-1 rounded-xl bg-slate-950 border border-slate-800 space-x-1">
                    <button type="button"
                            @click="setMode('draw')"
                            :class="mode === 'draw' ? 'bg-amber-500 text-black font-black shadow-sm' : 'text-slate-400 hover:text-white'"
                            class="px-3 py-1 rounded-lg text-xs font-bold transition flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                        <span>Draw with Pen / Touch</span>
                    </button>
                    <button type="button"
                            @click="setMode('type')"
                            :class="mode === 'type' ? 'bg-amber-500 text-black font-black shadow-sm' : 'text-slate-400 hover:text-white'"
                            class="px-3 py-1 rounded-lg text-xs font-bold transition flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18M10 6l-2 12m8-12l-2 12"/></svg>
                        <span>Type Signature / Initials</span>
                    </button>
                </div>

                <!-- Ink Color Switcher -->
                <div class="flex items-center space-x-1.5">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mr-1">Ink:</span>
                    <button type="button"
                            @click="setColor('#000000')"
                            :class="color === '#000000' ? 'ring-2 ring-amber-400 ring-offset-2 ring-offset-slate-900' : ''"
                            class="w-5 h-5 rounded-full bg-black border border-slate-600 transition"
                            title="Black Ink"></button>
                    <button type="button"
                            @click="setColor('#0f2942')"
                            :class="color === '#0f2942' ? 'ring-2 ring-amber-400 ring-offset-2 ring-offset-slate-900' : ''"
                            class="w-5 h-5 rounded-full bg-[#0f2942] border border-blue-900 transition"
                            title="Official Navy Ink"></button>
                    <button type="button"
                            @click="setColor('#1e3a8a')"
                            :class="color === '#1e3a8a' ? 'ring-2 ring-amber-400 ring-offset-2 ring-offset-slate-900' : ''"
                            class="w-5 h-5 rounded-full bg-[#1e3a8a] border border-blue-600 transition"
                            title="Royal Blue Ink"></button>
                </div>
            </div>

            <!-- Content Area (Scrollable if needed) -->
            <div class="flex-1 overflow-y-auto space-y-3 min-h-[220px]">
                <!-- ============================================== -->
                <!-- DRAW MODE: HTML5 Touch / Pen Canvas Pad        -->
                <!-- ============================================== -->
                <div x-show="mode === 'draw'" class="space-y-2">
                    <div class="relative bg-white rounded-2xl border-2 border-slate-700 overflow-hidden shadow-inner flex flex-col justify-between"
                         style="height: 190px;"
                         wire:ignore>
                        <!-- Canvas Header Toolbar -->
                        <div class="flex items-center justify-between px-3 py-1.5 bg-slate-100 border-b border-slate-300 text-[11px] font-mono text-slate-600 select-none">
                            <div class="flex items-center space-x-2">
                                <span class="font-bold text-black uppercase">Signature Pad</span>
                                <span class="text-slate-400">&bull;</span>
                                <span class="text-[10px]">Stylus, Finger or Mouse</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <button type="button"
                                        @click="undoStroke()"
                                        class="px-2 py-0.5 rounded bg-slate-200 hover:bg-slate-300 text-slate-800 text-[10px] font-bold transition flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                                    <span>Undo</span>
                                </button>
                                <button type="button"
                                        @click="clearCanvas()"
                                        class="px-2 py-0.5 rounded bg-rose-100 hover:bg-rose-200 text-rose-800 text-[10px] font-bold transition flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    <span>Clear</span>
                                </button>
                            </div>
                        </div>

                        <!-- Canvas Drawing Area -->
                        <div class="relative flex-1 bg-white cursor-crosshair overflow-hidden">
                            <canvas x-ref="canvas"
                                    class="w-full h-full block"
                                    style="touch-action: none;"
                                    @pointerdown="startDrawing($event)"
                                    @pointermove="draw($event)"
                                    @pointerup="stopDrawing($event)"
                                    @pointerleave="stopDrawing($event)"></canvas>

                            <!-- Baseline indicator -->
                            <div class="absolute bottom-6 inset-x-8 border-b border-dashed border-slate-300 pointer-events-none flex justify-between items-center text-[9px] font-mono text-slate-400">
                                <span>✖ Sign on this line</span>
                                <span>Official Ledger Mark</span>
                            </div>
                        </div>

                        <!-- Pen Stroke Size Selector -->
                        <div class="flex items-center justify-between px-3 py-1 bg-slate-50 border-t border-slate-200 text-[10px] text-slate-500">
                            <span>Pen Stroke:</span>
                            <div class="flex items-center space-x-3">
                                <button type="button" @click="setStroke(2)" :class="strokeWidth === 2 ? 'font-bold text-black underline' : ''">Fine (2px)</button>
                                <button type="button" @click="setStroke(3.5)" :class="strokeWidth === 3.5 ? 'font-bold text-black underline' : ''">Standard (3.5px)</button>
                                <button type="button" @click="setStroke(5)" :class="strokeWidth === 5 ? 'font-bold text-black underline' : ''">Bold (5px)</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ============================================== -->
                <!-- TYPE MODE: Cursive & Calligraphy Script Styles -->
                <!-- ============================================== -->
                <div x-show="mode === 'type'" class="space-y-3">
                    <!-- Typing Type Selector (Full Name vs Initials) -->
                    <div class="flex items-center space-x-2 text-xs">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Format:</span>
                        <button type="button"
                                @click="typeSignFormat = 'full'"
                                :class="typeSignFormat === 'full' ? 'bg-slate-700 text-white font-bold' : 'bg-slate-900 text-slate-400'"
                                class="px-2.5 py-1 rounded-lg border border-slate-700 transition text-[11px]">
                            Full Signature (<span x-text="name || 'Name'"></span>)
                        </button>
                        <button type="button"
                                @click="typeSignFormat = 'initials'"
                                :class="typeSignFormat === 'initials' ? 'bg-slate-700 text-white font-bold' : 'bg-slate-900 text-slate-400'"
                                class="px-2.5 py-1 rounded-lg border border-slate-700 transition text-[11px]">
                            Initials Mark (<span x-text="initials || 'MT'"></span>)
                        </button>
                    </div>

                    <!-- Font Style Option Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                        <!-- Option 1: Dancing Script -->
                        <div @click="font = 'dancing_script'"
                             :class="font === 'dancing_script' ? 'border-amber-500 bg-amber-500/10 ring-1 ring-amber-500' : 'border-slate-800 bg-slate-950/60 hover:border-slate-700'"
                             class="p-3 rounded-2xl border cursor-pointer transition flex flex-col justify-between space-y-1">
                            <div class="flex items-center justify-between text-[10px] text-slate-400 uppercase font-mono">
                                <span>Modern Script</span>
                                <span x-show="font === 'dancing_script'" class="text-amber-400 font-bold">● Active</span>
                            </div>
                            <div class="py-2 text-center text-2xl" :style="'color: ' + color + '; font-family: \'Dancing Script\', cursive;'">
                                <span x-text="typeSignFormat === 'full' ? (name || 'Official Signature') : (initials || 'MT')"></span>
                            </div>
                        </div>

                        <!-- Option 2: Caveat Freehand -->
                        <div @click="font = 'caveat'"
                             :class="font === 'caveat' ? 'border-amber-500 bg-amber-500/10 ring-1 ring-amber-500' : 'border-slate-800 bg-slate-950/60 hover:border-slate-700'"
                             class="p-3 rounded-2xl border cursor-pointer transition flex flex-col justify-between space-y-1">
                            <div class="flex items-center justify-between text-[10px] text-slate-400 uppercase font-mono">
                                <span>Handwritten Pen</span>
                                <span x-show="font === 'caveat'" class="text-amber-400 font-bold">● Active</span>
                            </div>
                            <div class="py-2 text-center text-2xl font-bold" :style="'color: ' + color + '; font-family: \'Caveat\', cursive;'">
                                <span x-text="typeSignFormat === 'full' ? (name || 'Official Signature') : (initials || 'MT')"></span>
                            </div>
                        </div>

                        <!-- Option 3: Great Vibes Calligraphy -->
                        <div @click="font = 'great_vibes'"
                             :class="font === 'great_vibes' ? 'border-amber-500 bg-amber-500/10 ring-1 ring-amber-500' : 'border-slate-800 bg-slate-950/60 hover:border-slate-700'"
                             class="p-3 rounded-2xl border cursor-pointer transition flex flex-col justify-between space-y-1">
                            <div class="flex items-center justify-between text-[10px] text-slate-400 uppercase font-mono">
                                <span>Formal Flourish</span>
                                <span x-show="font === 'great_vibes'" class="text-amber-400 font-bold">● Active</span>
                            </div>
                            <div class="py-2 text-center text-2xl font-normal" :style="'color: ' + color + '; font-family: \'Great Vibes\', cursive;'">
                                <span x-text="typeSignFormat === 'full' ? (name || 'Official Signature') : (initials || 'MT')"></span>
                            </div>
                        </div>

                        <!-- Option 4: Executive Serif Italic -->
                        <div @click="font = 'formal'"
                             :class="font === 'formal' ? 'border-amber-500 bg-amber-500/10 ring-1 ring-amber-500' : 'border-slate-800 bg-slate-950/60 hover:border-slate-700'"
                             class="p-3 rounded-2xl border cursor-pointer transition flex flex-col justify-between space-y-1">
                            <div class="flex items-center justify-between text-[10px] text-slate-400 uppercase font-mono">
                                <span>Executive Italic</span>
                                <span x-show="font === 'formal'" class="text-amber-400 font-bold">● Active</span>
                            </div>
                            <div class="py-2 text-center text-xl italic font-serif" :style="'color: ' + color + ';'">
                                <span x-text="typeSignFormat === 'full' ? (name || 'Official Signature') : (initials || 'MT')"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Certification Legal Confirmation & Actions -->
            <div class="pt-2 border-t border-slate-800 space-y-3 shrink-0">
                <label class="flex items-start space-x-2 text-[11px] text-slate-300 cursor-pointer select-none">
                    <input type="checkbox"
                           x-model="isCertified"
                           class="mt-0.5 rounded border-slate-700 bg-slate-900 text-amber-500 focus:ring-amber-400">
                    <span>
                        I certify that I am the official named above and hereby sign and attest to the accuracy of this official basketball scorebook.
                    </span>
                </label>

                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center space-x-2">
                        <button type="button"
                                wire:click="closeSignatureModal"
                                class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold text-xs transition">
                            Cancel
                        </button>
                        @if (!empty($signatures[$activeSignRole]['data']))
                            <button type="button"
                                    @click="deleteExisting()"
                                    class="px-3 py-2 rounded-xl bg-rose-950/60 hover:bg-rose-900 border border-rose-800/80 text-rose-300 font-semibold text-xs transition flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                <span>Remove Signature</span>
                            </button>
                        @endif
                    </div>

                    <button type="button"
                            @click="submitSignature()"
                            :disabled="!isCertified || isSubmitting"
                            :class="(!isCertified || isSubmitting) ? 'opacity-50 cursor-not-allowed bg-slate-700 text-slate-400' : 'bg-amber-500 hover:bg-amber-400 text-black font-black shadow-lg shadow-amber-500/20'"
                            class="px-5 py-2 rounded-xl text-xs flex items-center gap-2 transition font-bold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span x-text="isSubmitting ? 'Saving...' : 'Apply Signature to Scorebook'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
