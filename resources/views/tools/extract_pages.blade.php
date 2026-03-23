@extends('layouts.app')

@section('title', __('messages.extract_pages') . ' - ToolPDF')

@section('content')
<style>
    .thumbnail-container { position: relative; transition: transform 0.2s; cursor: pointer; }
    .thumbnail-container:hover { transform: scale(1.02); z-index: 10; boxShadow: 0 0 10px rgba(0,0,0,0.2); }
    .page-number-badge {
        position: absolute;
        top: 5px;
        left: 5px;
        background: rgba(0, 0, 0, 0.7);
        color: white;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 14px;
        pointer-events: none;
        z-index: 5;
    }
    .image-preview {
        width: 100%;
        height: 200px;
        object-fit: contain;
        background-color: #f8f9fa;
        border-radius: 4px;
        transition: all 0.2s ease;
    }
    /* Removal Selection State */
    .card-selection { transition: all 0.2s ease; border: 2px solid transparent; }
    .marked-for-extraction .card-selection {
        border-color: #0d6efd;
        background-color: #cfe2ff !important;
    }
    .marked-for-extraction .image-preview {
        opacity: 0.8;
    }
    .selection-icon {
        position: absolute;
        top: 5px;
        right: 5px;
        font-size: 1.5rem;
        color: #0d6efd;
        display: none;
        z-index: 10;
        pointer-events: none;
        background: white;
        border-radius: 50%;
        width: 25px;
        height: 25px;
        align-items: center;
        justify-content: center;
    }
    .marked-for-extraction .selection-icon {
        display: flex;
    }
</style>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow border-0">
                <div class="card-header bg-primary text-white py-3">
                    <h4 class="mb-0 fw-bold"><i class="bi bi-file-earmark-break me-2"></i>{{ __('messages.extract_pages') }}</h4>
                </div>
                
                <div class="card-body p-4 bg-light">
                    <!-- Setup Area -->
                    <div id="drop-zone" class="drop-zone border rounded p-5 text-center bg-white shadow-sm mb-4" style="border: 2px dashed #0d6efd !important; cursor: pointer; transition: all 0.3s;">
                        <i class="bi bi-file-earmark-break mb-3 text-primary" style="font-size: 4rem;"></i>
                        <h5 class="fw-bold">{{ __('messages.extract_pages') }}</h5>
                        <p class="text-muted">Click or drag & drop a PDF file here</p>
                        <input type="file" id="file-input" class="d-none" accept=".pdf">
                    </div>

                    <!-- Processing/Convert Area -->
                    <div id="convert-area" class="d-none">
                        
                        <div class="d-flex justify-content-between align-items-center mb-4 p-3 bg-white border rounded shadow-sm">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-file-earmark-pdf fs-3 text-danger me-3"></i>
                                <div>
                                    <h6 class="mb-0 fw-bold text-truncate" style="max-width: 250px;" id="file-name">filename.pdf</h6>
                                    <small class="text-muted"><span id="page-count">0</span> Total | <span id="extracted-count" class="text-primary fw-bold">0</span> to Extract</small>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-danger btn-sm rounded-circle px-2" id="btn-remove" title="Remove PDF">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>

                        <!-- Progress Bar (Visible during loading) -->
                        <div id="progress-container" class="mb-4 d-none">
                            <div class="d-flex justify-content-between mb-1">
                                <small class="text-muted" id="progress-text">Loading pages...</small>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div id="progress-bar" class="progress-bar bg-primary progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%;"></div>
                            </div>
                        </div>

                        <div class="bg-white p-4 border rounded shadow-sm mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold mb-0">Click pages to mark them for extraction</h6>
                                <button class="btn btn-sm btn-outline-primary" id="btn-clear-selection"><i class="bi bi-eraser"></i> Clear Selection</button>
                            </div>
                            <div id="pages-grid" class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-4">
                                <!-- Thumbnails will be injected here -->
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="text-center">
                            <button type="button" id="btn-action" class="btn btn-lg px-5 shadow fw-bold text-white btn-primary">
                                <i class="bi bi-box-arrow-up-right me-2" id="btn-icon"></i> 
                                <span id="btn-text">{{ __('messages.extract_selected') }}</span>
                                <span class="spinner-border spinner-border-sm d-none ms-2" role="status" id="btn-spinner"></span>
                            </button>
                        </div>
                        
                        <div id="error-message" class="alert alert-danger mt-3 d-none"></div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts required -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
