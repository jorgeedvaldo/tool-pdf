@extends('layouts.app')

@section('title', __('messages.about') . ' - ToolPDF')

@section('content')

<style>
:root { --ab-primary: #E5322D; --ab-dark: #1a1a2e; }

.ab-hero {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
    color: #fff; padding: 72px 0 56px; position: relative; overflow: hidden;
}
.ab-hero::before {
    content: ''; position: absolute; inset: 0;
    background: radial-gradient(ellipse at 70% 50%, rgba(229,50,45,.18) 0%, transparent 65%);
}
.ab-hero h1 { font-size: 2.6rem; font-weight: 800; line-height: 1.2; }
.ab-hero .lead { font-size: 1.1rem; opacity: .85; max-width: 560px; }
.ab-badge {
    background: rgba(229,50,45,.2); border: 1px solid rgba(229,50,45,.5);
    color: #ff7b78; font-size: .72rem; font-weight: 700; letter-spacing: .05em;
    padding: .3em .8em; border-radius: 20px; text-transform: uppercase;
}

.ab-stat-card {
    background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.1);
    border-radius: 14px; padding: 20px 24px; text-align: center; backdrop-filter: blur(6px);
}
.ab-stat-card .num { font-size: 2.2rem; font-weight: 800; color: var(--ab-primary); line-height: 1; }
.ab-stat-card .lbl { font-size: .8rem; opacity: .7; margin-top: 4px; }

.ab-section { padding: 64px 0; }
.ab-section-alt { background: #f8f9fa; }

.ab-icon-box {
    width: 52px; height: 52px; border-radius: 14px; display: flex;
    align-items: center; justify-content: center; font-size: 1.5rem;
    flex-shrink: 0;
}

.ab-tool-pill {
    display: inline-flex; align-items: center; gap: 6px;
    background: #fff; border: 1px solid #e5e7eb; border-radius: 30px;
    padding: 6px 14px; font-size: .84rem; font-weight: 500; color: #374151;
    margin: 4px;
}
.ab-tool-pill i { font-size: 1rem; }

.ab-timeline { position: relative; padding-left: 32px; }
.ab-timeline::before {
    content: ''; position: absolute; left: 11px; top: 8px;
    width: 2px; height: calc(100% - 24px); background: #e5e7eb;
}
.ab-step { position: relative; margin-bottom: 32px; }
.ab-step-dot {
    width: 24px; height: 24px; border-radius: 50%;
    background: var(--ab-primary); color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: .7rem; font-weight: 800;
    position: absolute; left: -32px; top: 2px;
}

.ab-tech-badge {
    display: inline-flex; align-items: center; gap: 8px;
    background: #fff; border: 1px solid #e5e7eb; border-radius: 10px;
    padding: 8px 16px; font-size: .85rem; font-weight: 600; color: #374151;
    margin: 5px; box-shadow: 0 1px 3px rgba(0,0,0,.05);
}

.ab-cta {
    background: linear-gradient(135deg, var(--ab-primary) 0%, #b52420 100%);
    color: #fff; padding: 60px 0; text-align: center;
}
.ab-cta h2 { font-size: 2rem; font-weight: 800; margin-bottom: .5rem; }
.ab-cta .btn-white {
    background: #fff; color: var(--ab-primary); font-weight: 700;
    padding: 12px 36px; border-radius: 50px; font-size: 1rem;
    border: none; box-shadow: 0 4px 16px rgba(0,0,0,.15);
    transition: transform .15s, box-shadow .15s;
}
.ab-cta .btn-white:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.2); }
</style>

