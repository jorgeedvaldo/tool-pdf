@extends('layouts.app')

@section('title', __('messages.privacy') . ' - ToolPDF')

@section('content')

<style>
:root { --pv-primary: #16a34a; --pv-light: #f0fdf4; }
.pv-hero {
    background: linear-gradient(135deg, #14532d 0%, #166534 50%, #15803d 100%);
    color: #fff; padding: 60px 0 44px; position: relative; overflow: hidden;
}
.pv-hero::before {
    content: ''; position: absolute; inset: 0;
    background: radial-gradient(ellipse at 70% 50%, rgba(22,163,74,.25) 0%, transparent 65%);
}
.pv-hero h1 { font-size: 2.2rem; font-weight: 800; }
.pv-hero .lead { opacity: .88; font-size: 1.05rem; max-width: 600px; }
.pv-badge {
    background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.3);
    color: #bbf7d0; font-size: .71rem; font-weight: 700; letter-spacing: .05em;
    padding: .28em .75em; border-radius: 20px; text-transform: uppercase;
}
.pv-summary-card {
    border-radius: 14px; padding: 20px; border: 1px solid;
}
.pv-section { padding: 52px 0; }
.pv-section-alt { background: #f8fffe; }
.pv-section h2 { font-size: 1.35rem; font-weight: 800; color: #14532d; margin-bottom: .5rem; }
.pv-section h3 { font-size: 1.05rem; font-weight: 700; color: #166534; margin-bottom: .4rem; }
.pv-section p, .pv-section li { color: #374151; line-height: 1.8; }
.pv-icon-box {
    width: 44px; height: 44px; border-radius: 12px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center; font-size: 1.3rem;
}
.pv-tag {
    display: inline-block; background: #dcfce7; color: #166534;
    border-radius: 6px; font-size: .78rem; font-weight: 700; padding: 2px 10px; margin: 2px;
}
.pv-tag.red { background: #fee2e2; color: #991b1b; }
.pv-toc a { color: #16a34a; text-decoration: none; font-size: .9rem; }
.pv-toc a:hover { text-decoration: underline; }
</style>

{{-- Hero --}}
<section class="pv-hero">
    <div class="container position-relative">
        <div class="row">
            <div class="col-lg-8">
                <span class="pv-badge mb-3 d-inline-block">{{ __('messages.privacy') }}</span>
                <h1><i class="bi bi-shield-check me-3"></i>{{ __('messages.priv_hero_title') }}</h1>
                <p class="lead mt-3 mb-4">{{ __('messages.priv_hero_subtitle') }}</p>
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

            {{-- Summary boxes --}}
            <div class="row g-3 mb-5">
                <div class="col-md-4">
                    <div class="pv-summary-card border-success bg-success bg-opacity-10 h-100">
                        <div class="d-flex gap-2 align-items-center mb-2">
                            <i class="bi bi-check-circle-fill text-success fs-5"></i>
                            <strong class="text-success small">{{ __('messages.priv_collect_title') }}</strong>
                        </div>
                        <p class="small text-muted mb-0">{{ __('messages.priv_collect_text') }}</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="pv-summary-card border-danger bg-danger bg-opacity-10 h-100">
                        <div class="d-flex gap-2 align-items-center mb-2">
                            <i class="bi bi-x-circle-fill text-danger fs-5"></i>
                            <strong class="text-danger small">{{ __('messages.priv_not_collect_title') }}</strong>
                        </div>
                        <p class="small text-muted mb-0">{{ __('messages.priv_not_collect_text') }}</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="pv-summary-card border-primary bg-primary bg-opacity-10 h-100">
                        <div class="d-flex gap-2 align-items-center mb-2">
                            <i class="bi bi-clock-fill text-primary fs-5"></i>
                            <strong class="text-primary small">{{ __('messages.priv_retention_title') }}</strong>
                        </div>
                        <p class="small text-muted mb-0">{{ __('messages.priv_retention_text') }}</p>
                    </div>
                </div>
            </div>

            {{-- TOC --}}
            <div class="card border-0 bg-light rounded-3 p-3 mb-5">
                <p class="fw-bold small text-uppercase text-muted mb-2"><i class="bi bi-list-ul me-1"></i>{{ __('messages.priv_toc') }}</p>
                <div class="row pv-toc">
                    <div class="col-md-6"><a href="#s1">1. {{ __('messages.priv_s1_title') }}</a></div>
                    <div class="col-md-6"><a href="#s2">2. {{ __('messages.priv_s2_title') }}</a></div>
                    <div class="col-md-6"><a href="#s3">3. {{ __('messages.priv_s3_title') }}</a></div>
                    <div class="col-md-6"><a href="#s4">4. {{ __('messages.priv_s4_title') }}</a></div>
                    <div class="col-md-6"><a href="#s5">5. {{ __('messages.priv_s5_title') }}</a></div>
                    <div class="col-md-6"><a href="#contact">6. {{ __('messages.priv_contact_title') }}</a></div>
                </div>
            </div>

            {{-- Section 1: Data Processing --}}
            <section id="s1" class="mb-5">
                <h2><span class="text-success me-2">1.</span>{{ __('messages.priv_s1_title') }}</h2>
                <p>{{ __('messages.priv_s1_intro') }}</p>

                <div class="card border-0 shadow-sm p-4 mb-3">
                    <div class="d-flex gap-3">
                        <div class="pv-icon-box bg-success bg-opacity-10 text-success"><i class="bi bi-browser-chrome"></i></div>
                        <div>
                            <h3>{{ __('messages.priv_s1_browser_title') }}</h3>
                            <p class="mb-0">{{ __('messages.priv_s1_browser_text') }}</p>
                            <div class="mt-2">
                                <span class="pv-tag">Merge PDF</span>
                                <span class="pv-tag">Split PDF</span>
                                <span class="pv-tag">Compress PDF</span>
                                <span class="pv-tag">Rotate Pages</span>
                                <span class="pv-tag">PDF to Images</span>
                                <span class="pv-tag">Edit PDF</span>
                                <span class="pv-tag">+ 20 more</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm p-4">
                    <div class="d-flex gap-3">
                        <div class="pv-icon-box bg-primary bg-opacity-10 text-primary"><i class="bi bi-server"></i></div>
                        <div>
                            <h3>{{ __('messages.priv_s1_server_title') }}</h3>
                            <p class="mb-2">{{ __('messages.priv_s1_server_text') }}</p>
                            <p class="mb-0 small text-muted"><i class="bi bi-trash3 me-1 text-danger"></i>{{ __('messages.priv_s1_server_delete') }}</p>
                            <div class="mt-2">
                                <span class="pv-tag">PDF to Word</span>
                                <span class="pv-tag">Word to PDF</span>
                                <span class="pv-tag">Excel to PDF</span>
                                <span class="pv-tag">PPT to PDF</span>
                                <span class="pv-tag">HTML to PDF</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <hr class="my-4">

            {{-- Section 2: Analytics & Cookies --}}
            <section id="s2" class="mb-5">
                <h2><span class="text-success me-2">2.</span>{{ __('messages.priv_s2_title') }}</h2>

                <div class="card border-0 shadow-sm p-4 mb-3">
                    <div class="d-flex gap-3">
                        <div class="pv-icon-box bg-warning bg-opacity-15 text-warning"><i class="bi bi-bar-chart-line"></i></div>
                        <div>
                            <h3>{{ __('messages.priv_s2_analytics_title') }}</h3>
                            <p class="mb-0">{{ __('messages.priv_s2_analytics_text') }}</p>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm p-4">
                    <div class="d-flex gap-3">
                        <div class="pv-icon-box bg-info bg-opacity-10 text-info"><i class="bi bi-cookie"></i></div>
                        <div>
                            <h3>{{ __('messages.priv_s2_cookies_title') }}</h3>
                            <p class="mb-0">{{ __('messages.priv_s2_cookies_text') }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <hr class="my-4">

            {{-- Section 3: Third-party --}}
            <section id="s3" class="mb-5">
                <h2><span class="text-success me-2">3.</span>{{ __('messages.priv_s3_title') }}</h2>
                <p>{{ __('messages.priv_s3_intro') }}</p>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="table-success">
                            <tr>
                                <th>{{ __('messages.priv_table_service') }}</th>
                                <th>{{ __('messages.priv_table_purpose') }}</th>
                                <th>{{ __('messages.priv_table_data') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Google Analytics</strong></td>
                                <td>{{ __('messages.priv_ga_purpose') }}</td>
                                <td><span class="pv-tag">{{ __('messages.priv_anonymous') }}</span></td>
                            </tr>
                            <tr>
                                <td><strong>jsDelivr CDN</strong></td>
                                <td>{{ __('messages.priv_cdn_purpose') }}</td>
                                <td><span class="pv-tag">{{ __('messages.priv_ip_only') }}</span></td>
                            </tr>
                            <tr>
                                <td><strong>Bootstrap CDN</strong></td>
                                <td>{{ __('messages.priv_bootstrap_purpose') }}</td>
                                <td><span class="pv-tag">{{ __('messages.priv_ip_only') }}</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="small text-muted"><i class="bi bi-info-circle me-1"></i>{{ __('messages.priv_s3_note') }}</p>
            </section>

            <hr class="my-4">

            {{-- Section 4: Your Rights --}}
            <section id="s4" class="mb-5">
                <h2><span class="text-success me-2">4.</span>{{ __('messages.priv_s4_title') }}</h2>
                <p>{{ __('messages.priv_s4_intro') }}</p>
                <div class="row g-2">
                    @foreach([
                        ['bi-search','priv_right_access'],
                        ['bi-trash3','priv_right_delete'],
                        ['bi-download','priv_right_portability'],
                        ['bi-hand-thumbs-down','priv_right_object'],
                    ] as [$icon, $key])
                    <div class="col-md-6">
                        <div class="d-flex gap-3 p-3 border rounded-3">
                            <i class="bi {{ $icon }} text-success fs-5 mt-1"></i>
                            <div>
                                <strong class="small">{{ __('messages.'.$key.'_title') }}</strong>
                                <p class="small text-muted mb-0">{{ __('messages.'.$key.'_text') }}</p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                <p class="mt-3 text-muted small">{{ __('messages.priv_s4_note') }}</p>
            </section>

            <hr class="my-4">

            {{-- Section 5: Changes --}}
            <section id="s5" class="mb-5">
                <h2><span class="text-success me-2">5.</span>{{ __('messages.priv_s5_title') }}</h2>
                <p>{{ __('messages.priv_s5_text') }}</p>
            </section>

            <hr class="my-4">

            {{-- Contact --}}
            <section id="contact" class="mb-2">
                <h2><span class="text-success me-2">6.</span>{{ __('messages.priv_contact_title') }}</h2>
                <p>{{ __('messages.priv_contact_text') }}</p>
                <a href="https://github.com/jorgeedvaldo/tool-pdf/issues" target="_blank" rel="noopener" class="btn btn-outline-success rounded-pill">
                    <i class="bi bi-github me-2"></i>{{ __('messages.priv_contact_btn') }}
                </a>
            </section>

        </div>
    </div>
</div>

@endsection
