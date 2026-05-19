import { diffWords } from 'https://cdn.jsdelivr.net/npm/diff@5.2.0/+esm';

// ── Web Worker for visual diff ────────────────────────────────────────────────
const diffWorker = new Worker(new URL('./compare-pdf-worker.js', import.meta.url), { type: 'module' });
const pendingDiffs = new Map();
let diffIdCounter = 0;

diffWorker.onmessage = ({ data }) => {
    const cb = pendingDiffs.get(data.id);
    if (!cb) return;
    pendingDiffs.delete(data.id);
    cb(data);
};
diffWorker.onerror = e => console.error('Diff worker error:', e);

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

        // Exact-size copy to avoid browser ImageData buffer padding mismatch
        const exactCopy = arr => {
            const out = new Uint8ClampedArray(w * h * 4);
            out.set(new Uint8ClampedArray(arr.buffer, arr.byteOffset, Math.min(arr.byteLength, w * h * 4)));
            return out.buffer;
        };

        const bufA = exactCopy(normalise(imgA.imageData, imgA.width, imgA.height));
        const bufB = exactCopy(normalise(imgB.imageData, imgB.width, imgB.height));
        const id = diffIdCounter++;

        pendingDiffs.set(id, (data) => {
            if (data.type === 'error') {
                console.warn('pixelmatch skipped:', data.message);
                resolve({ ratio: 0, diffCanvas: null });
                return;
            }
            const diffCanvas = document.createElement('canvas');
            diffCanvas.width = w; diffCanvas.height = h;
            diffCanvas.getContext('2d').putImageData(
                new ImageData(new Uint8ClampedArray(data.diffOut), w, h), 0, 0
            );
            resolve({ ratio: data.ratio, diffCanvas });
        });

        diffWorker.postMessage(
            { type: 'visual-diff', id, dataA: new Uint8ClampedArray(bufA), dataB: new Uint8ClampedArray(bufB), w, h, threshold },
            [bufA, bufB]
        );
    });
}

// ── State ─────────────────────────────────────────────────────────────────────
const S = {
    pdfA: null, pdfB: null, fileA: null, fileB: null,
    results: [], zoom: 1.0, syncScroll: true,
    showDiffOverlay: true,   // pixelmatch overlay
    showTextHighlights: true, // text highlights
    changedOnly: false, currentPage: 1, totalPages: 0,
    cancelled: false, ocrEnabled: false, TesseractLib: null,
    textPage: 1, overlayPage: 1, threshold: 0.1,
};

const $ = id => document.getElementById(id);
const fmtBytes = b => b < 1048576 ? (b / 1024).toFixed(1) + ' KB' : (b / 1048576).toFixed(1) + ' MB';
const RENDER_SCALE = 1.5;

