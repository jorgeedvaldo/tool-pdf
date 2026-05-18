import { formatBytes } from './utils.js';
import { renderOverlayToContainer } from './overlay.js';

// ─── DOM references ────────────────────────────────────────────────────────
function $id(id) { return document.getElementById(id); }

// ─── Public API ────────────────────────────────────────────────────────────

export function initUI(state, handlers) {
    _setupDropZones(state, handlers);
    _setupCompareButton(state, handlers);
    _setupCancelButton(handlers);
    _setupTabs(state, handlers);
    _setupOverlayControls(state);
    _setupExportButtons(handlers);
    _setupOCRToggle(state);
    _setupThresholdSlider(state);
    _setupNavButtons(handlers);
    _setupZoomControls(state);
}

export function updateProgress({ message = '', percent = 0, current = 0, total = 0, error = false }) {
    const area = $id('cmp-progress-area');
    if (area) area.classList.remove('d-none');

    const bar = $id('cmp-progress-bar');
    if (bar) {
        bar.style.width = `${percent}%`;
        bar.className = `progress-bar progress-bar-striped${error ? ' bg-danger' : ' bg-danger progress-bar-animated'}`;
    }

    const msg = $id('cmp-progress-message');
    if (msg) msg.textContent = message;

    const detail = $id('cmp-progress-detail');
    if (detail && total) detail.textContent = `Page ${current} of ${total}`;
}

export function showResults(state) {
    const area = $id('cmp-progress-area');
    if (area) area.classList.add('d-none');

    const results = $id('cmp-results-area');
    if (results) results.classList.remove('d-none');

    _updateSidebar(state);
    _renderCurrentPage(state);
}

export function updatePageView(state) {
    _renderCurrentPage(state);
    _updatePageIndicator(state);
}

// ─── Private helpers ────────────────────────────────────────────────────────

function _setupDropZones(state, handlers) {
    ['original', 'modified'].forEach(type => {
        const zone = $id(`${type}-drop-zone`);
        const input = $id(`${type}-file-input`);
        const removeBtn = $id(`${type}-remove-btn`);

        if (!zone || !input) return;

        zone.addEventListener('click', () => input.click());
        zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('cmp-drag-over'); });
        zone.addEventListener('dragleave', () => zone.classList.remove('cmp-drag-over'));
        zone.addEventListener('drop', e => {
            e.preventDefault();
            zone.classList.remove('cmp-drag-over');
            const file = e.dataTransfer.files[0];
            if (file) _handleFileSelect(type, file, state);
        });
        input.addEventListener('change', e => {
            if (e.target.files[0]) _handleFileSelect(type, e.target.files[0], state);
        });
        removeBtn?.addEventListener('click', () => _clearFile(type, state));
    });
}

function _handleFileSelect(type, file, state) {
    if (file.type !== 'application/pdf') {
        _showFileError(type, 'Please select a valid PDF file.');
        return;
    }

    const maxBytes = 150 * 1024 * 1024;
    if (file.size > maxBytes) {
        _showFileError(type, `File too large (max 150 MB). Your file: ${formatBytes(file.size)}.`);
        return;
    }

    state[`${type}File`] = file;
    _renderFileCard(type, file);
    _updateCompareButton(state);
}

function _renderFileCard(type, file) {
    const zone = $id(`${type}-drop-zone`);
    const card = $id(`${type}-file-card`);
    const nameEl = $id(`${type}-file-name`);
    const sizeEl = $id(`${type}-file-size`);

    if (zone) zone.classList.add('d-none');
    if (card) card.classList.remove('d-none');
    if (nameEl) nameEl.textContent = file.name;
    if (sizeEl) sizeEl.textContent = formatBytes(file.size);
}

function _clearFile(type, state) {
    state[`${type}File`] = null;
    state[`${type}Pdf`] = null;

    const zone = $id(`${type}-drop-zone`);
    const card = $id(`${type}-file-card`);
    const input = $id(`${type}-file-input`);

    if (zone) zone.classList.remove('d-none');
    if (card) card.classList.add('d-none');
    if (input) input.value = '';

    _updateCompareButton(state);
}

function _showFileError(type, msg) {
    const el = $id(`${type}-file-error`);
    if (el) { el.textContent = msg; el.classList.remove('d-none'); }
    setTimeout(() => el?.classList.add('d-none'), 5000);
}

function _updateCompareButton(state) {
    const btn = $id('cmp-compare-btn');
    if (btn) btn.disabled = !(state.originalFile && state.modifiedFile);
}

function _setupCompareButton(state, handlers) {
    $id('cmp-compare-btn')?.addEventListener('click', () => {
        if (handlers.onCompare) handlers.onCompare();
    });
}

