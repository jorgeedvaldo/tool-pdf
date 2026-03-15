@extends('layouts.app')

@section('title', $job->title . ' - Careers at ToolPDF')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ url('/jobs') }}" class="text-decoration-none">Careers</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $job->title }}</li>
                </ol>
            </nav>

            <div class="card shadow border-0 bg-white">
                @if($job->image)
                    <img src="{{ Storage::url($job->image) }}" class="card-img-top" alt="{{ $job->title }}" style="max-height: 400px; object-fit: cover;">
                @endif
                <div class="card-body p-5">
                    <h1 class="fw-bold mb-3">{{ $job->title }}</h1>
                    
                    <div class="d-flex flex-wrap gap-3 mb-4 pb-4 border-bottom text-muted">
                        <span><i class="bi bi-building me-1 text-primary"></i> {{ $job->company }}</span>
                        <span><i class="bi bi-geo-alt me-1 text-danger"></i> {{ $job->location ?? 'Remote' }}</span>
                        <span><i class="bi bi-calendar3 me-1 text-success"></i> Posted {{ $job->created_at->diffForHumans() }}</span>
                    </div>

                    <div class="job-description mb-5" style="line-height: 1.8;">
                        {!! $job->description !!}
                    </div>

                    <div class="text-center mt-4 pt-4 border-top">
                        <a href="{{ filter_var($job->email_or_link, FILTER_VALIDATE_URL) ? $job->email_or_link : 'mailto:' . $job->email_or_link }}" class="btn btn-primary btn-lg px-5 fw-bold shadow-sm" target="_blank">
                            <i class="bi bi-send me-2"></i> Apply Now
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
