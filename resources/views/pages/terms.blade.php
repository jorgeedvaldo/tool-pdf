@extends('layouts.app')

@section('title', __('messages.terms') . ' - ToolPDF')

@section('content')

<style>
:root { --tm-primary: #b45309; --tm-light: #fffbeb; }
.tm-hero {
    background: linear-gradient(135deg, #78350f 0%, #92400e 50%, #b45309 100%);
    color: #fff; padding: 60px 0 44px; position: relative; overflow: hidden;
}
.tm-hero::before {
    content: ''; position: absolute; inset: 0;
    background: radial-gradient(ellipse at 70% 50%, rgba(245,158,11,.22) 0%, transparent 65%);
}
.tm-hero h1 { font-size: 2.2rem; font-weight: 800; }
.tm-hero .lead { opacity: .88; font-size: 1.05rem; max-width: 600px; }
.tm-badge {
    background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.3);
    color: #fde68a; font-size: .71rem; font-weight: 700; letter-spacing: .05em;
    padding: .28em .75em; border-radius: 20px; text-transform: uppercase;
}
.tm-toc a { color: #b45309; text-decoration: none; font-size: .9rem; }
.tm-toc a:hover { text-decoration: underline; }
.tm-section h2 { font-size: 1.35rem; font-weight: 800; color: #78350f; margin-bottom: .5rem; }
.tm-section p, .tm-section li { color: #374151; line-height: 1.8; }
.tm-allowed { color: #166534; }
.tm-forbidden { color: #991b1b; }
</style>

{{-- Hero --}}
<section class="tm-hero">
    <div class="container position-relative">
        <div class="row">
            <div class="col-lg-8">
                <span class="tm-badge mb-3 d-inline-block">{{ __('messages.terms') }}</span>
                <h1><i class="bi bi-file-earmark-text me-3"></i>{{ __('messages.terms_hero_title') }}</h1>
                <p class="lead mt-3 mb-4">{{ __('messages.terms_hero_subtitle') }}</p>
                <p class="small mb-0" style="opacity:.65">
                    <i class="bi bi-calendar3 me-1"></i>{{ __('messages.terms_last_updated') }}: <strong>May 2025</strong>
                </p>
            </div>
        </div>
    </div>
</section>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">

            {{-- Notice box --}}
            <div class="alert alert-warning border-0 rounded-3 d-flex gap-3 mb-5">
                <i class="bi bi-info-circle-fill fs-4 text-warning mt-1"></i>
                <p class="mb-0">{{ __('messages.terms_notice') }}</p>
            </div>

            {{-- TOC --}}
            <div class="card border-0 bg-light rounded-3 p-3 mb-5">
                <p class="fw-bold small text-uppercase text-muted mb-2"><i class="bi bi-list-ul me-1"></i>{{ __('messages.priv_toc') }}</p>
                <div class="row tm-toc">
                    <div class="col-md-6"><a href="#t1">1. {{ __('messages.terms_s1_title') }}</a></div>
                    <div class="col-md-6"><a href="#t2">2. {{ __('messages.terms_s2_title') }}</a></div>
                    <div class="col-md-6"><a href="#t3">3. {{ __('messages.terms_s3_title') }}</a></div>
                    <div class="col-md-6"><a href="#t4">4. {{ __('messages.terms_s4_title') }}</a></div>
                    <div class="col-md-6"><a href="#t5">5. {{ __('messages.terms_s5_title') }}</a></div>
                    <div class="col-md-6"><a href="#t6">6. {{ __('messages.terms_s6_title') }}</a></div>
                    <div class="col-md-6"><a href="#t7">7. {{ __('messages.terms_s7_title') }}</a></div>
                    <div class="col-md-6"><a href="#t8">8. {{ __('messages.terms_s8_title') }}</a></div>
                </div>
            </div>

            {{-- S1: Acceptance --}}
            <section id="t1" class="tm-section mb-5">
                <h2><span class="text-warning me-2">1.</span>{{ __('messages.terms_s1_title') }}</h2>
                <p>{{ __('messages.terms_s1_text') }}</p>
            </section>
            <hr class="my-4">

            {{-- S2: Description --}}
            <section id="t2" class="tm-section mb-5">
                <h2><span class="text-warning me-2">2.</span>{{ __('messages.terms_s2_title') }}</h2>
                <p>{{ __('messages.terms_s2_text') }}</p>
                <div class="row g-3">
                    @foreach([
                        ['bi-browser-chrome','terms_feature_browser'],
                        ['bi-server','terms_feature_server'],
                        ['bi-shield-lock','terms_feature_privacy'],
                        ['bi-globe','terms_feature_free'],
                    ] as [$icon,$key])
                    <div class="col-md-6">
                        <div class="d-flex gap-2 p-3 border rounded-3">
                            <i class="bi {{ $icon }} text-warning fs-5"></i>
                            <p class="small mb-0 text-muted">{{ __('messages.'.$key) }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </section>
            <hr class="my-4">

            {{-- S3: Acceptable Use --}}
            <section id="t3" class="tm-section mb-5">
                <h2><span class="text-warning me-2">3.</span>{{ __('messages.terms_s3_title') }}</h2>
                <p>{{ __('messages.terms_s3_intro') }}</p>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="card border-success border-opacity-50 h-100 p-3">
                            <h6 class="text-success fw-bold"><i class="bi bi-check-circle me-2"></i>{{ __('messages.terms_allowed_title') }}</h6>
                            <ul class="small text-muted mb-0 ps-3">
                                @foreach(['terms_allow_personal','terms_allow_commercial','terms_allow_batch','terms_allow_formats'] as $k)
                                <li class="tm-allowed">{{ __('messages.'.$k) }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-danger border-opacity-50 h-100 p-3">
                            <h6 class="text-danger fw-bold"><i class="bi bi-x-circle me-2"></i>{{ __('messages.terms_prohibited_title') }}</h6>
                            <ul class="small text-muted mb-0 ps-3">
                                @foreach(['terms_forbid_illegal','terms_forbid_abuse','terms_forbid_reverse','terms_forbid_scrape'] as $k)
                                <li class="tm-forbidden">{{ __('messages.'.$k) }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </section>
            <hr class="my-4">

            {{-- S4: IP --}}
            <section id="t4" class="tm-section mb-5">
                <h2><span class="text-warning me-2">4.</span>{{ __('messages.terms_s4_title') }}</h2>
                <p>{{ __('messages.terms_s4_text') }}</p>
            </section>
            <hr class="my-4">

            {{-- S5: Disclaimers --}}
            <section id="t5" class="tm-section mb-5">
                <h2><span class="text-warning me-2">5.</span>{{ __('messages.terms_s5_title') }}</h2>
                <p>{{ __('messages.terms_s5_text') }}</p>
            </section>
            <hr class="my-4">

            {{-- S6: Liability --}}
            <section id="t6" class="tm-section mb-5">
                <h2><span class="text-warning me-2">6.</span>{{ __('messages.terms_s6_title') }}</h2>
                <p>{{ __('messages.terms_s6_text') }}</p>
            </section>
            <hr class="my-4">

            {{-- S7: Changes --}}
            <section id="t7" class="tm-section mb-5">
                <h2><span class="text-warning me-2">7.</span>{{ __('messages.terms_s7_title') }}</h2>
                <p>{{ __('messages.terms_s7_text') }}</p>
            </section>
            <hr class="my-4">

            {{-- S8: Governing Law --}}
            <section id="t8" class="tm-section mb-4">
                <h2><span class="text-warning me-2">8.</span>{{ __('messages.terms_s8_title') }}</h2>
                <p>{{ __('messages.terms_s8_text') }}</p>
            </section>

            <div class="alert alert-light border rounded-3 mt-4 d-flex gap-3">
                <i class="bi bi-envelope fs-4 text-secondary mt-1"></i>
                <div>
                    <strong>{{ __('messages.terms_contact_title') }}</strong>
                    <p class="mb-0 small text-muted">{{ __('messages.terms_contact_text') }}</p>
                    <a href="https://github.com/jorgeedvaldo/tool-pdf/issues" target="_blank" rel="noopener" class="btn btn-outline-warning btn-sm rounded-pill mt-2">
                        <i class="bi bi-github me-1"></i>GitHub Issues
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

@endsection
