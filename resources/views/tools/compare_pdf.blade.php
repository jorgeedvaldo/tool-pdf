@extends('layouts.app')

@section('title', 'Compare PDF Files Online - Find Text and Visual Differences - ToolPDF')

@section('content')

<style>
:root {
    --cmp-primary:    #E5322D;
    --cmp-dark:       #222;
    --cmp-muted:      #6B7280;
    --cmp-success-bg: #D1FAE5;
    --cmp-danger-bg:  #FEE2E2;
    --cmp-warning-bg: #FEF3C7;
    --cmp-panel-bg:   #525659;
    --cmp-top-bar-h:  48px;
    --cmp-hdr-h:      32px;
    --cmp-thumb-h:    76px;
    --cmp-sidebar-w:  290px;
}

/* ── Hero ── */
.cmp-hero {
    background: linear-gradient(135deg, #E5322D 0%, #b52420 100%);
    color: #fff; padding: 48px 0 36px;
}
.cmp-hero h1 { font-size: 1.9rem; font-weight: 800; margin-bottom: .4rem; }
.cmp-privacy-badges .badge {
    background: rgba(255,255,255,.18); border: 1px solid rgba(255,255,255,.35);
    font-size: .73rem; padding: .3em .65em; border-radius: 20px; margin-right: .35rem;
}

/* ── Upload ── */
.cmp-upload-card {
    border: 2px dashed #d1d5db; border-radius: 12px; background: #fff;
    transition: border-color .2s, background .2s; cursor: pointer; min-height: 160px;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    padding: 24px 20px; text-align: center;
}
.cmp-upload-card:hover, .cmp-upload-card.cmp-drag-over { border-color: var(--cmp-primary); background: #fff5f5; }
.cmp-upload-icon { font-size: 2.2rem; color: var(--cmp-primary); margin-bottom: .5rem; }
.cmp-file-card { border-radius: 10px; background: #fff; border: 1px solid #e5e7eb; padding: 14px 16px; }
.cmp-compare-btn {
    background: var(--cmp-primary); border: none; color: #fff;
    font-size: 1.05rem; font-weight: 700; padding: 13px 40px;
    border-radius: 50px; box-shadow: 0 4px 16px rgba(229,50,45,.3);
    transition: opacity .2s, transform .1s;
}
.cmp-compare-btn:hover:not(:disabled) { opacity: .9; transform: translateY(-1px); }
.cmp-compare-btn:disabled { opacity: .4; cursor: not-allowed; }
.cmp-privacy-notice { font-size: .8rem; color: var(--cmp-muted); }
.cmp-privacy-notice i { color: #22c55e; }

/* ── Full-screen viewer ── */
#cmp-results-area {
    display: flex;
    flex-direction: column;
    /* push content to full width, breaking out of main py-4 */
    margin-top: -1.5rem;
}

/* ── Top toolbar ── */
.cmp-top-bar {
    height: var(--cmp-top-bar-h);
    background: #fff;
    border-bottom: 1px solid #e5e7eb;
    display: flex; align-items: center; gap: 6px;
    padding: 0 14px; flex-shrink: 0; flex-wrap: wrap;
    overflow: hidden;
}
.cmp-toolbar-sep { width: 1px; height: 20px; background: #e5e7eb; flex-shrink: 0; }
.cmp-top-bar .form-check-label { font-size: .82rem; }
.cmp-top-bar .badge { font-size: .76rem; min-width: 42px; }

/* ── Column header bar ── */
.cmp-col-hdr-bar {
    height: var(--cmp-hdr-h);
    display: flex; background: #3a3a3a; flex-shrink: 0;
}
.cmp-col-hdr {
    flex: 1; padding: 0 14px; font-size: .72rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .06em; color: #ccc;
    display: flex; align-items: center; gap: 6px; overflow: hidden;
}
.cmp-col-hdr small { font-weight: 400; opacity: .7; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.cmp-col-hdr-sidebar { width: var(--cmp-sidebar-w); flex-shrink: 0; background: #2d2d2d; border-left: 1px solid #555; }

/* ── Viewer body ── */
.cmp-viewer-body {
    display: flex;
    height: calc(100vh - 58px - var(--cmp-top-bar-h) - var(--cmp-hdr-h) - var(--cmp-thumb-h));
    min-height: 420px;
    overflow: hidden;
    flex-shrink: 0;
}

/* ── PDF panels ── */
.cmp-panel {
    flex: 1; overflow-y: scroll; overflow-x: auto;
    background: var(--cmp-panel-bg); padding: 24px 16px;
    display: flex; flex-direction: column; align-items: center; gap: 20px;
    /* NO scroll-behavior: smooth — causes sync scroll loop bug */
}
.cmp-panel-divider {
    width: 5px; background: #E5322D; flex-shrink: 0; cursor: col-resize;
    transition: background .15s;
}
.cmp-panel-divider:hover { background: #ff6b68; }

/* ── Page blocks ── */
.cmp-page-block {
    position: relative; background: #fff;
    box-shadow: 0 4px 20px rgba(0,0,0,.45); border-radius: 2px; flex-shrink: 0;
}
.cmp-page-block .cmp-page-lbl {
    position: absolute; top: -20px; left: 0;
    font-size: 11px; color: #bbb; white-space: nowrap; user-select: none;
}
.cmp-page-block .cmp-diff-overlay {
    position: absolute; top: 0; left: 0; width: 100%; height: 100%;
    opacity: .45; pointer-events: none; border-radius: 2px;
}
.cmp-page-placeholder {
    display: flex; align-items: center; justify-content: center;
    background: #3a3a3a; color: #888; font-size: .82rem;
    border-radius: 2px; min-width: 220px; min-height: 300px;
}

/* ── Right sidebar ── */
.cmp-right-sidebar {
    width: var(--cmp-sidebar-w); flex-shrink: 0;
    background: #fff; border-left: 1px solid #e5e7eb;
    display: flex; flex-direction: column; overflow: hidden;
}
.cmp-stabs {
    display: flex; border-bottom: 1px solid #e5e7eb; flex-shrink: 0;
}
.cmp-stab {
    flex: 1; border: none; background: transparent; padding: 8px 4px;
    font-size: .75rem; font-weight: 600; color: var(--cmp-muted);
    border-bottom: 2px solid transparent; cursor: pointer;
    transition: color .12s, border-color .12s;
}
.cmp-stab.active { color: var(--cmp-primary); border-bottom-color: var(--cmp-primary); }
.cmp-stab-body { flex: 1; overflow-y: auto; padding: 10px; }

/* ── Sidebar stats ── */
.cmp-stat-pill {
    display: flex; justify-content: space-between; align-items: center;
    padding: 5px 8px; border-radius: 6px; background: #f3f4f6;
    font-size: .78rem; margin-bottom: 4px;
}
.cmp-stat-pill strong { font-size: .9rem; }
#cmp-pages-list { font-size: .78rem; }
#cmp-pages-list .list-group-item.active {
    background: var(--cmp-danger-bg); color: var(--cmp-primary); border-color: transparent;
}

/* ── Text diff ── */
.pdf-diff-added    { background: #dcfce7; color: #15803d; border-radius: 2px; padding: 0 2px; }
.pdf-diff-removed  { background: #fee2e2; color: #b91c1c; text-decoration: line-through; border-radius: 2px; padding: 0 2px; }
.pdf-diff-unchanged { color: #374151; }
.cmp-text-diff-wrap { font-family: Georgia, serif; font-size: .88rem; line-height: 1.7; white-space: pre-wrap; word-break: break-word; }

/* ── Overlay ── */
.cmp-viewer-pane { border: 1px solid #e5e7eb; border-radius: 6px; background: #fafafa; overflow: auto; padding: 8px; }

/* ── Report stat boxes ── */
.cmp-stat-box { background: #f3f4f6; border-radius: 8px; padding: 12px 8px; text-align: center; font-size: .72rem; color: var(--cmp-muted); }
.cmp-stat-box.cmp-stat-changed { background: var(--cmp-danger-bg); }
.cmp-stat-box.cmp-stat-ok      { background: var(--cmp-success-bg); }
.cmp-stat-num { font-size: 1.6rem; font-weight: 800; color: var(--cmp-dark); line-height: 1; margin-bottom: 2px; }

/* ── Thumbnail strip ── */
.cmp-thumbs-wrap {
    height: var(--cmp-thumb-h); flex-shrink: 0;
    background: #f1f3f5; border-top: 1px solid #ddd;
    display: flex; align-items: center; gap: 8px; padding: 0 12px; overflow: hidden;
}
.cmp-thumbs-label { font-size: .7rem; font-weight: 700; color: var(--cmp-muted); white-space: nowrap; }
.cmp-thumbs-strip {
    display: flex; gap: 5px; overflow-x: auto; padding: 4px 0; flex: 1;
    scrollbar-width: thin;
}
.cmp-thumb {
    flex-shrink: 0; width: 42px; cursor: pointer; text-align: center;
    border-radius: 4px; border: 2px solid #e5e7eb; background: #fff; overflow: hidden;
    transition: border-color .12s, transform .1s, box-shadow .1s;
}
.cmp-thumb:hover { transform: scale(1.1); box-shadow: 0 2px 8px rgba(0,0,0,.18); }
.cmp-thumb.cmp-thumb-active { border-color: var(--cmp-primary) !important; box-shadow: 0 0 0 2px rgba(229,50,45,.25); }
.cmp-thumb img { width: 100%; height: auto; display: block; }
.cmp-thumb-lbl { font-size: 9px; font-weight: 700; padding: 1px 0; background: #f3f4f6; color: #6b7280; }
</style>

{{-- ══════════════ HERO ══════════════ --}}
<section class="cmp-hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1>Compare PDF Files Online</h1>
                <p class="mb-3" style="opacity:.88;font-size:1rem">Compare two PDFs directly in your browser — visual pixel diff, word-level text diff, and synchronized scroll. Zero upload.</p>
                <div class="cmp-privacy-badges d-flex flex-wrap gap-1">
                    <span class="badge">🔒 100% Client-Side</span>
                    <span class="badge">☁️ No Upload</span>
                    <span class="badge">⚡ Web Worker</span>
                </div>
            </div>
            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                <i class="bi bi-file-diff" style="font-size:4.5rem;opacity:.2;"></i>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════ UPLOAD ══════════════ --}}
<div id="cmp-upload-section" class="container py-5">

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <p class="fw-bold mb-2 small text-uppercase" style="letter-spacing:.05em">Original PDF</p>
            <div id="original-drop-zone" class="cmp-upload-card">
                <div class="cmp-upload-icon"><i class="bi bi-file-earmark-arrow-up"></i></div>
                <div class="fw-semibold mb-1">Drag & drop or click to select</div>
                <div class="text-muted small">Original / reference document</div>
                <input type="file" id="original-file-input" class="d-none" accept="application/pdf">
            </div>
            <div id="original-file-card" class="cmp-file-card d-none mt-2">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-file-earmark-pdf text-danger fs-4"></i>
                        <div>
                            <div class="fw-semibold text-truncate" style="max-width:220px" id="original-file-name"></div>
                            <small class="text-muted" id="original-file-size"></small>
                        </div>
                    </div>
                    <button type="button" id="original-remove-btn" class="btn btn-sm btn-outline-danger rounded-circle px-2"><i class="bi bi-x"></i></button>
                </div>
            </div>
            <div id="original-file-error" class="alert alert-danger mt-2 py-2 small d-none"></div>
        </div>

        <div class="col-md-6">
            <p class="fw-bold mb-2 small text-uppercase" style="letter-spacing:.05em">Modified PDF</p>
            <div id="modified-drop-zone" class="cmp-upload-card">
                <div class="cmp-upload-icon"><i class="bi bi-file-earmark-arrow-up"></i></div>
                <div class="fw-semibold mb-1">Drag & drop or click to select</div>
                <div class="text-muted small">Updated / modified document</div>
                <input type="file" id="modified-file-input" class="d-none" accept="application/pdf">
            </div>
            <div id="modified-file-card" class="cmp-file-card d-none mt-2">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-file-earmark-pdf text-danger fs-4"></i>
                        <div>
                            <div class="fw-semibold text-truncate" style="max-width:220px" id="modified-file-name"></div>
                            <small class="text-muted" id="modified-file-size"></small>
                        </div>
                    </div>
                    <button type="button" id="modified-remove-btn" class="btn btn-sm btn-outline-danger rounded-circle px-2"><i class="bi bi-x"></i></button>
                </div>
            </div>
            <div id="modified-file-error" class="alert alert-danger mt-2 py-2 small d-none"></div>
        </div>
    </div>

    <div class="row g-3 mb-4 align-items-end">
        <div class="col-md-4">
            <label class="form-label small fw-semibold mb-1">Sensitivity (threshold)</label>
            <div class="d-flex align-items-center gap-2">
                <input type="range" id="cmp-threshold-slider" class="form-range" min="0.01" max="0.5" step="0.01" value="0.1">
                <span id="cmp-threshold-label" class="badge bg-secondary">10%</span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="cmp-ocr-toggle">
                <label class="form-check-label small fw-semibold" for="cmp-ocr-toggle">OCR for scanned PDFs</label>
            </div>
            <div id="cmp-ocr-warning" class="alert alert-warning py-1 mt-1 small d-none">
                <i class="bi bi-clock me-1"></i> OCR runs locally — may be slower.
            </div>
        </div>
        <div class="col-md-4 text-md-end">
            <p class="cmp-privacy-notice mb-0"><i class="bi bi-shield-lock-fill me-1"></i> Files stay on your device.</p>
        </div>
    </div>

    <div class="text-center">
        <button type="button" id="cmp-compare-btn" class="cmp-compare-btn" disabled>
            <i class="bi bi-file-diff me-2"></i>Compare PDFs
        </button>
    </div>
</div>

{{-- ══════════════ PROGRESS ══════════════ --}}
<div id="cmp-progress-area" class="d-none py-5" style="max-width:540px;margin:0 auto">
    <div class="text-center mb-3">
        <span id="cmp-progress-message" class="fw-semibold">Comparing…</span>
        <small id="cmp-progress-detail" class="text-muted ms-2"></small>
    </div>
    <div class="progress" style="height:12px;border-radius:8px">
        <div id="cmp-progress-bar" class="progress-bar bg-danger progress-bar-striped progress-bar-animated" style="width:0%;border-radius:8px"></div>
    </div>
    <div class="text-center mt-3">
        <button type="button" id="cmp-cancel-btn" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-x-circle me-1"></i>Cancel
        </button>
    </div>
</div>

{{-- ══════════════ FULL-SCREEN VIEWER ══════════════ --}}
<div id="cmp-results-area" class="d-none">

    {{-- Top toolbar --}}
    <div class="cmp-top-bar">
        {{-- Sync scroll --}}
        <div class="form-check form-switch mb-0 d-flex align-items-center gap-1">
            <input class="form-check-input mt-0" type="checkbox" id="cmp-sync-scroll-toggle" checked>
            <label class="form-check-label" for="cmp-sync-scroll-toggle" style="cursor:pointer;font-size:.82rem;font-weight:600">
                <i class="bi bi-arrow-down-up me-1"></i>Sync scroll
            </label>
        </div>

        <div class="cmp-toolbar-sep"></div>

        {{-- Zoom --}}
        <button id="cmp-zoom-out" class="btn btn-sm btn-outline-secondary px-2" title="Zoom out"><i class="bi bi-dash-lg"></i></button>
        <span id="cmp-zoom-label" class="badge bg-secondary">100%</span>
        <button id="cmp-zoom-in" class="btn btn-sm btn-outline-secondary px-2" title="Zoom in"><i class="bi bi-plus-lg"></i></button>
        <button id="cmp-fit-width" class="btn btn-sm btn-outline-secondary px-2" title="Fit width"><i class="bi bi-arrows-expand"></i></button>

        <div class="cmp-toolbar-sep"></div>

        {{-- Diff overlay --}}
        <div class="form-check form-switch mb-0 d-flex align-items-center gap-1">
            <input class="form-check-input mt-0" type="checkbox" id="cmp-show-diff-overlay" checked>
            <label class="form-check-label" for="cmp-show-diff-overlay" style="cursor:pointer;font-size:.82rem;font-weight:600">
                <i class="bi bi-circle-half me-1"></i>Highlight
            </label>
        </div>

        {{-- Text highlights --}}
        <div class="form-check form-switch mb-0 d-flex align-items-center gap-1">
            <input class="form-check-input mt-0" type="checkbox" id="cmp-show-text-hl" checked>
            <label class="form-check-label" for="cmp-show-text-hl" style="cursor:pointer;font-size:.82rem;font-weight:600">
                <i class="bi bi-fonts me-1"></i>Text marks
            </label>
        </div>

        {{-- Changed only --}}
        <div class="form-check form-switch mb-0 d-flex align-items-center gap-1">
            <input class="form-check-input mt-0" type="checkbox" id="cmp-changed-only">
            <label class="form-check-label" for="cmp-changed-only" style="cursor:pointer;font-size:.82rem;font-weight:600">Changed only</label>
        </div>

        <div class="cmp-toolbar-sep"></div>

        {{-- Page nav --}}
        <button id="cmp-prev-page" class="btn btn-sm btn-outline-secondary px-2"><i class="bi bi-chevron-left"></i></button>
        <span style="font-size:.82rem;font-weight:600;white-space:nowrap">
            <span id="cmp-page-current">1</span> / <span id="cmp-page-total">—</span>
        </span>
        <button id="cmp-next-page" class="btn btn-sm btn-outline-secondary px-2"><i class="bi bi-chevron-right"></i></button>
        <button id="cmp-next-diff" class="btn btn-sm btn-danger"><i class="bi bi-arrow-right-circle me-1"></i>Next diff</button>
        <span id="cmp-page-status" class="badge bg-secondary" style="font-size:.7rem">—</span>

        <div class="ms-auto">
            <button id="cmp-new-comparison" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-repeat me-1"></i>New comparison
            </button>
        </div>
    </div>

    {{-- Column headers --}}
    <div class="cmp-col-hdr-bar">
        <div class="cmp-col-hdr">
            <i class="bi bi-file-earmark-text" style="opacity:.6"></i>
            Original
            <small id="cmp-orig-pages-lbl"></small>
            <small id="cmp-orig-file-lbl" class="ms-1" style="opacity:.5"></small>
        </div>
        <div style="width:5px;background:#E5322D;flex-shrink:0"></div>
        <div class="cmp-col-hdr">
            <i class="bi bi-file-earmark-diff" style="opacity:.6"></i>
            Modified
            <small id="cmp-mod-pages-lbl"></small>
            <small id="cmp-mod-file-lbl" class="ms-1" style="opacity:.5"></small>
        </div>
        <div class="cmp-col-hdr cmp-col-hdr-sidebar" style="font-size:.7rem">
            <i class="bi bi-bar-chart me-1" style="opacity:.5"></i>Analysis
        </div>
    </div>

    {{-- Viewer body: panels + sidebar --}}
    <div class="cmp-viewer-body">
        <div class="cmp-panel" id="cmp-panel-left">
            <div style="color:#999;margin:auto;font-size:.85rem">Run comparison to see pages</div>
        </div>
        <div class="cmp-panel-divider" id="cmp-panel-divider" title="Drag to resize"></div>
        <div class="cmp-panel" id="cmp-panel-right"></div>

        {{-- Right sidebar --}}
        <div class="cmp-right-sidebar">
            <div class="cmp-stabs">
                <button class="cmp-stab active" data-stab="pages"><i class="bi bi-list-ul me-1"></i>Pages</button>
                <button class="cmp-stab" data-stab="text"><i class="bi bi-fonts me-1"></i>Text</button>
                <button class="cmp-stab" data-stab="overlay"><i class="bi bi-layers me-1"></i>Overlay</button>
                <button class="cmp-stab" data-stab="report"><i class="bi bi-bar-chart me-1"></i>Report</button>
            </div>

            {{-- Pages tab --}}
            <div class="cmp-stab-body" id="cmp-stab-pages">
                <div class="cmp-stat-pill mb-1"><span>Total</span><strong id="cmp-stat-total">—</strong></div>
                <div class="cmp-stat-pill mb-1" style="background:var(--cmp-danger-bg)"><span>Changed</span><strong id="cmp-stat-changed">—</strong></div>
                <div class="cmp-stat-pill mb-1" style="background:var(--cmp-success-bg)"><span>Unchanged</span><strong id="cmp-stat-unchanged">—</strong></div>
                <div class="cmp-stat-pill mb-1" style="background:#dbeafe"><span>Added</span><strong id="cmp-stat-added">—</strong></div>
                <div class="cmp-stat-pill mb-3" style="background:var(--cmp-warning-bg)"><span>Removed</span><strong id="cmp-stat-removed">—</strong></div>
                <ul class="list-group list-group-flush" id="cmp-pages-list" style="font-size:.78rem"></ul>
            </div>

            {{-- Text diff tab --}}
            <div class="cmp-stab-body d-none" id="cmp-stab-text">
                <div class="d-flex align-items-center gap-1 mb-2">
                    <button id="cmp-text-prev" class="btn btn-sm btn-outline-secondary px-2"><i class="bi bi-chevron-left"></i></button>
                    <span style="font-size:.8rem;font-weight:600">Page <span id="cmp-text-page-cur">1</span>/<span id="cmp-text-page-tot">—</span></span>
                    <button id="cmp-text-next" class="btn btn-sm btn-outline-secondary px-2"><i class="bi bi-chevron-right"></i></button>
                    <span id="cmp-text-status" class="badge bg-secondary ms-1" style="font-size:.68rem">—</span>
                </div>
                <div id="cmp-text-diff-content" class="cmp-text-diff-wrap" style="font-size:.83rem">
                    <p class="text-muted">Run comparison first.</p>
                </div>
            </div>

            {{-- Overlay tab --}}
            <div class="cmp-stab-body d-none" id="cmp-stab-overlay">
                <div class="d-flex align-items-center gap-1 mb-2">
                    <button id="cmp-overlay-prev" class="btn btn-sm btn-outline-secondary px-2"><i class="bi bi-chevron-left"></i></button>
                    <span style="font-size:.8rem;font-weight:600">Page <span id="cmp-overlay-page-cur">1</span>/<span id="cmp-overlay-page-tot">—</span></span>
                    <button id="cmp-overlay-next" class="btn btn-sm btn-outline-secondary px-2"><i class="bi bi-chevron-right"></i></button>
                </div>
                <div class="mb-2">
                    <label class="form-label small mb-1">Original opacity</label>
                    <input type="range" id="cmp-opacity-a" class="form-range form-range-sm" min="0" max="1" step="0.05" value="0.5">
                </div>
                <div class="mb-2">
                    <label class="form-label small mb-1">Modified opacity</label>
                    <input type="range" id="cmp-opacity-b" class="form-range form-range-sm" min="0" max="1" step="0.05" value="0.5">
                </div>
                <div class="mb-3">
                    <label class="form-label small mb-1">Blend mode</label>
                    <select id="cmp-blend-mode" class="form-select form-select-sm">
                        <option value="source-over">Normal</option>
                        <option value="difference">Difference</option>
                        <option value="multiply">Multiply</option>
                        <option value="screen">Screen</option>
                        <option value="exclusion">Exclusion</option>
                    </select>
                </div>
                <div class="cmp-viewer-pane" id="cmp-overlay-container" style="text-align:center"></div>
            </div>

            {{-- Report tab --}}
            <div class="cmp-stab-body d-none" id="cmp-stab-report">
                <div id="cmp-report-summary" class="mb-3"></div>
                <div class="table-responsive" style="font-size:.75rem">
                    <table class="table table-sm table-hover mb-2">
                        <thead class="table-light">
                            <tr><th>Pg</th><th>Status</th><th>Diff%</th><th>+W</th><th>-W</th></tr>
                        </thead>
                        <tbody id="cmp-report-tbody"></tbody>
                    </table>
                </div>
                <div class="d-flex gap-2 mt-2">
                    <button id="cmp-export-json" class="btn btn-outline-secondary btn-sm flex-fill"><i class="bi bi-filetype-json me-1"></i>JSON</button>
                    <button id="cmp-export-html" class="btn btn-outline-secondary btn-sm flex-fill"><i class="bi bi-filetype-html me-1"></i>HTML</button>
                </div>
            </div>
        </div>{{-- /cmp-right-sidebar --}}
    </div>{{-- /cmp-viewer-body --}}

    {{-- Thumbnail strip --}}
    <div class="cmp-thumbs-wrap">
        <span class="cmp-thumbs-label"><i class="bi bi-grid me-1"></i>Pages</span>
        <div class="cmp-thumbs-strip" id="cmp-thumbs-strip">
            <span class="text-muted small" style="opacity:.4">—</span>
        </div>
    </div>

</div>{{-- /cmp-results-area --}}

{{-- ══════════════ SEO ══════════════ --}}
<div class="bg-white py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h2 class="fw-bold mb-4">How to compare PDF files online</h2>
                <ol class="mb-5">
                    <li class="mb-2">Upload your <strong>Original PDF</strong> to the left card.</li>
                    <li class="mb-2">Upload your <strong>Modified PDF</strong> to the right card.</li>
                    <li class="mb-2">Adjust the <em>Sensitivity</em> slider if needed.</li>
                    <li class="mb-2">Click <strong>Compare PDFs</strong> and wait for the analysis.</li>
                    <li class="mb-2">Scroll both panels in sync, browse thumbnails, and use the right sidebar to view Text diff, Overlay or Report.</li>
                </ol>

                <h2 class="fw-bold mb-3">Features</h2>
                <div class="row g-3 mb-5">
                    <div class="col-sm-6"><div class="d-flex gap-3"><i class="bi bi-layout-split fs-4 text-danger mt-1"></i><div><strong>Synchronized Scroll</strong><br><span class="text-muted small">Both panels scroll together for effortless comparison.</span></div></div></div>
                    <div class="col-sm-6"><div class="d-flex gap-3"><i class="bi bi-eye fs-4 text-danger mt-1"></i><div><strong>Visual Pixel Diff</strong><br><span class="text-muted small">Pixel-level diff overlay via pixelmatch Web Worker.</span></div></div></div>
                    <div class="col-sm-6"><div class="d-flex gap-3"><i class="bi bi-fonts fs-4 text-danger mt-1"></i><div><strong>Word-level Text Diff</strong><br><span class="text-muted small">Highlights added / removed words with jsdiff.</span></div></div></div>
                    <div class="col-sm-6"><div class="d-flex gap-3"><i class="bi bi-layers fs-4 text-danger mt-1"></i><div><strong>Overlay Mode</strong><br><span class="text-muted small">Blend both pages with adjustable opacity and blend mode.</span></div></div></div>
                    <div class="col-sm-6"><div class="d-flex gap-3"><i class="bi bi-grid fs-4 text-danger mt-1"></i><div><strong>Page Thumbnails</strong><br><span class="text-muted small">Click any thumbnail to jump straight to that page.</span></div></div></div>
                    <div class="col-sm-6"><div class="d-flex gap-3"><i class="bi bi-shield-lock fs-4 text-danger mt-1"></i><div><strong>100% Private</strong><br><span class="text-muted small">All processing in your browser. Zero upload, zero storage.</span></div></div></div>
                </div>

                <h2 class="fw-bold mb-3">Frequently Asked Questions</h2>
                <div class="accordion accordion-flush mb-5" id="cmp-faq">
                    <div class="accordion-item border-bottom">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">Are my PDF files uploaded to a server?</button>
                        </h3>
                        <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#cmp-faq">
                            <div class="accordion-body text-muted">No. Your files never leave your device. All processing runs entirely in your browser.</div>
                        </div>
                    </div>
                    <div class="accordion-item border-bottom">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">Can I compare scanned PDFs?</button>
                        </h3>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#cmp-faq">
                            <div class="accordion-body text-muted">Yes. Enable <strong>OCR for scanned PDFs</strong> before clicking Compare. Tesseract.js extracts text from images locally.</div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">How does synchronized scroll work?</button>
                        </h3>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#cmp-faq">
                            <div class="accordion-body text-muted">When <strong>Sync scroll</strong> is enabled, scrolling either panel automatically mirrors the position in the other panel proportionally.</div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-light border d-flex gap-3 align-items-start">
                    <i class="bi bi-info-circle-fill text-primary fs-5 mt-1"></i>
                    <div><strong>Is my PDF safe?</strong><br><span class="text-muted small">This tool processes PDFs locally in your browser. We never receive, store or transmit your documents.</span></div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
<script>pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';</script>
<script type="module" src="{{ asset('js/tools/compare-pdf.js') }}"></script>
@endpush

@endsection
