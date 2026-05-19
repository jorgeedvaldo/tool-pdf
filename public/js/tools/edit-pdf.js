// ═══════════════════════════════════════════════════════════════════════════
//  PDF Editor — Zendocs/Adobe Acrobat style
//  Tools: select, text, edit-text, rect, highlight, image, signature, erase
//  Save: pdf-lib + @pdf-lib/fontkit
// ═══════════════════════════════════════════════════════════════════════════

const { PDFDocument, rgb, StandardFonts, degrees } = PDFLib;

// ── State ────────────────────────────────────────────────────────────────────
const S = {
    file: null,
    pdfDoc: null,          // pdf.js doc
    originalBytes: null,   // ArrayBuffer for pdf-lib later
    totalPages: 0,
    currentPage: 1,
    zoom: 1.0,
    renderScale: 1.5,
    pageViewports: [],     // viewport per page (at renderScale)
    pageCanvases: [],      // rendered canvases per page
    pageTextItems: [],     // text items per page (for edit-text tool)
    items: [],             // editable overlay items (across all pages)
    selectedItemId: null,
    currentTool: 'select',
    undoStack: [],
    redoStack: [],
    nextId: 1,
    // drawing
    drawing: false,
    drawStart: null,
    drawTempEl: null,
    // signature/freehand
    penPath: [],
    // resize/drag
    interaction: null,     // { type, itemId, startX, startY, origX, origY, origW, origH, handle }
};

const $ = id => document.getElementById(id);
const fmtBytes = b => b < 1048576 ? (b / 1024).toFixed(1) + ' KB' : (b / 1048576).toFixed(1) + ' MB';

// ── Upload ───────────────────────────────────────────────────────────────────
function setupUpload() {
    const zone = $('ed-drop-zone');
    const input = $('ed-file-input');
    zone.addEventListener('click', () => input.click());
    zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('ed-drag-over'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('ed-drag-over'));
    zone.addEventListener('drop', e => {
        e.preventDefault(); zone.classList.remove('ed-drag-over');
        if (e.dataTransfer.files[0]) loadFile(e.dataTransfer.files[0]);
    });
    input.addEventListener('change', e => e.target.files[0] && loadFile(e.target.files[0]));
}
setupUpload();

async function loadFile(file) {
    if (file.type !== 'application/pdf') { alert('Por favor selecione um PDF válido.'); return; }
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
    } catch (err) {
        console.error(err);
        alert('Erro ao abrir o PDF: ' + err.message);
    }
}

// ── Page rendering ───────────────────────────────────────────────────────────
async function renderAllPages() {
    const wrap = $('ed-pages-wrap');
    wrap.innerHTML = '';
    S.pageViewports = [];
    S.pageCanvases = [];
    S.pageTextItems = [];

    for (let i = 1; i <= S.totalPages; i++) {
        const page = await S.pdfDoc.getPage(i);
        const vp = page.getViewport({ scale: S.renderScale });
        S.pageViewports[i - 1] = vp;

        const canvas = document.createElement('canvas');
        canvas.width = vp.width; canvas.height = vp.height;
        canvas.className = 'ed-page-canvas';
        await page.render({ canvasContext: canvas.getContext('2d'), viewport: vp }).promise;
        S.pageCanvases[i - 1] = canvas;

        // text items for edit-text tool
        const tc = await page.getTextContent();
        const items = [];
        for (const it of tc.items) {
            if (!it.str || !it.str.trim()) continue;
            const [cx, cy] = pdfjsLib.Util.applyTransform(
                [it.transform[4], it.transform[5]], vp.transform
            );
            const fontSize = Math.hypot(it.transform[0], it.transform[1]) * S.renderScale;
            const itemW = Math.abs(it.width || 0) * S.renderScale;
            items.push({
                str: it.str,
                x: cx, y: cy - fontSize, w: Math.max(itemW, 8), h: fontSize * 1.25,
                fontSize, transform: it.transform,
                pdfX: it.transform[4], pdfY: it.transform[5],
                pdfFontSize: Math.hypot(it.transform[0], it.transform[1]),
                pdfW: Math.abs(it.width || 0),
            });
        }
        S.pageTextItems[i - 1] = items;

        // build DOM
        const pageWrap = document.createElement('div');
        pageWrap.className = 'ed-page-wrap';
        pageWrap.dataset.page = i;
        pageWrap.style.width = vp.width + 'px';
        pageWrap.style.height = vp.height + 'px';

        const pageLbl = document.createElement('div');
        pageLbl.className = 'ed-page-lbl';
        pageLbl.textContent = 'Página ' + i;

        canvas.style.width = '100%';
        canvas.style.height = '100%';

        const overlay = document.createElement('div');
        overlay.className = 'ed-overlay';
        overlay.dataset.page = i;

        pageWrap.appendChild(pageLbl);
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
        el.style.width = (vp.width * S.zoom) + 'px';
        el.style.height = (vp.height * S.zoom) + 'px';
    });
    $('ed-zoom-label').textContent = Math.round(S.zoom * 100) + '%';
}

