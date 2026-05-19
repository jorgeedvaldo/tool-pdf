import { diffWords } from 'https://cdn.jsdelivr.net/npm/diff@5.2.0/+esm';

// ── Web Worker for visual diff ────────────────────────────────────────────────
const diffWorker = new Worker(new URL('./compare-pdf-worker.js', import.meta.url), { type: 'module' });
const pendingDiffs = new Map();
let diffIdCounter = 0;

diffWorker.onmessage = ({ data }) => {
    if (data.type !== 'result') return;
    const resolve = pendingDiffs.get(data.id);
    if (!resolve) return;
    pendingDiffs.delete(data.id);
    resolve(data);
};

diffWorker.onerror = (e) => console.error('Diff worker error:', e);

function runVisualDiff(imgA, imgB, threshold) {
    return new Promise((resolve) => {
        const w = Math.max(imgA.width, imgB.width);
        const h = Math.max(imgA.height, imgB.height);

        const normalise = (src, sw, sh) => {
            if (sw === w && sh === h) return src.data;
            const c = document.createElement('canvas'); c.width = w; c.height = h;
            const tmp = document.createElement('canvas'); tmp.width = sw; tmp.height = sh;
            tmp.getContext('2d').putImageData(src, 0, 0);
            c.getContext('2d').drawImage(tmp, 0, 0);
            return c.getContext('2d').getImageData(0, 0, w, h).data;
        };

        const rawA = normalise(imgA.imageData, imgA.width, imgA.height);
        const rawB = normalise(imgB.imageData, imgB.width, imgB.height);

        // Slice to copy — keeps original ImageData intact after transfer
        const bufA = rawA.buffer.slice(0);
        const bufB = rawB.buffer.slice(0);

        const id = diffIdCounter++;
        pendingDiffs.set(id, ({ diffOut, ratio }) => {
            const diffCanvas = document.createElement('canvas');
            diffCanvas.width = w; diffCanvas.height = h;
            diffCanvas.getContext('2d').putImageData(new ImageData(new Uint8ClampedArray(diffOut), w, h), 0, 0);
            resolve({ ratio, diffCanvas });
        });

        diffWorker.postMessage(
            { type: 'visual-diff', id, dataA: new Uint8ClampedArray(bufA), dataB: new Uint8ClampedArray(bufB), w, h, threshold },
            [bufA, bufB]
        );
    });
}

// ── State ─────────────────────────────────────────────────────────────────────
const S = {
    pdfA: null, pdfB: null,
    fileA: null, fileB: null,
    results: [],
    zoom: 1.0,
    syncScroll: true,
    showDiffOverlay: true,
    changedOnly: false,
    currentPage: 1,
    totalPages: 0,
    cancelled: false,
    ocrEnabled: false,
    TesseractLib: null,
    textPage: 1,
    overlayPage: 1,
    threshold: 0.1,
};

const $ = id => document.getElementById(id);
const fmtBytes = b => b < 1048576 ? (b / 1024).toFixed(1) + ' KB' : (b / 1048576).toFixed(1) + ' MB';
const RENDER_SCALE = 1.5;

// ── Upload UI ─────────────────────────────────────────────────────────────────
function setupUpload(dropZoneId, inputId, cardId, nameId, sizeId, removeId, errorId, which) {
    const zone = $(dropZoneId), input = $(inputId), card = $(cardId);
    const nameEl = $(nameId), sizeEl = $(sizeId), removeBtn = $(removeId), errorEl = $(errorId);

    const setFile = (file) => {
        if (!file || file.type !== 'application/pdf') {
            errorEl.textContent = 'Please select a valid PDF file.';
            errorEl.classList.remove('d-none');
            return;
        }
        errorEl.classList.add('d-none');
        S['file' + which] = file;
        nameEl.textContent = file.name;
        sizeEl.textContent = fmtBytes(file.size);
        zone.classList.add('d-none');
        card.classList.remove('d-none');
        updateCompareBtn();
    };

    zone.addEventListener('click', () => input.click());
    input.addEventListener('change', e => e.target.files[0] && setFile(e.target.files[0]));
    zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('cmp-drag-over'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('cmp-drag-over'));
    zone.addEventListener('drop', e => {
        e.preventDefault(); zone.classList.remove('cmp-drag-over');
        if (e.dataTransfer.files[0]) setFile(e.dataTransfer.files[0]);
    });
    removeBtn.addEventListener('click', () => {
        S['file' + which] = null; S['pdf' + which] = null;
        input.value = ''; card.classList.add('d-none'); zone.classList.remove('d-none');
        updateCompareBtn();
    });
}