// ── Upload UI ─────────────────────────────────────────────────────────────────
function setupUpload(dropZoneId, inputId, cardId, nameId, sizeId, removeId, errorId, which) {
    const zone = $(dropZoneId), input = $(inputId), card = $(cardId);
    const nameEl = $(nameId), sizeEl = $(sizeId), removeBtn = $(removeId), errorEl = $(errorId);

    const setFile = file => {
        if (!file || file.type !== 'application/pdf') {
            errorEl.textContent = 'Please select a valid PDF file.';
            errorEl.classList.remove('d-none'); return;
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
        S['file' + which] = null; S['pdf' + which] = null; input.value = '';
        card.classList.add('d-none'); zone.classList.remove('d-none');
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
    return pdfjsLib.getDocument({ data: await file.arrayBuffer() }).promise;
}

// Render page to ImageData (clean, no highlights)
async function renderPageToData(pdfDoc, pageNum) {
    const page = await pdfDoc.getPage(pageNum);
    const vp = page.getViewport({ scale: RENDER_SCALE });
    const canvas = document.createElement('canvas');
    canvas.width = vp.width; canvas.height = vp.height;
    const ctx = canvas.getContext('2d');
    await page.render({ canvasContext: ctx, viewport: vp }).promise;
    return { imageData: ctx.getImageData(0, 0, vp.width, vp.height), canvas, width: vp.width, height: vp.height };
}

// ── Text extraction with positions ────────────────────────────────────────────
async function extractTextItems(pdfDoc, pageNum) {
    const page = await pdfDoc.getPage(pageNum);
    const vp   = page.getViewport({ scale: RENDER_SCALE });
    const tc   = await page.getTextContent();

    const items = [];
    let fullText = '';

    for (const item of tc.items) {
        if (!item.str) continue;

        // Convert PDF user-space origin (bottom-left of text) → canvas coords
        const [cx, cy] = pdfjsLib.Util.applyTransform(
            [item.transform[4], item.transform[5]], vp.transform
        );

        // Font size in canvas units (approximate from transform matrix)
        const fontSize = Math.hypot(item.transform[0], item.transform[1]) * RENDER_SCALE;
        // Text width in canvas units
        const itemW = Math.abs(item.width || 0) * RENDER_SCALE;

        if (item.str.trim()) {
            items.push({
                str: item.str,
                startChar: fullText.length,
                endChar:   fullText.length + item.str.length,
                x: cx,
                y: cy - fontSize,        // baseline → top of glyph
                w: Math.max(itemW, 2),
                h: fontSize * 1.25,
            });
        }
        fullText += item.str;
    }

    return { items, fullText };
}

// OCR fallback (no positional data)
async function extractTextOCR(pdfDoc, pageNum) {
    const { canvas } = await renderPageToData(pdfDoc, pageNum);
    const { data: { text } } = await S.TesseractLib.recognize(canvas, 'eng');
    return { items: [], fullText: text };
}

// ── Text diff ─────────────────────────────────────────────────────────────────
function buildTextDiff(textA, textB) {
    let added = 0, removed = 0, html = '';
    for (const p of diffWords(textA, textB)) {
        const esc = p.value.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        if (p.added)        { added   += p.value.split(/\s+/).filter(Boolean).length; html += `<span class="pdf-diff-added">${esc}</span>`; }
        else if (p.removed) { removed += p.value.split(/\s+/).filter(Boolean).length; html += `<span class="pdf-diff-removed">${esc}</span>`; }
        else                { html += `<span class="pdf-diff-unchanged">${esc}</span>`; }
    }
    return { html, added, removed };
}

// Map diff words → text item positions for green/red highlights
function computeTextHighlights(textA, itemsA, textB, itemsB) {
    const parts = diffWords(textA, textB);
    const hlA = [], hlB = [];
    let posA = 0, posB = 0;

    for (const part of parts) {
        const len = part.value.length;
        if (part.removed) {
            itemsA.forEach(it => {
                if (it.endChar > posA && it.startChar < posA + len) hlA.push(it);
            });
            posA += len;
        } else if (part.added) {
            itemsB.forEach(it => {
                if (it.endChar > posB && it.startChar < posB + len) hlB.push(it);
            });
            posB += len;
        } else {
            posA += len; posB += len;
        }
    }
    return { hlA, hlB };
}

// ── Run comparison ────────────────────────────────────────────────────────────
$('cmp-compare-btn').addEventListener('click', runComparison);
$('cmp-cancel-btn').addEventListener('click', () => { S.cancelled = true; });
$('cmp-new-comparison').addEventListener('click', () => {
    $('cmp-results-area').classList.add('d-none');
    $('cmp-upload-section').classList.remove('d-none');
});

async function runComparison() {
    S.cancelled = false; S.results = [];
    $('cmp-upload-section').classList.add('d-none');
    $('cmp-results-area').classList.add('d-none');
    $('cmp-progress-area').classList.remove('d-none');
    $('cmp-compare-btn').disabled = true;
    setProgress(0, 'Loading PDFs…');

    try {
        S.pdfA = await loadPdf(S.fileA);
        S.pdfB = await loadPdf(S.fileB);
        const nA = S.pdfA.numPages, nB = S.pdfB.numPages;
        S.totalPages = Math.max(nA, nB); S.currentPage = 1;

        $('cmp-orig-pages-lbl').textContent = nA + ' pg';
        $('cmp-mod-pages-lbl').textContent  = nB + ' pg';
        $('cmp-orig-file-lbl').textContent  = S.fileA.name;
        $('cmp-mod-file-lbl').textContent   = S.fileB.name;
        $('cmp-page-total').textContent     = S.totalPages;
        $('cmp-text-page-tot').textContent  = S.totalPages;
        $('cmp-overlay-page-tot').textContent = S.totalPages;

        for (let i = 1; i <= S.totalPages; i++) {
            if (S.cancelled) break;
            setProgress(Math.round(i / S.totalPages * 90), `Analysing page ${i} of ${S.totalPages}…`);

            const hasA = i <= nA, hasB = i <= nB;

            // 1. Extract text with positions
            const getItems = async (pdf, n) => {
                if (S.ocrEnabled && S.TesseractLib) return extractTextOCR(pdf, n);
                return extractTextItems(pdf, n);
            };
            const { items: itemsA, fullText: textA } = hasA ? await getItems(S.pdfA, i) : { items: [], fullText: '' };
            const { items: itemsB, fullText: textB } = hasB ? await getItems(S.pdfB, i) : { items: [], fullText: '' };

            // 2. Text diff
            const { html: diffHtml, added: addedWords, removed: removedWords } = buildTextDiff(textA, textB);

            // 3. Text highlight positions (green/red rectangles on the PDF)
            const { hlA, hlB } = computeTextHighlights(textA, itemsA, textB, itemsB);

            // 4. Render pages (clean imageData for pixelmatch)
            const imgA = hasA ? await renderPageToData(S.pdfA, i) : null;
            const imgB = hasB ? await renderPageToData(S.pdfB, i) : null;

            // 5. Visual diff via worker
            let status = 'unchanged', diffRatio = 0, diffCanvas = null;
            if (!hasA) { status = 'added'; }
            else if (!hasB) { status = 'removed'; }
            else {
                const vd = await runVisualDiff(imgA, imgB, S.threshold);
                diffRatio = vd.ratio; diffCanvas = vd.diffCanvas;
                if (diffRatio > 0.001 || addedWords > 0 || removedWords > 0) status = 'changed';
            }

            S.results.push({
                i, status, diffRatio, addedWords, removedWords,
                diffHtml, textA, textB,
                imgA, imgB, diffCanvas,
                hlA, hlB,   // ← text highlight regions
            });
        }

        if (!S.cancelled) { setProgress(100, 'Done!'); renderResults(); }
    } catch (err) {
        setProgress(0, 'Error: ' + err.message); console.error(err);
        $('cmp-upload-section').classList.remove('d-none');
    } finally {
        $('cmp-progress-area').classList.add('d-none');
        $('cmp-compare-btn').disabled = false;
    }
}

// ── Render results ────────────────────────────────────────────────────────────
function renderResults() {
    updateSidebarStats();
    renderPagesList();
    renderContinuousViewer();
    renderThumbs();
    renderTextDiffPage(1);
    renderOverlayPage(1);
    renderReport();
    $('cmp-results-area').classList.remove('d-none');
    activateSidebarTab('pages');
}

// ── Stats ─────────────────────────────────────────────────────────────────────
function updateSidebarStats() {
    $('cmp-stat-total').textContent     = S.results.length;
    $('cmp-stat-changed').textContent   = S.results.filter(r => r.status === 'changed').length;
    $('cmp-stat-unchanged').textContent = S.results.filter(r => r.status === 'unchanged').length;
    $('cmp-stat-added').textContent     = S.results.filter(r => r.status === 'added').length;
    $('cmp-stat-removed').textContent   = S.results.filter(r => r.status === 'removed').length;
}

function statusBadgeClass(s) {
    return s === 'changed' ? 'bg-danger' : s === 'unchanged' ? 'bg-success' : s === 'added' ? 'bg-primary' : 'bg-warning text-dark';
}

// ── Pages list ────────────────────────────────────────────────────────────────
function renderPagesList() {
    const ul = $('cmp-pages-list'); ul.innerHTML = '';
    S.results.forEach(r => {
        const li = document.createElement('li');
        li.className = 'list-group-item list-group-item-action py-1 px-2 d-flex justify-content-between align-items-center';
        li.style.cursor = 'pointer';
        if (r.status === 'changed') li.classList.add('active');
        li.innerHTML = `<span>Page ${r.i}</span><span class="badge ${statusBadgeClass(r.status)}" style="font-size:.65rem">${r.status}</span>`;
        li.addEventListener('click', () => scrollToPage(r.i));
        ul.appendChild(li);
    });
}

// ── Continuous viewer ─────────────────────────────────────────────────────────
let _syncing = false;

function renderContinuousViewer() {
    const left = $('cmp-panel-left'), right = $('cmp-panel-right');
    left.innerHTML = ''; right.innerHTML = '';

    S.results.forEach(r => {
        const bL = makePageBlock(r.i), bR = makePageBlock(r.i);
        left.appendChild(bL); right.appendChild(bR);

        if (r.imgA) drawPageBlock(bL, r.imgA, r.diffCanvas, r.hlA, 'rgba(220,60,60,0.32)');
        else bL.appendChild(makePlaceholder('No page'));

        if (r.imgB) drawPageBlock(bR, r.imgB, null, r.hlB, 'rgba(34,197,94,0.32)');
        else bR.appendChild(makePlaceholder('No page'));
    });

    setupSyncScroll();
    setupIntersectionObserver();
    updateChangedOnlyFilter();
}

function makePageBlock(pg) {
    const wrap = document.createElement('div');
    wrap.className = 'cmp-page-block'; wrap.dataset.page = pg;
    const lbl = document.createElement('div');
    lbl.className = 'cmp-page-lbl'; lbl.textContent = 'Page ' + pg;
    wrap.appendChild(lbl); return wrap;
}

function drawPageBlock(block, imgData, diffCanvas, textHighlights, hlColor) {
    const w = Math.round(imgData.width * S.zoom);
    const h = Math.round(imgData.height * S.zoom);
    block.style.width = w + 'px';

    // Main page canvas
    const canvas = document.createElement('canvas');
    canvas.width = imgData.width; canvas.height = imgData.height;
    canvas.style.cssText = `width:${w}px;height:${h}px;display:block`;
    canvas.getContext('2d').putImageData(imgData.imageData, 0, 0);
    block.appendChild(canvas);

    // Text highlight overlay (green/red rectangles on changed words)
    if (S.showTextHighlights && textHighlights && textHighlights.length > 0) {
        const hlCanvas = document.createElement('canvas');
        hlCanvas.width = imgData.width; hlCanvas.height = imgData.height;
        hlCanvas.style.cssText = `position:absolute;top:0;left:0;width:${w}px;height:${h}px;pointer-events:none`;
        const ctx = hlCanvas.getContext('2d');
        ctx.fillStyle = hlColor;
        for (const hl of textHighlights) {
            ctx.fillRect(hl.x, hl.y, hl.w, hl.h);
        }
        block.appendChild(hlCanvas);
    }

    // Pixelmatch overlay (red diff pixels)
    if (S.showDiffOverlay && diffCanvas) {
        const ov = document.createElement('canvas');
        ov.className = 'cmp-diff-overlay';
        ov.width = diffCanvas.width; ov.height = diffCanvas.height;
        ov.getContext('2d').drawImage(diffCanvas, 0, 0);
        block.appendChild(ov);
    }
}

function makePlaceholder(text) {
    const d = document.createElement('div'); d.className = 'cmp-page-placeholder'; d.textContent = text; return d;
}

// ── Sync scroll ───────────────────────────────────────────────────────────────
function setupSyncScroll() {
    const L = $('cmp-panel-left'), R = $('cmp-panel-right');
    const sync = (src, tgt) => {
        if (!S.syncScroll || _syncing) return;
        _syncing = true;
        const srcMax = src.scrollHeight - src.clientHeight;
        const tgtMax = tgt.scrollHeight - tgt.clientHeight;
        if (srcMax > 0) tgt.scrollTop = (src.scrollTop / srcMax) * Math.max(0, tgtMax);
        _syncing = false;
    };
    L.addEventListener('scroll', () => sync(L, R), { passive: true });
    R.addEventListener('scroll', () => sync(R, L), { passive: true });
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
        b.textContent = r.status; b.className = 'badge ms-1 ' + statusBadgeClass(r.status); b.style.fontSize = '.7rem';
    }
    document.querySelectorAll('#cmp-pages-list li').forEach((li, idx) => li.classList.toggle('fw-bold', idx === S.currentPage - 1));
    document.querySelectorAll('#cmp-thumbs-strip .cmp-thumb').forEach((t, idx) => t.classList.toggle('cmp-thumb-active', idx === S.currentPage - 1));
}

function scrollToPage(pg) {
    const block = $('cmp-panel-left').querySelector('[data-page="' + pg + '"]');
    if (block) block.scrollIntoView({ block: 'start' });
    S.currentPage = pg; refreshPageIndicator();
}

// ── Toolbar ───────────────────────────────────────────────────────────────────
$('cmp-zoom-in').addEventListener('click', () => applyZoom(0.25));
$('cmp-zoom-out').addEventListener('click', () => applyZoom(-0.25));
$('cmp-fit-width').addEventListener('click', () => {
    const first = S.results.find(r => r.imgA || r.imgB);
    if (!first) return;
    const srcW = (first.imgA || first.imgB).width;
    S.zoom = Math.max(0.25, Math.min(3, ($('cmp-panel-left').clientWidth - 32) / srcW));
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
$('cmp-show-text-hl').addEventListener('change', e => {
    S.showTextHighlights = e.target.checked;
    if (S.results.length) renderContinuousViewer();
});
$('cmp-changed-only').addEventListener('change', e => { S.changedOnly = e.target.checked; updateChangedOnlyFilter(); });

function updateChangedOnlyFilter() {
    const L = $('cmp-panel-left'), R = $('cmp-panel-right');
    S.results.forEach(r => {
        const hide = S.changedOnly && r.status === 'unchanged';
        const bL = L.querySelector('[data-page="' + r.i + '"]');
        const bR = R.querySelector('[data-page="' + r.i + '"]');
        if (bL) bL.style.display = hide ? 'none' : '';
        if (bR) bR.style.display = hide ? 'none' : '';
    });
}

$('cmp-prev-page').addEventListener('click', () => { if (S.currentPage > 1) scrollToPage(S.currentPage - 1); });
$('cmp-next-page').addEventListener('click', () => { if (S.currentPage < S.totalPages) scrollToPage(S.currentPage + 1); });
$('cmp-next-diff').addEventListener('click', () => {
    for (let i = S.currentPage; i < S.results.length; i++) { if (S.results[i].status === 'changed') { scrollToPage(i + 1); return; } }
    for (let i = 0; i < S.currentPage - 1; i++) { if (S.results[i].status === 'changed') { scrollToPage(i + 1); return; } }
});

// ── Thumbnails ────────────────────────────────────────────────────────────────
function renderThumbs() {
    const strip = $('cmp-thumbs-strip'); strip.innerHTML = '';
    const colors = { changed: '#ef4444', unchanged: '#22c55e', added: '#3b82f6', removed: '#f59e0b' };
    S.results.forEach(r => {
        const thumb = document.createElement('div');
        thumb.className = 'cmp-thumb'; thumb.style.borderColor = colors[r.status] || '#e5e7eb';
        const src = r.imgA || r.imgB;
        if (src) {
            const tmp = document.createElement('canvas'); tmp.width = src.width; tmp.height = src.height;
            tmp.getContext('2d').putImageData(src.imageData, 0, 0);
            const img = new Image(); img.src = tmp.toDataURL('image/jpeg', 0.4); thumb.appendChild(img);
        }
        const lbl = document.createElement('div'); lbl.className = 'cmp-thumb-lbl'; lbl.textContent = r.i;
        lbl.style.color = colors[r.status] || '#6b7280'; thumb.appendChild(lbl);
        thumb.addEventListener('click', () => scrollToPage(r.i));
        if (r.i === S.currentPage) thumb.classList.add('cmp-thumb-active');
        strip.appendChild(thumb);
    });
}

// ── Sidebar tabs ──────────────────────────────────────────────────────────────
function activateSidebarTab(name) {
    document.querySelectorAll('.cmp-stab').forEach(b => b.classList.toggle('active', b.dataset.stab === name));
    ['pages','text','overlay','report'].forEach(t => {
        const el = $('cmp-stab-' + t);
        if (el) el.classList.toggle('d-none', t !== name);
    });
}
document.querySelectorAll('.cmp-stab').forEach(btn => btn.addEventListener('click', () => activateSidebarTab(btn.dataset.stab)));

// ── Text diff panel ───────────────────────────────────────────────────────────
function renderTextDiffPage(pg) {
    S.textPage = pg; $('cmp-text-page-cur').textContent = pg;
    const r = S.results[pg - 1];
    $('cmp-text-status').textContent = r ? r.status : '—';
    $('cmp-text-status').className = 'badge ms-1 ' + (r ? statusBadgeClass(r.status) : 'bg-secondary');
    const content = $('cmp-text-diff-content');
    if (!r || (!r.textA && !r.textB)) { content.innerHTML = '<p class="text-muted small">No text found. Try enabling OCR.</p>'; return; }
    content.innerHTML = r.diffHtml;
}
$('cmp-text-prev').addEventListener('click', () => { if (S.textPage > 1) renderTextDiffPage(S.textPage - 1); });
$('cmp-text-next').addEventListener('click', () => { if (S.textPage < S.totalPages) renderTextDiffPage(S.textPage + 1); });

// ── Overlay panel ─────────────────────────────────────────────────────────────
function renderOverlayPage(pg) {
    S.overlayPage = pg; $('cmp-overlay-page-cur').textContent = pg;
    const r = S.results[pg - 1]; const container = $('cmp-overlay-container'); container.innerHTML = '';
    if (!r || (!r.imgA && !r.imgB)) { container.textContent = 'No data.'; return; }
    const w = Math.max(r.imgA ? r.imgA.width : 0, r.imgB ? r.imgB.width : 0);
    const h = Math.max(r.imgA ? r.imgA.height : 0, r.imgB ? r.imgB.height : 0);
    const canvas = document.createElement('canvas'); canvas.width = w; canvas.height = h; canvas.style.maxWidth = '100%';
    container.appendChild(canvas); drawOverlay(canvas, r);
}
function drawOverlay(canvas, r) {
    const ctx = canvas.getContext('2d'); ctx.clearRect(0, 0, canvas.width, canvas.height);
    const draw = (imgData, opacity, blend) => {
        if (!imgData) return;
        const tmp = document.createElement('canvas'); tmp.width = imgData.width; tmp.height = imgData.height;
        tmp.getContext('2d').putImageData(imgData.imageData, 0, 0);
        ctx.save(); ctx.globalAlpha = opacity; ctx.globalCompositeOperation = blend;
        ctx.drawImage(tmp, 0, 0, canvas.width, canvas.height); ctx.restore();
    };
    draw(r.imgA, parseFloat($('cmp-opacity-a').value), 'source-over');
    draw(r.imgB, parseFloat($('cmp-opacity-b').value), $('cmp-blend-mode').value);
}
$('cmp-overlay-prev').addEventListener('click', () => { if (S.overlayPage > 1) renderOverlayPage(S.overlayPage - 1); });
$('cmp-overlay-next').addEventListener('click', () => { if (S.overlayPage < S.totalPages) renderOverlayPage(S.overlayPage + 1); });
['cmp-opacity-a','cmp-opacity-b','cmp-blend-mode'].forEach(id => {
    $(id).addEventListener('input', () => {
        const r = S.results[S.overlayPage - 1], c = $('cmp-overlay-container').querySelector('canvas');
        if (r && c) drawOverlay(c, r);
    });
});

// ── Report ────────────────────────────────────────────────────────────────────
function renderReport() {
    const total = S.results.length;
    const changed = S.results.filter(r => r.status === 'changed').length;
    const unchanged = S.results.filter(r => r.status === 'unchanged').length;
    const other = total - changed - unchanged;
    $('cmp-report-summary').innerHTML = `
        <div class="row g-2 mb-2">
            <div class="col-6"><div class="cmp-stat-box"><div class="cmp-stat-num">${total}</div>Total</div></div>
            <div class="col-6"><div class="cmp-stat-box cmp-stat-changed"><div class="cmp-stat-num text-danger">${changed}</div>Changed</div></div>
            <div class="col-6"><div class="cmp-stat-box cmp-stat-ok"><div class="cmp-stat-num text-success">${unchanged}</div>Unchanged</div></div>
            <div class="col-6"><div class="cmp-stat-box"><div class="cmp-stat-num text-primary">${other}</div>Added/Rmvd</div></div>
        </div>`;
    const tbody = $('cmp-report-tbody'); tbody.innerHTML = '';
    S.results.forEach(r => {
        const tr = document.createElement('tr'); tr.style.cursor = 'pointer';
        tr.innerHTML = `<td>${r.i}</td><td><span class="badge ${statusBadgeClass(r.status)}" style="font-size:.65rem">${r.status}</span></td><td>${(r.diffRatio*100).toFixed(1)}%</td><td class="text-success">${r.addedWords}</td><td class="text-danger">${r.removedWords}</td>`;
        tr.addEventListener('click', () => { activateSidebarTab('pages'); scrollToPage(r.i); });
        tbody.appendChild(tr);
    });
}

// ── Export ────────────────────────────────────────────────────────────────────
$('cmp-export-json').addEventListener('click', () => {
    const blob = new Blob([JSON.stringify({ originalFile: S.fileA?.name, modifiedFile: S.fileB?.name, pages: S.results.map(r => ({ page: r.i, status: r.status, diffRatio: +(r.diffRatio*100).toFixed(2), addedWords: r.addedWords, removedWords: r.removedWords })) }, null, 2)], { type: 'application/json' });
    Object.assign(document.createElement('a'), { href: URL.createObjectURL(blob), download: 'pdf-comparison.json' }).click();
});
$('cmp-export-html').addEventListener('click', () => {
    const rows = S.results.map(r => `<tr><td>${r.i}</td><td>${r.status}</td><td>${(r.diffRatio*100).toFixed(2)}%</td><td>${r.addedWords}</td><td>${r.removedWords}</td></tr>`).join('');
    const html = `<!DOCTYPE html><html><head><meta charset="UTF-8"><title>PDF Comparison Report</title><style>body{font-family:sans-serif;padding:20px}table{border-collapse:collapse;width:100%}th,td{border:1px solid #ddd;padding:8px}th{background:#f3f4f6}</style></head><body><h1>PDF Comparison Report</h1><p>Original: ${S.fileA?.name} | Modified: ${S.fileB?.name}</p><table><thead><tr><th>Page</th><th>Status</th><th>Diff%</th><th>+Words</th><th>-Words</th></tr></thead><tbody>${rows}</tbody></table></body></html>`;
    Object.assign(document.createElement('a'), { href: URL.createObjectURL(new Blob([html], { type: 'text/html' })), download: 'pdf-comparison.html' }).click();
});

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
        const total = divider.parentElement.offsetWidth - divider.offsetWidth - document.querySelector('.cmp-right-sidebar').offsetWidth;
        const newW = Math.max(80, Math.min(total - 80, startW + e.clientX - startX));
        const L = $('cmp-panel-left'); L.style.flex = 'none'; L.style.width = newW + 'px';
        $('cmp-panel-right').style.flex = '1';
    });
    document.addEventListener('mouseup', () => { dragging = false; document.body.style.cursor = ''; });
})();
