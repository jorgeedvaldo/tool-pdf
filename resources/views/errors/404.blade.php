@extends('layouts.app')

@section('title', __('Not Found') . ' - ToolPDF')

@section('content')
<div class="container py-5 text-center" style="min-height: 60vh; display: flex; flex-direction: column; justify-content: center;">
    <div class="row justify-content-center">
        <div class="col-md-8 mt-5">
            <div class="card shadow-retro border-pattern p-5">
                <div class="card-body">
                    <h1 class="display-1 fw-bold text-dark mb-3">404</h1>
                    <h2 class="h3 mb-4 fw-bold">{{ __('Page Not Found') }}</h2>
                    <p class="lead text-muted mb-5">
                        {{ __('Oops! The page you are looking for does not exist. It might have been moved or deleted.') }}
                    </p>
                    <a href="{{ url('/') }}" class="btn btn-dark px-4 py-2 fw-bold rounded-pill shadow-sm">
                        <i class="bi bi-house-door me-2"></i> {{ __('Go back to Homepage') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