// ── Thumbnails ───────────────────────────────────────────────────────────────
function renderThumbs() {
    const strip = $('ed-thumbs');
    strip.innerHTML = '';
    for (let i = 1; i <= S.totalPages; i++) {
        const canvas = S.pageCanvases[i - 1];
        const t = document.createElement('div');
        t.className = 'ed-thumb';
        t.dataset.page = i;
        const tmp = document.createElement('canvas');
        const ratio = 64 / canvas.height;
        tmp.width = canvas.width * ratio; tmp.height = 64;
        tmp.getContext('2d').drawImage(canvas, 0, 0, tmp.width, tmp.height);
        const img = document.createElement('img');
        img.src = tmp.toDataURL('image/jpeg', 0.6);
        const lbl = document.createElement('div');
        lbl.className = 'ed-thumb-lbl'; lbl.textContent = i;
        t.appendChild(img); t.appendChild(lbl);
        t.addEventListener('click', () => scrollToPage(i));
        strip.appendChild(t);
    }
}

function scrollToPage(pg) {
    S.currentPage = pg;
    $('ed-page-cur').textContent = pg;
    const el = document.querySelector(`.ed-page-wrap[data-page="${pg}"]`);
    if (el) el.scrollIntoView({ block: 'start', behavior: 'smooth' });
    document.querySelectorAll('#ed-thumbs .ed-thumb').forEach(t => {
        t.classList.toggle('ed-thumb-active', +t.dataset.page === pg);
    });
}

// ── Tool selection ───────────────────────────────────────────────────────────
document.querySelectorAll('.ed-tool-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.ed-tool-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        S.currentTool = btn.dataset.tool;
        selectItem(null);
        document.body.style.cursor = S.currentTool === 'select' ? 'default' : 'crosshair';
        // image picker shortcut
        if (S.currentTool === 'image') $('ed-image-input').click();
    });
});

// ── Items: CRUD + history ────────────────────────────────────────────────────
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
    document.querySelectorAll('.ed-item').forEach(el => el.classList.remove('selected'));
    if (id) {
        const el = document.querySelector(`.ed-item[data-id="${id}"]`);
        if (el) el.classList.add('selected');
    }
    refreshPropertiesPanel();
}

function rebuildAllOverlays() {
    document.querySelectorAll('.ed-overlay').forEach(o => o.innerHTML = '');
    S.items.forEach(renderItem);
}

// ── Item rendering (DOM) ─────────────────────────────────────────────────────
function renderItem(it) {
    const overlay = document.querySelector(`.ed-overlay[data-page="${it.page}"]`);
    if (!overlay) return;
    let el = overlay.querySelector(`.ed-item[data-id="${it.id}"]`);
    if (!el) {
        el = document.createElement('div');
        el.className = 'ed-item';
        el.dataset.id = it.id;
        overlay.appendChild(el);
        attachItemEvents(el);
    }
    el.dataset.type = it.type;
    el.style.left = (it.x / S.renderScale * 100) + '%';
    el.style.top = (it.y / S.renderScale * 100) + '%';
    el.style.width = (it.w / S.renderScale * 100) + '%';
    el.style.height = (it.h / S.renderScale * 100) + '%';
    el.style.transform = `rotate(${it.rotation || 0}deg)`;
    el.style.opacity = it.opacity ?? 1;

    el.innerHTML = '';

    if (it.type === 'text' || it.type === 'edit-text') {
        const inner = document.createElement('div');
        inner.className = 'ed-item-text';
        inner.contentEditable = 'true';
        inner.spellcheck = false;
        inner.textContent = it.text || '';
        inner.style.fontSize = (it.fontSize * S.zoom) + 'px';
        inner.style.color = it.color || '#000';
        inner.style.fontFamily = it.fontFamily || 'Helvetica, Arial, sans-serif';
        inner.style.fontWeight = it.bold ? '700' : '400';
        inner.style.fontStyle = it.italic ? 'italic' : 'normal';
        inner.style.textDecoration = it.underline ? 'underline' : 'none';
        inner.style.textAlign = it.align || 'left';
        inner.style.lineHeight = '1.2';
        inner.addEventListener('input', () => {
            it.text = inner.textContent;
        });
        inner.addEventListener('focus', () => selectItem(it.id));
        inner.addEventListener('blur', () => refreshPropertiesPanel());
        el.appendChild(inner);
    } else if (it.type === 'rect') {
        el.style.background = it.fill || 'transparent';
        el.style.border = `${it.strokeWidth || 2}px solid ${it.stroke || '#E5322D'}`;
        el.style.borderRadius = (it.radius || 0) + 'px';
    } else if (it.type === 'highlight') {
        el.style.background = it.color || 'rgba(255,235,59,0.45)';
    } else if (it.type === 'erase') {
        el.style.background = '#ffffff';
        el.style.border = '1px dashed #ccc';
    } else if (it.type === 'image') {
        const img = document.createElement('img');
        img.src = it.dataUrl;
        img.style.width = '100%'; img.style.height = '100%';
        img.style.objectFit = 'fill'; img.draggable = false;
        el.appendChild(img);
    } else if (it.type === 'signature') {
        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('viewBox', `0 0 ${it.w} ${it.h}`);
        svg.style.width = '100%'; svg.style.height = '100%';
        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('d', it.path);
        path.setAttribute('fill', 'none');
        path.setAttribute('stroke', it.color || '#000');
        path.setAttribute('stroke-width', it.strokeWidth || 2);
        path.setAttribute('stroke-linecap', 'round');
        path.setAttribute('stroke-linejoin', 'round');
        svg.appendChild(path);
        el.appendChild(svg);
    }

    // 8 resize handles (only when selected)
    ['nw','n','ne','e','se','s','sw','w'].forEach(dir => {
        const h = document.createElement('div');
        h.className = 'ed-handle ed-handle-' + dir;
        h.dataset.handle = dir;
        el.appendChild(h);
    });

    // rotation handle
    const rot = document.createElement('div');
    rot.className = 'ed-handle ed-handle-rot';
    rot.dataset.handle = 'rot';
    el.appendChild(rot);

    // delete button
    const del = document.createElement('button');
    del.className = 'ed-item-del';
    del.innerHTML = '<i class="bi bi-trash"></i>';
    del.title = 'Eliminar';
    del.addEventListener('click', e => {
        e.stopPropagation();
        deleteItem(it.id);
    });
    el.appendChild(del);
}