{{-- ── Hero ── --}}
<section class="ab-hero">
    <div class="container position-relative">
        <div class="row align-items-center gy-4">
            <div class="col-lg-7">
                <span class="ab-badge mb-3 d-inline-block">{{ __('messages.about') }}</span>
                <h1>{{ __('messages.about_hero_title') }}</h1>
                <p class="lead mt-3 mb-4">{{ __('messages.about_hero_subtitle') }}</p>
                <a href="{{ route('home', ['locale' => app()->getLocale()]) }}" class="btn btn-danger px-4 py-2 fw-bold rounded-pill">
                    <i class="bi bi-tools me-2"></i>{{ __('messages.about_cta_explore') }}
                </a>
            </div>
            <div class="col-lg-5">
                <div class="row g-3">
                    <div class="col-6"><div class="ab-stat-card"><div class="num">30+</div><div class="lbl">{{ __('messages.about_stat_tools') }}</div></div></div>
                    <div class="col-6"><div class="ab-stat-card"><div class="num">7</div><div class="lbl">{{ __('messages.about_stat_langs') }}</div></div></div>
                    <div class="col-6"><div class="ab-stat-card"><div class="num">100%</div><div class="lbl">{{ __('messages.about_stat_browser') }}</div></div></div>
                    <div class="col-6"><div class="ab-stat-card"><div class="num">0</div><div class="lbl">{{ __('messages.about_stat_upload') }}</div></div></div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ── Mission ── --}}
<section class="ab-section">
    <div class="container">
        <div class="row align-items-center gy-5">
            <div class="col-lg-6">
                <span class="text-danger fw-bold small text-uppercase letter-spacing-1">{{ __('messages.about_mission_label') }}</span>
                <h2 class="fw-bold mt-2 mb-3" style="font-size:1.9rem">{{ __('messages.about_mission_title') }}</h2>
                <p class="text-muted fs-5" style="line-height:1.8">{{ __('messages.about_mission_p1') }}</p>
                <p class="text-muted" style="line-height:1.8">{{ __('messages.about_mission_p2') }}</p>
                <div class="d-flex flex-wrap gap-2 mt-4">
                    <div class="d-flex align-items-center gap-2 text-success fw-semibold small"><i class="bi bi-check-circle-fill"></i>{{ __('messages.about_feat_free') }}</div>
                    <div class="d-flex align-items-center gap-2 text-success fw-semibold small"><i class="bi bi-check-circle-fill"></i>{{ __('messages.about_feat_noregister') }}</div>
                    <div class="d-flex align-items-center gap-2 text-success fw-semibold small"><i class="bi bi-check-circle-fill"></i>{{ __('messages.about_feat_nowatermark') }}</div>
                    <div class="d-flex align-items-center gap-2 text-success fw-semibold small"><i class="bi bi-check-circle-fill"></i>{{ __('messages.about_feat_nolimit') }}</div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="card border-0 shadow-sm h-100 p-3">
                            <div class="ab-icon-box bg-danger bg-opacity-10 text-danger mb-3"><i class="bi bi-shield-lock-fill"></i></div>
                            <h6 class="fw-bold">{{ __('messages.about_card_privacy_title') }}</h6>
                            <p class="text-muted small mb-0">{{ __('messages.about_card_privacy_text') }}</p>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="card border-0 shadow-sm h-100 p-3">
                            <div class="ab-icon-box bg-primary bg-opacity-10 text-primary mb-3"><i class="bi bi-lightning-charge-fill"></i></div>
                            <h6 class="fw-bold">{{ __('messages.about_card_speed_title') }}</h6>
                            <p class="text-muted small mb-0">{{ __('messages.about_card_speed_text') }}</p>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="card border-0 shadow-sm h-100 p-3">
                            <div class="ab-icon-box bg-success bg-opacity-10 text-success mb-3"><i class="bi bi-globe2"></i></div>
                            <h6 class="fw-bold">{{ __('messages.about_card_multilang_title') }}</h6>
                            <p class="text-muted small mb-0">{{ __('messages.about_card_multilang_text') }}</p>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="card border-0 shadow-sm h-100 p-3">
                            <div class="ab-icon-box bg-warning bg-opacity-10 text-warning mb-3"><i class="bi bi-code-slash"></i></div>
                            <h6 class="fw-bold">{{ __('messages.about_card_open_title') }}</h6>
                            <p class="text-muted small mb-0">{{ __('messages.about_card_open_text') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ── Tool Categories ── --}}
