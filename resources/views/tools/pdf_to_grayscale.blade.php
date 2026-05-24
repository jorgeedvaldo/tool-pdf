@extends('layouts.app')

@section('title', 'PDF to Grayscale - ToolPDF')

@section('content')
<style>
    #drop-zone { cursor: pointer; transition: background 0.2s, border-color 0.2s; }
    #drop-zone:hover { background: #f8f9fa !important; }
    #drop-zone.drag-over { background: #e9ecef !important; border-color: #495057 !important; }
    .scale-btn.active { background: #6c757d !important; color: #fff !important; border-color: #6c757d !important; }
</style>

<!-- Hero -->
<section style="background: linear-gradient(135deg, #6c757d 0%, #495057 100%); color:#fff; padding:44px 0 28px;">
    <div class="container">
        <h1 class="fw-bold mb-2"><i class="bi bi-circle-half me-2"></i>PDF to Grayscale</h1>
        <p class="mb-3 opacity-75">Convert your PDF to grayscale instantly — all processing happens in your browser.</p>
        <div class="d-flex flex-wrap gap-1">
            <span style="background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.35);font-size:.73rem;padding:.3em .65em;border-radius:20px">🖤 Browser-side · No upload</span>
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
            <div id="drop-zone" style="border:2px dashed #6c757d;border-radius:12px;min-height:200px;background:#fff;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:40px 20px;">
                <input type="file" id="file-input" class="d-none" accept=".pdf">
                <i class="bi bi-circle-half mb-3" style="font-size:3.5rem;color:#6c757d;"></i>
                <h5 class="fw-bold mb-1">Drop your PDF here</h5>
                <p class="text-muted mb-3">or click to browse</p>
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" onclick="document.getElementById('file-input').click()">
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
                <h6 class="fw-bold mb-3"><i class="bi bi-sliders me-2 text-secondary"></i>Output Quality (Scale)</h6>
                <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-outline-secondary scale-btn active" data-scale="1">1x — Standard</button>
                    <button type="button" class="btn btn-outline-secondary scale-btn" data-scale="1.5">1.5x — High</button>
                    <button type="button" class="btn btn-outline-secondary scale-btn" data-scale="2">2x — Best</button>
                </div>
                <div class="mt-2 text-muted small"><i class="bi bi-info-circle me-1"></i>Higher scale = better quality, larger file size.</div>
            </div>

            <!-- Progress -->
            <div id="progress-wrap" class="d-none mt-3">
                <div class="progress" style="height:10px;border-radius:8px;">
                    <div id="progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" style="width:0%;background:#6c757d;"></div>
                </div>
                <p id="progress-msg" class="text-muted small mt-1 mb-0"></p>
            </div>

            <!-- Action button -->
            <div class="text-center mt-4">
                <button id="btn-action" class="btn btn-lg rounded-pill px-5 text-white fw-bold" style="background:#6c757d;border:none;box-shadow:0 4px 16px rgba(108,117,125,.4);" disabled>
                    <i class="bi bi-circle-half me-2"></i>Convert to Grayscale
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

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
<script>pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const dropZone      = document.getElementById('drop-zone');
    const fileInput     = document.getElementById('file-input');
    const fileCard      = document.getElementById('file-card');
    const fileNameEl    = document.getElementById('file-name');
    const fileMetaEl    = document.getElementById('file-meta');
    const optionsPanel  = document.getElementById('options-panel');
    const progressWrap  = document.getElementById('progress-wrap');
    const progressBar   = document.getElementById('progress-bar');
    const progressMsg   = document.getElementById('progress-msg');
    const btnAction     = document.getElementById('btn-action');
    const btnSpinner    = document.getElementById('btn-spinner');
    const btnRemove     = document.getElementById('btn-remove');
    const errorMsg      = document.getElementById('error-msg');
    const scaleBtns     = document.querySelectorAll('.scale-btn');

    let selectedFile = null;
    let selectedScale = 1;

    // Scale buttons
    scaleBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            scaleBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            selectedScale = parseFloat(btn.dataset.scale);
        });
    });

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
        btnAction.innerHTML = '<i class="bi bi-circle-half me-2"></i>Convert to Grayscale <span class="spinner-border spinner-border-sm d-none ms-2" id="btn-spinner" role="status"></span>';
    }

    async function handleFile(file) {
        errorMsg.classList.add('d-none');
        if (!file.name.toLowerCase().endsWith('.pdf') && file.type !== 'application/pdf') {
            showError('Please select a valid PDF file.');
            return;
        }
        selectedFile = file;

        // Read page count
        try {
            const ab = await file.arrayBuffer();
            const doc = await pdfjsLib.getDocument({ data: new Uint8Array(ab) }).promise;
            const pages = doc.numPages;
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
        errorMsg.classList.add('d-none');
        progressWrap.classList.remove('d-none');
        progressBar.style.width = '0%';
        progressBar.classList.add('progress-bar-animated');
        btnAction.disabled = true;
        btnSpinner.classList.remove('d-none');

        try {
            const ab = await selectedFile.arrayBuffer();
            progressMsg.textContent = 'Loading PDF…';

            const pdfDoc = await pdfjsLib.getDocument({ data: new Uint8Array(ab) }).promise;
            const totalPages = pdfDoc.numPages;
            const { jsPDF } = window.jspdf;

            const firstPage = await pdfDoc.getPage(1);
            const fvp = firstPage.getViewport({ scale: 1.0 });
            const outDoc = new jsPDF({
                orientation: fvp.width > fvp.height ? 'landscape' : 'portrait',
                unit: 'px',
                format: [fvp.width, fvp.height]
            });

            for (let i = 1; i <= totalPages; i++) {
                progressBar.style.width = `${Math.round(((i - 1) / totalPages) * 90)}%`;
                progressMsg.textContent = `Converting page ${i} of ${totalPages}…`;

                const page = await pdfDoc.getPage(i);
                const vpBase = page.getViewport({ scale: 1.0 });
                const vpScaled = page.getViewport({ scale: selectedScale });

                const canvas = document.createElement('canvas');
                canvas.width = vpScaled.width;
                canvas.height = vpScaled.height;
                const ctx = canvas.getContext('2d');
                ctx.filter = 'grayscale(100%)';

                await page.render({ canvasContext: ctx, viewport: vpScaled }).promise;

                const imgData = canvas.toDataURL('image/jpeg', 0.92);

                if (i > 1) {
                    outDoc.addPage(
                        [vpBase.width, vpBase.height],
                        vpBase.width > vpBase.height ? 'landscape' : 'portrait'
                    );
                }
                outDoc.addImage(imgData, 'JPEG', 0, 0, vpBase.width, vpBase.height, '', 'FAST');
            }

            progressMsg.textContent = 'Generating file…';
            progressBar.style.width = '95%';

            const blob = outDoc.output('blob');
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = selectedFile.name.replace(/\.pdf$/i, '') + '_grayscale.pdf';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);

            progressBar.style.width = '100%';
            progressBar.classList.remove('progress-bar-animated');
            progressMsg.textContent = 'Done! Your grayscale PDF has been downloaded.';

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