// ── Item interaction (drag, resize, rotate) ──────────────────────────────────
function attachItemEvents(el) {
    el.addEventListener('mousedown', e => {
        if (S.currentTool !== 'select') return;
        const id = +el.dataset.id;
        const item = getItem(id);
        if (!item) return;
        selectItem(id);

        const handle = e.target.dataset?.handle;
        const overlay = el.parentElement;
        const rect = overlay.getBoundingClientRect();

        if (handle === 'rot') {
            S.interaction = { type: 'rotate', itemId: id, startX: e.clientX, startY: e.clientY,
                cx: rect.left + (item.x + item.w / 2) / S.renderScale * (rect.width / overlay.offsetWidth * overlay.offsetWidth),
                origRot: item.rotation || 0 };
            // compute centre via element
            const er = el.getBoundingClientRect();
            S.interaction.cx = er.left + er.width / 2;
            S.interaction.cy = er.top + er.height / 2;
        } else if (handle) {
            pushHistory();
            S.interaction = { type: 'resize', handle, itemId: id,
                startX: e.clientX, startY: e.clientY,
                origX: item.x, origY: item.y, origW: item.w, origH: item.h,
                scaleX: overlay.offsetWidth / S.pageViewports[item.page - 1].width,
                scaleY: overlay.offsetHeight / S.pageViewports[item.page - 1].height };
        } else if (!e.target.classList.contains('ed-item-text') &&
                   !e.target.classList.contains('ed-item-del')) {
            pushHistory();
            S.interaction = { type: 'drag', itemId: id,
                startX: e.clientX, startY: e.clientY,
                origX: item.x, origY: item.y,
                scaleX: overlay.offsetWidth / S.pageViewports[item.page - 1].width,
                scaleY: overlay.offsetHeight / S.pageViewports[item.page - 1].height };
        }
        e.stopPropagation();
        e.preventDefault();
    });
}

document.addEventListener('mousemove', e => {
    if (!S.interaction) return;
    const it = getItem(S.interaction.itemId);
    if (!it) return;

    if (S.interaction.type === 'drag') {
        const dx = (e.clientX - S.interaction.startX) / S.interaction.scaleX;
        const dy = (e.clientY - S.interaction.startY) / S.interaction.scaleY;
        it.x = S.interaction.origX + dx;
        it.y = S.interaction.origY + dy;
        renderItem(it); refreshPropertiesPanel();
    } else if (S.interaction.type === 'resize') {
        const dx = (e.clientX - S.interaction.startX) / S.interaction.scaleX;
        const dy = (e.clientY - S.interaction.startY) / S.interaction.scaleY;
        const h = S.interaction.handle;
        let { origX, origY, origW, origH } = S.interaction;
        if (h.includes('e')) origW = Math.max(10, origW + dx);
        if (h.includes('s')) origH = Math.max(10, origH + dy);
        if (h.includes('w')) { origX += dx; origW = Math.max(10, S.interaction.origW - dx); }
        if (h.includes('n')) { origY += dy; origH = Math.max(10, S.interaction.origH - dy); }
        it.x = origX; it.y = origY; it.w = origW; it.h = origH;
        renderItem(it); refreshPropertiesPanel();
    } else if (S.interaction.type === 'rotate') {
        const ang = Math.atan2(e.clientY - S.interaction.cy, e.clientX - S.interaction.cx) * 180 / Math.PI + 90;
        it.rotation = Math.round(ang);
        renderItem(it); refreshPropertiesPanel();
    }
});

document.addEventListener('mouseup', () => { S.interaction = null; });

