@extends('layouts.app')

@section('title', 'Flatten PDF - ToolPDF')

@section('content')

<style>
:root {
    --fp-gradient: linear-gradient(135deg, #ea580c 0%, #c2410c 100%);
    --fp-hover-bg: #fff7ed;
    --fp-btn-shadow: rgba(234,88,12,.3);
}
.fp-hero {
    background: var(--fp-gradient);
    color: #fff; padding: 44px 0 28px;
}
.fp-hero h1 { font-size: 1.95rem; font-weight: 800; margin-bottom: .35rem; }
.fp-hero .badge {
    background: rgba(255,255,255,.18); border: 1px solid rgba(255,255,255,.35);
    font-size: .73rem; padding: .3em .65em; border-radius: 20px; margin-right: .35rem;
}
.fp-drop {
    border: 2px dashed #d1d5db; border-radius: 12px; background: #fff;
    transition: border-color .2s, background .2s; cursor: pointer;
    min-height: 220px; padding: 32px 22px; text-align: center;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
}
.fp-drop:hover, .fp-drop.fp-drag-over {
    border-color: #ea580c;
    background: var(--fp-hover-bg);
}
.fp-drop-icon { font-size: 3rem; margin-bottom: .4rem; }
.fp-file-card {
    border-radius: 10px; background: #fff; border: 1px solid #e5e7eb;
    padding: 14px 16px; display: flex; justify-content: space-between; align-items: center;
}
.fp-convert-btn {
    background: var(--fp-gradient);
    border: none; color: #fff;
    font-size: 1.05rem; font-weight: 700; padding: 13px 40px;
    border-radius: 50px; box-shadow: 0 4px 16px var(--fp-btn-shadow);
    transition: opacity .2s, transform .1s;
}
.fp-convert-btn:hover:not(:disabled) { opacity: .92; transform: translateY(-1px); }
.fp-convert-btn:disabled { opacity: .42; cursor: not-allowed; }
</style>

<section class="fp-hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1>
                    <i class="bi bi-layers me-2"></i>
                    Flatten PDF
                </h1>
                <p class="mb-3" style="opacity:.9;font-size:1rem">
                    Make form fields and annotations permanent — converts interactive elements into static content.
                </p>
                <div class="d-flex flex-wrap gap-1">
                    <span class="badge">🔒 100% Local</span>
                    <span class="badge">⚡ Browser-based</span>
                </div>
            </div>
            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                <i class="bi bi-layers" style="font-size:4.5rem;opacity:.22"></i>
            </div>
        </div>
    </div>
</section>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            {{-- Drop zone --}}
            <div id="fp-zone" class="fp-drop text-warning">
                <div class="fp-drop-icon"><i class="bi bi-layers" style="color:#ea580c"></i></div>
                <h5 class="fw-bold mb-1 text-dark">Drop your PDF here</h5>
                <p class="text-muted mb-0 small">Click to browse or drag and drop your PDF file here</p>
                <input type="file" id="fp-input" class="d-none" accept="application/pdf,.pdf">
            </div>

            {{-- File card --}}
            <div id="fp-card" class="fp-file-card mt-3 d-none">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-file-earmark-pdf fs-3" style="color:#ea580c"></i>
                    <div>
                        <div class="fw-semibold text-truncate" style="max-width:280px" id="fp-name">—</div>
                        <small class="text-muted" id="fp-size">—</small>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger rounded-circle px-2" id="fp-remove" title="Remove">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            {{-- Progress --}}
            <div id="fp-progress" class="mt-4 d-none">
                <div class="d-flex justify-content-between mb-1 small">
                    <span id="fp-progress-msg" class="text-muted">Processing…</span>
                </div>
                <div class="progress" style="height: 10px; border-radius: 6px;">
                    <div id="fp-progress-bar"
                         class="progress-bar progress-bar-striped progress-bar-animated"
                         style="width:0%; background: var(--fp-gradient);"></div>
                </div>
            </div>

            {{-- Action --}}
            <div class="text-center mt-4">
                <button type="button" id="fp-btn" class="fp-convert-btn" disabled>
                    <i class="bi bi-layers me-2"></i>Flatten &amp; Download
                </button>
            </div>

            <div id="fp-error" class="alert alert-danger mt-3 d-none"></div>

            <p class="text-center mt-3 small text-muted">
                <i class="bi bi-shield-lock-fill text-success me-1"></i>
                Your file never leaves your device — all processing happens locally in your browser.
            </p>

        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf-lib/1.17.1/pdf-lib.min.js"></script>
<script src="{{ asset('js/tools/flatten-pdf.js') }}"></script>
@endpush

@endsection