function _setupCancelButton(handlers) {
    $id('cmp-cancel-btn')?.addEventListener('click', () => {
        if (handlers.onCancel) handlers.onCancel();
    });
}

function _setupTabs(state, handlers) {
    document.querySelectorAll('[data-cmp-tab]').forEach(tab => {
        tab.addEventListener('click', () => {
            const mode = tab.dataset.cmpTab;
            state.mode = mode;

            document.querySelectorAll('[data-cmp-tab]').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');

            document.querySelectorAll('[data-cmp-panel]').forEach(p => p.classList.add('d-none'));
            $id(`cmp-panel-${mode}`)?.classList.remove('d-none');

            if (handlers.onModeChange) handlers.onModeChange(mode);
        });
    });
}

function _setupOverlayControls(state) {
    const opacityA = $id('cmp-opacity-a');
    const opacityB = $id('cmp-opacity-b');

    const refresh = () => {
        const page = state.results.pages[state.currentPage - 1];
        if (!page) return;
        renderOverlayToContainer(
            $id('cmp-overlay-container'),
            page.visual?.originalCanvas,
            page.visual?.modifiedCanvas,
            {
                opacityA: parseFloat(opacityA?.value ?? 0.5),
                opacityB: parseFloat(opacityB?.value ?? 0.5),
            }
        );
    };

    opacityA?.addEventListener('input', refresh);
    opacityB?.addEventListener('input', refresh);
}

function _setupExportButtons(handlers) {
    $id('cmp-export-json')?.addEventListener('click', () => handlers.onExport?.('json'));
    $id('cmp-export-html')?.addEventListener('click', () => handlers.onExport?.('html'));
}

function _setupOCRToggle(state) {
    $id('cmp-ocr-toggle')?.addEventListener('change', e => {
        state.useOCR = e.target.checked;
        $id('cmp-ocr-warning')?.classList.toggle('d-none', !state.useOCR);
    });
}

function _setupThresholdSlider(state) {
    const slider = $id('cmp-threshold-slider');
    const label = $id('cmp-threshold-label');
    if (!slider) return;

    slider.addEventListener('input', () => {
        state.threshold = parseFloat(slider.value);
        if (label) label.textContent = `${Math.round(state.threshold * 100)}%`;
    });
}

function _setupNavButtons(handlers) {
    $id('cmp-prev-page')?.addEventListener('click', () => handlers.onPageChange?.('prev'));
    $id('cmp-next-page')?.addEventListener('click', () => handlers.onPageChange?.('next'));
    $id('cmp-next-diff')?.addEventListener('click', () => handlers.onPageChange?.('next-diff'));
}

function _setupZoomControls(state) {
    $id('cmp-zoom-in')?.addEventListener('click', () => {
        state.scale = Math.min(state.scale + 0.25, 3);
        _applyZoom(state.scale);
    });
    $id('cmp-zoom-out')?.addEventListener('click', () => {
        state.scale = Math.max(state.scale - 0.25, 0.5);
        _applyZoom(state.scale);
    });
    $id('cmp-zoom-reset')?.addEventListener('click', () => {
        state.scale = 1.5;
        _applyZoom(state.scale);
    });
}

function _applyZoom(scale) {
    const label = $id('cmp-zoom-label');
    if (label) label.textContent = `${Math.round(scale * 100)}%`;
}

function _updateSidebar(state) {
    const { summary, pages } = state.results;
    if (!summary) return;

    _setText('cmp-stat-total', summary.totalPages);
    _setText('cmp-stat-changed', summary.changedPages);
    _setText('cmp-stat-unchanged', summary.unchangedPages);
    _setText('cmp-stat-added', summary.addedPages);
    _setText('cmp-stat-removed', summary.removedPages);
    _setText('cmp-stat-avgdiff', `${summary.avgDiffPercentage.toFixed(1)}%`);

    const list = $id('cmp-pages-list');
    if (!list) return;

    list.innerHTML = pages.map((p, idx) => {
        const icons = { changed: '🔴', unchanged: '🟢', added: '🔵', removed: '🟡' };
        return `<li class="list-group-item list-group-item-action d-flex justify-content-between align-items-center cmp-page-item${p.status === 'unchanged' ? ' text-muted' : ''}" data-page="${idx + 1}" style="cursor:pointer">
            <span>${icons[p.status] ?? '⚪'} Page ${p.pageNumber}</span>
            <small>${p.status !== 'unchanged' ? `${(p.visual?.diffPercentage ?? 0).toFixed(1)}%` : ''}</small>
        </li>`;
    }).join('');

    list.querySelectorAll('.cmp-page-item').forEach(item => {
        item.addEventListener('click', () => {
            const pg = parseInt(item.dataset.page, 10);
            state.currentPage = pg;
            updatePageView(state);
        });
    });
}

