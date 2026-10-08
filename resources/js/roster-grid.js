/**
 * ProKeeper Literal Spreadsheet Roster Engine
 * Google Sheets / Excel style grid navigation and direct clipboard paste
 */

export function parseRosterClipboardText(text) {
    if (!text || typeof text !== 'string') return [];
    const lines = text.split(/\r\n|\r|\n/).map(l => l.trim()).filter(Boolean);
    const parsed = [];

    for (const line of lines) {
        let cells = [];
        if (line.includes('\t')) {
            cells = line.split('\t').map(c => c.trim()).filter(c => c !== '');
        } else if (line.includes(',') && !/^[0-9]+$/.test(line)) {
            cells = line.split(',').map(c => c.trim().replace(/^"|"$/g, '')).filter(c => c !== '');
        } else {
            cells = [line];
        }

        let jersey = '';
        let name = '';

        if (cells.length >= 2) {
            const c0Clean = cells[0].replace(/^#/, '').trim();
            const cLastClean = cells[cells.length - 1].replace(/^#/, '').trim();

            if (/^\d{1,4}[A-Za-z]?$/.test(c0Clean)) {
                jersey = c0Clean;
                const textCells = cells.slice(1).filter(c => !['starter', 'start', 'bench', 'libero', 'lib', 'yes', 'no', 'true', 'false'].includes(c.toLowerCase()));
                if (textCells.length >= 2 && textCells[textCells.length - 1].length <= 3 && /^[A-Z\/]+$/i.test(textCells[textCells.length - 1])) {
                    textCells.pop();
                }
                name = textCells.join(' ');
            } else if (/^\d{1,4}[A-Za-z]?$/.test(cLastClean)) {
                jersey = cLastClean;
                const textCells = cells.slice(0, -1).filter(c => !['starter', 'start', 'bench', 'libero', 'lib', 'yes', 'no', 'true', 'false'].includes(c.toLowerCase()));
                name = textCells.join(' ');
            } else {
                const numIdx = cells.findIndex(c => /^\#?\d{1,4}[A-Za-z]?$/.test(c.trim()));
                if (numIdx !== -1) {
                    jersey = cells[numIdx].replace(/^#/, '').trim();
                    const textCells = cells.filter((_, idx) => idx !== numIdx);
                    name = textCells.join(' ');
                } else {
                    name = cells.join(' ');
                }
            }
        } else if (cells.length === 1) {
            const raw = cells[0];
            const matchLead = raw.match(/^\#?(\d{1,4}[A-Za-z]?)\s+(.+)$/);
            const matchTrail = raw.match(/^(.+?)\s+\#?(\d{1,4}[A-Za-z]?)$/);

            if (matchLead) {
                jersey = matchLead[1];
                let rest = matchLead[2].trim();
                rest = rest.replace(/\s+(starter|start|bench|libero|lib)$/i, '');
                name = rest;
            } else if (matchTrail) {
                name = matchTrail[1].trim();
                jersey = matchTrail[2];
            } else {
                name = raw;
            }
        }

        if (jersey || name) {
            parsed.push({
                jersey_number: jersey,
                name: name.trim()
            });
        }
    }

    return parsed;
}

export function handleGridKeydown(e, rowIndex, fieldName, component, gridId = 'roster-grid') {
    const fields = ['jersey_number', 'name'];
    const colIndex = fields.indexOf(fieldName);
    const target = e.target;
    const isAtStart = target.selectionStart === 0 && target.selectionEnd === 0;
    const isAtEnd = target.selectionStart === target.value.length && target.selectionEnd === target.value.length;
    const isAllSelected = target.selectionStart === 0 && target.selectionEnd === target.value.length;

    function focusCell(r, colName, select = true) {
        component.$nextTick(() => {
            const el = document.querySelector(`[data-grid='${gridId}'] [data-row='${r}'][data-field='${colName}']`);
            if (el) {
                el.focus();
                if (select && el.select) el.select();
            }
        });
    }

    if (e.key === 'ArrowUp') {
        if (rowIndex > 0) {
            e.preventDefault();
            focusCell(rowIndex - 1, fieldName);
        }
    } else if (e.key === 'ArrowDown') {
        e.preventDefault();
        if (rowIndex === component.rows.length - 1) {
            component.addEmptyRow();
            focusCell(rowIndex + 1, fieldName);
        } else {
            focusCell(rowIndex + 1, fieldName);
        }
    } else if (e.key === 'ArrowLeft') {
        if (isAtStart || isAllSelected) {
            if (colIndex > 0) {
                e.preventDefault();
                focusCell(rowIndex, fields[colIndex - 1]);
            } else if (rowIndex > 0) {
                e.preventDefault();
                focusCell(rowIndex - 1, fields[fields.length - 1]);
            }
        }
    } else if (e.key === 'ArrowRight') {
        if (isAtEnd || isAllSelected) {
            if (colIndex < fields.length - 1) {
                e.preventDefault();
                focusCell(rowIndex, fields[colIndex + 1]);
            } else if (rowIndex < component.rows.length - 1) {
                e.preventDefault();
                focusCell(rowIndex + 1, fields[0]);
            } else if (rowIndex === component.rows.length - 1) {
                e.preventDefault();
                component.addEmptyRow();
                focusCell(rowIndex + 1, fields[0]);
            }
        }
    } else if (e.key === 'Enter') {
        e.preventDefault();
        if (rowIndex === component.rows.length - 1) {
            component.addEmptyRow();
            focusCell(rowIndex + 1, 'jersey_number');
        } else {
            focusCell(rowIndex + 1, fieldName);
        }
    } else if (e.key === 'Tab' && !e.shiftKey) {
        if (colIndex === fields.length - 1 && rowIndex === component.rows.length - 1) {
            e.preventDefault();
            component.addEmptyRow();
            focusCell(rowIndex + 1, fields[0]);
        }
    }
}

export function handleGridPaste(e, startRowIndex, startFieldName, component, gridId = 'roster-grid') {
    const clipData = (e.clipboardData || window.clipboardData)?.getData('text');
    if (!clipData) return;

    if (clipData.includes('\n') || clipData.includes('\t') || (clipData.includes(',') && !/^[0-9]+$/.test(clipData.trim()))) {
        e.preventDefault();
        const items = parseRosterClipboardText(clipData);
        if (items.length === 0) return;

        let curRow = startRowIndex;
        items.forEach(item => {
            if (curRow < component.rows.length) {
                component.rows[curRow].jersey_number = item.jersey_number || component.rows[curRow].jersey_number;
                component.rows[curRow].name = item.name || component.rows[curRow].name;
            } else {
                component.rows.push({
                    id: null,
                    player_id: null,
                    jersey_number: item.jersey_number,
                    name: item.name,
                    is_on_court: curRow < 5,
                });
            }
            curRow++;
        });

        component.isDirty = true;

        component.$nextTick(() => {
            const nextFocusRow = Math.min(curRow, component.rows.length - 1);
            const el = document.querySelector(`[data-grid='${gridId}'] [data-row='${nextFocusRow}'][data-field='name']`);
            if (el) el.focus();
        });
    }
}

export function rosterSpreadsheet(config) {
    return {
        sport: config.sport,
        saveUrl: config.saveUrl,
        teamSlug: config.teamSlug,
        rows: Array.isArray(config.initialPlayers) ? config.initialPlayers : [],
        isDirty: false,
        isSaving: false,
        saveSuccessMessage: '',

        init() {
            // Ensure at least 15 rows for authentic spreadsheet feel
            const minRows = 15;
            while (this.rows.length < minRows) {
                this.rows.push({ id: null, player_id: null, jersey_number: '', name: '' });
            }
            this.$watch('rows', () => { this.isDirty = true; }, { deep: true });
        },

        addEmptyRow() {
            this.rows.push({ id: null, player_id: null, jersey_number: '', name: '' });
            this.isDirty = true;
        },

        removeRow(index) {
            this.rows.splice(index, 1);
            this.isDirty = true;
            if (this.rows.length < 5) {
                this.addEmptyRow();
            }
        },

        handleKeydown(e, rowIndex, fieldName) {
            handleGridKeydown(e, rowIndex, fieldName, this, 'main-roster-grid');
        },

        handlePaste(e, rowIndex, fieldName) {
            handleGridPaste(e, rowIndex, fieldName, this, 'main-roster-grid');
        },

        async saveRoster() {
            const validRows = this.rows.filter(r => (r.jersey_number || '').trim() !== '' && (r.name || '').trim() !== '');

            if (validRows.length === 0 && this.rows.some(r => r.jersey_number || r.name)) {
                alert('Please ensure each player has both a Jersey # and a Name.');
                return;
            }

            this.isSaving = true;
            this.saveSuccessMessage = '';

            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const response = await fetch(this.saveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf || ''
                    },
                    body: JSON.stringify({ players: validRows })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.isDirty = false;
                    this.saveSuccessMessage = 'Roster saved!';
                    setTimeout(() => { this.saveSuccessMessage = ''; }, 3000);
                } else {
                    alert(data.message || 'Error saving roster.');
                }
            } catch (err) {
                console.error(err);
                alert('A network error occurred while saving.');
            } finally {
                this.isSaving = false;
            }
        },

        get validPlayerCount() {
            return this.rows.filter(r => (r.jersey_number || '').trim() !== '' && (r.name || '').trim() !== '').length;
        }
    };
}

export function inGameRosterSpreadsheet(config) {
    return {
        teamSide: config.teamSide || 'home',
        rows: Array.isArray(config.initialPlayers) ? config.initialPlayers : [],
        isDirty: false,
        isSaving: false,
        saveSuccessMessage: '',

        init() {
            const minRows = 12;
            const maxCourt = config.sport === 'volleyball' ? 6 : 5;
            while (this.rows.length < minRows) {
                const courtCount = this.rows.filter(r => r.is_on_court).length;
                this.rows.push({
                    id: null,
                    jersey_number: '',
                    name: '',
                    is_on_court: courtCount < maxCourt,
                });
            }
            this.$watch('rows', () => { this.isDirty = true; }, { deep: true });
        },

        addEmptyRow() {
            const courtCount = this.rows.filter(r => r.is_on_court).length;
            const maxCourt = config.sport === 'volleyball' ? 6 : 5;
            this.rows.push({
                id: null,
                jersey_number: '',
                name: '',
                is_on_court: courtCount < maxCourt,
            });
            this.isDirty = true;
        },

        removeRow(index) {
            this.rows.splice(index, 1);
            this.isDirty = true;
            if (this.rows.length < 5) {
                this.addEmptyRow();
            }
        },

        toggleCourt(index) {
            if (this.rows[index]) {
                this.rows[index].is_on_court = !this.rows[index].is_on_court;
                this.isDirty = true;
            }
        },

        handleKeydown(e, rowIndex, fieldName) {
            handleGridKeydown(e, rowIndex, fieldName, this, 'ingame-roster-grid');
        },

        handlePaste(e, rowIndex, fieldName) {
            handleGridPaste(e, rowIndex, fieldName, this, 'ingame-roster-grid');
        },

        async saveAndApply(wire) {
            const validRows = this.rows.filter(r => (r.jersey_number || '').trim() !== '' && (r.name || '').trim() !== '');
            this.isSaving = true;
            try {
                if (wire && wire.saveRosterSpreadsheet) {
                    await wire.saveRosterSpreadsheet(this.teamSide, validRows);
                    this.isDirty = false;
                    this.saveSuccessMessage = 'Lineup saved!';
                    setTimeout(() => { this.saveSuccessMessage = ''; }, 3000);
                }
            } catch (err) {
                console.error(err);
            } finally {
                this.isSaving = false;
            }
        },

        get validCount() {
            return this.rows.filter(r => (r.jersey_number || '').trim() !== '' && (r.name || '').trim() !== '').length;
        },

        get onCourtCount() {
            return this.rows.filter(r => r.is_on_court && (r.jersey_number || '').trim() !== '').length;
        }
    };
}

// Attach globally for inline Blade templates & Alpine components
if (typeof window !== 'undefined') {
    window.ProKeeperRosterGrid = {
        parseRosterClipboardText,
        handleGridKeydown,
        handleGridPaste,
        rosterSpreadsheet,
        inGameRosterSpreadsheet
    };
    window.rosterSpreadsheet = rosterSpreadsheet;
    window.inGameRosterSpreadsheet = inGameRosterSpreadsheet;
}
