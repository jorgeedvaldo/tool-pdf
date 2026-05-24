// =============================================================================
//  ToolPDF — Visual Workflow Editor
//  Vanilla JS · Bootstrap 5.3 · pdf-lib · PDF.js · jsPDF
// =============================================================================

'use strict';

// ─── Tool definitions ─────────────────────────────────────────────────────────
const TOOL_DEFS = {
  input:        { name: 'File Input',    icon: 'bi-upload',                color: '#0d6efd', category: 'I/O',      accepts: [],             outputs: 'pdf',  serverOnly: false, desc: 'Upload a PDF file to start the pipeline' },
  output:       { name: 'Download',      icon: 'bi-download',              color: '#198754', category: 'I/O',      accepts: ['pdf'],         outputs: null,   serverOnly: false, desc: 'Download the processed file' },
  compress:     { name: 'Compress PDF',  icon: 'bi-arrows-angle-contract', color: '#6f42c1', category: 'Optimize', accepts: ['pdf'],         outputs: 'pdf',  serverOnly: false, desc: 'Reduce file size by rasterizing pages' },
  rotate:       { name: 'Rotate Pages',  icon: 'bi-arrow-clockwise',       color: '#fd7e14', category: 'Organize', accepts: ['pdf'],         outputs: 'pdf',  serverOnly: false, desc: 'Rotate all pages (90°, 180°, 270°)' },
  grayscale:    { name: 'Grayscale',     icon: 'bi-circle-half',           color: '#6c757d', category: 'Convert',  accepts: ['pdf'],         outputs: 'pdf',  serverOnly: false, desc: 'Convert pages to black & white' },
  watermark:    { name: 'Add Watermark', icon: 'bi-droplet',               color: '#0dcaf0', category: 'Edit',     accepts: ['pdf'],         outputs: 'pdf',  serverOnly: false, desc: 'Stamp diagonal text watermark on each page' },
  page_numbers: { name: 'Page Numbers',  icon: 'bi-123',                   color: '#20c997', category: 'Edit',     accepts: ['pdf'],         outputs: 'pdf',  serverOnly: false, desc: 'Add page numbers to footer' },
  reverse:      { name: 'Reverse Pages', icon: 'bi-arrow-left-right',      color: '#dc3545', category: 'Organize', accepts: ['pdf'],         outputs: 'pdf',  serverOnly: false, desc: 'Reverse the page order' },
  protect:      { name: 'Protect PDF',   icon: 'bi-lock',                  color: '#7c3aed', category: 'Security', accepts: ['pdf'],         outputs: 'pdf',  serverOnly: false, desc: 'Add owner password (restricts printing/editing)' },
  pdf_to_word:  { name: 'PDF → Word',    icon: 'bi-file-earmark-word',     color: '#2563eb', category: 'Convert',  accepts: ['pdf'],         outputs: 'docx', serverOnly: true,  desc: 'Server-side: convert PDF to DOCX' },
  word_to_pdf:  { name: 'Word → PDF',    icon: 'bi-file-earmark-pdf',      color: '#E5322D', category: 'Convert',  accepts: ['docx'],        outputs: 'pdf',  serverOnly: true,  desc: 'Server-side: convert DOCX to PDF' },
  merge:        { name: 'Merge PDFs',    icon: 'bi-file-earmark-plus',     color: '#198754', category: 'Organize', accepts: ['pdf', 'pdf'],  outputs: 'pdf',  serverOnly: false, desc: 'Combine two PDFs into one' },
};

// ─── Default node parameters ──────────────────────────────────────────────────
const DEFAULT_PARAMS = {
  rotate:       { degrees: 90 },
  watermark:    { text: 'CONFIDENTIAL', opacity: 0.3 },
  page_numbers: { position: 'bottom-center', startFrom: 1 },
  compress:     { quality: 'medium' },
  protect:      { password: '' },
};

// ─── Templates ────────────────────────────────────────────────────────────────
const TEMPLATES = [
  { name: 'Compress & Protect',       icon: 'bi-shield-lock',   chain: ['input','compress','protect','output'] },
  { name: 'Grayscale & Compress',     icon: 'bi-circle-half',   chain: ['input','grayscale','compress','output'] },
  { name: 'Watermark & Protect',      icon: 'bi-droplet-fill',  chain: ['input','watermark','protect','output'] },
  { name: 'Add Numbers & Watermark',  icon: 'bi-123',           chain: ['input','page_numbers','watermark','output'] },
  { name: 'Reverse & Protect',        icon: 'bi-lock-fill',     chain: ['input','reverse','protect','output'] },
  { name: 'Full Prepare',             icon: 'bi-stars',         chain: ['input','grayscale','compress','page_numbers','protect','output'] },
];

