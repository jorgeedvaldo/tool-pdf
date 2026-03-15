@extends('layouts.app')

@section('title', __('messages.legal') . ' - ToolPDF')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-9 text-dark">
            <h1 class="mb-4 text-secondary fw-bold"><i class="bi bi-bank me-3"></i>{{ __('messages.legal') }}</h1>
            <div class="card shadow-sm border-0 bg-white">
                <div class="card-body p-4 p-md-5 fs-5" style="line-height: 1.8;">
                    {!! nl2br(e(__('messages.legal_content'))) !!}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