// ── Overlay events (click to add new items) ──────────────────────────────────
function attachOverlayEvents(overlay, pageNum) {
    overlay.addEventListener('mousedown', e => {
        if (e.target !== overlay) return;
        const rect = overlay.getBoundingClientRect();
        const scaleX = S.pageViewports[pageNum - 1].width / rect.width;
        const scaleY = S.pageViewports[pageNum - 1].height / rect.height;
        const x = (e.clientX - rect.left) * scaleX;
        const y = (e.clientY - rect.top) * scaleY;

        if (S.currentTool === 'text') {
            const item = addItem({
                page: pageNum, type: 'text', x, y, w: 200, h: 40,
                text: 'Texto', fontSize: 18, color: '#000000',
                fontFamily: 'Helvetica, Arial, sans-serif',
                bold: false, italic: false, underline: false, align: 'left',
                rotation: 0, opacity: 1,
            });
            setTimeout(() => {
                const el = document.querySelector(`.ed-item[data-id="${item.id}"] .ed-item-text`);
                el?.focus();
                document.execCommand?.('selectAll', false, null);
            }, 30);
            selectTool('select');
        } else if (S.currentTool === 'edit-text') {
            const items = S.pageTextItems[pageNum - 1];
            const hit = items.find(t => x >= t.x && x <= t.x + t.w && y >= t.y && y <= t.y + t.h);
            if (hit) {
                // 1. cover original with white
                addItem({
                    page: pageNum, type: 'erase',
                    x: hit.x - 2, y: hit.y - 1,
                    w: hit.w + 4, h: hit.h + 2,
                    rotation: 0, opacity: 1,
                });
                // 2. add editable text on top with original text
                const item = addItem({
                    page: pageNum, type: 'edit-text',
                    x: hit.x, y: hit.y,
                    w: Math.max(hit.w + 40, 100), h: hit.h + 4,
                    text: hit.str, fontSize: hit.fontSize, color: '#000000',
                    fontFamily: 'Helvetica, Arial, sans-serif',
                    bold: false, italic: false, underline: false, align: 'left',
                    rotation: 0, opacity: 1,
                });
                setTimeout(() => {
                    const el = document.querySelector(`.ed-item[data-id="${item.id}"] .ed-item-text`);
                    el?.focus();
                    document.execCommand?.('selectAll', false, null);
                }, 30);
                selectTool('select');
            } else {
                showToast('Clique sobre um texto existente para editar.');
            }
        } else if (S.currentTool === 'rect' || S.currentTool === 'highlight' || S.currentTool === 'erase') {
            startRubberBand(e, overlay, pageNum);
        } else if (S.currentTool === 'signature') {
            openSignatureDialog(pageNum, x, y);
        } else {
            selectItem(null);
        }
    });
}

function startRubberBand(e, overlay, pageNum) {
    const rect = overlay.getBoundingClientRect();
    const scaleX = S.pageViewports[pageNum - 1].width / rect.width;
    const scaleY = S.pageViewports[pageNum - 1].height / rect.height;
    const startCSSX = e.clientX - rect.left;
    const startCSSY = e.clientY - rect.top;

    const tmp = document.createElement('div');
    tmp.className = 'ed-rubber-band';
    tmp.style.left = startCSSX + 'px'; tmp.style.top = startCSSY + 'px';
    overlay.appendChild(tmp);

    const tool = S.currentTool;
    const move = ev => {
        const cx = ev.clientX - rect.left;
        const cy = ev.clientY - rect.top;
        tmp.style.left = Math.min(cx, startCSSX) + 'px';
        tmp.style.top = Math.min(cy, startCSSY) + 'px';
        tmp.style.width = Math.abs(cx - startCSSX) + 'px';
        tmp.style.height = Math.abs(cy - startCSSY) + 'px';
    };
    const up = ev => {
        document.removeEventListener('mousemove', move);
        document.removeEventListener('mouseup', up);
        const cx = ev.clientX - rect.left;
        const cy = ev.clientY - rect.top;
        const x  = Math.min(cx, startCSSX) * scaleX;
        const y  = Math.min(cy, startCSSY) * scaleY;
        const w  = Math.abs(cx - startCSSX) * scaleX;
        const h  = Math.abs(cy - startCSSY) * scaleY;
        tmp.remove();
        if (w < 5 || h < 5) return;

        const opts = { page: pageNum, x, y, w, h, rotation: 0, opacity: 1 };
        if (tool === 'rect') {
            addItem({ ...opts, type: 'rect', stroke: '#E5322D', strokeWidth: 2, fill: 'transparent', radius: 0 });
        } else if (tool === 'highlight') {
            addItem({ ...opts, type: 'highlight', color: 'rgba(255,235,59,0.45)' });
        } else if (tool === 'erase') {
            addItem({ ...opts, type: 'erase' });
        }
        selectTool('select');
    };
    document.addEventListener('mousemove', move);
    document.addEventListener('mouseup', up);
}

function selectTool(tool) {
    document.querySelectorAll('.ed-tool-btn').forEach(b => b.classList.toggle('active', b.dataset.tool === tool));
    S.currentTool = tool;
    document.body.style.cursor = tool === 'select' ? 'default' : 'crosshair';
}

