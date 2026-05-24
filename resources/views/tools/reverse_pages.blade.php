@extends('layouts.app')

@section('title', 'Reverse PDF Pages - ToolPDF')

@section('content')
<style>
    #drop-zone { cursor: pointer; transition: background 0.2s, border-color 0.2s; }
    #drop-zone:hover { background: #fff5f5 !important; }
    #drop-zone.drag-over { background: #ffe0e0 !important; border-color: #b02a37 !important; }
    .page-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2rem;
        height: 2rem;
        border-radius: 6px;
        font-size: .78rem;
        font-weight: 700;
        background: #f8d7da;
        color: #842029;
        border: 1px solid #f1aeb5;
    }
    .page-chip.after {
        background: #d1e7dd;
        color: #0a3622;
        border-color: #a3cfbb;
    }
    #order-preview { gap: .35rem; flex-wrap: wrap; align-items: center; }
</style>

<!-- Hero -->
<section style="background: linear-gradient(135deg, #dc3545 0%, #a71d2a 100%); color:#fff; padding:44px 0 28px;">
    <div class="container">
        <h1 class="fw-bold mb-2"><i class="bi bi-arrow-left-right me-2"></i>Reverse PDF Pages</h1>
        <p class="mb-3 opacity-75">Reverse the page order of any PDF instantly — all processing happens in your browser.</p>
        <div class="d-flex flex-wrap gap-1">
            <span style="background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.35);font-size:.73rem;padding:.3em .65em;border-radius:20px">🔄 Browser-side · No upload</span>
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
            <div id="drop-zone" style="border:2px dashed #dc3545;border-radius:12px;min-height:200px;background:#fff;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:40px 20px;">
                <input type="file" id="file-input" class="d-none" accept=".pdf">
                <i class="bi bi-arrow-left-right mb-3" style="font-size:3.5rem;color:#dc3545;"></i>
                <h5 class="fw-bold mb-1">Drop your PDF here</h5>
                <p class="text-muted mb-3">or click to browse</p>
                <button type="button" class="btn btn-outline-danger rounded-pill px-4" onclick="document.getElementById('file-input').click()">
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

            <!-- Order preview panel -->
            <div id="options-panel" class="d-none mt-3 p-4 bg-white border rounded-3 shadow-sm">
                <h6 class="fw-bold mb-3"><i class="bi bi-sort-numeric-down-alt me-2 text-danger"></i>Page Order Preview</h6>
                <div class="mb-2 text-muted small">Before → After reversal:</div>
                <div id="order-preview" class="d-flex">
                    <!-- chips rendered by JS -->
                </div>
                <div class="mt-3 text-muted small">
                    <i class="bi bi-info-circle me-1"></i>All pages will be included; only the order is reversed.
                </div>
            </div>

            <!-- Progress -->
            <div id="progress-wrap" class="d-none mt-3">
                <div class="progress" style="height:10px;border-radius:8px;">
                    <div id="progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" style="width:0%;background:#dc3545;"></div>
                </div>
                <p id="progress-msg" class="text-muted small mt-1 mb-0"></p>
            </div>

            <!-- Action button -->
            <div class="text-center mt-4">
                <button id="btn-action" class="btn btn-lg rounded-pill px-5 text-white fw-bold" style="background:#dc3545;border:none;box-shadow:0 4px 16px rgba(220,53,69,.4);" disabled>
                    <i class="bi bi-arrow-left-right me-2"></i>Reverse Pages
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
    const dropZone     = document.getElementById('drop-zone');
    const fileInput    = document.getElementById('file-input');
    const fileCard     = document.getElementById('file-card');
    const fileNameEl   = document.getElementById('file-name');
    const fileMetaEl   = document.getElementById('file-meta');
    const optionsPanel = document.getElementById('options-panel');
    const orderPreview = document.getElementById('order-preview');
    const progressWrap = document.getElementById('progress-wrap');
    const progressBar  = document.getElementById('progress-bar');
    const progressMsg  = document.getElementById('progress-msg');
    const btnAction    = document.getElementById('btn-action');
    const btnSpinner   = document.getElementById('btn-spinner');
    const btnRemove    = document.getElementById('btn-remove');
    const errorMsg     = document.getElementById('error-msg');

    let selectedFile = null;
    let pageCount    = 0;

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

    function buildPreview(n) {
        orderPreview.innerHTML = '';
        // Show at most 10 chips before arrow, 10 after — then ellipsis if more
        const maxShow = 10;
        const beforeNums = [];
        const afterNums  = [];
        for (let i = 1; i <= n; i++) beforeNums.push(i);
        for (let i = n; i >= 1; i--) afterNums.push(i);

        function renderChips(nums, cls) {
            const show = nums.slice(0, maxShow);
            show.forEach(num => {
                const chip = document.createElement('span');
                chip.className = 'page-chip' + (cls ? ' ' + cls : '');
                chip.textContent = num;
                orderPreview.appendChild(chip);
            });
            if (nums.length > maxShow) {
                const dots = document.createElement('span');
                dots.className = 'text-muted small fw-bold px-1';
                dots.textContent = '…';
                orderPreview.appendChild(dots);
            }
        }

        renderChips(beforeNums, '');

        const arrow = document.createElement('span');
        arrow.className = 'fw-bold text-muted mx-2 fs-5';
        arrow.textContent = '→';
        orderPreview.appendChild(arrow);

        renderChips(afterNums, 'after');
    }

    function resetUI() {
        selectedFile = null;
        pageCount    = 0;
        fileInput.value = '';
        fileCard.classList.add('d-none');
        optionsPanel.classList.add('d-none');
        progressWrap.classList.add('d-none');
        progressBar.style.width = '0%';
        dropZone.style.display = 'flex';
        btnAction.disabled = true;
        errorMsg.classList.add('d-none');
        orderPreview.innerHTML = '';
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
            pageCount = doc.getPageCount();
            fileMetaEl.textContent = `${pageCount} page${pageCount !== 1 ? 's' : ''} · ${formatBytes(file.size)}`;
        } catch (e) {
            pageCount = 0;
            fileMetaEl.textContent = formatBytes(file.size);
        }

        fileNameEl.textContent = file.name;
        dropZone.style.display = 'none';
        fileCard.classList.remove('d-none');
        optionsPanel.classList.remove('d-none');
        if (pageCount > 0) buildPreview(pageCount);
        btnAction.disabled = false;
    }

    btnAction.addEventListener('click', async () => {
        if (!selectedFile) return;
        errorMsg.classList.add('d-none');
        progressWrap.classList.remove('d-none');
        progressBar.style.width = '0%';
        progressBar.classList.add('progress-bar-animated');
        btnAction.disabled = true;
        btnSpinner.classList.remove('d-none');

        try {
            progressMsg.textContent = 'Loading PDF…';
            const ab = await selectedFile.arrayBuffer();
            const { PDFDocument } = PDFLib;

            progressBar.style.width = '20%';
            const srcDoc = await PDFDocument.load(ab);
            const total  = srcDoc.getPageCount();

            progressMsg.textContent = 'Reversing page order…';
            progressBar.style.width = '40%';

            const newDoc = await PDFDocument.create();

            // Build reversed index array
            const indices = [];
            for (let i = total - 1; i >= 0; i--) indices.push(i);

            progressBar.style.width = '60%';
            const copiedPages = await newDoc.copyPages(srcDoc, indices);
            copiedPages.forEach(page => newDoc.addPage(page));

            progressMsg.textContent = 'Generating file…';
            progressBar.style.width = '90%';

            const pdfBytes = await newDoc.save();
            const blob = new Blob([pdfBytes], { type: 'application/pdf' });
            const url  = URL.createObjectURL(blob);
            const a    = document.createElement('a');
            a.href     = url;
            a.download = selectedFile.name.replace(/\.pdf$/i, '') + '_reversed.pdf';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);

            progressBar.style.width = '100%';
            progressBar.classList.remove('progress-bar-animated');
            progressMsg.textContent = `Done! ${total} pages reversed and downloaded.`;

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
