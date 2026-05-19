// ═══════════════════════════════════════════════════════════════════════════
//  PDF Editor
// ═══════════════════════════════════════════════════════════════════════════
const { PDFDocument, rgb, StandardFonts, degrees } = PDFLib;

// ── State ────────────────────────────────────────────────────────────────────
const S = {
    file: null, pdfDoc: null, originalBytes: null,
    totalPages: 0, currentPage: 1,
    zoom: 1.0, renderScale: 1.5,
    pageViewports: [], pageCanvases: [], pageTextItems: [],
    items: [], selectedItemId: null,
    currentTool: 'select',
    undoStack: [], redoStack: [],
    nextId: 1,
    interaction: null,
};

const $ = id => document.getElementById(id);
const fmtBytes = b => b < 1048576 ? (b/1024).toFixed(1)+' KB' : (b/1048576).toFixed(1)+' MB';

// ── Upload ───────────────────────────────────────────────────────────────────
const zone  = $('ed-drop-zone');
const input = $('ed-file-input');
zone.addEventListener('click', () => input.click());
zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('ed-drag-over'); });
zone.addEventListener('dragleave', () => zone.classList.remove('ed-drag-over'));
zone.addEventListener('drop', e => { e.preventDefault(); zone.classList.remove('ed-drag-over'); e.dataTransfer.files[0] && loadFile(e.dataTransfer.files[0]); });
input.addEventListener('change', e => e.target.files[0] && loadFile(e.target.files[0]));

async function loadFile(file) {
    if (file.type !== 'application/pdf') { alert('Selecione um PDF válido.'); return; }
    S.file = file;
    S.originalBytes = await file.arrayBuffer();
    try {
        S.pdfDoc = await pdfjsLib.getDocument({ data: new Uint8Array(S.originalBytes.slice(0)) }).promise;
        S.totalPages = S.pdfDoc.numPages;
        S.currentPage = 1;
        $('ed-file-name').textContent = file.name;
        $('ed-file-size').textContent = fmtBytes(file.size);
        $('ed-page-total').textContent = S.totalPages;
        $('ed-upload-section').classList.add('d-none');
        $('ed-hero').classList.add('d-none');
        $('ed-editor').classList.remove('d-none');
        await renderAllPages();
        renderThumbs();
        scrollToPage(1);
    } catch (err) { console.error(err); alert('Erro ao abrir o PDF: ' + err.message); }
}

// ── Page rendering ───────────────────────────────────────────────────────────
async function renderAllPages() {
    const wrap = $('ed-pages-wrap');
    wrap.innerHTML = '';
    S.pageViewports = []; S.pageCanvases = []; S.pageTextItems = [];

    for (let i = 1; i <= S.totalPages; i++) {
        const page = await S.pdfDoc.getPage(i);
        const vp = page.getViewport({ scale: S.renderScale });
        S.pageViewports[i - 1] = vp;

        const canvas = document.createElement('canvas');
        canvas.width = vp.width; canvas.height = vp.height;
        await page.render({ canvasContext: canvas.getContext('2d'), viewport: vp }).promise;
        S.pageCanvases[i - 1] = canvas;

        // Extract text items with canvas-space bounding boxes
        const tc = await page.getTextContent();
        const textItems = [];
        for (const it of tc.items) {
            if (!it.str || !it.str.trim()) continue;
            const [cx, cy] = pdfjsLib.Util.applyTransform([it.transform[4], it.transform[5]], vp.transform);
            const fontSize = Math.hypot(it.transform[0], it.transform[1]) * S.renderScale;
            const itemW    = Math.abs(it.width  || 0) * S.renderScale;
            // Widen hit area — some items have near-zero reported width
            textItems.push({
                str: it.str,
                x: cx - 2, y: cy - fontSize - 2,
                w: Math.max(itemW, fontSize * it.str.length * 0.55) + 4,
                h: fontSize * 1.4,
                fontSize, pdfFontSize: Math.hypot(it.transform[0], it.transform[1]),
                pdfX: it.transform[4], pdfY: it.transform[5],
            });
        }
        S.pageTextItems[i - 1] = textItems;

        // DOM
        const pageWrap = document.createElement('div');
        pageWrap.className = 'ed-page-wrap'; pageWrap.dataset.page = i;
        pageWrap.style.width  = (vp.width  * S.zoom) + 'px';
        pageWrap.style.height = (vp.height * S.zoom) + 'px';

        const lbl = document.createElement('div');
        lbl.className = 'ed-page-lbl'; lbl.textContent = 'Página ' + i;

        canvas.style.cssText = 'display:block;width:100%;height:100%;user-select:none';

        const overlay = document.createElement('div');
        overlay.className = 'ed-overlay'; overlay.dataset.page = i;

        pageWrap.appendChild(lbl);
        pageWrap.appendChild(canvas);
        pageWrap.appendChild(overlay);
        wrap.appendChild(pageWrap);
        attachOverlayEvents(overlay, i);
    }
    applyZoom();
}

function applyZoom() {
    document.querySelectorAll('.ed-page-wrap').forEach((el, idx) => {
        const vp = S.pageViewports[idx];
        el.style.width  = (vp.width  * S.zoom) + 'px';
        el.style.height = (vp.height * S.zoom) + 'px';
    });
    // Update font sizes of all rendered text items
    document.querySelectorAll('.ed-item-text').forEach(el => {
        const id = +el.closest('.ed-item').dataset.id;
        const it = getItem(id);
        if (it) el.style.fontSize = (it.fontSize * S.zoom) + 'px';
    });
    $('ed-zoom-label').textContent = Math.round(S.zoom * 100) + '%';
}

