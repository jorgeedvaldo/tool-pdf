@extends('layouts.app')

@section('title', 'Add Header & Footer to PDF - ToolPDF')

@section('content')
<style>
    #drop-zone { cursor: pointer; transition: background 0.2s, border-color 0.2s; }
    #drop-zone:hover { background: #f0f4ff !important; }
    #drop-zone.drag-over { background: #e0e9ff !important; border-color: #0a58ca !important; }
    #font-size-val { min-width: 2rem; display: inline-block; text-align: center; }
</style>

<!-- Hero -->
<section style="background: linear-gradient(135deg, #0d6efd 0%, #0a4bbd 100%); color:#fff; padding:44px 0 28px;">
    <div class="container">
        <h1 class="fw-bold mb-2"><i class="bi bi-body-text me-2"></i>Add Header &amp; Footer</h1>
        <p class="mb-3 opacity-75">Add custom header and footer text to every page of your PDF — entirely in the browser.</p>
        <div class="d-flex flex-wrap gap-1">
            <span style="background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.35);font-size:.73rem;padding:.3em .65em;border-radius:20px">📝 Browser-side · No upload</span>
            <span style="background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.35);font-size:.73rem;padding:.3em .65em;border-radius:20px">🔒 100% Private</span>
            <span style="background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.35);font-size:.73rem;padding:.3em .65em;border-radius:20px">📄 PDF Input</span>
        </div>
    </div>
</section>

<!-- Main content -->
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">

            <!-- Drop zone -->
            <div id="drop-zone" style="border:2px dashed #0d6efd;border-radius:12px;min-height:200px;background:#fff;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:40px 20px;">
                <input type="file" id="file-input" class="d-none" accept=".pdf">
                <i class="bi bi-body-text mb-3" style="font-size:3.5rem;color:#0d6efd;"></i>
                <h5 class="fw-bold mb-1">Drop your PDF here</h5>
                <p class="text-muted mb-3">or click to browse</p>
                <button type="button" class="btn btn-outline-primary rounded-pill px-4" onclick="document.getElementById('file-input').click()">
                    <i class="bi bi-folder2-open me-2"></i>Choose PDF
                </button>
            </div>

            <!-- File card -->
            <div id="file-card" class="d-none mt-3 p-3 bg-white border rounded-3 shadow-sm d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-file-earmark-pdf fs-2 text-danger"></i>
                    <div>
                        <div class="fw-bold text-truncate" style="max-width:280px;" id="file-name">file.pdf</div>
                        <small class="text-muted" id="file-meta"></small>
                    </div>
                </div>
                <button type="button" class="btn btn-outline-danger btn-sm rounded-circle px-2" id="btn-remove" title="Remove">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <!-- Options panel -->
            <div id="options-panel" class="d-none mt-3 p-4 bg-white border rounded-3 shadow-sm">
                <h6 class="fw-bold mb-3"><i class="bi bi-sliders me-2 text-primary"></i>Header &amp; Footer Settings</h6>

                <!-- Header -->
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Header Text</label>
                    <input type="text" id="header-text" class="form-control" placeholder="Leave empty to skip header">
                </div>
                <div class="mb-4">
                    <label class="form-label text-muted small fw-bold">Header Position</label>
                    <select id="header-position" class="form-select">
                        <option value="left">Left</option>
                        <option value="center" selected>Center</option>
                        <option value="right">Right</option>
                    </select>
                </div>

                <!-- Footer -->
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Footer Text</label>
                    <input type="text" id="footer-text" class="form-control" placeholder="Leave empty to skip footer">
                </div>
                <div class="mb-4">
                    <label class="form-label text-muted small fw-bold">Footer Position</label>
                    <select id="footer-position" class="form-select">
                        <option value="left">Left</option>
                        <option value="center" selected>Center</option>
                        <option value="right">Right</option>
                    </select>
                </div>

                <div class="row g-3">
                    <!-- Font size -->
                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-bold">Font Size: <span id="font-size-val">10</span>px</label>
                        <input type="range" id="font-size" class="form-range" min="8" max="16" step="1" value="10">
                    </div>
                    <!-- Color -->
                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-bold">Text Color</label>
                        <input type="color" id="text-color" class="form-control form-control-color w-100" value="#333333">
                    </div>
                </div>
            </div>

            <!-- Progress -->
            <div id="progress-wrap" class="d-none mt-3">
                <div class="progress" style="height:10px;border-radius:8px;">
                    <div id="progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" style="width:0%;background:#0d6efd;"></div>
                </div>
                <p id="progress-msg" class="text-muted small mt-1 mb-0"></p>
            </div>

            <!-- Action button -->
            <div class="text-center mt-4">
                <button id="btn-action" class="btn btn-lg rounded-pill px-5 text-white fw-bold" style="background:#0d6efd;border:none;box-shadow:0 4px 16px rgba(13,110,253,.4);" disabled>
                    <i class="bi bi-body-text me-2"></i>Add Header &amp; Footer
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

