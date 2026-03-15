@extends('layouts.app')

@section('title', __('messages.blog') ?? 'Blog' . ' - ToolPDF')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center mb-5 text-center">
        <div class="col-12 col-md-8">
            <h1 class="fw-bold mb-3"><i class="bi bi-newspaper me-2 text-primary"></i>ToolPDF Blog</h1>
            <p class="lead text-muted">Tips, tutorials, and news about PDF processing and digital privacy.</p>
        </div>
    </div>

    @if($posts->count() > 0)
        <div class="row g-4">
            @foreach($posts as $post)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm tool-card border-0">
                        @if($post->image)
                            <img src="{{ Storage::url($post->image) }}" class="card-img-top" alt="{{ $post->title }}" style="height: 220px; object-fit: cover;">
                        @else
                            <div class="bg-primary bg-gradient card-img-top d-flex align-items-center justify-content-center" style="height: 220px;">
                                <i class="bi bi-journal-text text-white" style="font-size: 4rem;"></i>
                            </div>
                        @endif
                        <div class="card-body d-flex flex-column p-4">
                            <span class="badge bg-light text-primary mb-2 align-self-start"><i class="bi bi-calendar3 me-1"></i>{{ $post->created_at->format('M d, Y') }}</span>
                            <h5 class="card-title fw-bold mb-3">{{ $post->title }}</h5>
                            <p class="card-text text-muted flex-grow-1">{{ Str::limit(strip_tags($post->description), 120) }}</p>
                            <a href="{{ route('blog.show', ['slug' => $post->slug, 'locale' => app()->getLocale()]) }}" class="text-decoration-none fw-bold text-primary mt-3 d-inline-flex align-items-center">
                                Read Article <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        
        <div class="d-flex justify-content-center mt-5">
            {{ $posts->links('pagination::bootstrap-5') }}
        </div>
    @else
        <div class="text-center py-5 bg-white rounded shadow-sm border p-4">
            <i class="bi bi-pen fs-1 text-muted mb-3 d-block"></i>
            <h4 class="fw-bold">No Articles Yet</h4>
            <p class="text-muted">We haven't published any articles in this language yet. Please check back later!</p>
        </div>
    @endif
</div>
@endsection