// ── Thumbnails ───────────────────────────────────────────────────────────────
function renderThumbs() {
    const strip = $('ed-thumbs'); strip.innerHTML = '';
    for (let i = 1; i <= S.totalPages; i++) {
        const canvas = S.pageCanvases[i - 1];
        const t = document.createElement('div');
        t.className = 'ed-thumb'; t.dataset.page = i;
        const ratio = 64 / canvas.height;
        const tmp = document.createElement('canvas');
        tmp.width = canvas.width * ratio; tmp.height = 64;
        tmp.getContext('2d').drawImage(canvas, 0, 0, tmp.width, tmp.height);
        const img = document.createElement('img'); img.src = tmp.toDataURL('image/jpeg', 0.6);
        const lbl = document.createElement('div'); lbl.className = 'ed-thumb-lbl'; lbl.textContent = i;
        t.appendChild(img); t.appendChild(lbl);
        t.addEventListener('click', () => scrollToPage(i));
        strip.appendChild(t);
    }
}

function scrollToPage(pg) {
    S.currentPage = pg;
    $('ed-page-cur').textContent = pg;
    document.querySelector(`.ed-page-wrap[data-page="${pg}"]`)?.scrollIntoView({ block: 'start', behavior: 'smooth' });
    document.querySelectorAll('#ed-thumbs .ed-thumb').forEach(t =>
        t.classList.toggle('ed-thumb-active', +t.dataset.page === pg));
}

// ── Tool selection ───────────────────────────────────────────────────────────
document.querySelectorAll('.ed-tool-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.ed-tool-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        S.currentTool = btn.dataset.tool;
        selectItem(null);
        updateCursor();
        if (S.currentTool === 'image') $('ed-image-input').click();
    });
});
function updateCursor() {
    const c = S.currentTool === 'select' ? 'default' : 'crosshair';
    document.querySelectorAll('.ed-overlay').forEach(o => o.style.cursor = c);
}

// ── History ──────────────────────────────────────────────────────────────────
function pushHistory() {
    S.undoStack.push(JSON.parse(JSON.stringify(S.items)));
    if (S.undoStack.length > 50) S.undoStack.shift();
    S.redoStack = [];
}
function undo() {
    if (!S.undoStack.length) return;
    S.redoStack.push(JSON.parse(JSON.stringify(S.items)));
    S.items = S.undoStack.pop();
    rebuildAllOverlays(); selectItem(null);
}
function redo() {
    if (!S.redoStack.length) return;
    S.undoStack.push(JSON.parse(JSON.stringify(S.items)));
    S.items = S.redoStack.pop();
    rebuildAllOverlays(); selectItem(null);
}

// ── Items CRUD ───────────────────────────────────────────────────────────────
function addItem(item) {
    pushHistory();
    item.id = S.nextId++;
    S.items.push(item);
    renderItem(item);
    selectItem(item.id);
    return item;
}
function deleteItem(id) {
    pushHistory();
    S.items = S.items.filter(it => it.id !== id);
    document.querySelector(`.ed-item[data-id="${id}"]`)?.remove();
    selectItem(null);
}
function getItem(id) { return S.items.find(it => it.id === id); }

function selectItem(id) {
    S.selectedItemId = id;
    document.querySelectorAll('.ed-item').forEach(el =>
        el.classList.toggle('selected', +el.dataset.id === id));
    refreshPropertiesPanel();
}
function rebuildAllOverlays() {
    document.querySelectorAll('.ed-overlay').forEach(o => o.innerHTML = '');
    S.items.forEach(renderItem);
}

// ── Item rendering ───────────────────────────────────────────────────────────
// renderItem: create or update an item's DOM element.
// IMPORTANT: never resets innerHTML on existing elements — prevents destroying
// contenteditable focus during drag/resize.
function renderItem(it) {
    const vp = S.pageViewports[it.page - 1];
    const overlay = document.querySelector(`.ed-overlay[data-page="${it.page}"]`);
    if (!overlay || !vp) return;

    let el = overlay.querySelector(`.ed-item[data-id="${it.id}"]`);
    if (!el) {
        el = buildItemEl(it, vp);
        overlay.appendChild(el);
    }

    // Layout — coordinates are stored in canvas px (at renderScale);
    // the overlay is 100% of the page wrap which is vp.width * zoom CSS px,
    // so percentage = canvas_px / vp.dimension * 100.
    el.dataset.type    = it.type;
    el.style.left      = (it.x / vp.width  * 100) + '%';
    el.style.top       = (it.y / vp.height * 100) + '%';
    el.style.width     = (it.w / vp.width  * 100) + '%';
    el.style.height    = (it.h / vp.height * 100) + '%';
    el.style.transform = `rotate(${it.rotation || 0}deg)`;
    el.style.opacity   = it.opacity ?? 1;

    // Visual style (non-destructive updates)
    applyItemStyle(it, el);

    if (S.selectedItemId === it.id) el.classList.add('selected');
    return el;
}

function buildItemEl(it, vp) {
    const el = document.createElement('div');
    el.className = 'ed-item'; el.dataset.id = it.id; el.dataset.type = it.type;

    if (it.type === 'text' || it.type === 'edit-text') {
        const inner = document.createElement('div');
        inner.className = 'ed-item-text';
        inner.contentEditable = 'true';
        inner.spellcheck = false;
        inner.textContent = it.text || '';
        inner.addEventListener('input',  () => { it.text = inner.textContent; });
        inner.addEventListener('focus',  () => selectItem(it.id));
        el.appendChild(inner);
    } else if (it.type === 'image') {
        const img = document.createElement('img');
        img.src = it.dataUrl;
        img.style.cssText = 'width:100%;height:100%;object-fit:fill;pointer-events:none;display:block';
        img.draggable = false;
        el.appendChild(img);
    } else if (it.type === 'signature') {
        el.appendChild(buildSignatureSvg(it));
    }

    // Resize handles
    ['nw','n','ne','e','se','s','sw','w'].forEach(dir => {
        const h = document.createElement('div');
        h.className = `ed-handle ed-handle-${dir}`; h.dataset.handle = dir;
        el.appendChild(h);
    });
    const rot = document.createElement('div');
    rot.className = 'ed-handle ed-handle-rot'; rot.dataset.handle = 'rot';
    el.appendChild(rot);

    // Delete button
    const del = document.createElement('button');
    del.className = 'ed-item-del'; del.title = 'Eliminar';
    del.innerHTML = '<i class="bi bi-trash"></i>';
    del.addEventListener('click', e => { e.stopPropagation(); deleteItem(it.id); });
    el.appendChild(del);

    attachItemEvents(el);
    return el;
}

