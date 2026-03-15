@extends('layouts.app')

@section('title', 'Careers - ToolPDF')

@section('content')
<div class="container py-5">
    <div class="row mb-5 text-center">
        <div class="col-12">
            <h1 class="fw-bold mb-3">Join Our Team</h1>
            <p class="lead text-muted">Help us build the best privacy-focused PDF tools on the web.</p>
        </div>
    </div>

    @if($jobs->count() > 0)
        <div class="row g-4">
            @foreach($jobs as $job)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm tool-card border-pattern">
                        @if($job->image)
                            <img src="{{ Storage::url($job->image) }}" class="card-img-top" alt="{{ $job->title }}" style="height: 200px; object-fit: cover;">
                        @endif
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title fw-bold">{{ $job->title }}</h5>
                            <h6 class="card-subtitle mb-2 text-primary"><i class="bi bi-building me-1"></i> {{ $job->company }}</h6>
                            <p class="card-text text-muted small"><i class="bi bi-geo-alt me-1"></i> {{ $job->location ?? 'Remote' }}</p>
                            <p class="card-text flex-grow-1">{{ Str::limit(strip_tags($job->description), 100) }}</p>
                            <a href="{{ url('/jobs', $job->slug) }}" class="btn btn-outline-primary mt-3 w-100 fw-bold">View Details</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        
        <div class="d-flex justify-content-center mt-5">
            {{ $jobs->links('pagination::bootstrap-5') }}
        </div>
    @else
        <div class="text-center py-5 bg-light rounded shadow-sm border-pattern">
            <i class="bi bi-emoji-smile fs-1 text-muted mb-3 d-block"></i>
            <h4 class="fw-bold">No Open Positions</h4>
            <p class="text-muted">We don't have any open roles right now. Check back later!</p>
        </div>
    @endif
</div>
@endsection
