<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', __('messages.title'))</title>
    
    <!-- Bootstrap 5 CSS via CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    
    <!-- Custom CSS -->
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-custom-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="{{ route('home') }}">
            <i class="bi bi-file-earmark-pdf-fill text-danger me-2 fs-3"></i>
            <span class="fw-bold fs-4">ToolPDF</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('tool.merge_pdf') }}"><i class="bi bi-file-earmark-plus me-1"></i>{{ __('messages.merge_pdf') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('tool.split_pdf') }}"><i class="bi bi-layout-split me-1"></i>{{ __('messages.split_pdf') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('home') }}"><i class="bi bi-arrows-angle-contract me-1"></i>{{ __('messages.compress_pdf') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('home') }}"><i class="bi bi-file-earmark-font me-1"></i>{{ __('messages.convert_pdf') }}</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle fw-bold text-white" href="#" id="allToolsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-grid-3x3-gap-fill me-1"></i>{{ __('messages.tools') }}
                    </a>
                    <ul class="dropdown-menu shadow-lg border-0 mt-2" aria-labelledby="allToolsDropdown">
                        <li><h6 class="dropdown-header text-primary fw-bold">{{ __('messages.cat_manipulate') }}</h6></li>
                        <li><a class="dropdown-item" href="{{ route('tool.merge_pdf') }}"><i class="bi bi-file-earmark-plus me-2 text-primary"></i>{{ __('messages.merge_pdf') }}</a></li>
                        <li><a class="dropdown-item" href="{{ route('tool.split_pdf') }}"><i class="bi bi-layout-split me-2 text-warning"></i>{{ __('messages.split_pdf') }}</a></li>
                        <li><a class="dropdown-item" href="{{ route('home') }}"><i class="bi bi-arrows-angle-contract me-2 text-success"></i>{{ __('messages.compress_pdf') }}</a></li>
                        <li><a class="dropdown-item" href="{{ route('home') }}"><i class="bi bi-pencil-square me-2 text-info"></i>{{ __('messages.edit_pdf') }}</a></li>
                        <li><a class="dropdown-item" href="{{ route('tool.sign_pdf') }}"><i class="bi bi-pen me-2 text-secondary"></i>{{ __('messages.sign_pdf') }}</a></li>
                        <li><h6 class="dropdown-header text-primary fw-bold">{{ __('messages.cat_manage') }}</h6></li>
                        <li><a class="dropdown-item" href="{{ route('tool.reorganize_pages') }}"><i class="bi bi-shuffle me-2 text-purple"></i>{{ __('messages.reorganize_pages') }}</a></li>
                        <li><a class="dropdown-item" href="{{ route('tool.rotate_pages') }}"><i class="bi bi-arrow-clockwise me-2 text-warning"></i>{{ __('messages.rotate_pages') }}</a></li>
                        <li><a class="dropdown-item" href="{{ route('tool.remove_pages') }}"><i class="bi bi-file-earmark-minus me-2 text-danger"></i>{{ __('messages.remove_pages') }}</a></li>
                        <li><a class="dropdown-item" href="{{ route('tool.extract_pages') }}"><i class="bi bi-file-earmark-break me-2 text-info"></i>{{ __('messages.extract_pages') }}</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><h6 class="dropdown-header text-primary fw-bold">{{ __('messages.cat_security') }}</h6></li>
                        <li><a class="dropdown-item" href="{{ route('tool.unlock_pdf') }}"><i class="bi bi-unlock me-2 text-success"></i>{{ __('messages.unlock_pdf') }}</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><h6 class="dropdown-header text-primary fw-bold">{{ __('messages.cat_advanced') }}</h6></li>
                        <li><a class="dropdown-item" href="{{ route('tool.add_watermark') }}"><i class="bi bi-droplet me-2 text-primary"></i>{{ __('messages.add_watermark') }}</a></li>
                        <li><a class="dropdown-item" href="{{ route('tool.add_page_numbers') }}"><i class="bi bi-123 me-2 text-secondary"></i>{{ __('messages.add_page_numbers') }}</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><h6 class="dropdown-header text-primary fw-bold">{{ __('messages.cat_convert') }}</h6></li>
                        <li><a class="dropdown-item" href="{{ route('home') }}"><i class="bi bi-file-earmark-font me-2 text-danger"></i>{{ __('messages.convert_pdf') }}</a></li>
                        <li><a class="dropdown-item" href="{{ route('tool.images_to_pdf') }}"><i class="bi bi-images me-2 text-warning"></i>{{ __('messages.images_to_pdf') }}</a></li>
                        <li><a class="dropdown-item" href="{{ route('tool.pdf_to_images') }}"><i class="bi bi-file-earmark-image me-2 text-warning"></i>{{ __('messages.pdf_to_images') }}</a></li>
                        <li><a class="dropdown-item" href="{{ route('home') }}"><i class="bi bi-globe me-2 text-info"></i>{{ __('messages.web_to_pdf') }}</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item fw-bold text-center bg-light py-2" href="{{ route('home') }}#tools-section">{{ __('messages.tools') }}...</a></li>
                    </ul>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle bg-language-selector" href="#" id="languageDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-globe me-1"></i> {{ strtoupper(app()->getLocale()) }}
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="languageDropdown">
                        @php
                            $currentRoute = Route::currentRouteName() ?? 'home';
                            $params = request()->route() ? request()->route()->parameters() : [];
                        @endphp
                        <li><a class="dropdown-item" href="{{ route($currentRoute, array_merge($params, ['locale' => 'en'])) }}">English (EN)</a></li>
                        <li><a class="dropdown-item" href="{{ route($currentRoute, array_merge($params, ['locale' => 'pt'])) }}">Português (PT)</a></li>
                        <li><a class="dropdown-item" href="{{ route($currentRoute, array_merge($params, ['locale' => 'es'])) }}">Español (ES)</a></li>
                        <li><a class="dropdown-item" href="{{ route($currentRoute, array_merge($params, ['locale' => 'fr'])) }}">Français (FR)</a></li>
                        <li><a class="dropdown-item" href="{{ route($currentRoute, array_merge($params, ['locale' => 'zh'])) }}">中文 (ZH)</a></li>
                        <li><a class="dropdown-item" href="{{ route($currentRoute, array_merge($params, ['locale' => 'hi'])) }}">हिन्दी (HI)</a></li>
                        <li><a class="dropdown-item" href="{{ route($currentRoute, array_merge($params, ['locale' => 'ru'])) }}">Русский (RU)</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<main class="py-4">
    @yield('content')
</main>

<footer class="bg-custom-dark text-white text-center py-4 mt-auto">
    <div class="container">
        <p class="mb-0">&copy; {{ __('messages.copyright') }}</p>
    </div>
</footer>

<!-- Bootstrap 5 JS Bundle via CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</body>
</html>
