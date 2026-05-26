@extends('layouts.app')

@section('content')

<div class="hero-section text-center py-5 mb-5 shadow-inner">
    <div class="container">
        <h1 class="display-4 fw-bold text-white text-shadow mb-3">{{ __('messages.title') }}</h1>
        <p class="lead text-white text-shadow-sm mb-4">{{ __('messages.subtitle') }}</p>
    </div>
</div>

{{-- Workflow Editor Feature Banner --}}
<div class="container mb-5">
    <div class="row align-items-center p-4 rounded-4 shadow-sm" style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); color:#fff;">
        <div class="col-lg-7">
            <span class="badge mb-2" style="background:rgba(229,50,45,.85);font-size:.75rem;padding:.35em .75em;border-radius:20px">✨ New Feature</span>
            <h2 class="fw-bold mb-2" style="font-size:1.6rem">Visual Workflow Editor <span style="font-size:0.8rem;background:rgba(255,255,255,.15);padding:.2em .6em;border-radius:12px;vertical-align:middle;margin-left:6px">Beta</span></h2>
            <p class="mb-3" style="opacity:.85;font-size:.95rem">Chain multiple PDF operations together visually — drag-and-drop tools onto a canvas, connect them, and execute the entire pipeline in one click.</p>
            <div class="d-flex flex-wrap gap-2 mb-3">
                <span style="background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.25);font-size:.75rem;padding:.3em .7em;border-radius:16px">🔗 Node-based editor</span>
                <span style="background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.25);font-size:.75rem;padding:.3em .7em;border-radius:16px">📋 Pre-built templates</span>
                <span style="background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.25);font-size:.75rem;padding:.3em .7em;border-radius:16px">💾 Save &amp; load workflows</span>
                <span style="background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.25);font-size:.75rem;padding:.3em .7em;border-radius:16px">⚡ One-click execute</span>
            </div>
            <a href="{{ Route::has('tool.workflow_editor') ? route('tool.workflow_editor') : '#' }}" class="btn fw-bold px-4 py-2 rounded-pill" style="background:#E5322D;color:#fff;border:none;box-shadow:0 4px 14px rgba(229,50,45,.4)">
                <i class="bi bi-diagram-3 me-2"></i>Open Workflow Editor
            </a>
        </div>
        <div class="col-lg-5 text-center mt-4 mt-lg-0 d-none d-lg-block">
            <div style="background:rgba(255,255,255,.07);border-radius:12px;padding:16px;border:1px solid rgba(255,255,255,.1)">
                <div class="d-flex align-items-center gap-2 mb-2" style="font-size:.75rem;color:rgba(255,255,255,.6)">
                    <span style="width:10px;height:10px;background:#ff5f57;border-radius:50%"></span>
                    <span style="width:10px;height:10px;background:#febc2e;border-radius:50%"></span>
                    <span style="width:10px;height:10px;background:#28c840;border-radius:50%"></span>
                    <span class="ms-1">workflow.json</span>
                </div>
                <div class="d-flex align-items-center justify-content-center gap-1" style="font-size:.78rem">
                    @foreach(['📥 Input', '⚙️ Compress', '🔲 Grayscale', '🔒 Protect', '📤 Output'] as $node)
                        <span style="background:rgba(255,255,255,.13);border:1px solid rgba(255,255,255,.2);border-radius:8px;padding:.3em .55em;white-space:nowrap">{{ $node }}</span>
                        @if(!$loop->last)<span style="color:rgba(255,255,255,.4)">→</span>@endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container" id="tools-section">
    <!-- Categories Array definition -->
    @php
    $categories = [
        [
            'name' => __('messages.cat_manipulate'),
            'icon' => 'bi-puzzle-fill',
            'tools' => [
                ['id' => 'merge_pdf', 'icon' => 'bi-file-earmark-plus', 'color' => 'blue'],
                ['id' => 'split_pdf', 'icon' => 'bi-layout-split', 'color' => 'orange'],
                ['id' => 'compress_pdf', 'icon' => 'bi-arrows-angle-contract', 'color' => 'green'],
                ['id' => 'edit_pdf', 'icon' => 'bi-pencil-square', 'color' => 'purple'],
                ['id' => 'sign_pdf', 'icon' => 'bi-pen', 'color' => 'cyan'],
            ]
        ],
        [
            'name' => __('messages.cat_security'),
            'icon' => 'bi-shield-lock',
            'tools' => [
                ['id' => 'protect_pdf', 'icon' => 'bi-lock',   'color' => 'gray'],
                ['id' => 'unlock_pdf',  'icon' => 'bi-unlock', 'color' => 'green'],
            ]
        ],
        [
            'name' => __('messages.cat_manage'),
            'icon' => 'bi-files',
            'tools' => [
                ['id' => 'rotate_pages',     'icon' => 'bi-arrow-clockwise',    'color' => 'orange'],
                ['id' => 'remove_pages',     'icon' => 'bi-file-earmark-minus', 'color' => 'red'],
                ['id' => 'extract_pages',    'icon' => 'bi-file-earmark-break', 'color' => 'blue'],
                ['id' => 'reorganize_pages', 'icon' => 'bi-shuffle',            'color' => 'purple'],
                ['id' => 'reverse_pages',    'icon' => 'bi-arrow-left-right',   'color' => 'red'],
                ['id' => 'n_up_pdf',         'icon' => 'bi-grid',               'color' => 'purple'],
                ['id' => 'flatten_pdf',      'icon' => 'bi-layers',             'color' => 'orange'],
                ['id' => 'repair_pdf',       'icon' => 'bi-wrench',             'color' => 'teal'],
            ]
        ],
        [
            'name' => __('messages.cat_convert'),
            'icon' => 'bi-arrow-left-right',
            'tools' => [
                ['id' => 'images_to_pdf',    'icon' => 'bi-images',                  'color' => 'yellow'],
                ['id' => 'pdf_to_images',    'icon' => 'bi-file-earmark-image',       'color' => 'yellow'],
                ['id' => 'pdf_to_word',      'icon' => 'bi-file-earmark-word',        'color' => 'blue'],
                ['id' => 'word_to_pdf',      'icon' => 'bi-file-earmark-pdf',         'color' => 'red'],
                ['id' => 'pdf_to_excel',     'icon' => 'bi-file-earmark-spreadsheet', 'color' => 'green'],
                ['id' => 'excel_to_pdf',     'icon' => 'bi-file-earmark-spreadsheet', 'color' => 'green'],
                ['id' => 'pdf_to_ppt',       'icon' => 'bi-file-earmark-slides',      'color' => 'orange'],
                ['id' => 'ppt_to_pdf',       'icon' => 'bi-file-earmark-slides',      'color' => 'orange'],
                ['id' => 'html_to_pdf',      'icon' => 'bi-globe',                    'color' => 'blue'],
                ['id' => 'pdf_to_grayscale', 'icon' => 'bi-circle-half',              'color' => 'gray'],
                ['id' => 'txt_to_pdf',       'icon' => 'bi-file-text',                'color' => 'teal'],
                ['id' => 'markdown_to_pdf',  'icon' => 'bi-markdown',                 'color' => 'blue'],
                ['id' => 'extract_images',   'icon' => 'bi-file-earmark-image',       'color' => 'orange'],
            ]
        ],
        [
            'name' => __('messages.cat_advanced'),
            'icon' => 'bi-gear',
            'tools' => [
                ['id' => 'ocr_pdf',           'icon' => 'bi-search',     'color' => 'cyan'],
                ['id' => 'add_watermark',     'icon' => 'bi-droplet',    'color' => 'indigo'],
                ['id' => 'add_page_numbers',  'icon' => 'bi-123',        'color' => 'gray'],
                ['id' => 'add_header_footer', 'icon' => 'bi-layout-text-sidebar', 'color' => 'blue'],
                ['id' => 'overlay_pdfs',      'icon' => 'bi-layers',     'color' => 'teal'],
            ]
        ],
        [
            'name' => __('messages.cat_compare'),
            'icon' => 'bi-file-diff',
            'tools' => [
                ['id' => 'compare_pdf', 'icon' => 'bi-file-diff', 'color' => 'red'],
                ['id' => 'view_pdf',    'icon' => 'bi-eye',        'color' => 'blue'],
            ]
        ],
        [
            'name' => __('messages.cat_workflow'),
            'icon' => 'bi-diagram-3',
            'tools' => [
                ['id' => 'workflow_editor', 'icon' => 'bi-diagram-3', 'color' => 'red'],
            ]
        ]
    ];
    @endphp

    <!-- Category Filter Pills -->
    <div class="category-filters text-center mb-5">
        <div class="d-inline-flex flex-wrap justify-content-center gap-2 p-2 bg-white rounded-pill shadow-sm border">
            <button class="btn btn-dark rounded-pill px-4 py-1 filter-btn active" data-filter="all">{{ __('messages.all') ?? 'Todas' }}</button>
            @foreach($categories as $category)
                <button class="btn btn-light rounded-pill px-4 py-1 filter-btn border-0 text-muted" data-filter="{{ 'cat_' . Str::slug($category['name']) }}">
                    {{ $category['name'] }}
                </button>
            @endforeach
        </div>
    </div>

    <!-- Tools Grid -->
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4 mb-5" id="tools-grid">
        @foreach($categories as $category)
            @php $catId = 'cat_' . Str::slug($category['name']); @endphp
            @foreach($category['tools'] as $tool)
                <div class="col tool-item" data-category="{{ $catId }}">
                    <a href="{{ Route::has('tool.'.$tool['id']) ? route('tool.'.$tool['id']) : '#' }}" class="text-decoration-none">
                        <div class="card h-100 tool-card shadow-retro border-pattern">
                            <div class="card-body text-center d-flex flex-column justify-content-center">
                                <div class="icon-wrapper mb-3 mx-auto gradient-{{ $tool['color'] }}">
                                    <i class="bi {{ $tool['icon'] }} fs-1 text-white text-shadow-sm"></i>
                                </div>
                                <h5 class="card-title fw-bold text-dark mb-2">{{ __('messages.'.$tool['id']) }}</h5>
                                <p class="card-text text-muted small">{{ __('messages.'.$tool['id'].'_desc') }}</p>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        @endforeach
    </div>

    <!-- Informational Section matching 2013 style -->
    <div class="info-section mt-5 pt-4 border-top">
        <div class="text-center mb-5">
            <h2 class="fw-bold mb-3 border-bottom d-inline-block pb-2">{{ __('messages.info_title') }}</h2>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-6 col-lg-4">
                <div class="d-flex p-3 bg-white border rounded shadow-sm h-100">
                    <div class="flex-shrink-0 me-3">
                        <i class="bi bi-collection fs-2 text-primary"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold">{{ __('messages.info_feature_1_title') }}</h5>
                        <p class="text-muted small mb-0">{{ __('messages.info_feature_1_desc') }}</p>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="d-flex p-3 bg-white border rounded shadow-sm h-100">
                    <div class="flex-shrink-0 me-3">
                        <i class="bi bi-emoji-smile fs-2 text-primary"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold">{{ __('messages.info_feature_2_title') }}</h5>
                        <p class="text-muted small mb-0">{{ __('messages.info_feature_2_desc') }}</p>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="d-flex p-3 bg-white border rounded shadow-sm h-100">
                    <div class="flex-shrink-0 me-3">
                        <i class="bi bi-shield-check fs-2 text-primary"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold">{{ __('messages.info_feature_3_title') }}</h5>
                        <p class="text-muted small mb-0">{{ __('messages.info_feature_3_desc') }}</p>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="d-flex p-3 bg-white border rounded shadow-sm h-100">
                    <div class="flex-shrink-0 me-3">
                        <i class="bi bi-browser-chrome fs-2 text-primary"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold">{{ __('messages.info_feature_4_title') }}</h5>
                        <p class="text-muted small mb-0">{{ __('messages.info_feature_4_desc') }}</p>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="d-flex p-3 bg-white border rounded shadow-sm h-100">
                    <div class="flex-shrink-0 me-3">
                        <i class="bi bi-laptop fs-2 text-primary"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold">{{ __('messages.info_feature_5_title') }}</h5>
                        <p class="text-muted small mb-0">{{ __('messages.info_feature_5_desc') }}</p>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="d-flex p-3 bg-white border rounded shadow-sm h-100">
                    <div class="flex-shrink-0 me-3">
                        <i class="bi bi-cpu fs-2 text-primary"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold">{{ __('messages.info_feature_6_title') }}</h5>
                        <p class="text-muted small mb-0">{{ __('messages.info_feature_6_desc') }}</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-4">
                <div class="d-flex p-3 bg-white border rounded shadow-sm h-100">
                    <div class="flex-shrink-0 me-3">
                        <i class="bi bi-tag fs-2 text-primary"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold">{{ __('messages.info_feature_7_title') }}</h5>
                        <p class="text-muted small mb-0">{{ __('messages.info_feature_7_desc') }}</p>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="d-flex p-3 bg-white border rounded shadow-sm h-100">
                    <div class="flex-shrink-0 me-3">
                        <i class="bi bi-infinity fs-2 text-primary"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold">{{ __('messages.info_feature_8_title') }}</h5>
                        <p class="text-muted small mb-0">{{ __('messages.info_feature_8_desc') }}</p>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="d-flex p-3 bg-white border rounded shadow-sm h-100">
                    <div class="flex-shrink-0 me-3">
                        <i class="bi bi-cloud fs-2 text-primary"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold">{{ __('messages.info_feature_9_title') }}</h5>
                        <p class="text-muted small mb-0">{{ __('messages.info_feature_9_desc') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row align-items-center bg-white border rounded p-4 shadow-sm mb-4">
            <div class="col-md-8">
                <h4 class="fw-bold text-primary">{{ __('messages.info_lovers') }}</h4>
                <p class="mb-0 text-muted">{{ __('messages.info_lovers_desc') }}</p>
            </div>
            <div class="col-md-4 text-center mt-3 mt-md-0">
                <i class="bi bi-heart-fill fs-1 text-danger shadow-sm p-3 border rounded-circle" style="background: #fff0f0;"></i>
            </div>
        </div>

        <div class="row align-items-center bg-white border rounded p-4 shadow-sm">
            <div class="col-md-8 order-md-2">
                <h4 class="fw-bold text-success">{{ __('messages.info_trusted') }}</h4>
                <p class="mb-0 text-muted">{{ __('messages.info_trusted_desc') }}</p>
            </div>
            <div class="col-md-4 order-md-1 text-center mt-3 mt-md-0 border-end border-light d-none d-md-block">
                <i class="bi bi-shield-fill-check fs-1 text-success shadow-sm p-3 border rounded-circle" style="background: #f0fff0;"></i>
            </div>
        </div>

    </div>

    <!-- Blog Section -->
    @if(isset($recentPosts) && $recentPosts->count() > 0)
    <div class="blog-section mt-5 pt-4 border-top">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold mb-0 d-inline-block">{{ __('messages.latest_articles') }}</h2>
            <a href="{{ route('blog.index') }}" class="btn btn-outline-dark rounded-pill px-4">{{ __('messages.view_all_articles') }}</a>
        </div>

        <div class="row g-4 mb-5">
            @foreach($recentPosts as $post)
            <div class="col-md-4">
                <div class="card h-100 shadow-sm border rounded">
                    @if($post->thumbnail ?? $post->image)
                        <img src="{{ asset('storage/' . ($post->thumbnail ?? $post->image)) }}" class="card-img-top" alt="{{ $post->title }}" style="height: 200px; object-fit: cover;" loading="lazy">
                    @else
                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                            <i class="bi bi-image text-muted fs-1"></i>
                        </div>
                    @endif
                    <div class="card-body">
                        <h5 class="card-title fw-bold"><a href="{{ route('blog.show', ['slug' => $post->slug]) }}" class="text-dark text-decoration-none">{{ $post->title }}</a></h5>
                        <p class="card-text text-muted small">{{ Str::limit(strip_tags($post->description), 100) }}</p>
                    </div>
                    <div class="card-footer bg-white border-top-0 pb-3">
                        <a href="{{ route('blog.show', ['slug' => $post->slug]) }}" class="btn btn-sm btn-dark w-100 rounded-pill">{{ __('messages.read_more') }}</a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterBtns = document.querySelectorAll('.filter-btn');
    const toolItems = document.querySelectorAll('.tool-item');

    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Remove active classes
            filterBtns.forEach(b => {
                b.classList.remove('active', 'btn-dark', 'text-white');
                b.classList.add('btn-light', 'text-muted');
            });
            
            // Add active classes to clicked
            this.classList.remove('btn-light', 'text-muted');
            this.classList.add('active', 'btn-dark', 'text-white');

            const filterValue = this.getAttribute('data-filter');

            toolItems.forEach(item => {
                if (filterValue === 'all') {
                    item.classList.remove('d-none');
                } else {
                    if (item.getAttribute('data-category') === filterValue) {
                        item.classList.remove('d-none');
                    } else {
                        item.classList.add('d-none');
                    }
                }
            });
        });
    });
});
</script>
@endpush

@endsection
