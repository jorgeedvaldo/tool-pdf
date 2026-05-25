@extends('layouts.app')

@section('title', 'Markdown to PDF - Convert .md Files to PDF - ToolPDF')

@section('content')

<style>
:root {
    --md-color: #0d6efd;
    --md-dark:  #0a58ca;
}
.md-hero {
    background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
    color: #fff;
    padding: 44px 0 28px;
}
.md-hero h1 { font-size: 1.95rem; font-weight: 800; margin-bottom: .35rem; }
.md-badge {
    background: rgba(255,255,255,.18);
    border: 1px solid rgba(255,255,255,.35);
    font-size: .73rem; padding: .3em .65em;
    border-radius: 20px;
}
.md-drop-zone {
    border: 2.5px dashed #93c5fd !important;
    border-radius: 14px;
    cursor: pointer;
    transition: background .2s, border-color .2s;
    background: #eff6ff;
    min-height: 150px;
}
.md-drop-zone:hover, .md-drop-zone.drag-over {
    background: #dbeafe;
    border-color: #0d6efd !important;
}
.md-action-btn {
    background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
    border: none; color: #fff;
    font-size: 1.05rem; font-weight: 700;
    padding: 13px 40px;
    border-radius: 50px;
    box-shadow: 0 4px 16px rgba(13,110,253,.35);
    transition: opacity .2s, transform .1s;
}
.md-action-btn:hover:not(:disabled) { opacity: .92; transform: translateY(-1px); }
.md-action-btn:disabled { opacity: .4; cursor: not-allowed; }

/* Nav tabs override */
.md-tabs .nav-link { color: #495057; }
.md-tabs .nav-link.active { color: #0d6efd; font-weight: 600; border-bottom: 2px solid #0d6efd; background: transparent; }
.md-tabs .nav-link:hover:not(.active) { color: #0d6efd; }

/* Textarea */
#md-textarea {
    font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
    font-size: .88rem;
    resize: vertical;
    min-height: 340px;
    line-height: 1.6;
    background: #1e1e2e;
    color: #cdd6f4;
    border: 1px solid #313244;
    border-radius: 8px;
    padding: 16px;
}
#md-textarea:focus {
    outline: none;
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13,110,253,.15);
}
#md-textarea::placeholder { color: #6c7086; }

/* Live preview */
#md-preview-pane {
    min-height: 340px;
    max-height: 500px;
    overflow-y: auto;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #fff;
    padding: 24px 28px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif;
    font-size: .95rem;
    line-height: 1.7;
    color: #24292e;
}
/* GitHub-like Markdown preview */
#md-preview-pane h1,#md-preview-pane h2 { border-bottom: 1px solid #eaecef; padding-bottom: .3em; }
#md-preview-pane h1 { font-size:2em; } #md-preview-pane h2 { font-size:1.5em; }
#md-preview-pane h3 { font-size:1.25em; } #md-preview-pane h4 { font-size:1em; }
#md-preview-pane code {
    font-family: 'SFMono-Regular', Consolas, monospace;
    font-size: .875em;
    padding: .2em .4em;
    background: #f6f8fa; border-radius: 3px;
}
#md-preview-pane pre {
    background: #f6f8fa; border-radius: 6px;
    padding: 16px; overflow: auto;
    border: 1px solid #e1e4e8;
}
#md-preview-pane pre code { background: none; padding: 0; }
#md-preview-pane blockquote {
    padding: 0 1em; color: #6a737d;
    border-left: .25em solid #dfe2e5; margin: 0 0 16px;
}
#md-preview-pane table { border-collapse: collapse; width: 100%; margin-bottom: 16px; }
#md-preview-pane th, #md-preview-pane td { padding: 6px 13px; border: 1px solid #dfe2e5; }
#md-preview-pane tr:nth-child(even) { background: #f6f8fa; }
#md-preview-pane img { max-width: 100%; }
#md-preview-pane a { color: #0366d6; }
#md-preview-pane ul,#md-preview-pane ol { padding-left: 2em; }
#md-preview-pane hr { height: .25em; background-color: #e1e4e8; border: 0; }