<section class="ab-section ab-section-alt">
    <div class="container">
        <div class="text-center mb-5">
            <span class="text-danger fw-bold small text-uppercase">{{ __('messages.about_tools_label') }}</span>
            <h2 class="fw-bold mt-2" style="font-size:1.9rem">{{ __('messages.about_tools_title') }}</h2>
            <p class="text-muted">{{ __('messages.about_tools_subtitle') }}</p>
        </div>
        <div class="row g-4">
            {{-- Organize & Edit --}}
            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-danger text-white border-0 fw-bold">
                        <i class="bi bi-folder2-open me-2"></i>{{ __('messages.cat_manage') }}
                    </div>
                    <div class="card-body p-3">
                        @foreach([['bi-files','merge_pdf'],['bi-scissors','split_pdf'],['bi-arrows-angle-contract','compress_pdf'],['bi-arrow-clockwise','rotate_pages'],['bi-trash','remove_pages'],['bi-book','extract_pages'],['bi-grip-horizontal','reorganize_pages']] as [$icon,$key])
                        <div class="d-flex align-items-center gap-2 py-1 border-bottom">
                            <i class="bi {{ $icon }} text-danger"></i>
                            <span class="small">{{ __('messages.'.$key) }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            {{-- Convert --}}
            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-primary text-white border-0 fw-bold">
                        <i class="bi bi-arrow-left-right me-2"></i>{{ __('messages.cat_convert') }}
                    </div>
                    <div class="card-body p-3">
                        @foreach([['bi-file-earmark-word','pdf_to_word'],['bi-file-earmark-pdf','word_to_pdf'],['bi-file-earmark-spreadsheet','pdf_to_excel'],['bi-file-earmark-slides','pdf_to_ppt'],['bi-images','pdf_to_images'],['bi-markdown','markdown_to_pdf'],['bi-filetype-txt','txt_to_pdf']] as [$icon,$key])
                        <div class="d-flex align-items-center gap-2 py-1 border-bottom">
                            <i class="bi {{ $icon }} text-primary"></i>
                            <span class="small">{{ __('messages.'.$key) }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            {{-- Security --}}
            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-success text-white border-0 fw-bold">
                        <i class="bi bi-shield-lock me-2"></i>{{ __('messages.cat_security') }}
                    </div>
                    <div class="card-body p-3">
                        @foreach([['bi-lock','protect_pdf'],['bi-unlock','unlock_pdf'],['bi-droplet','add_watermark'],['bi-list-ol','add_page_numbers'],['bi-layers','overlay_pdfs'],['bi-eye','ocr_pdf'],['bi-subtract','flatten_pdf']] as [$icon,$key])
                        <div class="d-flex align-items-center gap-2 py-1 border-bottom">
                            <i class="bi {{ $icon }} text-success"></i>
                            <span class="small">{{ __('messages.'.$key) }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            {{-- Advanced --}}
            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-warning text-dark border-0 fw-bold">
                        <i class="bi bi-stars me-2"></i>{{ __('messages.cat_advanced') }}
                    </div>
                    <div class="card-body p-3">
                        @foreach([['bi-palette','pdf_to_grayscale'],['bi-layout-text-sidebar','add_header_footer'],['bi-arrow-left-right','reverse_pages'],['bi-grid','n_up_pdf'],['bi-card-image','extract_images'],['bi-diagram-3','workflow_editor'],['bi-pencil-square','edit_pdf']] as [$icon,$key])
                        <div class="d-flex align-items-center gap-2 py-1 border-bottom">
                            <i class="bi {{ $icon }} text-warning"></i>
                            <span class="small">{{ __('messages.'.$key) }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ── How It Works ── --}}
<section class="ab-section">
    <div class="container">
        <div class="row gy-5 align-items-start">
            <div class="col-lg-5">
                <span class="text-danger fw-bold small text-uppercase">{{ __('messages.about_how_label') }}</span>
                <h2 class="fw-bold mt-2 mb-4" style="font-size:1.9rem">{{ __('messages.about_how_title') }}</h2>

                <h6 class="fw-bold text-success mb-2"><i class="bi bi-browser-chrome me-2"></i>{{ __('messages.about_how_browser_title') }}</h6>
                <div class="ab-timeline mb-4">
                    <div class="ab-step"><div class="ab-step-dot">1</div><p class="mb-0 text-muted small">{{ __('messages.about_how_b1') }}</p></div>
                    <div class="ab-step"><div class="ab-step-dot">2</div><p class="mb-0 text-muted small">{{ __('messages.about_how_b2') }}</p></div>
                    <div class="ab-step"><div class="ab-step-dot">3</div><p class="mb-0 text-muted small">{{ __('messages.about_how_b3') }}</p></div>
                    <div class="ab-step"><div class="ab-step-dot">4</div><p class="mb-0 text-muted small">{{ __('messages.about_how_b4') }}</p></div>
                </div>

                <h6 class="fw-bold text-primary mb-2"><i class="bi bi-server me-2"></i>{{ __('messages.about_how_server_title') }}</h6>
                <div class="ab-timeline">
                    <div class="ab-step"><div class="ab-step-dot">1</div><p class="mb-0 text-muted small">{{ __('messages.about_how_s1') }}</p></div>
                    <div class="ab-step"><div class="ab-step-dot">2</div><p class="mb-0 text-muted small">{{ __('messages.about_how_s2') }}</p></div>
                    <div class="ab-step"><div class="ab-step-dot">3</div><p class="mb-0 text-muted small">{{ __('messages.about_how_s3') }}</p></div>
                    <div class="ab-step"><div class="ab-step-dot">4</div><p class="mb-0 text-muted small">{{ __('messages.about_how_s4') }}</p></div>
                </div>
            </div>
            <div class="col-lg-7">
                <span class="text-danger fw-bold small text-uppercase">{{ __('messages.about_tech_label') }}</span>
                <h2 class="fw-bold mt-2 mb-4" style="font-size:1.9rem">{{ __('messages.about_tech_title') }}</h2>
                <div class="row g-3">
                    <div class="col-12">
                        <p class="text-muted fw-semibold small mb-2 text-uppercase">Backend</p>
                        <div>
                            <span class="ab-tech-badge"><i class="bi bi-filetype-php text-danger"></i> Laravel 9.x</span>
                            <span class="ab-tech-badge"><i class="bi bi-file-earmark-word text-primary"></i> PHPWord</span>
                            <span class="ab-tech-badge"><i class="bi bi-file-earmark-pdf text-danger"></i> mPDF</span>
                            <span class="ab-tech-badge"><i class="bi bi-file-earmark-spreadsheet text-success"></i> PhpSpreadsheet</span>
                            <span class="ab-tech-badge"><i class="bi bi-file-earmark-slides text-warning"></i> PHPPresentation</span>
                            <span class="ab-tech-badge"><i class="bi bi-search text-secondary"></i> smalot/pdfparser</span>
                        </div>
                    </div>
                    <div class="col-12 mt-2">
                        <p class="text-muted fw-semibold small mb-2 text-uppercase">Frontend</p>
                        <div>
                            <span class="ab-tech-badge"><i class="bi bi-filetype-js text-warning"></i> PDF.js</span>
                            <span class="ab-tech-badge"><i class="bi bi-filetype-js text-warning"></i> pdf-lib</span>
                            <span class="ab-tech-badge"><i class="bi bi-bootstrap text-purple" style="color:#7952b3"></i> Bootstrap 5.3</span>
                            <span class="ab-tech-badge"><i class="bi bi-filetype-js text-warning"></i> jsPDF</span>
                            <span class="ab-tech-badge"><i class="bi bi-filetype-js text-warning"></i> Tesseract.js</span>
                            <span class="ab-tech-badge"><i class="bi bi-filetype-js text-warning"></i> JSZip</span>
                        </div>
                    </div>
                    <div class="col-12 mt-2">
                        <div class="card bg-danger bg-opacity-10 border-0 p-3">
                            <div class="d-flex gap-3">
                                <i class="bi bi-cpu text-danger fs-3 mt-1"></i>
                                <div>
                                    <h6 class="fw-bold text-danger mb-1">{{ __('messages.about_tech_smart_title') }}</h6>
                                    <p class="text-muted small mb-0">{{ __('messages.about_tech_smart_text') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ── Privacy ── --}}
<section class="ab-section ab-section-alt">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9 text-center mb-5">
                <span class="text-danger fw-bold small text-uppercase">{{ __('messages.about_privacy_label') }}</span>
                <h2 class="fw-bold mt-2" style="font-size:1.9rem">{{ __('messages.about_privacy_title') }}</h2>
                <p class="text-muted">{{ __('messages.about_privacy_subtitle') }}</p>
            </div>
        </div>
        <div class="row g-4 justify-content-center">
            <div class="col-md-4">
                <div class="text-center">
                    <div class="ab-icon-box bg-success bg-opacity-10 text-success mx-auto mb-3" style="width:64px;height:64px;font-size:1.8rem;border-radius:50%">
                        <i class="bi bi-laptop"></i>
                    </div>
                    <h6 class="fw-bold">{{ __('messages.about_priv1_title') }}</h6>
                    <p class="text-muted small">{{ __('messages.about_priv1_text') }}</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-center">
                    <div class="ab-icon-box bg-success bg-opacity-10 text-success mx-auto mb-3" style="width:64px;height:64px;font-size:1.8rem;border-radius:50%">
                        <i class="bi bi-trash3"></i>
                    </div>
                    <h6 class="fw-bold">{{ __('messages.about_priv2_title') }}</h6>
                    <p class="text-muted small">{{ __('messages.about_priv2_text') }}</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-center">
                    <div class="ab-icon-box bg-success bg-opacity-10 text-success mx-auto mb-3" style="width:64px;height:64px;font-size:1.8rem;border-radius:50%">
                        <i class="bi bi-incognito"></i>
                    </div>
                    <h6 class="fw-bold">{{ __('messages.about_priv3_title') }}</h6>
                    <p class="text-muted small">{{ __('messages.about_priv3_text') }}</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ── Contact ── --}}