function applyItemStyle(it, el) {
    if (it.type === 'text' || it.type === 'edit-text') {
        const inner = el.querySelector('.ed-item-text');
        if (!inner) return;
        inner.style.fontSize       = (it.fontSize * S.zoom) + 'px';
        inner.style.color          = it.color      || '#000000';
        inner.style.fontFamily     = it.fontFamily || 'Helvetica, Arial, sans-serif';
        inner.style.fontWeight     = it.bold       ? '700' : '400';
        inner.style.fontStyle      = it.italic     ? 'italic' : 'normal';
        inner.style.textDecoration = it.underline  ? 'underline' : 'none';
        inner.style.textAlign      = it.align      || 'left';
        // Only replace text if it changed externally (e.g. from properties panel)
        if (inner.textContent !== (it.text || '')) inner.textContent = it.text || '';
        el.style.background = ''; el.style.border = ''; el.style.borderRadius = '';
    } else if (it.type === 'rect') {
        el.style.background   = it.fill        || 'transparent';
        el.style.border       = `${it.strokeWidth || 2}px solid ${it.stroke || '#E5322D'}`;
        el.style.borderRadius = (it.radius || 0) + 'px';
    } else if (it.type === 'highlight') {
        el.style.background = it.color || 'rgba(255,235,59,0.45)';
        el.style.border = '';
    } else if (it.type === 'erase') {
        el.style.background = '#ffffff';
        el.style.border = '1px dashed rgba(0,0,0,.15)';
    }
}

function buildSignatureSvg(it) {
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('viewBox', `0 0 ${it.origW || it.w} ${it.origH || it.h}`);
    svg.setAttribute('preserveAspectRatio', 'none');
    svg.style.cssText = 'width:100%;height:100%;display:block';
    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    path.setAttribute('d', it.path); path.setAttribute('fill', 'none');
    path.setAttribute('stroke', it.color || '#000');
    path.setAttribute('stroke-width', it.strokeWidth || 2);
    path.setAttribute('stroke-linecap', 'round');
    path.setAttribute('stroke-linejoin', 'round');
    svg.appendChild(path);
    return svg;
}

// ── Interaction: drag / resize / rotate ──────────────────────────────────────
function attachItemEvents(el) {
    el.addEventListener('mousedown', e => {
        if (S.currentTool !== 'select') return;
        const id  = +el.dataset.id;
        const it  = getItem(id);
        if (!it) return;
        selectItem(id);

        const handle = e.target.dataset?.handle;
        const overlay = el.parentElement;
        // CSS px per canvas px (= zoom)
        const csx = overlay.offsetWidth  / S.pageViewports[it.page - 1].width;
        const csy = overlay.offsetHeight / S.pageViewports[it.page - 1].height;

        if (handle === 'rot') {
            const er = el.getBoundingClientRect();
            S.interaction = { type: 'rotate', itemId: id,
                cx: er.left + er.width / 2, cy: er.top + er.height / 2 };
        } else if (handle) {
            pushHistory();
            S.interaction = { type: 'resize', handle, itemId: id,
                startX: e.clientX, startY: e.clientY,
                origX: it.x, origY: it.y, origW: it.w, origH: it.h,
                csx, csy };
        } else if (!e.target.classList.contains('ed-item-text') &&
                   !e.target.classList.contains('ed-item-del')) {
            pushHistory();
            S.interaction = { type: 'drag', itemId: id,
                startX: e.clientX, startY: e.clientY,
                origX: it.x, origY: it.y, csx, csy };
        }
        e.stopPropagation(); e.preventDefault();
    });
}

document.addEventListener('mousemove', e => {
    if (!S.interaction) return;
    const it = getItem(S.interaction.itemId); if (!it) return;

    if (S.interaction.type === 'drag') {
        it.x = S.interaction.origX + (e.clientX - S.interaction.startX) / S.interaction.csx;
        it.y = S.interaction.origY + (e.clientY - S.interaction.startY) / S.interaction.csy;
        renderItem(it); refreshPropertiesPanel();
    } else if (S.interaction.type === 'resize') {
        const dx = (e.clientX - S.interaction.startX) / S.interaction.csx;
        const dy = (e.clientY - S.interaction.startY) / S.interaction.csy;
        const h  = S.interaction.handle;
        let { origX: x, origY: y, origW: w, origH: h2 } = S.interaction;
        if (h.includes('e')) w  = Math.max(10, w + dx);
        if (h.includes('s')) h2 = Math.max(10, h2 + dy);
        if (h.includes('w')) { x += dx; w  = Math.max(10, S.interaction.origW - dx); }
        if (h.includes('n')) { y += dy; h2 = Math.max(10, S.interaction.origH - dy); }
        it.x = x; it.y = y; it.w = w; it.h = h2;
        renderItem(it); refreshPropertiesPanel();
    } else if (S.interaction.type === 'rotate') {
        const ang = Math.atan2(e.clientY - S.interaction.cy, e.clientX - S.interaction.cx) * 180 / Math.PI + 90;
        it.rotation = Math.round(ang);
        renderItem(it); refreshPropertiesPanel();
    }
});
document.addEventListener('mouseup', () => { S.interaction = null; });

