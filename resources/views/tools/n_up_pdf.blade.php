@extends('layouts.app')

@section('title', 'N-Up PDF - Combine Multiple Pages Per Sheet - ToolPDF')

@section('content')

<style>
:root {
    --nup-color: #7c3aed;
    --nup-dark:  #5b21b6;
}
.nup-hero {
    background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%);
    color: #fff;
    padding: 44px 0 28px;
}
.nup-hero h1 { font-size: 1.95rem; font-weight: 800; margin-bottom: .35rem; }
.nup-badge {
    background: rgba(255,255,255,.18);
    border: 1px solid rgba(255,255,255,.35);
    font-size: .73rem; padding: .3em .65em;
    border-radius: 20px;
}
.nup-drop-zone {
    border: 2.5px dashed #c4b5fd !important;
    border-radius: 14px;
    cursor: pointer;
    transition: background .2s, border-color .2s;
    background: #faf5ff;
    min-height: 180px;
}
.nup-drop-zone:hover, .nup-drop-zone.drag-over {
    background: #ede9fe;
    border-color: #7c3aed !important;
}
.nup-layout-radio { display: none; }
.nup-layout-label {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    padding: 12px 14px;
    cursor: pointer;
    transition: border-color .2s, background .2s;
    min-width: 80px;
    font-size: .82rem;
    font-weight: 600;
    color: #475569;
    user-select: none;
}
.nup-layout-label:hover { border-color: #a78bfa; background: #f5f3ff; }
.nup-layout-radio:checked + .nup-layout-label {
    border-color: #7c3aed;
    background: #ede9fe;
    color: #5b21b6;
}
.nup-grid-preview {
    display: grid;
    gap: 2px;
    width: 52px; height: 40px;
}
.nup-grid-cell {
    background: #a78bfa;
    border-radius: 2px;
}
.nup-action-btn {
    background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%);
    border: none; color: #fff;
    font-size: 1.05rem; font-weight: 700;
    padding: 13px 40px;
    border-radius: 50px;
    box-shadow: 0 4px 16px rgba(124,58,237,.35);
    transition: opacity .2s, transform .1s;
}
.nup-action-btn:hover:not(:disabled) { opacity: .92; transform: translateY(-1px); }
.nup-action-btn:disabled { opacity: .4; cursor: not-allowed; }
#nup-preview-canvas {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    max-width: 100%;
    max-height: 260px;
    object-fit: contain;
    background: #fff;
}
</style>

<section class="nup-hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-9">
                <h1><i class="bi bi-grid me-2"></i>N-Up PDF</h1>
                <p class="mb-3" style="opacity:.9;font-size:1rem">
                    Combine multiple PDF pages onto a single sheet — 2, 4, 6 or 9 pages per sheet.
                    Perfect for printing handouts or saving paper.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <span class="nup-badge">&#128306; Browser-side</span>
                    <span class="nup-badge">No upload required</span>
                    <span class="nup-badge">2-up / 4-up / 6-up / 9-up</span>
                </div>
            </div>
            <div class="col-lg-3 text-lg-end mt-3 mt-lg-0">
                <i class="bi bi-grid-3x3" style="font-size:4.5rem;opacity:.22"></i>
            </div>
        </div>
    </div>
