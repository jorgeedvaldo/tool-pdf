@extends('layouts.app')

@section('title', 'Compare PDF Files Online - Find Text and Visual Differences - ToolPDF')

@section('content')

<style>
/* ─── Variables ─── */
:root {
    --cmp-primary:    #E5322D;
    --cmp-dark:       #222222;
    --cmp-muted:      #6B7280;
    --cmp-bg:         #F8F9FA;
    --cmp-success-bg: #D1FAE5;
    --cmp-danger-bg:  #FEE2E2;
    --cmp-warning-bg: #FEF3C7;
    --cmp-panel-bg:   #525659;
}

/* ─── Hero ─── */
.cmp-hero {
    background: linear-gradient(135deg, #E5322D 0%, #b52420 100%);
    color: #fff;
    padding: 56px 0 40px;
}
.cmp-hero h1 { font-size: 2rem; font-weight: 800; margin-bottom: .5rem; }
.cmp-privacy-badges .badge {
    background: rgba(255,255,255,.2);
    border: 1px solid rgba(255,255,255,.4);
    font-size: .75rem; padding: .35em .7em; border-radius: 20px; margin-right: .4rem;
}

/* ─── Upload cards ─── */
.cmp-upload-card {
    border: 2px dashed #d1d5db;
    border-radius: 12px; background: #fff;
    transition: border-color .2s, background .2s;
    cursor: pointer; min-height: 180px;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    padding: 28px 20px; text-align: center;
}
.cmp-upload-card:hover, .cmp-upload-card.cmp-drag-over { border-color: var(--cmp-primary); background: #fff5f5; }
.cmp-upload-icon { font-size: 2.5rem; color: var(--cmp-primary); margin-bottom: .6rem; }
.cmp-file-card { border-radius: 10px; background: #fff; border: 1px solid #e5e7eb; padding: 16px 18px; }

/* ─── Compare button ─── */
.cmp-compare-btn {
    background: var(--cmp-primary); border: none; color: #fff;
    font-size: 1.1rem; font-weight: 700; padding: 14px 44px;
    border-radius: 50px; transition: opacity .2s, transform .1s;
    box-shadow: 0 4px 16px rgba(229,50,45,.3);
}
.cmp-compare-btn:hover:not(:disabled) { opacity: .9; transform: translateY(-1px); }
.cmp-compare-btn:disabled { opacity: .45; cursor: not-allowed; }

/* ─── Tabs ─── */
.cmp-tabs { border-bottom: 2px solid #e5e7eb; gap: .15rem; }
.cmp-tab-btn {
    border: none; background: transparent; padding: 9px 16px; font-weight: 600;
    color: var(--cmp-muted); border-bottom: 3px solid transparent; margin-bottom: -2px;
    cursor: pointer; transition: color .15s, border-color .15s; white-space: nowrap;
    font-size: .88rem;
}
.cmp-tab-btn.active { color: var(--cmp-primary); border-bottom-color: var(--cmp-primary); }
.cmp-tab-btn:hover:not(.active) { color: var(--cmp-dark); }

/* ─── Viewer toolbar ─── */
.cmp-viewer-toolbar {
    background: #fff; border: 1px solid #e5e7eb; border-radius: 8px 8px 0 0;
    border-bottom: none; padding: 8px 12px; display: flex; flex-wrap: wrap;
    align-items: center; gap: 6px;
}
.cmp-toolbar-sep { width: 1px; height: 20px; background: #e5e7eb; flex-shrink: 0; }

/* ─── Column headers ─── */
.cmp-col-headers {
    display: flex; background: #3d3d3d; border-left: 1px solid #444; border-right: 1px solid #444;
}
.cmp-col-header {
    flex: 1; padding: 6px 12px; font-size: .75rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .06em; color: #ccc;
    display: flex; align-items: center; gap: 6px;
}
.cmp-col-header .badge-orig { background: #555; color: #eee; font-size: .65rem; padding: 2px 7px; border-radius: 10px; }
.cmp-col-header .badge-mod  { background: #2563eb33; color: #93c5fd; font-size: .65rem; padding: 2px 7px; border-radius: 10px; }

/* ─── Side-by-side panels ─── */
.cmp-panels-wrap {
    display: flex; height: 620px;
    border: 1px solid #444; border-top: none; border-radius: 0 0 8px 8px; overflow: hidden;
}
.cmp-panel {
    flex: 1; overflow-y: auto; overflow-x: auto;
    background: var(--cmp-panel-bg); padding: 20px 16px;
    display: flex; flex-direction: column; align-items: center; gap: 16px;
    scroll-behavior: smooth;
}
.cmp-panel-divider { width: 4px; background: #444; flex-shrink: 0; cursor: col-resize; }

/* ─── Page block inside panel ─── */
.cmp-page-block {
    position: relative; background: #fff;
    box-shadow: 0 4px 16px rgba(0,0,0,.4);
    border-radius: 3px; flex-shrink: 0;
    transition: outline .1s;
}
.cmp-page-block.cmp-page-active { outline: 3px solid var(--cmp-primary); }
.cmp-page-block .cmp-page-lbl {
    position: absolute; top: -22px; left: 0;
    font-size: 11px; color: #bbb; white-space: nowrap; user-select: none;
}
.cmp-page-block .cmp-diff-overlay {
    position: absolute; top: 0; left: 0; width: 100%; height: 100%;
    opacity: .4; pointer-events: none; border-radius: 3px;
}
.cmp-page-placeholder {
    display: flex; align-items: center; justify-content: center;
    background: #3a3a3a; color: #888; font-size: .82rem;
    border-radius: 3px; min-width: 200px; min-height: 280px;
}

/* ─── Thumbnail strip ─── */
.cmp-thumbs-wrap {
    margin-top: 8px; border: 1px solid #e5e7eb; border-radius: 8px;
    background: #f9fafb; padding: 8px 10px;
    display: flex; align-items: center; gap: 8px;
}
.cmp-thumbs-label { font-size: .72rem; font-weight: 700; color: var(--cmp-muted); white-space: nowrap; }
.cmp-thumbs-strip {
    display: flex; gap: 6px; overflow-x: auto; padding-bottom: 4px; flex: 1;
    scrollbar-width: thin;
}
.cmp-thumb {
    flex-shrink: 0; width: 54px; cursor: pointer; text-align: center;
    border-radius: 5px; border: 2px solid #e5e7eb;
    background: #fff; overflow: hidden;
    transition: border-color .15s, transform .12s, box-shadow .12s;
}
.cmp-thumb:hover { transform: scale(1.08); box-shadow: 0 2px 8px rgba(0,0,0,.15); }
.cmp-thumb.cmp-thumb-active { border-color: var(--cmp-primary) !important; box-shadow: 0 0 0 2px rgba(229,50,45,.3); }
.cmp-thumb img { width: 100%; height: auto; display: block; }
.cmp-thumb-lbl {
    font-size: 10px; font-weight: 700; padding: 2px 0; background: #f3f4f6;
}

/* ─── Text diff ─── */
.pdf-diff-added    { background: #dcfce7; color: #15803d; border-radius: 2px; padding: 0 2px; }
.pdf-diff-removed  { background: #fee2e2; color: #b91c1c; text-decoration: line-through; border-radius: 2px; padding: 0 2px; }
.pdf-diff-unchanged { color: #374151; }
.cmp-text-diff-wrap { font-family: Georgia, serif; font-size: .92rem; line-height: 1.75; white-space: pre-wrap; word-break: break-word; }

/* ─── Sidebar ─── */
.cmp-sidebar { position: sticky; top: 16px; }
.cmp-stat-pill {
    display: flex; justify-content: space-between; align-items: center;
    padding: 6px 10px; border-radius: 6px; background: #f3f4f6;
    font-size: .82rem; margin-bottom: 5px;
}
.cmp-stat-pill strong { font-size: .95rem; }
#cmp-pages-list { max-height: 340px; overflow-y: auto; font-size: .82rem; }
#cmp-pages-list .active { background: var(--cmp-danger-bg); color: var(--cmp-primary); border-color: transparent; }

/* ─── Overlay ─── */
.cmp-viewer-pane { border: 1px solid #e5e7eb; border-radius: 8px; background: #fafafa; overflow: auto; min-height: 300px; padding: 8px; }

/* ─── Report stat boxes ─── */
.cmp-stat-box { background: #f3f4f6; border-radius: 10px; padding: 18px; text-align: center; font-size: .8rem; color: var(--cmp-muted); }
.cmp-stat-box.cmp-stat-changed { background: var(--cmp-danger-bg); }
.cmp-stat-box.cmp-stat-ok      { background: var(--cmp-success-bg); }
.cmp-stat-num { font-size: 2rem; font-weight: 800; color: var(--cmp-dark); line-height: 1; margin-bottom: 4px; }

/* ─── Misc ─── */
.cmp-privacy-notice { font-size: .8rem; color: var(--cmp-muted); }
.cmp-privacy-notice i { color: #22c55e; }
#cmp-progress-area { max-width: 560px; margin: 0 auto; }
</style>

{{-- ══════════════ HERO ══════════════ --}}
<section class="cmp-hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1>Compare PDF Files Online</h1>
                <p class="mb-3" style="opacity:.9;font-size:1.05rem">
                    Compare two PDF documents directly in your browser. Find visual and text differences without uploading your files to any server.
                </p>
                <div class="cmp-privacy-badges d-flex flex-wrap gap-1">
                    <span class="badge">🔒 100% Client-Side</span>
                    <span class="badge">☁️ No Upload</span>
                    <span class="badge">👁️ Private PDF Comparison</span>
                </div>
            </div>
            <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
                <i class="bi bi-file-diff" style="font-size:5rem;opacity:.25;"></i>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════ TOOL ══════════════ --}}
<div class="container py-5" data-tool="compare-pdf">

    {{-- Upload --}}
    <div class="row g-4 mb-4">
        {{-- Original --}}
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
                            <div class="fw-semibold text-truncate" style="max-width:200px" id="original-file-name"></div>
                            <small class="text-muted" id="original-file-size"></small>
                        </div>
                    </div>
                    <button type="button" id="original-remove-btn" class="btn btn-sm btn-outline-danger rounded-circle px-2"><i class="bi bi-x"></i></button>
                </div>
            </div>
            <div id="original-file-error" class="alert alert-danger mt-2 py-2 small d-none"></div>
        </div>
        {{-- Modified --}}
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
                            <div class="fw-semibold text-truncate" style="max-width:200px" id="modified-file-name"></div>
                            <small class="text-muted" id="modified-file-size"></small>
                        </div>
                    </div>
                    <button type="button" id="modified-remove-btn" class="btn btn-sm btn-outline-danger rounded-circle px-2"><i class="bi bi-x"></i></button>
                </div>
            </div>
            <div id="modified-file-error" class="alert alert-danger mt-2 py-2 small d-none"></div>
        </div>
    </div>

    {{-- Options --}}
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
                <label class="form-check-label small fw-semibold" for="cmp-ocr-toggle">Use OCR for scanned PDFs</label>
            </div>
            <div id="cmp-ocr-warning" class="alert alert-warning py-1 mt-1 small d-none">
                <i class="bi bi-clock me-1"></i> OCR runs locally in your browser — it may be slower.
            </div>
        </div>
        <div class="col-md-4 text-md-end">
            <p class="cmp-privacy-notice mb-0"><i class="bi bi-shield-lock-fill me-1"></i> Your files stay on your device. No upload to any server.</p>
        </div>
    </div>

    {{-- Compare button --}}
    <div class="text-center mb-5">
        <button type="button" id="cmp-compare-btn" class="cmp-compare-btn" disabled>
            <i class="bi bi-file-diff me-2"></i>Compare PDFs
        </button>
    </div>

    {{-- Progress --}}
    <div id="cmp-progress-area" class="d-none mb-5">
        <div class="text-center mb-3">
            <span id="cmp-progress-message" class="fw-semibold">Comparing…</span>
            <small id="cmp-progress-detail" class="text-muted ms-2"></small>
        </div>
        <div class="progress" style="height:14px;border-radius:8px">
            <div id="cmp-progress-bar" class="progress-bar bg-danger progress-bar-striped progress-bar-animated" style="width:0%;border-radius:8px"></div>
        </div>
        <div class="text-center mt-3">
            <button type="button" id="cmp-cancel-btn" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-x-circle me-1"></i>Cancel
            </button>
        </div>
    </div>

    {{-- ══════════════ RESULTS ══════════════ --}}
    <div id="cmp-results-area" class="d-none">
        <div class="row g-4">

            {{-- ── Main panel ── --}}
            <div class="col-lg-9">

                {{-- Tabs (no controls here — moved into each panel) --}}
                <div class="cmp-tabs d-flex flex-wrap mb-3">
                    <button class="cmp-tab-btn active" data-cmp-tab="visual"><i class="bi bi-layout-split me-1"></i>Visual Diff</button>
                    <button class="cmp-tab-btn" data-cmp-tab="text"><i class="bi bi-fonts me-1"></i>Text Diff</button>
                    <button class="cmp-tab-btn" data-cmp-tab="overlay"><i class="bi bi-layers me-1"></i>Overlay</button>
                    <button class="cmp-tab-btn" data-cmp-tab="report"><i class="bi bi-bar-chart me-1"></i>Report</button>
                </div>

                {{-- ══ Visual panel — continuous side-by-side viewer ══ --}}
                <div id="cmp-panel-visual" data-cmp-panel="visual">

                    {{-- Viewer toolbar --}}
                    <div class="cmp-viewer-toolbar">
                        {{-- Zoom --}}
                        <button id="cmp-zoom-out" class="btn btn-sm btn-outline-secondary px-2" title="Zoom out"><i class="bi bi-dash-lg"></i></button>
                        <span id="cmp-zoom-label" class="badge bg-secondary" style="min-width:44px;font-size:.78rem">100%</span>
                        <button id="cmp-zoom-in" class="btn btn-sm btn-outline-secondary px-2" title="Zoom in"><i class="bi bi-plus-lg"></i></button>
                        <button id="cmp-fit-width" class="btn btn-sm btn-outline-secondary" title="Fit to panel width">
                            <i class="bi bi-arrows-expand me-1"></i>Fit
                        </button>

                        <div class="cmp-toolbar-sep"></div>

                        {{-- Sync scroll --}}
                        <div class="form-check form-switch mb-0 d-flex align-items-center gap-2">
                            <input class="form-check-input mt-0" type="checkbox" id="cmp-sync-scroll-toggle" checked>
                            <label class="form-check-label small fw-semibold" for="cmp-sync-scroll-toggle" style="cursor:pointer">
                                <i class="bi bi-arrow-down-up me-1"></i>Sync scroll
                            </label>
                        </div>

                        {{-- Diff overlay --}}
                        <div class="form-check form-switch mb-0 d-flex align-items-center gap-2">
                            <input class="form-check-input mt-0" type="checkbox" id="cmp-show-diff-overlay" checked>
                            <label class="form-check-label small fw-semibold" for="cmp-show-diff-overlay" style="cursor:pointer">
                                <i class="bi bi-circle-half me-1"></i>Highlight diff
                            </label>
                        </div>

                        {{-- Changed pages only filter --}}
                        <div class="form-check form-switch mb-0 d-flex align-items-center gap-2">
                            <input class="form-check-input mt-0" type="checkbox" id="cmp-changed-only">
                            <label class="form-check-label small fw-semibold" for="cmp-changed-only" style="cursor:pointer">
                                Changed only
                            </label>
                        </div>

                        <div class="cmp-toolbar-sep"></div>

                        {{-- Page navigation --}}
                        <button id="cmp-prev-page" class="btn btn-sm btn-outline-secondary px-2" disabled title="Previous page"><i class="bi bi-chevron-left"></i></button>
                        <span class="small fw-semibold" style="white-space:nowrap">
                            Page <span id="cmp-page-current">1</span> / <span id="cmp-page-total">—</span>
                        </span>
                        <button id="cmp-next-page" class="btn btn-sm btn-outline-secondary px-2" title="Next page"><i class="bi bi-chevron-right"></i></button>

                        <button id="cmp-next-diff" class="btn btn-sm btn-danger ms-1" title="Jump to next changed page">
                            <i class="bi bi-arrow-right-circle me-1"></i>Next diff
                        </button>

                        <span id="cmp-page-status" class="badge bg-secondary ms-1" style="font-size:.72rem">—</span>
                    </div>

                    {{-- Column headers --}}
                    <div class="cmp-col-headers">
                        <div class="cmp-col-header">
                            <i class="bi bi-file-earmark-text" style="opacity:.6"></i>
                            Original
                            <span class="badge-orig" id="cmp-orig-pages-lbl"></span>
                        </div>
                        <div style="width:4px;background:#555;flex-shrink:0"></div>
                        <div class="cmp-col-header">
                            <i class="bi bi-file-earmark-diff" style="opacity:.6"></i>
                            Modified
                            <span class="badge-mod" id="cmp-mod-pages-lbl"></span>
                        </div>
                    </div>

                    {{-- Side-by-side scrollable panels --}}
                    <div class="cmp-panels-wrap">
                        <div class="cmp-panel" id="cmp-panel-left">
                            <div class="text-muted small" style="color:#aaa!important;margin-top:auto;margin-bottom:auto">
                                Run comparison to see pages
                            </div>
                        </div>
                        <div class="cmp-panel-divider" id="cmp-panel-divider" title="Drag to resize"></div>
                        <div class="cmp-panel" id="cmp-panel-right"></div>
                    </div>

                    {{-- Thumbnail strip --}}
                    <div class="cmp-thumbs-wrap">
                        <span class="cmp-thumbs-label"><i class="bi bi-grid me-1"></i>Pages</span>
                        <div class="cmp-thumbs-strip" id="cmp-thumbs-strip">
                            <span class="text-muted small" style="opacity:.5">—</span>
                        </div>
                    </div>

                </div>{{-- /cmp-panel-visual --}}

                {{-- ══ Text diff panel ══ --}}
                <div id="cmp-panel-text" data-cmp-panel="text" class="d-none">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <button id="cmp-text-prev" class="btn btn-sm btn-outline-secondary px-2"><i class="bi bi-chevron-left"></i></button>
                        <span class="small fw-semibold">Page <span id="cmp-text-page-cur">1</span> / <span id="cmp-text-page-tot">—</span></span>
                        <button id="cmp-text-next" class="btn btn-sm btn-outline-secondary px-2"><i class="bi bi-chevron-right"></i></button>
                        <span id="cmp-text-status" class="badge bg-secondary ms-1" style="font-size:.72rem">—</span>
                    </div>
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4" id="cmp-text-diff-content">
                            <p class="text-muted">Select a page to view text differences.</p>
                        </div>
                    </div>
                </div>

                {{-- ══ Overlay panel ══ --}}
                <div id="cmp-panel-overlay" data-cmp-panel="overlay" class="d-none">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <button id="cmp-overlay-prev" class="btn btn-sm btn-outline-secondary px-2"><i class="bi bi-chevron-left"></i></button>
                        <span class="small fw-semibold">Page <span id="cmp-overlay-page-cur">1</span> / <span id="cmp-overlay-page-tot">—</span></span>
                        <button id="cmp-overlay-next" class="btn btn-sm btn-outline-secondary px-2"><i class="bi bi-chevron-right"></i></button>
                    </div>
                    <div class="card border-0 shadow-sm p-3 mb-3">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold mb-1">Original opacity</label>
                                <input type="range" id="cmp-opacity-a" class="form-range" min="0" max="1" step="0.05" value="0.5">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold mb-1">Modified opacity</label>
                                <input type="range" id="cmp-opacity-b" class="form-range" min="0" max="1" step="0.05" value="0.5">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold mb-1">Blend mode</label>
                                <select id="cmp-blend-mode" class="form-select form-select-sm">
                                    <option value="source-over">Normal</option>
                                    <option value="difference">Difference</option>
                                    <option value="multiply">Multiply</option>
                                    <option value="screen">Screen</option>
                                    <option value="exclusion">Exclusion</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="cmp-viewer-pane" id="cmp-overlay-container" style="text-align:center"></div>
                </div>

                {{-- ══ Report panel ══ --}}
                <div id="cmp-panel-report" data-cmp-panel="report" class="d-none">
                    <div id="cmp-report-summary" class="mb-4"></div>
                    <div id="cmp-report-page-table" class="card border-0 shadow-sm mb-4">
                        <div class="card-body p-0">
                            <table class="table table-sm table-hover mb-0" id="cmp-report-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Page</th>
                                        <th>Status</th>
                                        <th>Visual diff</th>
                                        <th>Words added</th>
                                        <th>Words removed</th>
                                    </tr>
                                </thead>
                                <tbody id="cmp-report-tbody"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button id="cmp-export-json" class="btn btn-outline-secondary btn-sm"><i class="bi bi-filetype-json me-1"></i>Export JSON</button>
                        <button id="cmp-export-html" class="btn btn-outline-secondary btn-sm"><i class="bi bi-filetype-html me-1"></i>Export HTML</button>
                    </div>
                </div>

            </div>{{-- /col-lg-9 --}}

            {{-- ── Sidebar ── --}}
            <div class="col-lg-3">
                <div class="cmp-sidebar">
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-white fw-bold small py-2">Summary</div>
                        <div class="card-body p-3">
                            <div class="cmp-stat-pill"><span>Total pages</span><strong id="cmp-stat-total">—</strong></div>
                            <div class="cmp-stat-pill" style="background:var(--cmp-danger-bg)"><span>Changed</span><strong id="cmp-stat-changed">—</strong></div>
                            <div class="cmp-stat-pill" style="background:var(--cmp-success-bg)"><span>Unchanged</span><strong id="cmp-stat-unchanged">—</strong></div>
                            <div class="cmp-stat-pill" style="background:#dbeafe"><span>Added</span><strong id="cmp-stat-added">—</strong></div>
                            <div class="cmp-stat-pill" style="background:var(--cmp-warning-bg)"><span>Removed</span><strong id="cmp-stat-removed">—</strong></div>
                            <div class="cmp-stat-pill"><span>Avg. diff</span><strong id="cmp-stat-avgdiff">—</strong></div>
                        </div>
                    </div>
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white fw-bold small py-2 d-flex justify-content-between align-items-center">
                            <span>Pages</span>
                            <button id="cmp-next-diff-sb" class="btn btn-outline-danger py-0 px-2" style="font-size:.7rem;border-radius:4px"
                                onclick="document.getElementById('cmp-next-diff').click()">Next diff</button>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush" id="cmp-pages-list"></ul>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>{{-- /container --}}

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
                    <li class="mb-2">Scroll both panels in sync, browse thumbnails and switch between <em>Visual</em>, <em>Text</em>, <em>Overlay</em> and <em>Report</em> tabs.</li>
                </ol>

                <h2 class="fw-bold mb-3">Features</h2>
                <div class="row g-3 mb-5">
                    <div class="col-sm-6"><div class="d-flex gap-3"><i class="bi bi-layout-split fs-4 text-danger mt-1"></i><div><strong>Synchronized Scroll</strong><br><span class="text-muted small">Both pages scroll together for effortless comparison.</span></div></div></div>
                    <div class="col-sm-6"><div class="d-flex gap-3"><i class="bi bi-eye fs-4 text-danger mt-1"></i><div><strong>Visual Pixel Diff</strong><br><span class="text-muted small">Pixel-level diff overlay using pixelmatch.</span></div></div></div>
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
                            <div class="accordion-body text-muted">No. Your files never leave your device. All comparison — rendering, pixel analysis, text extraction and OCR — runs entirely in your browser using JavaScript.</div>
                        </div>
                    </div>
                    <div class="accordion-item border-bottom">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">Can I compare scanned PDFs?</button>
                        </h3>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#cmp-faq">
                            <div class="accordion-body text-muted">Yes. Enable the <strong>Use OCR for scanned PDFs</strong> toggle before clicking Compare. Tesseract.js will extract text from rendered images. It is slower but works on image-only PDFs.</div>
                        </div>
                    </div>
                    <div class="accordion-item border-bottom">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">How does synchronized scroll work?</button>
                        </h3>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#cmp-faq">
                            <div class="accordion-body text-muted">When <strong>Sync scroll</strong> is enabled (default), scrolling either the Original or Modified panel automatically moves the other to the same relative position, so both documents stay aligned as you read.</div>
                        </div>
                    </div>
                    <div class="accordion-item border-bottom">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">Can I see visual differences?</button>
                        </h3>
                        <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#cmp-faq">
                            <div class="accordion-body text-muted">Yes. The <strong>Visual Diff</strong> tab shows the original and modified pages side-by-side. Changed areas are highlighted in red directly on top of each page. You can toggle the diff overlay on/off from the toolbar.</div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">Does this work offline?</button>
                        </h3>
                        <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#cmp-faq">
                            <div class="accordion-body text-muted">Once the page has loaded the JavaScript libraries, the comparison itself works without an internet connection.</div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-light border d-flex gap-3 align-items-start">
                    <i class="bi bi-info-circle-fill text-primary fs-5 mt-1"></i>
                    <div><strong>Is my PDF safe?</strong><br><span class="text-muted small">This tool processes PDFs locally in your browser. Your files stay on your device. We never receive, store or transmit your documents.</span></div>
                </div>

            </div>
        </div>
    </div>
</div>

@vite(['resources/css/app.css', 'resources/js/app.js'])
@endsection
