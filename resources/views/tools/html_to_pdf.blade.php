@extends('layouts.app')

@section('title', 'Webpage to PDF - ToolPDF')

@section('content')

<style>
:root {
    --hp-gradient: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    --hp-hover-bg: #eff6ff;
    --hp-btn-shadow: rgba(37,99,235,.3);
}
.hp-hero {
    background: var(--hp-gradient);
    color: #fff; padding: 44px 0 28px;
}
.hp-hero h1 { font-size: 1.95rem; font-weight: 800; margin-bottom: .35rem; }
.hp-hero .badge {
    background: rgba(255,255,255,.18); border: 1px solid rgba(255,255,255,.35);
    font-size: .73rem; padding: .3em .65em; border-radius: 20px; margin-right: .35rem;
}
.hp-convert-btn {
    background: var(--hp-gradient);
    border: none; color: #fff;
    font-size: 1.05rem; font-weight: 700; padding: 13px 40px;
    border-radius: 50px; box-shadow: 0 4px 16px var(--hp-btn-shadow);
    transition: opacity .2s, transform .1s;
}
.hp-convert-btn:hover:not(:disabled) { opacity: .92; transform: translateY(-1px); }
.hp-convert-btn:disabled { opacity: .42; cursor: not-allowed; }
</style>

<section class="hp-hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1>
                    <i class="bi bi-globe me-2"></i>
                    Webpage to PDF
                </h1>
                <p class="mb-3" style="opacity:.9;font-size:1rem">
                    Convert any public web page URL into a downloadable PDF file.
                </p>
                <div class="d-flex flex-wrap gap-1">
                    <span class="badge">⚡ Server-side</span>
                    <span class="badge">🗑️ Auto-deleted</span>
                </div>
            </div>
            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                <i class="bi bi-globe" style="font-size:4.5rem;opacity:.22"></i>
            </div>
        </div>
    </div>
</section>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">

                    <label for="hp-url" class="form-label fw-semibold">Web Page URL</label>
                    <input type="url"
                           id="hp-url"
                           class="form-control form-control-lg"
                           placeholder="https://example.com"
                           autocomplete="off"
                           spellcheck="false">
                    <p class="text-muted small mt-2 mb-4">
                        Enter any public web page URL. JavaScript-heavy SPAs may not render completely.
                    </p>

                    {{-- Progress --}}
                    <div id="hp-progress" class="mb-4 d-none">
                        <div class="d-flex justify-content-between mb-1 small">
                            <span id="hp-progress-msg" class="text-muted">Processing…</span>
                        </div>
                        <div class="progress" style="height: 10px; border-radius: 6px;">
                            <div id="hp-progress-bar"
                                 class="progress-bar bg-primary progress-bar-striped progress-bar-animated"
                                 style="width:0%"></div>
                        </div>
                    </div>

                    {{-- Action --}}
                    <div class="text-center">
                        <button type="button" id="hp-convert-btn" class="hp-convert-btn" disabled>
                            <i class="bi bi-filetype-pdf me-2"></i>Convert &amp; Download
                        </button>
                    </div>

                    <div id="hp-error" class="alert alert-danger mt-3 d-none"></div>

                    <p class="text-center mt-3 small text-muted">
                        <i class="bi bi-shield-lock-fill text-success me-1"></i>
                        Files are processed on our server and automatically deleted immediately after conversion.
                    </p>

                </div>
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script>
window.HP_ROUTE = '{{ route("convert.html_to_pdf") }}';
window.HP_CSRF  = '{{ csrf_token() }}';
</script>
<script src="{{ asset('js/tools/html-to-pdf.js') }}"></script>
@endpush

@endsection
