import { formatBytes } from './utils.js';
import { renderOverlayToContainer } from './overlay.js';

// ─── Viewer state ────────────────────────────────────────────────────────────
let _zoom = 1.0;              // CSS width factor relative to canvas native width
let _syncEnabled = true;      // synchronized scroll toggle
let _showOverlay = true;      // diff highlight toggle
let _isSyncing = false;       // prevent scroll event loops
let _changedOnly = false;     // "changed pages only" filter
let _pageObserver = null;     // IntersectionObserver for current-page detection
let _appState = null;         // reference to the global state object

// ─── DOM helper ──────────────────────────────────────────────────────────────
const $id = id => document.getElementById(id);

// ════════════════════════════════════════════════════════════════════════════
// PUBLIC API
// ════════════════════════════════════════════════════════════════════════════

export function initUI(state, handlers) {
    _appState = state;

    _setupDropZones(state);
    _setupCompareButton(state, handlers);
    _setupCancelButton(handlers);
    _setupTabs(state, handlers);
    _setupOverlayControls(state);
    _setupExportButtons(handlers);
    _setupOCRToggle(state);
    _setupThresholdSlider(state);

    // Viewer toolbar (wired once; panels exist from the start in the DOM)
    _setupViewerToolbar(state, handlers);
    _setupSubPanelNav(state);
}

export function updateProgress({ message = '', percent = 0, current = 0, total = 0, error = false }) {
    $id('cmp-progress-area')?.classList.remove('d-none');

    const bar = $id('cmp-progress-bar');
    if (bar) {
        bar.style.width = `${percent}%`;
        bar.className = `progress-bar progress-bar-striped${error ? ' bg-danger' : ' bg-danger progress-bar-animated'}`;
    }
    const msg = $id('cmp-progress-message');
    if (msg) msg.textContent = message;
    const det = $id('cmp-progress-detail');
    if (det) det.textContent = total ? `Page ${current} of ${total}` : '';
}

export function showResults(state) {
    $id('cmp-progress-area')?.classList.add('d-none');
    $id('cmp-results-area')?.classList.remove('d-none');

    // Column header labels
    _setText('cmp-orig-pages-lbl', `${state.originalPdf?.numPages ?? 0} pages`);
    _setText('cmp-mod-pages-lbl',  `${state.modifiedPdf?.numPages ?? 0} pages`);

    _updateSidebar(state);
    _buildThumbnailStrip(state);
    renderContinuousViewer(state);
    _updatePageIndicator(state);
    _updateSubPanelPageIndicators(state);
}

/** Called from index.js after navigatePage / mode change */
export function updatePageView(state) {
    _scrollToPage(state.currentPage);
    _updatePageIndicator(state);
    _updateSubPanelPageIndicators(state);
    _highlightThumbnail(state.currentPage);

    const pageData = state.results.pages[state.currentPage - 1];
    if (!pageData) return;
    _renderText(pageData);
    if (state.mode === 'overlay') _renderOverlay(pageData);
    if (state.mode === 'report')  _renderReportTab(state);
}

// ════════════════════════════════════════════════════════════════════════════
// CONTINUOUS VIEWER
// ════════════════════════════════════════════════════════════════════════════

export function renderContinuousViewer(state) {
    const left  = $id('cmp-panel-left');
    const right = $id('cmp-panel-right');
    if (!left || !right) return;

    left.innerHTML  = '';
    right.innerHTML = '';

    const pages = _visiblePages(state);

    pages.forEach(pageData => {
        left.appendChild(_buildPageBlock(pageData, 'original'));
        right.appendChild(_buildPageBlock(pageData, 'modified'));
    });

    // Wire up sync scroll between the two panels
    _setupSyncScroll(left, right);

    // Wire up IntersectionObserver to track which page is in view
    _setupPageObserver(left, state);

    // Fit content width to available panel space on first render
    requestAnimationFrame(() => _fitWidth(left));
}

function _visiblePages(state) {
    const pages = state.results.pages;
    return _changedOnly ? pages.filter(p => p.status !== 'unchanged') : pages;
}