<section class="ab-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <span class="text-danger fw-bold small text-uppercase">{{ __('messages.about_contact_label') }}</span>
                <h2 class="fw-bold mt-2 mb-3" style="font-size:1.9rem">{{ __('messages.about_contact_title') }}</h2>
                <p class="text-muted mb-4">{{ __('messages.about_contact_text') }}</p>
                <div class="d-flex justify-content-center flex-wrap gap-3">
                    <a href="https://github.com/jorgeedvaldo/tool-pdf" target="_blank" rel="noopener" class="btn btn-outline-dark rounded-pill px-4">
                        <i class="bi bi-github me-2"></i>GitHub
                    </a>
                    <a href="{{ route('blog.index', ['locale' => app()->getLocale()]) }}" class="btn btn-outline-danger rounded-pill px-4">
                        <i class="bi bi-newspaper me-2"></i>Blog
                    </a>
                    <a href="{{ route('pages.privacy', ['locale' => app()->getLocale()]) }}" class="btn btn-outline-secondary rounded-pill px-4">
                        <i class="bi bi-shield-check me-2"></i>{{ __('messages.privacy') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ── CTA ── --}}
<section class="ab-cta">
    <div class="container">
        <h2>{{ __('messages.about_cta_title') }}</h2>
        <p style="opacity:.85;font-size:1.05rem" class="mb-4">{{ __('messages.about_cta_subtitle') }}</p>
        <a href="{{ route('home', ['locale' => app()->getLocale()]) }}" class="btn-white btn">
            <i class="bi bi-tools me-2"></i>{{ __('messages.about_cta_btn') }}
        </a>
    </div>
</section>

@endsection
