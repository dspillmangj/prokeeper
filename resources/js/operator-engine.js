// Global singleton event listeners to prevent duplicate event bindings across Livewire renders
if (!window._prokeeperGlobalListenersAttached) {
    window._prokeeperGlobalListenersAttached = true;
    window._actionCooldownsGlobal = {};

    window.addEventListener('keydown', (e) => {
        if (window._activeOperator && typeof window._activeOperator.handleKeyDown === 'function') {
            window._activeOperator.handleKeyDown(e);
        }
    });

    window.addEventListener('online', () => {
        if (window._activeOperator && typeof window._activeOperator.flushQueue === 'function') {
            window._activeOperator.flushQueue();
        }
    });
}

window.ProKeeperEngine = {
    // Action definitions for fast client-side calculations
    basketballActions: {
        'X': { name: '2pt MAKE', points: 2, teamTarget: 'self', isScore: true, stat: 'fgm' },
        'Z': { name: '2pt Miss', points: 0, teamTarget: 'self', isScore: false, stat: 'fga' },
        'M': { name: '3pt MAKE', points: 3, teamTarget: 'self', isScore: true, stat: 'fg3m' },
        'N': { name: '3pt Miss', points: 0, teamTarget: 'self', isScore: false, stat: 'fg3a' },
        'B': { name: 'FT MAKE', points: 1, teamTarget: 'self', isScore: true, stat: 'ftm' },
        'V': { name: 'FT Miss', points: 0, teamTarget: 'self', isScore: false, stat: 'fta' },
        'D': { name: 'Def Reb', points: 0, teamTarget: 'self', isScore: false, stat: 'dreb' },
        'O': { name: 'Off Reb', points: 0, teamTarget: 'self', isScore: false, stat: 'oreb' },
        'A': { name: 'Assist', points: 0, teamTarget: 'self', isScore: false, stat: 'ast' },
        'S': { name: 'Steal', points: 0, teamTarget: 'self', isScore: false, stat: 'stl' },
        'K': { name: 'Block', points: 0, teamTarget: 'self', isScore: false, stat: 'blk' },
        'W': { name: 'Swat', points: 0, teamTarget: 'self', isScore: false, stat: 'swat' },
        'P': { name: 'Pass TO', points: 0, teamTarget: 'self', isScore: false, stat: 'to_pass' },
        'U': { name: 'Fumble TO', points: 0, teamTarget: 'self', isScore: false, stat: 'to_fumble' },
        'I': { name: 'Violation', points: 0, teamTarget: 'self', isScore: false, stat: 'to_violation' },
        'F': { name: 'Pers Foul', points: 0, teamTarget: 'self', isScore: false, isFoul: true, stat: 'fouls_pers' },
        'R': { name: 'Off Foul', points: 0, teamTarget: 'self', isScore: false, isFoul: true, stat: 'fouls_off' },
        'T': { name: 'Tech Foul', points: 0, teamTarget: 'self', isScore: false, isFoul: true, stat: 'fouls_tech' },
        'H': { name: 'Forced Foul', points: 0, teamTarget: 'self', isScore: false, isFoul: true, stat: 'fouls_forced' },
    },

    volleyballActions: {
        'K': { name: 'Kill', points: 1, teamTarget: 'self', isScore: true },
        'A': { name: 'Ace', points: 1, teamTarget: 'self', isScore: true },
        'B': { name: 'Block Solo', points: 1, teamTarget: 'self', isScore: true },
        'C': { name: 'Block Assist', points: 1, teamTarget: 'self', isScore: true },
        'E': { name: 'Attack Error', points: 1, teamTarget: 'opp', isScore: true },
        'S': { name: 'Service Error', points: 1, teamTarget: 'opp', isScore: true },
        'H': { name: 'Handling Error', points: 1, teamTarget: 'opp', isScore: true },
        'R': { name: 'Reception Error', points: 1, teamTarget: 'opp', isScore: true },
        'D': { name: 'Dig', points: 0, teamTarget: 'self', isScore: false },
        'Z': { name: 'Set Assist', points: 0, teamTarget: 'self', isScore: false },
        'T': { name: 'Attack Attempt', points: 0, teamTarget: 'self', isScore: false },
    },

    createOperator(sport, config) {
        return {
            sport,
            gameId: config.gameId,
            homeTeamName: config.homeTeamName || 'Home',
            awayTeamName: config.awayTeamName || 'Away',

            // Ultra-Fast 0ms Local Reactive State
            homeScore: config.homeScore || 0,
            awayScore: config.awayScore || 0,
            homePeriodScores: Array.isArray(config.homePeriodScores) ? [...config.homePeriodScores] : [0, 0, 0, 0],
            awayPeriodScores: Array.isArray(config.awayPeriodScores) ? [...config.awayPeriodScores] : [0, 0, 0, 0],
            currentPeriod: config.currentPeriod || 1,
            periodName: config.periodName || (sport === 'volleyball' ? 'Set 1' : '1st Quarter'),
            possession: config.possession || 'home',
            server: config.server || 'home',
            homeFouls: config.homeFouls || 0,
            awayFouls: config.awayFouls || 0,
            homeTimeouts: config.homeTimeouts ?? 5,
            awayTimeouts: config.awayTimeouts ?? 5,
            homeRotation: config.homeRotation || 1,
            awayRotation: config.awayRotation || 1,

            // Local Roster & Court Lineup Arrays
            homeCourt: JSON.parse(JSON.stringify(config.homeCourt || [])),
            awayCourt: JSON.parse(JSON.stringify(config.awayCourt || [])),
            homeBench: JSON.parse(JSON.stringify(config.homeBench || [])),
            awayBench: JSON.parse(JSON.stringify(config.awayBench || [])),

            // Local Play-by-Play and Undo Stack
            recentEvents: JSON.parse(JSON.stringify(config.recentEvents || [])),
            undoStack: [],

            // Modal & Selection State
            selectedPlayer: null,
            showLineupModal: false,
            showExecuteSubModal: false,
            showPlayByPlayModal: false,
            editingEvent: null,
            pbpFilterPeriod: 'all',
            pbpFilterTeam: 'all',
            _swipeTouchState: {},
            pendingSubs: { home: [], away: [] },
            selectedOutSubs: { home: [], away: [] },
            showSubSheet: false,
            subTeamSide: 'home',
            subOutJersey: '',
            subInJersey: '',
            jerseyBuffer: '',
            bufferTimeout: null,
            feedbackMessage: 'Ready',
            feedbackType: 'info',
            _actionCooldowns: {},

            openPlayByPlayModal() {
                if (!this.canExecute('openPlayByPlayModal', 150)) return;
                this.showPlayByPlayModal = true;
                this.selectedPlayer = null;
                this.showLineupModal = false;
                this.showExecuteSubModal = false;
                this.playSound('tap');
            },

            closePlayByPlayModal() {
                this.showPlayByPlayModal = false;
                this.editingEvent = null;
            },

            filteredRecentEvents() {
                return (this.recentEvents || []).filter(e => {
                    if (this.pbpFilterPeriod !== 'all' && Number(e.period) !== Number(this.pbpFilterPeriod)) return false;
                    if (this.pbpFilterTeam !== 'all' && e.team_side !== this.pbpFilterTeam) return false;
                    return true;
                });
            },

            deleteEventFast(eventId) {
                if (!this.canExecute('deleteEvent_' + eventId, 250)) return;
                const idx = this.recentEvents.findIndex(e => String(e.id) === String(eventId));
                if (idx === -1) return;

                const event = this.recentEvents[idx];
                this.pushUndoSnapshot('delete_event', { event });

                // Reverse local points if any
                const pts = Number(event.points) || 0;
                if (pts > 0) {
                    if (event.team_side === 'home') {
                        this.homeScore = Math.max(0, this.homeScore - pts);
                    } else {
                        this.awayScore = Math.max(0, this.awayScore - pts);
                    }
                    this.updateCurrentPeriodScore(event.team_side, -pts);
                }

                // Reverse local fouls if any
                if (['F', 'R', 'T'].includes(event.action_code)) {
                    if (event.team_side === 'home') {
                        this.homeFouls = Math.max(0, this.homeFouls - 1);
                    } else {
                        this.awayFouls = Math.max(0, this.awayFouls - 1);
                    }
                }

                // Remove from recentEvents array
                this.recentEvents.splice(idx, 1);
                this.playSound('tap');
                this.feedbackMessage = `Deleted: ${event.description || 'Play'}`;
                this.feedbackType = 'info';

                // Asynchronously sync to backend
                if (typeof event.id === 'number' || !String(event.id).startsWith('local_')) {
                    this.enqueueSync('deleteGameEvent', [Number(event.id)]);
                }
            },

            startEditingEvent(event) {
                this.editingEvent = {
                    id: event.id,
                    team_side: event.team_side,
                    jersey_number: String(event.jersey_number || ''),
                    player_name: event.player_name || '',
                    action_code: event.action_code || 'X',
                    period: Number(event.period || this.currentPeriod),
                };
                this.playSound('tap');
            },

            saveEditedEventFast() {
                if (!this.editingEvent) return;
                const eventId = this.editingEvent.id;
                const idx = this.recentEvents.findIndex(e => String(e.id) === String(eventId));
                if (idx === -1) return;

                const oldEvent = this.recentEvents[idx];
                const newJersey = this.editingEvent.jersey_number;
                const newActionCode = this.editingEvent.action_code;
                const newPeriod = Number(this.editingEvent.period);

                let actionDef;
                if (this.sport === 'basketball') {
                    actionDef = ProKeeperEngine.basketballActions[newActionCode] || { name: newActionCode, points: 0 };
                } else {
                    actionDef = ProKeeperEngine.volleyballActions[newActionCode] || { name: newActionCode, points: 0 };
                }

                const oldPts = Number(oldEvent.points) || 0;
                const newPts = Number(actionDef.points) || 0;
                const delta = newPts - oldPts;

                if (delta !== 0) {
                    if (oldEvent.team_side === 'home') {
                        this.homeScore = Math.max(0, this.homeScore + delta);
                    } else {
                        this.awayScore = Math.max(0, this.awayScore + delta);
                    }
                    this.updateCurrentPeriodScore(oldEvent.team_side, delta);
                }

                // Update local event object
                const newDesc = `${oldEvent.team_side.toUpperCase()} #${newJersey} ${oldEvent.player_name}: ${actionDef.name}`;
                this.recentEvents[idx] = {
                    ...oldEvent,
                    jersey_number: newJersey,
                    action_code: newActionCode,
                    action_name: actionDef.name,
                    points: newPts,
                    period: newPeriod,
                    description: newDesc,
                    home_score_after: this.homeScore,
                    away_score_after: this.awayScore,
                };

                this.playSound(delta > 0 ? 'score' : 'tap');
                this.feedbackMessage = `Updated: ${newDesc}`;
                this.feedbackType = 'success';
                this.editingEvent = null;

                if (typeof eventId === 'number' || !String(eventId).startsWith('local_')) {
                    this.enqueueSync('updateGameEvent', [Number(eventId), newJersey, newActionCode, newPeriod]);
                }
            },

            // Touch Swipe Handlers for fluid gestures
            onTouchStartPlay(e, eventId) {
                const touch = e.touches[0];
                this._swipeTouchState[eventId] = { startX: touch.clientX, currentX: touch.clientX, diffX: 0 };
            },

            onTouchMovePlay(e, eventId) {
                if (!this._swipeTouchState[eventId]) return;
                const touch = e.touches[0];
                const diff = touch.clientX - this._swipeTouchState[eventId].startX;
                if (diff < 0) {
                    this._swipeTouchState[eventId].diffX = diff;
                }
            },

            onTouchEndPlay(e, eventId) {
                if (!this._swipeTouchState[eventId]) return;
                const diff = this._swipeTouchState[eventId].diffX;
                delete this._swipeTouchState[eventId];
                if (diff < -80) {
                    this.deleteEventFast(eventId);
                }
            },

            hasPendingSubs() {
                return ((this.pendingSubs?.home?.length || 0) + (this.pendingSubs?.away?.length || 0)) > 0;
            },

            pendingSubCount() {
                return (this.pendingSubs?.home?.length || 0) + (this.pendingSubs?.away?.length || 0);
            },

            isPlayerPending(side, jersey) {
                const list = this.pendingSubs[side] || [];
                return list.some(p => String(p.jersey_number) === String(jersey));
            },

            togglePendingSub(side, player) {
                if (!this.pendingSubs[side]) this.pendingSubs[side] = [];
                const jersey = String(player.jersey_number);
                const idx = this.pendingSubs[side].findIndex(p => String(p.jersey_number) === jersey);
                if (idx >= 0) {
                    this.pendingSubs[side].splice(idx, 1);
                    this.playSound('tap');
                } else {
                    this.pendingSubs[side].push({
                        id: player.id,
                        jersey_number: jersey,
                        player_name: player.player_name,
                        position: player.position || ''
                    });
                    this.playSound('tap');
                }
            },

            openLineupModal() {
                if (!this.canExecute('openLineupModal', 150)) return;
                this.showLineupModal = true;
                this.showExecuteSubModal = false;
                this.selectedPlayer = null;
                this.playSound('tap');
            },

            openExecuteSubModal() {
                if (!this.canExecute('openExecuteSubModal', 150)) return;
                this.showLineupModal = false;
                this.showExecuteSubModal = true;
                this.selectedPlayer = null;
                this.selectedOutSubs = { home: [], away: [] };
                this.playSound('tap');
            },

            isPlayerSelectedOut(side, jersey) {
                const list = this.selectedOutSubs[side] || [];
                return list.includes(String(jersey));
            },

            toggleSelectedOut(side, jersey) {
                if (!this.selectedOutSubs[side]) this.selectedOutSubs[side] = [];
                const jStr = String(jersey);
                const list = this.selectedOutSubs[side];
                const idx = list.indexOf(jStr);
                const maxAllowed = (this.pendingSubs[side] || []).length;

                if (idx >= 0) {
                    list.splice(idx, 1);
                    this.playSound('tap');
                } else {
                    if (maxAllowed === 0) return;
                    if (list.length >= maxAllowed) {
                        list.shift(); // Cycle out oldest selection when at quota
                    }
                    list.push(jStr);
                    this.playSound('tap');
                }
            },

            canExecutePendingSubs() {
                const homeIn = this.pendingSubs.home || [];
                const awayIn = this.pendingSubs.away || [];
                if (homeIn.length === 0 && awayIn.length === 0) return false;

                const homeOut = this.selectedOutSubs.home || [];
                const awayOut = this.selectedOutSubs.away || [];

                if (homeIn.length > 0 && homeOut.length !== homeIn.length) return false;
                if (awayIn.length > 0 && awayOut.length !== awayIn.length) return false;

                return true;
            },

            executeAllPendingSubs() {
                if (!this.canExecutePendingSubs()) {
                    this.feedbackMessage = 'Please select the required number of outgoing players.';
                    this.feedbackType = 'error';
                    return;
                }
                if (!this.canExecute('executeAllPendingSubs', 350)) return;

                const executedPairs = [];

                ['home', 'away'].forEach(side => {
                    const inList = this.pendingSubs[side] || [];
                    const outList = this.selectedOutSubs[side] || [];
                    const courtList = side === 'home' ? this.homeCourt : this.awayCourt;
                    const benchList = side === 'home' ? this.homeBench : this.awayBench;

                    for (let i = 0; i < inList.length; i++) {
                        const inPlayer = inList[i];
                        const inJ = String(inPlayer.jersey_number);
                        const outJ = String(outList[i]);

                        const courtIdx = courtList.findIndex(p => String(p.jersey_number) === outJ);
                        const benchIdx = benchList.findIndex(p => String(p.jersey_number) === inJ);

                        if (courtIdx !== -1 && benchIdx !== -1) {
                            const courtP = courtList[courtIdx];
                            const benchP = benchList[benchIdx];

                            courtList[courtIdx] = { ...benchP, is_on_court: true };
                            benchList[benchIdx] = { ...courtP, is_on_court: false };

                            executedPairs.push({ side, outJ, inJ, outName: courtP.player_name, inName: benchP.player_name });
                            this.enqueueSync('setAndExecuteSub', [side, outJ, inJ]);
                        }
                    }
                });

                if (executedPairs.length > 0) {
                    this.pushUndoSnapshot('bulk_subs', { executedPairs });
                    this.feedbackMessage = `${executedPairs.length} substitution(s) entered the game`;
                    this.feedbackType = 'success';
                    this.playSound('score');
                }

                // Reset queue
                this.pendingSubs = { home: [], away: [] };
                this.selectedOutSubs = { home: [], away: [] };
                this.showExecuteSubModal = false;
                this.showLineupModal = false;
            },

            clearPendingSubs() {
                this.pendingSubs = { home: [], away: [] };
                this.selectedOutSubs = { home: [], away: [] };
                this.playSound('tap');
            },

            canExecute(actionKey, cooldownMs = 350) {
                const now = Date.now();
                if (!window._actionCooldownsGlobal) window._actionCooldownsGlobal = {};
                const last = window._actionCooldownsGlobal[actionKey] || 0;
                if (now - last < cooldownMs) {
                    return false;
                }
                window._actionCooldownsGlobal[actionKey] = now;
                return true;
            },

            // Background Async Sync Queue
            syncQueue: [],
            syncStatus: 'synced', // 'synced' | 'syncing' | 'offline'
            isSyncing: false,

            init() {
                window._activeOperator = this;
                this.loadQueue();

                if (window._operatorSyncInterval) clearInterval(window._operatorSyncInterval);
                window._operatorSyncInterval = setInterval(() => {
                    if (this.syncQueue.length > 0 && !this.isSyncing) {
                        this.flushQueue();
                    }
                }, 3000);
            },

            destroy() {
                if (window._activeOperator === this) {
                    window._activeOperator = null;
                }
                if (window._operatorSyncInterval) {
                    clearInterval(window._operatorSyncInterval);
                }
            },

            // Universal instant escape: exits any modal, sheet, or overlay and returns directly to the court view
            escapeToMainDashboard() {
                if (document.activeElement && typeof document.activeElement.blur === 'function') {
                    document.activeElement.blur();
                }
                this.selectedPlayer = null;
                this.showLineupModal = false;
                this.showExecuteSubModal = false;
                this.showPlayByPlayModal = false;
                this.editingEvent = null;
                this.showSubSheet = false;
                this.selectedOutSubs = { home: [], away: [] };
                this.jerseyBuffer = '';
                if (this.bufferTimeout) {
                    clearTimeout(this.bufferTimeout);
                    this.bufferTimeout = null;
                }

                // If Livewire Roster Modal is open, close it instantly
                try {
                    const wireEl = document.querySelector('[wire\\:id]');
                    if (wireEl && window.Livewire) {
                        const comp = window.Livewire.find(wireEl.getAttribute('wire:id'));
                        if (comp && typeof comp.call === 'function') {
                            comp.call('closeRosterModal');
                        }
                    }
                } catch (e) {}

                // Broadcast to any layout modals (such as operator help modal)
                window.dispatchEvent(new CustomEvent('close-all-modals'));
                this.playSound('tap');
            },

            handleKeyDown(e) {
                // Prevent OS key-repeat auto-firing when holding keys down
                if (e.repeat) return;

                // ESCAPE KEY: Universally escape from any state/modal back to main dashboard instantly
                if (e.key === 'Escape' || e.code === 'Escape') {
                    e.preventDefault();
                    this.escapeToMainDashboard();
                    return;
                }

                if (['INPUT', 'SELECT', 'TEXTAREA'].includes(document.activeElement?.tagName)) {
                    return;
                }

                // Blur any focused button on hotkey triggers to prevent browser duplicate Space/Enter click
                if (document.activeElement?.tagName === 'BUTTON') {
                    document.activeElement.blur();
                }

                // 1. INLINE PLAY EDIT MODAL SHORTCUTS
                if (this.editingEvent) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        this.saveEditedEventFast();
                        return;
                    }
                    if (e.key === 'Escape') {
                        e.preventDefault();
                        this.editingEvent = null;
                        this.playSound('tap');
                        return;
                    }
                }

                // 2. PLAY-BY-PLAY VIEWER MODAL SHORTCUTS
                if (this.showPlayByPlayModal) {
                    const k = e.key.toUpperCase();
                    if (k === 'L' || e.key === 'Escape') {
                        e.preventDefault();
                        this.closePlayByPlayModal();
                        return;
                    }
                    if (k === '1' || k === 'A' && e.altKey) {
                        e.preventDefault();
                        this.pbpFilterTeam = 'all';
                        this.playSound('tap');
                        return;
                    }
                    if (k === '2' || k === 'H') {
                        e.preventDefault();
                        this.pbpFilterTeam = 'home';
                        this.playSound('tap');
                        return;
                    }
                    if (k === '3' || k === 'V' || k === 'W') {
                        e.preventDefault();
                        this.pbpFilterTeam = 'away';
                        this.playSound('tap');
                        return;
                    }
                    if (k === 'U') {
                        e.preventDefault();
                        this.undo();
                        return;
                    }
                }

                // 3. EXECUTE SUBSTITUTIONS (ASSIGN OUTGOING) MODAL SHORTCUTS
                if (this.showExecuteSubModal) {
                    const k = e.key.toUpperCase();
                    if (e.key === 'Enter' || k === 'C') {
                        e.preventDefault();
                        if (this.canExecutePendingSubs()) {
                            this.executeAllPendingSubs();
                        } else {
                            this.feedbackMessage = 'Select required number of OUT players first';
                            this.feedbackType = 'error';
                        }
                        return;
                    }
                    if (k === 'B' || e.key === 'Backspace') {
                        e.preventDefault();
                        this.openLineupModal();
                        return;
                    }
                    if (e.key === 'Escape') {
                        e.preventDefault();
                        this.showExecuteSubModal = false;
                        this.playSound('tap');
                        return;
                    }

                    // Number typing inside Execute Sub Modal toggles on-court player as OUT
                    if (e.key >= '0' && e.key <= '9') {
                        e.preventDefault();
                        this.jerseyBuffer += e.key;
                        clearTimeout(this.bufferTimeout);
                        this.feedbackMessage = `Select OUT #${this.jerseyBuffer} — Press [-] for HOME or [=] for AWAY`;
                        this.feedbackType = 'info';

                        this.bufferTimeout = setTimeout(() => {
                            if (this.jerseyBuffer) {
                                this.jerseyBuffer = '';
                                this.feedbackMessage = '';
                            }
                        }, 2500);
                        return;
                    }

                    const isHomeKey = e.key === '-' || e.key === '_' || e.code === 'Minus' || e.code === 'NumpadSubtract';
                    const isAwayKey = e.key === '=' || e.key === '+' || e.code === 'Equal' || e.code === 'NumpadAdd';

                    if (isHomeKey && this.jerseyBuffer) {
                        e.preventDefault();
                        clearTimeout(this.bufferTimeout);
                        const searchNum = this.jerseyBuffer;
                        this.jerseyBuffer = '';

                        const matchedHome = this.homeCourt.find(p => String(p.jersey_number) === searchNum);
                        if (matchedHome) {
                            this.toggleSelectedOut('home', matchedHome.jersey_number);
                        } else {
                            this.feedbackMessage = `HOME player #${searchNum} is not on the court`;
                            this.feedbackType = 'error';
                        }
                        return;
                    }

                    if (isAwayKey && this.jerseyBuffer) {
                        e.preventDefault();
                        clearTimeout(this.bufferTimeout);
                        const searchNum = this.jerseyBuffer;
                        this.jerseyBuffer = '';

                        const matchedAway = this.awayCourt.find(p => String(p.jersey_number) === searchNum);
                        if (matchedAway) {
                            this.toggleSelectedOut('away', matchedAway.jersey_number);
                        } else {
                            this.feedbackMessage = `AWAY player #${searchNum} is not on the court`;
                            this.feedbackType = 'error';
                        }
                        return;
                    }

                    if (e.code === 'Space' || e.key === ' ') {
                        e.preventDefault();
                        if (this.jerseyBuffer) {
                            this.feedbackMessage = `OUT #${this.jerseyBuffer} — Press [-] for HOME or [=] for AWAY`;
                            this.feedbackType = 'info';
                        }
                        return;
                    }
                }

                // 4. LINEUP & CHECK-IN TABLE MODAL SHORTCUTS
                if (this.showLineupModal) {
                    const k = e.key.toUpperCase();
                    if (e.key === 'Enter' || k === 'X') {
                        e.preventDefault();
                        if (this.pendingSubCount() > 0) {
                            this.openExecuteSubModal();
                        } else {
                            this.feedbackMessage = 'Line up at least 1 bench sub first';
                            this.feedbackType = 'info';
                        }
                        return;
                    }
                    if (k === 'C' || e.key === 'Backspace') {
                        e.preventDefault();
                        this.clearPendingSubs();
                        return;
                    }
                    if (e.key === 'Escape') {
                        e.preventDefault();
                        this.showLineupModal = false;
                        this.playSound('tap');
                        return;
                    }

                    // Number typing inside Lineup Modal toggles bench player into pending subs
                    if (e.key >= '0' && e.key <= '9') {
                        e.preventDefault();
                        this.jerseyBuffer += e.key;
                        clearTimeout(this.bufferTimeout);
                        this.feedbackMessage = `Line up #${this.jerseyBuffer} — Press [-] for HOME or [=] for AWAY`;
                        this.feedbackType = 'info';

                        this.bufferTimeout = setTimeout(() => {
                            if (this.jerseyBuffer) {
                                this.jerseyBuffer = '';
                                this.feedbackMessage = '';
                            }
                        }, 2500);
                        return;
                    }

                    const isHomeKey = e.key === '-' || e.key === '_' || e.code === 'Minus' || e.code === 'NumpadSubtract';
                    const isAwayKey = e.key === '=' || e.key === '+' || e.code === 'Equal' || e.code === 'NumpadAdd';

                    if (isHomeKey && this.jerseyBuffer) {
                        e.preventDefault();
                        clearTimeout(this.bufferTimeout);
                        const searchNum = this.jerseyBuffer;
                        this.jerseyBuffer = '';

                        const matchedHome = this.homeBench.find(p => String(p.jersey_number) === searchNum);
                        if (matchedHome) {
                            this.togglePendingSub('home', matchedHome);
                        } else {
                            this.feedbackMessage = `HOME player #${searchNum} is not on the bench`;
                            this.feedbackType = 'error';
                        }
                        return;
                    }

                    if (isAwayKey && this.jerseyBuffer) {
                        e.preventDefault();
                        clearTimeout(this.bufferTimeout);
                        const searchNum = this.jerseyBuffer;
                        this.jerseyBuffer = '';

                        const matchedAway = this.awayBench.find(p => String(p.jersey_number) === searchNum);
                        if (matchedAway) {
                            this.togglePendingSub('away', matchedAway);
                        } else {
                            this.feedbackMessage = `AWAY player #${searchNum} is not on the bench`;
                            this.feedbackType = 'error';
                        }
                        return;
                    }

                    if (e.code === 'Space' || e.key === ' ') {
                        e.preventDefault();
                        if (this.jerseyBuffer) {
                            this.feedbackMessage = `Line up #${this.jerseyBuffer} — Press [-] for HOME or [=] for AWAY`;
                            this.feedbackType = 'info';
                        }
                        return;
                    }
                }

                // 5. ACTION PAD MODAL SHORTCUTS (Target Player Selected)
                if (this.selectedPlayer) {
                    const k = e.key.toUpperCase();
                    let actionMap = {};

                    if (this.sport === 'basketball') {
                        actionMap = {
                            'X': 'X',
                            'M': 'M',
                            'B': 'B',
                            'Z': 'Z',
                            'N': 'N',
                            'V': 'V',
                            'D': 'D',
                            'O': 'O',
                            'A': 'A',
                            'S': 'S',
                            'K': 'K',
                            'P': 'P',
                            'F': 'F',
                        };
                    } else {
                        actionMap = {
                            'K': 'K',
                            'A': 'A',
                            'D': 'D',
                            'B': 'B',
                            'C': 'C',
                            'E': 'E',
                            'S': 'S',
                            'H': 'H',
                            'R': 'R',
                            'Z': 'Z',
                            'T': 'T',
                        };
                    }

                    if (actionMap[k]) {
                        e.preventDefault();
                        this.executeAction(actionMap[k]);
                        return;
                    }

                    if (e.key === 'Escape') {
                        e.preventDefault();
                        this.selectedPlayer = null;
                        this.playSound('tap');
                        return;
                    }

                    if (k === 'W' || k === 'S' || e.key === 'Tab') {
                        e.preventDefault();
                        this.openLineupModal();
                        return;
                    }
                }

                // 6. MAIN DASHBOARD NUMBER ENTRY (Buffer jersey # -> Press [-] for HOME or [=] for AWAY)
                if (e.key >= '0' && e.key <= '9') {
                    e.preventDefault();
                    this.jerseyBuffer += e.key;
                    clearTimeout(this.bufferTimeout);
                    this.feedbackMessage = `Jersey #${this.jerseyBuffer} — Press [-] for HOME or [=] for AWAY`;
                    this.feedbackType = 'info';

                    this.bufferTimeout = setTimeout(() => {
                        if (this.jerseyBuffer) {
                            this.jerseyBuffer = '';
                            this.feedbackMessage = 'Jersey entry cancelled (timeout)';
                        }
                    }, 2500);
                    return;
                }

                const isHomeKey = e.key === '-' || e.key === '_' || e.code === 'Minus' || e.code === 'NumpadSubtract';
                const isAwayKey = e.key === '=' || e.key === '+' || e.code === 'Equal' || e.code === 'NumpadAdd';

                // Press [-] with jersey buffer -> Open Action Pad for Home Player
                if (isHomeKey && this.jerseyBuffer) {
                    e.preventDefault();
                    clearTimeout(this.bufferTimeout);
                    const searchNum = this.jerseyBuffer;
                    this.jerseyBuffer = '';

                    const matchedHome = this.homeCourt.find(p => String(p.jersey_number) === searchNum);
                    if (matchedHome) {
                        this.openActionPad('home', matchedHome.jersey_number, matchedHome.player_name, matchedHome.id);
                    } else {
                        const benchHome = this.homeBench.find(p => String(p.jersey_number) === searchNum);
                        if (benchHome) {
                            this.feedbackMessage = `HOME #${searchNum} (${benchHome.player_name}) is on the bench (Press [S] for Subs)`;
                            this.feedbackType = 'info';
                        } else {
                            this.feedbackMessage = `No HOME player found with Jersey #${searchNum}`;
                            this.feedbackType = 'error';
                        }
                    }
                    return;
                }

                // Press [=] with jersey buffer -> Open Action Pad for Away Player
                if (isAwayKey && this.jerseyBuffer) {
                    e.preventDefault();
                    clearTimeout(this.bufferTimeout);
                    const searchNum = this.jerseyBuffer;
                    this.jerseyBuffer = '';

                    const matchedAway = this.awayCourt.find(p => String(p.jersey_number) === searchNum);
                    if (matchedAway) {
                        this.openActionPad('away', matchedAway.jersey_number, matchedAway.player_name, matchedAway.id);
                    } else {
                        const benchAway = this.awayBench.find(p => String(p.jersey_number) === searchNum);
                        if (benchAway) {
                            this.feedbackMessage = `AWAY #${searchNum} (${benchAway.player_name}) is on the bench (Press [S] for Subs)`;
                            this.feedbackType = 'info';
                        } else {
                            this.feedbackMessage = `No AWAY player found with Jersey #${searchNum}`;
                            this.feedbackType = 'error';
                        }
                    }
                    return;
                }

                // Spacebar: Toggle server/possession, or prompt if jerseyBuffer is typed
                if (e.code === 'Space' || e.key === ' ') {
                    e.preventDefault();
                    if (this.jerseyBuffer) {
                        this.feedbackMessage = `Jersey #${this.jerseyBuffer} — Press [-] for HOME or [=] for AWAY`;
                        this.feedbackType = 'info';
                        return;
                    }

                    // Default Space action if no jersey was typed
                    if (this.sport === 'basketball') {
                        this.togglePossession();
                    } else {
                        this.toggleServer();
                    }
                    return;
                }

                // Hotkey Undo: U or Ctrl/Cmd + Z
                if ((e.key.toLowerCase() === 'u' && !e.metaKey && !e.ctrlKey) || (e.key.toLowerCase() === 'z' && (e.metaKey || e.ctrlKey))) {
                    e.preventDefault();
                    this.undo();
                    return;
                }

                // 7. DIRECT HOTKEYS FOR MAIN DASHBOARD DOCK & SCORE CORRECTIONS
                if (!this.selectedPlayer && !this.jerseyBuffer && !this.showLineupModal && !this.showExecuteSubModal && !this.showPlayByPlayModal) {
                    const k = e.key.toUpperCase();
                    
                    // Home Timeout
                    if (k === 'H') {
                        e.preventDefault();
                        this.callTimeoutFast('home');
                        return;
                    }
                    // Away Timeout
                    if (k === 'A') {
                        e.preventDefault();
                        this.callTimeoutFast('away');
                        return;
                    }
                    // Advance Period / Set
                    if (k === 'N') {
                        e.preventDefault();
                        this.nextPeriodFast();
                        return;
                    }
                    // Quick Substitution / Line Up
                    if (k === 'S') {
                        e.preventDefault();
                        this.openLineupModal();
                        return;
                    }
                    // Open Rosters Modal
                    if (k === 'R') {
                        e.preventDefault();
                        try {
                            const wireEl = document.querySelector('[wire\\:id]');
                            if (wireEl && window.Livewire) {
                                window.Livewire.find(wireEl.getAttribute('wire:id'))?.call('openRosterModal', 'home');
                                this.playSound('tap');
                            }
                        } catch (err) {}
                        return;
                    }
                    // Basketball Possession
                    if (this.sport === 'basketball' && k === 'P') {
                        e.preventDefault();
                        this.togglePossession();
                        return;
                    }
                    // Volleyball Server
                    if (this.sport === 'volleyball' && k === 'V') {
                        e.preventDefault();
                        this.toggleServer();
                        return;
                    }
                    // Volleyball Rotate Home
                    if (this.sport === 'volleyball' && k === 'W') {
                        e.preventDefault();
                        this.rotateTeamFast('home');
                        return;
                    }
                    // Volleyball Rotate Away
                    if (this.sport === 'volleyball' && k === 'E') {
                        e.preventDefault();
                        this.rotateTeamFast('away');
                        return;
                    }

                    // Open Play-by-Play Log
                    if (k === 'L') {
                        e.preventDefault();
                        this.openPlayByPlayModal();
                        return;
                    }

                    // Score Adjustments: '[' = Home +1, '{' = Home -1, ']' = Away +1, '}' = Away -1
                    if (e.key === '[') {
                        e.preventDefault();
                        this.adjustScoreFast('home', 1);
                        return;
                    }
                    if (e.key === '{') {
                        e.preventDefault();
                        this.adjustScoreFast('home', -1);
                        return;
                    }
                    if (e.key === ']') {
                        e.preventDefault();
                        this.adjustScoreFast('away', 1);
                        return;
                    }
                    if (e.key === '}') {
                        e.preventDefault();
                        this.adjustScoreFast('away', -1);
                        return;
                    }
                }

                if (e.key === 'Escape') {
                    this.selectedPlayer = null;
                    this.showLineupModal = false;
                    this.showExecuteSubModal = false;
                    this.showPlayByPlayModal = false;
                    this.editingEvent = null;
                    this.showSubSheet = false;
                    this.jerseyBuffer = '';
                }
            },

            openActionPad(side, jersey, name, id) {
                if (!this.canExecute('openActionPad', 150)) return;
                this.selectedPlayer = { side, jersey, name, id };
                this.playSound('tap');
            },

            // 0ms Latency Stat Execution Engine
            executeAction(actionCode) {
                if (!this.selectedPlayer) return;
                if (!this.canExecute('executeAction', 200)) return;

                const side = this.selectedPlayer.side;
                const jersey = this.selectedPlayer.jersey;
                const name = this.selectedPlayer.name;

                // Save snapshot for 0ms local undo
                this.pushUndoSnapshot('stat', { side, jersey, actionCode });

                let actionDef;
                let playSoundType = 'tap';

                if (this.sport === 'basketball') {
                    actionDef = ProKeeperEngine.basketballActions[actionCode] || { name: actionCode, points: 0 };
                    const pts = actionDef.points || 0;

                    if (pts > 0) {
                        if (side === 'home') this.homeScore += pts;
                        else this.awayScore += pts;
                        this.updateCurrentPeriodScore(side, pts);
                        playSoundType = 'score';
                    }

                    if (actionDef.isFoul) {
                        if (side === 'home') this.homeFouls += 1;
                        else this.awayFouls += 1;
                    }
                } else {
                    actionDef = ProKeeperEngine.volleyballActions[actionCode] || { name: actionCode, points: 0 };
                    if (actionDef.points > 0) {
                        if (actionDef.teamTarget === 'self') {
                            if (side === 'home') this.homeScore += 1;
                            else this.awayScore += 1;
                            this.updateCurrentPeriodScore(side, 1);
                        } else {
                            // Point goes to opposing team on error
                            if (side === 'home') this.awayScore += 1;
                            else this.homeScore += 1;
                            this.updateCurrentPeriodScore(side === 'home' ? 'away' : 'home', 1);
                        }
                        playSoundType = 'score';
                    }
                }

                // Add instant play entry to local recent events list
                const eventDesc = `${side.toUpperCase()} #${jersey} ${name}: ${actionDef.name}`;
                this.recentEvents.unshift({
                    id: 'local_' + Date.now() + '_' + Math.random().toString(36).substr(2, 4),
                    sequence: (this.recentEvents[0]?.sequence || 0) + 1,
                    period: this.currentPeriod,
                    team_side: side,
                    jersey_number: jersey,
                    player_name: name,
                    action_code: actionCode,
                    action_name: actionDef.name,
                    description: eventDesc,
                    home_score_after: this.homeScore,
                    away_score_after: this.awayScore,
                });

                this.feedbackMessage = eventDesc;
                this.feedbackType = 'success';
                this.playSound(playSoundType);

                // Close selection overlay instantly
                this.selectedPlayer = null;

                // Queue mutation for non-blocking asynchronous sync
                this.enqueueSync('recordQuickStat', [side, jersey, actionCode]);
            },

            // 0ms Direct Score Adjustment (+1, +2, +3, -1)
            adjustScoreFast(side, delta) {
                if (!this.canExecute('score_' + side + '_' + delta, 180)) return;

                this.pushUndoSnapshot('score', { side, delta });

                if (side === 'home') {
                    this.homeScore = Math.max(0, this.homeScore + delta);
                } else {
                    this.awayScore = Math.max(0, this.awayScore + delta);
                }
                this.updateCurrentPeriodScore(side, delta);

                this.playSound(delta > 0 ? 'score' : 'tap');
                this.feedbackMessage = `${side.toUpperCase()} Score ${delta > 0 ? '+' : ''}${delta}`;
                this.feedbackType = 'info';

                this.enqueueSync('adjustScore', [side, delta]);
            },

            updateCurrentPeriodScore(side, delta) {
                const pIdx = Math.max(0, this.currentPeriod - 1);
                if (side === 'home') {
                    while (this.homePeriodScores.length <= pIdx) this.homePeriodScores.push(0);
                    this.homePeriodScores[pIdx] = Math.max(0, this.homePeriodScores[pIdx] + delta);
                } else {
                    while (this.awayPeriodScores.length <= pIdx) this.awayPeriodScores.push(0);
                    this.awayPeriodScores[pIdx] = Math.max(0, this.awayPeriodScores[pIdx] + delta);
                }
            },

            // 0ms Instant Substitution
            openSubSheet(side, jersey = '') {
                if (!this.canExecute('openSubSheet', 200)) return;
                this.subTeamSide = side;
                this.subOutJersey = String(jersey || '');
                this.subInJersey = '';
                this.showSubSheet = true;
                this.selectedPlayer = null;
                this.playSound('tap');
            },

            confirmSubFast() {
                if (!this.subOutJersey || !this.subInJersey) return;
                if (!this.canExecute('confirmSubFast', 350)) return;

                const side = this.subTeamSide;
                const outJ = String(this.subOutJersey);
                const inJ = String(this.subInJersey);

                this.pushUndoSnapshot('sub', { side, outJ, inJ });

                // Swap court and bench arrays locally with zero latency
                const courtList = side === 'home' ? this.homeCourt : this.awayCourt;
                const benchList = side === 'home' ? this.homeBench : this.awayBench;

                const courtIdx = courtList.findIndex(p => String(p.jersey_number) === outJ);
                const benchIdx = benchList.findIndex(p => String(p.jersey_number) === inJ);

                if (courtIdx !== -1 && benchIdx !== -1) {
                    const outPlayer = courtList[courtIdx];
                    const inPlayer = benchList[benchIdx];

                    // Swap
                    courtList[courtIdx] = { ...inPlayer, is_on_court: true };
                    benchList[benchIdx] = { ...outPlayer, is_on_court: false };
                }

                this.playSound('tap');
                this.feedbackMessage = `Subbed OUT #${outJ}, IN #${inJ} (${side.toUpperCase()})`;
                this.feedbackType = 'success';

                this.showSubSheet = false;
                this.subOutJersey = '';
                this.subInJersey = '';

                this.enqueueSync('setAndExecuteSub', [side, outJ, inJ]);
            },

            // 0ms Volleyball Rotation (P1-P6 order shift)
            rotateTeamFast(side) {
                if (!this.canExecute('rotate_' + side, 300)) return;

                this.pushUndoSnapshot('rotate', { side });

                const court = side === 'home' ? this.homeCourt : this.awayCourt;
                if (court && court.length === 6) {
                    // Standard clockwise volleyball rotation: position 1 -> 6, 2 -> 1, etc.
                    const first = court.shift();
                    court.push(first);
                }

                if (side === 'home') {
                    this.homeRotation = (this.homeRotation % 6) + 1;
                } else {
                    this.awayRotation = (this.awayRotation % 6) + 1;
                }

                this.playSound('tap');
                this.feedbackMessage = `Rotated ${side.toUpperCase()} to P${side === 'home' ? this.homeRotation : this.awayRotation}`;
                this.feedbackType = 'info';

                this.enqueueSync('rotateTeam', [side]);
            },

            // 0ms Toggle Possession Arrow
            togglePossession() {
                if (!this.canExecute('togglePossession', 300)) return;

                this.pushUndoSnapshot('possession', { prev: this.possession });
                this.possession = (this.possession === 'home') ? 'away' : 'home';
                this.playSound('tap');
                this.feedbackMessage = `Possession: ${this.possession.toUpperCase()}`;
                this.enqueueSync('togglePossession', []);
            },

            // 0ms Toggle Volleyball Server
            toggleServer() {
                if (!this.canExecute('toggleServer', 300)) return;

                this.pushUndoSnapshot('server', { prev: this.server });
                this.server = (this.server === 'home') ? 'away' : 'home';
                this.playSound('tap');
                this.feedbackMessage = `Server: ${this.server.toUpperCase()}`;
                this.enqueueSync('toggleServer', []);
            },

            // 0ms Timeout Charge
            callTimeoutFast(side) {
                if (!this.canExecute('timeout_' + side, 400)) return;

                this.pushUndoSnapshot('timeout', { side });
                if (side === 'home' && this.homeTimeouts > 0) {
                    this.homeTimeouts -= 1;
                } else if (side === 'away' && this.awayTimeouts > 0) {
                    this.awayTimeouts -= 1;
                }
                this.playSound('tap');
                this.feedbackMessage = `Timeout charged to ${side.toUpperCase()}`;
                this.enqueueSync('callTimeout', [side]);
            },

            // 0ms Advance Period / Set
            nextPeriodFast() {
                if (this.sport === 'basketball' && this.currentPeriod >= 6) {
                    this.feedbackMessage = 'Maximum of 6 periods reached (2nd Overtime).';
                    this.feedbackType = 'error';
                    return;
                }
                if (!this.canExecute('nextPeriod', 600)) return;

                this.pushUndoSnapshot('period', {
                    currentPeriod: this.currentPeriod,
                    homeFouls: this.homeFouls,
                    awayFouls: this.awayFouls,
                });

                this.currentPeriod += 1;
                this.periodName = this.sport === 'volleyball' 
                    ? `Set ${this.currentPeriod}` 
                    : (this.currentPeriod <= 4 ? `Q${this.currentPeriod}` : `OT${this.currentPeriod - 4}`);
                this.homeFouls = 0;
                this.awayFouls = 0;

                this.playSound('tap');
                this.feedbackMessage = `Advanced to ${this.periodName}`;
                this.enqueueSync(this.sport === 'volleyball' ? 'nextSet' : 'nextPeriod', []);
            },

            // 0ms Switch Period / Set manually
            setPeriodFast(period) {
                const target = Number(period);
                if (!target || target < 1 || target === this.currentPeriod) return;
                if (this.sport === 'basketball' && target > 6) {
                    this.feedbackMessage = 'Basketball is limited to 6 periods (Q1-Q4, OT1, OT2).';
                    this.feedbackType = 'error';
                    return;
                }
                if (!this.canExecute('setPeriodFast', 200)) return;

                this.pushUndoSnapshot('period', {
                    currentPeriod: this.currentPeriod,
                    homeFouls: this.homeFouls,
                    awayFouls: this.awayFouls,
                });

                this.currentPeriod = target;
                this.periodName = this.sport === 'volleyball' 
                    ? `Set ${this.currentPeriod}` 
                    : (this.currentPeriod <= 4 ? `Q${this.currentPeriod}` : `OT${this.currentPeriod - 4}`);

                const maxLen = this.sport === 'basketball' ? Math.min(6, target) : target;
                while (this.homePeriodScores.length < maxLen) this.homePeriodScores.push(0);
                while (this.awayPeriodScores.length < maxLen) this.awayPeriodScores.push(0);

                this.homeFouls = 0;
                this.awayFouls = 0;

                this.playSound('tap');
                this.feedbackMessage = `Switched to ${this.periodName}`;
                this.feedbackType = 'info';

                this.enqueueSync(this.sport === 'volleyball' ? 'setSet' : 'setPeriod', [target]);
            },

            // 0ms Local Undo Execution
            undo() {
                if (!this.canExecute('undo', 250)) return;

                if (this.undoStack.length === 0 && this.recentEvents.length === 0) {
                    this.feedbackMessage = 'No plays to undo.';
                    this.feedbackType = 'info';
                    return;
                }

                const snapshot = this.undoStack.pop();
                if (snapshot) {
                    this.restoreSnapshot(snapshot);
                }

                // Remove top play from recent events
                if (this.recentEvents.length > 0) {
                    const undone = this.recentEvents.shift();
                    this.feedbackMessage = `Reverted: ${undone.description || 'Last play'}`;
                } else {
                    this.feedbackMessage = 'Last action reverted';
                }

                this.feedbackType = 'info';
                this.playSound('tap');

                // Queue undo on server
                this.enqueueSync('undo', []);
            },

            pushUndoSnapshot(type, meta) {
                this.undoStack.push({
                    type,
                    meta,
                    homeScore: this.homeScore,
                    awayScore: this.awayScore,
                    homePeriodScores: [...this.homePeriodScores],
                    awayPeriodScores: [...this.awayPeriodScores],
                    homeFouls: this.homeFouls,
                    awayFouls: this.awayFouls,
                    homeTimeouts: this.homeTimeouts,
                    awayTimeouts: this.awayTimeouts,
                    possession: this.possession,
                    server: this.server,
                    homeRotation: this.homeRotation,
                    awayRotation: this.awayRotation,
                    homeCourt: JSON.parse(JSON.stringify(this.homeCourt)),
                    awayCourt: JSON.parse(JSON.stringify(this.awayCourt)),
                    homeBench: JSON.parse(JSON.stringify(this.homeBench)),
                    awayBench: JSON.parse(JSON.stringify(this.awayBench)),
                });
                if (this.undoStack.length > 50) this.undoStack.shift();
            },

            restoreSnapshot(s) {
                this.homeScore = s.homeScore;
                this.awayScore = s.awayScore;
                this.homePeriodScores = [...s.homePeriodScores];
                this.awayPeriodScores = [...s.awayPeriodScores];
                this.homeFouls = s.homeFouls;
                this.awayFouls = s.awayFouls;
                this.homeTimeouts = s.homeTimeouts;
                this.awayTimeouts = s.awayTimeouts;
                this.possession = s.possession;
                this.server = s.server;
                this.homeRotation = s.homeRotation;
                this.awayRotation = s.awayRotation;
                this.homeCourt = JSON.parse(JSON.stringify(s.homeCourt));
                this.awayCourt = JSON.parse(JSON.stringify(s.awayCourt));
                this.homeBench = JSON.parse(JSON.stringify(s.homeBench));
                this.awayBench = JSON.parse(JSON.stringify(s.awayBench));
            },

            // Web Audio API Sound Synthesizer with 0ms latency
            playSound(type) {
                try {
                    window.dispatchEvent(new CustomEvent('play-sound', { detail: type }));
                } catch (e) {}
            },

            // Asynchronous Background Sync Pipeline (Zero UI blocking)
            enqueueSync(method, args) {
                this.syncQueue.push({
                    id: 'mut_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5),
                    method,
                    args,
                    createdAt: Date.now(),
                });
                this.saveQueue();
                this.flushQueue();
            },

            saveQueue() {
                try {
                    localStorage.setItem(`prokeeper_queue_${this.gameId}`, JSON.stringify(this.syncQueue));
                } catch (e) {}
            },

            loadQueue() {
                try {
                    const saved = localStorage.getItem(`prokeeper_queue_${this.gameId}`);
                    if (saved) {
                        this.syncQueue = JSON.parse(saved);
                        if (this.syncQueue.length > 0) {
                            this.flushQueue();
                        }
                    }
                } catch (e) {}
            },

            async flushQueue() {
                if (this.isSyncing || this.syncQueue.length === 0) return;
                this.isSyncing = true;
                this.syncStatus = 'syncing';

                while (this.syncQueue.length > 0) {
                    const item = this.syncQueue[0];
                    try {
                        if (this.$wire) {
                            if (item.method === 'setAndExecuteSub') {
                                const [side, outJ, inJ] = item.args;
                                this.$wire.set('subTeamSide', side);
                                this.$wire.set('subOutJersey', outJ);
                                this.$wire.set('subInJersey', inJ);
                                await this.$wire.executeSub();
                            } else if (typeof this.$wire[item.method] === 'function') {
                                await this.$wire[item.method](...item.args);
                            }
                        }

                        // Mutation synchronized
                        this.syncQueue.shift();
                        this.saveQueue();
                    } catch (err) {
                        console.warn('Background sync deferred (offline or network pause):', err);
                        this.syncStatus = 'offline';
                        this.isSyncing = false;
                        return;
                    }
                }

                this.syncStatus = 'synced';
                this.isSyncing = false;
            }
        };
    }
};