setupUpload('original-drop-zone','original-file-input','original-file-card','original-file-name','original-file-size','original-remove-btn','original-file-error','A');
setupUpload('modified-drop-zone','modified-file-input','modified-file-card','modified-file-name','modified-file-size','modified-remove-btn','modified-file-error','B');

function updateCompareBtn() { $('cmp-compare-btn').disabled = !(S.fileA && S.fileB); }

// ── Options ───────────────────────────────────────────────────────────────────
$('cmp-threshold-slider').addEventListener('input', function () {
    S.threshold = parseFloat(this.value);
    $('cmp-threshold-label').textContent = Math.round(S.threshold * 100) + '%';
});

$('cmp-ocr-toggle').addEventListener('change', function () {
    S.ocrEnabled = this.checked;
    $('cmp-ocr-warning').classList.toggle('d-none', !S.ocrEnabled);
    if (S.ocrEnabled && !S.TesseractLib) {
        const s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js';
        s.onload = () => { S.TesseractLib = window.Tesseract; };
        document.head.appendChild(s);
    }
});

// ── Progress ──────────────────────────────────────────────────────────────────
function setProgress(pct, msg, detail) {
    $('cmp-progress-bar').style.width = pct + '%';
    $('cmp-progress-message').textContent = msg;
    $('cmp-progress-detail').textContent = detail || '';
}

// ── PDF loading ───────────────────────────────────────────────────────────────
async function loadPdf(file) {
    const buf = await file.arrayBuffer();
    return pdfjsLib.getDocument({ data: buf }).promise;
}

async function renderPageToData(pdfDoc, pageNum) {
    const page = await pdfDoc.getPage(pageNum);
    const vp = page.getViewport({ scale: RENDER_SCALE });
    const canvas = document.createElement('canvas');
    canvas.width = vp.width; canvas.height = vp.height;
    const ctx = canvas.getContext('2d');
    await page.render({ canvasContext: ctx, viewport: vp }).promise;
    return { imageData: ctx.getImageData(0, 0, vp.width, vp.height), canvas, width: vp.width, height: vp.height };
}

async function extractText(pdfDoc, pageNum) {
    if (S.ocrEnabled && S.TesseractLib) {
        const { canvas } = await renderPageToData(pdfDoc, pageNum);
        const { data: { text } } = await S.TesseractLib.recognize(canvas, 'eng');
        return text;
    }
    const page = await pdfDoc.getPage(pageNum);
    const tc = await page.getTextContent();
    return tc.items.map(i => i.str).join(' ');
}

// ── Text diff ─────────────────────────────────────────────────────────────────
function buildTextDiff(textA, textB) {
    const parts = diffWords(textA, textB);
    let added = 0, removed = 0, html = '';
    for (const p of parts) {
        const esc = p.value.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        if (p.added)        { added   += p.value.split(/\s+/).filter(Boolean).length; html += `<span class="pdf-diff-added">${esc}</span>`; }
        else if (p.removed) { removed += p.value.split(/\s+/).filter(Boolean).length; html += `<span class="pdf-diff-removed">${esc}</span>`; }
        else                { html += `<span class="pdf-diff-unchanged">${esc}</span>`; }
    }
    return { html, added, removed };
}

// ── Run comparison ────────────────────────────────────────────────────────────
$('cmp-compare-btn').addEventListener('click', runComparison);
$('cmp-cancel-btn').addEventListener('click', () => { S.cancelled = true; });