function _renderCurrentPage(state) {
    const { pages, summary } = state.results;
    if (!pages.length) return;

    const pageData = pages[state.currentPage - 1];
    if (!pageData) return;

    _updatePageIndicator(state);

    if (state.mode === 'visual') _renderVisual(pageData);
    else if (state.mode === 'text') _renderText(pageData);
    else if (state.mode === 'overlay') _renderOverlay(pageData);
    else if (state.mode === 'report') _renderReportTab(state);
}

function _renderVisual(pageData) {
    const origContainer = $id('cmp-visual-original');
    const modContainer = $id('cmp-visual-modified');
    const diffContainer = $id('cmp-visual-diff');

    _canvasToContainer(origContainer, pageData.visual?.originalCanvas);
    _canvasToContainer(modContainer, pageData.visual?.modifiedCanvas);
    _canvasToContainer(diffContainer, pageData.visual?.diffCanvas);

    const badge = $id('cmp-visual-diff-pct');
    if (badge) badge.textContent = `${(pageData.visual?.diffPercentage ?? 0).toFixed(2)}% diff`;

    const statusBadge = $id('cmp-page-status');
    if (statusBadge) {
        statusBadge.textContent = pageData.status;
        statusBadge.className = `badge ms-2 bg-${_statusColor(pageData.status)}`;
    }
}

function _renderText(pageData) {
    const container = $id('cmp-text-diff-content');
    if (!container) return;

    if (!pageData.text?.htmlDiff) {
        container.innerHTML = '<p class="text-muted">No text extracted for this page.</p>';
        return;
    }

    container.innerHTML = `<div class="cmp-text-diff-wrap">${pageData.text.htmlDiff}</div>
        <div class="mt-3 small text-muted">
            <span class="badge bg-success me-1">+${pageData.text.addedWords} words added</span>
            <span class="badge bg-danger">-${pageData.text.removedWords} words removed</span>
        </div>`;
}

function _renderOverlay(pageData) {
    renderOverlayToContainer(
        $id('cmp-overlay-container'),
        pageData.visual?.originalCanvas,
        pageData.visual?.modifiedCanvas,
        {
            opacityA: parseFloat($id('cmp-opacity-a')?.value ?? 0.5),
            opacityB: parseFloat($id('cmp-opacity-b')?.value ?? 0.5),
        }
    );
}

function _renderReportTab(state) {
    const el = $id('cmp-report-summary');
    if (!el) return;
    const s = state.results.summary ?? {};
    el.innerHTML = `
        <div class="row g-3">
            <div class="col-6 col-md-3"><div class="cmp-stat-box"><div class="cmp-stat-num">${s.totalPages ?? 0}</div><div>Total Pages</div></div></div>
            <div class="col-6 col-md-3"><div class="cmp-stat-box cmp-stat-changed"><div class="cmp-stat-num">${s.changedPages ?? 0}</div><div>Changed</div></div></div>
            <div class="col-6 col-md-3"><div class="cmp-stat-box cmp-stat-ok"><div class="cmp-stat-num">${s.unchangedPages ?? 0}</div><div>Unchanged</div></div></div>
            <div class="col-6 col-md-3"><div class="cmp-stat-box"><div class="cmp-stat-num">${(s.avgDiffPercentage ?? 0).toFixed(1)}%</div><div>Avg. Diff</div></div></div>
        </div>`;
}

function _updatePageIndicator(state) {
    const total = state.results.pages.length;
    _setText('cmp-page-current', state.currentPage);
    _setText('cmp-page-total', total);

    const prevBtn = $id('cmp-prev-page');
    const nextBtn = $id('cmp-next-page');
    if (prevBtn) prevBtn.disabled = state.currentPage <= 1;
    if (nextBtn) nextBtn.disabled = state.currentPage >= total;

    // Highlight active item in sidebar list
    document.querySelectorAll('.cmp-page-item').forEach(item => {
        item.classList.toggle('active', parseInt(item.dataset.page, 10) === state.currentPage);
    });
}

function _canvasToContainer(container, canvas) {
    if (!container) return;
    container.innerHTML = '';
    if (!canvas) {
        container.innerHTML = '<div class="text-muted small text-center py-4">Page not present</div>';
        return;
    }
    const img = document.createElement('img');
    img.src = canvas.toDataURL();
    img.style.maxWidth = '100%';
    img.style.height = 'auto';
    img.style.display = 'block';
    container.appendChild(img);
}

function _statusColor(status) {
    return { changed: 'danger', unchanged: 'success', added: 'primary', removed: 'warning' }[status] ?? 'secondary';
}

function _setText(id, value) {
    const el = $id(id);
    if (el) el.textContent = value;
}