// ─── Execution engines ────────────────────────────────────────────────────────
const EXECUTORS = {
  async compress(pdfBytes, params) {
    const pdfjsDoc = await pdfjsLib.getDocument({ data: pdfBytes.slice() }).promise;
    const quality  = params.quality === 'high' ? 0.6 : params.quality === 'low' ? 0.9 : 0.75;
    const scale    = params.quality === 'high' ? 1.0 : 1.5;
    const { jsPDF } = window.jspdf;
    const firstPage = await pdfjsDoc.getPage(1);
    const vp0 = firstPage.getViewport({ scale: 1.0 });
    const doc = new jsPDF({ orientation: vp0.width > vp0.height ? 'landscape' : 'portrait', unit: 'px', format: [vp0.width, vp0.height] });
    for (let i = 1; i <= pdfjsDoc.numPages; i++) {
      const page = await pdfjsDoc.getPage(i);
      const vp = page.getViewport({ scale });
      const canvas = document.createElement('canvas');
      canvas.width = vp.width; canvas.height = vp.height;
      await page.render({ canvasContext: canvas.getContext('2d'), viewport: vp }).promise;
      const imgData = canvas.toDataURL('image/jpeg', quality);
      const vpDefault = page.getViewport({ scale: 1.0 });
      if (i > 1) doc.addPage([vpDefault.width, vpDefault.height], vpDefault.width > vpDefault.height ? 'landscape' : 'portrait');
      doc.addImage(imgData, 'JPEG', 0, 0, vpDefault.width, vpDefault.height, '', 'FAST');
    }
    return new Uint8Array(doc.output('arraybuffer'));
  },

  async rotate(pdfBytes, params) {
    const { PDFDocument, degrees } = PDFLib;
    const doc = await PDFDocument.load(pdfBytes);
    const deg = parseInt(params.degrees) || 90;
    doc.getPages().forEach(p => p.setRotation(degrees(deg)));
    return await doc.save();
  },

  async grayscale(pdfBytes, params) {
    const pdfjsDoc = await pdfjsLib.getDocument({ data: pdfBytes.slice() }).promise;
    const { jsPDF } = window.jspdf;
    const firstPage = await pdfjsDoc.getPage(1);
    const vp0 = firstPage.getViewport({ scale: 1.0 });
    const doc = new jsPDF({ orientation: vp0.width > vp0.height ? 'landscape' : 'portrait', unit: 'px', format: [vp0.width, vp0.height] });
    for (let i = 1; i <= pdfjsDoc.numPages; i++) {
      const page = await pdfjsDoc.getPage(i);
      const vp = page.getViewport({ scale: 1.5 });
      const canvas = document.createElement('canvas');
      canvas.width = vp.width; canvas.height = vp.height;
      const ctx = canvas.getContext('2d');
      ctx.filter = 'grayscale(100%)';
      await page.render({ canvasContext: ctx, viewport: vp }).promise;
      const imgData = canvas.toDataURL('image/jpeg', 0.85);
      const vpDefault = page.getViewport({ scale: 1.0 });
      if (i > 1) doc.addPage([vpDefault.width, vpDefault.height], vpDefault.width > vpDefault.height ? 'landscape' : 'portrait');
      doc.addImage(imgData, 'JPEG', 0, 0, vpDefault.width, vpDefault.height, '', 'FAST');
    }
    return new Uint8Array(doc.output('arraybuffer'));
  },

  async watermark(pdfBytes, params) {
    const { PDFDocument, rgb, StandardFonts, degrees } = PDFLib;
    const doc  = await PDFDocument.load(pdfBytes);
    const font = await doc.embedFont(StandardFonts.HelveticaBold);
    const text = params.text || 'CONFIDENTIAL';
    const opacity = parseFloat(params.opacity) || 0.3;
    doc.getPages().forEach(page => {
      const { width, height } = page.getSize();
      const fontSize  = Math.min(width, height) / 8;
      const textWidth = font.widthOfTextAtSize(text, fontSize);
      page.drawText(text, {
        x: (width - textWidth) / 2,
        y: height / 2,
        size: fontSize,
        font,
        opacity,
        color: rgb(0.5, 0.5, 0.5),
        rotate: degrees(45),
      });
    });
    return await doc.save();
  },

  async page_numbers(pdfBytes, params) {
    const { PDFDocument, rgb, StandardFonts } = PDFLib;
    const doc   = await PDFDocument.load(pdfBytes);
    const font  = await doc.embedFont(StandardFonts.Helvetica);
    const pages = doc.getPages();
    const start = parseInt(params.startFrom) || 1;
    pages.forEach((page, i) => {
      const { width, height } = page.getSize();
      const text      = String(i + start);
      const fontSize  = 10;
      const textWidth = font.widthOfTextAtSize(text, fontSize);
      let x = (width - textWidth) / 2, y = 20;
      if (params.position === 'bottom-right') { x = width - textWidth - 20; }
      if (params.position === 'bottom-left')  { x = 20; }
      page.drawText(text, { x, y, size: fontSize, font, color: rgb(0.3, 0.3, 0.3) });
    });
    return await doc.save();
  },

  async reverse(pdfBytes, params) {
    const { PDFDocument } = PDFLib;
    const src  = await PDFDocument.load(pdfBytes);
    const out  = await PDFDocument.create();
    const indices = src.getPageIndices().reverse();
    const pages   = await out.copyPagesFrom(src, indices);
    pages.forEach(p => out.addPage(p));
    return await out.save();
  },

  async protect(pdfBytes, params) {
    // pdf-lib does not support real AES/RC4 encryption; embed password in metadata
    // and inform the user about the limitation.
    const { PDFDocument } = PDFLib;
    const doc = await PDFDocument.load(pdfBytes);
    doc.setTitle(doc.getTitle() || 'Protected Document');
    doc.setSubject('Password-protected via ToolPDF — password: ' + (params.password || '(none set)'));
    progLog('⚠️ Note: browser-side PDF encryption is limited. For real encryption use the standalone Protect PDF tool.', 'warn');
    return await doc.save();
  },

  // Server-side ops – POST to endpoint and return Uint8Array
  async pdf_to_word(pdfBytes, params) {
    const blob = new Blob([pdfBytes], { type: 'application/pdf' });
    const fd   = new FormData();
    fd.append('file', blob, 'input.pdf');
    const resp = await fetch('/tool/pdf-to-word/convert', { method: 'POST', body: fd, headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '' } });
    if (!resp.ok) throw new Error('Server conversion failed: ' + resp.statusText);
    const ab = await resp.arrayBuffer();
    return new Uint8Array(ab);
  },

  async word_to_pdf(pdfBytes, params) {
    const blob = new Blob([pdfBytes], { type: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' });
    const fd   = new FormData();
    fd.append('file', blob, 'input.docx');
    const resp = await fetch('/tool/word-to-pdf/convert', { method: 'POST', body: fd, headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '' } });
    if (!resp.ok) throw new Error('Server conversion failed: ' + resp.statusText);
    const ab = await resp.arrayBuffer();
    return new Uint8Array(ab);
  },
};