// ── Properties panel ─────────────────────────────────────────────────────────
function refreshPropertiesPanel() {
    const panel = $('ed-props-body');
    const it = S.selectedItemId ? getItem(S.selectedItemId) : null;
    if (!it) {
        panel.innerHTML = '<p class="text-muted small mb-0">Nenhum item selecionado. Use as ferramentas à esquerda para adicionar conteúdo ao PDF.</p>';
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
                    <label class="form-label small mb-1">Tamanho (px)</label>
                    <input type="number" class="form-control form-control-sm" id="pp-fontSize" value="${it.fontSize}" min="6" max="144">
                </div>
                <div class="col-6">
                    <label class="form-label small mb-1">Cor</label>
                    <input type="color" class="form-control form-control-color form-control-sm w-100" id="pp-color" value="${it.color}">
                </div>
            </div>
            <div class="mb-2">
                <label class="form-label small mb-1">Fonte</label>
                <select id="pp-font" class="form-select form-select-sm">
                    <option value="Helvetica, Arial, sans-serif" ${it.fontFamily?.includes('Helvetica') ? 'selected' : ''}>Helvetica</option>
                    <option value="Times New Roman, Times, serif" ${it.fontFamily?.includes('Times') ? 'selected' : ''}>Times</option>
                    <option value="Courier New, Courier, monospace" ${it.fontFamily?.includes('Courier') ? 'selected' : ''}>Courier</option>
                </select>
            </div>
            <div class="btn-group btn-group-sm mb-2 w-100" role="group">
                <button class="btn btn-outline-secondary ${it.bold?'active':''}" id="pp-bold"><b>B</b></button>
                <button class="btn btn-outline-secondary ${it.italic?'active':''}" id="pp-italic"><i>I</i></button>
                <button class="btn btn-outline-secondary ${it.underline?'active':''}" id="pp-underline"><u>U</u></button>
                <button class="btn btn-outline-secondary ${it.align==='left'?'active':''}" id="pp-al"><i class="bi bi-text-left"></i></button>
                <button class="btn btn-outline-secondary ${it.align==='center'?'active':''}" id="pp-ac"><i class="bi bi-text-center"></i></button>
                <button class="btn btn-outline-secondary ${it.align==='right'?'active':''}" id="pp-ar"><i class="bi bi-text-right"></i></button>
            </div>
        ` : ''}
        ${it.type === 'rect' ? `
            <div class="row g-2 mb-2">
                <div class="col-6"><label class="form-label small mb-1">Borda</label><input type="color" class="form-control form-control-color form-control-sm w-100" id="pp-stroke" value="${it.stroke || '#E5322D'}"></div>
                <div class="col-6"><label class="form-label small mb-1">Preenchimento</label><input type="color" class="form-control form-control-color form-control-sm w-100" id="pp-fill" value="${it.fill === 'transparent' ? '#ffffff' : it.fill}"></div>
            </div>
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" id="pp-fill-on" ${it.fill !== 'transparent' ? 'checked' : ''}>
                <label class="form-check-label small" for="pp-fill-on">Preencher</label>
            </div>
            <div class="row g-2 mb-2">
                <div class="col-6"><label class="form-label small mb-1">Espessura</label><input type="number" class="form-control form-control-sm" id="pp-stroke-w" value="${it.strokeWidth || 2}" min="1" max="20"></div>
                <div class="col-6"><label class="form-label small mb-1">Raio</label><input type="number" class="form-control form-control-sm" id="pp-radius" value="${it.radius || 0}" min="0" max="60"></div>
            </div>
        ` : ''}
        ${it.type === 'highlight' ? `
            <div class="mb-2">
                <label class="form-label small mb-1">Cor</label>
                <input type="color" class="form-control form-control-color form-control-sm w-100" id="pp-hl-color" value="#ffeb3b">
            </div>
        ` : ''}
        <hr class="my-2">
        <div class="row g-2 mb-2">
            <div class="col-3"><label class="form-label small mb-1">X</label><input type="number" class="form-control form-control-sm" id="pp-x" value="${Math.round(it.x)}"></div>
            <div class="col-3"><label class="form-label small mb-1">Y</label><input type="number" class="form-control form-control-sm" id="pp-y" value="${Math.round(it.y)}"></div>
            <div class="col-3"><label class="form-label small mb-1">L</label><input type="number" class="form-control form-control-sm" id="pp-w" value="${Math.round(it.w)}"></div>
            <div class="col-3"><label class="form-label small mb-1">A</label><input type="number" class="form-control form-control-sm" id="pp-h" value="${Math.round(it.h)}"></div>
        </div>
        <div class="row g-2 mb-2">
            <div class="col-6"><label class="form-label small mb-1">Rotação</label><input type="number" class="form-control form-control-sm" id="pp-rot" value="${it.rotation || 0}"></div>
            <div class="col-6"><label class="form-label small mb-1">Opacidade</label><input type="range" class="form-range" id="pp-op" min="0" max="1" step="0.05" value="${it.opacity ?? 1}"></div>
        </div>
        <button class="btn btn-outline-danger btn-sm w-100" id="pp-delete"><i class="bi bi-trash me-1"></i>Eliminar item</button>
    `;
    bindPropEvents(it);
}

function bindPropEvents(it) {
    const update = () => { renderItem(it); };
    const bind = (id, prop, parse = v => v) => {
        const el = $(id); if (!el) return;
        el.addEventListener('input', () => { it[prop] = parse(el.value); update(); });
    };
    bind('pp-text', 'text');
    bind('pp-fontSize', 'fontSize', v => parseFloat(v) || 12);
    bind('pp-color', 'color');
    bind('pp-font', 'fontFamily');
    bind('pp-stroke', 'stroke');
    bind('pp-fill', 'fill');
    bind('pp-stroke-w', 'strokeWidth', v => parseInt(v) || 1);
    bind('pp-radius', 'radius', v => parseInt(v) || 0);
    bind('pp-hl-color', null);
    $('pp-hl-color')?.addEventListener('input', e => {
        const hex = e.target.value;
        const r = parseInt(hex.slice(1,3),16), g = parseInt(hex.slice(3,5),16), b = parseInt(hex.slice(5,7),16);
        it.color = `rgba(${r},${g},${b},0.45)`; update();
    });
    $('pp-fill-on')?.addEventListener('change', e => {
        it.fill = e.target.checked ? ($('pp-fill').value || '#ffff00') : 'transparent'; update();
    });
    bind('pp-x', 'x', v => parseFloat(v) || 0);
    bind('pp-y', 'y', v => parseFloat(v) || 0);
    bind('pp-w', 'w', v => Math.max(5, parseFloat(v) || 5));
    bind('pp-h', 'h', v => Math.max(5, parseFloat(v) || 5));
    bind('pp-rot', 'rotation', v => parseFloat(v) || 0);
    bind('pp-op', 'opacity', v => parseFloat(v));
    ['bold','italic','underline'].forEach(prop => {
        $('pp-' + prop)?.addEventListener('click', () => { it[prop] = !it[prop]; update(); refreshPropertiesPanel(); });
    });
    [['pp-al','left'],['pp-ac','center'],['pp-ar','right']].forEach(([id, val]) => {
        $(id)?.addEventListener('click', () => { it.align = val; update(); refreshPropertiesPanel(); });
    });
    $('pp-delete')?.addEventListener('click', () => deleteItem(it.id));
}

function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));
}

