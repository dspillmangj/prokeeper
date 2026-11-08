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
        'X': { name: '2pt MAKE', points: 2, teamTarget: 'self', isScore: true, stat: 'fgm', category: 'scoring' },
        'Z': { name: '2pt Miss', points: 0, teamTarget: 'self', isScore: false, stat: 'fga', category: 'scoring' },
        'M': { name: '3pt MAKE', points: 3, teamTarget: 'self', isScore: true, stat: 'fg3m', category: 'scoring' },
        'N': { name: '3pt Miss', points: 0, teamTarget: 'self', isScore: false, stat: 'fg3a', category: 'scoring' },
        'B': { name: 'FT MAKE', points: 1, teamTarget: 'self', isScore: true, stat: 'ftm', category: 'scoring' },
        'V': { name: 'FT Miss', points: 0, teamTarget: 'self', isScore: false, stat: 'fta', category: 'scoring' },
        'D': { name: 'Def Reb', points: 0, teamTarget: 'self', isScore: false, stat: 'dreb', category: 'rebounds' },
        'O': { name: 'Off Reb', points: 0, teamTarget: 'self', isScore: false, stat: 'oreb', category: 'rebounds' },
        'A': { name: 'Assist', points: 0, teamTarget: 'self', isScore: false, stat: 'ast', category: 'ball_movement' },
        'S': { name: 'Steal', points: 0, teamTarget: 'self', isScore: false, stat: 'stl', category: 'defense' },
        'K': { name: 'Block', points: 0, teamTarget: 'self', isScore: false, stat: 'blk', category: 'defense' },
        'W': { name: 'Swat', points: 0, teamTarget: 'self', isScore: false, stat: 'swat', category: 'defense' },
        'P': { name: 'Pass TO', points: 0, teamTarget: 'self', isScore: false, stat: 'to_pass', category: 'turnovers' },
        'U': { name: 'Fumble TO', points: 0, teamTarget: 'self', isScore: false, stat: 'to_fumble', category: 'turnovers' },
        'I': { name: 'Violation', points: 0, teamTarget: 'self', isScore: false, stat: 'to_violation', category: 'turnovers' },
        'F': { name: 'Pers Foul', points: 0, teamTarget: 'self', isScore: false, isFoul: true, isTeamFoul: true, stat: 'fouls_pers', category: 'fouls' },
        'R': { name: 'Off Foul', points: 0, teamTarget: 'self', isScore: false, isFoul: true, isTeamFoul: true, stat: 'fouls_off', category: 'fouls' },
        'T': { name: 'Tech Foul', points: 0, teamTarget: 'self', isScore: false, isFoul: true, isTeamFoul: false, stat: 'fouls_tech', category: 'fouls' },
        'G': { name: 'Flagrant Foul', points: 0, teamTarget: 'self', isScore: false, isFoul: true, isTeamFoul: true, stat: 'fouls_flagrant', category: 'fouls' },
        'FL': { name: 'Flagrant Foul', points: 0, teamTarget: 'self', isScore: false, isFoul: true, isTeamFoul: true, stat: 'fouls_flagrant', category: 'fouls' },
        'FLAG': { name: 'Flagrant Foul', points: 0, teamTarget: 'self', isScore: false, isFoul: true, isTeamFoul: true, stat: 'fouls_flagrant', category: 'fouls' },
        'H': { name: 'Drawn Foul', points: 0, teamTarget: 'self', isScore: false, isFoul: false, isTeamFoul: false, stat: 'fouls_forced', category: 'fouls' },
        'TIMEOUT': { name: 'Timeout', points: 0, teamTarget: 'self', isScore: false, category: 'administrative' },
        'SCORE_ADJ': { name: 'Score Adjustment', points: 0, teamTarget: 'self', isScore: false, category: 'administrative' },
        'SUB': { name: 'Substitution', points: 0, teamTarget: 'self', isScore: false, category: 'administrative' },
        'PERIOD': { name: 'Period Advance', points: 0, teamTarget: 'self', isScore: false, category: 'administrative' },
        'NOTE': { name: 'Audit Note', points: 0, teamTarget: 'self', isScore: false, category: 'administrative' },
    },

    volleyballActions: {
        'K': { name: 'Kill', points: 1, teamTarget: 'self', isScore: true, category: 'attack' },
        'A': { name: 'Ace', points: 1, teamTarget: 'self', isScore: true, category: 'serve' },
        'B': { name: 'Block Solo', points: 1, teamTarget: 'self', isScore: true, category: 'defense' },
        'C': { name: 'Block Assist', points: 1, teamTarget: 'self', isScore: true, category: 'defense' },
        'E': { name: 'Attack Error', points: 1, teamTarget: 'opp', isScore: true, category: 'attack' },
        'S': { name: 'Service Error', points: 1, teamTarget: 'opp', isScore: true, category: 'serve' },
        'H': { name: 'Handling Error', points: 1, teamTarget: 'opp', isScore: true, category: 'errors' },
        'R': { name: 'Reception Error', points: 1, teamTarget: 'opp', isScore: true, category: 'errors' },
        'D': { name: 'Dig', points: 0, teamTarget: 'self', isScore: false, category: 'defense' },
        'Z': { name: 'Set Assist', points: 0, teamTarget: 'self', isScore: false, category: 'ball_movement' },
        'T': { name: 'Attack Attempt', points: 0, teamTarget: 'self', isScore: false, category: 'attack' },
        'TIMEOUT': { name: 'Timeout', points: 0, teamTarget: 'self', isScore: false, category: 'administrative' },
        'SCORE_ADJ': { name: 'Score Adjustment', points: 0, teamTarget: 'self', isScore: false, category: 'administrative' },
        'SUB': { name: 'Substitution', points: 0, teamTarget: 'self', isScore: false, category: 'administrative' },
        'PERIOD': { name: 'Set Advance', points: 0, teamTarget: 'self', isScore: false, category: 'administrative' },
        'ROTATE': { name: 'Rotation', points: 0, teamTarget: 'self', isScore: false, category: 'administrative' },
        'NOTE': { name: 'Audit Note', points: 0, teamTarget: 'self', isScore: false, category: 'administrative' },
    },

    createOperator(sport, config) {
        return {
            sport,
            gameId: config.gameId,
            homeTeamName: config.homeTeamName || 'Home',
            awayTeamName: config.awayTeamName || 'Away',
            homeTeamColor: config.homeTeamColor || '#1e40af',
            awayTeamColor: config.awayTeamColor || '#b91c1c',
            broadcastFlip: !!config.broadcastFlip,
            isFlipped: (function() {
                try {
                    const saved = localStorage.getItem('prokeeper_flipped_' + config.gameId);
                    if (saved !== null) return saved === 'true';
                } catch (e) {}
                return !!config.isFlipped || !!config.broadcastFlip;
            })(),

            // Ultra-Fast 0ms Local Reactive State
            periodFormat: config.periodFormat || (sport === 'volleyball' ? 'sets' : 'quarters'),
            otPeriodsAdded: 0,
            homeScore: config.homeScore || 0,
            awayScore: config.awayScore || 0,
            homePeriodScores: (function() {
                const minLen = sport === 'basketball' ? (config.periodFormat === 'halves' ? 2 : 4) : 5;
                const arr = Array.isArray(config.homePeriodScores) ? [...config.homePeriodScores] : [];
                while (arr.length < minLen) arr.push(0);
                return arr;
            })(),
            awayPeriodScores: (function() {
                const minLen = sport === 'basketball' ? (config.periodFormat === 'halves' ? 2 : 4) : 5;
                const arr = Array.isArray(config.awayPeriodScores) ? [...config.awayPeriodScores] : [];
                while (arr.length < minLen) arr.push(0);
                return arr;
            })(),
            currentPeriod: config.currentPeriod || 1,
            periodName: config.periodName || (sport === 'volleyball' ? 'Set 1' : (config.periodFormat === 'halves' ? '1st Half' : '1st Quarter')),
            possession: config.possession || 'home',
            server: config.server || 'home',
            homeFouls: config.homeFouls || 0,
            awayFouls: config.awayFouls || 0,
            timeoutsFullAllowed: Number(config.timeoutsFull ?? 3),
            timeouts30sAllowed: Number(config.timeouts30s ?? 2),
            timeoutsOtAllowed: Number(config.timeoutsOt ?? 1),
            timeoutsPerSet: Number(config.timeoutsPerSet ?? 2),
            homeFullTimeouts: Number(config.homeFullTimeouts ?? 3),
            home30sTimeouts: Number(config.home30sTimeouts ?? 2),
            awayFullTimeouts: Number(config.awayFullTimeouts ?? 3),
            away30sTimeouts: Number(config.away30sTimeouts ?? 2),
            homeTimeouts: Number(config.homeTimeouts ?? (sport === 'volleyball' ? (config.timeoutsPerSet ?? 2) : (Number(config.homeFullTimeouts ?? 3) + Number(config.home30sTimeouts ?? 2)))),
            awayTimeouts: Number(config.awayTimeouts ?? (sport === 'volleyball' ? (config.timeoutsPerSet ?? 2) : (Number(config.awayFullTimeouts ?? 3) + Number(config.away30sTimeouts ?? 2)))),
            homeFullTimeoutsTaken: Number(config.homeFullTimeoutsTaken ?? 0),
            home30sTimeoutsTaken: Number(config.home30sTimeoutsTaken ?? 0),
            awayFullTimeoutsTaken: Number(config.awayFullTimeoutsTaken ?? 0),
            away30sTimeoutsTaken: Number(config.away30sTimeoutsTaken ?? 0),
            homeTimeoutsTaken: Number(config.homeTimeoutsTaken ?? (sport === 'volleyball' ? 0 : (Number(config.homeFullTimeoutsTaken ?? 0) + Number(config.home30sTimeoutsTaken ?? 0)))),
            awayTimeoutsTaken: Number(config.awayTimeoutsTaken ?? (sport === 'volleyball' ? 0 : (Number(config.awayFullTimeoutsTaken ?? 0) + Number(config.away30sTimeoutsTaken ?? 0)))),
            homeRotation: config.homeRotation || 1,
            bonusFoulThreshold: config.bonusFoulThreshold || 5,
            doubleBonusFoulThreshold: config.doubleBonusFoulThreshold || 5,
            playerFoulLimit: config.playerFoulLimit || 5,
            playerStats: { home: {}, away: {} },

            // Local Roster & Court Lineup Arrays
            homeCourt: JSON.parse(JSON.stringify(config.homeCourt || [])),
            awayCourt: JSON.parse(JSON.stringify(config.awayCourt || [])),
            homeBench: JSON.parse(JSON.stringify(config.homeBench || [])),
            awayBench: JSON.parse(JSON.stringify(config.awayBench || [])),

            // Local Play-by-Play and Undo Stack
            recentEvents: JSON.parse(JSON.stringify(config.recentEvents || [])),
            undoStack: [],

            // Game Clock & Real-Time Sync State
            clockSeconds: Number(config.clockSeconds ?? 480),
            clockRunning: Boolean(config.clockRunning ?? false),
            clockInterval: null,
            _optimisticUuids: new Set(),

            // Modal & Selection State
            selectedPlayer: null,
            showLineupModal: false,
            showRosterModal: false,
            rosterModalTeam: 'home',
            showExecuteSubModal: false,
            showPlayByPlayModal: false,
            showAddEventModal: false,
            editingEvent: null,
            newEvent: {
                team_side: 'home',
                jersey_number: '',
                player_name: '',
                action_code: 'X',
                period: 1,
                points: 2,
                clock_seconds_remaining: 0,
                description: '',
            },
            pbpFilterPeriod: 'all',
            pbpFilterTeam: 'all',
            pbpFilterCategory: 'all', // all, scoring, rebounds, fouls, turnovers, administrative
            pbpSearchQuery: '',
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
            errorQueue: [],
            showErrorList: false,
            _errorHoverCloseTimer: null,
            _errorPruneInterval: null,
            _errorTimeouts: {},

            get frontError() {
                return (this.errorQueue && this.errorQueue.length > 0) ? this.errorQueue[0] : null;
            },

            pushError(message, details = null) {
                if (!message || typeof message !== 'string') return;
                const trimmed = message.trim();
                if (!trimmed) return;

                const now = new Date();
                const timeStr = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });

                // Suppress identical duplicate error if received within 1 second
                if (this.errorQueue.length > 0 && this.errorQueue[0].message === trimmed && (Date.now() - this.errorQueue[0].timestamp) < 1000) {
                    return;
                }

                const errId = 'err_' + Date.now() + '_' + Math.random().toString(36).substr(2, 6);
                const errObj = {
                    id: errId,
                    message: trimmed,
                    details: details || null,
                    timestamp: Date.now(),
                    timeStr: timeStr,
                };

                this.errorQueue.unshift(errObj);
                this.saveErrorQueue();
                this.playSound('error');
                if (navigator.vibrate) {
                    try { navigator.vibrate(50); } catch (e) {}
                }

                // Automatically expire error after 30 seconds
                if (!this._errorTimeouts) this._errorTimeouts = {};
                if (this._errorTimeouts[errId]) clearTimeout(this._errorTimeouts[errId]);
                this._errorTimeouts[errId] = setTimeout(() => {
                    this.expireError(errId);
                }, 30000);
            },

            expireError(id) {
                if (this._errorTimeouts && this._errorTimeouts[id]) {
                    clearTimeout(this._errorTimeouts[id]);
                    delete this._errorTimeouts[id];
                }
                if (!this.errorQueue || this.errorQueue.length === 0) return;
                const idx = this.errorQueue.findIndex(e => e.id === id);
                if (idx !== -1) {
                    this.errorQueue.splice(idx, 1);
                    if (this.errorQueue.length === 0) {
                        this.showErrorList = false;
                        if (this._errorHoverCloseTimer) {
                            clearTimeout(this._errorHoverCloseTimer);
                            this._errorHoverCloseTimer = null;
                        }
                    }
                    this.saveErrorQueue();
                }
            },

            pruneExpiredErrors() {
                if (!this.errorQueue || this.errorQueue.length === 0) return;
                const now = Date.now();
                const before = this.errorQueue.length;
                this.errorQueue = this.errorQueue.filter(err => {
                    const age = now - (err.timestamp || 0);
                    if (age >= 30000) {
                        if (this._errorTimeouts && this._errorTimeouts[err.id]) {
                            clearTimeout(this._errorTimeouts[err.id]);
                            delete this._errorTimeouts[err.id];
                        }
                        return false;
                    }
                    return true;
                });
                if (this.errorQueue.length !== before) {
                    if (this.errorQueue.length === 0) {
                        this.showErrorList = false;
                        if (this._errorHoverCloseTimer) {
                            clearTimeout(this._errorHoverCloseTimer);
                            this._errorHoverCloseTimer = null;
                        }
                    }
                    this.saveErrorQueue();
                }
            },

            dismissFrontError() {
                if (!this.errorQueue || this.errorQueue.length === 0) return;
                const front = this.errorQueue.shift();
                if (front && this._errorTimeouts && this._errorTimeouts[front.id]) {
                    clearTimeout(this._errorTimeouts[front.id]);
                    delete this._errorTimeouts[front.id];
                }
                if (this.errorQueue.length === 0) {
                    this.showErrorList = false;
                    if (this._errorHoverCloseTimer) {
                        clearTimeout(this._errorHoverCloseTimer);
                        this._errorHoverCloseTimer = null;
                    }
                }
                this.saveErrorQueue();
                this.playSound('tap');
            },

            dismissAllErrors() {
                if (this._errorTimeouts) {
                    Object.values(this._errorTimeouts).forEach(t => clearTimeout(t));
                    this._errorTimeouts = {};
                }
                this.errorQueue = [];
                this.showErrorList = false;
                if (this._errorHoverCloseTimer) {
                    clearTimeout(this._errorHoverCloseTimer);
                    this._errorHoverCloseTimer = null;
                }
                this.saveErrorQueue();
                this.playSound('tap');
            },

            dismissError(id) {
                if (this._errorTimeouts && this._errorTimeouts[id]) {
                    clearTimeout(this._errorTimeouts[id]);
                    delete this._errorTimeouts[id];
                }
                if (!this.errorQueue) return;
                this.errorQueue = this.errorQueue.filter(e => e.id !== id);
                if (this.errorQueue.length === 0) {
                    this.showErrorList = false;
                    if (this._errorHoverCloseTimer) {
                        clearTimeout(this._errorHoverCloseTimer);
                        this._errorHoverCloseTimer = null;
                    }
                }
                this.saveErrorQueue();
                this.playSound('tap');
            },

            toggleErrorList(force) {
                if (!this.errorQueue || this.errorQueue.length === 0) {
                    this.showErrorList = false;
                    return;
                }
                this.showErrorList = typeof force === 'boolean' ? force : !this.showErrorList;
                this.playSound('tap');
            },

            openErrorHover() {
                if (this._errorHoverCloseTimer) {
                    clearTimeout(this._errorHoverCloseTimer);
                    this._errorHoverCloseTimer = null;
                }
                if (this.errorQueue && this.errorQueue.length > 0) {
                    this.showErrorList = true;
                }
            },

            closeErrorHover() {
                if (this._errorHoverCloseTimer) {
                    clearTimeout(this._errorHoverCloseTimer);
                }
                this._errorHoverCloseTimer = setTimeout(() => {
                    this.showErrorList = false;
                    this._errorHoverCloseTimer = null;
                }, 350);
            },

            cancelCloseErrorHover() {
                if (this._errorHoverCloseTimer) {
                    clearTimeout(this._errorHoverCloseTimer);
                    this._errorHoverCloseTimer = null;
                }
                if (this._errorPruneInterval) {
                    clearInterval(this._errorPruneInterval);
                    this._errorPruneInterval = null;
                }
                if (this._errorTimeouts) {
                    Object.values(this._errorTimeouts).forEach(t => clearTimeout(t));
                    this._errorTimeouts = {};
                }
            },

            saveErrorQueue() {
                try {
                    sessionStorage.setItem(`prokeeper_errors_${this.gameId}`, JSON.stringify(this.errorQueue || []));
                } catch (e) {}
            },

            loadErrorQueue() {
                try {
                    const stored = sessionStorage.getItem(`prokeeper_errors_${this.gameId}`);
                    if (stored) {
                        const parsed = JSON.parse(stored);
                        if (Array.isArray(parsed)) {
                            const now = Date.now();
                            // Only load errors that are less than 30 seconds old
                            this.errorQueue = parsed.filter(err => (now - (err.timestamp || 0)) < 30000);
                            if (!this._errorTimeouts) this._errorTimeouts = {};
                            this.errorQueue.forEach(err => {
                                const remaining = Math.max(100, 30000 - (now - (err.timestamp || 0)));
                                this._errorTimeouts[err.id] = setTimeout(() => {
                                    this.expireError(err.id);
                                }, remaining);
                            });
                        }
                    }
                } catch (e) {}
            },
            attentionAlert: null,
            alertFlashActive: false,
            alertFlashTimer: null,
            alertCountdownTimer: null,
            alertRemainingSeconds: 0,
            alertTotalSeconds: 0,
            benchSelectSide: null,
            pendingTimeout: null,

            triggerAttentionAlert(type, title, message, details = '', durationSeconds = 30, metadata = {}) {
                // If an error is passed, route directly to pushError so errors are never duplicated
                if (type === 'error') {
                    this.pushError(title ? `${title}: ${message}` : message, details);
                    return;
                }

                if (this.alertFlashTimer) {
                    clearTimeout(this.alertFlashTimer);
                    this.alertFlashTimer = null;
                }
                if (this.alertCountdownTimer) {
                    clearInterval(this.alertCountdownTimer);
                    this.alertCountdownTimer = null;
                }

                this.alertFlashActive = true;
                this.alertFlashTimer = setTimeout(() => {
                    this.alertFlashActive = false;
                    this.alertFlashTimer = null;
                }, 4500);

                this.alertRemainingSeconds = durationSeconds;
                this.alertTotalSeconds = durationSeconds;
                this.attentionAlert = {
                    type, // 'foul_out', 'bonus', 'double_bonus'
                    title,
                    message,
                    details,
                    side: metadata.side || null,
                    jersey: metadata.jersey != null ? String(metadata.jersey) : null,
                    name: metadata.name || null,
                    teamName: metadata.teamName || null,
                    createdAt: Date.now(),
                };

                this.feedbackMessage = `${title}: ${message}`;
                this.feedbackType = 'info';

                this.playSound(type);

                this.alertCountdownTimer = setInterval(() => {
                    if (this.alertRemainingSeconds > 1) {
                        this.alertRemainingSeconds--;
                    } else {
                        this.dismissAttentionAlert();
                    }
                }, 1000);
            },

            dismissAttentionAlert() {
                if (this.alertFlashTimer) {
                    clearTimeout(this.alertFlashTimer);
                    this.alertFlashTimer = null;
                }
                if (this.alertCountdownTimer) {
                    clearInterval(this.alertCountdownTimer);
                    this.alertCountdownTimer = null;
                }
                this.alertFlashActive = false;
                this.attentionAlert = null;
                this.alertRemainingSeconds = 0;
                this.alertTotalSeconds = 0;
            },

            openBenchModal(side) {
                if (!this.canExecute('openBenchModal', 150)) return;
                this.selectedPlayer = null;
                this.benchSelectSide = side;
                this.playSound('tap');
            },

            closeBenchModal() {
                this.benchSelectSide = null;
                this.playSound('tap');
            },

            selectBenchPlayer(player, side) {
                if (!player) return;
                this.benchSelectSide = null;
                this.openActionPad(side, player.jersey_number, player.player_name, player.id);
            },

            requestTimeout(side, type = 'full') {
                if (this.sport === 'basketball') {
                    const count = (side === 'home')
                        ? (type === '30s' ? Number(this.home30sTimeouts || 0) : Number(this.homeFullTimeouts || 0))
                        : (type === '30s' ? Number(this.away30sTimeouts || 0) : Number(this.awayFullTimeouts || 0));
                    if (count <= 0) {
                        this.pushError(`No ${type === '30s' ? '30-Second' : 'Full'} Timeouts remaining for ${side.toUpperCase()}`);
                        return;
                    }
                } else {
                    const count = Number((side === 'home' ? this.homeTimeouts : this.awayTimeouts) || 0);
                    if (count <= 0) {
                        this.pushError(`No Timeouts remaining for ${side.toUpperCase()}`);
                        return;
                    }
                }

                const teamName = (side === 'home' ? this.homeTeamName : this.awayTeamName) || (side === 'home' ? 'Home' : 'Away');
                const teamColor = (side === 'home' ? this.homeTeamColor : this.awayTeamColor) || (side === 'home' ? '#2563eb' : '#dc2626');
                const typeLabel = (type === '30s') ? '30-Second' : 'Full (60s)';
                const remFull = Number((side === 'home' ? this.homeFullTimeouts : this.awayFullTimeouts) ?? 0);
                const rem30s = Number((side === 'home' ? this.home30sTimeouts : this.away30sTimeouts) ?? 0);
                const totalRem = Number((side === 'home' ? this.homeTimeouts : this.awayTimeouts) ?? (remFull + rem30s));
                const takenFull = Number((side === 'home' ? this.homeFullTimeoutsTaken : this.awayFullTimeoutsTaken) ?? 0);
                const taken30s = Number((side === 'home' ? this.home30sTimeoutsTaken : this.away30sTimeoutsTaken) ?? 0);
                const totalTaken = Number((side === 'home' ? this.homeTimeoutsTaken : this.awayTimeoutsTaken) ?? (takenFull + taken30s));

                const formattedRemaining = this.sport === 'basketball'
                    ? `${remFull} Full, ${rem30s} 30s (${totalRem} Total)`
                    : `${totalRem} left this set`;
                const formattedTaken = this.sport === 'basketball'
                    ? `${takenFull} Full, ${taken30s} 30s (${totalTaken} Total)`
                    : `${totalTaken} taken this set`;
                const formattedSummary = this.sport === 'basketball'
                    ? `Taken: ${totalTaken} (${takenFull} Full, ${taken30s} 30s) • Remain: ${totalRem} (${remFull} Full, ${rem30s} 30s)`
                    : `Taken: ${totalTaken} • Remain: ${totalRem} this set`;

                this.pendingTimeout = {
                    side,
                    type,
                    teamName,
                    teamColor,
                    typeLabel,
                    timeoutTypeLabel: typeLabel,
                    remFull,
                    rem30s,
                    totalRemaining: totalRem,
                    takenFull,
                    taken30s,
                    totalTaken,
                    formattedRemaining,
                    formattedTaken,
                    formattedSummary,
                };
                this.playSound('tap');
            },

            executePendingTimeout() {
                if (!this.pendingTimeout) return;
                const { side, type } = this.pendingTimeout;
                this.pendingTimeout = null;
                this.callTimeoutFast(side, type);
            },

            cancelPendingTimeout() {
                this.pendingTimeout = null;
                this.playSound('tap');
            },
            // Assist Selection Reactive State (for Hold & Drag-to-Assist)
            assistModal: {
                open: false,
                actionCode: null,
                actionName: '',
                points: 0,
                hoveredJersey: null,
            },
            // 3-Second Post-Shot Keyboard Assist Window Reactive State
            assistWindow: {
                active: false,
                teamSide: null,
                shooterJersey: null,
                shooterName: '',
                actionCode: null,
                points: 0,
                eventId: null,
                secondsRemaining: 3.0,
                progressPercent: 100,
                waitingForJersey: false,
                assistBuffer: '',
                matchedPlayer: null,
            },
            _assistCountdownInterval: null,
            _assistIdleTimer: null,
            _holdTimer: null,
            _holdTriggered: false,
            _suppressNextClick: false,
            _actionCooldowns: {},

            openPlayByPlayModal() {
                if (!this.canExecute('openPlayByPlayModal', 150)) return;
                this.showPlayByPlayModal = true;
                this.selectedPlayer = null;
                this.showLineupModal = false;
                this.showExecuteSubModal = false;
                this.pbpSearchQuery = '';
                this.playSound('tap');
            },

            closePlayByPlayModal() {
                this.showPlayByPlayModal = false;
                this.showAddEventModal = false;
                this.editingEvent = null;
            },

            openAddEventModal() {
                this.newEvent = {
                    team_side: 'home',
                    jersey_number: '',
                    player_name: '',
                    action_code: this.sport === 'basketball' ? 'X' : 'K',
                    period: this.currentPeriod,
                    points: this.sport === 'basketball' ? 2 : 1,
                    clock_seconds_remaining: 0,
                    description: '',
                };
                this.showAddEventModal = true;
                this.playSound('tap');
            },

            closeAddEventModal() {
                this.showAddEventModal = false;
            },

            onNewEventActionChange() {
                const code = this.newEvent.action_code;
                const def = this.sport === 'basketball'
                    ? ProKeeperEngine.basketballActions[code]
                    : ProKeeperEngine.volleyballActions[code];
                if (def && typeof def.points === 'number') {
                    this.newEvent.points = def.points;
                }
            },

            getRegularPeriodsCount() {
                if (this.sport === 'volleyball') return 5;
                return (this.periodFormat === 'halves') ? 2 : 4;
            },

            formatPeriodName(period) {
                const p = Number(period) || 1;
                if (this.sport === 'volleyball') {
                    return `Set ${p}`;
                }
                if (this.periodFormat === 'halves') {
                    if (p === 1) return '1st Half';
                    if (p === 2) return '2nd Half';
                    if (p === 3) return 'Overtime';
                    if (p === 4) return '2nd Overtime';
                    if (p === 5) return '3rd Overtime';
                    return `OT ${p - 2}`;
                }
                if (p === 1) return '1st Quarter';
                if (p === 2) return '2nd Quarter';
                if (p === 3) return '3rd Quarter';
                if (p === 4) return '4th Quarter';
                if (p === 5) return 'Overtime';
                if (p === 6) return '2nd Overtime';
                if (p === 7) return '3rd Overtime';
                return `Quarter ${p}`;
            },

            formatPeriodBadge(period) {
                const p = Number(period) || 1;
                if (this.sport === 'volleyball') {
                    return `S${p}`;
                }
                if (this.periodFormat === 'halves') {
                    if (p === 1) return 'H1';
                    if (p === 2) return 'H2';
                    return `OT${p - 2}`;
                }
                if (p <= 4) return `Q${p}`;
                return `OT${p - 4}`;
            },

            formatPeriodTitle(period) {
                const p = Number(period) || 1;
                if (this.sport === 'volleyball') {
                    return `Switch to Set ${p}`;
                }
                if (this.periodFormat === 'halves') {
                    if (p === 1) return 'Switch to 1st Half';
                    if (p === 2) return 'Switch to 2nd Half';
                    return `Switch to Overtime ${p - 2}`;
                }
                if (p <= 4) return `Switch to Quarter ${p}`;
                return `Switch to Overtime ${p - 4}`;
            },

            getDisplayedPeriods() {
                if (this.sport === 'volleyball') {
                    return [1, 2, 3, 4, 5];
                }
                const regCount = (this.periodFormat === 'halves') ? 2 : 4;
                let highest = regCount;

                if (this.currentPeriod > highest) {
                    highest = this.currentPeriod;
                }

                const maxScoreLen = Math.max(
                    Array.isArray(this.homePeriodScores) ? this.homePeriodScores.length : 0,
                    Array.isArray(this.awayPeriodScores) ? this.awayPeriodScores.length : 0
                );
                for (let i = regCount; i < maxScoreLen; i++) {
                    if ((this.homePeriodScores[i] || 0) > 0 || (this.awayPeriodScores[i] || 0) > 0) {
                        highest = Math.max(highest, i + 1);
                    }
                }

                if (Array.isArray(this.recentEvents)) {
                    for (const ev of this.recentEvents) {
                        const ep = Number(ev.period || 0);
                        if (ep > highest) {
                            highest = Math.max(highest, ep);
                        }
                    }
                }

                if (this.otPeriodsAdded) {
                    highest = Math.max(highest, regCount + this.otPeriodsAdded);
                }

                highest = Math.min(6, highest);

                const periods = [];
                for (let p = 1; p <= highest; p++) {
                    periods.push(p);
                }
                return periods;
            },

            canAddOvertime() {
                if (this.sport !== 'basketball') return false;
                const periods = this.getDisplayedPeriods();
                return periods.length < 6;
            },

            addOvertime() {
                if (this.sport !== 'basketball') return;
                const regCount = (this.periodFormat === 'halves') ? 2 : 4;
                const periods = this.getDisplayedPeriods();
                const highest = periods[periods.length - 1] || regCount;
                if (highest >= 6) {
                    this.pushError('Maximum periods reached.');
                    return;
                }
                const nextPeriod = highest + 1;
                this.otPeriodsAdded = Math.max(this.otPeriodsAdded || 0, nextPeriod - regCount);

                while (this.homePeriodScores.length < nextPeriod) this.homePeriodScores.push(0);
                while (this.awayPeriodScores.length < nextPeriod) this.awayPeriodScores.push(0);

                this.setPeriodFast(nextPeriod);
            },

            recalculateLocalEventsAndScores() {
                if (!Array.isArray(this.recentEvents)) return;

                // Active events: filter out any explicitly undone events
                const activeEvents = this.recentEvents.filter(e => !e.is_undone);

                // Sort chronological: oldest to newest
                // In this.recentEvents, newest event is unshifted to index 0.
                const indexed = activeEvents.map((ev, i) => ({ ev, i }));
                indexed.sort((a, b) => {
                    const seqA = Number(a.ev?.sequence ?? 0);
                    const seqB = Number(b.ev?.sequence ?? 0);
                    if (seqA && seqB && seqA !== seqB) return seqA - seqB;
                    return b.i - a.i; // preserve reverse recentEvents order
                });
                const chronological = indexed.map(item => item.ev);

                let runningHome = 0;
                let runningAway = 0;
                const minLen = this.sport === 'basketball' ? (this.periodFormat === 'halves' ? 2 : 4) : 5;
                const homePeriod = Array(minLen).fill(0);
                const awayPeriod = Array(minLen).fill(0);
                let currentHomeFouls = 0;
                let currentAwayFouls = 0;
                let homeTOFullUsed = 0;
                let homeTO30sUsed = 0;
                let awayTOFullUsed = 0;
                let awayTO30sUsed = 0;
                let homeTOVolleyUsed = 0;
                let awayTOVolleyUsed = 0;

                const newPlayerStats = { home: {}, away: {} };
                const ensureP = (sd, jsy) => {
                    const sideKey = (sd === 'away') ? 'away' : 'home';
                    const j = String(jsy ?? '').trim();
                    if (!newPlayerStats[sideKey][j]) {
                        newPlayerStats[sideKey][j] = {
                            points: 0,
                            fouls_pers: 0,
                            fouls_off: 0,
                            fouls_tech: 0,
                            fouls_flagrant: 0,
                            fouls_forced: 0,
                            total_fouls: 0,
                        };
                    }
                    return newPlayerStats[sideKey][j];
                };

                // Pre-populate court and bench players so their stats structures are ready
                ['home', 'away'].forEach(sd => {
                    const court = sd === 'home' ? this.homeCourt : this.awayCourt;
                    const bench = sd === 'home' ? this.homeBench : this.awayBench;
                    [...(court || []), ...(bench || [])].forEach(p => {
                        if (p && p.jersey_number) ensureP(sd, p.jersey_number);
                    });
                });

                for (const ev of chronological) {
                    let pts = (typeof ev.points === 'number' && !isNaN(ev.points)) ? ev.points : null;
                    const code = String(ev.action_code || '').trim().toUpperCase();
                    if (pts === null) {
                        const def = this.sport === 'basketball'
                            ? ProKeeperEngine.basketballActions[code]
                            : ProKeeperEngine.volleyballActions[code];
                        pts = Number(def?.points || 0);
                        ev.points = pts;
                    }
                    const p = Number(ev.period) || 1;
                    const pIdx = Math.max(0, p - 1);
                    const side = (ev.team_side === 'away') ? 'away' : 'home';

                    if (side === 'home') {
                        runningHome += pts;
                        while (homePeriod.length <= pIdx) homePeriod.push(0);
                        homePeriod[pIdx] = (homePeriod[pIdx] || 0) + pts;
                        // Team fouls: all fouls EXCEPT technicals count towards team bonus fouls (pers, off, flagrant)
                        if (['F', 'R', 'G', 'FL', 'FLAG'].includes(code) && p === this.currentPeriod) {
                            currentHomeFouls++;
                        }
                    } else {
                        runningAway += pts;
                        while (awayPeriod.length <= pIdx) awayPeriod.push(0);
                        awayPeriod[pIdx] = (awayPeriod[pIdx] || 0) + pts;
                        // Team fouls: all fouls EXCEPT technicals count towards team bonus fouls (pers, off, flagrant)
                        if (['F', 'R', 'G', 'FL', 'FLAG'].includes(code) && p === this.currentPeriod) {
                            currentAwayFouls++;
                        }
                    }

                    ev.home_score_after = runningHome;
                    ev.away_score_after = runningAway;

                    if (this.sport === 'basketball') {
                        const jsy = ev.jersey_number ? String(ev.jersey_number).trim() : null;
                        if (jsy) {
                            const st = ensureP(side, jsy);
                            if (pts > 0) st.points += pts;
                            if (code === 'F') {
                                st.fouls_pers++;
                                st.total_fouls++;
                            } else if (code === 'R') {
                                st.fouls_off++;
                                st.total_fouls++;
                            } else if (code === 'T') {
                                st.fouls_tech++;
                                st.total_fouls++;
                            } else if (['G', 'FL', 'FLAG'].includes(code)) {
                                st.fouls_flagrant++;
                                st.total_fouls++;
                            } else if (code === 'H') {
                                st.fouls_forced = (st.fouls_forced || 0) + 1;
                            }
                        }
                    }

                    if (code === 'TIMEOUT') {
                        const toType = ev.metadata?.timeout_type || 'full';
                        if (this.sport === 'volleyball') {
                            if (Number(p) === Number(this.currentPeriod)) {
                                if (side === 'home') homeTOVolleyUsed++;
                                else awayTOVolleyUsed++;
                            }
                        } else {
                            if (side === 'home') {
                                if (toType === '30s') homeTO30sUsed++;
                                else homeTOFullUsed++;
                            } else {
                                if (toType === '30s') awayTO30sUsed++;
                                else awayTOFullUsed++;
                            }
                        }
                    }
                }

                const targetLen = Math.max(minLen, this.currentPeriod, minLen + (this.otPeriodsAdded || 0));
                while (homePeriod.length < targetLen) homePeriod.push(0);
                while (awayPeriod.length < targetLen) awayPeriod.push(0);

                this.homeScore = Math.max(0, runningHome);
                this.awayScore = Math.max(0, runningAway);
                this.homePeriodScores = homePeriod;
                this.awayPeriodScores = awayPeriod;

                if (this.sport === 'volleyball') {
                    const allowedSet = Number(this.timeoutsPerSet || 2);
                    this.homeTimeoutsTaken = homeTOVolleyUsed;
                    this.awayTimeoutsTaken = awayTOVolleyUsed;
                    this.homeTimeouts = Math.max(0, allowedSet - homeTOVolleyUsed);
                    this.awayTimeouts = Math.max(0, allowedSet - awayTOVolleyUsed);
                } else if (this.sport === 'basketball') {
                    const regPeriods = (this.periodFormat === 'halves') ? 2 : 4;
                    const otPeriods = Math.max(0, this.currentPeriod - regPeriods);
                    const otAddition = otPeriods * Number(this.timeoutsOtAllowed || 1);
                    const allowedFull = Number(this.timeoutsFullAllowed || 3);
                    const allowed30s = Number(this.timeouts30sAllowed || 2) + otAddition;

                    this.homeFullTimeoutsTaken = homeTOFullUsed;
                    this.home30sTimeoutsTaken = homeTO30sUsed;
                    this.homeTimeoutsTaken = homeTOFullUsed + homeTO30sUsed;

                    this.awayFullTimeoutsTaken = awayTOFullUsed;
                    this.away30sTimeoutsTaken = awayTO30sUsed;
                    this.awayTimeoutsTaken = awayTOFullUsed + awayTO30sUsed;

                    this.homeFullTimeouts = Math.max(0, allowedFull - homeTOFullUsed);
                    this.home30sTimeouts = Math.max(0, allowed30s - homeTO30sUsed);
                    this.homeTimeouts = this.homeFullTimeouts + this.home30sTimeouts;

                    this.awayFullTimeouts = Math.max(0, allowedFull - awayTOFullUsed);
                    this.away30sTimeouts = Math.max(0, allowed30s - awayTO30sUsed);
                    this.awayTimeouts = this.awayFullTimeouts + this.away30sTimeouts;
                }

                if (this.sport === 'basketball') {
                    this.homeFouls = currentHomeFouls;
                    this.awayFouls = currentAwayFouls;
                    this.playerStats = newPlayerStats;

                    // Automatically dismiss active foul-out alert if undo restored them below the foul limit
                    if (this.attentionAlert && this.attentionAlert.type === 'foul_out') {
                        const alSide = this.attentionAlert.side;
                        const alJersey = this.attentionAlert.jersey;
                        if (alSide && alJersey) {
                            const curStat = this.getPlayerStat(alSide, alJersey);
                            if (curStat.total_fouls < (this.playerFoulLimit || 5)) {
                                this.dismissAttentionAlert();
                            }
                        }
                    }
                    // Automatically dismiss active bonus alert if undo restored fouls below the threshold
                    if (this.attentionAlert && (this.attentionAlert.type === 'bonus' || this.attentionAlert.type === 'double_bonus')) {
                        const alSide = this.attentionAlert.side;
                        if (alSide) {
                            const curFouls = alSide === 'home' ? this.homeFouls : this.awayFouls;
                            const thresh = this.attentionAlert.type === 'double_bonus' ? this.doubleBonusFoulThreshold : this.bonusFoulThreshold;
                            if (curFouls < thresh) {
                                this.dismissAttentionAlert();
                            }
                        }
                    }
                }
            },

            initPlayerStats(homeStats = [], awayStats = []) {
                if (!this.playerStats) this.playerStats = { home: {}, away: {} };
                const processList = (list, side) => {
                    if (!this.playerStats[side]) this.playerStats[side] = {};
                    for (const st of (list || [])) {
                        const j = String(st.jersey_number ?? '').trim();
                        if (!j) continue;
                        const pers = Number(st.fouls_pers || 0);
                        const off = Number(st.fouls_off || 0);
                        const tech = Number(st.fouls_tech || 0);
                        const flagrant = Number(st.fouls_flagrant || 0);
                        const forced = Number(st.fouls_forced || 0);
                        const total = Number(st.total_fouls || (pers + off + tech + flagrant));
                        this.playerStats[side][j] = {
                            points: Number(st.points || 0),
                            fouls_pers: pers,
                            fouls_off: off,
                            fouls_tech: tech,
                            fouls_flagrant: flagrant,
                            fouls_forced: forced,
                            total_fouls: total,
                        };
                    }
                };
                processList(homeStats, 'home');
                processList(awayStats, 'away');
            },

            getPlayerStat(side, jersey) {
                const j = String(jersey ?? '').trim();
                if (!this.playerStats) this.playerStats = { home: {}, away: {} };
                if (!this.playerStats[side]) this.playerStats[side] = {};
                if (!this.playerStats[side][j]) {
                    this.playerStats[side][j] = {
                        points: 0,
                        fouls_pers: 0,
                        fouls_off: 0,
                        fouls_tech: 0,
                        fouls_flagrant: 0,
                        fouls_forced: 0,
                        total_fouls: 0,
                    };
                }
                return this.playerStats[side][j];
            },

            saveManualEventFast() {
                const teamSide = this.newEvent.team_side || 'home';
                const jersey = this.newEvent.jersey_number ? String(this.newEvent.jersey_number).trim() : null;
                const actionCode = this.newEvent.action_code || 'NOTE';
                const period = Number(this.newEvent.period || this.currentPeriod);
                const points = Number(this.newEvent.points || 0);
                const clock = Number(this.newEvent.clock_seconds_remaining || 0);

                const def = this.sport === 'basketball'
                    ? (ProKeeperEngine.basketballActions[actionCode] || { name: actionCode, points: 0 })
                    : (ProKeeperEngine.volleyballActions[actionCode] || { name: actionCode, points: 0 });

                let playerName = this.newEvent.player_name;
                if (!playerName && jersey) {
                    const court = (teamSide === 'home' ? this.homeCourt : this.awayCourt) || [];
                    const bench = (teamSide === 'home' ? this.homeBench : this.awayBench) || [];
                    const found = [...court, ...bench].find(p => String(p.jersey_number) === jersey);
                    playerName = found ? found.player_name : `Player #${jersey}`;
                }

                const desc = this.newEvent.description || (
                    jersey
                        ? `${teamSide.toUpperCase()} #${jersey} ${playerName || ''}: ${def.name}${points > 0 ? ` (+${points} pts)` : ''}`
                        : `${teamSide.toUpperCase()}: ${def.name}${points > 0 ? ` (+${points} pts)` : ''}`
                );

                const mutId = 'mut_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5);
                const localEvent = {
                    id: mutId,
                    game_id: this.gameId,
                    sequence: (this.recentEvents[0]?.sequence || 0) + 1,
                    period: period,
                    clock_seconds_remaining: clock,
                    team_side: teamSide,
                    jersey_number: jersey,
                    player_name: playerName || 'Manual Entry',
                    action_code: actionCode,
                    action_type: def.type || 'manual_entry',
                    action_name: def.name || actionCode,
                    points: points,
                    home_score_after: this.homeScore + (teamSide === 'home' ? points : 0),
                    away_score_after: this.awayScore + (teamSide === 'away' ? points : 0),
                    description: desc,
                };

                this.recentEvents.unshift(localEvent);
                this.recalculateLocalEventsAndScores();
                let manualAlertTriggered = false;
                if (this.sport === 'basketball' && ['F', 'R', 'T', 'G', 'FL', 'FLAG'].includes(actionCode)) {
                    const st = jersey ? this.getPlayerStat(teamSide, jersey) : null;
                    const foulLimit = this.playerFoulLimit || 5;
                    const foulingTeamName = teamSide === 'home' ? this.homeTeamName : this.awayTeamName;
                    const shootingTeamName = teamSide === 'home' ? this.awayTeamName : this.homeTeamName;
                    const curTeamFouls = teamSide === 'home' ? this.homeFouls : this.awayFouls;
                    const bonusThresh = this.bonusFoulThreshold || (this.periodFormat === 'halves' ? 7 : 5);
                    const doubleBonusThresh = this.doubleBonusFoulThreshold || (this.periodFormat === 'halves' ? 10 : 5);

                    if (st && st.total_fouls >= foulLimit) {
                        manualAlertTriggered = true;
                        this.triggerAttentionAlert(
                            'foul_out',
                            'PLAYER FOULED OUT',
                            `#${jersey} ${playerName || ''} (${foulingTeamName}) has reached ${st.total_fouls} fouls and must be substituted.`,
                            'Disqualified from play. Immediate substitution required.',
                            60,
                            { side: teamSide, jersey, name: playerName, teamName: foulingTeamName }
                        );
                    } else if (doubleBonusThresh > bonusThresh && curTeamFouls >= doubleBonusThresh) {
                        manualAlertTriggered = true;
                        this.triggerAttentionAlert(
                            'double_bonus',
                            'DOUBLE BONUS ACTIVE',
                            `${foulingTeamName} is at ${curTeamFouls} team fouls in Period ${this.currentPeriod}.`,
                            `${shootingTeamName} shoots 2 Free Throws on all common fouls.`,
                            45,
                            { side: teamSide, teamName: foulingTeamName, fouls: curTeamFouls }
                        );
                    } else if (curTeamFouls >= bonusThresh) {
                        manualAlertTriggered = true;
                        const bonusType = this.periodFormat === 'halves' ? '1-and-1 Free Throws' : '2 Free Throws';
                        this.triggerAttentionAlert(
                            'bonus',
                            'TEAM BONUS ACTIVE',
                            `${foulingTeamName} is at ${curTeamFouls} team fouls in Period ${this.currentPeriod}.`,
                            `${shootingTeamName} shoots ${bonusType} on all common fouls.`,
                            45,
                            { side: teamSide, teamName: foulingTeamName, fouls: curTeamFouls }
                        );
                    }
                }

                if (!manualAlertTriggered) {
                    this.playSound(points > 0 ? 'score' : 'tap');
                    this.feedbackMessage = `Logged: ${desc}`;
                    this.feedbackType = 'success';
                }
                this.showAddEventModal = false;

                this.enqueueSync('createManualEvent', [{
                    team_side: teamSide,
                    jersey_number: jersey,
                    player_name: playerName,
                    action_code: actionCode,
                    period: period,
                    points: points,
                    clock_seconds_remaining: clock,
                    description: desc,
                }], 'stats', mutId);
            },

            filteredRecentEvents() {
                const query = (this.pbpSearchQuery || '').toLowerCase().trim();
                return (this.recentEvents || []).filter(e => {
                    if (this.pbpFilterPeriod !== 'all' && Number(e.period) !== Number(this.pbpFilterPeriod)) return false;
                    if (this.pbpFilterTeam !== 'all' && e.team_side !== this.pbpFilterTeam) return false;

                    if (this.pbpFilterCategory !== 'all') {
                        const def = this.sport === 'basketball'
                            ? ProKeeperEngine.basketballActions[e.action_code]
                            : ProKeeperEngine.volleyballActions[e.action_code];
                        const cat = def?.category || (['TIMEOUT', 'SCORE_ADJ', 'SUB', 'PERIOD', 'ROTATE', 'NOTE'].includes(e.action_code) ? 'administrative' : 'scoring');
                        if (cat !== this.pbpFilterCategory) return false;
                    }

                    if (query) {
                        const j = String(e.jersey_number || '').toLowerCase();
                        const p = String(e.player_name || '').toLowerCase();
                        const d = String(e.description || '').toLowerCase();
                        const a = String(e.action_name || '').toLowerCase();
                        if (!j.includes(query) && !p.includes(query) && !d.includes(query) && !a.includes(query)) {
                            return false;
                        }
                    }

                    return true;
                });
            },

            deleteEventFast(eventId) {
                if (!this.canExecute('deleteEvent_' + eventId, 250)) return;
                const idx = this.recentEvents.findIndex(e => String(e.id) === String(eventId));
                if (idx === -1) return;

                const event = this.recentEvents[idx];
                this.pushUndoSnapshot('delete_event', { event });

                // Remove from recentEvents array and recalculate running state directly from active play log
                this.recentEvents.splice(idx, 1);
                this.recalculateLocalEventsAndScores();

                this.playSound('tap');
                this.feedbackMessage = `Deleted: ${event.description || 'Play'}`;
                this.feedbackType = 'info';

                // Asynchronously sync to backend
                if (typeof event.id === 'number' || (!String(event.id).startsWith('local_') && !String(event.id).startsWith('mut_'))) {
                    this.enqueueSync('deleteGameEvent', [Number(event.id)], 'stats');
                }
            },

            startEditingEvent(event) {
                this.editingEvent = {
                    id: event.id,
                    team_side: event.team_side || 'home',
                    jersey_number: String(event.jersey_number ?? ''),
                    player_name: event.player_name || '',
                    action_code: event.action_code || 'X',
                    period: Number(event.period || this.currentPeriod),
                    points: Number(event.points || 0),
                    clock_seconds_remaining: Number(event.clock_seconds_remaining || 0),
                    description: event.description || '',
                };
                this.playSound('tap');
            },

            saveEditedEventFast() {
                if (!this.editingEvent) return;
                const eventId = this.editingEvent.id;
                const idx = this.recentEvents.findIndex(e => String(e.id) === String(eventId));
                if (idx === -1) return;

                const oldEvent = this.recentEvents[idx];
                const newTeamSide = this.editingEvent.team_side || oldEvent.team_side;
                const newJersey = this.editingEvent.jersey_number ? String(this.editingEvent.jersey_number).trim() : '';
                const newActionCode = this.editingEvent.action_code;
                const newPeriod = Number(this.editingEvent.period);
                const newClock = Number(this.editingEvent.clock_seconds_remaining || 0);

                let actionDef;
                if (this.sport === 'basketball') {
                    actionDef = ProKeeperEngine.basketballActions[newActionCode] || { name: newActionCode, points: 0 };
                } else {
                    actionDef = ProKeeperEngine.volleyballActions[newActionCode] || { name: newActionCode, points: 0 };
                }

                const newPts = (typeof this.editingEvent.points === 'number' && !isNaN(this.editingEvent.points))
                    ? Number(this.editingEvent.points)
                    : (Number(actionDef.points) || 0);

                let newPlayerName = oldEvent.player_name;
                if (newJersey && newJersey !== String(oldEvent.jersey_number || '')) {
                    const court = (newTeamSide === 'home' ? this.homeCourt : this.awayCourt) || [];
                    const bench = (newTeamSide === 'home' ? this.homeBench : this.awayBench) || [];
                    const found = [...court, ...bench].find(p => String(p.jersey_number) === newJersey);
                    newPlayerName = found ? found.player_name : `Player #${newJersey}`;
                }

                // Update local event object
                const customDesc = this.editingEvent.description ? this.editingEvent.description.trim() : null;
                const newDesc = customDesc || (
                    newJersey
                        ? `${newTeamSide.toUpperCase()} #${newJersey} ${newPlayerName || ''}: ${actionDef.name}${newPts > 0 ? ` (+${newPts} pts)` : ''}`
                        : `${newTeamSide.toUpperCase()}: ${actionDef.name}${newPts > 0 ? ` (+${newPts} pts)` : ''}`
                );

                this.recentEvents[idx] = {
                    ...oldEvent,
                    team_side: newTeamSide,
                    jersey_number: newJersey,
                    player_name: newPlayerName,
                    action_code: newActionCode,
                    action_name: actionDef.name,
                    points: newPts,
                    period: newPeriod,
                    clock_seconds_remaining: newClock,
                    description: newDesc,
                };

                this.recalculateLocalEventsAndScores();

                this.playSound(newPts > 0 ? 'score' : 'tap');
                this.feedbackMessage = `Updated: ${newDesc}`;
                this.feedbackType = 'success';
                this.editingEvent = null;

                if (typeof eventId === 'number' || (!String(eventId).startsWith('local_') && !String(eventId).startsWith('mut_'))) {
                    this.enqueueSync('updateGameEvent', [
                        Number(eventId),
                        newJersey,
                        newActionCode,
                        newPeriod,
                        newDesc,
                        newPts,
                        newTeamSide,
                        newClock
                    ], 'stats');
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

            quickSubForPlayer(side, jersey) {
                if (!this.canExecute('quickSubForPlayer', 150)) return;
                this.preSelectedOutSubs = { home: [], away: [] };
                this.preSelectedOutSubs[side] = [String(jersey)];
                this.openLineupModal();
                this.feedbackMessage = `Select incoming substitute for #${jersey}`;
                this.feedbackType = 'info';
            },

            openLineupModal() {
                if (!this.canExecute('openLineupModal', 150)) return;
                this.showLineupModal = true;
                this.showExecuteSubModal = false;
                this.selectedPlayer = null;
                this.benchSelectSide = null;
                this.playSound('tap');
            },

            openExecuteSubModal() {
                if (!this.canExecute('openExecuteSubModal', 150)) return;
                this.showLineupModal = false;
                this.showExecuteSubModal = true;
                this.selectedPlayer = null;
                this.benchSelectSide = null;
                if (this.preSelectedOutSubs && (this.preSelectedOutSubs.home?.length > 0 || this.preSelectedOutSubs.away?.length > 0)) {
                    this.selectedOutSubs = { 
                        home: [...(this.preSelectedOutSubs.home || [])], 
                        away: [...(this.preSelectedOutSubs.away || [])] 
                    };
                    this.preSelectedOutSubs = null;
                } else {
                    this.selectedOutSubs = { home: [], away: [] };
                }
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
                    this.triggerAttentionAlert(
                        'error',
                        'SUBSTITUTION INCOMPLETE',
                        'Please select the required number of outgoing players.',
                        'Every incoming player must be matched with an outgoing court player.',
                        10
                    );
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
                            this.enqueueSync('setAndExecuteSub', [side, outJ, inJ], 'lineup');
                        }
                    }
                });

                if (executedPairs.length > 0) {
                    this.pushUndoSnapshot('bulk_subs', { executedPairs });
                    this.feedbackMessage = `${executedPairs.length} substitution(s) entered the game`;
                    this.feedbackType = 'success';
                    this.playSound('score');

                    // If the player who fouled out was subbed out, dismiss the attention alert
                    if (this.attentionAlert && this.attentionAlert.type === 'foul_out') {
                        const outFouled = String(this.attentionAlert.jersey);
                        if (executedPairs.some(p => String(p.outJ) === outFouled)) {
                            this.dismissAttentionAlert();
                        }
                    }
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

            cancelSubOperation() {
                this.pendingSubs = { home: [], away: [] };
                this.selectedOutSubs = { home: [], away: [] };
                this.preSelectedOutSubs = null;
                this.showLineupModal = false;
                this.showExecuteSubModal = false;
                this.feedbackMessage = 'Substitution canceled';
                this.feedbackType = 'info';
                this.playSound('tap');
            },

            keepSubsWaiting() {
                this.showLineupModal = false;
                this.showExecuteSubModal = false;
                this.selectedOutSubs = { home: [], away: [] };
                const count = this.pendingSubCount();
                if (count > 0) {
                    this.feedbackMessage = `${count} substitution(s) waiting at table`;
                    this.feedbackType = 'info';
                }
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

            // Client is God State Tracking & Async Background Sync Pipeline
            deviceId: (function() {
                try {
                    let id = sessionStorage.getItem('prokeeper_device_id');
                    if (!id) {
                        id = 'dev_' + Date.now().toString(36) + '_' + Math.random().toString(36).substr(2, 6);
                        sessionStorage.setItem('prokeeper_device_id', id);
                    }
                    return id;
                } catch (e) {
                    return 'dev_' + Date.now().toString(36);
                }
            })(),
            deviceLabel: (function() {
                try {
                    const isMobile = window.innerWidth < 768;
                    return isMobile ? 'Mobile Device' : 'Main Console';
                } catch (e) {
                    return 'Device';
                }
            })(),
            activeDeviceCount: 1,
            activeDevices: [],
            activeConflicts: [],
            showConflictDrawer: false,
            lastLocalActionTime: 0,
            localRevision: 0,
            syncQueue: [],
            syncStatus: 'synced', // 'synced' | 'syncing' | 'offline'
            isSyncing: false,

            isQueuePending() {
                return Array.isArray(this.syncQueue) && this.syncQueue.length > 0;
            },

            getAuthoritativeSnapshot() {
                return {
                    homeScore: Number(this.homeScore || 0),
                    awayScore: Number(this.awayScore || 0),
                    homePeriodScores: Array.isArray(this.homePeriodScores) ? [...this.homePeriodScores] : [],
                    awayPeriodScores: Array.isArray(this.awayPeriodScores) ? [...this.awayPeriodScores] : [],
                    currentPeriod: Number(this.currentPeriod || 1),
                    possession: this.possession || 'home',
                    server: this.server || 'home',
                    homeFouls: Number(this.homeFouls || 0),
                    awayFouls: Number(this.awayFouls || 0),
                    homeTimeouts: Number(this.homeTimeouts || 0),
                    awayTimeouts: Number(this.awayTimeouts || 0),
                    homeFullTimeouts: Number(this.homeFullTimeouts || 0),
                    home30sTimeouts: Number(this.home30sTimeouts || 0),
                    awayFullTimeouts: Number(this.awayFullTimeouts || 0),
                    away30sTimeouts: Number(this.away30sTimeouts || 0),
                    homeTimeoutsTaken: Number(this.homeTimeoutsTaken || 0),
                    awayTimeoutsTaken: Number(this.awayTimeoutsTaken || 0),
                    homeFullTimeoutsTaken: Number(this.homeFullTimeoutsTaken || 0),
                    home30sTimeoutsTaken: Number(this.home30sTimeoutsTaken || 0),
                    awayFullTimeoutsTaken: Number(this.awayFullTimeoutsTaken || 0),
                    away30sTimeoutsTaken: Number(this.away30sTimeoutsTaken || 0),
                    homeRotation: Number(this.homeRotation || 1),
                    awayRotation: Number(this.awayRotation || 1),
                };
            },

            hotPatchLineups(data) {
                if (!data) return;

                const mergeList = (targetList, sourceList) => {
                    if (!Array.isArray(targetList) || !Array.isArray(sourceList)) return;
                    sourceList.forEach(src => {
                        const found = targetList.find(p => (p.id && src.id && Number(p.id) === Number(src.id)) || (String(p.jersey_number) === String(src.jersey_number)));
                        if (found) {
                            if (typeof src.player_name !== 'undefined') found.player_name = src.player_name;
                            if (typeof src.jersey_number !== 'undefined') found.jersey_number = src.jersey_number;
                            if (typeof src.position !== 'undefined') found.position = src.position;
                        }
                    });
                };

                if (this.isQueuePending()) {
                    // Hot-patch player metadata (names, jerseys, positions, new bench players) without blowing away court lineup state or typing focus
                    if (data.homeCourt) mergeList(this.homeCourt, data.homeCourt);
                    if (data.awayCourt) mergeList(this.awayCourt, data.awayCourt);
                    if (data.homeBench) {
                        mergeList(this.homeBench, data.homeBench);
                        data.homeBench.forEach(src => {
                            const exists = (this.homeBench || []).some(p => (p.id && src.id && Number(p.id) === Number(src.id)) || (String(p.jersey_number) === String(src.jersey_number))) ||
                                           (this.homeCourt || []).some(p => (p.id && src.id && Number(p.id) === Number(src.id)) || (String(p.jersey_number) === String(src.jersey_number)));
                            if (!exists) this.homeBench.push(JSON.parse(JSON.stringify(src)));
                        });
                    }
                    if (data.awayBench) {
                        mergeList(this.awayBench, data.awayBench);
                        data.awayBench.forEach(src => {
                            const exists = (this.awayBench || []).some(p => (p.id && src.id && Number(p.id) === Number(src.id)) || (String(p.jersey_number) === String(src.jersey_number))) ||
                                           (this.awayCourt || []).some(p => (p.id && src.id && Number(p.id) === Number(src.id)) || (String(p.jersey_number) === String(src.jersey_number)));
                            if (!exists) this.awayBench.push(JSON.parse(JSON.stringify(src)));
                        });
                    }
                } else {
                    if (data.homeCourt) this.homeCourt = JSON.parse(JSON.stringify(data.homeCourt));
                    if (data.awayCourt) this.awayCourt = JSON.parse(JSON.stringify(data.awayCourt));
                    if (data.homeBench) this.homeBench = JSON.parse(JSON.stringify(data.homeBench));
                    if (data.awayBench) this.awayBench = JSON.parse(JSON.stringify(data.awayBench));
                }
                this.saveLocalState();
            },

            sendHeartbeat() {
                if (this.$wire && typeof this.$wire.heartbeatDevice === 'function') {
                    const domain = (this.isEditingRoster || this.showRosterModal) ? 'roster' : (this.statInput ? 'stat' : 'general');
                    this.$wire.heartbeatDevice(this.deviceId, this.deviceLabel, domain).catch(() => {});
                }
            },

            async resolveConflict(conflictId, resolution) {
                if (this.$wire && typeof this.$wire.resolveConflict === 'function') {
                    await this.$wire.resolveConflict(conflictId, resolution);
                }
                this.activeConflicts = (this.activeConflicts || []).filter(c => c.id !== conflictId);
                if (this.activeConflicts.length === 0) {
                    this.showConflictDrawer = false;
                }
            },

            saveLocalState() {
                try {
                    const payload = {
                        version: this.localRevision,
                        savedAt: Date.now(),
                        snapshot: this.getAuthoritativeSnapshot(),
                        homeCourt: this.homeCourt,
                        awayCourt: this.awayCourt,
                        homeBench: this.homeBench,
                        awayBench: this.awayBench,
                        playerStats: this.playerStats,
                        recentEvents: this.recentEvents || [],
                    };
                    localStorage.setItem(`prokeeper_state_${this.gameId}`, JSON.stringify(payload));
                } catch (e) {}
            },

            loadLocalState() {
                try {
                    const saved = localStorage.getItem(`prokeeper_state_${this.gameId}`);
                    if (saved) {
                        const parsed = JSON.parse(saved);
                        if (parsed && parsed.snapshot && (Date.now() - parsed.savedAt < 86400000)) { // within 24h
                            const s = parsed.snapshot;
                            const hasActiveQueue = Array.isArray(this.syncQueue) && this.syncQueue.length > 0;

                            // FIDELITY GUARANTEE: Server baseline from play log is ALWAYS authoritative when queue is empty!
                            if (hasActiveQueue) {
                                if (typeof s.homeScore !== 'undefined') this.homeScore = s.homeScore;
                                if (typeof s.awayScore !== 'undefined') this.awayScore = s.awayScore;
                                if (Array.isArray(s.homePeriodScores) && s.homePeriodScores.length > 0) this.homePeriodScores = [...s.homePeriodScores];
                                if (Array.isArray(s.awayPeriodScores) && s.awayPeriodScores.length > 0) this.awayPeriodScores = [...s.awayPeriodScores];
                                if (parsed.playerStats && typeof parsed.playerStats === 'object' && Object.keys(parsed.playerStats).length > 0) {
                                    this.playerStats = parsed.playerStats;
                                }
                            } else {
                                this.homeScore = Number(config.homeScore || 0);
                                this.awayScore = Number(config.awayScore || 0);
                                const defaultLen = (config.periodFormat === 'halves') ? 2 : (sport === 'volleyball' ? 5 : 4);
                                const defScores = Array(defaultLen).fill(0);
                                this.homePeriodScores = Array.isArray(config.homePeriodScores) ? [...config.homePeriodScores] : [...defScores];
                                this.awayPeriodScores = Array.isArray(config.awayPeriodScores) ? [...config.awayPeriodScores] : [...defScores];
                            }

                            if (typeof s.currentPeriod !== 'undefined' && s.currentPeriod >= (config.currentPeriod || 1)) {
                                this.currentPeriod = s.currentPeriod;
                                this.periodName = this.formatPeriodName(this.currentPeriod);
                            }
                            if (s.possession) this.possession = s.possession;
                            if (s.server) this.server = s.server;
                            if (hasActiveQueue) {
                                if (typeof s.homeFouls !== 'undefined') this.homeFouls = s.homeFouls;
                                if (typeof s.awayFouls !== 'undefined') this.awayFouls = s.awayFouls;
                            } else {
                                this.homeFouls = Number(config.homeFouls || 0);
                                this.awayFouls = Number(config.awayFouls || 0);
                            }
                            if (typeof s.homeTimeouts !== 'undefined') this.homeTimeouts = s.homeTimeouts;
                            if (typeof s.awayTimeouts !== 'undefined') this.awayTimeouts = s.awayTimeouts;
                            if (typeof s.homeFullTimeouts !== 'undefined') this.homeFullTimeouts = s.homeFullTimeouts;
                            if (typeof s.home30sTimeouts !== 'undefined') this.home30sTimeouts = s.home30sTimeouts;
                            if (typeof s.awayFullTimeouts !== 'undefined') this.awayFullTimeouts = s.awayFullTimeouts;
                            if (typeof s.away30sTimeouts !== 'undefined') this.away30sTimeouts = s.away30sTimeouts;
                            if (typeof s.homeTimeoutsTaken !== 'undefined') this.homeTimeoutsTaken = s.homeTimeoutsTaken;
                            if (typeof s.awayTimeoutsTaken !== 'undefined') this.awayTimeoutsTaken = s.awayTimeoutsTaken;
                            if (typeof s.homeFullTimeoutsTaken !== 'undefined') this.homeFullTimeoutsTaken = s.homeFullTimeoutsTaken;
                            if (typeof s.home30sTimeoutsTaken !== 'undefined') this.home30sTimeoutsTaken = s.home30sTimeoutsTaken;
                            if (typeof s.awayFullTimeoutsTaken !== 'undefined') this.awayFullTimeoutsTaken = s.awayFullTimeoutsTaken;
                            if (typeof s.away30sTimeoutsTaken !== 'undefined') this.away30sTimeoutsTaken = s.away30sTimeoutsTaken;
                            if (typeof s.homeRotation !== 'undefined') this.homeRotation = s.homeRotation;
                            if (typeof s.awayRotation !== 'undefined') this.awayRotation = s.awayRotation;
                            if (parsed.homeCourt && Array.isArray(parsed.homeCourt) && parsed.homeCourt.length > 0) this.homeCourt = parsed.homeCourt;
                            if (parsed.awayCourt && Array.isArray(parsed.awayCourt) && parsed.awayCourt.length > 0) this.awayCourt = parsed.awayCourt;
                            if (parsed.homeBench && Array.isArray(parsed.homeBench)) this.homeBench = parsed.homeBench;
                            if (parsed.awayBench && Array.isArray(parsed.awayBench)) this.awayBench = parsed.awayBench;

                            // For events: server config.recentEvents is authoritative for database events.
                            // Only restore uncommitted local mutations whose IDs are STILL actively waiting in syncQueue!
                            const pendingMutIds = new Set((this.syncQueue || []).map(item => String(item.id)));
                            if (parsed.recentEvents && Array.isArray(parsed.recentEvents) && pendingMutIds.size > 0) {
                                const localPending = parsed.recentEvents.filter(e => pendingMutIds.has(String(e.id)));
                                const existingIds = new Set(this.recentEvents.map(e => String(e.id)));
                                const toAdd = localPending.filter(e => !existingIds.has(String(e.id)));
                                if (toAdd.length > 0) {
                                    this.recentEvents = [...toAdd, ...this.recentEvents];
                                }
                            }
                            this.localRevision = parsed.version || 0;
                            this.recalculateLocalEventsAndScores();
                        }
                    }
                } catch (e) {}
            },

            init() {
                window._activeOperator = this;
                this.updateCssVariables();
                this.loadQueue();
                this.loadLocalState();
                this.loadErrorQueue();

                this.pruneExpiredErrors();
                if (this._errorPruneInterval) clearInterval(this._errorPruneInterval);
                this._errorPruneInterval = setInterval(() => {
                    this.pruneExpiredErrors();
                }, 1000);

                this._operatorErrorListener = (event) => {
                    const data = event.detail?.[0] || event.detail;
                    const msg = typeof data === 'string' ? data : (data?.message || 'Error occurred');
                    this.pushError(msg);
                };
                window.addEventListener('operator-error', this._operatorErrorListener);

                if (typeof this.$watch === 'function') {
                    this.$watch('feedbackType', (newVal) => {
                        if (newVal === 'error') {
                            if (this.feedbackMessage && this.feedbackMessage !== 'Ready') {
                                this.pushError(this.feedbackMessage);
                            }
                            this.feedbackType = 'info';
                            this.feedbackMessage = 'Ready';
                        }
                    });

                    if (this.$wire) {
                        this.$watch('$wire.feedbackMessage', (newVal) => {
                            if (!newVal) return;
                            if (this.$wire.feedbackType === 'error') {
                                this.pushError(newVal);
                            }
                        });
                    }
                }

                if (this.sport === 'basketball') {
                    this.initPlayerStats(config.homeStats || [], config.awayStats || []);
                }
                this.recalculateLocalEventsAndScores();
                this.syncClockTimer();

                // Real-Time Reverb WebSocket Listener
                if (typeof window !== 'undefined' && window.Echo && this.gameId) {
                    try {
                        window.Echo.channel(`game.${this.gameId}`)
                            .listen('.game.event', (payload) => {
                                this.handleRemoteGameEvent(payload);
                            });
                    } catch (e) {
                        console.warn('Echo channel setup notice:', e);
                    }
                }

                this._colorsListener = (event) => {
                    const data = event.detail?.[0] || event.detail;
                    if (data) {
                        if (data.homeColor) this.homeTeamColor = data.homeColor;
                        if (data.awayColor) this.awayTeamColor = data.awayColor;
                        if (typeof data.broadcastFlip !== 'undefined') this.broadcastFlip = data.broadcastFlip;
                        this.updateCssVariables();
                    }
                };
                window.addEventListener('game-colors-updated', this._colorsListener);

                this._lineupsListener = (event) => {
                    const data = event.detail?.[0] || event.detail;
                    if (data) {
                        this.hotPatchLineups(data);
                    }
                };
                window.addEventListener('lineups-updated', this._lineupsListener);

                this._presenceListener = (event) => {
                    const data = event.detail?.[0] || event.detail;
                    if (data) {
                        if (typeof data.activeDeviceCount !== 'undefined') this.activeDeviceCount = data.activeDeviceCount;
                        if (data.devices) this.activeDevices = data.devices;
                        if (data.conflicts) this.activeConflicts = data.conflicts;
                    }
                };
                window.addEventListener('presence-updated', this._presenceListener);

                this._conflictsListener = (event) => {
                    const data = event.detail?.[0] || event.detail;
                    if (data && data.conflicts) {
                        this.activeConflicts = data.conflicts;
                    }
                };
                window.addEventListener('conflicts-updated', this._conflictsListener);

                this._gameStateListener = (event) => {
                    const data = event.detail?.[0] || event.detail;
                    if (data) {
                        if (data.conflicts) this.activeConflicts = data.conflicts;
                        if (typeof data.activeDeviceCount !== 'undefined') this.activeDeviceCount = data.activeDeviceCount;

                        if (data.recentEvents) {
                            const serverEvents = JSON.parse(JSON.stringify(data.recentEvents));
                            if (this.syncQueue.length === 0) {
                                // Zero pending mutations: server play log is 100% authoritative
                                this.recentEvents = serverEvents;
                            } else {
                                // Only retain local events whose mutation ID is still actively pending in syncQueue
                                const pendingMutIds = new Set(this.syncQueue.map(item => String(item.id)));
                                const toKeep = (this.recentEvents || []).filter(e => pendingMutIds.has(String(e.id)));
                                this.recentEvents = [...toKeep, ...serverEvents];
                            }
                            this.recalculateLocalEventsAndScores();
                        }

                        // Check if local score has suffered an unnatural doubling or gross drift (>10 pts) vs server events ledger
                        const scoreDivergence = typeof data.homeScore !== 'undefined' && typeof data.awayScore !== 'undefined' &&
                            (Math.abs(Number(data.homeScore) - Number(this.homeScore)) > 10 || Math.abs(Number(data.awayScore) - Number(this.awayScore)) > 10);

                        if (data.forceAuthoritativeReset || scoreDivergence) {
                            // Recover immediately from stale or doubled cache: accept authoritative event totals
                            if (typeof data.homeScore !== 'undefined') this.homeScore = Number(data.homeScore);
                            if (typeof data.awayScore !== 'undefined') this.awayScore = Number(data.awayScore);
                            if (data.homePeriodScores) this.homePeriodScores = JSON.parse(JSON.stringify(data.homePeriodScores));
                            if (data.awayPeriodScores) this.awayPeriodScores = JSON.parse(JSON.stringify(data.awayPeriodScores));
                            this.syncQueue = [];
                            this.saveQueue();
                            try { localStorage.removeItem(`prokeeper_state_${this.gameId}`); } catch(e) {}
                        } else if (this.isQueuePending()) {
                            // Still hot-patch lineups/metadata if provided
                            if (data.homeCourt || data.awayCourt || data.homeBench || data.awayBench) {
                                this.hotPatchLineups(data);
                            }
                            return;
                        }

                        if (typeof data.homeScore !== 'undefined') this.homeScore = Number(data.homeScore);
                        if (typeof data.awayScore !== 'undefined') this.awayScore = Number(data.awayScore);
                        if (data.homePeriodScores) this.homePeriodScores = JSON.parse(JSON.stringify(data.homePeriodScores));
                        if (data.awayPeriodScores) this.awayPeriodScores = JSON.parse(JSON.stringify(data.awayPeriodScores));
                        if (typeof data.currentPeriod !== 'undefined') this.currentPeriod = Number(data.currentPeriod);
                        if (data.periodName) this.periodName = data.periodName;
                        if (data.possession) this.possession = data.possession;
                        if (data.server) this.server = data.server;
                        if (typeof data.homeFouls !== 'undefined') this.homeFouls = Number(data.homeFouls);
                        if (typeof data.awayFouls !== 'undefined') this.awayFouls = Number(data.awayFouls);
                        if (typeof data.homeTimeouts !== 'undefined') this.homeTimeouts = Number(data.homeTimeouts);
                        if (typeof data.awayTimeouts !== 'undefined') this.awayTimeouts = Number(data.awayTimeouts);
                        if (typeof data.homeFullTimeouts !== 'undefined') this.homeFullTimeouts = Number(data.homeFullTimeouts);
                        if (typeof data.home30sTimeouts !== 'undefined') this.home30sTimeouts = Number(data.home30sTimeouts);
                        if (typeof data.awayFullTimeouts !== 'undefined') this.awayFullTimeouts = Number(data.awayFullTimeouts);
                        if (typeof data.away30sTimeouts !== 'undefined') this.away30sTimeouts = Number(data.away30sTimeouts);
                        if (typeof data.homeTimeoutsTaken !== 'undefined') this.homeTimeoutsTaken = Number(data.homeTimeoutsTaken);
                        if (typeof data.awayTimeoutsTaken !== 'undefined') this.awayTimeoutsTaken = Number(data.awayTimeoutsTaken);
                        if (typeof data.homeFullTimeoutsTaken !== 'undefined') this.homeFullTimeoutsTaken = Number(data.homeFullTimeoutsTaken);
                        if (typeof data.home30sTimeoutsTaken !== 'undefined') this.home30sTimeoutsTaken = Number(data.home30sTimeoutsTaken);
                        if (typeof data.awayFullTimeoutsTaken !== 'undefined') this.awayFullTimeoutsTaken = Number(data.awayFullTimeoutsTaken);
                        if (typeof data.away30sTimeoutsTaken !== 'undefined') this.away30sTimeoutsTaken = Number(data.away30sTimeoutsTaken);
                        if (typeof data.homeRotation !== 'undefined') this.homeRotation = Number(data.homeRotation);
                        if (typeof data.awayRotation !== 'undefined') this.awayRotation = Number(data.awayRotation);
                        if (data.homeCourt || data.awayCourt || data.homeBench || data.awayBench) {
                            this.hotPatchLineups(data);
                        }
                        if (this.sport === 'basketball' && (data.homeStats || data.awayStats)) {
                            this.initPlayerStats(data.homeStats, data.awayStats);
                        }
                        this.recalculateLocalEventsAndScores();
                        if (data.feedbackMessage) {
                            if (data.feedbackType === 'error') {
                                this.pushError(data.feedbackMessage);
                            } else {
                                this.feedbackMessage = data.feedbackMessage;
                                if (data.feedbackType) this.feedbackType = data.feedbackType;
                            }
                        }
                        this.saveLocalState();
                    }
                };
                window.addEventListener('game-state-updated', this._gameStateListener);

                if (window._operatorSyncInterval) clearInterval(window._operatorSyncInterval);
                window._operatorSyncInterval = setInterval(() => {
                    if (this.syncQueue.length > 0 && !this.isSyncing) {
                        this.flushQueue();
                    }
                }, 2000);

                this.sendHeartbeat();
                if (window._operatorHeartbeatInterval) clearInterval(window._operatorHeartbeatInterval);
                window._operatorHeartbeatInterval = setInterval(() => {
                    this.sendHeartbeat();
                }, 5000);
            },

            destroy() {
                if (this.alertFlashTimer) {
                    clearTimeout(this.alertFlashTimer);
                    this.alertFlashTimer = null;
                }
                if (this.alertCountdownTimer) {
                    clearInterval(this.alertCountdownTimer);
                    this.alertCountdownTimer = null;
                }
                if (window._activeOperator === this) {
                    window._activeOperator = null;
                }
                if (this._colorsListener) {
                    window.removeEventListener('game-colors-updated', this._colorsListener);
                }
                if (this._lineupsListener) {
                    window.removeEventListener('lineups-updated', this._lineupsListener);
                }
                if (this._presenceListener) {
                    window.removeEventListener('presence-updated', this._presenceListener);
                }
                if (this._conflictsListener) {
                    window.removeEventListener('conflicts-updated', this._conflictsListener);
                }
                if (this._gameStateListener) {
                    window.removeEventListener('game-state-updated', this._gameStateListener);
                }
                if (this._operatorErrorListener) {
                    window.removeEventListener('operator-error', this._operatorErrorListener);
                }
                if (this._errorHoverCloseTimer) {
                    clearTimeout(this._errorHoverCloseTimer);
                    this._errorHoverCloseTimer = null;
                }
                if (this._errorPruneInterval) {
                    clearInterval(this._errorPruneInterval);
                    this._errorPruneInterval = null;
                }
                if (this._errorTimeouts) {
                    Object.values(this._errorTimeouts).forEach(t => clearTimeout(t));
                    this._errorTimeouts = {};
                }
                if (window._operatorSyncInterval) {
                    clearInterval(window._operatorSyncInterval);
                }
                if (window._operatorHeartbeatInterval) {
                    clearInterval(window._operatorHeartbeatInterval);
                }
            },

            updateCssVariables() {
                if (this.homeTeamColor) {
                    document.documentElement.style.setProperty('--home-team-color', this.homeTeamColor);
                }
                if (this.awayTeamColor) {
                    document.documentElement.style.setProperty('--away-team-color', this.awayTeamColor);
                }
            },

            toggleFlipCourt() {
                this.isFlipped = !this.isFlipped;
                try {
                    localStorage.setItem('prokeeper_flipped_' + this.gameId, this.isFlipped);
                } catch (e) {}
                const leftName = this.isFlipped ? this.awayTeamName : this.homeTeamName;
                const rightName = this.isFlipped ? this.homeTeamName : this.awayTeamName;
                this.feedbackMessage = `Court Flipped: ${leftName} (Left) / ${rightName} (Right)`;
                this.feedbackType = 'info';
                this.playSound('tap');
            },

            // Universal instant escape: exits any modal, sheet, or overlay and returns directly to the court view
            escapeToMainDashboard() {
                if (document.activeElement && typeof document.activeElement.blur === 'function') {
                    document.activeElement.blur();
                }
                this.dismissAttentionAlert();
                if (this.showLineupModal || this.showExecuteSubModal) {
                    this.pendingSubs = { home: [], away: [] };
                    this.preSelectedOutSubs = null;
                    this.feedbackMessage = 'Substitution canceled';
                    this.feedbackType = 'info';
                }
                this.selectedPlayer = null;
                this.benchSelectSide = null;
                this.pendingTimeout = null;
                this.showLineupModal = false;
                this.showExecuteSubModal = false;
                this.showPlayByPlayModal = false;
                this.showAddEventModal = false;
                this.showRosterModal = false;
                this.editingEvent = null;
                this.showSubSheet = false;
                this.showErrorList = false;
                this.closeAssistWindow();
                if (this._errorHoverCloseTimer) {
                    clearTimeout(this._errorHoverCloseTimer);
                    this._errorHoverCloseTimer = null;
                }
                if (this._errorPruneInterval) {
                    clearInterval(this._errorPruneInterval);
                    this._errorPruneInterval = null;
                }
                if (this._errorTimeouts) {
                    Object.values(this._errorTimeouts).forEach(t => clearTimeout(t));
                    this._errorTimeouts = {};
                }
                this.selectedOutSubs = { home: [], away: [] };
                this.jerseyBuffer = '';
                if (this.bufferTimeout) {
                    clearTimeout(this.bufferTimeout);
                    this.bufferTimeout = null;
                }

                // If Livewire Modals (Game Setup / Details, Roster, Signatures) are open, close them instantly
                try {
                    const wireEls = document.querySelectorAll('[wire\\:id]');
                    wireEls.forEach(el => {
                        if (window.Livewire) {
                            const comp = window.Livewire.find(el.getAttribute('wire:id'));
                            if (comp && typeof comp.get === 'function') {
                                if (comp.get('showGameDetailsModal') === true) {
                                    comp.call('closeGameDetailsModal');
                                }
                                if (comp.get('showRosterModal') === true) {
                                    comp.call('closeRosterModal');
                                }
                                if (comp.get('showSignatureModal') === true) {
                                    comp.call('closeSignatureModal');
                                }
                            }
                        }
                    });
                } catch (e) {}

                // Broadcast to any layout modals (such as operator help modal, share modal)
                window.dispatchEvent(new CustomEvent('close-all-modals'));
                this.playSound('tap');
            },

            // 0ms Roster Modal Controls
            openRosterModal(teamSide = 'home') {
                this.rosterModalTeam = teamSide;
                this.showRosterModal = true;
                this.playSound('tap');
            },

            closeRosterModal() {
                this.showRosterModal = false;
                this.playSound('tap');
            },

            // Game Clock Display & Controls
            formatClock(seconds) {
                const s = Math.max(0, Number(seconds || 0));
                const min = Math.floor(s / 60);
                const sec = Math.floor(s % 60);
                return `${String(min).padStart(2, '0')}:${String(sec).padStart(2, '0')}`;
            },

            syncClockTimer() {
                if (this.clockRunning) {
                    if (!this.clockInterval) {
                        this.clockInterval = setInterval(() => {
                            if (this.clockSeconds > 0) {
                                this.clockSeconds--;
                            } else {
                                this.clockRunning = false;
                                clearInterval(this.clockInterval);
                                this.clockInterval = null;
                                this.playSound('tap');
                            }
                        }, 1000);
                    }
                } else {
                    if (this.clockInterval) {
                        clearInterval(this.clockInterval);
                        this.clockInterval = null;
                    }
                }
            },

            toggleClockFast() {
                this.clockRunning = !this.clockRunning;
                this.syncClockTimer();
                this.playSound('tap');
                this.feedbackMessage = this.clockRunning ? 'Clock Running' : 'Clock Stopped';
                this.feedbackType = 'info';

                if (window.Livewire) {
                    const el = document.querySelector('[wire\\:id]');
                    if (el) {
                        const comp = window.Livewire.find(el.getAttribute('wire:id'));
                        if (comp && typeof comp.toggleClock === 'function') {
                            comp.toggleClock(this.clockRunning, this.clockSeconds);
                        }
                    }
                }
            },

            syncClockFast(seconds) {
                this.clockSeconds = Math.max(0, Number(seconds || 0));
                this.playSound('tap');
                this.feedbackMessage = `Clock synced to ${this.formatClock(this.clockSeconds)}`;
                this.feedbackType = 'info';

                if (window.Livewire) {
                    const el = document.querySelector('[wire\\:id]');
                    if (el) {
                        const comp = window.Livewire.find(el.getAttribute('wire:id'));
                        if (comp && typeof comp.syncClock === 'function') {
                            comp.syncClock(this.clockSeconds);
                        }
                    }
                }
            },

            parseClockShortcut(input) {
                if (!input || typeof input !== 'string') return null;
                const trimmed = input.trim().toLowerCase();

                // Format 1: tMMSS (e.g. t2000, t0800, t0530, t0005)
                const m1 = trimmed.match(/^t([0-9]{2})([0-9]{2})$/);
                if (m1) {
                    return (parseInt(m1[1], 10) * 60) + parseInt(m1[2], 10);
                }
                // Format 1b: tMMSSf (5 digits e.g. t00055 -> 5.5s -> 5s)
                const m1b = trimmed.match(/^t([0-9]{2})([0-9]{2})([0-9])$/);
                if (m1b) {
                    return (parseInt(m1b[1], 10) * 60) + parseInt(m1b[2], 10);
                }
                // Format 2: tM:SS or tMM:SS (e.g. t8:00, t20:00, t0:30)
                const m2 = trimmed.match(/^t([0-9]{1,2}):([0-9]{2})$/);
                if (m2) {
                    return (parseInt(m2[1], 10) * 60) + parseInt(m2[2], 10);
                }
                // Format 3: tSS (e.g. t45 -> 45s)
                const m3 = trimmed.match(/^t([0-9]{1,2})$/);
                if (m3) {
                    return parseInt(m3[1], 10);
                }
                return null;
            },

            handleRemoteGameEvent(payload) {
                if (!payload || !payload.gameState) return;

                if (payload.clientUuid && this._optimisticUuids && this._optimisticUuids.has(payload.clientUuid)) {
                    this._optimisticUuids.delete(payload.clientUuid);
                    return;
                }

                const s = payload.gameState;
                if (typeof s.homeScore !== 'undefined') this.homeScore = s.homeScore;
                if (typeof s.awayScore !== 'undefined') this.awayScore = s.awayScore;
                if (s.homePeriodScores) this.homePeriodScores = [...s.homePeriodScores];
                if (s.awayPeriodScores) this.awayPeriodScores = [...s.awayPeriodScores];
                if (typeof s.homeFouls !== 'undefined') this.homeFouls = s.homeFouls;
                if (typeof s.awayFouls !== 'undefined') this.awayFouls = s.awayFouls;
                if (typeof s.homeTimeouts !== 'undefined') this.homeTimeouts = s.homeTimeouts;
                if (typeof s.awayTimeouts !== 'undefined') this.awayTimeouts = s.awayTimeouts;
                if (s.possessionArrow) this.possession = s.possessionArrow;
                if (typeof s.clockSeconds !== 'undefined') this.clockSeconds = s.clockSeconds;
                if (typeof s.clockRunning !== 'undefined') {
                    this.clockRunning = s.clockRunning;
                    this.syncClockTimer();
                }

                if (payload.eventData && Array.isArray(this.recentEvents)) {
                    const existingIdx = this.recentEvents.findIndex(ev => ev.id === payload.eventData.id || (payload.clientUuid && ev.uuid === payload.clientUuid));
                    if (existingIdx !== -1) {
                        if (payload.mutationType === 'delete') {
                            this.recentEvents.splice(existingIdx, 1);
                        } else {
                            this.recentEvents[existingIdx] = { ...this.recentEvents[existingIdx], ...payload.eventData };
                        }
                    } else if (payload.mutationType !== 'delete') {
                        this.recentEvents.unshift(payload.eventData);
                    }
                }

                this.syncStatus = 'synced';
            },

            handleKeyDown(e) {
                // Prevent OS key-repeat auto-firing when holding keys down
                if (e.repeat) return;

                // ESCAPE KEY: Close expanded error list first if open, or universally escape from any state/modal back to main dashboard instantly
                if (e.key === 'Escape' || e.code === 'Escape') {
                    if (this.showErrorList) {
                        e.preventDefault();
                        this.showErrorList = false;
                        this.playSound('tap');
                        return;
                    }
                    if (this.attentionAlert) {
                        e.preventDefault();
                        this.dismissAttentionAlert();
                        this.playSound('tap');
                        return;
                    }
                    e.preventDefault();
                    this.escapeToMainDashboard();
                    return;
                }

                if (['INPUT', 'SELECT', 'TEXTAREA'].includes(document.activeElement?.tagName)) {
                    return;
                }

                // If in-game roster spreadsheet or team roster grid is open, let spreadsheet engine handle keys
                if (document.querySelector('[data-grid="ingame-roster-grid"]') || document.querySelector('[data-grid="main-roster-grid"]')) {
                    return;
                }

                // Blur any focused button on hotkey triggers to prevent browser duplicate Space/Enter click
                if (document.activeElement?.tagName === 'BUTTON') {
                    document.activeElement.blur();
                }

                // ERROR DISMISSAL & TOGGLE HOTKEYS
                // Hotkey for expanding/toggling error list: Alt+E
                if ((e.code === 'KeyE' && e.altKey) || (e.key.toLowerCase() === 'e' && e.altKey)) {
                    e.preventDefault();
                    this.toggleErrorList();
                    return;
                }

                // Error Dismissal: ` (Backquote) dismisses front error; Shift+` (Tilde ~) dismisses all errors
                if (this.errorQueue && this.errorQueue.length > 0) {
                    if (e.key === '`' || e.code === 'Backquote' || e.key === '~' || e.key === 'Delete') {
                        e.preventDefault();
                        if (e.shiftKey || e.key === '~') {
                            this.dismissAllErrors();
                        } else {
                            this.dismissFrontError();
                        }
                        return;
                    }
                }

                // PENDING TIMEOUT CONFIRMATION SHORTCUTS
                if (this.pendingTimeout) {
                    if (e.key === 'Enter' || e.code === 'Space' || e.key === ' ') {
                        e.preventDefault();
                        this.executePendingTimeout();
                        return;
                    }
                    if (e.key === 'Escape' || e.key === 'Backspace') {
                        e.preventDefault();
                        this.cancelPendingTimeout();
                        return;
                    }
                    return;
                }

                // BENCH PLAYER SELECTOR SHORTCUTS
                if (this.benchSelectSide) {
                    if (e.key === 'Escape') {
                        e.preventDefault();
                        this.benchSelectSide = null;
                        this.playSound('tap');
                        return;
                    }
                    // Number entry to quickly select bench player
                    if (e.key >= '0' && e.key <= '9') {
                        e.preventDefault();
                        this.jerseyBuffer += e.key;
                        clearTimeout(this.bufferTimeout);
                        const targetBench = this.benchSelectSide === 'home' ? this.homeBench : this.awayBench;
                        const targetName = this.benchSelectSide === 'home' ? this.homeTeamName : this.awayTeamName;
                        this.feedbackMessage = `Select #${this.jerseyBuffer} — Press [Enter] or [-]/[=]`;
                        this.feedbackType = 'info';

                        this.bufferTimeout = setTimeout(() => {
                            if (this.jerseyBuffer) {
                                this.jerseyBuffer = '';
                                this.feedbackMessage = '';
                            }
                        }, 2000);
                        return;
                    }

                    if ((e.key === 'Enter' || e.key === '-' || e.key === '=' || e.code === 'NumpadSubtract' || e.code === 'NumpadAdd') && this.jerseyBuffer) {
                        e.preventDefault();
                        clearTimeout(this.bufferTimeout);
                        const searchNum = this.jerseyBuffer;
                        this.jerseyBuffer = '';
                        const targetBench = this.benchSelectSide === 'home' ? this.homeBench : this.awayBench;
                        const matched = targetBench.find(p => String(p.jersey_number) === searchNum);
                        if (matched) {
                            this.selectBenchPlayer(matched, this.benchSelectSide);
                        } else {
                            this.pushError(`No bench player found with Jersey #${searchNum}`);
                        }
                        return;
                    }
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
                            this.pushError('Select required number of OUT players first');
                        }
                        return;
                    }
                    if (k === 'B' || e.key === 'Backspace') {
                        e.preventDefault();
                        this.openLineupModal();
                        return;
                    }
                    if (e.key === 'Tab') {
                        e.preventDefault();
                        this.keepSubsWaiting();
                        return;
                    }
                    if (e.key === 'Escape') {
                        e.preventDefault();
                        this.cancelSubOperation();
                        return;
                    }

                    // Number typing inside Execute Sub Modal toggles on-court player as OUT
                    if (e.key >= '0' && e.key <= '9') {
                        e.preventDefault();
                        this.jerseyBuffer += e.key;
                        clearTimeout(this.bufferTimeout);
                        const leftName = this.isFlipped ? this.awayTeamName : this.homeTeamName;
                        const rightName = this.isFlipped ? this.homeTeamName : this.awayTeamName;
                        this.feedbackMessage = `Select OUT #${this.jerseyBuffer} — Press [-] for ${leftName} or [=] for ${rightName}`;
                        this.feedbackType = 'info';

                        this.bufferTimeout = setTimeout(() => {
                            if (this.jerseyBuffer) {
                                this.jerseyBuffer = '';
                                this.feedbackMessage = '';
                            }
                        }, 2000);
                        return;
                    }

                    const isLeftKey = e.key === '-' || e.key === '_' || e.code === 'Minus' || e.code === 'NumpadSubtract';
                    const isRightKey = e.key === '=' || e.key === '+' || e.code === 'Equal' || e.code === 'NumpadAdd';

                    if (isLeftKey && this.jerseyBuffer) {
                        e.preventDefault();
                        clearTimeout(this.bufferTimeout);
                        const searchNum = this.jerseyBuffer;
                        this.jerseyBuffer = '';
                        const targetSide = this.isFlipped ? 'away' : 'home';
                        const targetCourt = targetSide === 'home' ? this.homeCourt : this.awayCourt;
                        const targetName = targetSide === 'home' ? this.homeTeamName : this.awayTeamName;

                        const matched = targetCourt.find(p => String(p.jersey_number) === searchNum);
                        if (matched) {
                            this.toggleSelectedOut(targetSide, matched.jersey_number);
                        } else {
                            this.pushError(`${targetName} player #${searchNum} is not on the court`);
                        }
                        return;
                    }

                    if (isRightKey && this.jerseyBuffer) {
                        e.preventDefault();
                        clearTimeout(this.bufferTimeout);
                        const searchNum = this.jerseyBuffer;
                        this.jerseyBuffer = '';
                        const targetSide = this.isFlipped ? 'home' : 'away';
                        const targetCourt = targetSide === 'home' ? this.homeCourt : this.awayCourt;
                        const targetName = targetSide === 'home' ? this.homeTeamName : this.awayTeamName;

                        const matched = targetCourt.find(p => String(p.jersey_number) === searchNum);
                        if (matched) {
                            this.toggleSelectedOut(targetSide, matched.jersey_number);
                        } else {
                            this.pushError(`${targetName} player #${searchNum} is not on the court`);
                        }
                        return;
                    }

                    if (e.code === 'Space' || e.key === ' ') {
                        e.preventDefault();
                        if (this.jerseyBuffer) {
                            const leftName = this.isFlipped ? this.awayTeamName : this.homeTeamName;
                            const rightName = this.isFlipped ? this.homeTeamName : this.awayTeamName;
                            this.feedbackMessage = `OUT #${this.jerseyBuffer} — Press [-] for ${leftName} or [=] for ${rightName}`;
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
                    if (e.key === 'Tab') {
                        e.preventDefault();
                        this.keepSubsWaiting();
                        return;
                    }
                    if (e.key === 'Escape') {
                        e.preventDefault();
                        this.cancelSubOperation();
                        return;
                    }

                    // Number typing inside Lineup Modal toggles bench player into pending subs
                    if (e.key >= '0' && e.key <= '9') {
                        e.preventDefault();
                        this.jerseyBuffer += e.key;
                        clearTimeout(this.bufferTimeout);
                        const leftName = this.isFlipped ? this.awayTeamName : this.homeTeamName;
                        const rightName = this.isFlipped ? this.homeTeamName : this.awayTeamName;
                        this.feedbackMessage = `Line up #${this.jerseyBuffer} — Press [-] for ${leftName} or [=] for ${rightName}`;
                        this.feedbackType = 'info';

                        this.bufferTimeout = setTimeout(() => {
                            if (this.jerseyBuffer) {
                                this.jerseyBuffer = '';
                                this.feedbackMessage = '';
                            }
                        }, 2000);
                        return;
                    }

                    const isLeftKey = e.key === '-' || e.key === '_' || e.code === 'Minus' || e.code === 'NumpadSubtract';
                    const isRightKey = e.key === '=' || e.key === '+' || e.code === 'Equal' || e.code === 'NumpadAdd';

                    if (isLeftKey && this.jerseyBuffer) {
                        e.preventDefault();
                        clearTimeout(this.bufferTimeout);
                        const searchNum = this.jerseyBuffer;
                        this.jerseyBuffer = '';
                        const targetSide = this.isFlipped ? 'away' : 'home';
                        const targetBench = targetSide === 'home' ? this.homeBench : this.awayBench;
                        const targetName = targetSide === 'home' ? this.homeTeamName : this.awayTeamName;

                        const matched = targetBench.find(p => String(p.jersey_number) === searchNum);
                        if (matched) {
                            this.togglePendingSub(targetSide, matched);
                        } else {
                            this.pushError(`${targetName} player #${searchNum} is not on the bench`);
                        }
                        return;
                    }

                    if (isRightKey && this.jerseyBuffer) {
                        e.preventDefault();
                        clearTimeout(this.bufferTimeout);
                        const searchNum = this.jerseyBuffer;
                        this.jerseyBuffer = '';
                        const targetSide = this.isFlipped ? 'home' : 'away';
                        const targetBench = targetSide === 'home' ? this.homeBench : this.awayBench;
                        const targetName = targetSide === 'home' ? this.homeTeamName : this.awayTeamName;

                        const matched = targetBench.find(p => String(p.jersey_number) === searchNum);
                        if (matched) {
                            this.togglePendingSub(targetSide, matched);
                        } else {
                            this.pushError(`${targetName} player #${searchNum} is not on the bench`);
                        }
                        return;
                    }

                    if (e.code === 'Space' || e.key === ' ') {
                        e.preventDefault();
                        if (this.jerseyBuffer) {
                            const leftName = this.isFlipped ? this.awayTeamName : this.homeTeamName;
                            const rightName = this.isFlipped ? this.homeTeamName : this.awayTeamName;
                            this.feedbackMessage = `Line up #${this.jerseyBuffer} — Press [-] for ${leftName} or [=] for ${rightName}`;
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
                            'Z': 'Z',
                            'M': 'M',
                            'N': 'N',
                            'B': 'B',
                            'V': 'V',
                            'D': 'D',
                            'O': 'O',
                            'A': 'A',
                            'S': 'S',
                            'K': 'K',
                            'W': 'W',
                            'P': 'P',
                            'U': 'U',
                            'I': 'I',
                            'F': 'F',
                            'R': 'R',
                            'T': 'T',
                            'G': 'G',
                            'H': 'H',
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
                        if (this.assistModal && this.assistModal.open) {
                            this.closeAssistModal();
                        } else {
                            this.selectedPlayer = null;
                        }
                        this.playSound('tap');
                        return;
                    }

                    if (e.key === 'Tab') {
                        e.preventDefault();
                        this.openLineupModal();
                        return;
                    }
                }

                // 5.5. POST-SHOT 3-SECOND KEYBOARD ASSIST WINDOW SHORTCUTS
                if (this.assistWindow && this.assistWindow.active) {
                    const k = e.key.toUpperCase();

                    // If operator has already pressed 'A' and we are waiting for teammate jersey:
                    if (this.assistWindow.waitingForJersey) {
                        // Enter or Space: confirm current buffer immediately
                        if (e.key === 'Enter' || e.code === 'Space' || e.key === ' ') {
                            e.preventDefault();
                            if (this.assistWindow.assistBuffer) {
                                this.commitAssist(this.assistWindow.assistBuffer);
                            }
                            return;
                        }

                        // Escape: cancel assist window
                        if (e.key === 'Escape' || e.code === 'Escape') {
                            e.preventDefault();
                            this.closeAssistWindow();
                            this.playSound('tap');
                            return;
                        }

                        // Backspace: delete last digit or reset to waiting state
                        if (e.key === 'Backspace') {
                            e.preventDefault();
                            if (this._assistIdleTimer) {
                                clearTimeout(this._assistIdleTimer);
                                this._assistIdleTimer = null;
                            }
                            if (this.assistWindow.assistBuffer.length > 0) {
                                this.assistWindow.assistBuffer = this.assistWindow.assistBuffer.slice(0, -1);
                                const side = this.assistWindow.teamSide;
                                const court = (side === 'home' ? this.homeCourt : this.awayCourt) || [];
                                const bench = (side === 'home' ? this.homeBench : this.awayBench) || [];
                                this.assistWindow.matchedPlayer = [...court, ...bench].find(p => p && String(p.jersey_number) === this.assistWindow.assistBuffer && String(p.jersey_number) !== String(this.assistWindow.shooterJersey)) || null;
                            } else {
                                this.assistWindow.waitingForJersey = false;
                            }
                            this.playSound('tap');
                            return;
                        }

                        // Number digit entry (0-9)
                        if (e.key >= '0' && e.key <= '9') {
                            e.preventDefault();
                            if (this._assistIdleTimer) {
                                clearTimeout(this._assistIdleTimer);
                                this._assistIdleTimer = null;
                            }

                            this.assistWindow.assistBuffer += e.key;
                            const currentBuffer = this.assistWindow.assistBuffer;
                            const side = this.assistWindow.teamSide;
                            const court = (side === 'home' ? this.homeCourt : this.awayCourt) || [];
                            const bench = (side === 'home' ? this.homeBench : this.awayBench) || [];
                            this.assistWindow.matchedPlayer = [...court, ...bench].find(p => p && String(p.jersey_number) === currentBuffer && String(p.jersey_number) !== String(this.assistWindow.shooterJersey)) || null;

                            // Option B: 400ms idle timer auto-commits if no further digits typed
                            this._assistIdleTimer = setTimeout(() => {
                                if (this.assistWindow && this.assistWindow.active && this.assistWindow.assistBuffer) {
                                    this.commitAssist(this.assistWindow.assistBuffer);
                                }
                            }, 400);
                            return;
                        }

                        // If any other key is pressed while waiting for jersey, cancel assist window
                        this.closeAssistWindow();
                    } else {
                        // Operator has NOT pressed 'A' yet:
                        // If 'A' is pressed: enter jersey listening mode!
                        if (k === 'A') {
                            e.preventDefault();
                            this.assistWindow.waitingForJersey = true;
                            this.assistWindow.assistBuffer = '';
                            this.assistWindow.matchedPlayer = null;
                            this.playSound('tap');
                            return;
                        }

                        // If Escape: dismiss assist window
                        if (e.key === 'Escape' || e.code === 'Escape') {
                            e.preventDefault();
                            this.closeAssistWindow();
                            this.playSound('tap');
                            return;
                        }

                        // If user starts typing jersey digits for the next play (0-9):
                        // Close assist window quietly and let section 6 handle number buffering!
                        if (e.key >= '0' && e.key <= '9') {
                            this.closeAssistWindow();
                        }
                    }
                }

                // 6. MAIN DASHBOARD NUMBER ENTRY & CLOCK SHORTCUTS
                // Clock Sync Hotkey Shorthand: typing 't' or 'T' initiates clock buffer (e.g. t0800, t2000, t0530, t0005, t00055, t8:00)
                if ((e.key === 't' || e.key === 'T') && !this.jerseyBuffer && !this.selectedPlayer) {
                    e.preventDefault();
                    this.jerseyBuffer = 't';
                    clearTimeout(this.bufferTimeout);
                    this.feedbackMessage = 'Clock Sync: Type time (e.g. t0800, t2000, t0530) & press Enter';
                    this.feedbackType = 'info';
                    this.bufferTimeout = setTimeout(() => {
                        if (this.jerseyBuffer === 't') {
                            this.jerseyBuffer = '';
                            this.feedbackMessage = '';
                        }
                    }, 4000);
                    return;
                }

                // If currently buffering clock time (starts with 't'), allow 0-9 and ':'
                if (this.jerseyBuffer && (this.jerseyBuffer.startsWith('t') || this.jerseyBuffer.startsWith('T'))) {
                    if ((e.key >= '0' && e.key <= '9') || e.key === ':') {
                        e.preventDefault();
                        this.jerseyBuffer += e.key;
                        clearTimeout(this.bufferTimeout);
                        this.feedbackMessage = `Clock Sync: ${this.jerseyBuffer} — Press [Enter] to Sync`;
                        this.feedbackType = 'info';
                        this.bufferTimeout = setTimeout(() => {
                            this.jerseyBuffer = '';
                            this.feedbackMessage = '';
                        }, 5000);
                        return;
                    }

                    if (e.key === 'Enter') {
                        e.preventDefault();
                        clearTimeout(this.bufferTimeout);
                        const parsed = this.parseClockShortcut(this.jerseyBuffer);
                        if (parsed !== null) {
                            this.syncClockFast(parsed);
                        } else {
                            this.pushError(`Invalid clock format: ${this.jerseyBuffer}`);
                        }
                        this.jerseyBuffer = '';
                        return;
                    }

                    if (e.key === 'Escape' || e.key === 'Backspace') {
                        e.preventDefault();
                        clearTimeout(this.bufferTimeout);
                        this.jerseyBuffer = '';
                        this.feedbackMessage = 'Clock sync cancelled';
                        this.feedbackType = 'info';
                        return;
                    }
                }

                if (e.key >= '0' && e.key <= '9') {
                    e.preventDefault();
                    this.jerseyBuffer += e.key;
                    clearTimeout(this.bufferTimeout);
                    const leftName = this.isFlipped ? this.awayTeamName : this.homeTeamName;
                    const rightName = this.isFlipped ? this.homeTeamName : this.awayTeamName;
                    this.feedbackMessage = `Jersey #${this.jerseyBuffer} — Press [-] for ${leftName} or [=] for ${rightName}`;
                    this.feedbackType = 'info';

                    this.bufferTimeout = setTimeout(() => {
                        if (this.jerseyBuffer) {
                            this.jerseyBuffer = '';
                            this.feedbackMessage = 'Jersey entry cleared';
                        }
                    }, 2000);
                    return;
                }

                const isLeftKey = e.key === '-' || e.key === '_' || e.code === 'Minus' || e.code === 'NumpadSubtract';
                const isRightKey = e.key === '=' || e.key === '+' || e.code === 'Equal' || e.code === 'NumpadAdd';

                // Press [-] with jersey buffer -> Open Action Pad for Left Player
                if (isLeftKey && this.jerseyBuffer) {
                    e.preventDefault();
                    clearTimeout(this.bufferTimeout);
                    const searchNum = this.jerseyBuffer;
                    this.jerseyBuffer = '';
                    const targetSide = this.isFlipped ? 'away' : 'home';
                    const targetCourt = targetSide === 'home' ? this.homeCourt : this.awayCourt;
                    const targetBench = targetSide === 'home' ? this.homeBench : this.awayBench;
                    const targetName = targetSide === 'home' ? this.homeTeamName : this.awayTeamName;

                    const matched = targetCourt.find(p => String(p.jersey_number) === searchNum);
                    if (matched) {
                        this.openActionPad(targetSide, matched.jersey_number, matched.player_name, matched.id);
                    } else {
                        const benchP = targetBench.find(p => String(p.jersey_number) === searchNum);
                        if (benchP) {
                            this.openActionPad(targetSide, benchP.jersey_number, benchP.player_name, benchP.id);
                            this.feedbackMessage = `${targetName} #${searchNum} (${benchP.player_name}) [Bench]`;
                            this.feedbackType = 'info';
                        } else {
                            this.pushError(`No ${targetName} player found with Jersey #${searchNum}`);
                        }
                    }
                    return;
                }

                // Press [=] with jersey buffer -> Open Action Pad for Right Player
                if (isRightKey && this.jerseyBuffer) {
                    e.preventDefault();
                    clearTimeout(this.bufferTimeout);
                    const searchNum = this.jerseyBuffer;
                    this.jerseyBuffer = '';
                    const targetSide = this.isFlipped ? 'home' : 'away';
                    const targetCourt = targetSide === 'home' ? this.homeCourt : this.awayCourt;
                    const targetBench = targetSide === 'home' ? this.homeBench : this.awayBench;
                    const targetName = targetSide === 'home' ? this.homeTeamName : this.awayTeamName;

                    const matched = targetCourt.find(p => String(p.jersey_number) === searchNum);
                    if (matched) {
                        this.openActionPad(targetSide, matched.jersey_number, matched.player_name, matched.id);
                    } else {
                        const benchP = targetBench.find(p => String(p.jersey_number) === searchNum);
                        if (benchP) {
                            this.openActionPad(targetSide, benchP.jersey_number, benchP.player_name, benchP.id);
                            this.feedbackMessage = `${targetName} #${searchNum} (${benchP.player_name}) [Bench]`;
                            this.feedbackType = 'info';
                        } else {
                            this.pushError(`No ${targetName} player found with Jersey #${searchNum}`);
                        }
                    }
                    return;
                }

                // Spacebar: Toggle Clock in Basketball, Server in Volleyball
                if (e.code === 'Space' || e.key === ' ') {
                    e.preventDefault();
                    if (this.jerseyBuffer) {
                        const leftName = this.isFlipped ? this.awayTeamName : this.homeTeamName;
                        const rightName = this.isFlipped ? this.homeTeamName : this.awayTeamName;
                        this.feedbackMessage = `Jersey #${this.jerseyBuffer} — Press [-] for ${leftName} or [=] for ${rightName}`;
                        this.feedbackType = 'info';
                        return;
                    }

                    // Default Space action if no jersey was typed
                    if (this.sport === 'basketball') {
                        this.toggleClockFast();
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
                if (!this.selectedPlayer && !this.jerseyBuffer && !this.showLineupModal && !this.showExecuteSubModal && !this.showPlayByPlayModal && !this.benchSelectSide && !this.pendingTimeout) {
                    const k = e.key.toUpperCase();
                    
                    // Flip Court / Switch Sides: '\' or Shift+X
                    if (e.key === '\\' || (k === 'X' && e.shiftKey)) {
                        e.preventDefault();
                        this.toggleFlipCourt();
                        return;
                    }

                    // Left and Right team sides dynamically respecting flipped court
                    const leftTeam = this.isFlipped ? 'away' : 'home';
                    const rightTeam = this.isFlipped ? 'home' : 'away';

                    // Left Hand Full Timeout: 'F'
                    if (k === 'F') {
                        e.preventDefault();
                        this.requestTimeout(leftTeam, 'full');
                        return;
                    }

                    // Right Hand Full Timeout: 'G'
                    if (k === 'G') {
                        e.preventDefault();
                        this.requestTimeout(rightTeam, 'full');
                        return;
                    }

                    // Left Hand 30s Timeout: 'H'
                    if (k === 'H') {
                        e.preventDefault();
                        if (this.sport === 'basketball') {
                            this.requestTimeout(leftTeam, '30s');
                        } else {
                            this.requestTimeout(leftTeam, 'full');
                        }
                        return;
                    }

                    // Right Hand 30s Timeout: 'J'
                    if (k === 'J') {
                        e.preventDefault();
                        if (this.sport === 'basketball') {
                            this.requestTimeout(rightTeam, '30s');
                        } else {
                            this.requestTimeout(rightTeam, 'full');
                        }
                        return;
                    }

                    // Advance Period / Set
                    if (k === 'N') {
                        e.preventDefault();
                        this.nextPeriodFast();
                        return;
                    }
                    // SEND IN Hotkey: 'E'
                    if (k === 'E') {
                        e.preventDefault();
                        if (this.pendingSubCount() > 0) {
                            this.openExecuteSubModal();
                        } else {
                            this.feedbackMessage = 'No pending substitutions lined up (Press S to line up)';
                            this.feedbackType = 'info';
                            this.playSound('tap');
                        }
                        return;
                    }
                    // Quick Substitution / Line Up
                    if (k === 'S') {
                        e.preventDefault();
                        this.openLineupModal();
                        return;
                    }
                    // Left Hand Team Roster Modal: 'R' (Instant 0ms Modal)
                    if (k === 'R') {
                        e.preventDefault();
                        this.openRosterModal(leftTeam);
                        return;
                    }
                    // Right Hand Team Roster Modal: 'T' (Instant 0ms Modal)
                    if (k === 'T') {
                        e.preventDefault();
                        this.openRosterModal(rightTeam);
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

                    // Spatial Score Adjustments: '[' / '{' = Left Team, ']' / '}' = Right Team
                    const leftScoreTeam = this.isFlipped ? 'away' : 'home';
                    const rightScoreTeam = this.isFlipped ? 'home' : 'away';

                    if (e.key === '[') {
                        e.preventDefault();
                        this.adjustScoreFast(leftScoreTeam, 1);
                        return;
                    }
                    if (e.key === '{') {
                        e.preventDefault();
                        this.adjustScoreFast(leftScoreTeam, -1);
                        return;
                    }
                    if (e.key === ']') {
                        e.preventDefault();
                        this.adjustScoreFast(rightScoreTeam, 1);
                        return;
                    }
                    if (e.key === '}') {
                        e.preventDefault();
                        this.adjustScoreFast(rightScoreTeam, -1);
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

            getOtherCourtPlayers() {
                if (!this.selectedPlayer) return [];
                const side = this.selectedPlayer.side;
                const court = (side === 'home' ? this.homeCourt : this.awayCourt) || [];
                return court.filter(p => p && String(p.jersey_number) !== String(this.selectedPlayer.jersey));
            },

            openAssistModal(actionCode) {
                if (!this.selectedPlayer) return;
                const def = ProKeeperEngine.basketballActions[actionCode] || { name: actionCode, points: 2 };
                this.assistModal = {
                    open: true,
                    actionCode: actionCode,
                    actionName: def.name,
                    points: def.points || 0,
                    hoveredJersey: null,
                };
                this.playSound('tap');
            },

            closeAssistModal() {
                clearTimeout(this._holdTimer);
                this.assistModal = {
                    open: false,
                    actionCode: null,
                    actionName: '',
                    points: 0,
                    hoveredJersey: null,
                };
                this._holdTriggered = false;
            },

            selectAssistTeammate(assistJersey) {
                if (!this.assistModal.open || !this.assistModal.actionCode) return;
                const code = this.assistModal.actionCode;
                this.closeAssistModal();
                this.executeAction(code, assistJersey);
            },

            // 3-Second Post-Shot Keyboard Assist Window Engine
            startAssistWindow(data) {
                if (this.sport !== 'basketball') return;
                this.closeAssistWindow();

                const totalSeconds = 3.0;
                this.assistWindow = {
                    active: true,
                    teamSide: data.teamSide,
                    shooterJersey: String(data.shooterJersey),
                    shooterName: data.shooterName || ('#' + data.shooterJersey),
                    actionCode: data.actionCode,
                    points: data.points || (data.actionCode === 'M' ? 3 : 2),
                    eventId: data.eventId,
                    secondsRemaining: totalSeconds,
                    progressPercent: 100,
                    waitingForJersey: false,
                    assistBuffer: '',
                    matchedPlayer: null,
                };

                const intervalMs = 50;
                this._assistCountdownInterval = setInterval(() => {
                    if (!this.assistWindow || !this.assistWindow.active) {
                        this.closeAssistWindow();
                        return;
                    }
                    // Pause countdown while operator is actively typing teammate jersey
                    if (this.assistWindow.waitingForJersey) return;

                    this.assistWindow.secondsRemaining = Math.max(0, this.assistWindow.secondsRemaining - (intervalMs / 1000));
                    this.assistWindow.progressPercent = Math.max(0, (this.assistWindow.secondsRemaining / totalSeconds) * 100);

                    if (this.assistWindow.secondsRemaining <= 0) {
                        this.closeAssistWindow();
                    }
                }, intervalMs);
            },

            closeAssistWindow() {
                if (this._assistCountdownInterval) {
                    clearInterval(this._assistCountdownInterval);
                    this._assistCountdownInterval = null;
                }
                if (this._assistIdleTimer) {
                    clearTimeout(this._assistIdleTimer);
                    this._assistIdleTimer = null;
                }
                this.assistWindow = {
                    active: false,
                    teamSide: null,
                    shooterJersey: null,
                    shooterName: '',
                    actionCode: null,
                    points: 0,
                    eventId: null,
                    secondsRemaining: 3.0,
                    progressPercent: 100,
                    waitingForJersey: false,
                    assistBuffer: '',
                    matchedPlayer: null,
                };
            },

            commitAssist(assistJersey) {
                if (!this.assistWindow || !this.assistWindow.active) return;
                const jerseyStr = String(assistJersey ?? '').trim();
                if (!jerseyStr) return;

                if (this._assistIdleTimer) {
                    clearTimeout(this._assistIdleTimer);
                    this._assistIdleTimer = null;
                }

                const side = this.assistWindow.teamSide;
                const shooterJersey = this.assistWindow.shooterJersey;
                const eventId = this.assistWindow.eventId;

                // Validate: cannot assist own basket
                if (jerseyStr === String(shooterJersey)) {
                    this.feedbackMessage = `Shooter #${jerseyStr} cannot assist their own basket`;
                    this.feedbackType = 'error';
                    this.playSound('tap');
                    this.assistWindow.assistBuffer = '';
                    this.assistWindow.matchedPlayer = null;
                    return;
                }

                // Check player on court or bench for the scoring team
                const court = (side === 'home' ? this.homeCourt : this.awayCourt) || [];
                const bench = (side === 'home' ? this.homeBench : this.awayBench) || [];
                const allPlayers = [...court, ...bench];
                const matched = allPlayers.find(p => p && String(p.jersey_number) === jerseyStr);

                if (!matched) {
                    const teamName = side === 'home' ? this.homeTeamName : this.awayTeamName;
                    this.feedbackMessage = `No ${teamName} player found with Jersey #${jerseyStr}`;
                    this.feedbackType = 'error';
                    this.playSound('tap');
                    this.assistWindow.assistBuffer = '';
                    this.assistWindow.matchedPlayer = null;
                    return;
                }

                const assistName = matched.player_name || ('#' + jerseyStr);

                // Update event in local recentEvents
                let targetEvent = null;
                if (Array.isArray(this.recentEvents)) {
                    targetEvent = this.recentEvents.find(e => String(e.id) === String(eventId));
                    if (!targetEvent) {
                        targetEvent = this.recentEvents.find(e => e.team_side === side && (e.action_code === 'X' || e.action_code === 'M') && !e.is_undone);
                    }
                }

                if (targetEvent) {
                    targetEvent.assist_jersey_number = jerseyStr;
                    const pts = targetEvent.points || (targetEvent.action_code === 'M' ? 3 : 2);
                    const ptsSuffix = pts > 0 ? ` (+${pts} pts)` : '';
                    const cleanDesc = (targetEvent.description || '').replace(/\s*\(Ast:\s*#[^)]+\)/i, '');
                    targetEvent.description = `${cleanDesc} (Ast: #${jerseyStr} ${assistName})`.trim();
                }

                // Update local player stats
                const astStat = this.getPlayerStat(side, jerseyStr);
                astStat.ast = (astStat.ast || 0) + 1;

                // Update top undo snapshot if it exists
                if (this.undoStack && this.undoStack.length > 0) {
                    const top = this.undoStack[this.undoStack.length - 1];
                    if (top && top.type === 'stat' && top.meta) {
                        top.meta.assistJersey = jerseyStr;
                    }
                }

                this.recalculateLocalEventsAndScores();
                this.saveLocalState();
                this.playSound('tap');

                const teamDisplayName = side === 'home' ? this.homeTeamName : this.awayTeamName;
                this.feedbackMessage = `✓ ${teamDisplayName} #${shooterJersey}: Assist credited to #${jerseyStr} ${assistName}`;
                this.feedbackType = 'success';

                // Check syncQueue: if original mutation hasn't synced yet, update its args
                const queued = this.syncQueue.find(item => String(item.id) === String(eventId));
                if (queued && queued.method === 'recordQuickStat') {
                    queued.args[3] = jerseyStr;
                    this.saveQueue();
                } else {
                    const numericId = typeof targetEvent?.id === 'number' ? targetEvent.id : null;
                    this.enqueueSync('attachAssistToEvent', [side, jerseyStr, numericId], 'stats');
                }

                this.closeAssistWindow();
            },

            onShotButtonPointerDown(e, actionCode) {
                if (this.sport !== 'basketball' || !this.selectedPlayer) return;
                if (e.button !== undefined && e.button !== 0) return;

                clearTimeout(this._holdTimer);
                this._holdTriggered = false;
                this._suppressNextClick = false;

                // 220ms deliberate hold triggers the assist teammate selector
                this._holdTimer = setTimeout(() => {
                    this._holdTriggered = true;
                    this.openAssistModal(actionCode);
                    if (navigator.vibrate) {
                        try { navigator.vibrate(25); } catch (err) {}
                    }
                }, 220);

                const onPointerMove = (moveEvt) => {
                    if (this._holdTriggered && this.assistModal.open) {
                        const target = document.elementFromPoint(moveEvt.clientX, moveEvt.clientY);
                        const card = target?.closest('[data-assist-jersey]');
                        const jersey = card?.getAttribute('data-assist-jersey') || null;
                        if (this.assistModal.hoveredJersey !== jersey) {
                            this.assistModal.hoveredJersey = jersey;
                            if (jersey && navigator.vibrate) {
                                try { navigator.vibrate(10); } catch (err) {}
                            }
                        }
                    }
                };

                const onPointerUp = (upEvt) => {
                    clearTimeout(this._holdTimer);
                    window.removeEventListener('pointermove', onPointerMove);
                    window.removeEventListener('pointerup', onPointerUp);
                    window.removeEventListener('pointercancel', onPointerCancel);

                    if (this._holdTriggered) {
                        this._suppressNextClick = true;
                        const releasedJersey = this.assistModal.hoveredJersey;
                        if (releasedJersey) {
                            if (releasedJersey === 'none') {
                                this.executeAction(actionCode);
                            } else {
                                this.executeAction(actionCode, releasedJersey);
                            }
                            this.closeAssistModal();
                        }
                        // If not released over a teammate card, keep assistModal open so user can tap
                    }
                };

                const onPointerCancel = () => {
                    clearTimeout(this._holdTimer);
                    window.removeEventListener('pointermove', onPointerMove);
                    window.removeEventListener('pointerup', onPointerUp);
                    window.removeEventListener('pointercancel', onPointerCancel);
                    this._holdTriggered = false;
                };

                window.addEventListener('pointermove', onPointerMove, { passive: true });
                window.addEventListener('pointerup', onPointerUp);
                window.addEventListener('pointercancel', onPointerCancel);
            },

            onShotButtonClick(actionCode) {
                if (this._suppressNextClick) {
                    this._suppressNextClick = false;
                    return;
                }
                this.executeAction(actionCode);
            },

            openActionPad(side, jersey, name, id) {
                if (!this.canExecute('openActionPad', 150)) return;
                this.closeAssistModal();
                this.closeAssistWindow();
                this.selectedPlayer = { side, jersey, name, id };
                this.playSound('tap');
            },

            // 0ms Latency Stat Execution Engine
            executeAction(actionCode, assistJersey = null) {
                if (!this.selectedPlayer) return;
                if (!this.canExecute('executeAction', 200)) return;

                const side = this.selectedPlayer.side;
                const jersey = this.selectedPlayer.jersey;
                const name = this.selectedPlayer.name;

                // Save snapshot for 0ms local undo
                this.pushUndoSnapshot('stat', { side, jersey, actionCode, assistJersey });

                let actionDef;
                let playSoundType = 'tap';
                let alertTriggered = false;

                if (this.sport === 'basketball') {
                    actionDef = ProKeeperEngine.basketballActions[actionCode] || { name: actionCode, points: 0 };
                    const pts = actionDef.points || 0;

                    if (pts > 0) {
                        if (side === 'home') this.homeScore += pts;
                        else this.awayScore += pts;
                        this.updateCurrentPeriodScore(side, pts);
                        playSoundType = 'score';
                    }

                    const oldTeamFouls = side === 'home' ? this.homeFouls : this.awayFouls;
                    if (actionDef.isTeamFoul) {
                        if (side === 'home') this.homeFouls += 1;
                        else this.awayFouls += 1;
                    }
                    const newTeamFouls = side === 'home' ? this.homeFouls : this.awayFouls;

                    // 0ms instant local player stats update
                    const pStat = this.getPlayerStat(side, jersey);
                    const oldTotalFouls = pStat.total_fouls;
                    if (pts > 0) pStat.points += pts;
                    if (actionCode === 'F') {
                        pStat.fouls_pers += 1;
                        pStat.total_fouls += 1;
                    } else if (actionCode === 'R') {
                        pStat.fouls_off += 1;
                        pStat.total_fouls += 1;
                    } else if (actionCode === 'T') {
                        pStat.fouls_tech += 1;
                        pStat.total_fouls += 1;
                    } else if (['G', 'FL', 'FLAG'].includes(actionCode)) {
                        pStat.fouls_flagrant += 1;
                        pStat.total_fouls += 1;
                    } else if (actionCode === 'H') {
                        pStat.fouls_forced = (pStat.fouls_forced || 0) + 1;
                    }

                    const foulLimit = this.playerFoulLimit || 5;
                    const isFouledOut = pStat.total_fouls >= foulLimit;
                    const justFouledOut = isFouledOut && oldTotalFouls < foulLimit;

                    const bonusThresh = this.bonusFoulThreshold || (this.periodFormat === 'halves' ? 7 : 5);
                    const doubleBonusThresh = this.doubleBonusFoulThreshold || (this.periodFormat === 'halves' ? 10 : 5);
                    const justEnteredDoubleBonus = actionDef.isTeamFoul && doubleBonusThresh > bonusThresh && newTeamFouls >= doubleBonusThresh && oldTeamFouls < doubleBonusThresh;
                    const justEnteredBonus = actionDef.isTeamFoul && !justEnteredDoubleBonus && newTeamFouls >= bonusThresh && oldTeamFouls < bonusThresh;

                    const foulingTeamName = (side === 'home' ? this.homeTeamName : this.awayTeamName) || (side === 'home' ? 'Home' : 'Away');
                    const opposingTeamName = (side === 'home' ? this.awayTeamName : this.homeTeamName) || (side === 'home' ? 'Away' : 'Home');

                    if (justFouledOut) {
                        alertTriggered = true;
                        let extraBonusNote = '';
                        if (justEnteredDoubleBonus) extraBonusNote = ` • ${foulingTeamName} also reached Double Bonus!`;
                        else if (justEnteredBonus) extraBonusNote = ` • ${foulingTeamName} also reached Bonus!`;

                        this.triggerAttentionAlert(
                            'foul_out',
                            'PLAYER FOULED OUT',
                            `#${jersey} ${name} (${foulingTeamName}) has reached ${pStat.total_fouls} fouls and must be substituted.${extraBonusNote}`,
                            'Official disqualification rules apply. Press S or click to substitute immediately.',
                            60,
                            { side, jersey, name, teamName: foulingTeamName }
                        );
                    } else if (justEnteredDoubleBonus) {
                        alertTriggered = true;
                        this.triggerAttentionAlert(
                            'double_bonus',
                            'DOUBLE BONUS ACTIVE',
                            `${foulingTeamName} reached ${newTeamFouls} team fouls in Period ${this.currentPeriod}.`,
                            `${opposingTeamName} shoots 2 Free Throws on all common fouls.`,
                            45,
                            { side, teamName: foulingTeamName, fouls: newTeamFouls }
                        );
                    } else if (justEnteredBonus) {
                        alertTriggered = true;
                        const bonusType = this.periodFormat === 'halves' ? '1-and-1 Free Throws' : '2 Free Throws';
                        this.triggerAttentionAlert(
                            'bonus',
                            'TEAM BONUS ACTIVE',
                            `${foulingTeamName} reached ${newTeamFouls} team fouls in Period ${this.currentPeriod}.`,
                            `${opposingTeamName} shoots ${bonusType} on all common fouls.`,
                            45,
                            { side, teamName: foulingTeamName, fouls: newTeamFouls }
                        );
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
                const pts = actionDef.points || 0;
                let assistPlayerName = '';
                if (assistJersey) {
                    const court = side === 'home' ? this.homeCourt : this.awayCourt;
                    const astPlayer = (court || []).find(p => p && String(p.jersey_number) === String(assistJersey));
                    assistPlayerName = astPlayer?.player_name ? ` ${astPlayer.player_name}` : '';
                }
                const assistSuffix = assistJersey ? ` (Ast: #${assistJersey}${assistPlayerName})` : '';
                const pointsSuffix = pts > 0 ? ` (+${pts} pts)` : '';
                const eventDesc = `${side.toUpperCase()} #${jersey} ${name}: ${actionDef.name}${pointsSuffix}${assistSuffix}`;

                const mutId = 'mut_' + Date.now() + '_' + Math.random().toString(36).substr(2, 6);
                this.recentEvents.unshift({
                    id: mutId,
                    sequence: (this.recentEvents[0]?.sequence || 0) + 1,
                    period: this.currentPeriod,
                    team_side: side,
                    jersey_number: jersey,
                    player_name: name,
                    assist_jersey_number: assistJersey || null,
                    action_code: actionCode,
                    action_name: actionDef.name,
                    points: pts,
                    description: eventDesc,
                    home_score_after: this.homeScore,
                    away_score_after: this.awayScore,
                });
                this.recalculateLocalEventsAndScores();

                if (!alertTriggered) {
                    this.feedbackMessage = eventDesc;
                    this.feedbackType = 'success';
                    this.playSound(playSoundType);
                }

                // Close selection overlay instantly
                this.selectedPlayer = null;
                if (this.assistModal && this.assistModal.open) {
                    this.closeAssistModal();
                }

                // Queue mutation for non-blocking asynchronous sync
                const syncArgs = assistJersey ? [side, jersey, actionCode, assistJersey] : [side, jersey, actionCode];
                this.enqueueSync('recordQuickStat', syncArgs, 'stats', mutId);

                // If basketball made field goal without assist, initiate 3-second keyboard assist window
                if (this.sport === 'basketball' && (actionCode === 'X' || actionCode === 'M') && !assistJersey) {
                    this.startAssistWindow({
                        teamSide: side,
                        shooterJersey: jersey,
                        shooterName: name,
                        actionCode: actionCode,
                        points: pts,
                        eventId: mutId,
                    });
                }
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

                const ptsText = (delta > 0 ? '+' : '') + delta;
                const mutId = 'mut_' + Date.now() + '_' + Math.random().toString(36).substr(2, 6);
                const scoreEvent = {
                    id: mutId,
                    game_id: this.gameId,
                    sequence: (this.recentEvents[0]?.sequence || 0) + 1,
                    period: this.currentPeriod,
                    clock_seconds_remaining: this.clockSeconds || 0,
                    team_side: side,
                    jersey_number: null,
                    player_name: 'Official Scorer',
                    action_code: 'SCORE_ADJ',
                    action_type: 'score_adjustment',
                    action_name: 'Score Adjustment',
                    points: delta,
                    home_score_after: this.homeScore,
                    away_score_after: this.awayScore,
                    description: `${side.toUpperCase()} Score Adjusted (${ptsText} pts)`,
                };
                this.recentEvents.unshift(scoreEvent);
                this.recalculateLocalEventsAndScores();

                this.playSound(delta > 0 ? 'score' : 'tap');
                this.feedbackMessage = `${side.toUpperCase()} Score ${ptsText}`;
                this.feedbackType = 'info';

                this.enqueueSync('adjustScore', [side, delta], 'score', mutId);
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

                let inPlayerName = '#' + inJ;
                if (courtIdx !== -1 && benchIdx !== -1) {
                    const outPlayer = courtList[courtIdx];
                    const inPlayer = benchList[benchIdx];
                    inPlayerName = inPlayer.player_name || inPlayerName;

                    // Swap
                    courtList[courtIdx] = { ...inPlayer, is_on_court: true };
                    benchList[benchIdx] = { ...outPlayer, is_on_court: false };
                }

                const mutId = 'mut_' + Date.now() + '_' + Math.random().toString(36).substr(2, 6);
                const subEvent = {
                    id: mutId,
                    game_id: this.gameId,
                    sequence: (this.recentEvents[0]?.sequence || 0) + 1,
                    period: this.currentPeriod,
                    clock_seconds_remaining: this.clockSeconds || 0,
                    team_side: side,
                    jersey_number: inJ,
                    player_name: inPlayerName,
                    action_code: 'SUB',
                    action_type: 'substitution',
                    action_name: 'Substitution',
                    points: 0,
                    home_score_after: this.homeScore,
                    away_score_after: this.awayScore,
                    description: `${side.toUpperCase()} Sub: OUT #${outJ}, IN #${inJ}`,
                };
                this.recentEvents.unshift(subEvent);

                this.playSound('tap');
                this.feedbackMessage = `Subbed OUT #${outJ}, IN #${inJ} (${side.toUpperCase()})`;
                this.feedbackType = 'success';

                this.showSubSheet = false;
                this.subOutJersey = '';
                this.subInJersey = '';

                // If the player substituted out had an active foul-out alert, dismiss it
                if (this.attentionAlert && this.attentionAlert.type === 'foul_out' && String(this.attentionAlert.jersey) === outJ) {
                    this.dismissAttentionAlert();
                }

                this.enqueueSync('setAndExecuteSub', [side, outJ, inJ], 'lineup', mutId);
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

                this.enqueueSync('rotateTeam', [side], 'rotation');
            },

            // 0ms Toggle Possession Arrow
            togglePossession() {
                if (!this.canExecute('togglePossession', 300)) return;

                this.pushUndoSnapshot('possession', { prev: this.possession });
                this.possession = (this.possession === 'home') ? 'away' : 'home';
                this.playSound('tap');
                this.feedbackMessage = `Possession: ${this.possession.toUpperCase()}`;
                this.enqueueSync('togglePossession', [], 'possession');
            },

            // 0ms Toggle Volleyball Server
            toggleServer() {
                if (!this.canExecute('toggleServer', 300)) return;

                this.pushUndoSnapshot('server', { prev: this.server });
                this.server = (this.server === 'home') ? 'away' : 'home';
                this.playSound('tap');
                this.feedbackMessage = `Server: ${this.server.toUpperCase()}`;
                this.enqueueSync('toggleServer', [], 'rotation');
            },

            // 0ms Timeout Charge (Full vs 30s tracking)
            callTimeoutFast(side, type = 'full') {
                if (!this.canExecute('timeout_' + side + '_' + type, 400)) return;

                if (this.sport === 'basketball') {
                    if (type === '30s') {
                        const count = (side === 'home') ? this.home30sTimeouts : this.away30sTimeouts;
                        if (count <= 0) {
                            this.pushError(`No 30-Second Timeouts remaining for ${side.toUpperCase()}`);
                            return;
                        }
                    } else {
                        const count = (side === 'home') ? this.homeFullTimeouts : this.awayFullTimeouts;
                        if (count <= 0) {
                            this.pushError(`No Full Timeouts remaining for ${side.toUpperCase()}`);
                            return;
                        }
                    }
                } else {
                    const count = (side === 'home') ? this.homeTimeouts : this.awayTimeouts;
                    if (count <= 0) {
                        this.pushError(`No Timeouts remaining for ${side.toUpperCase()}`);
                        return;
                    }
                }

                this.pushUndoSnapshot('timeout', {
                    side,
                    type,
                    homeFullTimeouts: this.homeFullTimeouts,
                    home30sTimeouts: this.home30sTimeouts,
                    awayFullTimeouts: this.awayFullTimeouts,
                    away30sTimeouts: this.away30sTimeouts,
                    homeTimeouts: this.homeTimeouts,
                    awayTimeouts: this.awayTimeouts,
                    homeFullTimeoutsTaken: this.homeFullTimeoutsTaken,
                    home30sTimeoutsTaken: this.home30sTimeoutsTaken,
                    awayFullTimeoutsTaken: this.awayFullTimeoutsTaken,
                    away30sTimeoutsTaken: this.away30sTimeoutsTaken,
                    homeTimeoutsTaken: this.homeTimeoutsTaken,
                    awayTimeoutsTaken: this.awayTimeoutsTaken,
                });

                if (this.sport === 'basketball') {
                    if (side === 'home') {
                        if (type === '30s' && this.home30sTimeouts > 0) {
                            this.home30sTimeouts -= 1;
                            this.home30sTimeoutsTaken = Number(this.home30sTimeoutsTaken || 0) + 1;
                        } else if (type !== '30s' && this.homeFullTimeouts > 0) {
                            this.homeFullTimeouts -= 1;
                            this.homeFullTimeoutsTaken = Number(this.homeFullTimeoutsTaken || 0) + 1;
                        }
                        if (this.homeTimeouts > 0) this.homeTimeouts -= 1;
                        this.homeTimeoutsTaken = Number(this.homeTimeoutsTaken || 0) + 1;
                    } else if (side === 'away') {
                        if (type === '30s' && this.away30sTimeouts > 0) {
                            this.away30sTimeouts -= 1;
                            this.away30sTimeoutsTaken = Number(this.away30sTimeoutsTaken || 0) + 1;
                        } else if (type !== '30s' && this.awayFullTimeouts > 0) {
                            this.awayFullTimeouts -= 1;
                            this.awayFullTimeoutsTaken = Number(this.awayFullTimeoutsTaken || 0) + 1;
                        }
                        if (this.awayTimeouts > 0) this.awayTimeouts -= 1;
                        this.awayTimeoutsTaken = Number(this.awayTimeoutsTaken || 0) + 1;
                    }
                } else {
                    if (side === 'home' && this.homeTimeouts > 0) {
                        this.homeTimeouts -= 1;
                        this.homeTimeoutsTaken = Number(this.homeTimeoutsTaken || 0) + 1;
                    } else if (side === 'away' && this.awayTimeouts > 0) {
                        this.awayTimeouts -= 1;
                        this.awayTimeoutsTaken = Number(this.awayTimeoutsTaken || 0) + 1;
                    }
                }

                const remaining = Number((side === 'home' ? this.homeTimeouts : this.awayTimeouts) || 0);
                const remFull = Number((side === 'home' ? this.homeFullTimeouts : this.awayFullTimeouts) || 0);
                const rem30s = Number((side === 'home' ? this.home30sTimeouts : this.away30sTimeouts) || 0);
                const takenTotal = Number((side === 'home' ? this.homeTimeoutsTaken : this.awayTimeoutsTaken) || 0);
                const takenFull = Number((side === 'home' ? this.homeFullTimeoutsTaken : this.awayFullTimeoutsTaken) || 0);
                const taken30s = Number((side === 'home' ? this.home30sTimeoutsTaken : this.away30sTimeoutsTaken) || 0);
                const teamName = (side === 'home' ? this.homeTeamName : this.awayTeamName) || (side === 'home' ? 'HOME' : 'AWAY');
                const typeLabel = (type === '30s') ? '30-Second' : 'Full (60s)';

                const mutId = 'mut_' + Date.now() + '_' + Math.random().toString(36).substr(2, 6);
                const timeoutEvent = {
                    id: mutId,
                    game_id: this.gameId,
                    sequence: (this.recentEvents[0]?.sequence || 0) + 1,
                    period: this.currentPeriod,
                    clock_seconds_remaining: this.clockSeconds || 0,
                    team_side: side,
                    jersey_number: null,
                    player_name: teamName || side.toUpperCase(),
                    action_code: 'TIMEOUT',
                    action_type: 'timeout',
                    action_name: `${typeLabel} Timeout`,
                    points: 0,
                    home_score_after: this.homeScore,
                    away_score_after: this.awayScore,
                    description: this.sport === 'basketball'
                        ? `${side.toUpperCase()} ${typeLabel} Timeout (${takenTotal} Taken [${takenFull}F/${taken30s}s], ${remaining} Remain [${remFull}F/${rem30s}s])`
                        : `${side.toUpperCase()} Timeout (${takenTotal} Taken, ${remaining} Remain this set)`,
                    metadata: {
                        timeout_type: type,
                        timeouts_remaining: remaining,
                        full_timeouts_remaining: remFull,
                        thirty_second_timeouts_remaining: rem30s,
                        timeouts_taken: takenTotal,
                        full_timeouts_taken: takenFull,
                        thirty_second_timeouts_taken: taken30s,
                    }
                };
                this.recentEvents.unshift(timeoutEvent);

                this.playSound('tap');
                this.feedbackMessage = this.sport === 'basketball'
                    ? `${typeLabel} Timeout charged to ${side.toUpperCase()} (Taken: ${takenTotal} • Remain: ${remaining})`
                    : `Timeout charged to ${side.toUpperCase()} (Taken: ${takenTotal} • Remain: ${remaining})`;
                this.enqueueSync('callTimeout', [side, type], 'timeouts', mutId);
            },

            // 0ms Advance Period / Set
            nextPeriodFast() {
                if (this.sport === 'basketball' && this.currentPeriod >= 6) {
                    this.pushError('Maximum of 6 periods reached.');
                    return;
                }
                if (!this.canExecute('nextPeriod', 600)) return;

                this.dismissAttentionAlert();

                this.pushUndoSnapshot('period', {
                    currentPeriod: this.currentPeriod,
                    homeFouls: this.homeFouls,
                    awayFouls: this.awayFouls,
                });

                this.currentPeriod += 1;
                this.periodName = this.formatPeriodName(this.currentPeriod);
                this.homeFouls = 0;
                this.awayFouls = 0;

                while (this.homePeriodScores.length < this.currentPeriod) this.homePeriodScores.push(0);
                while (this.awayPeriodScores.length < this.currentPeriod) this.awayPeriodScores.push(0);

                if (this.sport === 'volleyball') {
                    const perSet = Number(config.timeoutsPerSet || 2);
                    this.homeTimeouts = perSet;
                    this.awayTimeouts = perSet;
                } else if (this.sport === 'basketball') {
                    const regPeriods = (this.periodFormat === 'halves') ? 2 : 4;
                    if (this.currentPeriod > regPeriods) {
                        this.otPeriodsAdded = Math.max(this.otPeriodsAdded || 0, this.currentPeriod - regPeriods);
                        const otAddition = Number(config.timeoutsOt ?? 1);
                        this.home30sTimeouts = Number(this.home30sTimeouts || 0) + otAddition;
                        this.away30sTimeouts = Number(this.away30sTimeouts || 0) + otAddition;
                        this.homeTimeouts = Number(this.homeTimeouts || 0) + otAddition;
                        this.awayTimeouts = Number(this.awayTimeouts || 0) + otAddition;
                    }
                }

                const periodMutId = 'mut_' + Date.now() + '_' + Math.random().toString(36).substr(2, 6);
                const periodEvent = {
                    id: periodMutId,
                    game_id: this.gameId,
                    sequence: (this.recentEvents[0]?.sequence || 0) + 1,
                    period: this.currentPeriod,
                    clock_seconds_remaining: this.clockSeconds || 0,
                    team_side: 'home',
                    jersey_number: null,
                    player_name: 'Period Advance',
                    action_code: 'PERIOD',
                    action_type: 'period_change',
                    action_name: `Start of ${this.periodName}`,
                    points: 0,
                    home_score_after: this.homeScore,
                    away_score_after: this.awayScore,
                    description: `Advanced to ${this.periodName}`,
                };
                this.recentEvents.unshift(periodEvent);

                this.playSound('tap');
                this.feedbackMessage = `Advanced to ${this.periodName}`;
                this.enqueueSync(this.sport === 'volleyball' ? 'nextSet' : 'nextPeriod', [], 'period', periodMutId);
            },


            // 0ms Switch Period / Set manually
            setPeriodFast(period) {
                const target = Number(period);
                if (!target || target < 1 || target === this.currentPeriod) return;
                if (this.sport === 'basketball' && target > 6) {
                    this.pushError('Basketball is limited to 6 periods.');
                    return;
                }
                if (!this.canExecute('setPeriodFast', 200)) return;

                this.dismissAttentionAlert();

                this.pushUndoSnapshot('period', {
                    currentPeriod: this.currentPeriod,
                    homeFouls: this.homeFouls,
                    awayFouls: this.awayFouls,
                });

                this.currentPeriod = target;
                this.periodName = this.formatPeriodName(this.currentPeriod);

                const regPeriods = (this.periodFormat === 'halves') ? 2 : 4;
                if (this.sport === 'basketball' && this.currentPeriod > regPeriods) {
                    this.otPeriodsAdded = Math.max(this.otPeriodsAdded || 0, this.currentPeriod - regPeriods);
                }

                while (this.homePeriodScores.length < target) this.homePeriodScores.push(0);
                while (this.awayPeriodScores.length < target) this.awayPeriodScores.push(0);

                this.homeFouls = 0;
                this.awayFouls = 0;

                if (this.sport === 'volleyball') {
                    const perSet = Number(config.timeoutsPerSet || 2);
                    this.homeTimeouts = perSet;
                    this.awayTimeouts = perSet;
                }

                this.playSound('tap');
                this.feedbackMessage = `Switched to ${this.periodName}`;
                this.feedbackType = 'info';

                this.enqueueSync(this.sport === 'volleyball' ? 'setSet' : 'setPeriod', [target], 'period');
            },

            // 0ms Local Undo Execution
            undo() {
                if (!this.canExecute('undo', 250)) return;

                if (this.undoStack.length === 0 && this.recentEvents.length === 0) {
                    this.feedbackMessage = 'No plays to undo.';
                    this.feedbackType = 'info';
                    return;
                }

                if (this.undoStack.length > 0) {
                    const snapshot = this.undoStack.pop();
                    if (snapshot) {
                        this.restoreSnapshot(snapshot);
                    }
                    if (this.recentEvents.length > 0) {
                        const undone = this.recentEvents.shift();
                        this.feedbackMessage = `Reverted: ${undone.description || 'Last play'}`;
                    } else {
                        this.feedbackMessage = 'Last action reverted';
                    }
                } else if (this.recentEvents.length > 0) {
                    const undone = this.recentEvents.shift();
                    this.feedbackMessage = `Reverted: ${undone.description || 'Last play'}`;
                }

                this.recalculateLocalEventsAndScores();
                this.saveLocalState();
                this.feedbackType = 'info';
                this.playSound('tap');

                // Queue undo on server
                this.enqueueSync('undo', [], 'stats');
            },

            pushUndoSnapshot(type, meta) {
                this.undoStack.push({
                    type,
                    meta,
                    currentPeriod: this.currentPeriod,
                    homeScore: this.homeScore,
                    awayScore: this.awayScore,
                    homePeriodScores: [...this.homePeriodScores],
                    awayPeriodScores: [...this.awayPeriodScores],
                    homeFouls: this.homeFouls,
                    awayFouls: this.awayFouls,
                    homeFullTimeouts: this.homeFullTimeouts,
                    home30sTimeouts: this.home30sTimeouts,
                    awayFullTimeouts: this.awayFullTimeouts,
                    away30sTimeouts: this.away30sTimeouts,
                    homeTimeouts: this.homeTimeouts,
                    awayTimeouts: this.awayTimeouts,
                    homeFullTimeoutsTaken: this.homeFullTimeoutsTaken,
                    home30sTimeoutsTaken: this.home30sTimeoutsTaken,
                    awayFullTimeoutsTaken: this.awayFullTimeoutsTaken,
                    away30sTimeoutsTaken: this.away30sTimeoutsTaken,
                    homeTimeoutsTaken: this.homeTimeoutsTaken,
                    awayTimeoutsTaken: this.awayTimeoutsTaken,
                    possession: this.possession,
                    server: this.server,
                    homeRotation: this.homeRotation,
                    awayRotation: this.awayRotation,
                    homeCourt: JSON.parse(JSON.stringify(this.homeCourt)),
                    awayCourt: JSON.parse(JSON.stringify(this.awayCourt)),
                    homeBench: JSON.parse(JSON.stringify(this.homeBench)),
                    awayBench: JSON.parse(JSON.stringify(this.awayBench)),
                });
                if (this.undoStack.length > 500) this.undoStack.shift();
            },

            restoreSnapshot(s) {
                if (s.currentPeriod !== undefined) {
                    this.currentPeriod = s.currentPeriod;
                    this.periodName = this.formatPeriodName(this.currentPeriod);
                }
                this.homeScore = s.homeScore;
                this.awayScore = s.awayScore;
                this.homePeriodScores = [...s.homePeriodScores];
                this.awayPeriodScores = [...s.awayPeriodScores];
                this.homeFouls = s.homeFouls;
                this.awayFouls = s.awayFouls;
                if (s.homeFullTimeouts !== undefined) this.homeFullTimeouts = s.homeFullTimeouts;
                if (s.home30sTimeouts !== undefined) this.home30sTimeouts = s.home30sTimeouts;
                if (s.awayFullTimeouts !== undefined) this.awayFullTimeouts = s.awayFullTimeouts;
                if (s.away30sTimeouts !== undefined) this.away30sTimeouts = s.away30sTimeouts;
                if (s.homeFullTimeoutsTaken !== undefined) this.homeFullTimeoutsTaken = s.homeFullTimeoutsTaken;
                if (s.home30sTimeoutsTaken !== undefined) this.home30sTimeoutsTaken = s.home30sTimeoutsTaken;
                if (s.awayFullTimeoutsTaken !== undefined) this.awayFullTimeoutsTaken = s.awayFullTimeoutsTaken;
                if (s.away30sTimeoutsTaken !== undefined) this.away30sTimeoutsTaken = s.away30sTimeoutsTaken;
                if (s.homeTimeoutsTaken !== undefined) this.homeTimeoutsTaken = s.homeTimeoutsTaken;
                if (s.awayTimeoutsTaken !== undefined) this.awayTimeoutsTaken = s.awayTimeoutsTaken;
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
            enqueueSync(method, args, domain = 'general', mutationId = null) {
                this.lastLocalActionTime = Date.now();
                this.localRevision++;
                this.saveLocalState();

                const id = mutationId || ('mut_' + Date.now() + '_' + Math.random().toString(36).substr(2, 6));

                this.syncQueue.push({
                    id,
                    method,
                    args,
                    domain,
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
                let lastDomain = 'general';

                while (this.syncQueue.length > 0) {
                    const item = this.syncQueue[0];
                    if (item.domain) lastDomain = item.domain;
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

                        // Mutation synchronized: pop from queue and immediately drop matching optimistic event
                        const completed = this.syncQueue.shift();
                        this.saveQueue();

                        if (completed?.id && Array.isArray(this.recentEvents)) {
                            this.recentEvents = this.recentEvents.filter(e => String(e.id) !== String(completed.id));
                        }
                    } catch (err) {
                        console.warn('Background sync deferred (offline or network pause):', err);
                        this.syncStatus = 'offline';
                        this.isSyncing = false;
                        return;
                    }
                }

                // If syncQueue is completely empty, purge any lingering uncommitted local/mut events
                if (this.syncQueue.length === 0 && Array.isArray(this.recentEvents)) {
                    this.recentEvents = this.recentEvents.filter(e => {
                        const idStr = String(e.id);
                        return !idStr.startsWith('mut_') && !idStr.startsWith('local_');
                    });
                }

                // Once mutations are flushed, sync authoritative state snapshot onto server with domain scoping
                try {
                    if (this.$wire && typeof this.$wire.forceClientState === 'function') {
                        const meta = {
                            domain: lastDomain,
                            deviceId: this.deviceId,
                            timestamp: Date.now()
                        };
                        await this.$wire.forceClientState(this.getAuthoritativeSnapshot(), meta);
                    }
                } catch (err) {
                    console.warn('Authoritative state sync deferred:', err);
                }

                this.recalculateLocalEventsAndScores();
                this.syncStatus = 'synced';
                this.isSyncing = false;
                this.saveLocalState();
            }
        };
    }
};