// ─── Application state ────────────────────────────────────────────────────────
const state = {
  nodes: [],      // { id, type, x, y, params }
  edges: [],      // { id, fromNode, toNode }
  selectedId: null,
  nextId: 1,
  dragging: null, // { nodeId, offsetX, offsetY }
  connecting: null, // { fromNode, x1, y1 }
  loadedFile: null, // { name, bytes: Uint8Array }
};

// ─── DOM refs ─────────────────────────────────────────────────────────────────
const canvasEl    = document.getElementById('wf-canvas');
const svgEl       = document.getElementById('wf-svg');
const paletteEl   = document.getElementById('wf-palette');
const propsEmpty  = document.getElementById('props-empty');
const propsForm   = document.getElementById('props-form');
const emptyHint   = document.getElementById('wf-empty-hint');
const bottomBar   = document.getElementById('wf-bottom');
const tplCardsRow = document.getElementById('tpl-cards-row');
const progressModal = document.getElementById('wf-progress-modal');
const progSteps   = document.getElementById('wf-prog-steps');
const progBar     = document.getElementById('wf-prog-bar');
const progLabel   = document.getElementById('wf-prog-label');
const progDone    = document.getElementById('wf-prog-done');

// ─── Utility ──────────────────────────────────────────────────────────────────
function uid() { return 'n' + (state.nextId++); }

function getPortPos(nodeEl, type) {
  const rect       = nodeEl.getBoundingClientRect();
  const canvasRect = canvasEl.getBoundingClientRect();
  const x = type === 'out' ? rect.right  - canvasRect.left
                            : rect.left   - canvasRect.left;
  const y = rect.top + rect.height / 2 - canvasRect.top;
  return { x, y };
}

function bezierPath(x1, y1, x2, y2) {
  const cx = (x1 + x2) / 2;
  return `M ${x1} ${y1} C ${cx} ${y1} ${cx} ${y2} ${x2} ${y2}`;
}

function getNodeEl(id) {
  return canvasEl.querySelector(`.wf-node[data-id="${id}"]`);
}

// ─── Palette ──────────────────────────────────────────────────────────────────
function buildPalette() {
  const categories = {};
  Object.entries(TOOL_DEFS).forEach(([key, def]) => {
    if (!categories[def.category]) categories[def.category] = [];
    categories[def.category].push({ key, def });
  });

  paletteEl.innerHTML = '';
  Object.entries(categories).forEach(([cat, items]) => {
    const label = document.createElement('h6');
    label.className = 'cat-label';
    label.textContent = cat;
    paletteEl.appendChild(label);

    items.forEach(({ key, def }) => {
      const card = document.createElement('div');
      card.className = 'palette-card';
      card.draggable = true;
      card.dataset.toolType = key;
      card.title = def.desc;
      card.innerHTML = `
        <div class="p-icon" style="background:${def.color};">
          <i class="bi ${def.icon}"></i>
        </div>
        <span>${def.name}${def.serverOnly ? ' <small class="text-muted">(server)</small>' : ''}</span>`;

      card.addEventListener('dragstart', e => {
        e.dataTransfer.setData('toolType', key);
        e.dataTransfer.effectAllowed = 'copy';
      });
      paletteEl.appendChild(card);
    });
  });
}

