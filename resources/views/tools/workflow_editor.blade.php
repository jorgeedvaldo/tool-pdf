@extends('layouts.app')

@section('title', 'Visual Workflow Editor - ToolPDF')

@section('content')
<style>
/* ── Hero ──────────────────────────────────────────────────────── */
.wf-hero {
    background: linear-gradient(135deg, #E5322D 0%, #b52420 100%);
    color: #fff;
    padding: 2rem 0 1.5rem;
}
.wf-hero h1 { font-size: 1.9rem; font-weight: 800; letter-spacing: -0.5px; }
.wf-hero p  { opacity: .88; margin-bottom: 0; }

/* ── Editor shell ──────────────────────────────────────────────── */
#wf-shell {
    height: calc(100vh - 140px);
    min-height: 520px;
    display: flex;
    flex-direction: column;
    background: #f1f3f5;
}

/* ── Top toolbar ───────────────────────────────────────────────── */
#wf-toolbar {
    display: flex;
    align-items: center;
    gap: .5rem;
    padding: .5rem .75rem;
    background: #fff;
    border-bottom: 1px solid #dee2e6;
    flex-shrink: 0;
    flex-wrap: wrap;
}
#wf-toolbar .badge-beta {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffc107;
    font-size: .72rem;
    padding: .3em .6em;
    border-radius: 20px;
}
#wf-toolbar .sep { width: 1px; height: 26px; background: #dee2e6; margin: 0 .15rem; }

/* ── Three-panel row ───────────────────────────────────────────── */
#wf-panels {
    display: flex;
    flex: 1;
    overflow: hidden;
}

/* ── Left palette ──────────────────────────────────────────────── */
#wf-palette {
    width: 200px;
    flex-shrink: 0;
    background: #fff;
    border-right: 1px solid #dee2e6;
    overflow-y: auto;
    padding: .5rem .4rem;
}
#wf-palette h6.cat-label {
    font-size: .65rem;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: #6c757d;
    margin: .6rem .3rem .25rem;
    padding-bottom: .2rem;
    border-bottom: 1px solid #f0f0f0;
}
.palette-card {
    display: flex;
    align-items: center;
    gap: .45rem;
    padding: .42rem .5rem;
    border-radius: 7px;
    cursor: grab;
    font-size: .77rem;
    font-weight: 600;
    color: #212529;
    margin-bottom: .18rem;
    border: 1.5px solid transparent;
    transition: background .12s, border-color .12s;
    user-select: none;
}
.palette-card:hover {
    background: #f8f9fa;
    border-color: #dee2e6;
}
.palette-card .p-icon {
    width: 26px; height: 26px;
    border-radius: 6px;
    display: flex; align-items: center; justify-content: center;
    color: #fff;
    font-size: .85rem;
    flex-shrink: 0;
}

/* ── Canvas ────────────────────────────────────────────────────── */
#wf-canvas-wrap {
    flex: 1;
    position: relative;
    overflow: hidden;
}
#wf-canvas {
    position: absolute;
    inset: 0;
    background: #fafafa;
    background-image: radial-gradient(#e0e0e0 1px, transparent 1px);
    background-size: 24px 24px;
    overflow: hidden;
}
#wf-svg {
    position: absolute;
    inset: 0;
    width: 100%; height: 100%;
    pointer-events: none;
    z-index: 5;
}
#wf-canvas .wf-empty-hint {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    pointer-events: none;
    color: #adb5bd;
    text-align: center;
    gap: .5rem;
}
#wf-canvas .wf-empty-hint i { font-size: 3rem; }
#wf-canvas .wf-empty-hint p { font-size: .88rem; max-width: 240px; line-height: 1.4; }

