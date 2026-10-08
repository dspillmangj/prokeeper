/**
 * ProKeeper Zero-Latency Local-First Operator Engine
 * 
 * Guarantees 0ms tactile feedback, instant score updates, local undo,
 * instant player substitutions, local rotations, and resilient non-blocking background synchronization.
 */

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
            showSubSheet: false,
            subTeamSide: 'home',
            subOutJersey: '',
            subInJersey: '',
            jerseyBuffer: '',
            bufferTimeout: null,
            feedbackMessage: '⚡ Local Engine Ready (0ms Latency)',
            feedbackType: 'info',

            // Background Async Sync Queue
            syncQueue: [],
            syncStatus: 'synced', // 'synced' | 'syncing' | 'offline'
            isSyncing: false,
            syncRetryInterval: null,

            init() {
                // Restore any pending sync queue from localStorage
                this.loadQueue();

                // Setup online/offline and retry listeners
                window.addEventListener('online', () => {
                    this.flushQueue();
                });

                this.syncRetryInterval = setInterval(() => {
                    if (this.syncQueue.length > 0 && !this.isSyncing) {
                        this.flushQueue();
                    }
                }, 3000);

                // Keyboard listeners for rapid zero-lag entry
                window.addEventListener('keydown', (e) => this.handleKeyDown(e));
            },

            destroy() {
                if (this.syncRetryInterval) clearInterval(this.syncRetryInterval);
            },

            handleKeyDown(e) {
                if (['INPUT', 'SELECT', 'TEXTAREA'].includes(document.activeElement?.tagName)) {
                    if (e.key === 'Escape') document.activeElement.blur();
                    return;
                }

                // If Action Pad is open, action keys trigger immediately in 0ms
                if (this.selectedPlayer) {
                    const k = e.key.toUpperCase();
                    let actionMap = {};

                    if (this.sport === 'basketball') {
                        actionMap = {
                            '2': 'X', 'X': 'X',
                            '3': 'M', 'M': 'M',
                            '1': 'B', 'B': 'B',
                            'Z': 'Z',
                            'N': 'N',
                            'V': 'V',
                            'D': 'D', 'R': 'D',
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
                        return;
                    }
                }

                // Numeric Key Buffer for typing jersey #
                if (e.key >= '0' && e.key <= '9') {
                    e.preventDefault();
                    this.jerseyBuffer += e.key;
                    clearTimeout(this.bufferTimeout);

                    const matchedHome = this.homeCourt.find(p => String(p.jersey_number) === this.jerseyBuffer);
                    const matchedAway = this.awayCourt.find(p => String(p.jersey_number) === this.jerseyBuffer);

                    if (matchedHome) {
                        this.openActionPad('home', matchedHome.jersey_number, matchedHome.player_name, matchedHome.id);
                        this.jerseyBuffer = '';
                    } else if (matchedAway) {
                        this.openActionPad('away', matchedAway.jersey_number, matchedAway.player_name, matchedAway.id);
                        this.jerseyBuffer = '';
                    } else {
                        this.bufferTimeout = setTimeout(() => { this.jerseyBuffer = ''; }, 1200);
                    }
                    return;
                }

                // Fast Order Hotkeys (1-5 for Basketball, 1-6 for Volleyball)
                const maxOrder = this.sport === 'basketball' ? 5 : 6;
                const orderNum = parseInt(e.key);
                if (!this.jerseyBuffer && orderNum >= 1 && orderNum <= maxOrder) {
                    e.preventDefault();
                    const p = this.homeCourt[orderNum - 1];
                    if (p) this.openActionPad('home', p.jersey_number, p.player_name, p.id);
                    return;
                }

                // Global Spacebar: Toggle Possession or Server
                if (e.code === 'Space') {
                    e.preventDefault();
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

                if (e.key === 'Escape') {
                    this.selectedPlayer = null;
                    this.showSubSheet = false;
                    this.jerseyBuffer = '';
                }
            },

            openActionPad(side, jersey, name, id) {
                this.selectedPlayer = { side, jersey, name, id };
                this.playSound('tap');
            },

            // 0ms Latency Stat Execution Engine
            executeAction(actionCode) {
                if (!this.selectedPlayer) return;
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

                this.feedbackMessage = `⚡ 0ms: ${eventDesc}`;
                this.feedbackType = 'success';
                this.playSound(playSoundType);

                // Close selection overlay instantly
                this.selectedPlayer = null;

                // Queue mutation for non-blocking asynchronous sync
                this.enqueueSync('recordQuickStat', [side, jersey, actionCode]);
            },

            // 0ms Direct Score Adjustment (+1, +2, +3, -1)
            adjustScoreFast(side, delta) {
                this.pushUndoSnapshot('score', { side, delta });

                if (side === 'home') {
                    this.homeScore = Math.max(0, this.homeScore + delta);
                } else {
                    this.awayScore = Math.max(0, this.awayScore + delta);
                }
                this.updateCurrentPeriodScore(side, delta);

                this.playSound(delta > 0 ? 'score' : 'tap');
                this.feedbackMessage = `⚡ 0ms: ${side.toUpperCase()} Score ${delta > 0 ? '+' : ''}${delta}`;
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
                this.subTeamSide = side;
                this.subOutJersey = String(jersey || '');
                this.subInJersey = '';
                this.showSubSheet = true;
                this.selectedPlayer = null;
                this.playSound('tap');
            },

            confirmSubFast() {
                if (!this.subOutJersey || !this.subInJersey) return;
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
                this.feedbackMessage = `⚡ 0ms: Subbed OUT #${outJ} -> IN #${inJ} (${side.toUpperCase()})`;
                this.feedbackType = 'success';

                this.showSubSheet = false;
                this.subOutJersey = '';
                this.subInJersey = '';

                this.enqueueSync('setAndExecuteSub', [side, outJ, inJ]);
            },

            // 0ms Volleyball Rotation (P1-P6 order shift)
            rotateTeamFast(side) {
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
                this.feedbackMessage = `⚡ 0ms: Rotated ${side.toUpperCase()} to P${side === 'home' ? this.homeRotation : this.awayRotation}`;
                this.feedbackType = 'info';

                this.enqueueSync('rotateTeam', [side]);
            },

            // 0ms Toggle Possession Arrow
            togglePossession() {
                this.pushUndoSnapshot('possession', { prev: this.possession });
                this.possession = (this.possession === 'home') ? 'away' : 'home';
                this.playSound('tap');
                this.feedbackMessage = `⚡ 0ms: Possession -> ${this.possession.toUpperCase()}`;
                this.enqueueSync('togglePossession', []);
            },

            // 0ms Toggle Volleyball Server
            toggleServer() {
                this.pushUndoSnapshot('server', { prev: this.server });
                this.server = (this.server === 'home') ? 'away' : 'home';
                this.playSound('tap');
                this.feedbackMessage = `⚡ 0ms: Server -> ${this.server.toUpperCase()}`;
                this.enqueueSync('toggleServer', []);
            },

            // 0ms Timeout Charge
            callTimeoutFast(side) {
                this.pushUndoSnapshot('timeout', { side });
                if (side === 'home' && this.homeTimeouts > 0) {
                    this.homeTimeouts -= 1;
                } else if (side === 'away' && this.awayTimeouts > 0) {
                    this.awayTimeouts -= 1;
                }
                this.playSound('tap');
                this.feedbackMessage = `⚡ 0ms: Timeout charged to ${side.toUpperCase()}`;
                this.enqueueSync('callTimeout', [side]);
            },

            // 0ms Advance Period / Set
            nextPeriodFast() {
                this.pushUndoSnapshot('period', {
                    currentPeriod: this.currentPeriod,
                    homeFouls: this.homeFouls,
                    awayFouls: this.awayFouls,
                });

                this.currentPeriod += 1;
                this.periodName = this.sport === 'volleyball' ? `Set ${this.currentPeriod}` : (this.currentPeriod <= 4 ? `Q${this.currentPeriod}` : `OT${this.currentPeriod - 4}`);
                this.homeFouls = 0;
                this.awayFouls = 0;

                this.playSound('tap');
                this.feedbackMessage = `⚡ 0ms: Advanced to ${this.periodName}`;
                this.enqueueSync(this.sport === 'volleyball' ? 'nextSet' : 'nextPeriod', []);
            },

            // 0ms Local Undo Execution
            undo() {
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
                    this.feedbackMessage = `⚡ 0ms Reverted: ${undone.description || 'Last Play'}`;
                } else {
                    this.feedbackMessage = '⚡ 0ms: Last action reverted';
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