// ── Overlay events — add items ────────────────────────────────────────────────
function attachOverlayEvents(overlay, pageNum) {
    overlay.addEventListener('mousedown', e => {
        if (e.target !== overlay) return;
        const rect = overlay.getBoundingClientRect();
        // Canvas px coordinates
        const csx = S.pageViewports[pageNum - 1].width  / rect.width;
        const csy = S.pageViewports[pageNum - 1].height / rect.height;
        const x   = (e.clientX - rect.left) * csx;
        const y   = (e.clientY - rect.top)  * csy;

        const tool = S.currentTool;
        if (tool === 'text') {
            // fontSize stored in canvas px; 18pt * renderScale = 27 canvas px
            const fs = 27; // ≈ 18pt
            const item = addItem({
                page: pageNum, type: 'text',
                x, y, w: 250, h: fs * 1.6,
                text: 'Texto', fontSize: fs, color: '#000000',
                fontFamily: 'Helvetica, Arial, sans-serif',
                bold: false, italic: false, underline: false, align: 'left',
                rotation: 0, opacity: 1,
            });
            setTimeout(() => {
                const inner = document.querySelector(`.ed-item[data-id="${item.id}"] .ed-item-text`);
                if (inner) { inner.focus(); document.execCommand('selectAll', false, null); }
            }, 30);
            selectTool('select');

        } else if (tool === 'edit-text') {
            const hits = S.pageTextItems[pageNum - 1].filter(t =>
                x >= t.x && x <= t.x + t.w && y >= t.y && y <= t.y + t.h);
            if (hits.length) {
                // pick the smallest (most precise) hit
                const hit = hits.reduce((a, b) => a.w * a.h < b.w * b.h ? a : b);
                addItem({  // white cover over original
                    page: pageNum, type: 'erase',
                    x: hit.x - 1, y: hit.y - 1, w: hit.w + 2, h: hit.h + 2,
                    rotation: 0, opacity: 1,
                });
                const item = addItem({
                    page: pageNum, type: 'edit-text',
                    x: hit.x, y: hit.y,
                    w: Math.max(hit.w + 60, 120), h: hit.h + 2,
                    text: hit.str, fontSize: hit.fontSize, color: '#000000',
                    fontFamily: 'Helvetica, Arial, sans-serif',
                    bold: false, italic: false, underline: false, align: 'left',
                    rotation: 0, opacity: 1,
                });
                setTimeout(() => {
                    const inner = document.querySelector(`.ed-item[data-id="${item.id}"] .ed-item-text`);
                    if (inner) { inner.focus(); document.execCommand('selectAll', false, null); }
                }, 30);
                selectTool('select');
            } else {
                showToast('Clique sobre um texto existente para editar.');
            }

        } else if (tool === 'rect' || tool === 'highlight' || tool === 'erase') {
            startRubberBand(e, overlay, pageNum);
        } else if (tool === 'signature') {
            openSignatureDialog(pageNum, x, y);
        } else {
            selectItem(null);
        }
    });
}

function startRubberBand(e, overlay, pageNum) {
    const rect   = overlay.getBoundingClientRect();
    const csx    = S.pageViewports[pageNum - 1].width  / rect.width;
    const csy    = S.pageViewports[pageNum - 1].height / rect.height;
    const startX = e.clientX - rect.left;
    const startY = e.clientY - rect.top;

    const band = document.createElement('div');
    band.className = 'ed-rubber-band';
    band.style.left = startX + 'px'; band.style.top = startY + 'px';
    overlay.appendChild(band);
    const tool = S.currentTool;

    const move = ev => {
        const cx = ev.clientX - rect.left, cy = ev.clientY - rect.top;
        band.style.left   = Math.min(cx, startX) + 'px';
        band.style.top    = Math.min(cy, startY) + 'px';
        band.style.width  = Math.abs(cx - startX) + 'px';
        band.style.height = Math.abs(cy - startY) + 'px';
    };
    const up = ev => {
        document.removeEventListener('mousemove', move);
        document.removeEventListener('mouseup',   up);
        const cx = ev.clientX - rect.left, cy = ev.clientY - rect.top;
        const x  = Math.min(cx, startX) * csx, y  = Math.min(cy, startY) * csy;
        const w  = Math.abs(cx - startX) * csx, h  = Math.abs(cy - startY) * csy;
        band.remove();
        if (w < 5 || h < 5) return;
        const base = { page: pageNum, x, y, w, h, rotation: 0, opacity: 1 };
        if      (tool === 'rect')      addItem({ ...base, type: 'rect', stroke: '#E5322D', strokeWidth: 2, fill: 'transparent', radius: 0 });
        else if (tool === 'highlight') addItem({ ...base, type: 'highlight', color: 'rgba(255,235,59,0.45)' });
        else if (tool === 'erase')     addItem({ ...base, type: 'erase' });
        selectTool('select');
    };
    document.addEventListener('mousemove', move);
    document.addEventListener('mouseup',   up);
}

function selectTool(tool) {
    document.querySelectorAll('.ed-tool-btn').forEach(b => b.classList.toggle('active', b.dataset.tool === tool));
    S.currentTool = tool;
    updateCursor();
}