<script src="https://cdn.jsdelivr.net/npm/pdf-lib@1.17.1/dist/pdf-lib.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const dropZone       = document.getElementById('drop-zone');
    const fileInput      = document.getElementById('file-input');
    const fileCard       = document.getElementById('file-card');
    const fileNameEl     = document.getElementById('file-name');
    const fileMetaEl     = document.getElementById('file-meta');
    const optionsPanel   = document.getElementById('options-panel');
    const progressWrap   = document.getElementById('progress-wrap');
    const progressBar    = document.getElementById('progress-bar');
    const progressMsg    = document.getElementById('progress-msg');
    const btnAction      = document.getElementById('btn-action');
    const btnSpinner     = document.getElementById('btn-spinner');
    const btnRemove      = document.getElementById('btn-remove');
    const errorMsg       = document.getElementById('error-msg');
    const fontSizeRange  = document.getElementById('font-size');
    const fontSizeVal    = document.getElementById('font-size-val');

    let selectedFile = null;

    fontSizeRange.addEventListener('input', () => { fontSizeVal.textContent = fontSizeRange.value; });

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
        fileInput.value = '';
        fileCard.classList.add('d-none');
        optionsPanel.classList.add('d-none');
        progressWrap.classList.add('d-none');
        progressBar.style.width = '0%';
        dropZone.style.display = 'flex';
        btnAction.disabled = true;
        errorMsg.classList.add('d-none');
    }

    function hexToRgb01(hex) {
        const r = parseInt(hex.slice(1,3),16)/255;
        const g = parseInt(hex.slice(3,5),16)/255;
        const b = parseInt(hex.slice(5,7),16)/255;
        return { r, g, b };
    }

    async function handleFile(file) {
        errorMsg.classList.add('d-none');
        if (!file.name.toLowerCase().endsWith('.pdf') && file.type !== 'application/pdf') {
            showError('Please select a valid PDF file.');
            return;
        }
        selectedFile = file;

        try {
            const { PDFDocument } = PDFLib;
            const ab = await file.arrayBuffer();
            const doc = await PDFDocument.load(ab);
            const pages = doc.getPageCount();
            fileMetaEl.textContent = `${pages} page${pages !== 1 ? 's' : ''} · ${formatBytes(file.size)}`;
        } catch (e) {
            fileMetaEl.textContent = formatBytes(file.size);
        }

        fileNameEl.textContent = file.name;
        dropZone.style.display = 'none';
        fileCard.classList.remove('d-none');
        optionsPanel.classList.remove('d-none');
        btnAction.disabled = false;
    }

    btnAction.addEventListener('click', async () => {
        if (!selectedFile) return;

        const headerText   = document.getElementById('header-text').value.trim();
        const footerText   = document.getElementById('footer-text').value.trim();
        const headerPos    = document.getElementById('header-position').value;
        const footerPos    = document.getElementById('footer-position').value;
        const fontSize     = parseInt(fontSizeRange.value, 10);
        const colorHex     = document.getElementById('text-color').value;

        if (!headerText && !footerText) {
            showError('Please enter at least a header or footer text.');
            return;
        }

        errorMsg.classList.add('d-none');
        progressWrap.classList.remove('d-none');
        progressBar.style.width = '0%';
        progressBar.classList.add('progress-bar-animated');
        btnAction.disabled = true;
        btnSpinner.classList.remove('d-none');

        try {
            progressMsg.textContent = 'Loading PDF…';
            const ab = await selectedFile.arrayBuffer();
            const { PDFDocument, rgb, StandardFonts } = PDFLib;
            const pdfDoc = await PDFDocument.load(ab);
            const font = await pdfDoc.embedFont(StandardFonts.Helvetica);
            const { r, g, b } = hexToRgb01(colorHex);
            const color = rgb(r, g, b);

            const pages = pdfDoc.getPages();
            const total = pages.length;
            const margin = 10;

            for (let i = 0; i < total; i++) {
                progressBar.style.width = `${Math.round(((i) / total) * 90)}%`;
                progressMsg.textContent = `Processing page ${i + 1} of ${total}…`;

                const page = pages[i];
                const { width, height } = page.getSize();

                function calcX(text, pos) {
                    const tw = font.widthOfTextAtSize(text, fontSize);
                    if (pos === 'left') return margin;
                    if (pos === 'right') return width - tw - margin;
                    return (width - tw) / 2; // center
                }

                if (headerText) {
                    page.drawText(headerText, {
                        x: calcX(headerText, headerPos),
                        y: height - fontSize - margin,
                        size: fontSize,
                        font,
                        color,
                    });
                }

                if (footerText) {
                    page.drawText(footerText, {
                        x: calcX(footerText, footerPos),
                        y: margin,
                        size: fontSize,
                        font,
                        color,
                    });
                }
            }

            progressMsg.textContent = 'Generating file…';
            progressBar.style.width = '95%';

            const pdfBytes = await pdfDoc.save();
            const blob = new Blob([pdfBytes], { type: 'application/pdf' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = selectedFile.name.replace(/\.pdf$/i, '') + '_header_footer.pdf';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);

            progressBar.style.width = '100%';
            progressBar.classList.remove('progress-bar-animated');
            progressMsg.textContent = 'Done! Your PDF has been downloaded.';

        } catch (err) {
            console.error(err);
            showError('Error processing PDF: ' + (err.message || 'Unknown error.'));
            progressWrap.classList.add('d-none');
        } finally {
            btnAction.disabled = false;
            btnSpinner.classList.add('d-none');
        }
    });
});
</script>
@endsection
