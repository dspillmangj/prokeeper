/**
 * ProKeeper Scorebook Official Signature Pad Component
 * Provides smooth HTML5 stylus/touch/mouse drawing, cursive typing,
 * and high-DPI rasterization for official scorebook ledger compliance.
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('ncaaSignaturePad', (config = {}) => ({
        role: config.role || 'official_scorer',
        name: config.name || '',
        initials: config.initials || '',
        mode: config.mode || 'draw', // 'draw' or 'type'
        font: config.font || 'dancing_script',
        color: config.color || '#000000',
        strokeWidth: 3.5,
        typeSignFormat: 'full', // 'full' or 'initials'
        isCertified: true,
        isSubmitting: false,

        // Canvas state
        canvas: null,
        ctx: null,
        isDrawing: false,
        strokes: [],
        currentStroke: null,
        scale: 1,

        init() {
            this.$nextTick(() => {
                this.setupCanvas();
                if (config.existingData && this.mode === 'draw') {
                    this.loadExistingImage(config.existingData);
                }
            });
        },

        setupCanvas() {
            this.canvas = this.$refs.canvas;
            if (!this.canvas) return;

            const rect = this.canvas.getBoundingClientRect();
            this.scale = window.devicePixelRatio || 2;

            const width = rect.width || 560;
            const height = rect.height || 140;

            this.canvas.width = width * this.scale;
            this.canvas.height = height * this.scale;

            this.ctx = this.canvas.getContext('2d');
            this.ctx.scale(this.scale, this.scale);
            this.ctx.lineCap = 'round';
            this.ctx.lineJoin = 'round';
        },

        loadExistingImage(dataUrl) {
            if (!dataUrl || !this.ctx) return;
            const img = new Image();
            img.onload = () => {
                const rect = this.canvas.getBoundingClientRect();
                this.ctx.clearRect(0, 0, rect.width, rect.height);
                this.ctx.drawImage(img, 0, 0, rect.width, rect.height);
            };
            img.src = dataUrl;
        },

        getPointerPos(e) {
            const rect = this.canvas.getBoundingClientRect();
            return {
                x: e.clientX - rect.left,
                y: e.clientY - rect.top,
            };
        },

        startDrawing(e) {
            if (this.mode !== 'draw') return;
            this.isDrawing = true;
            const pos = this.getPointerPos(e);

            this.currentStroke = {
                color: this.color,
                width: this.strokeWidth,
                points: [pos],
            };

            this.ctx.beginPath();
            this.ctx.strokeStyle = this.color;
            this.ctx.lineWidth = this.strokeWidth;
            this.ctx.moveTo(pos.x, pos.y);
            this.ctx.lineTo(pos.x, pos.y);
            this.ctx.stroke();
        },

        draw(e) {
            if (!this.isDrawing || this.mode !== 'draw') return;
            const pos = this.getPointerPos(e);
            this.currentStroke.points.push(pos);

            const pts = this.currentStroke.points;
            if (pts.length < 2) return;

            this.ctx.beginPath();
            this.ctx.strokeStyle = this.color;
            this.ctx.lineWidth = this.strokeWidth;

            const prev = pts[pts.length - 2];
            this.ctx.moveTo(prev.x, prev.y);
            this.ctx.lineTo(pos.x, pos.y);
            this.ctx.stroke();
        },

        stopDrawing() {
            if (!this.isDrawing) return;
            this.isDrawing = false;
            if (this.currentStroke && this.currentStroke.points.length > 0) {
                this.strokes.push(this.currentStroke);
                this.currentStroke = null;
            }
        },

        redrawCanvas() {
            if (!this.ctx || !this.canvas) return;
            const rect = this.canvas.getBoundingClientRect();
            this.ctx.clearRect(0, 0, rect.width, rect.height);

            for (const stroke of this.strokes) {
                if (!stroke.points || stroke.points.length === 0) continue;

                this.ctx.beginPath();
                this.ctx.strokeStyle = stroke.color;
                this.ctx.lineWidth = stroke.width;

                const first = stroke.points[0];
                this.ctx.moveTo(first.x, first.y);

                if (stroke.points.length === 1) {
                    this.ctx.lineTo(first.x, first.y);
                } else {
                    for (let i = 1; i < stroke.points.length; i++) {
                        this.ctx.lineTo(stroke.points[i].x, stroke.points[i].y);
                    }
                }
                this.ctx.stroke();
            }
        },

        undoStroke() {
            if (this.strokes.length > 0) {
                this.strokes.pop();
                this.redrawCanvas();
            }
        },

        clearCanvas() {
            this.strokes = [];
            this.currentStroke = null;
            if (this.ctx && this.canvas) {
                const rect = this.canvas.getBoundingClientRect();
                this.ctx.clearRect(0, 0, rect.width, rect.height);
            }
        },

        setStroke(w) {
            this.strokeWidth = w;
        },

        setColor(c) {
            this.color = c;
        },

        setMode(m) {
            this.mode = m;
            if (m === 'draw') {
                this.$nextTick(() => {
                    this.setupCanvas();
                    this.redrawCanvas();
                });
            }
        },

        updateInitials() {
            if (!this.name) return;
            const words = this.name.trim().split(/\s+/);
            let inits = '';
            for (const w of words) {
                if (w) inits += w[0].toUpperCase();
            }
            this.initials = inits.slice(0, 4);
        },

        deleteExisting() {
            if (confirm('Are you sure you want to remove this official signature?')) {
                this.$wire.clearSignature(this.role);
            }
        },

        async submitSignature() {
            if (!this.isCertified) return;
            this.isSubmitting = true;

            try {
                let dataUrl = '';
                const signText = this.typeSignFormat === 'full' ? (this.name || 'Official Signature') : (this.initials || 'OFF');

                if (this.mode === 'draw') {
                    if (this.strokes.length === 0 && !config.existingData) {
                        alert('Please draw your signature on the pad before saving, or switch to Type Signature.');
                        this.isSubmitting = false;
                        return;
                    }
                    dataUrl = this.canvas.toDataURL('image/png');
                } else {
                    // Generate crisp rasterized graphic from typed cursive text
                    dataUrl = this.renderTypedSignatureToDataUrl(signText, this.font, this.color);
                }

                await this.$wire.saveSignature(
                    this.role,
                    this.mode,
                    dataUrl,
                    this.name,
                    this.initials,
                    this.font,
                    this.color
                );
            } catch (err) {
                console.error('Failed to save signature:', err);
                alert('Error saving signature. Please try again.');
            } finally {
                this.isSubmitting = false;
            }
        },

        renderTypedSignatureToDataUrl(text, fontStyle, color) {
            const offCanvas = document.createElement('canvas');
            const dpr = 2;
            const w = 480;
            const h = 120;
            offCanvas.width = w * dpr;
            offCanvas.height = h * dpr;
            const oCtx = offCanvas.getContext('2d');
            oCtx.scale(dpr, dpr);

            let fontDef = '42px "Dancing Script", "Brush Script MT", cursive';
            if (fontStyle === 'caveat') {
                fontDef = 'bold 44px "Caveat", "Segoe Script", cursive';
            } else if (fontStyle === 'great_vibes') {
                fontDef = '48px "Great Vibes", cursive';
            } else if (fontStyle === 'formal') {
                fontDef = 'italic 34px Georgia, "Times New Roman", serif';
            }

            oCtx.fillStyle = color || '#000000';
            oCtx.font = fontDef;
            oCtx.textAlign = 'center';
            oCtx.textBaseline = 'middle';
            oCtx.fillText(text, w / 2, h / 2);

            return offCanvas.toDataURL('image/png');
        }
    }));
});