// ── Properties panel ─────────────────────────────────────────────────────────
function refreshPropertiesPanel() {
    const panel = $('ed-props-body');
    const it = S.selectedItemId ? getItem(S.selectedItemId) : null;
    if (!it) {
        panel.innerHTML = '<p class="text-muted small mb-0">Nenhum item selecionado.<br>Use as ferramentas à esquerda para adicionar conteúdo.</p>';
        return;
    }
    const isText = it.type === 'text' || it.type === 'edit-text';
    panel.innerHTML = `
        <div class="mb-2"><span class="badge bg-secondary">${it.type}</span></div>
        ${isText ? `
        <div class="mb-2">
            <label class="form-label small mb-1">Texto</label>
            <textarea class="form-control form-control-sm" id="pp-text" rows="2">${escapeHtml(it.text || '')}</textarea>
        </div>
        <div class="row g-2 mb-2">
            <div class="col-6">
                <label class="form-label small mb-1">Tamanho (pt)</label>
                <input type="number" class="form-control form-control-sm" id="pp-fontSize"
                    value="${Math.round(it.fontSize / S.renderScale)}" min="4" max="200">
            </div>
            <div class="col-6">
                <label class="form-label small mb-1">Cor</label>
                <input type="color" class="form-control form-control-color form-control-sm w-100" id="pp-color" value="${it.color || '#000000'}">
            </div>
        </div>
        <div class="mb-2">
            <label class="form-label small mb-1">Fonte</label>
            <select id="pp-font" class="form-select form-select-sm">
                <option value="Helvetica, Arial, sans-serif"       ${(it.fontFamily||'').includes('Helvetica') ? 'selected' : ''}>Helvetica</option>
                <option value="Times New Roman, Times, serif"      ${(it.fontFamily||'').includes('Times')     ? 'selected' : ''}>Times</option>
                <option value="Courier New, Courier, monospace"    ${(it.fontFamily||'').includes('Courier')   ? 'selected' : ''}>Courier</option>
            </select>
        </div>
        <div class="btn-group btn-group-sm mb-3 w-100">
            <button class="btn btn-outline-secondary ${it.bold?'active':''}"      id="pp-bold"><b>B</b></button>
            <button class="btn btn-outline-secondary ${it.italic?'active':''}"    id="pp-italic"><i>I</i></button>
            <button class="btn btn-outline-secondary ${it.underline?'active':''}" id="pp-underline"><u>U</u></button>
            <button class="btn btn-outline-secondary ${it.align==='left'?'active':''}"   id="pp-al"><i class="bi bi-text-left"></i></button>
            <button class="btn btn-outline-secondary ${it.align==='center'?'active':''}" id="pp-ac"><i class="bi bi-text-center"></i></button>
            <button class="btn btn-outline-secondary ${it.align==='right'?'active':''}"  id="pp-ar"><i class="bi bi-text-right"></i></button>
        </div>
        ` : ''}
        ${it.type === 'rect' ? `
        <div class="row g-2 mb-2">
            <div class="col-6"><label class="form-label small mb-1">Borda</label>
                <input type="color" class="form-control form-control-color form-control-sm w-100" id="pp-stroke" value="${it.stroke || '#E5322D'}"></div>
            <div class="col-6"><label class="form-label small mb-1">Preench.</label>
                <input type="color" class="form-control form-control-color form-control-sm w-100" id="pp-fill-color" value="${it.fill && it.fill !== 'transparent' ? it.fill : '#ffffff'}"></div>
        </div>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" id="pp-fill-on" ${it.fill && it.fill !== 'transparent' ? 'checked' : ''}>
            <label class="form-check-label small" for="pp-fill-on">Preenchimento</label>
        </div>
        <div class="row g-2 mb-2">
            <div class="col-6"><label class="form-label small mb-1">Espessura</label>
                <input type="number" class="form-control form-control-sm" id="pp-stroke-w" value="${it.strokeWidth || 2}" min="1" max="30"></div>
            <div class="col-6"><label class="form-label small mb-1">Raio</label>
                <input type="number" class="form-control form-control-sm" id="pp-radius" value="${it.radius || 0}" min="0" max="80"></div>
        </div>
        ` : ''}
        ${it.type === 'highlight' ? `
        <div class="mb-2"><label class="form-label small mb-1">Cor</label>
            <input type="color" class="form-control form-control-color form-control-sm w-100" id="pp-hl-color" value="#ffeb3b"></div>
        ` : ''}
        <hr class="my-2">
        <div class="row g-2 mb-2">
            <div class="col-3"><label class="form-label small mb-1">X</label><input type="number" class="form-control form-control-sm" id="pp-x" value="${Math.round(it.x / S.renderScale)}"></div>
            <div class="col-3"><label class="form-label small mb-1">Y</label><input type="number" class="form-control form-control-sm" id="pp-y" value="${Math.round(it.y / S.renderScale)}"></div>
            <div class="col-3"><label class="form-label small mb-1">L</label><input type="number" class="form-control form-control-sm" id="pp-w" value="${Math.round(it.w / S.renderScale)}"></div>
            <div class="col-3"><label class="form-label small mb-1">A</label><input type="number" class="form-control form-control-sm" id="pp-h" value="${Math.round(it.h / S.renderScale)}"></div>
        </div>
        <div class="row g-2 mb-3">
            <div class="col-6"><label class="form-label small mb-1">Rotação °</label>
                <input type="number" class="form-control form-control-sm" id="pp-rot" value="${it.rotation || 0}"></div>
            <div class="col-6"><label class="form-label small mb-1">Opacidade</label>
                <input type="range" class="form-range" id="pp-op" min="0" max="1" step="0.05" value="${it.opacity ?? 1}"></div>
        </div>
        <button class="btn btn-outline-danger btn-sm w-100" id="pp-delete"><i class="bi bi-trash me-1"></i>Eliminar</button>
    `;
    bindPropEvents(it);
}

