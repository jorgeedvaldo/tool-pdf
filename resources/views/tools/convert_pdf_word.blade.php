@extends('layouts.app')

@php $mode = $mode ?? 'pdf-to-word'; @endphp

@section('title', ($mode === 'pdf-to-word' ? 'PDF to Word' : 'Word to PDF') . ' - ToolPDF')

@section('content')

<style>
:root {
    --cw-primary: #E5322D;
    --cw-muted:   #6B7280;
}
.cw-hero {
    background: linear-gradient(135deg, #E5322D 0%, #b52420 100%);
    color: #fff; padding: 44px 0 28px;
}
.cw-hero h1 { font-size: 1.95rem; font-weight: 800; margin-bottom: .35rem; }
.cw-hero .badge {
    background: rgba(255,255,255,.18); border: 1px solid rgba(255,255,255,.35);
    font-size: .73rem; padding: .3em .65em; border-radius: 20px; margin-right: .35rem;
}

.cw-tabs {
    display: flex; gap: 4px; border-bottom: 2px solid #e5e7eb; margin-bottom: 24px;
}
.cw-tab {
    background: transparent; border: none; padding: 12px 22px;
    font-size: .95rem; font-weight: 600; color: var(--cw-muted);
    border-bottom: 3px solid transparent; margin-bottom: -2px;
    cursor: pointer; transition: color .14s, border-color .14s;
}
.cw-tab:hover { color: var(--cw-primary); }
.cw-tab.active { color: var(--cw-primary); border-bottom-color: var(--cw-primary); }

.cw-drop {
    border: 2px dashed #d1d5db; border-radius: 12px; background: #fff;
    transition: border-color .2s, background .2s; cursor: pointer;
    min-height: 220px; padding: 32px 22px; text-align: center;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
}
.cw-drop:hover, .cw-drop.cw-drag-over { border-color: var(--cw-primary); background: #fff5f5; }
.cw-drop-icon { font-size: 3rem; color: var(--cw-primary); margin-bottom: .4rem; }

.cw-file-card {
    border-radius: 10px; background: #fff; border: 1px solid #e5e7eb;
    padding: 14px 16px; display: flex; justify-content: space-between; align-items: center;
}
.cw-file-card .bi-file-earmark-pdf  { color: #ef4444; }
.cw-file-card .bi-file-earmark-word { color: #0d6efd; }

.cw-convert-btn {
    background: var(--cw-primary); border: none; color: #fff;
    font-size: 1.05rem; font-weight: 700; padding: 13px 40px;
    border-radius: 50px; box-shadow: 0 4px 16px rgba(229,50,45,.3);
    transition: opacity .2s, transform .1s;
}
.cw-convert-btn:hover:not(:disabled) { opacity: .92; transform: translateY(-1px); }
.cw-convert-btn:disabled { opacity: .42; cursor: not-allowed; }
</style>

<section class="cw-hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1>
                    <i class="bi {{ $mode === 'pdf-to-word' ? 'bi-file-earmark-word' : 'bi-file-earmark-pdf' }} me-2"></i>
                    {{ $mode === 'pdf-to-word' ? 'PDF to Word' : 'Word to PDF' }}
                </h1>
                <p class="mb-3" style="opacity:.9;font-size:1rem">
                    {{ $mode === 'pdf-to-word'
                        ? 'Convert PDF documents to editable Word (.docx) files — text, tables, and images preserved.'
                        : 'Convert Word documents (.docx, .doc, .odt, .rtf) to PDF — fonts, layout, and images preserved.' }}
                </p>
                <div class="d-flex flex-wrap gap-1">
                    <span class="badge">⚡ High Fidelity</span>
                    <span class="badge">🔧 PHPOffice + mPDF Engine</span>
                    <span class="badge">🗑️ Auto-deleted after conversion</span>
                    <span class="badge">📊 Tables &amp; Lists Detected</span>
                </div>
            </div>
            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                <i class="bi bi-arrow-left-right" style="font-size:4.5rem;opacity:.22"></i>
            </div>
        </div>
    </div>
</section>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">

            {{-- Tabs --}}
            <div class="cw-tabs">
                <button type="button" class="cw-tab {{ $mode === 'pdf-to-word' ? 'active' : '' }}" data-mode="pdf-to-word">
                    <i class="bi bi-file-earmark-pdf me-1"></i> PDF → Word
                </button>
                <button type="button" class="cw-tab {{ $mode === 'word-to-pdf' ? 'active' : '' }}" data-mode="word-to-pdf">
                    <i class="bi bi-file-earmark-word me-1"></i> Word → PDF
                </button>
            </div>

            {{-- PDF → Word pane --}}
            <div class="cw-pane {{ $mode === 'pdf-to-word' ? '' : 'd-none' }}" data-mode="pdf-to-word">
                <div id="cw-pw-zone" class="cw-drop">
                    <div class="cw-drop-icon"><i class="bi bi-cloud-upload"></i></div>
                    <h5 class="fw-bold mb-1">Select or drop a PDF file</h5>
                    <p class="text-muted mb-0 small">Extract text, layout and (optionally) page images into a .docx file.</p>
                    <input type="file" id="cw-pw-input" class="d-none" accept="application/pdf,.pdf">
                </div>
                <div id="cw-pw-card" class="cw-file-card mt-3 d-none">
                    <div class="d-flex align-items-center gap-3">
                        <i class="bi bi-file-earmark-pdf fs-3"></i>
                        <div>
                            <div class="fw-semibold text-truncate" style="max-width:280px" id="cw-pw-name">—</div>
                            <small class="text-muted" id="cw-pw-size">—</small>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger rounded-circle px-2" id="cw-pw-remove" title="Remove"><i class="bi bi-x-lg"></i></button>
                </div>

                <p class="text-muted small mt-3 mb-0">
                    <i class="bi bi-cpu me-1"></i>Conversion runs on the server using LibreOffice — preserves text, tables, images, and fonts.
                </p>
            </div>

            {{-- Word → PDF pane --}}
            <div class="cw-pane {{ $mode === 'word-to-pdf' ? '' : 'd-none' }}" data-mode="word-to-pdf">
                <div id="cw-wp-zone" class="cw-drop">
                    <div class="cw-drop-icon"><i class="bi bi-cloud-upload"></i></div>
                    <h5 class="fw-bold mb-1">Select or drop a Word (.docx) file</h5>
                    <p class="text-muted mb-0 small">Supports .docx, .doc, .odt and .rtf formats.</p>
                    <input type="file" id="cw-wp-input" class="d-none" accept=".docx,.doc,.odt,.rtf">
                </div>
                <div id="cw-wp-card" class="cw-file-card mt-3 d-none">
                    <div class="d-flex align-items-center gap-3">
                        <i class="bi bi-file-earmark-word fs-3"></i>
                        <div>
                            <div class="fw-semibold text-truncate" style="max-width:280px" id="cw-wp-name">—</div>
                            <small class="text-muted" id="cw-wp-size">—</small>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger rounded-circle px-2" id="cw-wp-remove" title="Remove"><i class="bi bi-x-lg"></i></button>
                </div>
            </div>

            {{-- Progress --}}
            <div id="cw-progress" class="mt-4 d-none">
                <div class="d-flex justify-content-between mb-1 small">
                    <span id="cw-progress-msg" class="text-muted">A processar…</span>
                </div>
                <div class="progress" style="height: 10px; border-radius: 6px;">
                    <div id="cw-progress-bar" class="progress-bar bg-danger progress-bar-striped progress-bar-animated" style="width:0%"></div>
                </div>
            </div>

            {{-- Action --}}
            <div class="text-center mt-4">
                <button type="button" id="cw-convert-btn" class="cw-convert-btn" disabled>
                    <i class="bi bi-arrow-repeat me-2"></i>Convert &amp; Download
                </button>
            </div>

            <div id="cw-error" class="alert alert-danger mt-3 d-none"></div>

            <p class="text-center mt-3 small text-muted">
                <i class="bi bi-shield-lock-fill text-success me-1"></i>
                Files are processed on our server and automatically deleted immediately after conversion.
            </p>
        </div>
    </div>
</div>

{{-- SEO --}}
<div class="bg-white py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <h2 class="fw-bold mb-3">How to convert PDF to Word and Word to PDF</h2>
                <ol class="mb-4">
                    <li class="mb-1">Choose a direction: <strong>PDF → Word</strong> or <strong>Word → PDF</strong>.</li>
                    <li class="mb-1">Drop your file in the upload area (or click to browse).</li>
                    <li class="mb-1">Click <strong>Convert &amp; Download</strong> — the file is processed locally in your browser.</li>
                    <li class="mb-1">Save the converted file to your device.</li>
                </ol>

                <h2 class="fw-bold mb-3">Features</h2>
                <div class="row g-3 mb-4">
                    <div class="col-md-6"><div class="d-flex gap-3"><i class="bi bi-file-earmark-word fs-4 text-danger mt-1"></i><div><strong>Editable Word output</strong><br><span class="text-muted small">PDF → DOCX with paragraphs, alignment, and font sizes preserved.</span></div></div></div>
                    <div class="col-md-6"><div class="d-flex gap-3"><i class="bi bi-file-earmark-pdf fs-4 text-danger mt-1"></i><div><strong>Clean PDF output</strong><br><span class="text-muted small">Word → PDF with headings, lists, tables, and images preserved.</span></div></div></div>
                    <div class="col-md-6"><div class="d-flex gap-3"><i class="bi bi-shield-lock fs-4 text-danger mt-1"></i><div><strong>100% Private</strong><br><span class="text-muted small">No upload — everything happens locally in your browser.</span></div></div></div>
                    <div class="col-md-6"><div class="d-flex gap-3"><i class="bi bi-lightning fs-4 text-danger mt-1"></i><div><strong>Fast and free</strong><br><span class="text-muted small">No registration, no watermark, no limits.</span></div></div></div>
                </div>

                <h2 class="fw-bold mb-3">Frequently Asked Questions</h2>
                <div class="accordion accordion-flush mb-4" id="cw-faq">
                    <div class="accordion-item border-bottom">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#cw-q1">Are scanned PDFs supported?</button>
                        </h3>
                        <div id="cw-q1" class="accordion-collapse collapse" data-bs-parent="#cw-faq">
                            <div class="accordion-body text-muted">Scanned PDFs contain images, not text. Conversion will produce an empty document unless OCR is applied first. Use the PDF OCR tool, then run conversion.</div>
                        </div>
                    </div>
                    <div class="accordion-item border-bottom">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#cw-q2">Which Word formats can I convert?</button>
                        </h3>
                        <div id="cw-q2" class="accordion-collapse collapse" data-bs-parent="#cw-faq">
                            <div class="accordion-body text-muted">Word &rarr; PDF accepts .docx, legacy .doc (Word 97-2003), .odt and .rtf. .docx gives the most faithful result, because the older formats carry less style information; .rtf in particular loses accented characters. For the best output, open the file in Word or LibreOffice and save it as .docx first.</div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#cw-q3">Is the formatting 100% preserved?</button>
                        </h3>
                        <div id="cw-q3" class="accordion-collapse collapse" data-bs-parent="#cw-faq">
                            <div class="accordion-body text-muted">PDF and Word have different layout models, so converting between them is approximate. We preserve text, headings, paragraphs, alignment, lists, basic styling and images. For pixel-perfect layout, enable "embed page image" when converting PDF → Word.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
window.CW_INITIAL_MODE = @json($mode);
window.CW_ROUTES = {
    pdfToWord: '{{ route('convert.pdf_to_word') }}',
    wordToPdf: '{{ route('convert.word_to_pdf') }}',
    csrfToken: '{{ csrf_token() }}',
};
</script>
<script src="{{ asset('js/tools/convert-pdf-word.js') }}"></script>
@endpush

@endsection
