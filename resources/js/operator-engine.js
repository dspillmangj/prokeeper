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
        'F': { name: 'Pers Foul', points: 0, teamTarget: 'self', isScore: false, isFoul: true, stat: 'fouls_pers', category: 'fouls' },
        'R': { name: 'Off Foul', points: 0, teamTarget: 'self', isScore: false, isFoul: true, stat: 'fouls_off', category: 'fouls' },
        'T': { name: 'Tech Foul', points: 0, teamTarget: 'self', isScore: false, isFoul: true, stat: 'fouls_tech', category: 'fouls' },
        'H': { name: 'Forced Foul', points: 0, teamTarget: 'self', isScore: false, isFoul: true, stat: 'fouls_forced', category: 'fouls' },
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
            homeScore: config.homeScore || 0,
            awayScore: config.awayScore || 0,
            homePeriodScores: (function() {
                const targetLen = sport === 'basketball' ? 6 : 5;
                const arr = Array.isArray(config.homePeriodScores) ? [...config.homePeriodScores] : [];
                while (arr.length < targetLen) arr.push(0);
                return arr.slice(0, targetLen);
            })(),
            awayPeriodScores: (function() {
                const targetLen = sport === 'basketball' ? 6 : 5;
                const arr = Array.isArray(config.awayPeriodScores) ? [...config.awayPeriodScores] : [];
                while (arr.length < targetLen) arr.push(0);
                return arr.slice(0, targetLen);
            })(),
            currentPeriod: config.currentPeriod || 1,
            periodName: config.periodName || (sport === 'volleyball' ? 'Set 1' : '1st Quarter'),
            possession: config.possession || 'home',
            server: config.server || 'home',
            homeFouls: config.homeFouls || 0,
            awayFouls: config.awayFouls || 0,
            homeFullTimeouts: config.homeFullTimeouts ?? 3,
            home30sTimeouts: config.home30sTimeouts ?? 2,
            awayFullTimeouts: config.awayFullTimeouts ?? 3,
            away30sTimeouts: config.away30sTimeouts ?? 2,
            homeTimeouts: config.homeTimeouts ?? (sport === 'volleyball' ? 2 : ((config.homeFullTimeouts ?? 3) + (config.home30sTimeouts ?? 2))),
            awayTimeouts: config.awayTimeouts ?? (sport === 'volleyball' ? 2 : ((config.awayFullTimeouts ?? 3) + (config.away30sTimeouts ?? 2))),
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

            recalculateLocalEventsAndScores() {
                if (!Array.isArray(this.recentEvents)) return;

                // Sort ascending by sequence / reverse chronological to compute running scores
                const chronological = [...this.recentEvents].reverse();

                let runningHome = 0;
                let runningAway = 0;
                const homePeriod = [0, 0, 0, 0, 0, 0];
                const awayPeriod = [0, 0, 0, 0, 0, 0];
                let currentHomeFouls = 0;
                let currentAwayFouls = 0;

                for (const ev of chronological) {
                    const pts = Number(ev.points) || 0;
                    const p = Number(ev.period) || 1;
                    const pIdx = Math.max(0, p - 1);
                    const side = ev.team_side || 'home';

                    if (side === 'home') {
                        runningHome += pts;
                        while (homePeriod.length <= pIdx) homePeriod.push(0);
                        homePeriod[pIdx] = (homePeriod[pIdx] || 0) + pts;
                        if (['F', 'R', 'T'].includes(ev.action_code) && p === this.currentPeriod) {
                            currentHomeFouls++;
                        }
                    } else {
                        runningAway += pts;
                        while (awayPeriod.length <= pIdx) awayPeriod.push(0);
                        awayPeriod[pIdx] = (awayPeriod[pIdx] || 0) + pts;
                        if (['F', 'R', 'T'].includes(ev.action_code) && p === this.currentPeriod) {
                            currentAwayFouls++;
                        }
                    }

                    ev.home_score_after = runningHome;
                    ev.away_score_after = runningAway;
                }

                this.homeScore = Math.max(0, runningHome);
                this.awayScore = Math.max(0, runningAway);
                this.homePeriodScores = homePeriod;
                this.awayPeriodScores = awayPeriod;
                if (this.sport === 'basketball') {
                    this.homeFouls = currentHomeFouls;
                    this.awayFouls = currentAwayFouls;
                }
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

                const localEvent = {
                    id: 'local_manual_' + Date.now(),
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
                this.playSound(points > 0 ? 'score' : 'tap');
                this.feedbackMessage = `Logged: ${desc}`;
                this.feedbackType = 'success';
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
                }], 'stats');
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

                // Remove from recentEvents array and recalculate running state
                this.recentEvents.splice(idx, 1);
                this.recalculateLocalEventsAndScores();

                this.playSound('tap');
                this.feedbackMessage = `Deleted: ${event.description || 'Play'}`;
                this.feedbackType = 'info';

                // Asynchronously sync to backend
                if (typeof event.id === 'number' || !String(event.id).startsWith('local_')) {
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

                if (typeof eventId === 'number' || !String(eventId).startsWith('local_')) {
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
                            this.enqueueSync('setAndExecuteSub', [side, outJ, inJ], 'lineup');
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
                return (this.syncQueue && this.syncQueue.length > 0) || (Date.now() - (this.lastLocalActionTime || 0) < 4000);
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
                        recentEvents: (this.recentEvents || []).slice(0, 50),
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
                            if (typeof s.homeScore !== 'undefined') this.homeScore = s.homeScore;
                            if (typeof s.awayScore !== 'undefined') this.awayScore = s.awayScore;
                            if (Array.isArray(s.homePeriodScores) && s.homePeriodScores.length > 0) this.homePeriodScores = [...s.homePeriodScores];
                            if (Array.isArray(s.awayPeriodScores) && s.awayPeriodScores.length > 0) this.awayPeriodScores = [...s.awayPeriodScores];
                            if (typeof s.currentPeriod !== 'undefined') this.currentPeriod = s.currentPeriod;
                            if (s.possession) this.possession = s.possession;
                            if (s.server) this.server = s.server;
                            if (typeof s.homeFouls !== 'undefined') this.homeFouls = s.homeFouls;
                            if (typeof s.awayFouls !== 'undefined') this.awayFouls = s.awayFouls;
                            if (typeof s.homeTimeouts !== 'undefined') this.homeTimeouts = s.homeTimeouts;
                            if (typeof s.awayTimeouts !== 'undefined') this.awayTimeouts = s.awayTimeouts;
                            if (typeof s.homeFullTimeouts !== 'undefined') this.homeFullTimeouts = s.homeFullTimeouts;
                            if (typeof s.home30sTimeouts !== 'undefined') this.home30sTimeouts = s.home30sTimeouts;
                            if (typeof s.awayFullTimeouts !== 'undefined') this.awayFullTimeouts = s.awayFullTimeouts;
                            if (typeof s.away30sTimeouts !== 'undefined') this.away30sTimeouts = s.away30sTimeouts;
                            if (typeof s.homeRotation !== 'undefined') this.homeRotation = s.homeRotation;
                            if (typeof s.awayRotation !== 'undefined') this.awayRotation = s.awayRotation;
                            if (parsed.homeCourt && Array.isArray(parsed.homeCourt) && parsed.homeCourt.length > 0) this.homeCourt = parsed.homeCourt;
                            if (parsed.awayCourt && Array.isArray(parsed.awayCourt) && parsed.awayCourt.length > 0) this.awayCourt = parsed.awayCourt;
                            if (parsed.homeBench && Array.isArray(parsed.homeBench)) this.homeBench = parsed.homeBench;
                            if (parsed.awayBench && Array.isArray(parsed.awayBench)) this.awayBench = parsed.awayBench;
                            if (parsed.recentEvents && Array.isArray(parsed.recentEvents) && parsed.recentEvents.length > 0) this.recentEvents = parsed.recentEvents;
                            this.localRevision = parsed.version || 0;
                        }
                    }
                } catch (e) {}
            },

            init() {
                window._activeOperator = this;
                this.updateCssVariables();
                this.loadLocalState();
                this.loadQueue();

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

                        // CLIENT IS GOD: If client has pending sync mutations or recent user actions,
                        // NEVER allow server roundtrips or delayed responses to overwrite local client scores.
                        if (this.isQueuePending()) {
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
                        if (typeof data.homeRotation !== 'undefined') this.homeRotation = Number(data.homeRotation);
                        if (typeof data.awayRotation !== 'undefined') this.awayRotation = Number(data.awayRotation);
                        if (data.recentEvents) this.recentEvents = JSON.parse(JSON.stringify(data.recentEvents));
                        if (data.homeCourt || data.awayCourt || data.homeBench || data.awayBench) {
                            this.hotPatchLineups(data);
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
                this.selectedPlayer = null;
                this.showLineupModal = false;
                this.showExecuteSubModal = false;
                this.showPlayByPlayModal = false;
                this.showAddEventModal = false;
                this.editingEvent = null;
                this.showSubSheet = false;
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

                // If in-game roster spreadsheet or team roster grid is open, let spreadsheet engine handle keys
                if (document.querySelector('[data-grid="ingame-roster-grid"]') || document.querySelector('[data-grid="main-roster-grid"]')) {
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
                            this.feedbackMessage = `${targetName} player #${searchNum} is not on the court`;
                            this.feedbackType = 'error';
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
                            this.feedbackMessage = `${targetName} player #${searchNum} is not on the court`;
                            this.feedbackType = 'error';
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
                            this.feedbackMessage = `${targetName} player #${searchNum} is not on the bench`;
                            this.feedbackType = 'error';
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
                            this.feedbackMessage = `${targetName} player #${searchNum} is not on the bench`;
                            this.feedbackType = 'error';
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
                        this.selectedPlayer = null;
                        this.playSound('tap');
                        return;
                    }

                    if (e.key === 'Tab') {
                        e.preventDefault();
                        this.openLineupModal();
                        return;
                    }
                }

                // 6. MAIN DASHBOARD NUMBER ENTRY (Buffer jersey # -> Press [-] for Left Team or [=] for Right Team)
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
                            this.feedbackMessage = `${targetName} #${searchNum} (${benchP.player_name}) is on the bench (Press [S] for Subs)`;
                            this.feedbackType = 'info';
                        } else {
                            this.feedbackMessage = `No ${targetName} player found with Jersey #${searchNum}`;
                            this.feedbackType = 'error';
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
                            this.feedbackMessage = `${targetName} #${searchNum} (${benchP.player_name}) is on the bench (Press [S] for Subs)`;
                            this.feedbackType = 'info';
                        } else {
                            this.feedbackMessage = `No ${targetName} player found with Jersey #${searchNum}`;
                            this.feedbackType = 'error';
                        }
                    }
                    return;
                }

                // Spacebar: Toggle server/possession, or prompt if jerseyBuffer is typed
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
                        this.callTimeoutFast(leftTeam, 'full');
                        return;
                    }

                    // Right Hand Full Timeout: 'G'
                    if (k === 'G') {
                        e.preventDefault();
                        this.callTimeoutFast(rightTeam, 'full');
                        return;
                    }

                    // Left Hand 30s Timeout: 'H'
                    if (k === 'H') {
                        e.preventDefault();
                        if (this.sport === 'basketball') {
                            this.callTimeoutFast(leftTeam, '30s');
                        } else {
                            this.callTimeoutFast(leftTeam, 'full');
                        }
                        return;
                    }

                    // Right Hand 30s Timeout: 'J'
                    if (k === 'J') {
                        e.preventDefault();
                        if (this.sport === 'basketball') {
                            this.callTimeoutFast(rightTeam, '30s');
                        } else {
                            this.callTimeoutFast(rightTeam, 'full');
                        }
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
                    // Left Hand Team Roster Modal: 'R'
                    if (k === 'R') {
                        e.preventDefault();
                        try {
                            const wireEl = document.querySelector('[wire\\:id]');
                            if (wireEl && window.Livewire) {
                                window.Livewire.find(wireEl.getAttribute('wire:id'))?.call('openRosterModal', leftTeam);
                                this.playSound('tap');
                            }
                        } catch (err) {}
                        return;
                    }
                    // Right Hand Team Roster Modal: 'T'
                    if (k === 'T') {
                        e.preventDefault();
                        try {
                            const wireEl = document.querySelector('[wire\\:id]');
                            if (wireEl && window.Livewire) {
                                window.Livewire.find(wireEl.getAttribute('wire:id'))?.call('openRosterModal', rightTeam);
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
                this.enqueueSync('recordQuickStat', [side, jersey, actionCode], 'stats');
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
                const scoreEvent = {
                    id: 'local_adj_' + Date.now(),
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

                this.playSound(delta > 0 ? 'score' : 'tap');
                this.feedbackMessage = `${side.toUpperCase()} Score ${ptsText}`;
                this.feedbackType = 'info';

                this.enqueueSync('adjustScore', [side, delta], 'score');
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

                const subEvent = {
                    id: 'local_sub_' + Date.now(),
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

                this.enqueueSync('setAndExecuteSub', [side, outJ, inJ], 'lineup');
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
                            this.feedbackMessage = `No 30-Second Timeouts remaining for ${side.toUpperCase()}`;
                            this.feedbackType = 'error';
                            this.playSound('tap');
                            return;
                        }
                    } else {
                        const count = (side === 'home') ? this.homeFullTimeouts : this.awayFullTimeouts;
                        if (count <= 0) {
                            this.feedbackMessage = `No Full Timeouts remaining for ${side.toUpperCase()}`;
                            this.feedbackType = 'error';
                            this.playSound('tap');
                            return;
                        }
                    }
                } else {
                    const count = (side === 'home') ? this.homeTimeouts : this.awayTimeouts;
                    if (count <= 0) {
                        this.feedbackMessage = `No Timeouts remaining for ${side.toUpperCase()}`;
                        this.feedbackType = 'error';
                        this.playSound('tap');
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
                });

                if (this.sport === 'basketball') {
                    if (side === 'home') {
                        if (type === '30s' && this.home30sTimeouts > 0) {
                            this.home30sTimeouts -= 1;
                        } else if (type !== '30s' && this.homeFullTimeouts > 0) {
                            this.homeFullTimeouts -= 1;
                        }
                        if (this.homeTimeouts > 0) this.homeTimeouts -= 1;
                    } else if (side === 'away') {
                        if (type === '30s' && this.away30sTimeouts > 0) {
                            this.away30sTimeouts -= 1;
                        } else if (type !== '30s' && this.awayFullTimeouts > 0) {
                            this.awayFullTimeouts -= 1;
                        }
                        if (this.awayTimeouts > 0) this.awayTimeouts -= 1;
                    }
                } else {
                    if (side === 'home' && this.homeTimeouts > 0) {
                        this.homeTimeouts -= 1;
                    } else if (side === 'away' && this.awayTimeouts > 0) {
                        this.awayTimeouts -= 1;
                    }
                }

                const remaining = side === 'home' ? this.homeTimeouts : this.awayTimeouts;
                const remFull = side === 'home' ? this.homeFullTimeouts : this.awayFullTimeouts;
                const rem30s = side === 'home' ? this.home30sTimeouts : this.away30sTimeouts;
                const teamName = side === 'home' ? this.homeTeamName : this.awayTeamName;
                const typeLabel = (type === '30s') ? '30-Second' : 'Full (60s)';

                const timeoutEvent = {
                    id: 'local_to_' + Date.now(),
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
                        ? `${side.toUpperCase()} ${typeLabel} Timeout (${remFull} Full, ${rem30s} 30s remaining)`
                        : `${side.toUpperCase()} Timeout (${remaining} left)`,
                    metadata: {
                        timeout_type: type,
                        timeouts_remaining: remaining,
                        full_timeouts_remaining: remFull,
                        thirty_second_timeouts_remaining: rem30s
                    }
                };
                this.recentEvents.unshift(timeoutEvent);

                this.playSound('tap');
                this.feedbackMessage = this.sport === 'basketball'
                    ? `${typeLabel} Timeout charged to ${side.toUpperCase()} (${remFull} Full, ${rem30s} 30s remaining)`
                    : `Timeout charged to ${side.toUpperCase()} (${remaining} remaining)`;
                this.enqueueSync('callTimeout', [side, type], 'timeouts');
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

                const periodEvent = {
                    id: 'local_period_' + Date.now(),
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
                this.enqueueSync(this.sport === 'volleyball' ? 'nextSet' : 'nextPeriod', [], 'period');
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

                const snapshot = this.undoStack.pop();
                if (snapshot) {
                    this.restoreSnapshot(snapshot);
                }

                // Remove top play from recent events
                if (this.recentEvents.length > 0) {
                    const undone = this.recentEvents.shift();
                    this.recalculateLocalEventsAndScores();
                    this.feedbackMessage = `Reverted: ${undone.description || 'Last play'}`;
                } else {
                    this.feedbackMessage = 'Last action reverted';
                }

                this.feedbackType = 'info';
                this.playSound('tap');

                // Queue undo on server
                this.enqueueSync('undo', [], 'stats');
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
                    homeFullTimeouts: this.homeFullTimeouts,
                    home30sTimeouts: this.home30sTimeouts,
                    awayFullTimeouts: this.awayFullTimeouts,
                    away30sTimeouts: this.away30sTimeouts,
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
                if (s.homeFullTimeouts !== undefined) this.homeFullTimeouts = s.homeFullTimeouts;
                if (s.home30sTimeouts !== undefined) this.home30sTimeouts = s.home30sTimeouts;
                if (s.awayFullTimeouts !== undefined) this.awayFullTimeouts = s.awayFullTimeouts;
                if (s.away30sTimeouts !== undefined) this.away30sTimeouts = s.away30sTimeouts;
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
            enqueueSync(method, args, domain = 'general') {
                this.lastLocalActionTime = Date.now();
                this.localRevision++;
                this.saveLocalState();

                this.syncQueue.push({
                    id: 'mut_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5),
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

                // CLIENT IS GOD: Once mutations are flushed, force client's authoritative state snapshot onto server with domain scoping
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

                this.syncStatus = 'synced';
                this.isSyncing = false;
                this.saveLocalState();
            }
        };
    }
};
