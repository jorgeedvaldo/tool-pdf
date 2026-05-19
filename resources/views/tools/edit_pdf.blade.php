@extends('layouts.app')

@section('title', __('messages.edit_pdf') . ' - ToolPDF')

@section('content')

<style>
:root {
    --ed-primary: #E5322D;
    --ed-dark:    #222;
    --ed-muted:   #6B7280;
    --ed-panel-bg:#525659;
    --ed-tools-w: 72px;
    --ed-props-w: 290px;
    --ed-top-h:   52px;
    --ed-thumb-h: 92px;
}

/* ── Hero ── */
.ed-hero {
    background: linear-gradient(135deg, #E5322D 0%, #b52420 100%);
    color: #fff; padding: 44px 0 30px;
}
.ed-hero h1 { font-size: 1.95rem; font-weight: 800; margin-bottom: .35rem; }
.ed-hero .badge {
    background: rgba(255,255,255,.18); border: 1px solid rgba(255,255,255,.35);
    font-size: .73rem; padding: .3em .65em; border-radius: 20px; margin-right: .35rem;
}

/* ── Upload ── */
.ed-upload-card {
    border: 2px dashed #d1d5db; border-radius: 12px; background: #fff;
    transition: border-color .2s, background .2s; cursor: pointer; min-height: 200px;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    padding: 30px 20px; text-align: center;
}
.ed-upload-card:hover, .ed-upload-card.ed-drag-over { border-color: var(--ed-primary); background: #fff5f5; }
.ed-upload-icon { font-size: 3rem; color: var(--ed-primary); margin-bottom: .5rem; }

/* ── Editor layout ── */
#ed-editor {
    display: flex; flex-direction: column;
    margin-top: -1.5rem;
    background: #2d2d2d;
}

.ed-top-bar {
    height: var(--ed-top-h); background: #fff;
    border-bottom: 1px solid #e5e7eb;
    display: flex; align-items: center; gap: 8px;
    padding: 0 14px; flex-shrink: 0; flex-wrap: wrap;
}
.ed-top-sep { width: 1px; height: 22px; background: #e5e7eb; flex-shrink: 0; }
.ed-file-info { display: flex; align-items: center; gap: 10px; font-size: .85rem; min-width: 0; }
.ed-file-info i { color: var(--ed-primary); font-size: 1.4rem; }
.ed-file-info .ed-fname { font-weight: 600; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.ed-file-info small { color: var(--ed-muted); }

.ed-body {
    display: flex;
    height: calc(100vh - 58px - var(--ed-top-h) - var(--ed-thumb-h));
    min-height: 460px;
    overflow: hidden;
}

/* Tools palette (left) */
.ed-tools {
    width: var(--ed-tools-w); flex-shrink: 0;
    background: #383838; padding: 8px 6px;
    display: flex; flex-direction: column; gap: 4px;
    border-right: 1px solid #1f1f1f;
}
.ed-tool-btn {
    background: transparent; color: #ddd; border: 1px solid transparent;
    border-radius: 8px; padding: 8px 4px;
    display: flex; flex-direction: column; align-items: center; gap: 2px;
    font-size: .68rem; cursor: pointer;
    transition: background .12s, border-color .12s, color .12s;
}
.ed-tool-btn i { font-size: 1.15rem; }
.ed-tool-btn:hover { background: #4a4a4a; color: #fff; }
.ed-tool-btn.active { background: var(--ed-primary); color: #fff; border-color: var(--ed-primary); }

/* Canvas area (centre) */
.ed-canvas-area {
    flex: 1; overflow: auto; background: var(--ed-panel-bg);
    padding: 22px; display: flex; flex-direction: column; align-items: center; gap: 22px;
}

.ed-page-wrap {
    position: relative; background: #fff;
    box-shadow: 0 4px 20px rgba(0,0,0,.5); flex-shrink: 0;
}
.ed-page-canvas {
    display: block; width: 100%; height: 100%; user-select: none;
}
.ed-page-lbl {
    position: absolute; top: -20px; left: 0;
    font-size: 11px; color: #bbb; white-space: nowrap;
}
.ed-overlay {
    position: absolute; top: 0; left: 0; width: 100%; height: 100%;
    cursor: inherit;
}

/* Item (overlay element) */
.ed-item {
    position: absolute; box-sizing: border-box;
    border: 1px dashed transparent;
    transform-origin: center center;
}
.ed-item:hover { border-color: rgba(229,50,45,.5); }
.ed-item.selected { border: 1px solid var(--ed-primary); }
.ed-item-text {
    width: 100%; height: 100%;
    outline: none; padding: 2px 4px;
    overflow: hidden; word-wrap: break-word;
    cursor: text;
}
.ed-item[data-type="image"], .ed-item[data-type="signature"] { cursor: move; }

.ed-handle {
    position: absolute; width: 9px; height: 9px;
    background: #fff; border: 1.5px solid var(--ed-primary);
    border-radius: 50%; display: none; z-index: 5;
}
.ed-item.selected .ed-handle { display: block; }
.ed-handle-nw { top: -5px; left: -5px; cursor: nwse-resize; }
.ed-handle-n  { top: -5px; left: 50%; transform: translateX(-50%); cursor: ns-resize; }
.ed-handle-ne { top: -5px; right: -5px; cursor: nesw-resize; }
.ed-handle-e  { top: 50%; right: -5px; transform: translateY(-50%); cursor: ew-resize; }
.ed-handle-se { bottom: -5px; right: -5px; cursor: nwse-resize; }
.ed-handle-s  { bottom: -5px; left: 50%; transform: translateX(-50%); cursor: ns-resize; }
.ed-handle-sw { bottom: -5px; left: -5px; cursor: nesw-resize; }
.ed-handle-w  { top: 50%; left: -5px; transform: translateY(-50%); cursor: ew-resize; }
.ed-handle-rot {
    top: -26px; left: 50%; transform: translateX(-50%);
    background: var(--ed-primary); cursor: grab; width: 12px; height: 12px;
}
.ed-handle-rot::before {
    content: ''; position: absolute; top: 100%; left: 50%;
    width: 1px; height: 14px; background: var(--ed-primary);
    transform: translateX(-50%);
}

.ed-item-del {
    position: absolute; top: -28px; right: -10px;
    background: #fff; border: 1px solid #e5e7eb; color: #ef4444;
    width: 22px; height: 22px; border-radius: 50%;
    display: none; align-items: center; justify-content: center;
    font-size: .72rem; cursor: pointer; padding: 0;
    box-shadow: 0 1px 4px rgba(0,0,0,.15);
}
.ed-item.selected .ed-item-del { display: flex; }
.ed-item-del:hover { background: #fee2e2; }

.ed-rubber-band {
    position: absolute; border: 1.5px dashed var(--ed-primary);
    background: rgba(229,50,45,.07); pointer-events: none;
}

/* Properties panel */
.ed-props {
    width: var(--ed-props-w); flex-shrink: 0;
    background: #fff; border-left: 1px solid #e5e7eb;
    display: flex; flex-direction: column;
}
.ed-props-hdr {
    padding: 10px 14px; border-bottom: 1px solid #e5e7eb;
    font-size: .82rem; font-weight: 700; color: var(--ed-muted);
    text-transform: uppercase; letter-spacing: .04em;
}
.ed-props-body { flex: 1; overflow-y: auto; padding: 12px; font-size: .8rem; }

/* Thumbnails strip */
.ed-thumbs-wrap {
    height: var(--ed-thumb-h); flex-shrink: 0;
    background: #1f1f1f; padding: 8px 12px;
    display: flex; align-items: center; gap: 8px; overflow: hidden;
    border-top: 1px solid #111;
}
.ed-thumbs-label {
    font-size: .7rem; font-weight: 700; color: #999; white-space: nowrap;
}
.ed-thumbs {
    display: flex; gap: 6px; overflow-x: auto; padding: 4px 0; flex: 1;
}
.ed-thumb {
    flex-shrink: 0; width: 56px; cursor: pointer; text-align: center;
    border-radius: 4px; border: 2px solid transparent; background: #fff;
    overflow: hidden; transition: border-color .12s, transform .1s;
}
.ed-thumb:hover { transform: scale(1.07); }
.ed-thumb.ed-thumb-active { border-color: var(--ed-primary); box-shadow: 0 0 0 2px rgba(229,50,45,.3); }
.ed-thumb img { width: 100%; display: block; height: 64px; object-fit: cover; }
.ed-thumb-lbl { font-size: 10px; font-weight: 700; color: #6b7280; background: #f3f4f6; padding: 1px 0; }

/* Toast */
.ed-toast {
    position: fixed; bottom: 28px; left: 50%; transform: translateX(-50%);
    background: rgba(0,0,0,.85); color: #fff;
    padding: 10px 18px; border-radius: 22px;
    font-size: .82rem; z-index: 1090;
    opacity: 0; pointer-events: none; transition: opacity .2s;
}
.ed-toast.show { opacity: 1; }

/* Signature modal */
.ed-sig-modal {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,.55); z-index: 1080;
    align-items: center; justify-content: center;
}
.ed-sig-modal .ed-sig-dlg {
    background: #fff; border-radius: 10px; padding: 18px;
    width: min(640px, 92vw); box-shadow: 0 12px 50px rgba(0,0,0,.4);
}
.ed-sig-modal h5 { margin: 0 0 10px; font-weight: 700; }
.ed-sig-modal canvas {
    background: #fafafa; border: 1px dashed #ccc; border-radius: 6px;
    width: 100%; height: 220px; touch-action: none;
}
</style>

{{-- ══════════════ HERO ══════════════ --}}
<section class="ed-hero" id="ed-hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1><i class="bi bi-pencil-square me-2"></i>{{ __('messages.edit_pdf') }}</h1>
                <p class="mb-3" style="opacity:.9;font-size:1rem">Edite PDFs no seu navegador como no Adobe Acrobat ou Zendocs — adicione e edite texto, formas, imagens, assinaturas, destaque, e mais.</p>
                <div class="d-flex flex-wrap gap-1">
                    <span class="badge">🔒 100% Local</span>
                    <span class="badge">☁️ Sem Upload</span>
                    <span class="badge">✏️ Texto editável</span>
                    <span class="badge">🖼️ Imagens & Assinatura</span>
                </div>
            </div>
            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                <i class="bi bi-pencil-square" style="font-size:4.5rem;opacity:.2"></i>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════ UPLOAD ══════════════ --}}
<div id="ed-upload-section" class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-9">
            <div id="ed-drop-zone" class="ed-upload-card">
                <div class="ed-upload-icon"><i class="bi bi-cloud-upload"></i></div>
                <h5 class="fw-bold mb-1">Selecione ou arraste um PDF</h5>
                <p class="text-muted mb-0 small">Comece a editar instantaneamente — tudo no seu navegador.</p>
                <input type="file" id="ed-file-input" class="d-none" accept="application/pdf">
            </div>
        </div>
    </div>
</div>

{{-- ══════════════ EDITOR ══════════════ --}}
<div id="ed-editor" class="d-none">

    {{-- Top toolbar --}}
    <div class="ed-top-bar">
        <div class="ed-file-info">
            <i class="bi bi-file-earmark-pdf"></i>
            <div>
                <div class="ed-fname" id="ed-file-name">—</div>
                <small id="ed-file-size">—</small>
            </div>
        </div>

        <div class="ed-top-sep"></div>

        <button id="ed-undo" class="btn btn-sm btn-outline-secondary" title="Anular (Ctrl+Z)"><i class="bi bi-arrow-counterclockwise"></i></button>
        <button id="ed-redo" class="btn btn-sm btn-outline-secondary" title="Refazer (Ctrl+Y)"><i class="bi bi-arrow-clockwise"></i></button>

        <div class="ed-top-sep"></div>

        <button id="ed-zoom-out" class="btn btn-sm btn-outline-secondary"><i class="bi bi-dash-lg"></i></button>
        <span id="ed-zoom-label" class="badge bg-secondary">100%</span>
        <button id="ed-zoom-in" class="btn btn-sm btn-outline-secondary"><i class="bi bi-plus-lg"></i></button>
        <button id="ed-fit" class="btn btn-sm btn-outline-secondary" title="Ajustar à largura"><i class="bi bi-arrows-expand"></i></button>

        <div class="ed-top-sep"></div>

        <button id="ed-prev" class="btn btn-sm btn-outline-secondary"><i class="bi bi-chevron-left"></i></button>
        <span style="font-size:.85rem;font-weight:600">
            <span id="ed-page-cur">1</span> / <span id="ed-page-total">—</span>
        </span>
        <button id="ed-next" class="btn btn-sm btn-outline-secondary"><i class="bi bi-chevron-right"></i></button>

        <div class="ms-auto d-flex gap-2">
            <button id="ed-new" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-file-earmark me-1"></i>Novo
            </button>
            <button id="ed-save" class="btn btn-sm btn-danger fw-bold">
                <i class="bi bi-download me-1"></i>Guardar PDF
                <span class="spinner-border spinner-border-sm d-none ms-1" id="ed-save-spinner"></span>
            </button>
        </div>
    </div>

    {{-- Body: tools | canvas | properties --}}
    <div class="ed-body">

        {{-- Left tool palette --}}
        <div class="ed-tools">
            <button class="ed-tool-btn active" data-tool="select"     title="Selecionar / mover"><i class="bi bi-cursor"></i><span>Selec.</span></button>
            <button class="ed-tool-btn"        data-tool="text"       title="Adicionar texto"><i class="bi bi-fonts"></i><span>Texto</span></button>
            <button class="ed-tool-btn"        data-tool="edit-text"  title="Editar texto existente"><i class="bi bi-input-cursor-text"></i><span>Editar</span></button>
            <button class="ed-tool-btn"        data-tool="rect"       title="Retângulo"><i class="bi bi-square"></i><span>Forma</span></button>
            <button class="ed-tool-btn"        data-tool="highlight"  title="Destaque"><i class="bi bi-highlighter"></i><span>Destaque</span></button>
            <button class="ed-tool-btn"        data-tool="image"      title="Imagem"><i class="bi bi-image"></i><span>Imagem</span></button>
            <button class="ed-tool-btn"        data-tool="signature"  title="Assinatura"><i class="bi bi-pen"></i><span>Assinar</span></button>
            <button class="ed-tool-btn"        data-tool="erase"      title="Apagar (cobrir com branco)"><i class="bi bi-eraser"></i><span>Apagar</span></button>
            <input type="file" id="ed-image-input" class="d-none" accept="image/png,image/jpeg">
        </div>

        {{-- Canvas area --}}
        <div class="ed-canvas-area" id="ed-canvas-area">
            <div id="ed-pages-wrap" style="display:flex;flex-direction:column;align-items:center;gap:22px"></div>
        </div>

        {{-- Right properties panel --}}
        <div class="ed-props">
            <div class="ed-props-hdr"><i class="bi bi-sliders me-1"></i>Propriedades</div>
            <div class="ed-props-body" id="ed-props-body"></div>
        </div>
    </div>

    {{-- Thumbnails strip --}}
    <div class="ed-thumbs-wrap">
        <span class="ed-thumbs-label"><i class="bi bi-grid me-1"></i>Páginas</span>
        <div class="ed-thumbs" id="ed-thumbs"></div>
    </div>

</div>

{{-- ══════════════ SIGNATURE MODAL ══════════════ --}}
<div class="ed-sig-modal" id="ed-sig-modal">
    <div class="ed-sig-dlg">
        <h5><i class="bi bi-pen me-2"></i>Assinatura</h5>
        <p class="text-muted small mb-2">Desenhe a sua assinatura abaixo. Será inserida no documento como um traço vetorial.</p>
        <canvas id="ed-sig-canvas" width="900" height="300"></canvas>
        <div class="d-flex align-items-center gap-2 mt-2">
            <label class="small mb-0">Cor</label>
            <input type="color" id="ed-sig-color" value="#1f2937" class="form-control form-control-color form-control-sm">
            <label class="small mb-0">Espessura</label>
            <input type="range" id="ed-sig-width" min="1" max="6" step="0.5" value="2" class="form-range" style="max-width:120px">
            <button class="btn btn-outline-secondary btn-sm ms-2" id="ed-sig-clear"><i class="bi bi-x-lg"></i> Limpar</button>
        </div>
        <div class="text-end mt-3">
            <button class="btn btn-secondary btn-sm me-2" id="ed-sig-cancel">Cancelar</button>
            <button class="btn btn-danger btn-sm" id="ed-sig-apply">Inserir assinatura</button>
        </div>
    </div>
</div>

<div class="ed-toast" id="ed-toast"></div>

{{-- ══════════════ SEO ══════════════ --}}
<div class="bg-white py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <h2 class="fw-bold mb-3">Como editar um PDF online</h2>
                <ol class="mb-4">
                    <li class="mb-1">Carregue o seu PDF na zona de upload acima.</li>
                    <li class="mb-1">Selecione uma ferramenta na barra lateral esquerda: <strong>Texto, Editar, Forma, Destaque, Imagem, Assinatura, Apagar</strong>.</li>
                    <li class="mb-1">Clique ou arraste sobre a página para adicionar o item.</li>
                    <li class="mb-1">Mova, redimensione, rode e ajuste propriedades no painel à direita.</li>
                    <li class="mb-1">Quando estiver pronto, clique <strong>Guardar PDF</strong> para descarregar.</li>
                </ol>

                <h2 class="fw-bold mb-3">Funcionalidades</h2>
                <div class="row g-3 mb-4">
                    <div class="col-md-6"><div class="d-flex gap-3"><i class="bi bi-fonts fs-4 text-danger mt-1"></i><div><strong>Adicionar e editar texto</strong><br><span class="text-muted small">Crie caixas de texto ou substitua texto existente diretamente nas páginas.</span></div></div></div>
                    <div class="col-md-6"><div class="d-flex gap-3"><i class="bi bi-square fs-4 text-danger mt-1"></i><div><strong>Formas e destaque</strong><br><span class="text-muted small">Desenhe retângulos, destaques amarelos e máscaras brancas.</span></div></div></div>
                    <div class="col-md-6"><div class="d-flex gap-3"><i class="bi bi-image fs-4 text-danger mt-1"></i><div><strong>Inserir imagens</strong><br><span class="text-muted small">PNG ou JPEG embutidas no PDF final.</span></div></div></div>
                    <div class="col-md-6"><div class="d-flex gap-3"><i class="bi bi-pen fs-4 text-danger mt-1"></i><div><strong>Assinatura manuscrita</strong><br><span class="text-muted small">Desenhe a sua assinatura e arraste-a para onde quiser.</span></div></div></div>
                    <div class="col-md-6"><div class="d-flex gap-3"><i class="bi bi-arrow-counterclockwise fs-4 text-danger mt-1"></i><div><strong>Anular / Refazer</strong><br><span class="text-muted small">Use Ctrl+Z e Ctrl+Y para corrigir o que quiser.</span></div></div></div>
                    <div class="col-md-6"><div class="d-flex gap-3"><i class="bi bi-shield-lock fs-4 text-danger mt-1"></i><div><strong>100% privado</strong><br><span class="text-muted small">Os ficheiros nunca saem do seu dispositivo.</span></div></div></div>
                </div>

                <div class="alert alert-light border d-flex gap-3 align-items-start">
                    <i class="bi bi-info-circle-fill text-primary fs-5 mt-1"></i>
                    <div><strong>Privacidade garantida.</strong><br><span class="text-muted small">Toda a edição acontece localmente no seu navegador. Nenhum ficheiro é enviado para servidores.</span></div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';</script>
<script src="https://unpkg.com/pdf-lib@1.17.1/dist/pdf-lib.min.js"></script>
<script src="https://unpkg.com/@pdf-lib/fontkit@1.1.1/dist/fontkit.umd.min.js"></script>
<script src="{{ asset('js/tools/edit-pdf.js') }}"></script>
@endpush

@endsection