async function runComparison() {
    S.cancelled = false; S.results = [];
    $('cmp-results-area').classList.add('d-none');
    $('cmp-progress-area').classList.remove('d-none');
    $('cmp-compare-btn').disabled = true;
    setProgress(0, 'Loading PDFs…');

    try {
        S.pdfA = await loadPdf(S.fileA);
        S.pdfB = await loadPdf(S.fileB);
        const nA = S.pdfA.numPages, nB = S.pdfB.numPages;
        S.totalPages = Math.max(nA, nB);
        S.currentPage = 1;

        $('cmp-orig-pages-lbl').textContent = nA + ' pages';
        $('cmp-mod-pages-lbl').textContent = nB + ' pages';
        $('cmp-page-total').textContent = S.totalPages;
        $('cmp-text-page-tot').textContent = S.totalPages;
        $('cmp-overlay-page-tot').textContent = S.totalPages;

        for (let i = 1; i <= S.totalPages; i++) {
            if (S.cancelled) break;
            setProgress(Math.round(i / S.totalPages * 90), `Analysing page ${i} of ${S.totalPages}…`);

            const hasA = i <= nA, hasB = i <= nB;
            const imgA = hasA ? await renderPageToData(S.pdfA, i) : null;
            const imgB = hasB ? await renderPageToData(S.pdfB, i) : null;
            const textA = hasA ? await extractText(S.pdfA, i) : '';
            const textB = hasB ? await extractText(S.pdfB, i) : '';
            const { html: diffHtml, added: addedWords, removed: removedWords } = buildTextDiff(textA, textB);

            let status = 'unchanged', diffRatio = 0, diffCanvas = null;

            if (!hasA) {
                status = 'added';
            } else if (!hasB) {
                status = 'removed';
            } else {
                // ← visual diff runs off the main thread via Web Worker
                const vd = await runVisualDiff(imgA, imgB, S.threshold);
                diffRatio = vd.ratio; diffCanvas = vd.diffCanvas;
                if (diffRatio > 0.001 || addedWords > 0 || removedWords > 0) status = 'changed';
            }

            S.results.push({ i, status, diffRatio, addedWords, removedWords, diffHtml, textA, textB, imgA, imgB, diffCanvas });
        }

        if (!S.cancelled) {
            setProgress(100, 'Done!');
            renderResults();
        }
    } catch (err) {
        setProgress(0, 'Error: ' + err.message);
        console.error(err);
    } finally {
        $('cmp-progress-area').classList.add('d-none');
        $('cmp-compare-btn').disabled = false;
    }
}

// ── Render all results ────────────────────────────────────────────────────────
function renderResults() {
    updateSidebarStats();
    renderPagesList();
    renderContinuousViewer();
    renderThumbs();
    renderTextDiffPage(1);
    renderOverlayPage(1);
    renderReport();
    $('cmp-results-area').classList.remove('d-none');
    activateTab('visual');
}

// ── Sidebar stats ─────────────────────────────────────────────────────────────
function updateSidebarStats() {
    const total     = S.results.length;
    const changed   = S.results.filter(r => r.status === 'changed').length;
    const unchanged = S.results.filter(r => r.status === 'unchanged').length;
    const added     = S.results.filter(r => r.status === 'added').length;
    const removed   = S.results.filter(r => r.status === 'removed').length;
    const avg = total ? (S.results.reduce((a, r) => a + r.diffRatio, 0) / total * 100).toFixed(1) + '%' : '—';
    $('cmp-stat-total').textContent = total;
    $('cmp-stat-changed').textContent = changed;
    $('cmp-stat-unchanged').textContent = unchanged;
    $('cmp-stat-added').textContent = added;
    $('cmp-stat-removed').textContent = removed;
    $('cmp-stat-avgdiff').textContent = avg;
}

function statusBadgeClass(s) {
    return s === 'changed' ? 'bg-danger' : s === 'unchanged' ? 'bg-success' : s === 'added' ? 'bg-primary' : 'bg-warning text-dark';
}

// ── Sidebar pages list ────────────────────────────────────────────────────────
function renderPagesList() {
    const ul = $('cmp-pages-list');
    ul.innerHTML = '';
    S.results.forEach(r => {
        const li = document.createElement('li');
        li.className = 'list-group-item list-group-item-action py-1 px-2 d-flex justify-content-between align-items-center';
        li.style.cursor = 'pointer';
        if (r.status === 'changed') li.classList.add('active');
        li.innerHTML = `<span>Page ${r.i}</span><span class="badge ${statusBadgeClass(r.status)}">${r.status}</span>`;
        li.addEventListener('click', () => scrollToPage(r.i));
        ul.appendChild(li);
    });
}