function bindPropEvents(it) {
    const re = () => renderItem(it);
    const on = (id, fn) => { const el = $(id); if (el) el.addEventListener('input', fn); };

    on('pp-text',     () => { it.text = $('pp-text').value; re(); });
    // fontSize stored in canvas px; user types in pt
    on('pp-fontSize', () => { it.fontSize = (parseFloat($('pp-fontSize').value) || 12) * S.renderScale; re(); });
    on('pp-color',    () => { it.color = $('pp-color').value; re(); });
    on('pp-font',     () => { it.fontFamily = $('pp-font').value; re(); });
    on('pp-stroke',   () => { it.stroke = $('pp-stroke').value; re(); });
    on('pp-stroke-w', () => { it.strokeWidth = parseInt($('pp-stroke-w').value) || 1; re(); });
    on('pp-radius',   () => { it.radius = parseInt($('pp-radius').value) || 0; re(); });
    on('pp-fill-color', () => {
        if ($('pp-fill-on')?.checked) { it.fill = $('pp-fill-color').value; re(); }
    });
    const fillOn = $('pp-fill-on');
    if (fillOn) fillOn.addEventListener('change', () => {
        it.fill = fillOn.checked ? ($('pp-fill-color')?.value || '#ffffff') : 'transparent'; re();
    });
    on('pp-hl-color', () => {
        const hex = $('pp-hl-color').value;
        const r = parseInt(hex.slice(1,3),16), g = parseInt(hex.slice(3,5),16), b = parseInt(hex.slice(5,7),16);
        it.color = `rgba(${r},${g},${b},0.45)`; re();
    });
    // geometry — user types in pt, stored in canvas px
    on('pp-x',   () => { it.x = (parseFloat($('pp-x').value)   || 0) * S.renderScale; re(); });
    on('pp-y',   () => { it.y = (parseFloat($('pp-y').value)   || 0) * S.renderScale; re(); });
    on('pp-w',   () => { it.w = Math.max(5, (parseFloat($('pp-w').value) || 5) * S.renderScale); re(); });
    on('pp-h',   () => { it.h = Math.max(5, (parseFloat($('pp-h').value) || 5) * S.renderScale); re(); });
    on('pp-rot', () => { it.rotation = parseFloat($('pp-rot').value) || 0; re(); });
    on('pp-op',  () => { it.opacity  = parseFloat($('pp-op').value); re(); });

    ['bold','italic','underline'].forEach(p => {
        $('pp-' + p)?.addEventListener('click', () => { it[p] = !it[p]; re(); refreshPropertiesPanel(); });
    });
    [['pp-al','left'],['pp-ac','center'],['pp-ar','right']].forEach(([id, val]) => {
        $(id)?.addEventListener('click', () => { it.align = val; re(); refreshPropertiesPanel(); });
    });
    $('pp-delete')?.addEventListener('click', () => deleteItem(it.id));
}

function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

// ── Toast ────────────────────────────────────────────────────────────────────
function showToast(msg) {
    const t = $('ed-toast'); t.textContent = msg; t.classList.add('show');
    clearTimeout(t._t); t._t = setTimeout(() => t.classList.remove('show'), 2500);
}

// ── Image upload ─────────────────────────────────────────────────────────────
$('ed-image-input').addEventListener('change', async e => {
    const file = e.target.files[0]; if (!file) return;
    const dataUrl = await new Promise(res => { const r = new FileReader(); r.onload = () => res(r.result); r.readAsDataURL(file); });
    const img = new Image();
    img.onload = () => {
        const maxW = 300 * S.renderScale, maxH = 300 * S.renderScale;
        const ratio = Math.min(maxW / img.width, maxH / img.height, 1);
        const w = img.width * ratio, h = img.height * ratio;
        const vp = S.pageViewports[S.currentPage - 1];
        addItem({ page: S.currentPage, type: 'image',
            x: (vp.width  - w) / 2, y: (vp.height - h) / 2, w, h,
            dataUrl, mime: file.type, rotation: 0, opacity: 1 });
        selectTool('select');
    };
    img.src = dataUrl;
    e.target.value = '';
});

// ── Signature dialog ─────────────────────────────────────────────────────────
function openSignatureDialog(pageNum, atX, atY) {
    const modal  = $('ed-sig-modal');
    const canvas = $('ed-sig-canvas');
    const ctx    = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.lineCap = ctx.lineJoin = 'round';

    let drawing = false, pts = [], pathStr = '';

    const pos = ev => {
        const r = canvas.getBoundingClientRect(), t = ev.touches ? ev.touches[0] : ev;
        return { x: (t.clientX - r.left) * canvas.width / r.width, y: (t.clientY - r.top) * canvas.height / r.height };
    };
    const onStart = ev => {
        drawing = true; const p = pos(ev);
        pts = [p]; pathStr += (pathStr ? ' ' : '') + `M${p.x.toFixed(1)} ${p.y.toFixed(1)}`;
        ctx.beginPath(); ctx.moveTo(p.x, p.y);
        ctx.strokeStyle = $('ed-sig-color').value;
        ctx.lineWidth   = parseFloat($('ed-sig-width').value);
    };
    const onMove = ev => {
        if (!drawing) return;
        const p = pos(ev); pts.push(p);
        pathStr += ` L${p.x.toFixed(1)} ${p.y.toFixed(1)}`;
        ctx.lineTo(p.x, p.y); ctx.stroke(); ctx.beginPath(); ctx.moveTo(p.x, p.y);
    };
    const onEnd = () => { drawing = false; };
    canvas.onmousedown = onStart; canvas.onmousemove = onMove; canvas.onmouseup = canvas.onmouseleave = onEnd;
    canvas.ontouchstart = ev => { ev.preventDefault(); onStart(ev); };
    canvas.ontouchmove  = ev => { ev.preventDefault(); onMove(ev);  };
    canvas.ontouchend   = onEnd;

    $('ed-sig-clear').onclick = () => { ctx.clearRect(0, 0, canvas.width, canvas.height); pathStr = ''; pts = []; };
    $('ed-sig-color').oninput = ev => ctx.strokeStyle = ev.target.value;
    $('ed-sig-width').oninput = ev => ctx.lineWidth   = parseFloat(ev.target.value);

    $('ed-sig-apply').onclick = () => {
        if (!pathStr) { showToast('Desenhe a sua assinatura primeiro.'); return; }
        const xs = pts.map(p => p.x), ys = pts.map(p => p.y);
        const minX = Math.min(...xs), minY = Math.min(...ys);
        const origW = Math.max(...xs) - minX || 10, origH = Math.max(...ys) - minY || 10;
        // shift path to 0,0 origin
        const shiftedPath = pathStr.replace(/([ML])([\d.]+) ([\d.]+)/g, (_, c, x, y) =>
            `${c}${(parseFloat(x) - minX).toFixed(1)} ${(parseFloat(y) - minY).toFixed(1)}`);
        const scale = S.renderScale * (canvas.width / canvas.getBoundingClientRect().width);
        addItem({ page: pageNum, type: 'signature',
            x: atX, y: atY,
            w: origW / scale * S.renderScale,
            h: origH / scale * S.renderScale,
            path: shiftedPath, origW, origH,
            color: $('ed-sig-color').value,
            strokeWidth: parseFloat($('ed-sig-width').value),
            rotation: 0, opacity: 1 });
        modal.style.display = 'none';
        selectTool('select');
    };
    $('ed-sig-cancel').onclick = () => { modal.style.display = 'none'; selectTool('select'); };
    modal.style.display = 'flex';
}