<script>pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';</script>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <!-- SEO Article -->
            <div class="card shadow-sm border-0 bg-white">
                <div class="card-body p-4 p-md-5 text-start">
                    {!! __('messages.article_extract_pages_content') !!}
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://unpkg.com/pdf-lib@1.17.1/dist/pdf-lib.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const dropZone = document.getElementById('drop-zone');
        const fileInput = document.getElementById('file-input');
        const convertArea = document.getElementById('convert-area');
        const fileNameDiv = document.getElementById('file-name');
        const pageCountSpan = document.getElementById('page-count');
        const extractedCountSpan = document.getElementById('extracted-count');
        const btnRemove = document.getElementById('btn-remove');
        const btnAction = document.getElementById('btn-action');
        const btnSpinner = document.getElementById('btn-spinner');
        const errorMessage = document.getElementById('error-message');
        const pagesGrid = document.getElementById('pages-grid');
        const progressContainer = document.getElementById('progress-container');
        const btnClearSelection = document.getElementById('btn-clear-selection');

        let selectedFile = null;
        let pdfjsDoc = null;
        let pagesToExtract = new Set(); // Stores 1-indexed page numbers

        dropZone.addEventListener('click', () => fileInput.click());
        dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.style.borderColor = '#0a58ca'; });
        dropZone.addEventListener('dragleave', (e) => { e.preventDefault(); dropZone.style.borderColor = '#0d6efd'; });
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.style.borderColor = '#0d6efd';
            if (e.dataTransfer.files.length > 0) handleFile(e.dataTransfer.files[0]);
        });
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) handleFile(e.target.files[0]);
        });
        
        btnRemove.addEventListener('click', resetUI);

        function resetUI() {
            selectedFile = null;
            pdfjsDoc = null;
            pagesToExtract.clear();
            fileInput.value = '';
            pagesGrid.innerHTML = '';
            convertArea.classList.add('d-none');
            dropZone.classList.remove('d-none');
            errorMessage.classList.add('d-none');
            progressContainer.classList.add('d-none');
        }

        async function handleFile(file) {
            errorMessage.classList.add('d-none');
            if (file.type !== 'application/pdf') {
                showError('Please select a valid PDF file.');
                return;
            }

            try {
                selectedFile = file;
                fileNameDiv.textContent = file.name;
                dropZone.classList.add('d-none');
                convertArea.classList.remove('d-none');
                btnAction.disabled = true;
                progressContainer.classList.remove('d-none');
                pagesGrid.innerHTML = '';
                pagesToExtract.clear();
                updateCounter();

                const arrayBuffer = await file.arrayBuffer();
                pdfjsDoc = await pdfjsLib.getDocument(new Uint8Array(arrayBuffer)).promise;
                
                const totalPages = pdfjsDoc.numPages;
                pageCountSpan.textContent = totalPages;

                // Render all pages
                for (let i = 1; i <= totalPages; i++) {
                    await renderPageThumbnail(i);
                }

                progressContainer.classList.add('d-none');
                btnAction.disabled = true;

            } catch (err) {
                console.error(err);
                showError('Could not process this PDF: ' + err.message);
                resetUI();
            }
        }

        async function renderPageThumbnail(pageNum) {
            const page = await pdfjsDoc.getPage(pageNum);
            const viewport = page.getViewport({ scale: 1.0 }); 
            
            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d');
            canvas.height = viewport.height;
            canvas.width = viewport.width;

            await page.render({ canvasContext: context, viewport: viewport }).promise;
            const dataUrl = canvas.toDataURL('image/jpeg', 0.8);
            
            const colDiv = document.createElement('div');
            colDiv.className = 'col thumbnail-container';
            colDiv.dataset.page = pageNum;
            colDiv.onclick = () => toggleSelection(colDiv, pageNum);

            const card = document.createElement('div');
            card.className = 'card h-100 shadow-sm p-2 card-selection';

            const numBadge = document.createElement('div');
            numBadge.className = 'page-number-badge';
            numBadge.textContent = pageNum;

            const selectionIcon = document.createElement('i');
            selectionIcon.className = 'bi bi-check-circle-fill selection-icon shadow-sm';

            const img = document.createElement('img');
            img.src = dataUrl;
            img.className = 'image-preview border';

            card.appendChild(numBadge);
            card.appendChild(img);
            card.appendChild(selectionIcon);
            colDiv.appendChild(card);
            
            pagesGrid.appendChild(colDiv);
        }

        function toggleSelection(element, pageNum) {
            if (pagesToExtract.has(pageNum)) {
                pagesToExtract.delete(pageNum);
                element.classList.remove('marked-for-extraction');
            } else {
                pagesToExtract.add(pageNum);
                element.classList.add('marked-for-extraction');
            }
            updateCounter();
        }

        function updateCounter() {
            extractedCountSpan.textContent = pagesToExtract.size;
            btnAction.disabled = pagesToExtract.size === 0 || pdfjsDoc === null;
        }

        btnClearSelection.onclick = () => {
             pagesToExtract.clear();
             document.querySelectorAll('.thumbnail-container').forEach(el => el.classList.remove('marked-for-extraction'));
             updateCounter();
        };

        function showError(msg) {
            errorMessage.textContent = msg;
            errorMessage.classList.remove('d-none');
        }

        btnAction.addEventListener('click', async () => {
            if (!selectedFile || pagesToExtract.size === 0) return;
            errorMessage.classList.add('d-none');
            
            try {
                btnAction.disabled = true;
                btnSpinner.classList.remove('d-none');
                
                const arrayBuffer = await selectedFile.arrayBuffer();
                const { PDFDocument } = PDFLib;
                const originalDoc = await PDFDocument.load(arrayBuffer);
                const newDoc = await PDFDocument.create();
                
                // Keep the pages in numeric order
                const pagesToExt = Array.from(pagesToExtract).sort((a,b) => a - b);
                
                // Copy pages (0-indexed)
                const indices = pagesToExt.map(n => n - 1);
                const copiedPages = await newDoc.copyPages(originalDoc, indices);
                
                copiedPages.forEach((page) => {
                    newDoc.addPage(page);
                });

                const pdfBytes = await newDoc.save();
                
                const blob = new Blob([pdfBytes], { type: 'application/pdf' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                const originalName = selectedFile.name.replace('.pdf', '');
                a.download = `${originalName}_extracted.pdf`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
                
            } catch (err) {
                console.error(err);
                showError('Error processing PDF: ' + err.message);
            } finally {
                btnAction.disabled = false;
                btnSpinner.classList.add('d-none');
            }
        });
    });
</script>
@endsection
