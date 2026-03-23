@extends('layouts.app')

@section('title', __('messages.images_to_pdf') . ' - ToolPDF')

@section('content')
<style>
    .sortable-ghost { opacity: 0.4; }
    .thumbnail-container { cursor: grab; position: relative; transition: transform 0.2s; }
    .thumbnail-container:active { cursor: grabbing; transform: scale(1.05); z-index: 10; box-shadow: 0 0 10px rgba(0,0,0,0.5); }
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
    .image-preview {
        width: 100%;
        height: 150px;
        object-fit: cover;
        border-radius: 4px;
    }
</style>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow border-0">
                <div class="card-header bg-warning text-dark py-3">
                    <h4 class="mb-0 fw-bold"><i class="bi bi-images me-2"></i>{{ __('messages.images_to_pdf') }}</h4>
                </div>
                
                <div class="card-body p-4 bg-light">
                    <!-- Setup Area -->
                    <div id="drop-zone" class="drop-zone border rounded p-5 text-center bg-white shadow-sm mb-4" style="border: 2px dashed #ffc107 !important; cursor: pointer; transition: all 0.3s;">
                        <i class="bi bi-images text-warning mb-3" style="font-size: 4rem;"></i>
                        <h5 class="fw-bold">{{ __('messages.images_to_pdf') }}</h5>
                        <p class="text-muted">Click or drag & drop images (.jpg, .png) here</p>
                        <input type="file" id="file-input" class="d-none" accept="image/png, image/jpeg, image/jpg" multiple>
                    </div>

                    <!-- Processing/Convert Area -->
                    <div id="convert-area" class="d-none">
                        
                        <div class="d-flex justify-content-between align-items-center mb-3 p-3 bg-white border rounded shadow-sm">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-images fs-3 text-warning me-3"></i>
                                <div>
                                    <h6 class="mb-0 fw-bold text-dark" id="file-count">0 Images Selected</h6>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-danger btn-sm rounded-circle px-2" id="btn-remove-all" title="Remove All">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>

                        <div class="bg-white p-4 border rounded shadow-sm mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold mb-0"><i class="bi bi-arrows-move me-2"></i>Drag and drop to reorder images</h6>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-more">
                                    <i class="bi bi-plus-circle me-1"></i> Add More
                                </button>
                            </div>
                            <div id="pages-grid" class="row row-cols-2 row-cols-md-4 row-cols-lg-5 g-3">
                                <!-- Thumbnails will be injected here -->
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="text-center">
                            <button type="button" id="btn-action" class="btn btn-warning btn-lg px-5 shadow fw-bold">
                                <i class="bi bi-file-earmark-pdf me-2" id="btn-icon"></i> 
                                <span id="btn-text">Convert to PDF</span>
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
            <div class="mt-5 card shadow-sm border-0">
                <div class="card-body p-4 p-md-5 text-start">
                    {!! __('messages.article_images_to_pdf_content') !!}
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Scripts required -->
<script src="https://unpkg.com/pdf-lib@1.17.1/dist/pdf-lib.min.js"></script>
<!-- Sortable JS for drag and drop -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const dropZone = document.getElementById('drop-zone');
        const fileInput = document.getElementById('file-input');
        const convertArea = document.getElementById('convert-area');
        const fileCountDiv = document.getElementById('file-count');
        const btnRemoveAll = document.getElementById('btn-remove-all');
        const btnAddMore = document.getElementById('btn-add-more');
        const btnAction = document.getElementById('btn-action');
        const btnSpinner = document.getElementById('btn-spinner');
        const errorMessage = document.getElementById('error-message');
        const pagesGrid = document.getElementById('pages-grid');
        
        let loadedImages = []; // Stores { id, file, dataUrl }
        let sortableInstance = null;
        let imageIdCounter = 0;

        // Init Drag and Drop
        dropZone.addEventListener('click', () => fileInput.click());
        btnAddMore.addEventListener('click', () => fileInput.click());

        dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('border-primary'); });
        dropZone.addEventListener('dragleave', (e) => { e.preventDefault(); dropZone.classList.remove('border-primary'); });
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('border-primary');
            handleFiles(e.dataTransfer.files);
        });

        fileInput.addEventListener('change', (e) => {
            handleFiles(e.target.files);
            fileInput.value = ''; // reset so same files can be chosen again
        });
        
        btnRemoveAll.addEventListener('click', resetUI);

        function resetUI() {
            loadedImages = [];
            if (sortableInstance) {
                sortableInstance.destroy();
                sortableInstance = null;
            }
            pagesGrid.innerHTML = '';
            convertArea.classList.add('d-none');
            dropZone.classList.remove('d-none');
            errorMessage.classList.add('d-none');
        }

        function handleFiles(files) {
            errorMessage.classList.add('d-none');
            let validFilesFound = false;
            
            Array.from(files).forEach(file => {
                if (file.type.startsWith('image/')) {
                    validFilesFound = true;
                    readAndDisplayImage(file);
                }
            });

            if (!validFilesFound && loadedImages.length === 0) {
                showError('Please select valid image files (JPG, PNG).');
            }
            
            if (validFilesFound || loadedImages.length > 0) {
                dropZone.classList.add('d-none');
                convertArea.classList.remove('d-none');
                
                if (!sortableInstance) {
                    sortableInstance = new Sortable(pagesGrid, {
                        animation: 150,
                        ghostClass: 'sortable-ghost',
                        onEnd: updateImageOrder
                    });
                }
            }
        }
        
        function readAndDisplayImage(file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const dataUrl = e.target.result;
                const imageId = 'img_' + (imageIdCounter++);
                
                loadedImages.push({
                    id: imageId,
                    file: file,
                    dataUrl: dataUrl
                });
                
                renderThumbnail(imageId, dataUrl);
                updateImageOrder();
            };
            reader.readAsDataURL(file);
        }

        function renderThumbnail(imageId, dataUrl) {
            const colDiv = document.createElement('div');
            colDiv.className = 'col thumbnail-container';
            colDiv.dataset.imageId = imageId;

            const img = document.createElement('img');
            img.src = dataUrl;
            img.className = 'image-preview shadow-sm bg-white border';

            const numBadge = document.createElement('div');
            numBadge.className = 'page-number-badge';
            
            const deleteBtn = document.createElement('button');
            deleteBtn.className = 'btn btn-danger btn-sm p-0 delete-btn';
            deleteBtn.innerHTML = '<i class="bi bi-trash"></i>';
            deleteBtn.onclick = function(e) {
                e.stopPropagation();
                
                // Remove from DOM
                colDiv.remove();
                
                // Remove from array
                loadedImages = loadedImages.filter(imgObj => imgObj.id !== imageId);
                
                if (loadedImages.length === 0) {
                    resetUI();
                } else {
                    updateImageOrder();
                }
            };

            colDiv.appendChild(img);
            colDiv.appendChild(numBadge);
            colDiv.appendChild(deleteBtn);
            
            pagesGrid.appendChild(colDiv);
        }

        function updateImageOrder() {
            const elements = pagesGrid.querySelectorAll('.thumbnail-container');
            elements.forEach((el, idx) => {
                const badge = el.querySelector('.page-number-badge');
                if(badge) badge.textContent = idx + 1;
            });
            fileCountDiv.textContent = elements.length + (elements.length === 1 ? ' Image Selected' : ' Images Selected');
        }

        function showError(msg) {
            errorMessage.textContent = msg;
            errorMessage.classList.remove('d-none');
        }

        btnAction.addEventListener('click', async () => {
            const orderedElements = Array.from(pagesGrid.querySelectorAll('.thumbnail-container'));
            if (orderedElements.length === 0) return;
            
            errorMessage.classList.add('d-none');
            
            try {
                btnAction.disabled = true;
                btnSpinner.classList.remove('d-none');
                
                const { PDFDocument } = PDFLib;
                const pdfDoc = await PDFDocument.create();
                
                for (let i = 0; i < orderedElements.length; i++) {
                    const imgId = orderedElements[i].dataset.imageId;
                    const imageObj = loadedImages.find(img => img.id === imgId);
                    
                    if(imageObj) {
                        const arrayBuffer = await imageObj.file.arrayBuffer();
                        let pdfImage;
                        
                        try {
                            if (imageObj.file.type === 'image/png') {
                                pdfImage = await pdfDoc.embedPng(arrayBuffer);
                            } else {
                                // Default to JPG for image/jpeg etc
                                pdfImage = await pdfDoc.embedJpg(arrayBuffer);
                            }
                            
                            // add a page with dimensions of the image
                            const dim = pdfImage.scale(1);
                            const page = pdfDoc.addPage([dim.width, dim.height]);
                            page.drawImage(pdfImage, {
                                x: 0,
                                y: 0,
                                width: dim.width,
                                height: dim.height
                            });
                        } catch (imgErr) {
                            console.error('Error embedding image', imageObj.file.name, imgErr);
                            // skip bad images
                        }
                    }
                }
                
                const pdfBytes = await pdfDoc.save();
                
                // Trigger download
                const blob = new Blob([pdfBytes], { type: 'application/pdf' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `images_converted.pdf`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
                
                setTimeout(resetUI, 1000);
                
            } catch (err) {
                console.error(err);
                showError('Error creating PDF: ' + err.message);
            } finally {
                btnAction.disabled = false;
                btnSpinner.classList.add('d-none');
            }
        });
    });
</script>
@endsection