// ── Continuous side-by-side viewer ────────────────────────────────────────────
let _syncing = false;

function renderContinuousViewer() {
    const left = $('cmp-panel-left'), right = $('cmp-panel-right');
    left.innerHTML = ''; right.innerHTML = '';

    S.results.forEach(r => {
        const blockL = makePageBlock(r.i);
        const blockR = makePageBlock(r.i);
        left.appendChild(blockL);
        right.appendChild(blockR);

        if (r.imgA) drawPageBlock(blockL, r.imgA, r.diffCanvas);
        else blockL.appendChild(makePlaceholder('No page'));

        if (r.imgB) drawPageBlock(blockR, r.imgB, r.diffCanvas);
        else blockR.appendChild(makePlaceholder('No page'));
    });

    setupSyncScroll();
    setupIntersectionObserver();
    updateChangedOnlyFilter();
}

function makePageBlock(pageNum) {
    const wrap = document.createElement('div');
    wrap.className = 'cmp-page-block';
    wrap.dataset.page = pageNum;
    const lbl = document.createElement('div');
    lbl.className = 'cmp-page-lbl';
    lbl.textContent = 'Page ' + pageNum;
    wrap.appendChild(lbl);
    return wrap;
}

function drawPageBlock(block, imgData, diffCanvas) {
    const w = Math.round(imgData.width * S.zoom);
    const h = Math.round(imgData.height * S.zoom);
    block.style.width = w + 'px';

    const canvas = document.createElement('canvas');
    canvas.width = imgData.width; canvas.height = imgData.height;
    canvas.style.width = w + 'px'; canvas.style.height = h + 'px'; canvas.style.display = 'block';
    canvas.getContext('2d').putImageData(imgData.imageData, 0, 0);
    block.appendChild(canvas);

    if (diffCanvas && S.showDiffOverlay) {
        const ov = document.createElement('canvas');
        ov.className = 'cmp-diff-overlay';
        ov.width = diffCanvas.width; ov.height = diffCanvas.height;
        ov.getContext('2d').drawImage(diffCanvas, 0, 0);
        block.appendChild(ov);
    }
}

function makePlaceholder(text) {
    const d = document.createElement('div');
    d.className = 'cmp-page-placeholder';
    d.textContent = text;
    return d;
}

// ── Sync scroll ───────────────────────────────────────────────────────────────
function setupSyncScroll() {
    const L = $('cmp-panel-left'), R = $('cmp-panel-right');
    const sync = (src, tgt) => {
        if (!S.syncScroll || _syncing) return;
        _syncing = true;
        tgt.scrollTop = src.scrollTop / Math.max(1, src.scrollHeight - src.clientHeight) * Math.max(1, tgt.scrollHeight - tgt.clientHeight);
        _syncing = false;
    };
    L.addEventListener('scroll', () => sync(L, R));
    R.addEventListener('scroll', () => sync(R, L));
}

// ── IntersectionObserver ──────────────────────────────────────────────────────
function setupIntersectionObserver() {
    const left = $('cmp-panel-left');
    const obs = new IntersectionObserver(entries => {
        for (const e of entries) {
            if (e.isIntersecting) {
                const pg = parseInt(e.target.dataset.page);
                if (!isNaN(pg) && pg !== S.currentPage) { S.currentPage = pg; refreshPageIndicator(); }
            }
        }
    }, { root: left, threshold: 0.3 });
    left.querySelectorAll('.cmp-page-block').forEach(b => obs.observe(b));
}

function refreshPageIndicator() {
    $('cmp-page-current').textContent = S.currentPage;
    const r = S.results[S.currentPage - 1];
    if (r) {
        const b = $('cmp-page-status');
        b.textContent = r.status; b.className = 'badge ms-1 ' + statusBadgeClass(r.status); b.style.fontSize = '.72rem';
    }
    document.querySelectorAll('#cmp-pages-list li').forEach((li, idx) => li.classList.toggle('fw-bold', idx === S.currentPage - 1));
    document.querySelectorAll('#cmp-thumbs-strip .cmp-thumb').forEach((t, idx) => t.classList.toggle('cmp-thumb-active', idx === S.currentPage - 1));
}