// ─── Build one page block (left or right panel) ──────────────────────────────
function _buildPageBlock(pageData, side) {
    const canvas = side === 'original'
        ? pageData.visual?.originalCanvas
        : pageData.visual?.modifiedCanvas;
    const diffCanvas = pageData.visual?.diffCanvas;

    const wrap = document.createElement('div');
    wrap.className = `cmp-page-block cmp-status-${pageData.status}`;
    wrap.dataset.page = pageData.pageNumber;

    // Page label above the block
    const lbl = document.createElement('div');
    lbl.className = 'cmp-page-lbl';
    const statusEmoji = { changed: '🔴', added: '🔵', removed: '🟡', unchanged: '🟢' }[pageData.status] ?? '';
    lbl.textContent = `Page ${pageData.pageNumber}  ${statusEmoji} ${pageData.status !== 'unchanged' ? pageData.status : ''}`;
    wrap.appendChild(lbl);

    if (!canvas) {
        const ph = document.createElement('div');
        ph.className = 'cmp-page-placeholder';
        ph.style.width = '400px';
        ph.style.height = '565px';
        ph.textContent = side === 'original' ? '— Not in original —' : '— Not in modified —';
        wrap.appendChild(ph);
        return wrap;
    }

    // Main page image
    const img = document.createElement('img');
    img.className = 'cmp-page-img';
    img.src = canvas.toDataURL('image/jpeg', 0.88);
    img.dataset.nativeWidth = canvas.width;
    img.style.display = 'block';
    img.style.width = `${canvas.width * _zoom}px`;
    img.style.height = 'auto';
    img.style.borderRadius = '2px';
    wrap.appendChild(img);

    // Diff overlay (semi-transparent, shown on top of the page)
    if (diffCanvas && pageData.status !== 'unchanged') {
        const overlay = document.createElement('img');
        overlay.className = 'cmp-diff-overlay';
        overlay.src = diffCanvas.toDataURL();
        overlay.style.display = _showOverlay ? 'block' : 'none';
        wrap.appendChild(overlay);

        // Diff percentage badge (bottom-right corner)
        const badge = document.createElement('div');
        badge.style.cssText = `
            position:absolute;bottom:6px;right:6px;
            background:rgba(229,50,45,.85);color:#fff;
            font-size:10px;font-weight:700;padding:2px 6px;border-radius:10px;
            pointer-events:none;
        `;
        badge.textContent = `${(pageData.visual.diffPercentage ?? 0).toFixed(1)}%`;
        wrap.appendChild(badge);
    }

    return wrap;
}

// ─── Synchronized scroll ─────────────────────────────────────────────────────
function _setupSyncScroll(left, right) {
    const sync = (source, target) => {
        source.addEventListener('scroll', () => {
            if (!_syncEnabled || _isSyncing) return;
            _isSyncing = true;

            const maxSrc = source.scrollHeight - source.clientHeight;
            const maxTgt = target.scrollHeight - target.clientHeight;
            if (maxSrc > 0) {
                target.scrollTop = (source.scrollTop / maxSrc) * maxTgt;
            }

            _isSyncing = false;
        }, { passive: true });
    };

    sync(left, right);
    sync(right, left);
}

// ─── IntersectionObserver — track current page from scroll ───────────────────
function _setupPageObserver(panel, state) {
    if (_pageObserver) _pageObserver.disconnect();

    _pageObserver = new IntersectionObserver(entries => {
        // Find the block closest to the top of the panel viewport
        let best = null, bestTop = Infinity;
        entries.forEach(e => {
            if (e.isIntersecting) {
                const t = Math.abs(e.boundingClientRect.top - panel.getBoundingClientRect().top);
                if (t < bestTop) { bestTop = t; best = e.target; }
            }
        });

        if (!best) return;
        const pg = parseInt(best.dataset.page, 10);
        if (pg && pg !== state.currentPage) {
            state.currentPage = pg;
            _updatePageIndicator(state);
            _updateSubPanelPageIndicators(state);
            _highlightThumbnail(pg);
            _highlightSidebarItem(pg);

            const pageData = state.results.pages[pg - 1];
            if (pageData) {
                _renderText(pageData);
                if (state.mode === 'overlay') _renderOverlay(pageData);
            }
        }
    }, { root: panel, threshold: 0.3 });

    panel.querySelectorAll('.cmp-page-block').forEach(b => _pageObserver.observe(b));
}

// ─── Scroll panels to a given page ───────────────────────────────────────────
function _scrollToPage(pageNum) {
    _isSyncing = true;

    ['cmp-panel-left', 'cmp-panel-right'].forEach(id => {
        const panel = $id(id);
        const block = panel?.querySelector(`.cmp-page-block[data-page="${pageNum}"]`);
        if (block) block.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    });

    setTimeout(() => { _isSyncing = false; }, 600);
    _highlightThumbnail(pageNum);
    _highlightSidebarItem(pageNum);
}

