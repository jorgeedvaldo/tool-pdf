@extends('layouts.app')

@section('title', __('messages.rotate_pages') . ' - ToolPDF')

@section('content')
<style>
    .thumbnail-container { position: relative; transition: transform 0.2s; }
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
        transition: transform 0.3s ease;
    }
    .rotate-controls {
        display: flex;
        justify-content: space-between;
        margin-top: 5px;
    }
</style>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow border-0">
                <div class="card-header bg-orange text-white py-3" style="background-color: #fd7e14;">
                    <h4 class="mb-0 fw-bold"><i class="bi bi-arrow-clockwise me-2"></i>{{ __('messages.rotate_pages') }}</h4>
                </div>
                
                <div class="card-body p-4 bg-light">
                    <!-- Setup Area -->
                    <div id="drop-zone" class="drop-zone border rounded p-5 text-center bg-white shadow-sm mb-4" style="border: 2px dashed #fd7e14 !important; cursor: pointer; transition: all 0.3s;">
                        <i class="bi bi-arrow-clockwise mb-3" style="font-size: 4rem; color: #fd7e14;"></i>
                        <h5 class="fw-bold">{{ __('messages.rotate_pages') }}</h5>
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
                                    <small class="text-muted" id="page-count">0 Pages</small>
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
                                <div id="progress-bar" class="progress-bar bg-orange progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%; background-color: #fd7e14;"></div>
                            </div>
                        </div>

                        <div class="bg-white p-4 border rounded shadow-sm mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold mb-0">Select pages to rotate</h6>
                                <div>
                                    <button class="btn btn-sm btn-outline-secondary" id="btn-rotate-all-left"><i class="bi bi-arrow-counterclockwise"></i> All Left</button>
                                    <button class="btn btn-sm btn-outline-secondary" id="btn-rotate-all-right"><i class="bi bi-arrow-clockwise"></i> All Right</button>
                                </div>
                            </div>
                            <div id="pages-grid" class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-4">
                                <!-- Thumbnails will be injected here -->
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="text-center">
                            <button type="button" id="btn-action" class="btn btn-lg px-5 shadow fw-bold text-white" style="background-color: #fd7e14;">
                                <i class="bi bi-save me-2" id="btn-icon"></i> 
                                <span id="btn-text">Save Changes</span>
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
        </div>
    </div>
</div>