/* Dark code theme */
.md-code-dark #md-preview-pane pre,
.md-code-dark #md-preview-pane code {
    background: #282c34;
    color: #abb2bf;
    border-color: #3e4451;
}

/* TOC styling in preview */
#md-toc-section { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px 20px; margin-bottom: 20px; }
#md-toc-section h4 { font-size: .9rem; font-weight: 700; color: #374151; margin-bottom: 8px; }
#md-toc-section ul { margin: 0; padding-left: 1.2em; }
#md-toc-section li a { color: #0d6efd; font-size: .88rem; text-decoration: none; }
#md-toc-section li a:hover { text-decoration: underline; }

/* Hidden div used for PDF rendering */
#md-render-div {
    position: fixed;
    top: -9999px;
    left: -9999px;
    width: 794px; /* A4 width at 96dpi */
    padding: 48px 60px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif;
    font-size: 14px;
    line-height: 1.7;
    color: #24292e;
    background: #fff;
    box-sizing: border-box;
    visibility: hidden;
    pointer-events: none;
}
</style>

<section class="md-hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-9">
                <h1><i class="bi bi-markdown me-2"></i>Markdown to PDF</h1>
                <p class="mb-3" style="opacity:.9;font-size:1rem">
                    Convert Markdown files or pasted text to beautiful PDF documents.
                    Supports GitHub-flavored Markdown, tables, code blocks and more.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <span class="md-badge">&#128221; Browser-side</span>
                    <span class="md-badge">GitHub-flavored Markdown</span>
                    <span class="md-badge">Live preview</span>
                    <span class="md-badge">No upload required</span>
                </div>
            </div>
            <div class="col-lg-3 text-lg-end mt-3 mt-lg-0">
                <i class="bi bi-file-earmark-richtext" style="font-size:4.5rem;opacity:.22"></i>
            </div>
        </div>
    </div>
