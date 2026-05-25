@extends('layouts.app')

@section('title', 'Extract Images from PDF - Export Pages as PNG/JPEG - ToolPDF')

@section('content')

<style>
:root {
    --ei-color: #fd7e14;
    --ei-dark:  #c96209;
}
.ei-hero {
    background: linear-gradient(135deg, #fd7e14 0%, #c96209 100%);
    color: #fff;
    padding: 44px 0 28px;
}
.ei-hero h1 { font-size: 1.95rem; font-weight: 800; margin-bottom: .35rem; }
.ei-badge {
    background: rgba(255,255,255,.18);
    border: 1px solid rgba(255,255,255,.35);
    font-size: .73rem; padding: .3em .65em;
    border-radius: 20px;
}
.ei-drop-zone {
    border: 2.5px dashed #fed8a8 !important;
    border-radius: 14px;
    cursor: pointer;
    transition: background .2s, border-color .2s;
    background: #fff9f3;
    min-height: 180px;
}
.ei-drop-zone:hover, .ei-drop-zone.drag-over {
    background: #fff0e0;
    border-color: #fd7e14 !important;
}
.ei-action-btn {
    background: linear-gradient(135deg, #fd7e14 0%, #c96209 100%);
    border: none; color: #fff;
    font-size: 1.05rem; font-weight: 700;
    padding: 13px 40px;
    border-radius: 50px;
    box-shadow: 0 4px 16px rgba(253,126,20,.35);
    transition: opacity .2s, transform .1s;
}
.ei-action-btn:hover:not(:disabled) { opacity: .92; transform: translateY(-1px); }
.ei-action-btn:disabled { opacity: .4; cursor: not-allowed; }
.ei-thumb-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 12px;
}
.ei-thumb-item {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    overflow: hidden;
    background: #f8fafc;
    position: relative;
}
.ei-thumb-item img {
    width: 100%;
    height: 110px;
    object-fit: contain;
    display: block;
    background: #fff;
}
.ei-thumb-label {
    font-size: .72rem;
    text-align: center;
    padding: 4px 6px;
    color: #64748b;
    border-top: 1px solid #f1f5f9;
}
.ei-option-group label.fw-semibold { font-size: .88rem; }
</style>

<section class="ei-hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-9">
                <h1><i class="bi bi-file-earmark-image me-2"></i>Extract Images from PDF</h1>
                <p class="mb-3" style="opacity:.9;font-size:1rem">
                    Export every PDF page as a high-quality PNG or JPEG image.
                    Download a ZIP archive or a single image in one click.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <span class="ei-badge">&#128444;&#65039; Browser-side</span>
                    <span class="ei-badge">PNG/JPEG output</span>
                    <span class="ei-badge">No upload required</span>
                    <span class="ei-badge">ZIP download</span>
                </div>
            </div>
            <div class="col-lg-3 text-lg-end mt-3 mt-lg-0">
                <i class="bi bi-images" style="font-size:4.5rem;opacity:.22"></i>
            </div>
        </div>
    </div>
</section>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">

            {{-- Drop Zone --}}
            <div id="ei-drop-zone" class="ei-drop-zone d-flex flex-column align-items-center justify-content-center text-center p-5 mb-4">
                <i class="bi bi-file-earmark-pdf mb-3" style="font-size:3.5rem;color:#fd7e14"></i>
                <h5 class="fw-bold mb-1">Drop your PDF here</h5>
                <p class="text-muted mb-3">or click to browse</p>
                <button type="button" class="btn btn-sm px-4" style="background:#fd7e14;color:#fff;border-radius:20px"
                        onclick="document.getElementById('ei-file-input').click()">
                    <i class="bi bi-folder2-open me-1"></i> Choose PDF
                </button>
                <input type="file" id="ei-file-input" class="d-none" accept=".pdf,application/pdf">
            </div>

            {{-- File Info Bar --}}
            <div id="ei-file-bar" class="d-none mb-4">
                <div class="d-flex align-items-center justify-content-between p-3 bg-white border rounded-3 shadow-sm">
                    <div class="d-flex align-items-center gap-3">
                        <i class="bi bi-file-earmark-pdf fs-3 text-danger"></i>
                        <div>
                            <div class="fw-bold text-truncate" style="max-width:260px" id="ei-file-name">file.pdf</div>
                            <small class="text-muted" id="ei-page-count">—</small>
                        </div>
                    </div>
                    <button class="btn btn-outline-secondary btn-sm rounded-circle px-2" id="ei-btn-remove" title="Remove">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>

            {{-- Options Card --}}
            <div id="ei-options-card" class="card shadow-sm border-0 mb-4 d-none">
                <div class="card-body p-4">

                    <div class="row g-3 mb-4 ei-option-group">
                        <div class="col-sm-4">
                            <label class="form-label fw-semibold">Output Format</label>
                            <select id="ei-format" class="form-select">
                                <option value="png">PNG (lossless)</option>
                                <option value="jpeg">JPEG (smaller file)</option>
                            </select>
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label fw-semibold">Scale / Resolution</label>
                            <select id="ei-scale" class="form-select">
                                <option value="1">1× (72 DPI)</option>
                                <option value="1.5" selected>1.5× (108 DPI)</option>
                                <option value="2">2× (144 DPI)</option>
                                <option value="3">3× (216 DPI)</option>
                            </select>
                        </div>
                        <div class="col-sm-4" id="ei-quality-wrap">
                            <label class="form-label fw-semibold">
                                JPEG Quality: <span id="ei-quality-val">85</span>%
                            </label>
                            <input type="range" class="form-range" id="ei-quality" min="60" max="100" value="85">
                        </div>
                    </div>

                    {{-- Progress --}}
                    <div id="ei-progress" class="mt-2 mb-3 d-none">
                        <div class="d-flex justify-content-between mb-1 small">
                            <span id="ei-progress-msg" class="text-muted">Rendering pages…</span>
                            <span id="ei-progress-pages" class="text-muted"></span>
                        </div>
                        <div class="progress" style="height:10px;border-radius:6px">
                            <div id="ei-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated"
                                 style="width:0%;background:#fd7e14"></div>
                        </div>
                    </div>

                    <div id="ei-error" class="alert alert-danger mt-3 d-none"></div>

                    <div class="text-center mt-3">
                        <button type="button" id="ei-btn-convert" class="ei-action-btn" disabled>
                            <i class="bi bi-download me-2"></i>
                            <span id="ei-btn-text">Extract Images</span>
                            <span class="spinner-border spinner-border-sm d-none ms-2" id="ei-spinner"></span>
                        </button>
                    </div>

                </div>
            </div>

            {{-- Thumbnail preview grid --}}
            <div id="ei-thumb-section" class="d-none mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-1">Preview <small class="text-muted fw-normal" id="ei-thumb-note">(showing first 6 pages)</small></h6>
                        <div id="ei-thumb-grid" class="ei-thumb-grid mt-3"></div>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script>
(function () {
    'use strict';

    let pdfDoc      = null;
    let selectedFile = null;

    const dropZone   = document.getElementById('ei-drop-zone');
    const fileInput  = document.getElementById('ei-file-input');
    const fileBar    = document.getElementById('ei-file-bar');
    const fileName   = document.getElementById('ei-file-name');
    const pageCount  = document.getElementById('ei-page-count');
    const btnRemove  = document.getElementById('ei-btn-remove');
    const optCard    = document.getElementById('ei-options-card');
    const btnConvert = document.getElementById('ei-btn-convert');
    const btnText    = document.getElementById('ei-btn-text');
    const spinner    = document.getElementById('ei-spinner');
    const progress   = document.getElementById('ei-progress');
    const progressBar= document.getElementById('ei-progress-bar');
    const progressMsg= document.getElementById('ei-progress-msg');
    const progressPg = document.getElementById('ei-progress-pages');
    const errBox     = document.getElementById('ei-error');
    const thumbSection = document.getElementById('ei-thumb-section');
    const thumbGrid  = document.getElementById('ei-thumb-grid');
    const thumbNote  = document.getElementById('ei-thumb-note');
    const fmtSelect  = document.getElementById('ei-format');
    const qualityWrap= document.getElementById('ei-quality-wrap');
    const qualitySlider = document.getElementById('ei-quality');
    const qualityVal = document.getElementById('ei-quality-val');

    // Format change: show/hide quality slider
    fmtSelect.addEventListener('change', () => {
        qualityWrap.style.display = fmtSelect.value === 'jpeg' ? '' : 'none';
    });
    qualityWrap.style.display = 'none'; // PNG default — hide quality
    qualitySlider.addEventListener('input', () => { qualityVal.textContent = qualitySlider.value; });

    // Drag & drop
    dropZone.addEventListener('click', () => fileInput.click());
    dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
    dropZone.addEventListener('drop', e => {
        e.preventDefault(); dropZone.classList.remove('drag-over');
        if (e.dataTransfer.files[0]) handleFile(e.dataTransfer.files[0]);
    });
    fileInput.addEventListener('change', e => { if (e.target.files[0]) handleFile(e.target.files[0]); });
    btnRemove.addEventListener('click', resetAll);

    function showError(msg) {
        errBox.textContent = msg;
        errBox.classList.remove('d-none');
    }

    function resetAll() {
        pdfDoc = null; selectedFile = null; fileInput.value = '';
        dropZone.classList.remove('d-none');
        fileBar.classList.add('d-none');
        optCard.classList.add('d-none');
        thumbSection.classList.add('d-none');
        thumbGrid.innerHTML = '';
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
        thumbSection.classList.add('d-none');
        btnConvert.disabled = true;

        try {
            const buf = await file.arrayBuffer();
            pdfDoc = await pdfjsLib.getDocument({ data: new Uint8Array(buf) }).promise;
            const n = pdfDoc.numPages;
            pageCount.textContent = n + ' page' + (n !== 1 ? 's' : '');
            btnConvert.disabled = false;
        } catch (e) {
            showError('Could not open PDF: ' + e.message);
            resetAll();
        }
    }

    // Render one page to canvas and return dataURL
    async function renderPage(pgNum, scale, format, quality) {
        const page = await pdfDoc.getPage(pgNum);
        const vp   = page.getViewport({ scale });
        const canvas = document.createElement('canvas');
        canvas.width  = Math.round(vp.width);
        canvas.height = Math.round(vp.height);
        const ctx = canvas.getContext('2d');
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        await page.render({ canvasContext: ctx, viewport: vp }).promise;
        const mime = format === 'jpeg' ? 'image/jpeg' : 'image/png';
        return { dataURL: canvas.toDataURL(mime, quality / 100), canvas };
    }

    btnConvert.addEventListener('click', async () => {
        if (!pdfDoc) return;
        errBox.classList.add('d-none');
        btnConvert.disabled = true;
        spinner.classList.remove('d-none');
        btnText.textContent  = 'Processing…';
        progress.classList.remove('d-none');
        progressBar.style.width = '0%';
        thumbSection.classList.add('d-none');
        thumbGrid.innerHTML = '';

        const format  = fmtSelect.value;
        const scale   = parseFloat(document.getElementById('ei-scale').value);
        const quality = parseInt(qualitySlider.value, 10);
        const ext     = format === 'jpeg' ? 'jpg' : 'png';
        const mime    = format === 'jpeg' ? 'image/jpeg' : 'image/png';
        const total   = pdfDoc.numPages;

        try {
            const images = []; // { dataURL, filename }

            for (let i = 1; i <= total; i++) {
                progressMsg.textContent = `Rendering page ${i} of ${total}…`;
                progressPg.textContent  = `${i} / ${total}`;
                progressBar.style.width = Math.round((i / total) * 85) + '%';

                const { dataURL } = await renderPage(i, scale, format, quality);
                const padded = String(i).padStart(2, '0');
                images.push({ dataURL, filename: `page-${padded}.${ext}` });
            }

            progressMsg.textContent = total > 1 ? 'Creating ZIP archive…' : 'Preparing download…';
            progressBar.style.width = '92%';

            const baseName = selectedFile.name.replace(/\.pdf$/i, '');

            if (total === 1) {
                // Single page: direct download
                const a = document.createElement('a');
                a.href     = images[0].dataURL;
                a.download = baseName + '.' + ext;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            } else {
                // Multiple pages: ZIP
                const zip = new JSZip();
                const folder = zip.folder(baseName + '_images');
                for (const img of images) {
                    const b64 = img.dataURL.split(',')[1];
                    folder.file(img.filename, b64, { base64: true });
                }
                const blob = await zip.generateAsync({ type: 'blob', compression: 'DEFLATE', compressionOptions: { level: 6 } });
                const url  = URL.createObjectURL(blob);
                const a    = document.createElement('a');
                a.href     = url;
                a.download = baseName + '_images.zip';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                setTimeout(() => URL.revokeObjectURL(url), 5000);
            }

            progressBar.style.width = '100%';
            progressMsg.textContent = 'Done!';

            // Show thumbnail previews (max 6)
            const previewCount = Math.min(images.length, 6);
            thumbNote.textContent = images.length <= 6
                ? '(' + images.length + ' page' + (images.length !== 1 ? 's' : '') + ')'
                : '(showing first 6 of ' + images.length + ' pages)';
            for (let i = 0; i < previewCount; i++) {
                const wrap = document.createElement('div');
                wrap.className = 'ei-thumb-item';
                const img = document.createElement('img');
                img.src = images[i].dataURL;
                img.alt = images[i].filename;
                const lbl = document.createElement('div');
                lbl.className = 'ei-thumb-label';
                lbl.textContent = images[i].filename;
                wrap.appendChild(img);
                wrap.appendChild(lbl);
                thumbGrid.appendChild(wrap);
            }
            thumbSection.classList.remove('d-none');

            setTimeout(() => { progress.classList.add('d-none'); }, 1800);

        } catch (e) {
            console.error(e);
            showError('Error extracting images: ' + e.message);
        } finally {
            btnConvert.disabled = false;
            spinner.classList.add('d-none');
            btnText.textContent  = 'Extract Images';
        }
    });

})();
</script>
@endpush

@endsection
