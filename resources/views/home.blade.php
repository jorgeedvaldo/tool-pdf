@extends('layouts.app')

@section('content')

<div class="hero-section text-center py-5 mb-5 shadow-inner">
    <div class="container">
        <h1 class="display-4 fw-bold text-white text-shadow mb-3">{{ __('messages.title') }}</h1>
        <p class="lead text-white text-shadow-sm mb-4">{{ __('messages.subtitle') }}</p>
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
            'name' => __('messages.cat_convert'),
            'icon' => 'bi-arrow-left-right',
            'tools' => [
                ['id' => 'convert_pdf', 'icon' => 'bi-file-earmark-font', 'color' => 'red'],
                ['id' => 'images_to_pdf', 'icon' => 'bi-images', 'color' => 'yellow'],
                ['id' => 'pdf_to_images', 'icon' => 'bi-file-earmark-image', 'color' => 'yellow'],
                ['id' => 'web_to_pdf', 'icon' => 'bi-globe', 'color' => 'indigo'],
            ]
        ],
        [
            'name' => __('messages.cat_image'),
            'icon' => 'bi-image',
            'tools' => [
                ['id' => 'extract_images', 'icon' => 'bi-camera', 'color' => 'teal'],
            ]
        ],
        [
            'name' => __('messages.cat_security'),
            'icon' => 'bi-shield-lock',
            'tools' => [
                ['id' => 'protect_pdf', 'icon' => 'bi-lock', 'color' => 'gray'],
                ['id' => 'unlock_pdf', 'icon' => 'bi-unlock', 'color' => 'green'],
            ]
        ],
        [
            'name' => __('messages.cat_manage'),
            'icon' => 'bi-files',
            'tools' => [
                ['id' => 'rotate_pages', 'icon' => 'bi-arrow-clockwise', 'color' => 'orange'],
                ['id' => 'remove_pages', 'icon' => 'bi-file-earmark-minus', 'color' => 'red'],
                ['id' => 'extract_pages', 'icon' => 'bi-file-earmark-break', 'color' => 'blue'],
                ['id' => 'reorganize_pages', 'icon' => 'bi-shuffle', 'color' => 'purple'],
            ]
        ],
        [
            'name' => __('messages.cat_advanced'),
            'icon' => 'bi-gear',
            'tools' => [
                ['id' => 'ocr_pdf', 'icon' => 'bi-search', 'color' => 'cyan'],
                ['id' => 'add_watermark', 'icon' => 'bi-droplet', 'color' => 'indigo'],
                ['id' => 'add_page_numbers', 'icon' => 'bi-123', 'color' => 'gray'],
                ['id' => 'overlay_pdfs', 'icon' => 'bi-layers', 'color' => 'teal'],
                ['id' => 'compare_pdfs', 'icon' => 'bi-vr', 'color' => 'blue'],
            ]
        ],
        [
            'name' => __('messages.cat_optimize'),
            'icon' => 'bi-speedometer2',
            'tools' => [
                ['id' => 'optimize_web', 'icon' => 'bi-cloud-arrow-up', 'color' => 'cyan'],
                ['id' => 'redact_pdf', 'icon' => 'bi-eraser-fill', 'color' => 'black'],
            ]
        ],
        [
            'name' => __('messages.cat_create'),
            'icon' => 'bi-plus-circle',
            'tools' => [
                ['id' => 'create_pdf', 'icon' => 'bi-file-earmark-plus-fill', 'color' => 'green'],
            ]
        ]
    ];
    @endphp

    <!-- Category Filter Pills -->
    <div class="category-filters d-flex justify-content-center flex-wrap gap-2 mb-5">
        <button class="btn btn-primary rounded-pill px-4 filter-btn active" data-filter="all">{{ __('messages.all') ?? 'Todas' }}</button>
        @foreach($categories as $category)
            <button class="btn btn-outline-secondary rounded-pill px-4 filter-btn" data-filter="{{ 'cat_' . Str::slug($category['name']) }}">
                {{ $category['name'] }}
            </button>
        @endforeach
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
            <h2 class="fw-bold mb-0 d-inline-block">{{ __('messages.latest_articles') ?? 'Latest Articles' }}</h2>
            <a href="{{ route('blog.index') }}" class="btn btn-outline-primary rounded-pill px-4">{{ __('messages.view_all_articles') ?? 'Ver todos os artigos' }}</a>
        </div>

        <div class="row g-4 mb-5">
            @foreach($recentPosts as $post)
            <div class="col-md-4">
                <div class="card h-100 shadow-sm border rounded">
                    @if($post->image)
                        <img src="{{ asset('storage/' . $post->image) }}" class="card-img-top" alt="{{ $post->title }}" style="height: 200px; object-fit: cover;">
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
                        <a href="{{ route('blog.show', ['slug' => $post->slug]) }}" class="btn btn-sm btn-primary w-100">{{ __('messages.read_more') ?? 'Ler Mais' }}</a>
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
            // Remove active class from all
            filterBtns.forEach(b => b.classList.remove('active', 'btn-primary'));
            filterBtns.forEach(b => b.classList.add('btn-outline-secondary'));
            
            // Add active class to clicked
            this.classList.remove('btn-outline-secondary');
            this.classList.add('active', 'btn-primary');

            const filterValue = this.getAttribute('data-filter');

            toolItems.forEach(item => {
                if (filterValue === 'all') {
                    item.style.display = 'block';
                } else {
                    if (item.getAttribute('data-category') === filterValue) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                }
            });
        });
    });
});
</script>
@endpush

@endsection