</section>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-11">

            {{-- Hidden rendering div --}}
            <div id="md-render-div" aria-hidden="true"></div>

            <div class="row g-4">

                {{-- Left column: Input + Options --}}
                <div class="col-lg-6">
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-4">

                            {{-- Input tabs --}}
                            <ul class="nav nav-tabs md-tabs mb-3 border-0" id="md-input-tabs">
                                <li class="nav-item">
                                    <button class="nav-link active px-3 py-2 border-0 bg-transparent"
                                            id="tab-editor" data-tab="editor">
                                        <i class="bi bi-pencil me-1"></i>Editor
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link px-3 py-2 border-0 bg-transparent"
                                            id="tab-upload" data-tab="upload">
                                        <i class="bi bi-upload me-1"></i>Upload .md file
                                    </button>
                                </li>
                            </ul>

                            {{-- Editor tab --}}
                            <div id="md-pane-editor">
                                <textarea id="md-textarea"
                                    class="form-control w-100"
                                    spellcheck="false"
                                    placeholder="# My Document&#10;&#10;Start typing Markdown here…&#10;&#10;## Features&#10;- **Bold**, *italic*, `code`&#10;- Tables, blockquotes, code blocks&#10;- GitHub-flavored Markdown"
                                ></textarea>
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <small class="text-muted"><span id="md-char-count">0</span> characters</small>
                                    <button type="button" class="btn btn-link btn-sm p-0 text-muted" id="md-btn-clear">
                                        <i class="bi bi-trash me-1"></i>Clear
                                    </button>
                                </div>
                            </div>

                            {{-- Upload tab --}}
                            <div id="md-pane-upload" class="d-none">
                                <div id="md-drop-zone" class="md-drop-zone d-flex flex-column align-items-center justify-content-center text-center p-4">
                                    <i class="bi bi-file-earmark-text mb-2" style="font-size:2.8rem;color:#0d6efd"></i>
                                    <h6 class="fw-bold mb-1">Drop your .md or .txt file here</h6>
                                    <p class="text-muted small mb-3">or click to browse</p>
                                    <button type="button" class="btn btn-sm px-4" style="background:#0d6efd;color:#fff;border-radius:20px"
                                            onclick="document.getElementById('md-file-input').click()">
                                        <i class="bi bi-folder2-open me-1"></i> Choose File
                                    </button>
                                    <input type="file" id="md-file-input" class="d-none" accept=".md,.txt,.markdown">
                                </div>
                                <div id="md-file-loaded" class="d-none mt-3">
                                    <div class="d-flex align-items-center gap-2 p-2 bg-light rounded border">
                                        <i class="bi bi-file-earmark-text text-primary fs-5"></i>
                                        <span class="fw-semibold flex-grow-1 text-truncate" id="md-loaded-name"></span>
                                        <button class="btn btn-outline-secondary btn-sm rounded-circle px-2" id="md-btn-unload">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    {{-- Options Card --}}
                    <div class="card shadow-sm border-0 mt-3">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-3">PDF Options</h6>
                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Font Size</label>
                                    <select id="md-fontsize" class="form-select form-select-sm">
                                        <option value="10">10 pt</option>
                                        <option value="12" selected>12 pt</option>
                                        <option value="14">14 pt</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Page Format</label>
                                    <select id="md-pageformat" class="form-select form-select-sm">
                                        <option value="a4" selected>A4</option>
                                        <option value="letter">Letter</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Code Theme</label>
                                    <select id="md-codetheme" class="form-select form-select-sm">
                                        <option value="github">GitHub (light)</option>
                                        <option value="dark">Dark</option>
                                    </select>
                                </div>
                                <div class="col-6 d-flex align-items-end">
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox" id="md-toc">
                                        <label class="form-check-label small fw-semibold" for="md-toc">
                                            Table of Contents
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div id="md-error" class="alert alert-danger mt-3 d-none"></div>

                            {{-- Progress --}}
                            <div id="md-progress" class="mt-3 d-none">
                                <div class="d-flex justify-content-between mb-1 small">
                                    <span id="md-progress-msg" class="text-muted">Rendering…</span>
                                </div>
                                <div class="progress" style="height:8px;border-radius:6px">
                                    <div id="md-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated"
                                         style="width:0%;background:#0d6efd"></div>
                                </div>
                            </div>

                            <div class="text-center mt-4">
                                <button type="button" id="md-btn-convert" class="md-action-btn" disabled>
                                    <i class="bi bi-file-earmark-pdf me-2"></i>
                                    <span id="md-btn-text">Generate PDF</span>
                                    <span class="spinner-border spinner-border-sm d-none ms-2" id="md-spinner"></span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="text-center text-muted small mt-3">
                        <i class="bi bi-shield-check text-success me-1"></i>
                        All processing happens entirely in your browser. No files are sent to any server.
                    </div>
                </div>

                {{-- Right column: Live Preview --}}
                <div class="col-lg-6">
                    <div class="card shadow-sm border-0 h-100" style="min-height:500px">
                        <div class="card-header bg-white border-bottom py-2 px-4 d-flex align-items-center justify-content-between">
                            <span class="fw-semibold text-muted small">
                                <i class="bi bi-eye me-1"></i>Live Preview
                            </span>
                            <span class="badge" style="background:#e9ecef;color:#495057;font-size:.7rem">Rendered HTML</span>
                        </div>
                        <div class="card-body p-3" id="md-preview-container">
                            <div id="md-preview-pane">
                                <div class="text-center text-muted py-5">
                                    <i class="bi bi-markdown" style="font-size:3rem;opacity:.3"></i>
                                    <p class="mt-2 small">Start typing or upload a file to see the preview</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/marked@9.1.6/marked.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
