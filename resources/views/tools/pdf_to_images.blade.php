@extends('layouts.app')

@section('title', __('messages.pdf_to_images') . ' - ToolPDF')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow border-0">
                <div class="card-header bg-warning text-dark py-3">
                    <h4 class="mb-0 fw-bold"><i class="bi bi-file-earmark-image me-2"></i>{{ __('messages.pdf_to_images') }}</h4>
                </div>
                
                <div class="card-body p-4 bg-light">
                    <!-- Setup Area -->
                    <div id="drop-zone" class="drop-zone border rounded p-5 text-center bg-white shadow-sm mb-4" style="border: 2px dashed #ffc107 !important; cursor: pointer; transition: all 0.3s;">
                        <i class="bi bi-file-earmark-image text-warning mb-3" style="font-size: 4rem;"></i>
                        <h5 class="fw-bold">{{ __('messages.pdf_to_images') }}</h5>
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

                        <!-- Progress Bar (Visible during conversion) -->
                        <div id="progress-container" class="mb-4 d-none">
                            <div class="d-flex justify-content-between mb-1">
                                <small class="text-muted" id="progress-text">Converting: 0%</small>
                                <small class="text-muted" id="progress-pages">0 / 0 pages</small>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div id="progress-bar" class="progress-bar bg-warning progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="text-center">
                            <button type="button" id="btn-action" class="btn btn-warning btn-lg px-5 shadow fw-bold">
                                <i class="bi bi-arrow-right-circle me-2" id="btn-icon"></i> 
                                <span id="btn-text">Convert to JPG</span>
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
                    {!! __('messages.article_pdf_to_images_content') !!}
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Scripts required -->
<!-- PDF.js for rendering canvas thumbnails -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
<script>pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';</script>
<!-- JSZip for saving multiple images as a zip file -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

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
        
        const progressContainer = document.getElementById('progress-container');
        const progressBar = document.getElementById('progress-bar');
        const progressText = document.getElementById('progress-text');
        const progressPages = document.getElementById('progress-pages');

        let selectedFile = null;
        let pdfjsDoc = null; 
        const IMAGE_QUALITY = 0.95; // JPG quality
        const SCALE = 2.0; // Higher scale = better resolution images

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
            pdfjsDoc = null;
            fileInput.value = '';
            convertArea.classList.add('d-none');
            progressContainer.classList.add('d-none');
            dropZone.classList.remove('d-none');
            errorMessage.classList.add('d-none');
            
            progressBar.style.width = '0%';
            progressBar.setAttribute('aria-valuenow', 0);
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

                // Load with PDF.js
                const arrayBuffer = await file.arrayBuffer();
                pdfjsDoc = await pdfjsLib.getDocument(new Uint8Array(arrayBuffer)).promise;
                
                pageCountDiv.textContent = pdfjsDoc.numPages + ' Pages';
                btnAction.disabled = false;

            } catch (err) {
                console.error(err);
                showError('Could not process this PDF: ' + err.message);
                resetUI();
            }
        }

        function showError(msg) {
            errorMessage.textContent = msg;
            errorMessage.classList.remove('d-none');
        }

        // Helper to convert dataURI (base64) to a Blob that JSZip can absorb
        function dataURItoBlob(dataURI) {
            var byteString = atob(dataURI.split(',')[1]);
            var mimeString = dataURI.split(',')[0].split(':')[1].split(';')[0];
            var ab = new ArrayBuffer(byteString.length);
            var ia = new Uint8Array(ab);
            for (var i = 0; i < byteString.length; i++) {
                ia[i] = byteString.charCodeAt(i);
            }
            return new Blob([ab], {type: mimeString});
        }

        btnAction.addEventListener('click', async () => {
            if (!pdfjsDoc) return;
            errorMessage.classList.add('d-none');
            
            try {
                btnAction.disabled = true;
                btnSpinner.classList.remove('d-none');
                btnRemove.disabled = true;
                
                progressContainer.classList.remove('d-none');
                
                let zip = new JSZip();
                const totalPages = pdfjsDoc.numPages;

                // Iterate pages sequentially to not crash the browser
                for (let i = 1; i <= totalPages; i++) {
                    const page = await pdfjsDoc.getPage(i);
                    const viewport = page.getViewport({ scale: SCALE }); 
                    
                    const canvas = document.createElement('canvas');
                    const context = canvas.getContext('2d');
                    canvas.height = viewport.height;
                    canvas.width = viewport.width;

                    // Ensure white background
                    context.fillStyle = "white";
                    context.fillRect(0, 0, canvas.width, canvas.height);

                    await page.render({
                        canvasContext: context,
                        viewport: viewport
                    }).promise;
                    
                    const dataUrl = canvas.toDataURL('image/jpeg', IMAGE_QUALITY);
                    const blob = dataURItoBlob(dataUrl);
                    
                    // Add to zip, padding numbers (e.g. page_01.jpg)
                    const paddedNum = String(i).padStart(String(totalPages).length, '0');
                    zip.file(`page_${paddedNum}.jpg`, blob);
                    
                    // Update progress
                    const percent = Math.round((i / totalPages) * 100);
                    progressBar.style.width = percent + '%';
                    progressBar.setAttribute('aria-valuenow', percent);
                    progressText.textContent = `Converting: ${percent}%`;
                    progressPages.textContent = `${i} / ${totalPages} pages`;
                }

                progressText.textContent = "Zipping files...";

                const zipBlob = await zip.generateAsync({type:"blob"});
                
                // Trigger download
                const url = URL.createObjectURL(zipBlob);
                const a = document.createElement('a');
                a.href = url;
                const originalName = selectedFile.name.replace('.pdf', '');
                a.download = `${originalName}_images.zip`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
                
                // Reset form on success
                setTimeout(resetUI, 1000);
                
            } catch (err) {
                console.error(err);
                showError('Error during PDF conversion: ' + err.message);
            } finally {
                btnAction.disabled = false;
                btnSpinner.classList.add('d-none');
                btnRemove.disabled = false;
            }
        });
    });
</script>
@endsection