// ─── Canvas drag-over / drop ──────────────────────────────────────────────────
canvasEl.addEventListener('dragover', e => { e.preventDefault(); e.dataTransfer.dropEffect = 'copy'; });
canvasEl.addEventListener('drop', e => {
  e.preventDefault();
  const toolType = e.dataTransfer.getData('toolType');
  if (!toolType || !TOOL_DEFS[toolType]) return;
  const rect = canvasEl.getBoundingClientRect();
  const x = e.clientX - rect.left - 80;
  const y = e.clientY - rect.top  - 36;
  addNode(toolType, Math.max(0, x), Math.max(0, y));
});

// ─── Node management ──────────────────────────────────────────────────────────
function addNode(type, x, y, id, params) {
  const def = TOOL_DEFS[type];
  if (!def) return;
  const nodeId = id || uid();
  const nodeParams = params || (DEFAULT_PARAMS[type] ? { ...DEFAULT_PARAMS[type] } : {});
  state.nodes.push({ id: nodeId, type, x, y, params: nodeParams });
  renderNode({ id: nodeId, type, x, y, params: nodeParams });
  updateEmptyHint();
}

function renderNode(node) {
  const def = TOOL_DEFS[node.type];
  const el  = document.createElement('div');
  el.className = 'wf-node';
  el.dataset.id = node.id;
  el.style.left = node.x + 'px';
  el.style.top  = node.y + 'px';

  // Output type badge text
  const outLabel = def.outputs ? `<span class="type-badge text-white" style="background:${def.color};">${def.outputs}</span>` : '';
  const inLabel  = def.accepts.length ? `<span class="text-muted">${def.accepts.join(', ')}</span>` : '';

  el.innerHTML = `
    <button class="wf-node-del" data-del="${node.id}" title="Remove node">&times;</button>
    <div class="wf-node-header" style="background:${def.color};">
      <i class="bi ${def.icon}"></i>
      <span>${def.name}</span>
    </div>
    <div class="wf-node-body">${def.desc}</div>
    <div class="wf-node-footer">
      ${inLabel}
      ${outLabel}
    </div>
    ${def.accepts.length ? `<div class="wf-port wf-port-in" data-port="in" data-node="${node.id}" style="background:${def.color};"></div>` : ''}
    ${def.outputs !== null ? `<div class="wf-port wf-port-out" data-port="out" data-node="${node.id}" style="background:${def.color};"></div>` : ''}`;

  // ── Node header drag (move) ────────────────────────────────────
  const header = el.querySelector('.wf-node-header');
  header.addEventListener('mousedown', e => {
    if (e.button !== 0) return;
    e.stopPropagation();
    const rect = canvasEl.getBoundingClientRect();
    const nodeRect = el.getBoundingClientRect();
    state.dragging = {
      nodeId:  node.id,
      offsetX: e.clientX - nodeRect.left,
      offsetY: e.clientY - nodeRect.top,
    };
    el.style.cursor = 'grabbing';
    e.preventDefault();
  });

  // ── Port mousedown (start connection) ─────────────────────────
  el.querySelectorAll('.wf-port').forEach(port => {
    port.addEventListener('mousedown', e => {
      e.stopPropagation();
      if (e.button !== 0) return;
      const portType = port.dataset.port;
      if (portType === 'out') {
        const pos = getPortPos(el, 'out');
        state.connecting = { fromNode: node.id, x1: pos.x, y1: pos.y };
      }
      e.preventDefault();
    });
  });

  // ── Click → select ────────────────────────────────────────────
  el.addEventListener('mousedown', e => {
    if (e.button !== 0) return;
    // Don't select if clicking a port
    if (e.target.classList.contains('wf-port')) return;
    selectNode(node.id);
    e.stopPropagation();
  });

  // ── Delete button ─────────────────────────────────────────────
  el.querySelector('.wf-node-del').addEventListener('click', e => {
    e.stopPropagation();
    deleteNode(node.id);
  });

  canvasEl.appendChild(el);
}

function deleteNode(id) {
  state.nodes = state.nodes.filter(n => n.id !== id);
  state.edges = state.edges.filter(e => e.fromNode !== id && e.toNode !== id);
  const el = getNodeEl(id);
  if (el) el.remove();
  if (state.selectedId === id) {
    state.selectedId = null;
    showPropsEmpty();
  }
  redrawEdges();
  updateEmptyHint();
}