<!-- Scripts required -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
<script>pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';</script>
<script src="https://unpkg.com/pdf-lib@1.17.1/dist/pdf-lib.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const dropZone = document.getElementById('drop-zone');
        const fileInput = document.getElementById('file-input');
        const convertArea = document.getElementById('convert-area');
        const fileNameDiv = document.getElementById('file-name');
        const pageCountDiv = document.getElementById('page-count');
        const btnRemove = document.getElementById('btn-remove');
        const btnAction = document.getElementById('btn-action');
        const btnSpinner = document.getElementById('btn-spinner');
        const errorMessage = document.getElementById('error-message');
        const pagesGrid = document.getElementById('pages-grid');
        const progressContainer = document.getElementById('progress-container');
        
        const btnAllLeft = document.getElementById('btn-rotate-all-left');
        const btnAllRight = document.getElementById('btn-rotate-all-right');

        let selectedFile = null;
        let pdfjsDoc = null;
        let pageRotations = []; // Array tracking rotation degrees per page (1-indexed matching pdf.js)

        // Drag and Drop Logic
        dropZone.addEventListener('click', () => fileInput.click());
        dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.style.borderColor = '#0d6efd'; });
        dropZone.addEventListener('dragleave', (e) => { e.preventDefault(); dropZone.style.borderColor = '#fd7e14'; });
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.style.borderColor = '#fd7e14';
            if (e.dataTransfer.files.length > 0) handleFile(e.dataTransfer.files[0]);
        });
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) handleFile(e.target.files[0]);
        });
        
        btnRemove.addEventListener('click', resetUI);

        function resetUI() {
            selectedFile = null;
            pdfjsDoc = null;
            pageRotations = [];
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

                const arrayBuffer = await file.arrayBuffer();
                pdfjsDoc = await pdfjsLib.getDocument(new Uint8Array(arrayBuffer)).promise;
                
                const totalPages = pdfjsDoc.numPages;
                pageCountDiv.textContent = totalPages + ' Pages';
                
                // Init rotations to 0
                pageRotations = new Array(totalPages + 1).fill(0);

                // Render all pages
                for (let i = 1; i <= totalPages; i++) {
                    await renderPageThumbnail(i);
                }

                progressContainer.classList.add('d-none');
                btnAction.disabled = false;

            } catch (err) {
                console.error(err);
                showError('Could not process this PDF: ' + err.message);
                resetUI();
            }
        }

        async function renderPageThumbnail(pageNum) {
            const page = await pdfjsDoc.getPage(pageNum);
            const viewport = page.getViewport({ scale: 1.0 }); // Lower scale for thumbnails
            
            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d');
            canvas.height = viewport.height;
            canvas.width = viewport.width;

            await page.render({ canvasContext: context, viewport: viewport }).promise;
            
            const dataUrl = canvas.toDataURL('image/jpeg', 0.8);
            
            // Build UI
            const colDiv = document.createElement('div');
            colDiv.className = 'col thumbnail-container';
            colDiv.dataset.page = pageNum;

            const card = document.createElement('div');
            card.className = 'card h-100 shadow-sm border p-2';

            const numBadge = document.createElement('div');
            numBadge.className = 'page-number-badge';
            numBadge.textContent = pageNum;

            const img = document.createElement('img');
            img.src = dataUrl;
            img.className = 'image-preview border';
            img.id = `img-preview-${pageNum}`;

            const controlsWrap = document.createElement('div');
            controlsWrap.className = 'rotate-controls';
            
            const btnLeft = document.createElement('button');
            btnLeft.className = 'btn btn-sm btn-outline-secondary flex-fill me-1';
            btnLeft.innerHTML = '<i class="bi bi-arrow-counterclockwise"></i>';
            btnLeft.onclick = () => updateRotation(pageNum, -90);

            const btnRight = document.createElement('button');
            btnRight.className = 'btn btn-sm btn-outline-secondary flex-fill ms-1';
            btnRight.innerHTML = '<i class="bi bi-arrow-clockwise"></i>';
            btnRight.onclick = () => updateRotation(pageNum, 90);

            controlsWrap.appendChild(btnLeft);
            controlsWrap.appendChild(btnRight);

            card.appendChild(numBadge);
            card.appendChild(img);
            card.appendChild(controlsWrap);
            colDiv.appendChild(card);
            
            pagesGrid.appendChild(colDiv);
        }

        function updateRotation(pageNum, angleDelta) {
            let current = pageRotations[pageNum];
            current = (current + angleDelta) % 360;
            if (current < 0) current += 360;
            pageRotations[pageNum] = current;
            
            const img = document.getElementById(`img-preview-${pageNum}`);
            if (img) {
                img.style.transform = `rotate(${current}deg)`;
            }
        }
        
        btnAllLeft.onclick = () => {
             for(let i=1; i<pageRotations.length; i++) updateRotation(i, -90);
        };
        
        btnAllRight.onclick = () => {
             for(let i=1; i<pageRotations.length; i++) updateRotation(i, 90);
        };

        function showError(msg) {
            errorMessage.textContent = msg;
            errorMessage.classList.remove('d-none');
        }

        btnAction.addEventListener('click', async () => {
            if (!selectedFile) return;
            errorMessage.classList.add('d-none');
            
            try {
                btnAction.disabled = true;
                btnSpinner.classList.remove('d-none');
                
                const arrayBuffer = await selectedFile.arrayBuffer();
                const { PDFDocument, degrees } = PDFLib;
                const pdfDoc = await PDFDocument.load(arrayBuffer);
                const pages = pdfDoc.getPages();
                
                // Apply rotations
                for (let i = 0; i < pages.length; i++) {
                    const pageNum = i + 1;
                    const additionalRotation = pageRotations[pageNum];
                    
                    if (additionalRotation !== 0) {
                        const currentRotation = pages[i].getRotation().angle;
                        pages[i].setRotation(degrees(currentRotation + additionalRotation));
                    }
                }

                const pdfBytes = await pdfDoc.save();
                
                // Trigger download
                const blob = new Blob([pdfBytes], { type: 'application/pdf' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                const originalName = selectedFile.name.replace('.pdf', '');
                a.download = `${originalName}_rotated.pdf`;
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