// ── Toast ────────────────────────────────────────────────────────────────────
function showToast(msg) {
    const t = $('ed-toast');
    t.textContent = msg;
    t.classList.add('show');
    clearTimeout(t._timer);
    t._timer = setTimeout(() => t.classList.remove('show'), 2200);
}

// ── Image upload ─────────────────────────────────────────────────────────────
$('ed-image-input').addEventListener('change', async e => {
    const file = e.target.files[0]; if (!file) return;
    const dataUrl = await new Promise(res => {
        const r = new FileReader(); r.onload = () => res(r.result); r.readAsDataURL(file);
    });
    const img = new Image();
    img.onload = () => {
        const maxW = 300, maxH = 300;
        const ratio = Math.min(maxW / img.width, maxH / img.height, 1);
        const w = img.width * ratio * S.renderScale;
        const h = img.height * ratio * S.renderScale;
        const vp = S.pageViewports[S.currentPage - 1];
        addItem({
            page: S.currentPage, type: 'image',
            x: (vp.width - w) / 2, y: (vp.height - h) / 2, w, h,
            dataUrl, rotation: 0, opacity: 1,
            mime: file.type,
        });
        selectTool('select');
    };
    img.src = dataUrl;
    e.target.value = '';
});

// ── Signature dialog ─────────────────────────────────────────────────────────
function openSignatureDialog(pageNum, atX, atY) {
    const modal = $('ed-sig-modal');
    const canvas = $('ed-sig-canvas');
    const ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.lineCap = 'round'; ctx.lineJoin = 'round';
    ctx.strokeStyle = $('ed-sig-color').value;
    ctx.lineWidth = parseFloat($('ed-sig-width').value);

    let drawing = false; let pts = []; let pathStr = '';
    const start = e => { drawing = true; pts = []; const p = pos(e); pts.push(p); pathStr = `M${p.x} ${p.y}`; };
    const move = e => {
        if (!drawing) return;
        const p = pos(e); pts.push(p);
        pathStr += ` L${p.x} ${p.y}`;
        ctx.beginPath();
        ctx.moveTo(pts[pts.length - 2].x, pts[pts.length - 2].y);
        ctx.lineTo(p.x, p.y);
        ctx.stroke();
    };
    const end = () => { drawing = false; };
    const pos = e => {
        const r = canvas.getBoundingClientRect();
        const t = e.touches ? e.touches[0] : e;
        return { x: (t.clientX - r.left) * canvas.width / r.width, y: (t.clientY - r.top) * canvas.height / r.height };
    };
    canvas.onmousedown = start; canvas.onmousemove = move; canvas.onmouseup = end; canvas.onmouseleave = end;
    canvas.ontouchstart = e => { e.preventDefault(); start(e); };
    canvas.ontouchmove  = e => { e.preventDefault(); move(e); };
    canvas.ontouchend   = end;

    $('ed-sig-clear').onclick = () => { ctx.clearRect(0, 0, canvas.width, canvas.height); pathStr = ''; pts = []; };
    $('ed-sig-color').oninput = e => ctx.strokeStyle = e.target.value;
    $('ed-sig-width').oninput = e => ctx.lineWidth = parseFloat(e.target.value);

    $('ed-sig-apply').onclick = () => {
        if (!pathStr) { alert('Por favor desenhe a sua assinatura primeiro.'); return; }
        // bounds
        const xs = pts.map(p => p.x), ys = pts.map(p => p.y);
        const minX = Math.min(...xs), maxX = Math.max(...xs);
        const minY = Math.min(...ys), maxY = Math.max(...ys);
        const w = maxX - minX, h = maxY - minY;
        // shift path to origin
        const shifted = pathStr.replace(/([ML])(-?[\d.]+) (-?[\d.]+)/g, (_, c, x, y) =>
            `${c}${(parseFloat(x) - minX).toFixed(1)} ${(parseFloat(y) - minY).toFixed(1)}`);

        // place at click position with reasonable size
        const sigScale = 0.5;
        addItem({
            page: pageNum, type: 'signature',
            x: atX, y: atY,
            w: w * sigScale * S.renderScale / (canvas.width / 600),
            h: h * sigScale * S.renderScale / (canvas.width / 600),
            path: shifted,
            origW: w, origH: h,
            color: $('ed-sig-color').value,
            strokeWidth: parseFloat($('ed-sig-width').value),
            rotation: 0, opacity: 1,
        });
        modal.classList.remove('show');
        modal.style.display = 'none';
        selectTool('select');
    };
    $('ed-sig-cancel').onclick = () => {
        modal.classList.remove('show'); modal.style.display = 'none';
        selectTool('select');
    };
    modal.style.display = 'flex';
    modal.classList.add('show');
}

