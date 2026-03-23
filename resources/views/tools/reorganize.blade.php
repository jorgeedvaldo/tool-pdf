@extends('layouts.app')

@section('title', __('messages.reorganize_pages') . ' - ToolPDF')

@section('content')
<style>
    .sortable-ghost { opacity: 0.4; }
    .thumbnail-container { cursor: grab; position: relative; transition: transform 0.2s; }
    .thumbnail-container:active { cursor: grabbing; transform: scale(1.05); z-index: 10; shadow: 0 0 10px rgba(0,0,0,0.5); }
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
    }
    .delete-btn {
        position: absolute;
        top: 5px;
        right: 5px;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 5;
    }
    .thumbnail-container:hover .delete-btn {
        display: flex;
    }
</style>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow border-0">
                <div class="card-header bg-purple text-white py-3">
                    <h4 class="mb-0"><i class="bi bi-shuffle me-2"></i>{{ __('messages.reorganize_pages') }}</h4>
                </div>
                
                <div class="card-body p-4 bg-light">
                    <!-- Setup Area -->
                    <div id="drop-zone" class="drop-zone border rounded p-5 text-center bg-white shadow-sm mb-4" style="border: 2px dashed #9c27b0 !important; cursor: pointer; transition: all 0.3s;">
                        <i class="bi bi-file-earmark-pdf text-purple mb-3" style="font-size: 4rem;"></i>
                        <h5 class="fw-bold">{{ __('messages.reorganize_pages') }}</h5>
                        <p class="text-muted">Click or drag & drop a PDF file here</p>
                        <input type="file" id="file-input" class="d-none" accept=".pdf">
                    </div>

                    <!-- Processing/Reorganize Area -->
                    <div id="reorganize-area" class="d-none">
                        
                        <div class="d-flex justify-content-between align-items-center mb-3 p-3 bg-white border rounded shadow-sm">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-file-earmark-pdf fs-3 text-danger me-3"></i>
                                <div>
                                    <h6 class="mb-0 fw-bold text-truncate" style="max-width: 250px;" id="file-name">filename.pdf</h6>
                                    <small class="text-muted" id="page-count">0 Pages</small>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-danger btn-sm rounded-circle px-2" id="btn-remove" title="Remove PDF">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>

                        <div id="loading-spinner" class="text-center py-5 d-none">
                            <div class="spinner-border text-purple" role="status">
                                <span class="visually-hidden">Loading thumbnails...</span>
                            </div>
                            <p class="mt-3 text-muted">Rendering pages...</p>
                        </div>

                        <div class="bg-white p-4 border rounded shadow-sm mb-4">
                            <h6 class="fw-bold mb-3"><i class="bi bi-arrows-move me-2"></i>Drag and drop to reorder pages</h6>
                            <div id="pages-grid" class="row row-cols-3 row-cols-md-4 row-cols-lg-6 g-3">
                                <!-- Thumbnails will be injected here -->
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="text-center">
                            <button type="button" id="btn-action" class="btn btn-purple btn-lg px-5 text-white shadow" style="background-color: #9c27b0; border-color: #9c27b0;">
                                <i class="bi bi-shuffle me-2" id="btn-icon"></i> 
                                <span id="btn-text">{{ __('messages.reorganize_pages') }}</span>
                                <span class="spinner-border spinner-border-sm d-none ms-2" role="status" id="btn-spinner"></span>
                            </button>
                        </div>
                        
                        <div id="error-message" class="alert alert-danger mt-3 d-none"></div>
                    </div>

                </div>
            </div>
            
            <div class="mt-4 text-center text-muted col-8 mx-auto">
                <p class="small"><i class="bi bi-shield-check text-success me-1"></i> Privacy guaranteed. Document processing happens entirely within your web browser. No files are uploaded to our servers.</p>
            </div>
            
            <!-- SEO Article -->
            <div class="mt-5 card shadow-sm border-0 bg-white">
                <div class="card-body p-4 p-md-5 text-start">
                    {!! __('messages.article_reorganize_content') !!}
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Scripts required -->
<script src="https://unpkg.com/pdf-lib@1.17.1/dist/pdf-lib.min.js"></script>
<!-- PDF.js for rendering canvas thumbnails -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
<script>pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';</script>
<!-- Sortable JS for drag and drop -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const dropZone = document.getElementById('drop-zone');
        const fileInput = document.getElementById('file-input');
        const reorganizeArea = document.getElementById('reorganize-area');
        const fileNameDiv = document.getElementById('file-name');
        const pageCountDiv = document.getElementById('page-count');
        const btnRemove = document.getElementById('btn-remove');
        const btnAction = document.getElementById('btn-action');
        const btnSpinner = document.getElementById('btn-spinner');
        const errorMessage = document.getElementById('error-message');
        const pagesGrid = document.getElementById('pages-grid');
        const loadingSpinner = document.getElementById('loading-spinner');
        
        let selectedFile = null;
        let pdfDocCache = null; // Used for pdf-lib saving later
        let sortableInstance = null;

        // Drag and Drop Logic
        dropZone.addEventListener('click', () => fileInput.click());

        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('border-primary');
        });

        dropZone.addEventListener('dragleave', (e) => {
            e.preventDefault();
            dropZone.classList.remove('border-primary');
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('border-primary');
            if (e.dataTransfer.files.length > 0) {
                handleFile(e.dataTransfer.files[0]);
            }
        });

        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                handleFile(e.target.files[0]);
            }
        });
        
        btnRemove.addEventListener('click', () => {
            resetUI();
        });

        function resetUI() {
            selectedFile = null;
            pdfDocCache = null;
            if (sortableInstance) {
                sortableInstance.destroy();
                sortableInstance = null;
            }
            fileInput.value = '';
            pagesGrid.innerHTML = '';
            reorganizeArea.classList.add('d-none');
            dropZone.classList.remove('d-none');
            errorMessage.classList.add('d-none');
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
                reorganizeArea.classList.remove('d-none');
                pagesGrid.classList.add('d-none');
                loadingSpinner.classList.remove('d-none');
                btnAction.disabled = true;

                // 1. Load with pdf-lib for final manipulation
                const arrayBuffer = await file.arrayBuffer();
                const { PDFDocument } = PDFLib;
                pdfDocCache = await PDFDocument.load(arrayBuffer);
                const pageCount = pdfDocCache.getPageCount();
                pageCountDiv.textContent = pageCount + ' Pages';

                // 2. Load with PDF.js for rendering thumbnails
                const pdfjsDoc = await pdfjsLib.getDocument(new Uint8Array(arrayBuffer)).promise;
                
                pagesGrid.innerHTML = ''; // clear

                // 3. Render each page
                for (let i = 1; i <= pageCount; i++) {
                    const page = await pdfjsDoc.getPage(i);
                    // Scale it down significantly for thumbnail
                    const viewport = page.getViewport({ scale: 0.3 }); 
                    
                    const canvas = document.createElement('canvas');
                    const context = canvas.getContext('2d');
                    canvas.height = viewport.height;
                    canvas.width = viewport.width;
                    canvas.className = 'img-thumbnail shadow-sm bg-white';
                    canvas.style.width = '100%';
                    canvas.style.height = 'auto';

                    await page.render({
                        canvasContext: context,
                        viewport: viewport
                    }).promise;
                    
                    // Create wrapper
                    const colDiv = document.createElement('div');
                    colDiv.className = 'col thumbnail-container';
                    colDiv.dataset.pageIndex = (i - 1); // 0-indexed for pdf-lib

                    const numBadge = document.createElement('div');
                    numBadge.className = 'page-number-badge';
                    numBadge.textContent = i;
                    
                    const deleteBtn = document.createElement('button');
                    deleteBtn.className = 'btn btn-danger btn-sm p-0 delete-btn';
                    deleteBtn.innerHTML = '<i class="bi bi-trash"></i>';
                    deleteBtn.onclick = function(e) {
                        e.stopPropagation();
                        colDiv.remove();
                        updateMissingPages();
                    };

                    colDiv.appendChild(canvas);
                    colDiv.appendChild(numBadge);
                    colDiv.appendChild(deleteBtn);
                    
                    pagesGrid.appendChild(colDiv);
                }

                loadingSpinner.classList.add('d-none');
                pagesGrid.classList.remove('d-none');
                btnAction.disabled = false;

                // 4. Initialize SortableJS
                sortableInstance = new Sortable(pagesGrid, {
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    onEnd: function (evt) {
                        updateMissingPages(); // Update numbers on reorder
                    }
                });

            } catch (err) {
                console.error(err);
                showError('Could not process this PDF: ' + err.message);
                resetUI();
            }
        }
        
        function updateMissingPages() {
            // Update the visually displayed page numbers after reordering/deleting
            const labels = pagesGrid.querySelectorAll('.page-number-badge');
            labels.forEach((label, idx) => {
                label.textContent = idx + 1;
            });
            pageCountDiv.textContent = labels.length + ' Pages';
        }

        function showError(msg) {
            errorMessage.textContent = msg;
            errorMessage.classList.remove('d-none');
        }

        btnAction.addEventListener('click', async () => {
            if (!pdfDocCache) return;
            errorMessage.classList.add('d-none');
            
            try {
                btnAction.disabled = true;
                btnSpinner.classList.remove('d-none');
                
                // Get the new order array
                const newOrderElements = Array.from(pagesGrid.children);
                if(newOrderElements.length === 0) {
                     showError('Cannot create an empty PDF. Please reset and try again.');
                     return;
                }
                
                const newIndices = newOrderElements.map(el => parseInt(el.dataset.pageIndex));
                
                // Construct new PDF
                const { PDFDocument } = PDFLib;
                const newPdf = await PDFDocument.create();
                
                // Copy pages in the new order
                const copiedPages = await newPdf.copyPages(pdfDocCache, newIndices);
                copiedPages.forEach(page => newPdf.addPage(page));

                const pdfBytes = await newPdf.save();
                
                // Trigger download
                const blob = new Blob([pdfBytes], { type: 'application/pdf' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                const originalName = selectedFile.name.replace('.pdf', '');
                a.download = `${originalName}_reorganized.pdf`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
                
                // Reset form on success
                setTimeout(resetUI, 1000);
                
            } catch (err) {
                console.error(err);
                showError('Error during PDF reorganization: ' + err.message);
            } finally {
                btnAction.disabled = false;
                btnSpinner.classList.add('d-none');
            }
        });
    });
</script>
@endsection
