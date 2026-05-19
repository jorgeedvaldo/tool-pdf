@extends('layouts.app')

@section('title', $config['title'] . ' - ToolPDF')

@php
    $color = $config['icon_color'] ?? 'success';
    $gradients = [
        'success' => 'linear-gradient(135deg, #16a34a 0%, #15803d 100%)',
        'danger'  => 'linear-gradient(135deg, #E5322D 0%, #b52420 100%)',
        'primary' => 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)',
        'warning' => 'linear-gradient(135deg, #d97706 0%, #b45309 100%)',
        'info'    => 'linear-gradient(135deg, #0891b2 0%, #0e7490 100%)',
    ];
    $heroGradient = $gradients[$color] ?? $gradients['primary'];
    $hoverBg = [
        'success' => '#f0fdf4',
        'danger'  => '#fff5f5',
        'primary' => '#eff6ff',
        'warning' => '#fffbeb',
        'info'    => '#ecfeff',
    ];
    $hoverBgColor = $hoverBg[$color] ?? $hoverBg['primary'];
    $shadowColor = [
        'success' => 'rgba(22,163,74,.3)',
        'danger'  => 'rgba(229,50,45,.3)',
        'primary' => 'rgba(37,99,235,.3)',
        'warning' => 'rgba(217,119,6,.3)',
        'info'    => 'rgba(8,145,178,.3)',
    ];
    $btnShadow = $shadowColor[$color] ?? $shadowColor['primary'];
@endphp

@section('content')

<style>
:root {
    --cg-primary: {{ $heroGradient }};
    --cg-hover-bg: {{ $hoverBgColor }};
    --cg-btn-shadow: {{ $btnShadow }};
    --cg-muted: #6B7280;
}
.cg-hero {
    background: {{ $heroGradient }};
    color: #fff; padding: 44px 0 28px;
}
.cg-hero h1 { font-size: 1.95rem; font-weight: 800; margin-bottom: .35rem; }
.cg-hero .badge {
    background: rgba(255,255,255,.18); border: 1px solid rgba(255,255,255,.35);
    font-size: .73rem; padding: .3em .65em; border-radius: 20px; margin-right: .35rem;
}

.cg-drop {
    border: 2px dashed #d1d5db; border-radius: 12px; background: #fff;
    transition: border-color .2s, background .2s; cursor: pointer;
    min-height: 220px; padding: 32px 22px; text-align: center;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
}
.cg-drop:hover, .cg-drop.cg-drag-over {
    border-color: currentColor;
    background: var(--cg-hover-bg);
}
.cg-drop-icon { font-size: 3rem; margin-bottom: .4rem; }

.cg-file-card {
    border-radius: 10px; background: #fff; border: 1px solid #e5e7eb;
    padding: 14px 16px; display: flex; justify-content: space-between; align-items: center;
}

.cg-convert-btn {
    background: {{ str_replace(['linear-gradient(135deg, ', ' 100%)'], ['', ''], explode(' 0%,', $heroGradient)[0]) }};
    background: {{ $heroGradient }};
    border: none; color: #fff;
    font-size: 1.05rem; font-weight: 700; padding: 13px 40px;
    border-radius: 50px; box-shadow: 0 4px 16px var(--cg-btn-shadow);
    transition: opacity .2s, transform .1s;
}
.cg-convert-btn:hover:not(:disabled) { opacity: .92; transform: translateY(-1px); }
.cg-convert-btn:disabled { opacity: .42; cursor: not-allowed; }
</style>

<section class="cg-hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1>
                    <i class="bi {{ $config['icon_class'] }} me-2"></i>
                    {{ $config['title'] }}
                </h1>
                <p class="mb-3" style="opacity:.9;font-size:1rem">
                    {{ $config['title_desc'] }}
                </p>
                <div class="d-flex flex-wrap gap-1">
                    @foreach ($config['badges'] as $badge)
                        <span class="badge">{{ $badge }}</span>
                    @endforeach
                </div>
            </div>
            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                <i class="bi {{ $config['icon_class'] }}" style="font-size:4.5rem;opacity:.22"></i>
            </div>
        </div>
    </div>
</section>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">

            {{-- Drop zone --}}
            <div id="cg-zone" class="cg-drop text-{{ $config['icon_color'] }}">
                <div class="cg-drop-icon"><i class="bi bi-cloud-upload"></i></div>
                <h5 class="fw-bold mb-1 text-dark">{{ $config['accept_label'] }}</h5>
                <p class="text-muted mb-0 small">Click to browse or drag and drop your file here</p>
                <input type="file" id="cg-input" class="d-none" accept="{{ $config['accept'] }}">
            </div>

            {{-- File card --}}
            <div id="cg-card" class="cg-file-card mt-3 d-none">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi {{ $config['icon_class'] }} fs-3 text-{{ $config['icon_color'] }}"></i>
                    <div>
                        <div class="fw-semibold text-truncate" style="max-width:280px" id="cg-name">—</div>
                        <small class="text-muted" id="cg-size">—</small>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger rounded-circle px-2" id="cg-remove" title="Remove">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            {{-- Progress --}}
            <div id="cg-progress" class="mt-4 d-none">
                <div class="d-flex justify-content-between mb-1 small">
                    <span id="cg-progress-msg" class="text-muted">Processing…</span>
                </div>
                <div class="progress" style="height: 10px; border-radius: 6px;">
                    <div id="cg-progress-bar"
                         class="progress-bar bg-{{ $config['icon_color'] }} progress-bar-striped progress-bar-animated"
                         style="width:0%"></div>
                </div>
            </div>

            {{-- Action --}}
            <div class="text-center mt-4">
                <button type="button" id="cg-convert-btn" class="cg-convert-btn" disabled>
                    <i class="bi bi-arrow-repeat me-2"></i>Convert &amp; Download
                </button>
            </div>

            <div id="cg-error" class="alert alert-danger mt-3 d-none"></div>

            <p class="text-center mt-3 small text-muted">
                <i class="bi bi-shield-lock-fill text-success me-1"></i>
                Files are processed on our server and automatically deleted immediately after conversion.
            </p>

        </div>
    </div>
</div>

@push('scripts')
<script>
window.CG_CONFIG = {
    route: '{{ route($config["route"]) }}',
    csrfToken: '{{ csrf_token() }}',
    outputExt: '{{ $config["output_ext"] }}',
    accept: '{{ $config["accept"] }}',
};
</script>
<script src="{{ asset('js/tools/convert-generic.js') }}"></script>
@endpush

@endsection