(function () {
    'use strict';

    // Configure marked for GFM
    marked.setOptions({ gfm: true, breaks: true });

    let markdownText = '';
    let activeTab    = 'editor';

    const textarea    = document.getElementById('md-textarea');
    const charCount   = document.getElementById('md-char-count');
    const btnClear    = document.getElementById('md-btn-clear');
    const previewPane = document.getElementById('md-preview-pane');
    const renderDiv   = document.getElementById('md-render-div');
    const btnConvert  = document.getElementById('md-btn-convert');
    const btnText     = document.getElementById('md-btn-text');
    const spinner     = document.getElementById('md-spinner');
    const progress    = document.getElementById('md-progress');
    const progressBar = document.getElementById('md-progress-bar');
    const progressMsg = document.getElementById('md-progress-msg');
    const errBox      = document.getElementById('md-error');
    const codethemeSel= document.getElementById('md-codetheme');

    // Tab switching
    document.querySelectorAll('#md-input-tabs button').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('#md-input-tabs button').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            activeTab = btn.dataset.tab;
            document.getElementById('md-pane-editor').classList.toggle('d-none', activeTab !== 'editor');
            document.getElementById('md-pane-upload').classList.toggle('d-none', activeTab !== 'upload');
            refreshConvertBtn();
        });
    });

    // ---- Editor tab ----
    textarea.addEventListener('input', () => {
        markdownText = textarea.value;
        charCount.textContent = markdownText.length;
        updatePreview();
        refreshConvertBtn();
    });

    btnClear.addEventListener('click', () => {
        textarea.value = '';
        markdownText   = '';
        charCount.textContent = '0';
        updatePreview();
        refreshConvertBtn();
    });

    // ---- Upload tab ----
    const dropZone  = document.getElementById('md-drop-zone');
    const fileInput = document.getElementById('md-file-input');
    const fileLoaded= document.getElementById('md-file-loaded');
    const loadedName= document.getElementById('md-loaded-name');
    const btnUnload = document.getElementById('md-btn-unload');

    dropZone.addEventListener('click', () => fileInput.click());
    dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
    dropZone.addEventListener('drop', e => {
        e.preventDefault(); dropZone.classList.remove('drag-over');
        if (e.dataTransfer.files[0]) loadMdFile(e.dataTransfer.files[0]);
    });
    fileInput.addEventListener('change', e => { if (e.target.files[0]) loadMdFile(e.target.files[0]); });
    btnUnload.addEventListener('click', () => {
        fileInput.value = '';
        markdownText    = '';
        fileLoaded.classList.add('d-none');
        dropZone.classList.remove('d-none');
        updatePreview();
        refreshConvertBtn();
    });

    async function loadMdFile(file) {
        const allowed = ['.md', '.txt', '.markdown'];
        const ok = allowed.some(ext => file.name.toLowerCase().endsWith(ext));
        if (!ok) { showError('Please select a .md or .txt file.'); return; }
        errBox.classList.add('d-none');
        try {
            markdownText = await file.text();
            loadedName.textContent = file.name;
            dropZone.classList.add('d-none');
            fileLoaded.classList.remove('d-none');
            updatePreview();
            refreshConvertBtn();
        } catch (e) {
            showError('Could not read file: ' + e.message);
        }
    }

    // ---- Options listeners ----
    codethemeSel.addEventListener('change', () => {
        const container = document.getElementById('md-preview-container');
        container.classList.toggle('md-code-dark', codethemeSel.value === 'dark');
        updatePreview();
    });
    document.getElementById('md-toc').addEventListener('change', updatePreview);

    // ---- Helpers ----
    function showError(msg) {
        errBox.textContent = msg;
        errBox.classList.remove('d-none');
    }

    function refreshConvertBtn() {
        btnConvert.disabled = !markdownText.trim();
    }

    function buildTocHtml(mdText) {
        const headings = [];
        const lines = mdText.split('\n');
        lines.forEach(line => {
            const m = line.match(/^(#{1,6})\s+(.+)$/);
            if (m) headings.push({ level: m[1].length, text: m[2].trim() });
        });
        if (headings.length === 0) return '';
        let html = '<div id="md-toc-section"><h4>&#128218; Table of Contents</h4><ul>';
        headings.forEach(h => {
            const indent = (h.level - 1) * 12;
            const anchor = h.text.toLowerCase().replace(/[^\w\s-]/g, '').replace(/\s+/g, '-');
            html += `<li style="margin-left:${indent}px"><a href="#${anchor}">${h.text}</a></li>`;
        });
        html += '</ul></div>';
        return html;
    }

    function updatePreview() {
        if (!markdownText.trim()) {
            previewPane.innerHTML = '<div class="text-center text-muted py-5">' +
                '<i class="bi bi-markdown" style="font-size:3rem;opacity:.3"></i>' +
                '<p class="mt-2 small">Start typing or upload a file to see the preview</p></div>';
            return;
        }
        try {
            const showToc = document.getElementById('md-toc').checked;
            const toc     = showToc ? buildTocHtml(markdownText) : '';
            const body    = marked.parse(markdownText);
            previewPane.innerHTML = toc + body;
        } catch (e) {
            previewPane.innerHTML = '<div class="text-danger small p-3">Preview error: ' + e.message + '</div>';
        }
    }

    // ---- PDF Generation ----
    btnConvert.addEventListener('click', async () => {
        if (!markdownText.trim()) return;
        errBox.classList.add('d-none');
        btnConvert.disabled = true;
        spinner.classList.remove('d-none');
        btnText.textContent  = 'Generating…';
        progress.classList.remove('d-none');
        progressBar.style.width = '10%';
        progressMsg.textContent = 'Parsing Markdown…';

        try {
            const showToc  = document.getElementById('md-toc').checked;
            const pageFormat = document.getElementById('md-pageformat').value;
            const fontSize   = parseInt(document.getElementById('md-fontsize').value, 10);
            const isDarkCode = codethemeSel.value === 'dark';

            // Build HTML content
            const toc  = showToc ? buildTocHtml(markdownText) : '';
            const body = marked.parse(markdownText);

            // Font size adjustment via CSS variable on wrapper
            const fontPx = { 10: '13px', 12: '15px', 14: '17px' }[fontSize] || '15px';

            // Build the render div content with all styles inline
            const codeStyle = isDarkCode
                ? 'background:#282c34;color:#abb2bf;border-color:#3e4451;'
                : 'background:#f6f8fa;color:#24292e;border-color:#e1e4e8;';

            renderDiv.style.fontSize  = fontPx;
            renderDiv.style.visibility = 'hidden';
            renderDiv.style.width = pageFormat === 'letter' ? '816px' : '794px';

            renderDiv.innerHTML = `
                <style>
                    #md-render-div * { box-sizing: border-box; }
                    #md-render-div { font-size: ${fontPx}; }
                    #md-render-div h1,#md-render-div h2 { border-bottom:1px solid #eaecef;padding-bottom:.3em;margin-top:1.2em; }
                    #md-render-div h1{font-size:1.9em;}
                    #md-render-div h2{font-size:1.4em;}
                    #md-render-div h3{font-size:1.2em;}
                    #md-render-div code {
                        font-family:'SFMono-Regular',Consolas,monospace;font-size:.88em;
                        padding:.2em .4em;border-radius:3px;${codeStyle}
                    }
                    #md-render-div pre {
                        border-radius:6px;padding:16px;overflow:hidden;
                        border:1px solid;margin-bottom:16px;${codeStyle}
                    }
                    #md-render-div pre code{background:none;border:none;padding:0;color:inherit;}
                    #md-render-div blockquote{padding:0 1em;color:#6a737d;border-left:.25em solid #dfe2e5;margin:0 0 16px;}
                    #md-render-div table{border-collapse:collapse;width:100%;margin-bottom:16px;}
                    #md-render-div th,#md-render-div td{padding:6px 13px;border:1px solid #dfe2e5;}
                    #md-render-div tr:nth-child(even){background:#f6f8fa;}
                    #md-render-div img{max-width:100%;}
                    #md-render-div ul,#md-render-div ol{padding-left:2em;}
                    #md-render-div hr{height:.25em;background:#e1e4e8;border:0;}
                    #md-render-div p{margin-bottom:12px;}
                    #md-toc-section{background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:14px 18px;margin-bottom:20px;}
                    #md-toc-section h4{font-size:.9rem;font-weight:700;color:#374151;margin-bottom:8px;}
                    #md-toc-section ul{margin:0;padding-left:1.2em;}
                    #md-toc-section li a{color:#0d6efd;font-size:.88rem;text-decoration:none;}
                </style>
                ${toc}${body}
            `;

            progressBar.style.width = '25%';
            progressMsg.textContent = 'Rendering to canvas…';

            // Small delay to allow browser to lay out the div
            await new Promise(r => setTimeout(r, 80));

            const { jsPDF } = window.jspdf;
            const isLetter   = pageFormat === 'letter';
            const pdfW_mm    = isLetter ? 215.9 : 210;
            const pdfH_mm    = isLetter ? 279.4 : 297;
            const MARGIN_MM  = 15;
            const contentW_mm = pdfW_mm - MARGIN_MM * 2;
            const pxPerMm    = 96 / 25.4; // ~3.78 px/mm

            const doc = new jsPDF({
                orientation: 'portrait',
                unit: 'mm',
                format: pageFormat === 'letter' ? 'letter' : 'a4'
            });

            // Use html2canvas to capture the render div
            const canvas = await html2canvas(renderDiv, {
                scale: 2,
                useCORS: true,
                logging: false,
                backgroundColor: '#ffffff',
                windowWidth: renderDiv.offsetWidth,
                width: renderDiv.offsetWidth,
            });

            progressBar.style.width = '75%';
            progressMsg.textContent = 'Building PDF pages…';

            // The canvas represents the full document; split into pages
            const canvasWidthPx  = canvas.width;
            const canvasHeightPx = canvas.height;

            // How tall is one PDF page in canvas pixels?
            const contentW_px   = contentW_mm * pxPerMm * 2; // *2 for scale:2
            const scaleFactor   = canvasWidthPx / contentW_px;
            const pageH_mm      = pdfH_mm - MARGIN_MM * 2;
            const pageH_px      = pageH_mm * pxPerMm * 2 * scaleFactor;

            const totalPages = Math.ceil(canvasHeightPx / pageH_px);

            for (let pg = 0; pg < totalPages; pg++) {
                if (pg > 0) doc.addPage(pageFormat === 'letter' ? 'letter' : 'a4', 'portrait');

                const srcY = Math.round(pg * pageH_px);
                const srcH = Math.min(pageH_px, canvasHeightPx - srcY);

                // Slice the canvas for this page
                const pageCanvas = document.createElement('canvas');
                pageCanvas.width  = canvasWidthPx;
                pageCanvas.height = Math.round(srcH);
                const pCtx = pageCanvas.getContext('2d');
                pCtx.fillStyle = '#ffffff';
                pCtx.fillRect(0, 0, pageCanvas.width, pageCanvas.height);
                pCtx.drawImage(canvas, 0, srcY, canvasWidthPx, srcH, 0, 0, canvasWidthPx, Math.round(srcH));

                const imgData    = pageCanvas.toDataURL('image/jpeg', 0.95);
                const imgH_mm    = (srcH / canvasHeightPx) * (canvasHeightPx / pageH_px) * pageH_mm;

                doc.addImage(imgData, 'JPEG', MARGIN_MM, MARGIN_MM, contentW_mm, imgH_mm);

                progressBar.style.width = Math.round(75 + (pg / totalPages) * 22) + '%';
            }

            // Clean up render div
            renderDiv.innerHTML = '';
            renderDiv.style.visibility = 'hidden';

            progressBar.style.width = '100%';
            progressMsg.textContent = 'Saving…';

            doc.save('document.pdf');

            setTimeout(() => {
                progress.classList.add('d-none');
                btnText.textContent = 'Generate PDF';
            }, 1600);

        } catch (e) {
            console.error(e);
            renderDiv.innerHTML = '';
            showError('Error generating PDF: ' + e.message);
        } finally {
            btnConvert.disabled = !markdownText.trim();
            spinner.classList.add('d-none');
            btnText.textContent  = 'Generate PDF';
        }
    });

    // Sample content on load
    const SAMPLE = `# Welcome to Markdown to PDF

Convert any **Markdown** document to a polished PDF right in your browser.

## Features

- GitHub-flavored Markdown
- Live HTML preview
- Code syntax highlighting
- Tables, blockquotes & more
- Optional Table of Contents

## Code Example

\`\`\`javascript
function greet(name) {
  return \`Hello, \${name}!\`;
}
console.log(greet('World'));
\`\`\`

## Table Example

| Tool | Format | Browser-side |
|------|--------|:---:|
| N-Up PDF | PDF | ✅ |
| Extract Images | PNG/ZIP | ✅ |
| Markdown to PDF | PDF | ✅ |

> **Tip:** Use the options panel on the left to choose font size, page format and code theme.
`;

    textarea.value = SAMPLE;
    markdownText   = SAMPLE;
    charCount.textContent = SAMPLE.length;
    updatePreview();
    refreshConvertBtn();

})();
</script>
@endpush

@endsection