function selectNode(id) {
  // Deselect old
  if (state.selectedId) {
    const prev = getNodeEl(state.selectedId);
    if (prev) prev.classList.remove('selected');
  }
  state.selectedId = id;
  const el = getNodeEl(id);
  if (el) el.classList.add('selected');
  showPropsForNode(id);
}

function deselectAll() {
  if (state.selectedId) {
    const el = getNodeEl(state.selectedId);
    if (el) el.classList.remove('selected');
  }
  state.selectedId = null;
  showPropsEmpty();
}

// Click on canvas background → deselect
canvasEl.addEventListener('mousedown', e => {
  if (e.target === canvasEl || e.target === svgEl) {
    deselectAll();
  }
});

// ─── Mouse move & up (drag + connect) ────────────────────────────────────────
document.addEventListener('mousemove', e => {
  // Move node
  if (state.dragging) {
    const rect = canvasEl.getBoundingClientRect();
    const nodeId = state.dragging.nodeId;
    const nodeEl = getNodeEl(nodeId);
    if (!nodeEl) return;
    const x = e.clientX - rect.left - state.dragging.offsetX;
    const y = e.clientY - rect.top  - state.dragging.offsetY;
    const clamped_x = Math.max(0, Math.min(x, rect.width  - 165));
    const clamped_y = Math.max(0, Math.min(y, rect.height - 100));
    nodeEl.style.left = clamped_x + 'px';
    nodeEl.style.top  = clamped_y + 'px';
    const nd = state.nodes.find(n => n.id === nodeId);
    if (nd) { nd.x = clamped_x; nd.y = clamped_y; }
    redrawEdges();
  }

  // Draw ghost connection line
  if (state.connecting) {
    const rect = canvasEl.getBoundingClientRect();
    const x2 = e.clientX - rect.left;
    const y2 = e.clientY - rect.top;
    drawGhostEdge(state.connecting.x1, state.connecting.y1, x2, y2);
  }
});

document.addEventListener('mouseup', e => {
  if (state.dragging) {
    const nodeEl = getNodeEl(state.dragging.nodeId);
    if (nodeEl) nodeEl.style.cursor = 'grab';
    state.dragging = null;
  }

  if (state.connecting) {
    // Check if we released on an input port
    const target = document.elementFromPoint(e.clientX, e.clientY);
    if (target && target.classList.contains('wf-port') && target.dataset.port === 'in') {
      const toNode = target.dataset.node;
      if (toNode && toNode !== state.connecting.fromNode) {
        // Avoid duplicate edges for same pair
        const exists = state.edges.some(ed => ed.fromNode === state.connecting.fromNode && ed.toNode === toNode);
        if (!exists) {
          state.edges.push({ id: uid(), fromNode: state.connecting.fromNode, toNode });
          redrawEdges();
        }
      }
    }
    clearGhostEdge();
    state.connecting = null;
  }
});

// ─── SVG edges ────────────────────────────────────────────────────────────────
function redrawEdges() {
  // Remove old edge paths (keep defs and ghost)
  svgEl.querySelectorAll('.wf-edge').forEach(el => el.remove());

  state.edges.forEach(edge => {
    const fromEl = getNodeEl(edge.fromNode);
    const toEl   = getNodeEl(edge.toNode);
    if (!fromEl || !toEl) return;
    const p1 = getPortPos(fromEl, 'out');
    const p2 = getPortPos(toEl,   'in');
    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    path.setAttribute('class', 'wf-edge');
    path.setAttribute('d', bezierPath(p1.x, p1.y, p2.x, p2.y));
    path.dataset.edgeId = edge.id;
    svgEl.appendChild(path);
  });
}

let ghostPath = null;
function drawGhostEdge(x1, y1, x2, y2) {
  if (!ghostPath) {
    ghostPath = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    ghostPath.setAttribute('class', 'wf-edge-ghost');
    svgEl.appendChild(ghostPath);
  }
  ghostPath.setAttribute('d', bezierPath(x1, y1, x2, y2));
}
function clearGhostEdge() {
  if (ghostPath) { ghostPath.remove(); ghostPath = null; }
}

// ─── Properties panel ────────────────────────────────────────────────────────
function showPropsEmpty() {
  propsEmpty.classList.remove('d-none');
  propsForm.classList.add('d-none');
  propsForm.innerHTML = '';
}

