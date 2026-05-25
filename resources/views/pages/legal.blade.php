@extends('layouts.app')

@section('title', __('messages.legal') . ' - ToolPDF')

@section('content')

<style>
:root { --lg-primary: #475569; --lg-light: #f8fafc; }
.lg-hero {
    background: linear-gradient(135deg, #1e293b 0%, #334155 50%, #475569 100%);
    color: #fff; padding: 60px 0 44px; position: relative; overflow: hidden;
}
.lg-hero::before {
    content: ''; position: absolute; inset: 0;
    background: radial-gradient(ellipse at 70% 50%, rgba(148,163,184,.18) 0%, transparent 65%);
}
.lg-hero h1 { font-size: 2.2rem; font-weight: 800; }
.lg-hero .lead { opacity: .88; font-size: 1.05rem; max-width: 600px; }
.lg-badge {
    background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.3);
    color: #cbd5e1; font-size: .71rem; font-weight: 700; letter-spacing: .05em;
    padding: .28em .75em; border-radius: 20px; text-transform: uppercase;
}
.lg-toc a { color: #475569; text-decoration: none; font-size: .9rem; }
.lg-toc a:hover { text-decoration: underline; }
.lg-section h2 { font-size: 1.35rem; font-weight: 800; color: #1e293b; margin-bottom: .5rem; }
.lg-section p, .lg-section li { color: #374151; line-height: 1.8; }
.lg-info-row { display: flex; gap: 12px; padding: 10px 0; border-bottom: 1px solid #e2e8f0; }
.lg-info-row:last-child { border-bottom: none; }
.lg-info-label { font-weight: 700; color: #1e293b; min-width: 160px; flex-shrink: 0; font-size: .9rem; }
.lg-info-value { color: #475569; font-size: .9rem; }
.lg-lib-badge {
    display: inline-flex; align-items: center; gap: 6px;
    background: #f1f5f9; border: 1px solid #e2e8f0;
    border-radius: 8px; padding: 6px 14px; font-size: .82rem; font-weight: 600;
    color: #334155; margin: 4px;
}
</style>

{{-- Hero --}}
<section class="lg-hero">
    <div class="container position-relative">
        <div class="row">
            <div class="col-lg-8">
                <span class="lg-badge mb-3 d-inline-block">{{ __('messages.legal') }}</span>
                <h1><i class="bi bi-bank me-3"></i>{{ __('messages.legal_hero_title') }}</h1>
                <p class="lead mt-3 mb-4">{{ __('messages.legal_hero_subtitle') }}</p>
                <p class="small mb-0" style="opacity:.65">
                    <i class="bi bi-calendar3 me-1"></i>{{ __('messages.priv_last_updated') }}: <strong>May 2025</strong>
                </p>
            </div>
        </div>
    </div>
</section>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">

            {{-- TOC --}}
            <div class="card border-0 bg-light rounded-3 p-3 mb-5">
                <p class="fw-bold small text-uppercase text-muted mb-2"><i class="bi bi-list-ul me-1"></i>{{ __('messages.priv_toc') }}</p>
                <div class="row lg-toc">
                    <div class="col-md-6"><a href="#l1">1. {{ __('messages.legal_s1_title') }}</a></div>
                    <div class="col-md-6"><a href="#l2">2. {{ __('messages.legal_s2_title') }}</a></div>
                    <div class="col-md-6"><a href="#l3">3. {{ __('messages.legal_s3_title') }}</a></div>
                    <div class="col-md-6"><a href="#l4">4. {{ __('messages.legal_s4_title') }}</a></div>
                    <div class="col-md-6"><a href="#l5">5. {{ __('messages.legal_s5_title') }}</a></div>
                </div>
            </div>

            {{-- S1: Site Identity --}}
            <section id="l1" class="lg-section mb-5">
                <h2><span class="text-secondary me-2">1.</span>{{ __('messages.legal_s1_title') }}</h2>
                <p>{{ __('messages.legal_s1_intro') }}</p>
                <div class="card border-0 shadow-sm p-4">
                    <div class="lg-info-row"><span class="lg-info-label">{{ __('messages.legal_field_name') }}</span><span class="lg-info-value">ToolPDF</span></div>
                    <div class="lg-info-row"><span class="lg-info-label">{{ __('messages.legal_field_website') }}</span><span class="lg-info-value"><a href="https://toolpdf.org" target="_blank" rel="noopener" class="text-secondary">https://toolpdf.org</a></span></div>
                    <div class="lg-info-row"><span class="lg-info-label">{{ __('messages.legal_field_nature') }}</span><span class="lg-info-value">{{ __('messages.legal_field_nature_val') }}</span></div>
                    <div class="lg-info-row"><span class="lg-info-label">{{ __('messages.legal_field_tech') }}</span><span class="lg-info-value">Laravel 9 · PHP 8 · Bootstrap 5.3</span></div>
                    <div class="lg-info-row"><span class="lg-info-label">{{ __('messages.legal_field_repo') }}</span><span class="lg-info-value"><a href="https://github.com/jorgeedvaldo/tool-pdf" target="_blank" rel="noopener" class="text-secondary">github.com/jorgeedvaldo/tool-pdf</a></span></div>
                    <div class="lg-info-row"><span class="lg-info-label">{{ __('messages.legal_field_license') }}</span><span class="lg-info-value">MIT License</span></div>
                </div>
            </section>
            <hr class="my-4">

            {{-- S2: Copyright --}}
            <section id="l2" class="lg-section mb-5">
                <h2><span class="text-secondary me-2">2.</span>{{ __('messages.legal_s2_title') }}</h2>
                <p>{{ __('messages.legal_s2_text') }}</p>
                <div class="card bg-light border-0 p-3 rounded-3">
                    <p class="small text-muted mb-0">
                        <i class="bi bi-c-circle me-1"></i>{{ __('messages.legal_s2_notice') }}
                    </p>
                </div>
            </section>
            <hr class="my-4">

            {{-- S3: Open Source Libraries --}}
            <section id="l3" class="lg-section mb-5">
                <h2><span class="text-secondary me-2">3.</span>{{ __('messages.legal_s3_title') }}</h2>
                <p>{{ __('messages.legal_s3_intro') }}</p>

                <h6 class="fw-bold text-muted text-uppercase small mt-3 mb-2">Backend</h6>
                <div>
                    <span class="lg-lib-badge"><i class="bi bi-filetype-php text-danger"></i> Laravel — <a href="https://github.com/laravel/framework/blob/master/LICENSE.md" class="text-muted ms-1" target="_blank">MIT</a></span>
                    <span class="lg-lib-badge"><i class="bi bi-file-earmark-word text-primary"></i> PHPWord — <a href="https://github.com/PHPOffice/PHPWord/blob/master/LICENSE" class="text-muted ms-1" target="_blank">LGPL-3.0</a></span>
                    <span class="lg-lib-badge"><i class="bi bi-file-earmark-pdf text-danger"></i> mPDF — <a href="https://github.com/mpdf/mpdf/blob/development/LICENSE.txt" class="text-muted ms-1" target="_blank">GPL-2.0</a></span>
                    <span class="lg-lib-badge"><i class="bi bi-file-earmark-spreadsheet text-success"></i> PhpSpreadsheet — <a href="https://github.com/PHPOffice/PhpSpreadsheet/blob/master/LICENSE" class="text-muted ms-1" target="_blank">MIT</a></span>
                    <span class="lg-lib-badge"><i class="bi bi-search text-secondary"></i> smalot/pdfparser — <a href="https://github.com/smalot/pdfparser/blob/master/LICENSE" class="text-muted ms-1" target="_blank">LGPL-3.0</a></span>
                    <span class="lg-lib-badge"><i class="bi bi-file-earmark-slides text-warning"></i> PHPPresentation — <a href="https://github.com/PHPOffice/PHPPresentation/blob/master/LICENSE" class="text-muted ms-1" target="_blank">LGPL-3.0</a></span>
                </div>

                <h6 class="fw-bold text-muted text-uppercase small mt-4 mb-2">Frontend</h6>
                <div>
                    <span class="lg-lib-badge"><i class="bi bi-filetype-js text-warning"></i> PDF.js (Mozilla) — <a href="https://github.com/mozilla/pdf.js/blob/master/LICENSE" class="text-muted ms-1" target="_blank">Apache-2.0</a></span>
                    <span class="lg-lib-badge"><i class="bi bi-filetype-js text-warning"></i> pdf-lib — <a href="https://github.com/Hopding/pdf-lib/blob/master/LICENSE.md" class="text-muted ms-1" target="_blank">MIT</a></span>
                    <span class="lg-lib-badge"><i class="bi bi-bootstrap text-purple"></i> Bootstrap 5 — <a href="https://github.com/twbs/bootstrap/blob/main/LICENSE" class="text-muted ms-1" target="_blank">MIT</a></span>
                    <span class="lg-lib-badge"><i class="bi bi-filetype-js text-warning"></i> jsPDF — <a href="https://github.com/parallax/jsPDF/blob/master/LICENSE" class="text-muted ms-1" target="_blank">MIT</a></span>
                    <span class="lg-lib-badge"><i class="bi bi-filetype-js text-warning"></i> Tesseract.js — <a href="https://github.com/naptha/tesseract.js/blob/master/LICENSE.md" class="text-muted ms-1" target="_blank">Apache-2.0</a></span>
                    <span class="lg-lib-badge"><i class="bi bi-filetype-js text-warning"></i> JSZip — <a href="https://github.com/Stuk/jszip/blob/main/LICENSE.markdown" class="text-muted ms-1" target="_blank">MIT</a></span>
                    <span class="lg-lib-badge"><i class="bi bi-filetype-js text-warning"></i> marked.js — <a href="https://github.com/markedjs/marked/blob/master/LICENSE.md" class="text-muted ms-1" target="_blank">MIT</a></span>
                </div>
                <p class="small text-muted mt-3">{{ __('messages.legal_s3_note') }}</p>
            </section>
            <hr class="my-4">

            {{-- S4: Trademarks --}}
            <section id="l4" class="lg-section mb-5">
                <h2><span class="text-secondary me-2">4.</span>{{ __('messages.legal_s4_title') }}</h2>
                <p>{{ __('messages.legal_s4_text') }}</p>
            </section>
            <hr class="my-4">

            {{-- S5: Contact --}}
            <section id="l5" class="lg-section mb-4">
                <h2><span class="text-secondary me-2">5.</span>{{ __('messages.legal_s5_title') }}</h2>
                <p>{{ __('messages.legal_s5_text') }}</p>
                <a href="https://github.com/jorgeedvaldo/tool-pdf/issues" target="_blank" rel="noopener" class="btn btn-outline-secondary rounded-pill">
                    <i class="bi bi-github me-2"></i>GitHub Issues
                </a>
            </section>

            {{-- Related links --}}
            <div class="row g-3 mt-4">
                <div class="col-md-4">
                    <a href="{{ route('pages.privacy', ['locale' => app()->getLocale()]) }}" class="card border-0 shadow-sm p-3 text-decoration-none h-100 d-block">
                        <i class="bi bi-shield-check text-success fs-4 mb-2 d-block"></i>
                        <strong class="text-dark small">{{ __('messages.privacy') }}</strong>
                        <p class="text-muted small mb-0">{{ __('messages.legal_link_privacy') }}</p>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="{{ route('pages.terms', ['locale' => app()->getLocale()]) }}" class="card border-0 shadow-sm p-3 text-decoration-none h-100 d-block">
                        <i class="bi bi-file-earmark-text text-warning fs-4 mb-2 d-block"></i>
                        <strong class="text-dark small">{{ __('messages.terms') }}</strong>
                        <p class="text-muted small mb-0">{{ __('messages.legal_link_terms') }}</p>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="{{ route('pages.about', ['locale' => app()->getLocale()]) }}" class="card border-0 shadow-sm p-3 text-decoration-none h-100 d-block">
                        <i class="bi bi-info-circle text-primary fs-4 mb-2 d-block"></i>
                        <strong class="text-dark small">{{ __('messages.about') }}</strong>
                        <p class="text-muted small mb-0">{{ __('messages.legal_link_about') }}</p>
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

@endsection
