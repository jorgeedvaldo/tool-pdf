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
        <a class="navbar-brand d-flex align-items-center" href="{{ url('/') }}">
            <i class="bi bi-file-earmark-pdf-fill text-danger me-2 fs-3"></i>
            <span class="fw-bold fs-4">ToolPDF</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="{{ url('/') }}">{{ __('messages.home') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#tools-section">{{ __('messages.tools') }}</a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle bg-language-selector" href="#" id="languageDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-globe me-1"></i> {{ strtoupper(app()->getLocale()) }}
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="languageDropdown">
                        <li><a class="dropdown-item" href="{{ url('lang/en') }}">English (EN)</a></li>
                        <li><a class="dropdown-item" href="{{ url('lang/pt') }}">Português (PT)</a></li>
                        <li><a class="dropdown-item" href="{{ url('lang/es') }}">Español (ES)</a></li>
                        <li><a class="dropdown-item" href="{{ url('lang/fr') }}">Français (FR)</a></li>
                        <li><a class="dropdown-item" href="{{ url('lang/zh') }}">中文 (ZH)</a></li>
                        <li><a class="dropdown-item" href="{{ url('lang/hi') }}">हिन्दी (HI)</a></li>
                        <li><a class="dropdown-item" href="{{ url('lang/ru') }}">Русский (RU)</a></li>
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