// ─── Zoom ─────────────────────────────────────────────────────────────────────
function _applyZoom(factor) {
    _zoom = Math.max(0.2, Math.min(factor, 4));
    const pct = Math.round(_zoom * 100);
    _setText('cmp-zoom-label', `${pct}%`);

    document.querySelectorAll('.cmp-page-img').forEach(img => {
        const nw = parseInt(img.dataset.nativeWidth, 10);
        if (nw) img.style.width = `${nw * _zoom}px`;
    });
}

function _fitWidth(panel) {
    const avail = panel.clientWidth - 32; // subtract padding
    const first = panel.querySelector('.cmp-page-img');
    if (!first) return;
    const nw = parseInt(first.dataset.nativeWidth, 10);
    if (nw > 0) _applyZoom(avail / nw);
}

// ─── Thumbnail strip ──────────────────────────────────────────────────────────
function _buildThumbnailStrip(state) {
    const strip = $id('cmp-thumbs-strip');
    if (!strip) return;
    strip.innerHTML = '';

    const statusColor = { changed: '#E5322D', added: '#3b82f6', removed: '#f59e0b', unchanged: '#22c55e' };

    state.results.pages.forEach(page => {
        const thumb = document.createElement('div');
        thumb.className = 'cmp-thumb';
        thumb.dataset.thumb = page.pageNumber;
        thumb.style.borderColor = statusColor[page.status] ?? '#e5e7eb';

        const canvas = page.visual?.originalCanvas;
        if (canvas) {
            const img = document.createElement('img');
            img.src = canvas.toDataURL('image/jpeg', 0.5);
            thumb.appendChild(img);
        } else {
            const ph = document.createElement('div');
            ph.style.cssText = 'width:54px;height:72px;background:#eee;';
            thumb.appendChild(ph);
        }

        const lbl = document.createElement('div');
        lbl.className = 'cmp-thumb-lbl';
        lbl.style.color = statusColor[page.status] ?? '#6b7280';
        lbl.textContent = page.pageNumber;
        thumb.appendChild(lbl);

        thumb.addEventListener('click', () => {
            if (_appState) {
                _appState.currentPage = page.pageNumber;
                _scrollToPage(page.pageNumber);
                _updatePageIndicator(_appState);
                _updateSubPanelPageIndicators(_appState);
            }
        });

        strip.appendChild(thumb);
    });
}

function _highlightThumbnail(pageNum) {
    document.querySelectorAll('.cmp-thumb').forEach(t => {
        t.classList.toggle('cmp-thumb-active', parseInt(t.dataset.thumb, 10) === pageNum);
    });
}

// ─── Viewer toolbar ───────────────────────────────────────────────────────────
function _setupViewerToolbar(state, handlers) {
    $id('cmp-zoom-out')?.addEventListener('click', () => _applyZoom(_zoom - 0.15));
    $id('cmp-zoom-in') ?.addEventListener('click', () => _applyZoom(_zoom + 0.15));
    $id('cmp-fit-width')?.addEventListener('click', () => {
        const p = $id('cmp-panel-left');
        if (p) _fitWidth(p);
    });

    $id('cmp-sync-scroll-toggle')?.addEventListener('change', e => {
        _syncEnabled = e.target.checked;
    });

    $id('cmp-show-diff-overlay')?.addEventListener('change', e => {
        _showOverlay = e.target.checked;
        document.querySelectorAll('.cmp-diff-overlay').forEach(el => {
            el.style.display = _showOverlay ? 'block' : 'none';
        });
    });

    $id('cmp-changed-only')?.addEventListener('change', e => {
        _changedOnly = e.target.checked;
        renderContinuousViewer(state);
        _highlightThumbnail(state.currentPage);
    });

    $id('cmp-prev-page')?.addEventListener('click', () => handlers.onPageChange?.('prev'));
    $id('cmp-next-page')?.addEventListener('click', () => handlers.onPageChange?.('next'));
    $id('cmp-next-diff')?.addEventListener('click', () => handlers.onPageChange?.('next-diff'));

    // Resizable divider (drag to resize panels)
    _setupDividerDrag();
}