// ── Toolbar buttons ──────────────────────────────────────────────────────────
$('ed-zoom-in').addEventListener('click', () => { S.zoom = Math.min(3, S.zoom + 0.25); applyZoom(); rebuildAllOverlays(); });
$('ed-zoom-out').addEventListener('click', () => { S.zoom = Math.max(0.25, S.zoom - 0.25); applyZoom(); rebuildAllOverlays(); });
$('ed-fit').addEventListener('click', () => {
    const wrap = $('ed-canvas-area');
    const vp = S.pageViewports[0];
    if (!vp) return;
    S.zoom = Math.max(0.25, Math.min(3, (wrap.clientWidth - 40) / vp.width));
    applyZoom(); rebuildAllOverlays();
});
$('ed-undo').addEventListener('click', undo);
$('ed-redo').addEventListener('click', redo);
$('ed-prev').addEventListener('click', () => S.currentPage > 1 && scrollToPage(S.currentPage - 1));
$('ed-next').addEventListener('click', () => S.currentPage < S.totalPages && scrollToPage(S.currentPage + 1));
$('ed-new').addEventListener('click', () => location.reload());

// Keyboard
document.addEventListener('keydown', e => {
    if (e.target.matches('input, textarea, [contenteditable="true"]')) return;
    if ((e.ctrlKey || e.metaKey) && e.key === 'z') { e.preventDefault(); undo(); }
    else if ((e.ctrlKey || e.metaKey) && e.key === 'y') { e.preventDefault(); redo(); }
    else if (e.key === 'Delete' || e.key === 'Backspace') {
        if (S.selectedItemId) { e.preventDefault(); deleteItem(S.selectedItemId); }
    } else if (e.key === 'Escape') { selectItem(null); selectTool('select'); }
});

// Track current page on scroll
$('ed-canvas-area').addEventListener('scroll', () => {
    const wrap = $('ed-canvas-area');
    const pages = document.querySelectorAll('.ed-page-wrap');
    for (const p of pages) {
        const r = p.getBoundingClientRect();
        const cr = wrap.getBoundingClientRect();
        if (r.top - cr.top > -r.height / 2 && r.top - cr.top < cr.height / 2) {
            const pg = +p.dataset.page;
            if (pg !== S.currentPage) {
                S.currentPage = pg;
                $('ed-page-cur').textContent = pg;
                document.querySelectorAll('#ed-thumbs .ed-thumb').forEach(t => {
                    t.classList.toggle('ed-thumb-active', +t.dataset.page === pg);
                });
            }
            break;
        }
    }
});

// ── SAVE ─────────────────────────────────────────────────────────────────────
$('ed-save').addEventListener('click', saveAsPdf);

