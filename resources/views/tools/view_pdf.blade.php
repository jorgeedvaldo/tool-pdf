@extends('layouts.app')

@section('title', __('messages.view_pdf') . ' - ToolPDF')

@section('content')
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "SoftwareApplication",
  "name": "{{ __('messages.view_pdf') }} - ToolPDF",
  "applicationCategory": "BrowserApplication",
  "operatingSystem": "Any",
  "url": "{{ url()->current() }}",
  "description": "{{ __('messages.view_pdf_desc') }}",
  "offers": { "@type": "Offer", "price": "0", "priceCurrency": "USD" },
  "aggregateRating": { "@type": "AggregateRating", "ratingValue": "4.9", "ratingCount": "1280" }
}
</script>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "HowTo",
  "name": "{{ __('messages.view_pdf') }}",
  "description": "{{ __('messages.view_pdf_desc') }}",
  "step": [
    { "@type": "HowToStep", "position": 1, "name": "{{ __('messages.view_pdf_choose') }}" },
    { "@type": "HowToStep", "position": 2, "name": "{{ __('messages.view_pdf') }}" }
  ]
}
</script>
<style>
    .drop-zone { border: 2px dashed #0d6efd !important; cursor: pointer; transition: all 0.3s; }
    .drop-zone:hover, .drop-zone.dragover { background-color: #f0f5ff; border-color: #0a58ca !important; }

    #viewer-area { background: #525659; min-height: 500px; }

    #pdf-canvas-wrapper {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 24px 16px;
        gap: 16px;
        overflow-y: auto;
        max-height: 80vh;
    }

    .pdf-page-canvas {
        display: block;
        box-shadow: 0 4px 16px rgba(0,0,0,0.5);
        max-width: 100%;
    }

    #toolbar {
        background: #3d4043;
        border-bottom: 1px solid #222;
        padding: 8px 16px;
        gap: 8px;
        flex-wrap: wrap;
    }

    #toolbar .btn { font-size: 0.85rem; }
    #zoom-level { width: 80px; text-align: center; }
    #page-input { width: 60px; text-align: center; }

    .page-label { color: #ccc; font-size: 0.85rem; white-space: nowrap; }

    #thumbnail-panel {
        width: 140px;
        min-width: 140px;
        background: #3d4043;
        overflow-y: auto;
        max-height: 80vh;
        border-right: 1px solid #222;
        padding: 8px 0;
    }

    .thumb-item {
        cursor: pointer;
        padding: 6px;
        text-align: center;
        border-bottom: 1px solid #555;
        opacity: 0.7;
        transition: opacity 0.2s;
    }
    .thumb-item:hover { opacity: 1; background: rgba(255,255,255,0.05); }
    .thumb-item.active { opacity: 1; background: rgba(255,255,255,0.12); }
    .thumb-item canvas { max-width: 110px; display: inline-block; box-shadow: 0 1px 4px rgba(0,0,0,0.5); }
    .thumb-label { color: #aaa; font-size: 0.7rem; margin-top: 4px; }
</style>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-10">
            <div class="card shadow border-0">
                <div class="card-header bg-primary text-white py-3">
                    <h4 class="mb-0 fw-bold"><i class="bi bi-eye me-2"></i>{{ __('messages.view_pdf') }}</h4>
                </div>

                <div class="card-body p-4 bg-light">

                    <!-- Drop Zone -->
                    <div id="drop-zone" class="drop-zone border rounded p-5 text-center bg-white shadow-sm mb-3">
                        <i class="bi bi-eye mb-3 text-primary" style="font-size: 4rem;"></i>
                        <h5 class="fw-bold">{{ __('messages.view_pdf') }}</h5>
                        <p class="text-muted mb-3">{{ __('messages.view_pdf_desc') }}</p>
                        <button class="btn btn-primary px-4">
                            <i class="bi bi-upload me-2"></i>{{ __('messages.view_pdf_choose') ?? 'Choose PDF File' }}
                        </button>
                        <input type="file" id="file-input" class="d-none" accept=".pdf,application/pdf">
                        <p class="text-muted small mt-3 mb-0"><i class="bi bi-shield-check me-1"></i>{{ __('messages.view_pdf_privacy') ?? 'Processed entirely in your browser. No file is uploaded.' }}</p>
                    </div>

                    <!-- Viewer -->
                    <div id="viewer-area" class="d-none rounded overflow-hidden shadow">

                        <!-- Top bar: filename + controls -->
                        <div id="toolbar" class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                <i class="bi bi-file-earmark-pdf text-danger fs-5"></i>
                                <span id="file-name-label" class="text-white fw-semibold text-truncate" style="max-width:200px;"></span>
                                <span class="badge bg-secondary" id="total-pages-badge"></span>
                            </div>

                            <div class="d-flex align-items-center gap-2 flex-wrap justify-content-center">
                                <!-- Page navigation -->
                                <button class="btn btn-sm btn-outline-light" id="btn-first" title="First page"><i class="bi bi-skip-backward-fill"></i></button>
                                <button class="btn btn-sm btn-outline-light" id="btn-prev" title="Previous page"><i class="bi bi-chevron-left"></i></button>
                                <input type="number" id="page-input" class="form-control form-control-sm" min="1" value="1">
                                <span class="page-label" id="page-of-label">/ 1</span>
                                <button class="btn btn-sm btn-outline-light" id="btn-next" title="Next page"><i class="bi bi-chevron-right"></i></button>
                                <button class="btn btn-sm btn-outline-light" id="btn-last" title="Last page"><i class="bi bi-skip-forward-fill"></i></button>

                                <!-- Zoom -->
                                <button class="btn btn-sm btn-outline-light" id="btn-zoom-out" title="Zoom out"><i class="bi bi-zoom-out"></i></button>
                                <input type="number" id="zoom-level" class="form-control form-control-sm" min="25" max="400" step="25" value="100">
                                <span class="page-label">%</span>
                                <button class="btn btn-sm btn-outline-light" id="btn-zoom-in" title="Zoom in"><i class="bi bi-zoom-in"></i></button>
                                <button class="btn btn-sm btn-outline-secondary" id="btn-fit-width" title="Fit width"><i class="bi bi-arrows-expand"></i></button>

                                <!-- Rendering mode -->
                                <div class="btn-group" role="group">
                                    <input type="radio" class="btn-check" name="renderMode" id="mode-single" value="single" checked>
                                    <label class="btn btn-sm btn-outline-light" for="mode-single" title="Single page"><i class="bi bi-file-earmark"></i></label>
                                    <input type="radio" class="btn-check" name="renderMode" id="mode-all" value="all">
                                    <label class="btn btn-sm btn-outline-light" for="mode-all" title="All pages"><i class="bi bi-files"></i></label>
                                </div>

                                <!-- Actions -->
                                <button class="btn btn-sm btn-outline-warning" id="btn-fullscreen" title="Fullscreen"><i class="bi bi-fullscreen"></i></button>
                                <button class="btn btn-sm btn-success" id="btn-download" title="Download PDF"><i class="bi bi-download me-1"></i>Download</button>
                                <button class="btn btn-sm btn-outline-danger" id="btn-close" title="Close"><i class="bi bi-x-lg"></i></button>
                            </div>
                        </div>

                        <!-- Main viewer body -->
                        <div class="d-flex">
                            <!-- Thumbnail panel -->
                            <div id="thumbnail-panel">
                                <div id="thumb-container"></div>
                            </div>

                            <!-- Canvas area -->
                            <div id="pdf-canvas-wrapper" class="flex-grow-1">
                                <!-- canvases injected here -->
                            </div>
                        </div>

                        <!-- Loading overlay -->
                        <div id="loading-overlay" class="d-none position-absolute top-50 start-50 translate-middle text-center text-white">
                            <div class="spinner-border text-light mb-2" role="status"></div>
                            <div id="loading-text">Loading...</div>
                        </div>
                    </div>

                    <div id="error-message" class="alert alert-danger mt-3 d-none"></div>

                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
<script>
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';

document.addEventListener('DOMContentLoaded', function () {
    const dropZone        = document.getElementById('drop-zone');
    const fileInput       = document.getElementById('file-input');
    const viewerArea      = document.getElementById('viewer-area');
    const canvasWrapper   = document.getElementById('pdf-canvas-wrapper');
    const thumbContainer  = document.getElementById('thumb-container');
    const fileNameLabel   = document.getElementById('file-name-label');
    const totalPagesBadge = document.getElementById('total-pages-badge');
    const pageInput       = document.getElementById('page-input');
    const pageOfLabel     = document.getElementById('page-of-label');
    const zoomInput       = document.getElementById('zoom-level');
    const errorDiv        = document.getElementById('error-message');
    const loadingOverlay  = document.getElementById('loading-overlay');
    const loadingText     = document.getElementById('loading-text');

    let pdfDoc        = null;
    let currentPage   = 1;
    let zoomScale     = 1.0;
    let renderMode    = 'single'; // 'single' | 'all'
    let selectedFile  = null;
    let renderTask    = null;
    let thumbsRendered = false;

    // ── Drop zone ──────────────────────────────────────────────────────────
    dropZone.addEventListener('click', () => fileInput.click());
    dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('dragover'); });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
    dropZone.addEventListener('drop', e => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        if (e.dataTransfer.files.length > 0) loadFile(e.dataTransfer.files[0]);
    });
    fileInput.addEventListener('change', e => {
        if (e.target.files.length > 0) loadFile(e.target.files[0]);
    });

    async function loadFile(file) {
        if (file.type !== 'application/pdf') {
            showError('Please select a valid PDF file.');
            return;
        }
        hideError();
        selectedFile = file;
        fileNameLabel.textContent = file.name;

        const arrayBuffer = await file.arrayBuffer();
        try {
            pdfDoc = await pdfjsLib.getDocument({ data: arrayBuffer }).promise;
        } catch (err) {
            showError('Could not open PDF: ' + (err.message || err));
            return;
        }

        const total = pdfDoc.numPages;
        totalPagesBadge.textContent = total + (total === 1 ? ' page' : ' pages');
        pageInput.max = total;
        pageOfLabel.textContent = '/ ' + total;
        currentPage = 1;
        pageInput.value = 1;

        dropZone.classList.add('d-none');
        viewerArea.classList.remove('d-none');

        thumbsRendered = false;
        await renderThumbnails();
        await renderCurrent();
    }

    // ── Thumbnails ─────────────────────────────────────────────────────────
    async function renderThumbnails() {
        thumbContainer.innerHTML = '';
        const total = pdfDoc.numPages;
        for (let i = 1; i <= total; i++) {
            const item = document.createElement('div');
            item.className = 'thumb-item' + (i === currentPage ? ' active' : '');
            item.dataset.page = i;

            const canvas = document.createElement('canvas');
            item.appendChild(canvas);

            const label = document.createElement('div');
            label.className = 'thumb-label';
            label.textContent = i;
            item.appendChild(label);

            thumbContainer.appendChild(item);

            item.addEventListener('click', () => {
                currentPage = i;
                pageInput.value = i;
                if (renderMode === 'all') {
                    scrollToPage(i);
                } else {
                    renderCurrent();
                }
                updateActiveThumb();
            });

            // Render thumbnail at low scale
            const page = await pdfDoc.getPage(i);
            const vp = page.getViewport({ scale: 0.2 });
            canvas.width = vp.width;
            canvas.height = vp.height;
            await page.render({ canvasContext: canvas.getContext('2d'), viewport: vp }).promise;
        }
        thumbsRendered = true;
    }

    function updateActiveThumb() {
        document.querySelectorAll('.thumb-item').forEach(el => {
            el.classList.toggle('active', parseInt(el.dataset.page) === currentPage);
        });
        const active = thumbContainer.querySelector('.thumb-item.active');
        if (active) active.scrollIntoView({ block: 'nearest' });
    }

    // ── Render ─────────────────────────────────────────────────────────────
    async function renderCurrent() {
        if (!pdfDoc) return;
        if (renderMode === 'all') {
            await renderAll();
        } else {
            await renderSinglePage(currentPage);
        }
        updateActiveThumb();
    }

    async function renderSinglePage(num) {
        canvasWrapper.innerHTML = '';
        showLoading('Rendering page ' + num + '…');

        const page = await pdfDoc.getPage(num);
        const vp = page.getViewport({ scale: zoomScale });

        const canvas = document.createElement('canvas');
        canvas.className = 'pdf-page-canvas';
        canvas.width  = vp.width;
        canvas.height = vp.height;
        canvasWrapper.appendChild(canvas);

        if (renderTask) { renderTask.cancel(); }
        renderTask = page.render({ canvasContext: canvas.getContext('2d'), viewport: vp });
        try { await renderTask.promise; } catch (e) { /* cancelled */ }
        renderTask = null;
        hideLoading();
    }

    async function renderAll() {
        canvasWrapper.innerHTML = '';
        showLoading('Rendering all pages…');
        const total = pdfDoc.numPages;
        for (let i = 1; i <= total; i++) {
            loadingText.textContent = `Rendering page ${i} of ${total}…`;
            const page = await pdfDoc.getPage(i);
            const vp   = page.getViewport({ scale: zoomScale });
            const canvas = document.createElement('canvas');
            canvas.className = 'pdf-page-canvas';
            canvas.dataset.pageNum = i;
            canvas.width  = vp.width;
            canvas.height = vp.height;
            canvasWrapper.appendChild(canvas);
            await page.render({ canvasContext: canvas.getContext('2d'), viewport: vp }).promise;
        }
        hideLoading();
    }

    function scrollToPage(num) {
        const canvas = canvasWrapper.querySelector(`[data-page-num="${num}"]`);
        if (canvas) canvas.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // ── Toolbar controls ───────────────────────────────────────────────────
    document.getElementById('btn-first').addEventListener('click', () => navigate(1));
    document.getElementById('btn-last').addEventListener('click', () => navigate(pdfDoc?.numPages || 1));
    document.getElementById('btn-prev').addEventListener('click', () => navigate(currentPage - 1));
    document.getElementById('btn-next').addEventListener('click', () => navigate(currentPage + 1));

    pageInput.addEventListener('change', () => navigate(parseInt(pageInput.value)));

    function navigate(num) {
        if (!pdfDoc) return;
        num = Math.max(1, Math.min(pdfDoc.numPages, num));
        currentPage = num;
        pageInput.value = num;
        if (renderMode === 'all') {
            scrollToPage(num);
            updateActiveThumb();
        } else {
            renderCurrent();
        }
    }

    document.getElementById('btn-zoom-in').addEventListener('click', () => applyZoom(zoomScale + 0.25));
    document.getElementById('btn-zoom-out').addEventListener('click', () => applyZoom(zoomScale - 0.25));
    zoomInput.addEventListener('change', () => applyZoom(parseInt(zoomInput.value) / 100));

    document.getElementById('btn-fit-width').addEventListener('click', () => {
        if (!pdfDoc) return;
        pdfDoc.getPage(currentPage).then(page => {
            const vp = page.getViewport({ scale: 1.0 });
            const available = canvasWrapper.clientWidth - 32;
            applyZoom(available / vp.width);
        });
    });

    function applyZoom(scale) {
        zoomScale = Math.max(0.25, Math.min(4.0, scale));
        zoomInput.value = Math.round(zoomScale * 100);
        renderCurrent();
    }

    document.querySelectorAll('input[name="renderMode"]').forEach(radio => {
        radio.addEventListener('change', e => {
            renderMode = e.target.value;
            renderCurrent();
        });
    });

    document.getElementById('btn-fullscreen').addEventListener('click', () => {
        const el = viewerArea;
        if (document.fullscreenElement) {
            document.exitFullscreen();
        } else {
            el.requestFullscreen().catch(() => {});
        }
    });

    document.getElementById('btn-download').addEventListener('click', () => {
        if (!selectedFile) return;
        const url = URL.createObjectURL(selectedFile);
        const a = document.createElement('a');
        a.href = url;
        a.download = selectedFile.name;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    });

    document.getElementById('btn-close').addEventListener('click', () => {
        pdfDoc = null;
        selectedFile = null;
        canvasWrapper.innerHTML = '';
        thumbContainer.innerHTML = '';
        fileInput.value = '';
        viewerArea.classList.add('d-none');
        dropZone.classList.remove('d-none');
        hideError();
    });

    // Track current page while scrolling in "all pages" mode
    canvasWrapper.addEventListener('scroll', () => {
        if (renderMode !== 'all') return;
        const pages = canvasWrapper.querySelectorAll('[data-page-num]');
        let closest = null, minDist = Infinity;
        pages.forEach(canvas => {
            const dist = Math.abs(canvas.getBoundingClientRect().top - canvasWrapper.getBoundingClientRect().top);
            if (dist < minDist) { minDist = dist; closest = canvas; }
        });
        if (closest) {
            const num = parseInt(closest.dataset.pageNum);
            if (num !== currentPage) {
                currentPage = num;
                pageInput.value = num;
                updateActiveThumb();
            }
        }
    });

    // Keyboard navigation
    document.addEventListener('keydown', e => {
        if (!pdfDoc) return;
        if (e.target.tagName === 'INPUT') return;
        if (e.key === 'ArrowRight' || e.key === 'ArrowDown') navigate(currentPage + 1);
        if (e.key === 'ArrowLeft'  || e.key === 'ArrowUp')   navigate(currentPage - 1);
        if (e.key === 'Home') navigate(1);
        if (e.key === 'End')  navigate(pdfDoc.numPages);
        if (e.key === '+' || e.key === '=') applyZoom(zoomScale + 0.25);
        if (e.key === '-') applyZoom(zoomScale - 0.25);
    });

    // ── Helpers ────────────────────────────────────────────────────────────
    function showLoading(msg) {
        loadingText.textContent = msg || 'Loading…';
        loadingOverlay.classList.remove('d-none');
        viewerArea.style.position = 'relative';
    }
    function hideLoading() {
        loadingOverlay.classList.add('d-none');
    }
    function showError(msg) {
        errorDiv.textContent = msg;
        errorDiv.classList.remove('d-none');
    }
    function hideError() {
        errorDiv.classList.add('d-none');
    }
});
</script>
@endpush

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            @if(Lang::has('messages.article_view_pdf_content'))
            <div class="card shadow-sm border-0 bg-white">
                <div class="card-body p-4 p-md-5 text-start">
                    {!! __('messages.article_view_pdf_content') !!}
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