// ─── Divider drag to resize ───────────────────────────────────────────────────
function _setupDividerDrag() {
    const divider = $id('cmp-panel-divider');
    const wrap    = divider?.parentElement;
    const left    = $id('cmp-panel-left');
    const right   = $id('cmp-panel-right');
    if (!divider || !wrap || !left || !right) return;

    let dragging = false, startX = 0, startLeftW = 0;

    divider.addEventListener('mousedown', e => {
        dragging = true;
        startX = e.clientX;
        startLeftW = left.getBoundingClientRect().width;
        document.body.style.cursor = 'col-resize';
        document.body.style.userSelect = 'none';
    });

    document.addEventListener('mousemove', e => {
        if (!dragging) return;
        const dx = e.clientX - startX;
        const total = wrap.getBoundingClientRect().width - 4; // minus divider
        const newLeft = Math.max(100, Math.min(startLeftW + dx, total - 100));
        left.style.flex  = 'none';
        left.style.width = `${newLeft}px`;
        right.style.flex = '1';
        right.style.width = '';
    });

    document.addEventListener('mouseup', () => {
        if (dragging) {
            dragging = false;
            document.body.style.cursor = '';
            document.body.style.userSelect = '';
        }
    });
}

// ─── Per-panel nav in Text / Overlay sub-panels ────────────────────────────
function _setupSubPanelNav(state) {
    // Text panel
    $id('cmp-text-prev')?.addEventListener('click', () => {
        if (state.currentPage > 1) { state.currentPage--; _syncSubPanelPage(state); }
    });
    $id('cmp-text-next')?.addEventListener('click', () => {
        if (state.currentPage < state.results.pages.length) { state.currentPage++; _syncSubPanelPage(state); }
    });
    // Overlay panel
    $id('cmp-overlay-prev')?.addEventListener('click', () => {
        if (state.currentPage > 1) { state.currentPage--; _syncSubPanelPage(state); }
    });
    $id('cmp-overlay-next')?.addEventListener('click', () => {
        if (state.currentPage < state.results.pages.length) { state.currentPage++; _syncSubPanelPage(state); }
    });
}

function _syncSubPanelPage(state) {
    _updateSubPanelPageIndicators(state);
    const pageData = state.results.pages[state.currentPage - 1];
    if (pageData) {
        _renderText(pageData);
        _renderOverlay(pageData);
    }
    _scrollToPage(state.currentPage);
    _updatePageIndicator(state);
    _highlightThumbnail(state.currentPage);
}

// ════════════════════════════════════════════════════════════════════════════
// UPLOAD / FILE HANDLING
// ════════════════════════════════════════════════════════════════════════════

function _setupDropZones(state) {
    ['original', 'modified'].forEach(type => {
        const zone      = $id(`${type}-drop-zone`);
        const input     = $id(`${type}-file-input`);
        const removeBtn = $id(`${type}-remove-btn`);

        if (!zone || !input) return;

        zone.addEventListener('click', () => input.click());
        zone.addEventListener('dragover',  e => { e.preventDefault(); zone.classList.add('cmp-drag-over'); });
        zone.addEventListener('dragleave', () => zone.classList.remove('cmp-drag-over'));
        zone.addEventListener('drop', e => {
            e.preventDefault();
            zone.classList.remove('cmp-drag-over');
            const file = e.dataTransfer.files[0];
            if (file) _handleFile(type, file, state);
        });
        input.addEventListener('change', e => {
            if (e.target.files[0]) _handleFile(type, e.target.files[0], state);
        });
        removeBtn?.addEventListener('click', () => _clearFile(type, state));
    });
}

function _handleFile(type, file, state) {
    if (file.type !== 'application/pdf') {
        _showFileError(type, 'Please select a valid PDF file.'); return;
    }
    if (file.size > 150 * 1024 * 1024) {
        _showFileError(type, `File too large (max 150 MB). Your file: ${formatBytes(file.size)}.`); return;
    }
    state[`${type}File`] = file;
    $id(`${type}-drop-zone`)?.classList.add('d-none');
    $id(`${type}-file-card`)?.classList.remove('d-none');
    _setText(`${type}-file-name`, file.name);
    _setText(`${type}-file-size`, formatBytes(file.size));
    _updateCompareButton(state);
}

function _clearFile(type, state) {
    state[`${type}File`] = null;
    state[`${type}Pdf`]  = null;
    $id(`${type}-drop-zone`)?.classList.remove('d-none');
    $id(`${type}-file-card`)?.classList.add('d-none');
    const input = $id(`${type}-file-input`);
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
    $id('cmp-compare-btn')?.addEventListener('click', () => handlers.onCompare?.());
}

function _setupCancelButton(handlers) {
    $id('cmp-cancel-btn')?.addEventListener('click', () => handlers.onCancel?.());
}