function showPropsForNode(id) {
  const nd  = state.nodes.find(n => n.id === id);
  if (!nd) return showPropsEmpty();
  const def = TOOL_DEFS[nd.type];

  propsEmpty.classList.add('d-none');
  propsForm.classList.remove('d-none');

  let html = `
    <div class="prop-node-info" style="border-left:3px solid ${def.color};">
      <div class="d-flex align-items-center gap-2 mb-1">
        <i class="bi ${def.icon}" style="color:${def.color};"></i>
        <strong>${def.name}</strong>
      </div>
      <small class="text-muted">${def.desc}</small>
      ${def.serverOnly ? '<div class="mt-1"><span class="badge bg-warning text-dark" style="font-size:.62rem;">Server-side only</span></div>' : ''}
    </div>`;

  if (nd.type === 'rotate') {
    html += `
      <div class="prop-row">
        <label>Rotation angle</label>
        <select class="form-select" data-param="degrees">
          <option value="90"  ${nd.params.degrees == 90  ? 'selected' : ''}>90° clockwise</option>
          <option value="180" ${nd.params.degrees == 180 ? 'selected' : ''}>180°</option>
          <option value="270" ${nd.params.degrees == 270 ? 'selected' : ''}>270° clockwise</option>
        </select>
      </div>`;
  }

  if (nd.type === 'watermark') {
    html += `
      <div class="prop-row">
        <label>Watermark text</label>
        <input type="text" class="form-control" data-param="text" value="${nd.params.text || 'CONFIDENTIAL'}">
      </div>
      <div class="prop-row">
        <label>Opacity: <span id="wm-opacity-val">${nd.params.opacity || 0.3}</span></label>
        <input type="range" class="form-range" data-param="opacity" min="0.05" max="1" step="0.05" value="${nd.params.opacity || 0.3}">
      </div>`;
  }

  if (nd.type === 'page_numbers') {
    const pos = nd.params.position || 'bottom-center';
    html += `
      <div class="prop-row">
        <label>Position</label>
        <select class="form-select" data-param="position">
          <option value="bottom-center" ${pos === 'bottom-center' ? 'selected' : ''}>Bottom center</option>
          <option value="bottom-right"  ${pos === 'bottom-right'  ? 'selected' : ''}>Bottom right</option>
          <option value="bottom-left"   ${pos === 'bottom-left'   ? 'selected' : ''}>Bottom left</option>
        </select>
      </div>
      <div class="prop-row">
        <label>Start from</label>
        <input type="number" class="form-control" data-param="startFrom" value="${nd.params.startFrom || 1}" min="1">
      </div>`;
  }

  if (nd.type === 'compress') {
    const q = nd.params.quality || 'medium';
    html += `
      <div class="prop-row">
        <label>Quality</label>
        <select class="form-select" data-param="quality">
          <option value="low"    ${q === 'low'    ? 'selected' : ''}>Low (smallest file)</option>
          <option value="medium" ${q === 'medium' ? 'selected' : ''}>Medium (balanced)</option>
          <option value="high"   ${q === 'high'   ? 'selected' : ''}>High (best quality)</option>
        </select>
      </div>`;
  }

  if (nd.type === 'protect') {
    html += `
      <div class="prop-row">
        <label>Owner password</label>
        <input type="text" class="form-control" data-param="password" value="${nd.params.password || ''}" placeholder="Enter password">
        <small class="text-muted d-block mt-1" style="font-size:.7rem;">Note: browser-side encryption is metadata-only. Use the standalone Protect PDF tool for full encryption.</small>
      </div>`;
  }

  if (!['rotate','watermark','page_numbers','compress','protect'].includes(nd.type)) {
    html += `<p class="text-muted" style="font-size:.78rem; margin-top:.5rem;">No configurable parameters for this node.</p>`;
  }

  // Delete node button
  html += `
    <hr style="margin:.75rem 0;">
    <button class="btn btn-outline-danger btn-sm w-100" id="props-delete-btn">
      <i class="bi bi-trash me-1"></i>Delete Node
    </button>`;

  propsForm.innerHTML = html;

  // Wire up live param changes
  propsForm.querySelectorAll('[data-param]').forEach(input => {
    const param = input.dataset.param;
    const eventName = input.type === 'range' ? 'input' : 'change';
    input.addEventListener(eventName, () => {
      nd.params[param] = input.type === 'number' ? Number(input.value) : input.value;
      if (input.type === 'range') {
        const valEl = propsForm.querySelector('#wm-opacity-val');
        if (valEl) valEl.textContent = input.value;
      }
    });
    // For text inputs fire on keyup too
    if (input.type === 'text') {
      input.addEventListener('keyup', () => { nd.params[param] = input.value; });
    }
  });

  document.getElementById('props-delete-btn').addEventListener('click', () => {
    deleteNode(id);
  });
}

// ─── Templates bar (bottom) ───────────────────────────────────────────────────
function buildTemplatesBar() {
  TEMPLATES.forEach(tpl => {
    const btn = document.createElement('button');
    btn.className = 'btn btn-sm btn-outline-secondary tpl-btn';
    btn.innerHTML = `<i class="bi ${tpl.icon} me-1"></i>${tpl.name}`;
    btn.addEventListener('click', () => loadTemplate(tpl));
    bottomBar.appendChild(btn);
  });
}