// ── Toolbar ──────────────────────────────────────────────────────────────────
$('ed-zoom-in').addEventListener('click',  () => { S.zoom = Math.min(3, S.zoom + 0.25); applyZoom(); rebuildAllOverlays(); });
$('ed-zoom-out').addEventListener('click', () => { S.zoom = Math.max(0.25, S.zoom - 0.25); applyZoom(); rebuildAllOverlays(); });
$('ed-fit').addEventListener('click', () => {
    const vp = S.pageViewports[0]; if (!vp) return;
    S.zoom = Math.max(0.25, Math.min(3, ($('ed-canvas-area').clientWidth - 44) / vp.width));
    applyZoom(); rebuildAllOverlays();
});
$('ed-undo').addEventListener('click', undo);
$('ed-redo').addEventListener('click', redo);
$('ed-prev').addEventListener('click', () => S.currentPage > 1 && scrollToPage(S.currentPage - 1));
$('ed-next').addEventListener('click', () => S.currentPage < S.totalPages && scrollToPage(S.currentPage + 1));
$('ed-new').addEventListener('click',  () => location.reload());

// Keyboard shortcuts
document.addEventListener('keydown', e => {
    if (e.target.matches('input,textarea,[contenteditable="true"]')) return;
    if ((e.ctrlKey || e.metaKey) && e.key === 'z') { e.preventDefault(); undo(); }
    else if ((e.ctrlKey || e.metaKey) && e.key === 'y') { e.preventDefault(); redo(); }
    else if (e.key === 'Delete' || e.key === 'Backspace') { if (S.selectedItemId) { e.preventDefault(); deleteItem(S.selectedItemId); } }
    else if (e.key === 'Escape') { selectItem(null); selectTool('select'); }
});

// Scroll → current page indicator
$('ed-canvas-area').addEventListener('scroll', () => {
    const areaRect = $('ed-canvas-area').getBoundingClientRect();
    document.querySelectorAll('.ed-page-wrap').forEach(p => {
        const r = p.getBoundingClientRect();
        if (r.top - areaRect.top > -r.height * 0.5 && r.top - areaRect.top < areaRect.height * 0.5) {
            const pg = +p.dataset.page;
            if (pg !== S.currentPage) {
                S.currentPage = pg;
                $('ed-page-cur').textContent = pg;
                document.querySelectorAll('#ed-thumbs .ed-thumb').forEach(t =>
                    t.classList.toggle('ed-thumb-active', +t.dataset.page === pg));
            }
        }
    });
});

// ── Save ─────────────────────────────────────────────────────────────────────
$('ed-save').addEventListener('click', saveAsPdf);