</section>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">

            {{-- Drop Zone --}}
            <div id="nup-drop-zone" class="nup-drop-zone d-flex flex-column align-items-center justify-content-center text-center p-5 mb-4">
                <i class="bi bi-file-earmark-pdf text-purple mb-3" style="font-size:3.5rem;color:#7c3aed"></i>
                <h5 class="fw-bold mb-1">Drop your PDF here</h5>
                <p class="text-muted mb-3">or click to browse</p>
                <button type="button" class="btn btn-sm px-4" style="background:#7c3aed;color:#fff;border-radius:20px" onclick="document.getElementById('nup-file-input').click()">
                    <i class="bi bi-folder2-open me-1"></i> Choose File
                </button>
                <input type="file" id="nup-file-input" class="d-none" accept=".pdf,application/pdf">
            </div>

            {{-- File Info Bar --}}
            <div id="nup-file-bar" class="d-none mb-4">
                <div class="d-flex align-items-center justify-content-between p-3 bg-white border rounded-3 shadow-sm">
                    <div class="d-flex align-items-center gap-3">
                        <i class="bi bi-file-earmark-pdf fs-3 text-danger"></i>
                        <div>
                            <div class="fw-bold text-truncate" style="max-width:260px" id="nup-file-name">file.pdf</div>
                            <small class="text-muted" id="nup-page-count">—</small>
                        </div>
                    </div>
                    <button class="btn btn-outline-secondary btn-sm rounded-circle px-2" id="nup-btn-remove" title="Remove">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>

            {{-- Options Card --}}
            <div id="nup-options-card" class="card shadow-sm border-0 mb-4 d-none">
                <div class="card-body p-4">

                    {{-- Layout Selector --}}
                    <h6 class="fw-bold mb-3">Layout</h6>
                    <div class="d-flex flex-wrap gap-3 mb-4">

                        <div>
                            <input type="radio" name="nup-layout" id="nup-2up" class="nup-layout-radio" value="2" checked>
                            <label for="nup-2up" class="nup-layout-label">
                                <div class="nup-grid-preview" style="grid-template-columns:1fr 1fr;grid-template-rows:1fr">
                                    <div class="nup-grid-cell"></div>
                                    <div class="nup-grid-cell"></div>
                                </div>
                                2-up (1×2)
                            </label>
                        </div>

                        <div>
                            <input type="radio" name="nup-layout" id="nup-4up" class="nup-layout-radio" value="4">
                            <label for="nup-4up" class="nup-layout-label">
                                <div class="nup-grid-preview" style="grid-template-columns:1fr 1fr;grid-template-rows:1fr 1fr">
                                    <div class="nup-grid-cell"></div>
                                    <div class="nup-grid-cell"></div>
                                    <div class="nup-grid-cell"></div>
                                    <div class="nup-grid-cell"></div>
                                </div>
                                4-up (2×2)
                            </label>
                        </div>

                        <div>
                            <input type="radio" name="nup-layout" id="nup-6up" class="nup-layout-radio" value="6">
                            <label for="nup-6up" class="nup-layout-label">
                                <div class="nup-grid-preview" style="grid-template-columns:1fr 1fr;grid-template-rows:1fr 1fr 1fr">
                                    <div class="nup-grid-cell"></div>
                                    <div class="nup-grid-cell"></div>
                                    <div class="nup-grid-cell"></div>
                                    <div class="nup-grid-cell"></div>
                                    <div class="nup-grid-cell"></div>
                                    <div class="nup-grid-cell"></div>
                                </div>
                                6-up (2×3)
                            </label>
                        </div>

                        <div>
                            <input type="radio" name="nup-layout" id="nup-9up" class="nup-layout-radio" value="9">
                            <label for="nup-9up" class="nup-layout-label">
                                <div class="nup-grid-preview" style="grid-template-columns:1fr 1fr 1fr;grid-template-rows:1fr 1fr 1fr">
                                    <div class="nup-grid-cell"></div><div class="nup-grid-cell"></div><div class="nup-grid-cell"></div>
                                    <div class="nup-grid-cell"></div><div class="nup-grid-cell"></div><div class="nup-grid-cell"></div>
                                    <div class="nup-grid-cell"></div><div class="nup-grid-cell"></div><div class="nup-grid-cell"></div>
                                </div>
                                9-up (3×3)
                            </label>
                        </div>

                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-sm-4">
                            <label class="form-label fw-semibold">Output Orientation</label>
                            <select id="nup-orientation" class="form-select">
                                <option value="portrait">Portrait (A4)</option>
                                <option value="landscape">Landscape (A4)</option>
                            </select>
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label fw-semibold">Cell Background</label>
                            <select id="nup-bg" class="form-select">
                                <option value="white">White</option>
                                <option value="#f1f5f9">Light Gray</option>
                            </select>
                        </div>
                        <div class="col-sm-4 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="nup-borders" checked>
                                <label class="form-check-label fw-semibold" for="nup-borders">Show page borders</label>
                            </div>
                        </div>
                    </div>

                    {{-- Preview --}}
                    <div id="nup-preview-wrap" class="text-center mb-4 d-none">
                        <p class="text-muted small mb-2">Preview of first output sheet</p>
                        <canvas id="nup-preview-canvas"></canvas>
                    </div>

                    <button type="button" id="nup-btn-preview" class="btn btn-outline-secondary me-2">
                        <i class="bi bi-eye me-1"></i> Update Preview
                    </button>

                    {{-- Progress --}}
                    <div id="nup-progress" class="mt-4 d-none">
                        <div class="d-flex justify-content-between mb-1 small">
                            <span id="nup-progress-msg" class="text-muted">Processing…</span>
                            <span id="nup-progress-pages" class="text-muted"></span>
                        </div>
                        <div class="progress" style="height:10px;border-radius:6px">
                            <div id="nup-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated"
                                 style="width:0%;background:#7c3aed"></div>
                        </div>
                    </div>

                    <div id="nup-error" class="alert alert-danger mt-3 d-none"></div>

                    <div class="text-center mt-4">
                        <button type="button" id="nup-btn-convert" class="nup-action-btn" disabled>
                            <i class="bi bi-download me-2"></i>
                            <span id="nup-btn-text">Generate N-Up PDF</span>
                            <span class="spinner-border spinner-border-sm d-none ms-2" id="nup-spinner"></span>
                        </button>
                    </div>

                </div>
            </div>

            <div class="text-center text-muted small mt-3">
                <i class="bi bi-shield-check text-success me-1"></i>
                All processing happens entirely in your browser. No files are sent to any server.
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
<script>pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
(function () {
    'use strict';

    // --- Layout configs: { cols, rows, n } ---
    const LAYOUTS = {
        '2':  { cols: 2, rows: 1 },
        '4':  { cols: 2, rows: 2 },
        '6':  { cols: 2, rows: 3 },
        '9':  { cols: 3, rows: 3 },
    };

    // A4 in mm
    const A4_W = 210, A4_H = 297;

    let pdfDoc = null;
    let selectedFile = null;

    const dropZone   = document.getElementById('nup-drop-zone');
    const fileInput  = document.getElementById('nup-file-input');
    const fileBar    = document.getElementById('nup-file-bar');
    const fileName   = document.getElementById('nup-file-name');
    const pageCount  = document.getElementById('nup-page-count');
    const btnRemove  = document.getElementById('nup-btn-remove');
    const optCard    = document.getElementById('nup-options-card');
    const btnConvert = document.getElementById('nup-btn-convert');
    const btnPreview = document.getElementById('nup-btn-preview');
    const spinner    = document.getElementById('nup-spinner');
    const btnText    = document.getElementById('nup-btn-text');
    const progress   = document.getElementById('nup-progress');
    const progressBar= document.getElementById('nup-progress-bar');
    const progressMsg= document.getElementById('nup-progress-msg');
    const progressPg = document.getElementById('nup-progress-pages');
    const errBox     = document.getElementById('nup-error');
    const previewWrap= document.getElementById('nup-preview-wrap');
    const previewCanvas = document.getElementById('nup-preview-canvas');

    // Drag & drop
    dropZone.addEventListener('click', () => fileInput.click());
    dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
    dropZone.addEventListener('drop', e => { e.preventDefault(); dropZone.classList.remove('drag-over'); if (e.dataTransfer.files[0]) handleFile(e.dataTransfer.files[0]); });
    fileInput.addEventListener('change', e => { if (e.target.files[0]) handleFile(e.target.files[0]); });
    btnRemove.addEventListener('click', resetAll);

    // Re-render preview on option change
    document.querySelectorAll('input[name="nup-layout"]').forEach(r => r.addEventListener('change', () => buildPreview()));
    document.getElementById('nup-orientation').addEventListener('change', () => buildPreview());
    document.getElementById('nup-bg').addEventListener('change', () => buildPreview());
    document.getElementById('nup-borders').addEventListener('change', () => buildPreview());
    btnPreview.addEventListener('click', buildPreview);

    function showError(msg) {
        errBox.textContent = msg;
        errBox.classList.remove('d-none');
    }

    function resetAll() {
        pdfDoc = null; selectedFile = null; fileInput.value = '';
        dropZone.classList.remove('d-none');
        fileBar.classList.add('d-none');
        optCard.classList.add('d-none');
        previewWrap.classList.add('d-none');
        btnConvert.disabled = true;
        progress.classList.add('d-none');
        errBox.classList.add('d-none');
    }

    async function handleFile(file) {
        errBox.classList.add('d-none');
        if (!file.name.toLowerCase().endsWith('.pdf') && file.type !== 'application/pdf') {
            showError('Please select a valid PDF file.'); return;
        }
        selectedFile = file;
        fileName.textContent = file.name;
        pageCount.textContent = 'Loading…';
        dropZone.classList.add('d-none');
        fileBar.classList.remove('d-none');
        optCard.classList.remove('d-none');
        btnConvert.disabled = true;

        try {
            const buf = await file.arrayBuffer();
            pdfDoc = await pdfjsLib.getDocument({ data: new Uint8Array(buf) }).promise;
            pageCount.textContent = pdfDoc.numPages + ' page' + (pdfDoc.numPages !== 1 ? 's' : '');
            btnConvert.disabled = false;
            buildPreview();
        } catch (e) {
            showError('Could not open PDF: ' + e.message);
            resetAll();
        }
    }

    function getOptions() {
        const layout = document.querySelector('input[name="nup-layout"]:checked').value;
        const { cols, rows } = LAYOUTS[layout];
        const orientation = document.getElementById('nup-orientation').value;
        const bg = document.getElementById('nup-bg').value;
        const borders = document.getElementById('nup-borders').checked;
        const landscape = orientation === 'landscape';
        const sheetW = landscape ? A4_H : A4_W;
        const sheetH = landscape ? A4_W : A4_H;
        return { layout, cols, rows, n: cols * rows, landscape, sheetW, sheetH, bg, borders };
    }

    // Render a single source page to a temp canvas at given scale
    async function renderPageToCanvas(pageNum, scale) {
        const page = await pdfDoc.getPage(pageNum);
        const vp = page.getViewport({ scale });
        const c = document.createElement('canvas');
        c.width  = Math.round(vp.width);
        c.height = Math.round(vp.height);
        const ctx = c.getContext('2d');
        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, c.width, c.height);
        await page.render({ canvasContext: ctx, viewport: vp }).promise;
        return c;
    }

    // Build one output sheet canvas (pixels). pageNums is array of page numbers (1-based) for this sheet.
    async function buildSheetCanvas(pageNums, opts, pxPerMm) {
        const { cols, rows, sheetW, sheetH, bg, borders } = opts;
        const canvasW = Math.round(sheetW * pxPerMm);
        const canvasH = Math.round(sheetH * pxPerMm);
        const canvas = document.createElement('canvas');
        canvas.width  = canvasW;
        canvas.height = canvasH;
        const ctx = canvas.getContext('2d');

        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, canvasW, canvasH);

        const MARGIN_MM = 4;
        const GAP_MM    = 3;
        const margin = MARGIN_MM * pxPerMm;
        const gap    = GAP_MM * pxPerMm;

        const totalGapX = gap * (cols - 1);
        const totalGapY = gap * (rows - 1);
        const cellW = (canvasW - 2 * margin - totalGapX) / cols;
        const cellH = (canvasH - 2 * margin - totalGapY) / rows;

        for (let i = 0; i < pageNums.length; i++) {
            const col = i % cols;
            const row = Math.floor(i / cols);
            const cellX = margin + col * (cellW + gap);
            const cellY = margin + row * (cellH + gap);

            // Draw cell background
            ctx.fillStyle = bg;
            ctx.fillRect(cellX, cellY, cellW, cellH);

            const pgNum = pageNums[i];
            if (pgNum !== null) {
                try {
                    const page = await pdfDoc.getPage(pgNum);
                    const pvp  = page.getViewport({ scale: 1 });
                    // Fit page into cell keeping aspect ratio
                    const scaleX = cellW / pvp.width;
                    const scaleY = cellH / pvp.height;
                    const fitScale = Math.min(scaleX, scaleY);
                    const drawW = pvp.width * fitScale;
                    const drawH = pvp.height * fitScale;
                    const drawX = cellX + (cellW - drawW) / 2;
                    const drawY = cellY + (cellH - drawH) / 2;

                    const vp = page.getViewport({ scale: fitScale });
                    const pgCanvas = document.createElement('canvas');
                    pgCanvas.width  = Math.round(vp.width);
                    pgCanvas.height = Math.round(vp.height);
                    const pgCtx = pgCanvas.getContext('2d');
                    pgCtx.fillStyle = '#fff';
                    pgCtx.fillRect(0, 0, pgCanvas.width, pgCanvas.height);
                    await page.render({ canvasContext: pgCtx, viewport: vp }).promise;
                    ctx.drawImage(pgCanvas, drawX, drawY, drawW, drawH);
                } catch (e) { /* skip */ }
            }

            if (borders) {
                ctx.strokeStyle = '#94a3b8';
                ctx.lineWidth = 1;
                ctx.strokeRect(cellX + 0.5, cellY + 0.5, cellW - 1, cellH - 1);
            }
        }
        return canvas;
    }

    async function buildPreview() {
        if (!pdfDoc) return;
        errBox.classList.add('d-none');
        btnPreview.disabled = true;
        try {
            const opts = getOptions();
            // Use first N pages for preview
            const firstSheet = [];
            for (let i = 0; i < opts.n; i++) {
                firstSheet.push(i < pdfDoc.numPages ? i + 1 : null);
            }
            const pxPerMm = 2; // low resolution for preview
            const sheetCanvas = await buildSheetCanvas(firstSheet, opts, pxPerMm);
            previewCanvas.width  = sheetCanvas.width;
            previewCanvas.height = sheetCanvas.height;
            previewCanvas.getContext('2d').drawImage(sheetCanvas, 0, 0);
            previewWrap.classList.remove('d-none');
        } catch(e) {
            console.error(e);
        } finally {
            btnPreview.disabled = false;
        }
    }

    btnConvert.addEventListener('click', async () => {
        if (!pdfDoc) return;
        errBox.classList.add('d-none');
        btnConvert.disabled = true;
        spinner.classList.remove('d-none');
        btnText.textContent = 'Processing…';
        progress.classList.remove('d-none');
        progressBar.style.width = '0%';

        try {
            const opts = getOptions();
            const { jsPDF } = window.jspdf;
            const orientation = opts.landscape ? 'landscape' : 'portrait';
            const doc = new jsPDF({ orientation, unit: 'mm', format: 'a4' });

            const totalPages = pdfDoc.numPages;
            const totalSheets = Math.ceil(totalPages / opts.n);
            const PX_PER_MM = 3.78; // ~96 DPI

            for (let s = 0; s < totalSheets; s++) {
                if (s > 0) doc.addPage('a4', orientation);

                const pageNums = [];
                for (let i = 0; i < opts.n; i++) {
                    const pgIdx = s * opts.n + i;
                    pageNums.push(pgIdx < totalPages ? pgIdx + 1 : null);
                }

                progressMsg.textContent = `Rendering sheet ${s + 1} of ${totalSheets}…`;
                progressPg.textContent  = `Pages ${s * opts.n + 1}–${Math.min((s + 1) * opts.n, totalPages)} / ${totalPages}`;
                const pct = Math.round((s / totalSheets) * 90);
                progressBar.style.width = pct + '%';

                const sheetCanvas = await buildSheetCanvas(pageNums, opts, PX_PER_MM);
                const imgData = sheetCanvas.toDataURL('image/jpeg', 0.92);
                doc.addImage(imgData, 'JPEG', 0, 0, opts.sheetW, opts.sheetH);
            }

            progressMsg.textContent = 'Saving PDF…';
            progressBar.style.width = '100%';

            const baseName = selectedFile.name.replace(/\.pdf$/i, '');
            doc.save(baseName + '_nup.pdf');

            setTimeout(() => {
                progress.classList.add('d-none');
                btnText.textContent = 'Generate N-Up PDF';
            }, 1500);

        } catch (e) {
            console.error(e);
            showError('Error generating PDF: ' + e.message);
        } finally {
            btnConvert.disabled = false;
            spinner.classList.add('d-none');
            btnText.textContent = 'Generate N-Up PDF';
        }
    });

})();
</script>
@endpush

@endsection