// ════════════════════════════════════════════════════════════════════════════
// TABS
// ════════════════════════════════════════════════════════════════════════════

function _setupTabs(state, handlers) {
    document.querySelectorAll('[data-cmp-tab]').forEach(tab => {
        tab.addEventListener('click', () => {
            const mode = tab.dataset.cmpTab;
            state.mode = mode;

            document.querySelectorAll('[data-cmp-tab]').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');

            document.querySelectorAll('[data-cmp-panel]').forEach(p => p.classList.add('d-none'));
            $id(`cmp-panel-${mode}`)?.classList.remove('d-none');

            handlers.onModeChange?.(mode);

            // Trigger content render for the activated panel
            const pageData = state.results.pages[state.currentPage - 1];
            if (!pageData) return;
            if (mode === 'text')    _renderText(pageData);
            if (mode === 'overlay') _renderOverlay(pageData);
            if (mode === 'report')  _renderReportTab(state);
        });
    });
}

// ════════════════════════════════════════════════════════════════════════════
// PANEL RENDERERS
// ════════════════════════════════════════════════════════════════════════════

function _renderText(pageData) {
    const el = $id('cmp-text-diff-content');
    if (!el) return;

    if (!pageData.text?.htmlDiff) {
        el.innerHTML = '<p class="text-muted small">No text extracted for this page.</p>';
        return;
    }

    el.innerHTML = `
        <div class="cmp-text-diff-wrap">${pageData.text.htmlDiff}</div>
        <div class="mt-3 d-flex gap-2 flex-wrap">
            <span class="badge bg-success">+${pageData.text.addedWords} words added</span>
            <span class="badge bg-danger">-${pageData.text.removedWords} words removed</span>
            ${pageData.status === 'unchanged' ? '<span class="badge bg-secondary">No changes</span>' : ''}
        </div>`;
}

function _renderOverlay(pageData) {
    renderOverlayToContainer(
        $id('cmp-overlay-container'),
        pageData.visual?.originalCanvas,
        pageData.visual?.modifiedCanvas,
        {
            opacityA:  parseFloat($id('cmp-opacity-a')?.value   ?? 0.5),
            opacityB:  parseFloat($id('cmp-opacity-b')?.value   ?? 0.5),
            blendMode: $id('cmp-blend-mode')?.value ?? 'source-over',
        }
    );
}

function _renderReportTab(state) {
    const s = state.results.summary ?? {};

    // Summary boxes
    const summaryEl = $id('cmp-report-summary');
    if (summaryEl) {
        summaryEl.innerHTML = `
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3"><div class="cmp-stat-box"><div class="cmp-stat-num">${s.totalPages ?? 0}</div>Total Pages</div></div>
                <div class="col-6 col-md-3"><div class="cmp-stat-box cmp-stat-changed"><div class="cmp-stat-num">${s.changedPages ?? 0}</div>Changed</div></div>
                <div class="col-6 col-md-3"><div class="cmp-stat-box cmp-stat-ok"><div class="cmp-stat-num">${s.unchangedPages ?? 0}</div>Unchanged</div></div>
                <div class="col-6 col-md-3"><div class="cmp-stat-box"><div class="cmp-stat-num">${(s.avgDiffPercentage ?? 0).toFixed(1)}%</div>Avg. Diff</div></div>
            </div>`;
    }

    // Per-page table
    const tbody = $id('cmp-report-tbody');
    if (tbody) {
        const statusColor = { changed: 'danger', added: 'primary', removed: 'warning', unchanged: 'success' };
        tbody.innerHTML = state.results.pages.map(p => `
            <tr style="cursor:pointer" onclick="document.querySelector('[data-cmp-tab=visual]').click();setTimeout(()=>{document.getElementById('cmp-prev-page')?.dispatchEvent(new MouseEvent('click'));},0)">
                <td>Page ${p.pageNumber}</td>
                <td><span class="badge bg-${statusColor[p.status] ?? 'secondary'}">${p.status}</span></td>
                <td>${(p.visual?.diffPercentage ?? 0).toFixed(2)}%</td>
                <td>+${p.text?.addedWords ?? 0}</td>
                <td>-${p.text?.removedWords ?? 0}</td>
            </tr>`).join('');
    }
}

