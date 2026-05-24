@extends('layouts.app')

@section('title', 'TXT to PDF - ToolPDF')

@section('content')
<style>
    #drop-zone { cursor: pointer; transition: background 0.2s, border-color 0.2s; }
    #drop-zone:hover { background: #f0fdfa !important; }
    #drop-zone.drag-over { background: #ccfbf1 !important; border-color: #0f766e !important; }
    #font-size-val, #margin-val, #line-height-val { min-width: 2.5rem; display: inline-block; text-align: center; font-weight: 700; }
    .stat-box {
        background: #f0fdfa;
        border: 1px solid #99f6e4;
        border-radius: 8px;
        padding: .6rem 1rem;
        text-align: center;
    }
    .stat-box .stat-num { font-size: 1.4rem; font-weight: 700; color: #0d9488; }
    .stat-box .stat-lbl { font-size: .75rem; color: #6b7280; }
</style>

<!-- Hero -->
<section style="background: linear-gradient(135deg, #0d9488 0%, #0a7a70 100%); color:#fff; padding:44px 0 28px;">
    <div class="container">
        <h1 class="fw-bold mb-2"><i class="bi bi-file-text me-2"></i>TXT to PDF</h1>
        <p class="mb-3 opacity-75">Convert any plain-text file to a clean, well-formatted PDF — entirely in your browser.</p>
        <div class="d-flex flex-wrap gap-1">
            <span style="background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.35);font-size:.73rem;padding:.3em .65em;border-radius:20px">📄 Browser-side · No upload</span>
            <span style="background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.35);font-size:.73rem;padding:.3em .65em;border-radius:20px">🔒 100% Private</span>
            <span style="background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.35);font-size:.73rem;padding:.3em .65em;border-radius:20px">📝 TXT Input</span>
        </div>
    </div>
</section>

<!-- Main content -->
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">

            <!-- Drop zone -->
            <div id="drop-zone" style="border:2px dashed #0d9488;border-radius:12px;min-height:200px;background:#fff;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:40px 20px;">
                <input type="file" id="file-input" class="d-none" accept=".txt,text/plain">
                <i class="bi bi-file-text mb-3" style="font-size:3.5rem;color:#0d9488;"></i>
                <h5 class="fw-bold mb-1">Drop your TXT file here</h5>
                <p class="text-muted mb-3">or click to browse</p>
                <button type="button" class="btn rounded-pill px-4 text-white fw-semibold" style="background:#0d9488;border:none;" onclick="document.getElementById('file-input').click()">
                    <i class="bi bi-folder2-open me-2"></i>Choose TXT File
                </button>
            </div>

            <!-- File card -->
            <div id="file-card" class="d-none mt-3 p-3 bg-white border rounded-3 shadow-sm d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-file-earmark-text fs-2" style="color:#0d9488;"></i>
                    <div>
                        <div class="fw-bold text-truncate" style="max-width:280px;" id="file-name">file.txt</div>
                        <small class="text-muted" id="file-meta"></small>
                    </div>
                </div>
                <button type="button" class="btn btn-outline-danger btn-sm rounded-circle px-2" id="btn-remove" title="Remove">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <!-- Stats row -->
            <div id="stats-row" class="d-none mt-3">
                <div class="row g-2">
                    <div class="col-6 col-md-3">
                        <div class="stat-box">
                            <div class="stat-num" id="stat-chars">0</div>
                            <div class="stat-lbl">Characters</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-box">
                            <div class="stat-num" id="stat-words">0</div>
                            <div class="stat-lbl">Words</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-box">
                            <div class="stat-num" id="stat-lines">0</div>
                            <div class="stat-lbl">Lines</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-box">
                            <div class="stat-num" id="stat-pages">~1</div>
                            <div class="stat-lbl">Est. Pages</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Options panel -->
            <div id="options-panel" class="d-none mt-3 p-4 bg-white border rounded-3 shadow-sm">
                <h6 class="fw-bold mb-3"><i class="bi bi-sliders me-2" style="color:#0d9488;"></i>PDF Settings</h6>

                <div class="row g-3">
                    <!-- Font size -->
                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-bold">
                            Font Size: <span id="font-size-val">11</span>pt
                        </label>
                        <input type="range" id="font-size" class="form-range" min="8" max="18" step="1" value="11">
                    </div>

                    <!-- Page margin -->
                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-bold">
                            Page Margin: <span id="margin-val">20</span>mm
                        </label>
                        <input type="range" id="page-margin" class="form-range" min="10" max="40" step="1" value="20">
                    </div>

                    <!-- Line height -->
                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-bold">
                            Line Height: <span id="line-height-val">1.5</span>×
                        </label>
                        <input type="range" id="line-height" class="form-range" min="12" max="20" step="1" value="15">
                        <!-- stored as *10 to avoid float steps: 12→1.2, 15→1.5, 20→2.0 -->
                    </div>

                    <!-- Font family -->
                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-bold">Font Style</label>
                        <select id="font-family" class="form-select">
                            <option value="courier" selected>Monospace (Courier)</option>
                            <option value="helvetica">Sans-serif (Helvetica)</option>
                        </select>
                    </div>

                    <!-- Page format -->
                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-bold">Page Format</label>
                        <select id="page-format" class="form-select">
                            <option value="a4" selected>A4 (210 × 297mm)</option>
                            <option value="letter">Letter (216 × 279mm)</option>
                        </select>
                    </div>
                </div>

                <div class="mt-3 text-muted small">
                    <i class="bi bi-info-circle me-1"></i>Estimated page count updates automatically as you change settings.
                </div>
            </div>

            <!-- Progress -->
            <div id="progress-wrap" class="d-none mt-3">
                <div class="progress" style="height:10px;border-radius:8px;">
                    <div id="progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" style="width:0%;background:#0d9488;"></div>
                </div>
                <p id="progress-msg" class="text-muted small mt-1 mb-0"></p>
            </div>

            <!-- Action button -->
            <div class="text-center mt-4">
                <button id="btn-action" class="btn btn-lg rounded-pill px-5 text-white fw-bold" style="background:#0d9488;border:none;box-shadow:0 4px 16px rgba(13,148,136,.4);" disabled>
                    <i class="bi bi-file-earmark-arrow-down me-2"></i>Convert to PDF
                    <span class="spinner-border spinner-border-sm d-none ms-2" id="btn-spinner" role="status"></span>
                </button>
            </div>

            <div id="error-msg" class="alert alert-danger mt-3 d-none"></div>
            <p class="text-center text-muted small mt-3">
                <i class="bi bi-shield-lock-fill text-success me-1"></i>All processing happens in your browser — no file is uploaded.
            </p>

        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const dropZone      = document.getElementById('drop-zone');
    const fileInput     = document.getElementById('file-input');
    const fileCard      = document.getElementById('file-card');
    const fileNameEl    = document.getElementById('file-name');
    const fileMetaEl    = document.getElementById('file-meta');
    const statsRow      = document.getElementById('stats-row');
    const optionsPanel  = document.getElementById('options-panel');
    const progressWrap  = document.getElementById('progress-wrap');
    const progressBar   = document.getElementById('progress-bar');
    const progressMsg   = document.getElementById('progress-msg');
    const btnAction     = document.getElementById('btn-action');
    const btnSpinner    = document.getElementById('btn-spinner');
    const btnRemove     = document.getElementById('btn-remove');
    const errorMsg      = document.getElementById('error-msg');

    const fontSizeRange  = document.getElementById('font-size');
    const fontSizeVal    = document.getElementById('font-size-val');
    const marginRange    = document.getElementById('page-margin');
    const marginVal      = document.getElementById('margin-val');
    const lineHRange     = document.getElementById('line-height');
    const lineHVal       = document.getElementById('line-height-val');
    const fontFamilySel  = document.getElementById('font-family');
    const pageFormatSel  = document.getElementById('page-format');

    const statChars  = document.getElementById('stat-chars');
    const statWords  = document.getElementById('stat-words');
    const statLines  = document.getElementById('stat-lines');
    const statPages  = document.getElementById('stat-pages');

    let selectedFile = null;
    let fileText     = '';

    // Slider live updates
    fontSizeRange.addEventListener('input', () => { fontSizeVal.textContent = fontSizeRange.value; updateEstimate(); });
    marginRange.addEventListener('input', () => { marginVal.textContent = marginRange.value; updateEstimate(); });
    lineHRange.addEventListener('input', () => {
        lineHVal.textContent = (parseInt(lineHRange.value, 10) / 10).toFixed(1);
        updateEstimate();
    });
    pageFormatSel.addEventListener('change', updateEstimate);
    fontFamilySel.addEventListener('change', updateEstimate);

    function updateEstimate() {
        if (!fileText) return;
        const est = estimatePages();
        statPages.textContent = '~' + est;
    }

    function estimatePages() {
        const fontSize   = parseInt(fontSizeRange.value, 10);
        const marginMm   = parseInt(marginRange.value, 10);
        const lineH      = parseInt(lineHRange.value, 10) / 10;
        const format     = pageFormatSel.value;

        // Page dimensions in mm
        const pageH = format === 'a4' ? 297 : 279;
        const pageW = format === 'a4' ? 210 : 216;

        // jsPDF uses pt internally; 1mm ≈ 2.8346pt
        const mmToPt  = 2.8346;
        const fontPt  = fontSize;
        const lineHPt = fontPt * lineH;
        const usableH = (pageH - 2 * marginMm) * mmToPt;
        const usableW = (pageW - 2 * marginMm) * mmToPt;

        // Rough char width (courier ~0.6 × fontSize pt; helvetica ~0.55)
        const charW   = fontFamilySel.value === 'courier' ? fontPt * 0.6 : fontPt * 0.55;
        const charsPerLine = Math.max(1, Math.floor(usableW / charW));
        const linesPerPage = Math.max(1, Math.floor(usableH / lineHPt));

        // Split text into lines then wrap
        const rawLines = fileText.split('\n');
        let totalLines = 0;
        rawLines.forEach(line => {
            if (line.length === 0) {
                totalLines += 1;
            } else {
                totalLines += Math.ceil(line.length / charsPerLine);
            }
        });

        return Math.max(1, Math.ceil(totalLines / linesPerPage));
    }

    // Drop zone
    dropZone.addEventListener('click', () => fileInput.click());
    dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
    dropZone.addEventListener('drop', e => {
        e.preventDefault();
        dropZone.classList.remove('drag-over');
        if (e.dataTransfer.files.length) handleFile(e.dataTransfer.files[0]);
    });
    fileInput.addEventListener('change', e => { if (e.target.files.length) handleFile(e.target.files[0]); });
    btnRemove.addEventListener('click', resetUI);

    function formatBytes(b) {
        if (!+b) return '0 B';
        const k = 1024, s = ['B','KB','MB','GB'], i = Math.floor(Math.log(b) / Math.log(k));
        return parseFloat((b / Math.pow(k, i)).toFixed(1)) + ' ' + s[i];
    }

    function showError(msg) {
        errorMsg.textContent = msg;
        errorMsg.classList.remove('d-none');
    }

    function resetUI() {
        selectedFile = null;
        fileText     = '';
        fileInput.value = '';
        fileCard.classList.add('d-none');
        statsRow.classList.add('d-none');
        optionsPanel.classList.add('d-none');
        progressWrap.classList.add('d-none');
        progressBar.style.width = '0%';
        dropZone.style.display = 'flex';
        btnAction.disabled = true;
        errorMsg.classList.add('d-none');
    }

    async function handleFile(file) {
        errorMsg.classList.add('d-none');
        const name = file.name.toLowerCase();
        if (!name.endsWith('.txt') && file.type !== 'text/plain') {
            showError('Please select a valid .txt file.');
            return;
        }
        selectedFile = file;

        try {
            fileText = await file.text();
        } catch (e) {
            showError('Could not read file: ' + (e.message || 'Unknown error.'));
            return;
        }

        const chars = fileText.length;
        const words = fileText.trim() ? fileText.trim().split(/\s+/).length : 0;
        const lines = fileText.split('\n').length;

        statChars.textContent = chars.toLocaleString();
        statWords.textContent = words.toLocaleString();
        statLines.textContent = lines.toLocaleString();
        statPages.textContent = '~' + estimatePages();

        fileNameEl.textContent = file.name;
        fileMetaEl.textContent = `${chars.toLocaleString()} chars · ${formatBytes(file.size)}`;

        dropZone.style.display = 'none';
        fileCard.classList.remove('d-none');
        statsRow.classList.remove('d-none');
        optionsPanel.classList.remove('d-none');
        btnAction.disabled = false;
    }

    btnAction.addEventListener('click', async () => {
        if (!selectedFile || !fileText) return;
        errorMsg.classList.add('d-none');
        progressWrap.classList.remove('d-none');
        progressBar.style.width = '0%';
        progressBar.classList.add('progress-bar-animated');
        btnAction.disabled = true;
        btnSpinner.classList.remove('d-none');

        try {
            progressMsg.textContent = 'Preparing document…';
            progressBar.style.width = '10%';

            const fontSize   = parseInt(fontSizeRange.value, 10);
            const marginMm   = parseInt(marginRange.value, 10);
            const lineH      = parseInt(lineHRange.value, 10) / 10;
            const fontFamily = fontFamilySel.value;   // 'courier' | 'helvetica'
            const format     = pageFormatSel.value;   // 'a4' | 'letter'

            const { jsPDF } = window.jspdf;
            const doc = new jsPDF({
                orientation: 'portrait',
                unit: 'mm',
                format: format
            });

            // Font
            doc.setFont(fontFamily === 'courier' ? 'courier' : 'helvetica', 'normal');
            doc.setFontSize(fontSize);

            // Page dimensions
            const pageW = doc.internal.pageSize.getWidth();
            const pageH = doc.internal.pageSize.getHeight();
            const usableW = pageW - 2 * marginMm;
            const lineHMm  = (fontSize / 2.8346) * lineH;  // pt → mm then × lineH

            // Split all text with word wrap
            progressMsg.textContent = 'Laying out text…';
            progressBar.style.width = '30%';

            const rawLines  = fileText.split('\n');
            let allLines = [];
            rawLines.forEach(line => {
                if (line.trim() === '') {
                    allLines.push('');
                } else {
                    const wrapped = doc.splitTextToSize(line, usableW);
                    wrapped.forEach(l => allLines.push(l));
                }
            });

            const totalLines    = allLines.length;
            const linesPerPage  = Math.max(1, Math.floor((pageH - 2 * marginMm) / lineHMm));
            const totalPages    = Math.ceil(totalLines / linesPerPage);

            progressMsg.textContent = `Generating ${totalPages} page${totalPages !== 1 ? 's' : ''}…`;

            let lineIndex = 0;
            for (let p = 0; p < totalPages; p++) {
                if (p > 0) doc.addPage();
                const pct = 30 + Math.round((p / totalPages) * 60);
                progressBar.style.width = pct + '%';
                progressMsg.textContent = `Writing page ${p + 1} of ${totalPages}…`;

                const chunk = allLines.slice(lineIndex, lineIndex + linesPerPage);
                lineIndex += linesPerPage;

                chunk.forEach((line, idx) => {
                    const y = marginMm + idx * lineHMm + (fontSize / 2.8346);
                    doc.text(line, marginMm, y);
                });
            }

            progressMsg.textContent = 'Generating file…';
            progressBar.style.width = '95%';

            const blob = doc.output('blob');
            const url  = URL.createObjectURL(blob);
            const a    = document.createElement('a');
            a.href     = url;
            a.download = selectedFile.name.replace(/\.txt$/i, '') + '.pdf';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);

            progressBar.style.width = '100%';
            progressBar.classList.remove('progress-bar-animated');
            progressMsg.textContent = `Done! ${totalPages} page${totalPages !== 1 ? 's' : ''} converted and downloaded.`;

        } catch (err) {
            console.error(err);
            showError('Error converting file: ' + (err.message || 'Unknown error.'));
            progressWrap.classList.add('d-none');
        } finally {
            btnAction.disabled = false;
            btnSpinner.classList.add('d-none');
        }
    });
});
</script>
@endsection