function scrollToPage(pageNum) {
    const block = $('cmp-panel-left').querySelector('[data-page="' + pageNum + '"]');
    if (block) block.scrollIntoView({ behavior: 'smooth', block: 'start' });
    S.currentPage = pageNum; refreshPageIndicator();
}

// ── Toolbar controls ──────────────────────────────────────────────────────────
$('cmp-zoom-in').addEventListener('click', () => applyZoom(0.25));
$('cmp-zoom-out').addEventListener('click', () => applyZoom(-0.25));
$('cmp-fit-width').addEventListener('click', () => {
    const left = $('cmp-panel-left');
    const first = S.results.find(r => r.imgA || r.imgB);
    if (!first) return;
    const srcW = (first.imgA || first.imgB).width;
    S.zoom = Math.max(0.25, Math.min(3, (left.clientWidth - 40) / srcW));
    $('cmp-zoom-label').textContent = Math.round(S.zoom * 100) + '%';
    if (S.results.length) renderContinuousViewer();
});

function applyZoom(delta) {
    S.zoom = Math.max(0.25, Math.min(3, S.zoom + delta));
    $('cmp-zoom-label').textContent = Math.round(S.zoom * 100) + '%';
    if (S.results.length) renderContinuousViewer();
}

$('cmp-sync-scroll-toggle').addEventListener('change', e => { S.syncScroll = e.target.checked; });
$('cmp-show-diff-overlay').addEventListener('change', e => {
    S.showDiffOverlay = e.target.checked;
    if (S.results.length) renderContinuousViewer();
});
$('cmp-changed-only').addEventListener('change', e => { S.changedOnly = e.target.checked; updateChangedOnlyFilter(); });

function updateChangedOnlyFilter() {
    const left = $('cmp-panel-left'), right = $('cmp-panel-right');
    S.results.forEach(r => {
        const bL = left.querySelector('[data-page="' + r.i + '"]');
        const bR = right.querySelector('[data-page="' + r.i + '"]');
        const hide = S.changedOnly && r.status === 'unchanged';
        if (bL) bL.style.display = hide ? 'none' : '';
        if (bR) bR.style.display = hide ? 'none' : '';
    });
}

$('cmp-prev-page').addEventListener('click', () => { if (S.currentPage > 1) scrollToPage(S.currentPage - 1); });
$('cmp-next-page').addEventListener('click', () => { if (S.currentPage < S.totalPages) scrollToPage(S.currentPage + 1); });
$('cmp-next-diff').addEventListener('click', jumpToNextDiff);

function jumpToNextDiff() {
    for (let i = S.currentPage; i < S.results.length; i++) {
        if (S.results[i].status === 'changed') { scrollToPage(i + 1); return; }
    }
    for (let i = 0; i < S.currentPage - 1; i++) {
        if (S.results[i].status === 'changed') { scrollToPage(i + 1); return; }
    }
}

// ── Thumbnails ────────────────────────────────────────────────────────────────
function renderThumbs() {
    const strip = $('cmp-thumbs-strip');
    strip.innerHTML = '';
    const colors = { changed: '#ef4444', unchanged: '#22c55e', added: '#3b82f6', removed: '#f59e0b' };
    S.results.forEach(r => {
        const thumb = document.createElement('div');
        thumb.className = 'cmp-thumb';
        thumb.style.borderColor = colors[r.status] || '#e5e7eb';

        const src = r.imgA || r.imgB;
        if (src) {
            const tmp = document.createElement('canvas');
            tmp.width = src.width; tmp.height = src.height;
            tmp.getContext('2d').putImageData(src.imageData, 0, 0);
            const img = document.createElement('img');
            img.src = tmp.toDataURL('image/jpeg', 0.4);
            thumb.appendChild(img);
        }

        const lbl = document.createElement('div');
        lbl.className = 'cmp-thumb-lbl';
        lbl.textContent = r.i;
        lbl.style.color = colors[r.status] || '#6b7280';
        thumb.appendChild(lbl);

        thumb.addEventListener('click', () => scrollToPage(r.i));
        if (r.i === S.currentPage) thumb.classList.add('cmp-thumb-active');
        strip.appendChild(thumb);
    });
}