// ─── Overlay controls ────────────────────────────────────────────────────────
function _setupOverlayControls(state) {
    const refresh = () => {
        const pd = state.results.pages[state.currentPage - 1];
        if (pd) _renderOverlay(pd);
    };
    $id('cmp-opacity-a')?.addEventListener('input', refresh);
    $id('cmp-opacity-b')?.addEventListener('input', refresh);
    $id('cmp-blend-mode')?.addEventListener('change', refresh);
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
    const label  = $id('cmp-threshold-label');
    if (!slider) return;
    slider.addEventListener('input', () => {
        state.threshold = parseFloat(slider.value);
        if (label) label.textContent = `${Math.round(state.threshold * 100)}%`;
    });
}

// ════════════════════════════════════════════════════════════════════════════
// SIDEBAR
// ════════════════════════════════════════════════════════════════════════════

function _updateSidebar(state) {
    const { summary, pages } = state.results;
    if (!summary) return;

    _setText('cmp-stat-total',     summary.totalPages);
    _setText('cmp-stat-changed',   summary.changedPages);
    _setText('cmp-stat-unchanged', summary.unchangedPages);
    _setText('cmp-stat-added',     summary.addedPages);
    _setText('cmp-stat-removed',   summary.removedPages);
    _setText('cmp-stat-avgdiff',   `${summary.avgDiffPercentage.toFixed(1)}%`);

    const list = $id('cmp-pages-list');
    if (!list) return;

    const icons = { changed: '🔴', unchanged: '🟢', added: '🔵', removed: '🟡' };
    list.innerHTML = pages.map(p => `
        <li class="list-group-item list-group-item-action d-flex justify-content-between align-items-center cmp-sb-page-item${p.status === 'unchanged' ? ' text-muted' : ''}"
            data-page="${p.pageNumber}" style="cursor:pointer;font-size:.82rem;padding:6px 12px">
            <span>${icons[p.status] ?? '⚪'} Page ${p.pageNumber}</span>
            <small>${p.status !== 'unchanged' ? `${(p.visual?.diffPercentage ?? 0).toFixed(1)}%` : ''}</small>
        </li>`).join('');

    list.querySelectorAll('.cmp-sb-page-item').forEach(item => {
        item.addEventListener('click', () => {
            const pg = parseInt(item.dataset.page, 10);
            if (_appState) {
                _appState.currentPage = pg;
                _scrollToPage(pg);
                _updatePageIndicator(_appState);
                _updateSubPanelPageIndicators(_appState);
            }
        });
    });
}

function _highlightSidebarItem(pageNum) {
    document.querySelectorAll('.cmp-sb-page-item').forEach(item => {
        item.classList.toggle('active', parseInt(item.dataset.page, 10) === pageNum);
    });
}

// ════════════════════════════════════════════════════════════════════════════
// PAGE INDICATORS
// ════════════════════════════════════════════════════════════════════════════

function _updatePageIndicator(state) {
    const total = state.results.pages.length;
    _setText('cmp-page-current', state.currentPage);
    _setText('cmp-page-total',   total);

    $id('cmp-prev-page')?.toggleAttribute('disabled', state.currentPage <= 1);
    $id('cmp-next-page')?.toggleAttribute('disabled', state.currentPage >= total);

    const pageData = state.results.pages[state.currentPage - 1];
    const badge = $id('cmp-page-status');
    if (badge && pageData) {
        badge.textContent = pageData.status;
        badge.className = `badge ms-1 bg-${_statusColor(pageData.status)}`;
    }

    _highlightSidebarItem(state.currentPage);

    // Active outline on current page block
    document.querySelectorAll('.cmp-page-block').forEach(b => {
        b.classList.toggle('cmp-page-active', parseInt(b.dataset.page, 10) === state.currentPage);
    });
}

function _updateSubPanelPageIndicators(state) {
    const total = state.results.pages.length;
    const cur   = state.currentPage;
    const pd    = state.results.pages[cur - 1];
    const sc    = pd ? _statusColor(pd.status) : 'secondary';

    _setText('cmp-text-page-cur', cur);
    _setText('cmp-text-page-tot', total);
    const textStatus = $id('cmp-text-status');
    if (textStatus && pd) { textStatus.textContent = pd.status; textStatus.className = `badge ms-1 bg-${sc}`; }

    _setText('cmp-overlay-page-cur', cur);
    _setText('cmp-overlay-page-tot', total);
}

// ════════════════════════════════════════════════════════════════════════════
// UTILITIES
// ════════════════════════════════════════════════════════════════════════════

function _statusColor(status) {
    return { changed: 'danger', unchanged: 'success', added: 'primary', removed: 'warning' }[status] ?? 'secondary';
}

function _setText(id, value) {
    const el = $id(id);
    if (el) el.textContent = value;
}