async function saveAsPdf() {
    if (!S.originalBytes) return;
    $('ed-save').disabled = true;
    $('ed-save-spinner').classList.remove('d-none');
    try {
        const pdfLibDoc = await PDFDocument.load(S.originalBytes.slice(0));
        if (window.fontkit) pdfLibDoc.registerFontkit(window.fontkit);

        // Embed standard font variants
        const fonts = {
            helv:   await pdfLibDoc.embedFont(StandardFonts.Helvetica),
            helvB:  await pdfLibDoc.embedFont(StandardFonts.HelveticaBold),
            helvI:  await pdfLibDoc.embedFont(StandardFonts.HelveticaOblique),
            helvBI: await pdfLibDoc.embedFont(StandardFonts.HelveticaBoldOblique),
            times:   await pdfLibDoc.embedFont(StandardFonts.TimesRoman),
            timesB:  await pdfLibDoc.embedFont(StandardFonts.TimesRomanBold),
            timesI:  await pdfLibDoc.embedFont(StandardFonts.TimesRomanItalic),
            timesBI: await pdfLibDoc.embedFont(StandardFonts.TimesRomanBoldItalic),
            cour:   await pdfLibDoc.embedFont(StandardFonts.Courier),
            courB:  await pdfLibDoc.embedFont(StandardFonts.CourierBold),
            courI:  await pdfLibDoc.embedFont(StandardFonts.CourierOblique),
            courBI: await pdfLibDoc.embedFont(StandardFonts.CourierBoldOblique),
        };
        const pickFont = (family, bold, italic) => {
            const f = family || '';
            if (f.includes('Times'))   return bold && italic ? fonts.timesBI : bold ? fonts.timesB : italic ? fonts.timesI : fonts.times;
            if (f.includes('Courier')) return bold && italic ? fonts.courBI  : bold ? fonts.courB  : italic ? fonts.courI  : fonts.cour;
            return bold && italic ? fonts.helvBI : bold ? fonts.helvB : italic ? fonts.helvI : fonts.helv;
        };
        const hexRgb = hex => {
            if (!hex || hex[0] !== '#') return { r:0, g:0, b:0 };
            return { r: parseInt(hex.slice(1,3),16)/255, g: parseInt(hex.slice(3,5),16)/255, b: parseInt(hex.slice(5,7),16)/255 };
        };

        const pdfPages = pdfLibDoc.getPages();
        for (let p = 1; p <= S.totalPages; p++) {
            const page = pdfPages[p - 1];
            const { width: pdfW, height: pdfH } = page.getSize();
            const vp   = S.pageViewports[p - 1];
            // Canvas px → PDF pt conversion factors
            const sxPt = pdfW / vp.width;
            const syPt = pdfH / vp.height;

            // Draw erase items first (white covers go below text)
            const pageItems = S.items.filter(it => it.page === p)
                .sort((a, b) => (a.type === 'erase' ? -1 : 0) - (b.type === 'erase' ? -1 : 0));

            for (const it of pageItems) {
                const xPt   =  it.x * sxPt;
                const yTopPt = (vp.height - it.y) * syPt;   // PDF Y of item's top edge
                const wPt   =  it.w * sxPt;
                const hPt   =  it.h * syPt;
                const rot   = degrees(-(it.rotation || 0));
                const op    = it.opacity ?? 1;

                if (it.type === 'erase') {
                    page.drawRectangle({ x: xPt, y: yTopPt - hPt, width: wPt, height: hPt,
                        color: rgb(1,1,1), opacity: op });

                } else if (it.type === 'rect') {
                    const s = hexRgb(it.stroke || '#000');
                    const opts = { x: xPt, y: yTopPt - hPt, width: wPt, height: hPt,
                        borderColor: rgb(s.r,s.g,s.b),
                        borderWidth: (it.strokeWidth || 2) * sxPt,
                        opacity: op, rotate: rot };
                    if (it.fill && it.fill !== 'transparent') {
                        const f = hexRgb(it.fill); opts.color = rgb(f.r,f.g,f.b);
                    }
                    page.drawRectangle(opts);

                } else if (it.type === 'highlight') {
                    const m = (it.color||'').match(/rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*([\d.]+))?\)/);
                    if (m) page.drawRectangle({ x: xPt, y: yTopPt - hPt, width: wPt, height: hPt,
                        color: rgb(+m[1]/255, +m[2]/255, +m[3]/255),
                        opacity: parseFloat(m[4] ?? 0.45) * op });

                } else if (it.type === 'text' || it.type === 'edit-text') {
                    const c     = hexRgb(it.color || '#000');
                    const font  = pickFont(it.fontFamily, it.bold, it.italic);
                    // fontSize stored in canvas px → pt
                    const sizePt = it.fontSize * sxPt;
                    const lineH  = sizePt * 1.2;
                    const lines  = (it.text || '').split('\n');
                    for (let li = 0; li < lines.length; li++) {
                        const line = lines[li]; if (!line) continue;
                        const lineWPt = font.widthOfTextAtSize(line, sizePt);
                        let dx = 0;
                        if (it.align === 'center') dx = (wPt - lineWPt) / 2;
                        else if (it.align === 'right') dx = wPt - lineWPt;
                        const yLinePt = yTopPt - sizePt - li * lineH;
                        page.drawText(line, { x: xPt + dx, y: yLinePt, size: sizePt,
                            font, color: rgb(c.r,c.g,c.b), opacity: op, rotate: rot });
                        if (it.underline) {
                            page.drawLine({
                                start: { x: xPt + dx, y: yLinePt - 1.5 },
                                end:   { x: xPt + dx + lineWPt, y: yLinePt - 1.5 },
                                thickness: Math.max(0.4, sizePt * 0.05),
                                color: rgb(c.r,c.g,c.b), opacity: op });
                        }
                    }

                } else if (it.type === 'image') {
                    const bytes = dataUrlToBytes(it.dataUrl);
                    const mime  = it.mime || (it.dataUrl.startsWith('data:image/png') ? 'image/png' : 'image/jpeg');
                    const img   = mime.includes('png') ? await pdfLibDoc.embedPng(bytes) : await pdfLibDoc.embedJpg(bytes);
                    page.drawImage(img, { x: xPt, y: yTopPt - hPt, width: wPt, height: hPt,
                        opacity: op, rotate: rot });

                } else if (it.type === 'signature') {
                    const c = hexRgb(it.color || '#000');
                    // Scale signature path (origW/origH in canvas px on sig canvas)
                    // → fit into (wPt × hPt) PDF space
                    const sW = wPt / (it.origW || 1);
                    const sH = hPt / (it.origH || 1);
                    const tks = (it.path.match(/[ML][^ML]*/g) || []);
                    let prev = null;
                    for (const tk of tks) {
                        const m = tk.match(/([ML])\s*([\d.]+)\s+([\d.]+)/);
                        if (!m) continue;
                        const px = xPt    + parseFloat(m[2]) * sW;
                        const py = yTopPt - parseFloat(m[3]) * sH;
                        if (m[1] === 'M') { prev = { x: px, y: py }; }
                        else if (prev) {
                            page.drawLine({ start: prev, end: { x: px, y: py },
                                thickness: (it.strokeWidth || 2) * sxPt,
                                color: rgb(c.r,c.g,c.b), opacity: op });
                            prev = { x: px, y: py };
                        }
                    }
                }
            }
        }

        const bytes = await pdfLibDoc.save();
        const blob  = new Blob([bytes], { type: 'application/pdf' });
        const a     = document.createElement('a');
        a.href      = URL.createObjectURL(blob);
        a.download  = (S.file.name.replace(/\.pdf$/i, '') || 'document') + '_editado.pdf';
        document.body.appendChild(a); a.click(); document.body.removeChild(a);
        URL.revokeObjectURL(a.href);
        showToast('PDF guardado com sucesso!');
    } catch (err) {
        console.error(err); alert('Erro ao guardar PDF: ' + err.message);
    } finally {
        $('ed-save').disabled = false;
        $('ed-save-spinner').classList.add('d-none');
    }
}

function dataUrlToBytes(dataUrl) {
    const bin = atob(dataUrl.split(',')[1]);
    const out = new Uint8Array(bin.length);
    for (let i = 0; i < bin.length; i++) out[i] = bin.charCodeAt(i);
    return out;
}

refreshPropertiesPanel();