// ── Text diff panel ───────────────────────────────────────────────────────────
function renderTextDiffPage(pg) {
    S.textPage = pg;
    $('cmp-text-page-cur').textContent = pg;
    const r = S.results[pg - 1];
    const badge = $('cmp-text-status');
    badge.textContent = r ? r.status : '—';
    badge.className = 'badge ms-1 ' + (r ? statusBadgeClass(r.status) : 'bg-secondary');
    const content = $('cmp-text-diff-content');
    if (!r) { content.innerHTML = '<p class="text-muted">No data.</p>'; return; }
    if (!r.textA && !r.textB) { content.innerHTML = '<p class="text-muted">No text found. Try enabling OCR.</p>'; return; }
    content.innerHTML = '<div class="cmp-text-diff-wrap">' + r.diffHtml + '</div>';
}

$('cmp-text-prev').addEventListener('click', () => { if (S.textPage > 1) renderTextDiffPage(S.textPage - 1); });
$('cmp-text-next').addEventListener('click', () => { if (S.textPage < S.totalPages) renderTextDiffPage(S.textPage + 1); });

// ── Overlay panel ─────────────────────────────────────────────────────────────
function renderOverlayPage(pg) {
    S.overlayPage = pg;
    $('cmp-overlay-page-cur').textContent = pg;
    const r = S.results[pg - 1];
    const container = $('cmp-overlay-container');
    container.innerHTML = '';
    if (!r || (!r.imgA && !r.imgB)) { container.textContent = 'No data.'; return; }

    const w = Math.max(r.imgA ? r.imgA.width : 0, r.imgB ? r.imgB.width : 0);
    const h = Math.max(r.imgA ? r.imgA.height : 0, r.imgB ? r.imgB.height : 0);
    const canvas = document.createElement('canvas');
    canvas.width = w; canvas.height = h; canvas.style.maxWidth = '100%';
    container.appendChild(canvas);
    drawOverlay(canvas, r);
}

function drawOverlay(canvas, r) {
    const ctx = canvas.getContext('2d');
    const w = canvas.width, h = canvas.height;
    ctx.clearRect(0, 0, w, h);
    const draw = (imgData, opacity, blendMode) => {
        if (!imgData) return;
        const tmp = document.createElement('canvas');
        tmp.width = imgData.width; tmp.height = imgData.height;
        tmp.getContext('2d').putImageData(imgData.imageData, 0, 0);
        ctx.save(); ctx.globalAlpha = opacity; ctx.globalCompositeOperation = blendMode;
        ctx.drawImage(tmp, 0, 0, w, h); ctx.restore();
    };
    draw(r.imgA, parseFloat($('cmp-opacity-a').value), 'source-over');
    draw(r.imgB, parseFloat($('cmp-opacity-b').value), $('cmp-blend-mode').value);
}

$('cmp-overlay-prev').addEventListener('click', () => { if (S.overlayPage > 1) renderOverlayPage(S.overlayPage - 1); });
$('cmp-overlay-next').addEventListener('click', () => { if (S.overlayPage < S.totalPages) renderOverlayPage(S.overlayPage + 1); });
['cmp-opacity-a','cmp-opacity-b','cmp-blend-mode'].forEach(id => {
    $(id).addEventListener('input', () => {
        const r = S.results[S.overlayPage - 1];
        const c = $('cmp-overlay-container').querySelector('canvas');
        if (r && c) drawOverlay(c, r);
    });
});

