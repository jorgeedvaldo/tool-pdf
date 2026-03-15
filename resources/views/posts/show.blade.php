@extends('layouts.app')

@section('title', $post->title . ' - ToolPDF Blog')

@section('content')
<!-- NewsArticle JSON-LD -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "NewsArticle",
  "headline": "{{ $post->title }}",
  "image": [
    "{{ $post->image ? url(Storage::url($post->image)) : asset('img/logo.svg') }}"
   ],
  "datePublished": "{{ $post->created_at->toIso8601String() }}",
  "dateModified": "{{ $post->updated_at->toIso8601String() }}",
  "author": [{
      "@type": "Organization",
      "name": "ToolPDF",
      "url": "{{ url('/') }}"
    }]
}
</script>

<div class="container py-5">
    <div class="row">
        <!-- Main Article -->
        <div class="col-lg-8 mb-5 mb-lg-0">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('blog.index', ['locale' => app()->getLocale()]) }}" class="text-decoration-none">Blog</a></li>
                    <li class="breadcrumb-item active text-truncate" aria-current="page" style="max-width: 200px;">{{ $post->title }}</li>
                </ol>
            </nav>

            <article class="bg-white p-4 p-md-5 rounded shadow-sm border-0">
                <header class="mb-4 pb-4 border-bottom">
                    <h1 class="fw-bold mb-3" style="line-height: 1.3;">{{ $post->title }}</h1>
                    <div class="d-flex align-items-center text-muted small">
                        <span class="me-3"><i class="bi bi-calendar-event me-1"></i> {{ $post->created_at->format('F j, Y') }}</span>
                        <span><i class="bi bi-clock me-1"></i> {{ ceil(str_word_count(strip_tags($post->description)) / 200) }} min read</span>
                    </div>
                </header>

                @if($post->image)
                    <img src="{{ Storage::url($post->image) }}" class="img-fluid rounded mb-4 w-100" alt="{{ $post->title }}" style="max-height: 450px; object-fit: cover;">
                @endif

                <div class="article-content" style="line-height: 1.8; font-size: 1.05rem;">
                    {!! $post->description !!}
                </div>
            </article>
        </div>

        <!-- Sidebar / Recent Posts -->
        <div class="col-lg-4">
            <div class="bg-white p-4 rounded shadow-sm border-0 sticky-top" style="top: 20px;">
                <h5 class="fw-bold mb-4 border-bottom pb-2"><i class="bi bi-clock-history me-2 text-primary"></i>Recent Articles</h5>
                
                @if($recentPosts->count() > 0)
                    <ul class="list-unstyled mb-0">
                        @foreach($recentPosts as $recent)
                            <li class="mb-4 pb-3 border-bottom border-light">
                                <a href="{{ route('blog.show', ['slug' => $recent->slug, 'locale' => app()->getLocale()]) }}" class="text-decoration-none text-dark d-flex flex-column">
                                    <span class="fw-bold mb-1 hover-primary">{{ $recent->title }}</span>
                                    <small class="text-muted">{{ $recent->created_at->format('M d, Y') }}</small>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-muted small mb-0">No recent articles found.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
    .hover-primary { transition: color 0.2s; }
    .hover-primary:hover { color: #0d6efd !important; }
    .article-content img { max-width: 100%; height: auto; border-radius: 8px; margin: 1.5rem 0; }
    .article-content h2, .article-content h3 { font-weight: bold; margin-top: 2rem; margin-bottom: 1rem; }
    .article-content p { margin-bottom: 1.5rem; }
</style>
@endsection