/* ── Nodes ─────────────────────────────────────────────────────── */
.wf-node {
    position: absolute;
    width: 160px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,.12);
    background: #fff;
    border: 2px solid transparent;
    cursor: grab;
    user-select: none;
    transition: box-shadow .15s;
    z-index: 10;
}
.wf-node:hover { box-shadow: 0 4px 16px rgba(0,0,0,.16); }
.wf-node.selected {
    border-color: #E5322D;
    box-shadow: 0 0 0 3px rgba(229,50,45,.2);
}
.wf-node-header {
    border-radius: 8px 8px 0 0;
    padding: 8px 10px;
    color: #fff;
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: .82rem;
    font-weight: 700;
    cursor: grab;
}
.wf-node-body {
    padding: 8px 10px;
    font-size: .75rem;
    color: #6c757d;
    line-height: 1.3;
}
.wf-node-footer {
    padding: 2px 10px 6px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: .62rem;
}
.wf-node-footer .type-badge {
    border-radius: 10px;
    padding: .1em .45em;
    font-weight: 600;
}
.wf-port {
    width: 14px; height: 14px;
    border-radius: 50%;
    border: 2px solid #fff;
    cursor: crosshair;
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    z-index: 20;
    transition: transform .1s;
}
.wf-port:hover { transform: translateY(-50%) scale(1.4); }
.wf-port-in  { left: -8px; }
.wf-port-out { right: -8px; }
.wf-node-del {
    position: absolute;
    top: 4px; right: 6px;
    width: 16px; height: 16px;
    border-radius: 50%;
    background: rgba(255,255,255,.25);
    border: none;
    color: #fff;
    font-size: .65rem;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    line-height: 1;
    z-index: 15;
    opacity: 0;
    transition: opacity .15s;
}
.wf-node:hover .wf-node-del { opacity: 1; }

/* ── SVG edges ─────────────────────────────────────────────────── */
.wf-edge { fill: none; stroke: #6c757d; stroke-width: 2.5; marker-end: url(#arrow); }
.wf-edge-ghost { fill: none; stroke: #E5322D; stroke-width: 2; stroke-dasharray: 6 4; }

/* ── Right properties panel ────────────────────────────────────── */
#wf-props {
    width: 250px;
    flex-shrink: 0;
    background: #fff;
    border-left: 1px solid #dee2e6;
    overflow-y: auto;
    padding: .75rem;
}
#wf-props h6.props-title {
    font-size: .78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: #495057;
    margin-bottom: .75rem;
    padding-bottom: .35rem;
    border-bottom: 1px solid #f0f0f0;
}
#wf-props .prop-empty {
    text-align: center;
    color: #adb5bd;
    padding: 2rem .5rem;
    font-size: .82rem;
}
.prop-row { margin-bottom: .75rem; }
.prop-row label { font-size: .75rem; font-weight: 600; color: #495057; display: block; margin-bottom: .2rem; }
.prop-row .form-control, .prop-row .form-select {
    font-size: .8rem;
    padding: .25rem .5rem;
}
.prop-node-info {
    background: #f8f9fa;
    border-radius: 8px;
    padding: .6rem .75rem;
    margin-bottom: .75rem;
    font-size: .78rem;
}

/* ── Bottom bar ────────────────────────────────────────────────── */
#wf-bottom {
    padding: .45rem .75rem;
    background: #fff;
    border-top: 1px solid #dee2e6;
    display: flex;
    align-items: center;
    gap: .4rem;
    flex-shrink: 0;
    flex-wrap: wrap;
}
#wf-bottom .tpl-btn {
    font-size: .74rem;
    padding: .25rem .55rem;
    border-radius: 20px;
}

/* ── Progress modal ────────────────────────────────────────────── */
#wf-progress-modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.45);
    z-index: 9999;
    align-items: center;
    justify-content: center;
}
#wf-progress-modal.show { display: flex; }
#wf-progress-box {
    background: #fff;
    border-radius: 14px;
    padding: 2rem 2.5rem;
    min-width: 340px;
    max-width: 480px;
    box-shadow: 0 8px 40px rgba(0,0,0,.18);
    text-align: center;
}
#wf-progress-box h5 { font-size: 1.05rem; font-weight: 700; margin-bottom: 1rem; }
.prog-step-row {
    display: flex;
    align-items: center;
    gap: .5rem;
    padding: .25rem 0;
    border-bottom: 1px solid #f0f0f0;
    font-size: .82rem;
}
.prog-step-row:last-child { border-bottom: none; }
.prog-step-icon { width: 20px; text-align: center; font-size: .9rem; }