// ── Report panel ──────────────────────────────────────────────────────────────
function renderReport() {
    const total     = S.results.length;
    const changed   = S.results.filter(r => r.status === 'changed').length;
    const unchanged = S.results.filter(r => r.status === 'unchanged').length;
    const other     = S.results.filter(r => r.status === 'added' || r.status === 'removed').length;

    $('cmp-report-summary').innerHTML = `
        <div class="row g-3 mb-3">
            <div class="col-6 col-md-3"><div class="cmp-stat-box"><div class="cmp-stat-num">${total}</div>Total pages</div></div>
            <div class="col-6 col-md-3"><div class="cmp-stat-box cmp-stat-changed"><div class="cmp-stat-num text-danger">${changed}</div>Changed</div></div>
            <div class="col-6 col-md-3"><div class="cmp-stat-box cmp-stat-ok"><div class="cmp-stat-num text-success">${unchanged}</div>Unchanged</div></div>
            <div class="col-6 col-md-3"><div class="cmp-stat-box"><div class="cmp-stat-num text-primary">${other}</div>Added/Removed</div></div>
        </div>`;

    const tbody = $('cmp-report-tbody');
    tbody.innerHTML = '';
    S.results.forEach(r => {
        const tr = document.createElement('tr');
        tr.style.cursor = 'pointer';
        tr.innerHTML = `<td>${r.i}</td><td><span class="badge ${statusBadgeClass(r.status)}">${r.status}</span></td><td>${(r.diffRatio*100).toFixed(2)}%</td><td class="text-success">${r.addedWords}</td><td class="text-danger">${r.removedWords}</td>`;
        tr.addEventListener('click', () => { activateTab('visual'); scrollToPage(r.i); });
        tbody.appendChild(tr);
    });
}

// ── Export ────────────────────────────────────────────────────────────────────
$('cmp-export-json').addEventListener('click', () => {
    const data = { originalFile: S.fileA?.name, modifiedFile: S.fileB?.name, pages: S.results.map(r => ({ page: r.i, status: r.status, diffRatio: +(r.diffRatio*100).toFixed(2), addedWords: r.addedWords, removedWords: r.removedWords })) };
    const a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' }));
    a.download = 'pdf-comparison.json'; a.click();
});

$('cmp-export-html').addEventListener('click', () => {
    const rows = S.results.map(r => `<tr><td>${r.i}</td><td>${r.status}</td><td>${(r.diffRatio*100).toFixed(2)}%</td><td>${r.addedWords}</td><td>${r.removedWords}</td></tr>`).join('');
    const html = `<!DOCTYPE html><html><head><meta charset="UTF-8"><title>PDF Comparison Report</title><style>body{font-family:sans-serif;padding:20px}table{border-collapse:collapse;width:100%}th,td{border:1px solid #ddd;padding:8px}th{background:#f3f4f6}</style></head><body><h1>PDF Comparison Report</h1><p>Original: ${S.fileA?.name} | Modified: ${S.fileB?.name}</p><table><thead><tr><th>Page</th><th>Status</th><th>Visual Diff</th><th>Words Added</th><th>Words Removed</th></tr></thead><tbody>${rows}</tbody></table></body></html>`;
    const a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob([html], { type: 'text/html' }));
    a.download = 'pdf-comparison.html'; a.click();
});

// ── Tabs ──────────────────────────────────────────────────────────────────────
function activateTab(name) {
    document.querySelectorAll('.cmp-tab-btn').forEach(b => b.classList.toggle('active', b.dataset.cmpTab === name));
    document.querySelectorAll('[data-cmp-panel]').forEach(p => p.classList.toggle('d-none', p.dataset.cmpPanel !== name));
}
document.querySelectorAll('.cmp-tab-btn').forEach(btn => btn.addEventListener('click', () => activateTab(btn.dataset.cmpTab)));

// ── Panel divider drag-resize ─────────────────────────────────────────────────
(function () {
    const divider = $('cmp-panel-divider');
    let dragging = false, startX = 0, startW = 0;
    divider.addEventListener('mousedown', e => {
        dragging = true; startX = e.clientX; startW = $('cmp-panel-left').offsetWidth;
        document.body.style.cursor = 'col-resize'; e.preventDefault();
    });
    document.addEventListener('mousemove', e => {
        if (!dragging) return;
        const total = divider.parentElement.offsetWidth - 4;
        const newW = Math.max(80, Math.min(total - 80, startW + e.clientX - startX));
        const L = $('cmp-panel-left'), R = $('cmp-panel-right');
        L.style.flex = 'none'; L.style.width = newW + 'px'; R.style.flex = '1';
    });
    document.addEventListener('mouseup', () => { dragging = false; document.body.style.cursor = ''; });
})();
