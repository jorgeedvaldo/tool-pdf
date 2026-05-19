@extends('layouts.app')

@section('title', 'Repair PDF - ToolPDF')

@section('content')

<style>
:root {
    --rp-gradient: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
    --rp-hover-bg: #f0fdfa;
    --rp-btn-shadow: rgba(13,148,136,.3);
}
.rp-hero {
    background: var(--rp-gradient);
    color: #fff; padding: 44px 0 28px;
}
.rp-hero h1 { font-size: 1.95rem; font-weight: 800; margin-bottom: .35rem; }
.rp-hero .badge {
    background: rgba(255,255,255,.18); border: 1px solid rgba(255,255,255,.35);
    font-size: .73rem; padding: .3em .65em; border-radius: 20px; margin-right: .35rem;
}
.rp-drop {
    border: 2px dashed #d1d5db; border-radius: 12px; background: #fff;
    transition: border-color .2s, background .2s; cursor: pointer;
    min-height: 220px; padding: 32px 22px; text-align: center;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
}
.rp-drop:hover, .rp-drop.rp-drag-over {
    border-color: #0d9488;
    background: var(--rp-hover-bg);
}
.rp-drop-icon { font-size: 3rem; margin-bottom: .4rem; }
.rp-file-card {
    border-radius: 10px; background: #fff; border: 1px solid #e5e7eb;
    padding: 14px 16px; display: flex; justify-content: space-between; align-items: center;
}
.rp-convert-btn {
    background: var(--rp-gradient);
    border: none; color: #fff;
    font-size: 1.05rem; font-weight: 700; padding: 13px 40px;
    border-radius: 50px; box-shadow: 0 4px 16px var(--rp-btn-shadow);
    transition: opacity .2s, transform .1s;
}
.rp-convert-btn:hover:not(:disabled) { opacity: .92; transform: translateY(-1px); }
.rp-convert-btn:disabled { opacity: .42; cursor: not-allowed; }
</style>

<section class="rp-hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1>
                    <i class="bi bi-wrench me-2"></i>
                    Repair PDF
                </h1>
                <p class="mb-3" style="opacity:.9;font-size:1rem">
                    Re-processes the PDF structure to fix minor corruption, truncation, and encoding issues.
                </p>
                <div class="d-flex flex-wrap gap-1">
                    <span class="badge">🔒 100% Local</span>
                    <span class="badge">⚡ Browser-based</span>
                </div>
            </div>
            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                <i class="bi bi-wrench" style="font-size:4.5rem;opacity:.22"></i>
            </div>
        </div>
    </div>
</section>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            {{-- Drop zone --}}
            <div id="rp-zone" class="rp-drop">
                <div class="rp-drop-icon"><i class="bi bi-wrench" style="color:#0d9488"></i></div>
                <h5 class="fw-bold mb-1 text-dark">Drop your PDF here</h5>
                <p class="text-muted mb-0 small">Click to browse or drag and drop your PDF file here</p>
                <input type="file" id="rp-input" class="d-none" accept="application/pdf,.pdf">
            </div>

            {{-- File card --}}
            <div id="rp-card" class="rp-file-card mt-3 d-none">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-file-earmark-pdf fs-3" style="color:#0d9488"></i>
                    <div>
                        <div class="fw-semibold text-truncate" style="max-width:280px" id="rp-name">—</div>
                        <small class="text-muted" id="rp-size">—</small>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger rounded-circle px-2" id="rp-remove" title="Remove">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            {{-- Progress --}}
            <div id="rp-progress" class="mt-4 d-none">
                <div class="d-flex justify-content-between mb-1 small">
                    <span id="rp-progress-msg" class="text-muted">Processing…</span>
                </div>
                <div class="progress" style="height: 10px; border-radius: 6px;">
                    <div id="rp-progress-bar"
                         class="progress-bar progress-bar-striped progress-bar-animated"
                         style="width:0%; background: var(--rp-gradient);"></div>
                </div>
            </div>

            {{-- Action --}}
            <div class="text-center mt-4">
                <button type="button" id="rp-btn" class="rp-convert-btn" disabled>
                    <i class="bi bi-wrench me-2"></i>Repair &amp; Download
                </button>
            </div>

            <div id="rp-error" class="alert alert-danger mt-3 d-none"></div>

            <p class="text-center mt-3 small text-muted">
                <i class="bi bi-shield-lock-fill text-success me-1"></i>
                Your file never leaves your device — all processing happens locally in your browser.
            </p>

        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf-lib/1.17.1/pdf-lib.min.js"></script>
<script src="{{ asset('js/tools/repair-pdf.js') }}"></script>
@endpush

@endsection
