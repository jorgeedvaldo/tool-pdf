@extends('layouts.app')

@section('title', 'Compare PDF Files Online - Find Text and Visual Differences - ToolPDF')

@section('content')

{{-- ── Styles ──────────────────────────────────────────────────────────────── --}}
<style>
/* ─── Variables ─── */
:root {
    --cmp-primary: #E5322D;
    --cmp-dark:    #222222;
    --cmp-muted:   #6B7280;
    --cmp-bg:      #F8F9FA;
    --cmp-success-bg: #D1FAE5;
    --cmp-danger-bg:  #FEE2E2;
    --cmp-warning-bg: #FEF3C7;
    --cmp-added-bg:   #DCFCE7;
    --cmp-removed-bg: #FEE2E2;
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
    font-size: .75rem;
    padding: .35em .7em;
    border-radius: 20px;
    margin-right: .4rem;
}

/* ─── Upload cards ─── */
.cmp-upload-card {
    border: 2px dashed #d1d5db;
    border-radius: 12px;
    background: #fff;
    transition: border-color .2s, background .2s;
    cursor: pointer;
    min-height: 180px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 28px 20px;
    text-align: center;
}
.cmp-upload-card:hover, .cmp-upload-card.cmp-drag-over {
    border-color: var(--cmp-primary);
    background: #fff5f5;
}
.cmp-upload-icon { font-size: 2.5rem; color: var(--cmp-primary); margin-bottom: .6rem; }
.cmp-file-card { border-radius: 10px; background: #fff; border: 1px solid #e5e7eb; padding: 16px 18px; }

/* ─── Compare button ─── */
.cmp-compare-btn {
    background: var(--cmp-primary);
    border: none;
    color: #fff;
    font-size: 1.1rem;
    font-weight: 700;
    padding: 14px 44px;
    border-radius: 50px;
    transition: opacity .2s, transform .1s;
    box-shadow: 0 4px 16px rgba(229,50,45,.3);
}
.cmp-compare-btn:hover:not(:disabled) { opacity: .9; transform: translateY(-1px); }
.cmp-compare-btn:disabled { opacity: .45; cursor: not-allowed; }

/* ─── Progress ─── */
#cmp-progress-area { max-width: 540px; margin: 0 auto; }

/* ─── Tabs ─── */
.cmp-tabs { border-bottom: 2px solid #e5e7eb; margin-bottom: 0; gap: .25rem; }
.cmp-tabs .cmp-tab-btn {
    border: none;
    background: transparent;
    padding: 10px 18px;
    font-weight: 600;
    color: var(--cmp-muted);
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    cursor: pointer;
    transition: color .15s, border-color .15s;
    white-space: nowrap;
}
.cmp-tabs .cmp-tab-btn.active { color: var(--cmp-primary); border-bottom-color: var(--cmp-primary); }
.cmp-tabs .cmp-tab-btn:hover:not(.active) { color: var(--cmp-dark); }

/* ─── Side-by-side viewer ─── */
.cmp-viewer-pane {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #fafafa;
    overflow: auto;
    min-height: 300px;
    padding: 8px;
}
.cmp-viewer-label {
    font-size: .7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: var(--cmp-muted);
    padding: 4px 8px;
    background: #f3f4f6;
    border-radius: 4px;
    margin-bottom: 8px;
    display: inline-block;
}

/* ─── Text diff ─── */
.pdf-diff-added    { background: var(--cmp-added-bg);   color: #15803d; border-radius: 2px; padding: 0 2px; }
.pdf-diff-removed  { background: var(--cmp-removed-bg); color: #b91c1c; text-decoration: line-through; border-radius: 2px; padding: 0 2px; }
.pdf-diff-unchanged { color: #374151; }
.cmp-text-diff-wrap { font-family: 'Georgia', serif; font-size: .92rem; line-height: 1.7; white-space: pre-wrap; word-break: break-word; }

/* ─── Sidebar ─── */
.cmp-sidebar { position: sticky; top: 16px; }
.cmp-stat-pill {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 6px 10px;
    border-radius: 6px;
    background: #f3f4f6;
    font-size: .82rem;
    margin-bottom: 5px;
}
.cmp-stat-pill strong { font-size: .95rem; }
#cmp-pages-list { max-height: 320px; overflow-y: auto; font-size: .82rem; }
#cmp-pages-list .active { background: var(--cmp-danger-bg); color: var(--cmp-primary); border-color: transparent; }

/* ─── Overlay sliders ─── */
.cmp-overlay-controls label { font-size: .8rem; color: var(--cmp-muted); }

/* ─── Report stat boxes ─── */
.cmp-stat-box { background: #f3f4f6; border-radius: 10px; padding: 18px; text-align: center; font-size: .8rem; color: var(--cmp-muted); }
.cmp-stat-box.cmp-stat-changed { background: var(--cmp-danger-bg); }
.cmp-stat-box.cmp-stat-ok     { background: var(--cmp-success-bg); }
.cmp-stat-num { font-size: 2rem; font-weight: 800; color: var(--cmp-dark); line-height: 1; margin-bottom: 4px; }

/* ─── Privacy notice ─── */
.cmp-privacy-notice { font-size: .8rem; color: var(--cmp-muted); }
.cmp-privacy-notice i { color: #22c55e; }
</style>

{{-- ══════════════════════════════════════════════════════════════
     HERO
══════════════════════════════════════════════════════════════ --}}
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
                <i class="bi bi-file-diff" style="font-size:5rem;opacity:.3;"></i>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════════
     TOOL AREA
══════════════════════════════════════════════════════════════ --}}
<div class="container py-5" data-tool="compare-pdf">

    {{-- ── Upload section ──────────────────────────────────────── --}}
    <div class="row g-4 mb-4" id="cmp-upload-section">
        {{-- Original PDF --}}
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

        {{-- Modified PDF --}}
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

    {{-- ── Options row ──────────────────────────────────────────── --}}
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
                <label class="form-check-label small fw-semibold" for="cmp-ocr-toggle">
                    Use OCR for scanned PDFs
                </label>
            </div>
            <div id="cmp-ocr-warning" class="alert alert-warning py-1 mt-1 small d-none">
                <i class="bi bi-clock me-1"></i> OCR can be slower because everything runs locally in your browser.
            </div>
        </div>
        <div class="col-md-4 text-md-end">
            <p class="cmp-privacy-notice mb-1"><i class="bi bi-shield-lock-fill me-1"></i> Your files stay on your device. No upload to any server.</p>
        </div>
    </div>

    {{-- ── Compare button ──────────────────────────────────────── --}}
    <div class="text-center mb-5">
        <button type="button" id="cmp-compare-btn" class="cmp-compare-btn" disabled>
            <i class="bi bi-file-diff me-2"></i>Compare PDFs
        </button>
    </div>

    {{-- ── Progress ──────────────────────────────────────────────── --}}
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

    {{-- ══════════════════════════════════════════════════════════
         RESULTS
    ══════════════════════════════════════════════════════════ --}}
    <div id="cmp-results-area" class="d-none">
        <div class="row g-4">

            {{-- Main panel --}}
            <div class="col-lg-9">

                {{-- Toolbar --}}
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">

                    {{-- Tabs --}}
                    <div class="cmp-tabs d-flex flex-wrap">
                        <button class="cmp-tab-btn active" data-cmp-tab="visual"><i class="bi bi-eye me-1"></i>Visual Differences</button>
                        <button class="cmp-tab-btn" data-cmp-tab="text"><i class="bi bi-fonts me-1"></i>Text Differences</button>
                        <button class="cmp-tab-btn" data-cmp-tab="overlay"><i class="bi bi-layers me-1"></i>Overlay</button>
                        <button class="cmp-tab-btn" data-cmp-tab="report"><i class="bi bi-bar-chart me-1"></i>Report</button>
                    </div>

                    {{-- Zoom controls --}}
                    <div class="d-flex align-items-center gap-1">
                        <button id="cmp-zoom-out" class="btn btn-sm btn-outline-secondary" title="Zoom out"><i class="bi bi-zoom-out"></i></button>
                        <span id="cmp-zoom-label" class="badge bg-secondary">150%</span>
                        <button id="cmp-zoom-in" class="btn btn-sm btn-outline-secondary" title="Zoom in"><i class="bi bi-zoom-in"></i></button>
                        <button id="cmp-zoom-reset" class="btn btn-sm btn-outline-secondary ms-1" title="Reset zoom"><i class="bi bi-aspect-ratio"></i></button>
                    </div>
                </div>

                {{-- Page navigation --}}
                <div class="d-flex align-items-center gap-2 mb-3">
                    <button id="cmp-prev-page" class="btn btn-sm btn-outline-secondary" disabled><i class="bi bi-chevron-left"></i></button>
                    <span class="small">Page <strong id="cmp-page-current">1</strong> of <strong id="cmp-page-total">—</strong></span>
                    <button id="cmp-next-page" class="btn btn-sm btn-outline-secondary"><i class="bi bi-chevron-right"></i></button>
                    <button id="cmp-next-diff" class="btn btn-sm btn-outline-danger ms-2"><i class="bi bi-arrow-right-circle me-1"></i>Next difference</button>
                    <span id="cmp-page-status" class="badge ms-1 bg-secondary">—</span>
                </div>

                {{-- ── Visual panel ── --}}
                <div id="cmp-panel-visual" data-cmp-panel="visual">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <div class="cmp-viewer-label">Original</div>
                            <div class="cmp-viewer-pane" id="cmp-visual-original"></div>
                        </div>
                        <div class="col-md-4">
                            <div class="cmp-viewer-label">Modified</div>
                            <div class="cmp-viewer-pane" id="cmp-visual-modified"></div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="cmp-viewer-label">Diff</span>
                                <span id="cmp-visual-diff-pct" class="badge bg-danger">—</span>
                            </div>
                            <div class="cmp-viewer-pane" id="cmp-visual-diff"></div>
                        </div>
                    </div>
                </div>

                {{-- ── Text panel ── --}}
                <div id="cmp-panel-text" data-cmp-panel="text" class="d-none">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4" id="cmp-text-diff-content">
                            <p class="text-muted">Select a page to view text differences.</p>
                        </div>
                    </div>
                </div>

                {{-- ── Overlay panel ── --}}
                <div id="cmp-panel-overlay" data-cmp-panel="overlay" class="d-none">
                    <div class="cmp-overlay-controls card border-0 shadow-sm p-3 mb-3">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label mb-1 fw-semibold small">Original opacity</label>
                                <input type="range" id="cmp-opacity-a" class="form-range" min="0" max="1" step="0.05" value="0.5">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label mb-1 fw-semibold small">Modified opacity</label>
                                <input type="range" id="cmp-opacity-b" class="form-range" min="0" max="1" step="0.05" value="0.5">
                            </div>
                        </div>
                    </div>
                    <div class="cmp-viewer-pane" id="cmp-overlay-container" style="text-align:center"></div>
                </div>

                {{-- ── Report panel ── --}}
                <div id="cmp-panel-report" data-cmp-panel="report" class="d-none">
                    <div id="cmp-report-summary" class="mb-4"></div>
                    <div class="d-flex gap-2">
                        <button id="cmp-export-json" class="btn btn-outline-secondary btn-sm"><i class="bi bi-filetype-json me-1"></i>Export JSON</button>
                        <button id="cmp-export-html" class="btn btn-outline-secondary btn-sm"><i class="bi bi-filetype-html me-1"></i>Export HTML</button>
                    </div>
                </div>
            </div>

            {{-- ── Sidebar ── --}}
            <div class="col-lg-3">
                <div class="cmp-sidebar">
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-white fw-bold small py-2">Summary</div>
                        <div class="card-body p-3">
                            <div class="cmp-stat-pill"><span>Total pages</span><strong id="cmp-stat-total">—</strong></div>
                            <div class="cmp-stat-pill" style="background:var(--cmp-danger-bg)"><span>Changed</span><strong id="cmp-stat-changed">—</strong></div>
                            <div class="cmp-stat-pill" style="background:var(--cmp-success-bg)"><span>Unchanged</span><strong id="cmp-stat-unchanged">—</strong></div>
                            <div class="cmp-stat-pill" style="background:#DBEAFE"><span>Added</span><strong id="cmp-stat-added">—</strong></div>
                            <div class="cmp-stat-pill" style="background:var(--cmp-warning-bg)"><span>Removed</span><strong id="cmp-stat-removed">—</strong></div>
                            <div class="cmp-stat-pill"><span>Avg. diff</span><strong id="cmp-stat-avgdiff">—</strong></div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white fw-bold small py-2 d-flex justify-content-between">
                            <span>Pages</span>
                            <button id="cmp-next-diff-sb" class="btn btn-xs btn-outline-danger py-0 px-2" style="font-size:.72rem" onclick="document.getElementById('cmp-next-diff').click()">
                                Next diff
                            </button>
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

{{-- ══════════════════════════════════════════════════════════════
     SEO CONTENT
══════════════════════════════════════════════════════════════ --}}
<div class="bg-white py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">

                <h2 class="fw-bold mb-4">How to compare PDF files online</h2>
                <ol class="mb-5">
                    <li class="mb-2">Upload your <strong>Original PDF</strong> (the reference document) to the left card.</li>
                    <li class="mb-2">Upload your <strong>Modified PDF</strong> (the updated version) to the right card.</li>
                    <li class="mb-2">Adjust the <em>Sensitivity</em> slider if needed.</li>
                    <li class="mb-2">Click <strong>Compare PDFs</strong> and wait for the analysis to complete.</li>
                    <li class="mb-2">Browse results using the <em>Visual Differences</em>, <em>Text Differences</em>, <em>Overlay</em> and <em>Report</em> tabs.</li>
                </ol>

                <h2 class="fw-bold mb-3">Features</h2>
                <div class="row g-3 mb-5">
                    <div class="col-sm-6">
                        <div class="d-flex gap-3">
                            <i class="bi bi-eye fs-4 text-danger mt-1"></i>
                            <div><strong>Visual Comparison</strong><br><span class="text-muted small">Side-by-side pixel-level diff powered by pixelmatch.</span></div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex gap-3">
                            <i class="bi bi-fonts fs-4 text-danger mt-1"></i>
                            <div><strong>Text Differences</strong><br><span class="text-muted small">Word-by-word highlighting using jsdiff.</span></div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex gap-3">
                            <i class="bi bi-layers fs-4 text-danger mt-1"></i>
                            <div><strong>Overlay Mode</strong><br><span class="text-muted small">Blend both pages with adjustable opacity.</span></div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex gap-3">
                            <i class="bi bi-search fs-4 text-danger mt-1"></i>
                            <div><strong>OCR Support</strong><br><span class="text-muted small">Compare scanned PDFs using Tesseract.js (browser-based).</span></div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex gap-3">
                            <i class="bi bi-file-earmark-text fs-4 text-danger mt-1"></i>
                            <div><strong>Detailed Report</strong><br><span class="text-muted small">Export comparison report as JSON or HTML.</span></div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex gap-3">
                            <i class="bi bi-shield-lock fs-4 text-danger mt-1"></i>
                            <div><strong>100% Private</strong><br><span class="text-muted small">All processing happens in your browser. Zero upload.</span></div>
                        </div>
                    </div>
                </div>

                <h2 class="fw-bold mb-3">Frequently Asked Questions</h2>
                <div class="accordion accordion-flush mb-5" id="cmp-faq">

                    <div class="accordion-item border-bottom">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                Are my PDF files uploaded to a server?
                            </button>
                        </h3>
                        <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#cmp-faq">
                            <div class="accordion-body text-muted">
                                No. Your files never leave your device. All comparison work — rendering, pixel analysis, text extraction and OCR — happens entirely inside your web browser using JavaScript.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item border-bottom">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                Can I compare scanned PDFs?
                            </button>
                        </h3>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#cmp-faq">
                            <div class="accordion-body text-muted">
                                Yes. Enable the <strong>Use OCR for scanned PDFs</strong> toggle before clicking Compare. Tesseract.js will extract text from the rendered page images. It is slower than standard text extraction but works on image-only PDFs.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item border-bottom">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                Can I see visual differences?
                            </button>
                        </h3>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#cmp-faq">
                            <div class="accordion-body text-muted">
                                Yes. The <strong>Visual Differences</strong> tab shows the original page, the modified page and a highlighted diff image side by side. The red/pink pixels in the diff image indicate areas that changed.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item border-bottom">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                Can I compare text differences?
                            </button>
                        </h3>
                        <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#cmp-faq">
                            <div class="accordion-body text-muted">
                                Yes. The <strong>Text Differences</strong> tab shows words that were added (highlighted green) and words that were removed (highlighted red with strikethrough), page by page.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                Does this tool work offline?
                            </button>
                        </h3>
                        <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#cmp-faq">
                            <div class="accordion-body text-muted">
                                Once the page has loaded, all processing is done locally, so an internet connection is not required for the comparison itself. However, you need to load the page once to download the necessary JavaScript libraries.
                            </div>
                        </div>
                    </div>

                </div>

                <div class="alert alert-light border d-flex gap-3 align-items-start">
                    <i class="bi bi-info-circle-fill text-primary fs-5 mt-1"></i>
                    <div>
                        <strong>Is my PDF safe?</strong><br>
                        <span class="text-muted small">This tool processes PDFs locally in your browser. Your files stay on your device. We never receive, store or transmit your documents to any server.</span>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

{{-- Vite assets (loads compare-pdf JS only when data-tool attribute is present) --}}
@vite(['resources/css/app.css', 'resources/js/app.js'])

@endsection