async function saveAsPdf() {
    if (!S.originalBytes) return;
    $('ed-save').disabled = true;
    const spinner = $('ed-save-spinner');
    spinner.classList.remove('d-none');

    try {
        const pdfLibDoc = await PDFDocument.load(S.originalBytes.slice(0));
        // fontkit (for future custom fonts; safe to register)
        if (window.fontkit) pdfLibDoc.registerFontkit(window.fontkit);

        const fontHelv = await pdfLibDoc.embedFont(StandardFonts.Helvetica);
        const fontHelvB = await pdfLibDoc.embedFont(StandardFonts.HelveticaBold);
        const fontHelvI = await pdfLibDoc.embedFont(StandardFonts.HelveticaOblique);
        const fontHelvBI = await pdfLibDoc.embedFont(StandardFonts.HelveticaBoldOblique);
        const fontTimes = await pdfLibDoc.embedFont(StandardFonts.TimesRoman);
        const fontTimesB = await pdfLibDoc.embedFont(StandardFonts.TimesRomanBold);
        const fontTimesI = await pdfLibDoc.embedFont(StandardFonts.TimesRomanItalic);
        const fontTimesBI = await pdfLibDoc.embedFont(StandardFonts.TimesRomanBoldItalic);
        const fontCour = await pdfLibDoc.embedFont(StandardFonts.Courier);
        const fontCourB = await pdfLibDoc.embedFont(StandardFonts.CourierBold);
        const fontCourI = await pdfLibDoc.embedFont(StandardFonts.CourierOblique);
        const fontCourBI = await pdfLibDoc.embedFont(StandardFonts.CourierBoldOblique);

        const pickFont = (family, bold, italic) => {
            const f = family || '';
            if (f.includes('Times')) return bold && italic ? fontTimesBI : bold ? fontTimesB : italic ? fontTimesI : fontTimes;
            if (f.includes('Courier')) return bold && italic ? fontCourBI : bold ? fontCourB : italic ? fontCourI : fontCour;
            return bold && italic ? fontHelvBI : bold ? fontHelvB : italic ? fontHelvI : fontHelv;
        };

        const hexToRgbObj = hex => {
            if (!hex || hex[0] !== '#') return { r: 0, g: 0, b: 0 };
            return {
                r: parseInt(hex.slice(1,3), 16) / 255,
                g: parseInt(hex.slice(3,5), 16) / 255,
                b: parseInt(hex.slice(5,7), 16) / 255,
            };
        };

        const pages = pdfLibDoc.getPages();
        for (let p = 1; p <= S.totalPages; p++) {
            const page = pages[p - 1];
            const { width: pdfW, height: pdfH } = page.getSize();
            const vp = S.pageViewports[p - 1];
            // canvas px → pdf pt: pdfW / vp.width  (vp.width is at renderScale)
            const sxToPt = pdfW / vp.width;
            const syToPt = pdfH / vp.height;
            const pageItems = S.items.filter(it => it.page === p);
            // draw erase (white covers) first so they go under everything
            pageItems.sort((a, b) => (a.type === 'erase' ? -1 : 0) - (b.type === 'erase' ? -1 : 0));

            for (const it of pageItems) {
                const x = it.x * sxToPt;
                // PDF Y origin is bottom-left
                const yTop = (vp.height - it.y) * syToPt;
                const w = it.w * sxToPt;
                const h = it.h * syToPt;
                const rot = degrees(-(it.rotation || 0));
                const opacity = it.opacity ?? 1;

                if (it.type === 'erase') {
                    page.drawRectangle({
                        x, y: yTop - h, width: w, height: h,
                        color: rgb(1, 1, 1), opacity,
                    });
                } else if (it.type === 'rect') {
                    const stroke = hexToRgbObj(it.stroke || '#000');
                    const opts = {
                        x, y: yTop - h, width: w, height: h,
                        borderColor: rgb(stroke.r, stroke.g, stroke.b),
                        borderWidth: (it.strokeWidth || 2) * sxToPt,
                        opacity, rotate: rot,
                    };
                    if (it.fill && it.fill !== 'transparent') {
                        const f = hexToRgbObj(it.fill);
                        opts.color = rgb(f.r, f.g, f.b);
                    }
                    page.drawRectangle(opts);
                } else if (it.type === 'highlight') {
                    // parse rgba(r,g,b,a)
                    const m = (it.color || '').match(/rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*([\d.]+))?\)/);
                    if (m) {
                        page.drawRectangle({
                            x, y: yTop - h, width: w, height: h,
                            color: rgb(+m[1]/255, +m[2]/255, +m[3]/255),
                            opacity: (parseFloat(m[4] ?? 0.45)) * opacity,
                        });
                    }
                } else if (it.type === 'text' || it.type === 'edit-text') {
                    const c = hexToRgbObj(it.color || '#000');
                    const font = pickFont(it.fontFamily, it.bold, it.italic);
                    const sizePt = it.fontSize * sxToPt;
                    const lines = (it.text || '').split('\n');
                    const lineH = sizePt * 1.2;
                    let alignDX = 0;
                    for (let li = 0; li < lines.length; li++) {
                        const line = lines[li];
                        const lineW = font.widthOfTextAtSize(line, sizePt);
                        if (it.align === 'center') alignDX = (w - lineW) / 2;
                        else if (it.align === 'right') alignDX = w - lineW;
                        else alignDX = 0;
                        page.drawText(line, {
                            x: x + alignDX,
                            y: yTop - sizePt - li * lineH,
                            size: sizePt,
                            font, color: rgb(c.r, c.g, c.b),
                            opacity, rotate: rot,
                        });
                        if (it.underline) {
                            page.drawLine({
                                start: { x: x + alignDX, y: yTop - sizePt - li * lineH - 2 },
                                end:   { x: x + alignDX + lineW, y: yTop - sizePt - li * lineH - 2 },
                                thickness: Math.max(0.5, sizePt * 0.05),
                                color: rgb(c.r, c.g, c.b),
                                opacity,
                            });
                        }
                    }
                } else if (it.type === 'image') {
                    let img;
                    const mime = it.mime || (it.dataUrl.startsWith('data:image/png') ? 'image/png' : 'image/jpeg');
                    const bytes = dataUrlToBytes(it.dataUrl);
                    img = mime.includes('png')
                        ? await pdfLibDoc.embedPng(bytes)
                        : await pdfLibDoc.embedJpg(bytes);
                    page.drawImage(img, {
                        x, y: yTop - h, width: w, height: h,
                        opacity, rotate: rot,
                    });
                } else if (it.type === 'signature') {
                    // scale path: original svg viewBox is (w, h) in CSS pixels (canvas units)
                    // we need a PDF path moved/scaled to fit (x, yTop - h, w, h)
                    const sigScaleX = w / (it.origW || it.w);
                    const sigScaleY = h / (it.origH || it.h);
                    const c = hexToRgbObj(it.color || '#000');
                    const tokens = it.path.match(/[ML]-?[\d.]+\s-?[\d.]+/g) || [];
                    let prev = null;
                    for (const tk of tokens) {
                        const m = tk.match(/([ML])(-?[\d.]+)\s(-?[\d.]+)/);
                        if (!m) continue;
                        const px = x + parseFloat(m[2]) * sigScaleX;
                        const py = yTop - parseFloat(m[3]) * sigScaleY;
                        if (m[1] === 'M') { prev = { x: px, y: py }; }
                        else if (m[1] === 'L' && prev) {
                            page.drawLine({
                                start: prev, end: { x: px, y: py },
                                thickness: (it.strokeWidth || 2) * sxToPt,
                                color: rgb(c.r, c.g, c.b),
                                opacity,
                            });
                            prev = { x: px, y: py };
                        }
                    }
                }
            }
        }

        const out = await pdfLibDoc.save();
        const blob = new Blob([out], { type: 'application/pdf' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = (S.file.name.replace(/\.pdf$/i, '') || 'document') + '_edited.pdf';
        document.body.appendChild(a); a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(a.href);
        showToast('PDF guardado!');
    } catch (err) {
        console.error(err);
        alert('Erro ao guardar PDF: ' + err.message);
    } finally {
        $('ed-save').disabled = false;
        spinner.classList.add('d-none');
    }
}

function dataUrlToBytes(dataUrl) {
    const b64 = dataUrl.split(',')[1];
    const bin = atob(b64);
    const bytes = new Uint8Array(bin.length);
    for (let i = 0; i < bin.length; i++) bytes[i] = bin.charCodeAt(i);
    return bytes;
}

// Clear properties initially
refreshPropertiesPanel();