/* ── Templates section ─────────────────────────────────────────── */
.tpl-card {
    border: 1.5px solid #e9ecef;
    border-radius: 10px;
    padding: 1rem;
    cursor: pointer;
    transition: border-color .15s, box-shadow .15s, transform .12s;
    text-align: center;
    background: #fff;
    height: 100%;
}
.tpl-card:hover {
    border-color: #E5322D;
    box-shadow: 0 2px 12px rgba(229,50,45,.14);
    transform: translateY(-2px);
}
.tpl-card i { font-size: 1.6rem; color: #E5322D; display: block; margin-bottom: .4rem; }
.tpl-card h6 { font-size: .82rem; font-weight: 700; margin: 0 0 .35rem; }
.tpl-chain-pill {
    display: inline-block;
    background: #f1f3f5;
    border-radius: 20px;
    padding: .1rem .4rem;
    font-size: .65rem;
    color: #495057;
    margin: .1rem .05rem 0;
}

/* ── How to use steps ──────────────────────────────────────────── */
.how-step {
    display: flex;
    gap: 1rem;
    align-items: flex-start;
    margin-bottom: 1.5rem;
}
.how-step .step-num {
    width: 36px; height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, #E5322D, #b52420);
    color: #fff;
    font-weight: 800;
    font-size: 1rem;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    margin-top: .1rem;
}
</style>

<!-- Hero -->
<div class="wf-hero">
    <div class="container">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div>
                <h1 class="mb-1"><i class="bi bi-diagram-3 me-2"></i>Visual Workflow Editor</h1>
                <p>Build custom PDF pipelines by connecting processing nodes &mdash; no code required.</p>
            </div>
            <div class="ms-auto">
                <span class="badge bg-white text-danger fw-bold px-3 py-2" style="font-size:.8rem;">
                    <i class="bi bi-cpu me-1"></i>Runs 100% in your browser
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Editor Shell -->
<div class="container-fluid px-0">
<div id="wf-shell">

    <!-- Toolbar -->
    <div id="wf-toolbar">
        <span class="badge-beta"><i class="bi bi-exclamation-triangle-fill me-1"></i>Beta &mdash; Workflow execution supports browser-side tools only</span>
        <div class="sep"></div>
        <button id="btn-execute" class="btn btn-sm btn-danger fw-bold px-3">
            <i class="bi bi-play-fill me-1"></i>Execute
        </button>
        <button id="btn-clear" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-trash me-1"></i>Clear
        </button>
        <div class="sep"></div>
        <button id="btn-save-wf" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-download me-1"></i>Save Workflow
        </button>
        <button id="btn-load-wf" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-upload me-1"></i>Load Workflow
        </button>
        <input type="file" id="wf-load-input" class="d-none" accept=".json">
    </div>

    <!-- Three panels -->
    <div id="wf-panels">

        <!-- Left: Tool Palette -->
        <div id="wf-palette">
            <!-- populated by JS -->
        </div>

        <!-- Center: Canvas -->
        <div id="wf-canvas-wrap">
            <div id="wf-canvas">
                <svg id="wf-svg">
                    <defs>
                        <marker id="arrow" markerWidth="8" markerHeight="8" refX="6" refY="3" orient="auto">
                            <path d="M0,0 L0,6 L8,3 z" fill="#6c757d"/>
                        </marker>
                    </defs>
                </svg>
                <div class="wf-empty-hint" id="wf-empty-hint">
                    <i class="bi bi-diagram-3"></i>
                    <p>Drag tools from the left panel onto the canvas, then connect their ports to build a workflow.</p>
                </div>
            </div>
        </div>

        <!-- Right: Properties -->
        <div id="wf-props">
            <h6 class="props-title"><i class="bi bi-sliders me-1"></i>Properties</h6>
            <div class="prop-empty" id="props-empty">
                <i class="bi bi-cursor-text d-block mb-2" style="font-size:1.8rem;"></i>
                Click a node to edit its settings
            </div>
            <div id="props-form" class="d-none"></div>
        </div>

    </div><!-- /panels -->

    <!-- Bottom: Templates bar -->
    <div id="wf-bottom">
        <small class="text-muted fw-bold me-1"><i class="bi bi-lightning-charge me-1"></i>Templates:</small>
        <!-- populated by JS -->
    </div>

</div><!-- /shell -->
</div>

<!-- Progress Modal -->
<div id="wf-progress-modal">
    <div id="wf-progress-box">
        <h5><i class="bi bi-gear-fill text-danger me-2"></i>Running Workflow</h5>
        <div id="wf-prog-steps" class="text-start mb-3" style="font-size:.82rem; max-height:160px; overflow-y:auto;"></div>
        <div class="progress mb-2" style="height:10px;">
            <div id="wf-prog-bar" class="progress-bar bg-danger progress-bar-striped progress-bar-animated" style="width:0%"></div>
        </div>
        <small class="text-muted" id="wf-prog-label">Initializing...</small>
        <div id="wf-prog-done" class="mt-3 d-none">
            <button class="btn btn-success btn-sm" id="btn-close-modal">
                <i class="bi bi-check-lg me-1"></i>Done
            </button>
        </div>
    </div>
</div>

<!-- Hidden file input for workflow execution -->
<input type="file" id="wf-exec-file" class="d-none" accept=".pdf,.docx">

<!-- Templates Section -->
<div class="container py-5">
    <div class="row justify-content-center mb-4">
        <div class="col-lg-10 text-center">
            <h2 class="fw-bold" style="font-size:1.35rem;">
                <i class="bi bi-lightning-charge-fill text-danger me-2"></i>Quick Templates
            </h2>
            <p class="text-muted" style="font-size:.9rem;">Click any template to instantly load a pre-built workflow on the canvas.</p>
        </div>
    </div>
    <div class="row justify-content-center g-3" id="tpl-cards-row">
        <!-- populated by JS -->
    </div>
</div>

<!-- How to Use -->
<div class="container pb-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <h3 class="fw-bold mb-4" style="font-size:1.2rem;">
                        <i class="bi bi-info-circle-fill text-danger me-2"></i>How to Use
                    </h3>

                    <div class="how-step">
                        <div class="step-num">1</div>
                        <div>
                            <strong>Add nodes</strong>
                            <p class="mb-0 text-muted" style="font-size:.85rem;">Drag tool cards from the left palette onto the canvas, or click a template below the editor to load a pre-built chain instantly.</p>
                        </div>
                    </div>

                    <div class="how-step">
                        <div class="step-num">2</div>
                        <div>
                            <strong>Connect nodes</strong>
                            <p class="mb-0 text-muted" style="font-size:.85rem;">Click and drag from a node&rsquo;s <em>output port</em> (right circle) to another node&rsquo;s <em>input port</em> (left circle). A bezier curve appears when connected.</p>
                        </div>
                    </div>

                    <div class="how-step">
                        <div class="step-num">3</div>
                        <div>
                            <strong>Configure each step</strong>
                            <p class="mb-0 text-muted" style="font-size:.85rem;">Click any node to select it. Adjustable settings (rotation angle, watermark text, password, compression quality&hellip;) appear in the right <strong>Properties</strong> panel.</p>
                        </div>
                    </div>

                    <div class="how-step" style="margin-bottom:0;">
                        <div class="step-num">4</div>
                        <div>
                            <strong>Execute &amp; Download</strong>
                            <p class="mb-0 text-muted" style="font-size:.85rem;">Click <strong>Execute</strong> in the toolbar. You&rsquo;ll be prompted to choose a file if none is loaded. The workflow runs step-by-step in your browser; the result is downloaded automatically when the <em>Download</em> node is reached.</p>
                        </div>
                    </div>

                    <hr class="my-4">
                    <div class="alert alert-warning mb-0 py-2 small">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        <strong>Note:</strong> The <em>PDF &rarr; Word</em> and <em>Word &rarr; PDF</em> nodes require a server-side endpoint and are marked as server-only in the palette. All other nodes run entirely in your browser using <strong>pdf-lib</strong>, <strong>PDF.js</strong>, and <strong>jsPDF</strong>.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CDN Libraries (must load before workflow-editor.js) -->
<script src="https://cdn.jsdelivr.net/npm/pdf-lib@1.17.1/dist/pdf-lib.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
<script>pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

@push('scripts')
<script src="{{ asset('js/tools/workflow-editor.js') }}"></script>
@endpush

@endsection