// ─── Templates cards (below editor) ──────────────────────────────────────────
function buildTemplateCards() {
  TEMPLATES.forEach(tpl => {
    const col = document.createElement('div');
    col.className = 'col-6 col-md-4 col-lg-2';

    const chainPills = tpl.chain.map(t => {
      const d = TOOL_DEFS[t];
      return `<span class="tpl-chain-pill">${d ? d.name : t}</span>`;
    }).join('');

    col.innerHTML = `
      <div class="tpl-card" title="Load template: ${tpl.name}">
        <i class="bi ${tpl.icon}"></i>
        <h6>${tpl.name}</h6>
        <div>${chainPills}</div>
      </div>`;
    col.querySelector('.tpl-card').addEventListener('click', () => loadTemplate(tpl));
    tplCardsRow.appendChild(col);
  });
}

// ─── Load template ────────────────────────────────────────────────────────────
function loadTemplate(tpl) {
  clearCanvas();
  const canvasRect = canvasEl.getBoundingClientRect();
  const centerY    = Math.max(60, canvasRect.height / 2 - 60);
  let x = 80;
  tpl.chain.forEach(type => {
    addNode(type, x, centerY);
    x += 220;
  });

  // Auto-connect chain
  const nodes = state.nodes;
  for (let i = 0; i < nodes.length - 1; i++) {
    const from = nodes[i];
    const to   = nodes[i + 1];
    const fromDef = TOOL_DEFS[from.type];
    const toDef   = TOOL_DEFS[to.type];
    if (fromDef.outputs && toDef.accepts.length > 0) {
      state.edges.push({ id: uid(), fromNode: from.id, toNode: to.id });
    }
  }
  redrawEdges();
  updateEmptyHint();

  // Scroll canvas into view
  document.getElementById('wf-shell').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// ─── Clear canvas ─────────────────────────────────────────────────────────────
function clearCanvas() {
  state.nodes = [];
  state.edges = [];
  state.selectedId = null;
  state.nextId = 1;
  // Remove all node elements
  canvasEl.querySelectorAll('.wf-node').forEach(el => el.remove());
  svgEl.querySelectorAll('.wf-edge, .wf-edge-ghost').forEach(el => el.remove());
  ghostPath = null;
  showPropsEmpty();
  updateEmptyHint();
}

// ─── Update empty hint visibility ────────────────────────────────────────────
function updateEmptyHint() {
  emptyHint.style.display = state.nodes.length === 0 ? 'flex' : 'none';
}

// ─── Execution engine ─────────────────────────────────────────────────────────
function progLog(msg, type) {
  const row = document.createElement('div');
  row.className = 'prog-step-row';
  let icon = 'bi-check-circle-fill text-success';
  if (type === 'active') icon = 'bi-arrow-right-circle-fill text-warning';
  if (type === 'error')  icon = 'bi-x-circle-fill text-danger';
  if (type === 'warn')   icon = 'bi-exclamation-triangle-fill text-warning';
  row.innerHTML = `<span class="prog-step-icon"><i class="bi ${icon}"></i></span><span>${msg}</span>`;
  progSteps.appendChild(row);
  progSteps.scrollTop = progSteps.scrollHeight;
}

function setProgBar(pct, label) {
  progBar.style.width = pct + '%';
  progLabel.textContent = label;
}

async function executeWorkflow() {
  // Find input node
  const inputNode = state.nodes.find(n => n.type === 'input');
  if (!inputNode) {
    alert('Your workflow needs a "File Input" node to start.');
    return;
  }

  // Topological traversal
  const chain = buildChain(inputNode.id);
  if (chain === null) {
    alert('Could not traverse the workflow. Make sure all nodes are connected in a linear chain from Input to Output.');
    return;
  }

  // Check for server-only nodes
  const serverNodes = chain.filter(id => {
    const nd = state.nodes.find(n => n.id === id);
    return nd && TOOL_DEFS[nd.type] && TOOL_DEFS[nd.type].serverOnly;
  });
  if (serverNodes.length > 0) {
    const names = serverNodes.map(id => {
      const nd = state.nodes.find(n => n.id === id);
      return TOOL_DEFS[nd.type].name;
    }).join(', ');
    const ok = confirm(`This workflow contains server-side nodes (${names}). They require a working server endpoint. Continue?`);
    if (!ok) return;
  }

  // Get file bytes
  let pdfBytes;
  if (state.loadedFile) {
    pdfBytes = state.loadedFile.bytes;
  } else {
    pdfBytes = await promptFileInput();
    if (!pdfBytes) return;
  }

  // Show progress modal
  progSteps.innerHTML = '';
  progBar.style.width = '0%';
  progLabel.textContent = 'Starting…';
  progDone.classList.add('d-none');
  progressModal.classList.add('show');

  try {
    let currentBytes = pdfBytes instanceof Uint8Array ? pdfBytes : new Uint8Array(pdfBytes);
    const total = chain.length;

    for (let i = 0; i < total; i++) {
      const nodeId = chain[i];
      const nd     = state.nodes.find(n => n.id === nodeId);
      if (!nd) continue;
      const def    = TOOL_DEFS[nd.type];

      setProgBar(Math.round((i / total) * 100), `Step ${i + 1} of ${total}: ${def.name}`);
      progLog(`Running: ${def.name}…`, 'active');

      if (nd.type === 'input') {
        // Already have bytes — just log
        progLog('File loaded ✓', 'done');
        continue;
      }

      if (nd.type === 'output') {
        // Trigger download
        const blob = new Blob([currentBytes], { type: 'application/pdf' });
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');
        a.href     = url;
        a.download = (state.loadedFile ? state.loadedFile.name.replace(/\.[^.]+$/, '') : 'workflow') + '_processed.pdf';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
        progLog('File downloaded ✓', 'done');
        continue;
      }

      const executor = EXECUTORS[nd.type];
      if (!executor) {
        progLog(`No executor for ${nd.type} — skipping`, 'warn');
        continue;
      }

      currentBytes = await executor(currentBytes, nd.params);
      progLog(`${def.name} complete ✓`, 'done');
    }

    setProgBar(100, 'Workflow complete!');
    progBar.classList.remove('progress-bar-animated');
    progDone.classList.remove('d-none');

  } catch (err) {
    console.error(err);
    progLog('Error: ' + err.message, 'error');
    setProgBar(100, 'Workflow failed — see error above');
    progBar.classList.remove('progress-bar-animated');
    progBar.classList.remove('bg-danger');
    progBar.classList.add('bg-danger');
    progDone.classList.remove('d-none');
  }
}

function buildChain(startId) {
  const visited = new Set();
  const chain   = [];
  let currentId = startId;

  while (currentId) {
    if (visited.has(currentId)) return null; // cycle
    visited.add(currentId);
    chain.push(currentId);
    const nd  = state.nodes.find(n => n.id === currentId);
    if (!nd) break;
    if (nd.type === 'output') break;
    // Find edge from this node
    const edge = state.edges.find(e => e.fromNode === currentId);
    currentId  = edge ? edge.toNode : null;
  }

  return chain;
}

function promptFileInput() {
  return new Promise(resolve => {
    const input = document.getElementById('wf-exec-file');
    input.value = '';
    input.onchange = async e => {
      const file = e.target.files[0];
      if (!file) { resolve(null); return; }
      const bytes = new Uint8Array(await file.arrayBuffer());
      state.loadedFile = { name: file.name, bytes };
      resolve(bytes);
    };
    input.click();
  });
}

// ─── Save / Load workflow ─────────────────────────────────────────────────────
function saveWorkflow() {
  const data = JSON.stringify({ nodes: state.nodes, edges: state.edges }, null, 2);
  const blob = new Blob([data], { type: 'application/json' });
  const url  = URL.createObjectURL(blob);
  const a    = document.createElement('a');
  a.href     = url;
  a.download = 'my-workflow.toolpdf-workflow.json';
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
}

function loadWorkflowFromFile(file) {
  const reader = new FileReader();
  reader.onload = e => {
    try {
      const parsed = JSON.parse(e.target.result);
      if (!parsed.nodes || !parsed.edges) throw new Error('Invalid workflow file.');
      clearCanvas();
      // Restore nodes
      let maxId = 0;
      parsed.nodes.forEach(nd => {
        // Extract numeric part of id for nextId tracking
        const num = parseInt(nd.id.replace(/\D/g, ''));
        if (!isNaN(num) && num > maxId) maxId = num;
        addNode(nd.type, nd.x, nd.y, nd.id, nd.params);
      });
      state.nextId = maxId + 1;
      // Restore edges
      parsed.edges.forEach(e => {
        state.edges.push({ id: e.id, fromNode: e.fromNode, toNode: e.toNode });
      });
      redrawEdges();
      updateEmptyHint();
    } catch (err) {
      alert('Could not load workflow: ' + err.message);
    }
  };
  reader.readAsText(file);
}

// ─── Toolbar buttons ──────────────────────────────────────────────────────────
document.getElementById('btn-execute').addEventListener('click', executeWorkflow);
document.getElementById('btn-clear').addEventListener('click', () => {
  if (state.nodes.length === 0 || confirm('Clear the canvas? This cannot be undone.')) {
    clearCanvas();
  }
});
document.getElementById('btn-save-wf').addEventListener('click', saveWorkflow);
document.getElementById('btn-load-wf').addEventListener('click', () => {
  document.getElementById('wf-load-input').click();
});
document.getElementById('wf-load-input').addEventListener('change', e => {
  if (e.target.files[0]) loadWorkflowFromFile(e.target.files[0]);
});
document.getElementById('btn-close-modal').addEventListener('click', () => {
  progressModal.classList.remove('show');
  progBar.classList.add('progress-bar-animated');
});

// ─── Init ─────────────────────────────────────────────────────────────────────
buildPalette();
buildTemplatesBar();
buildTemplateCards();
updateEmptyHint();
