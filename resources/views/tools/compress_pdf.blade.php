@extends('layouts.app')

@section('title', __('messages.compress_pdf') . ' - ToolPDF')

@section('content')
<style>
    .drop-zone { border: 2px dashed #198754 !important; cursor: pointer; transition: all 0.3s; }
    .drop-zone:hover { background-color: #f8f9fa; }
</style>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow border-0">
                <div class="card-header bg-success text-white py-3">
                    <h4 class="mb-0 fw-bold"><i class="bi bi-arrows-angle-contract me-2"></i>{{ __('messages.compress_pdf') }}</h4>
                </div>
                
                <div class="card-body p-4 bg-light">
                    <!-- Setup Area -->
                    <div id="drop-zone" class="drop-zone border rounded p-5 text-center bg-white shadow-sm mb-4">
                        <i class="bi bi-arrows-angle-contract mb-3 text-success" style="font-size: 4rem;"></i>
                        <h5 class="fw-bold">{{ __('messages.compress_pdf') }}</h5>
                        <p class="text-muted">Click or drag & drop a PDF file here to compress</p>
                        <input type="file" id="file-input" class="d-none" accept=".pdf">
                    </div>

                    <!-- Processing/Convert Area -->
                    <div id="convert-area" class="d-none">
                        
                        <div class="d-flex justify-content-between align-items-center mb-4 p-3 bg-white border rounded shadow-sm">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-file-earmark-pdf fs-3 text-success me-3"></i>
                                <div>
                                    <h6 class="mb-0 fw-bold text-truncate" style="max-width: 250px;" id="file-name">filename.pdf</h6>
                                    <small class="text-muted" id="file-size-info"></small>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-danger btn-sm rounded-circle px-2" id="btn-remove" title="Remove PDF">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>

                        <!-- Options -->
                        <div class="bg-white p-4 border rounded shadow-sm mb-4">
                            <h6 class="fw-bold mb-3"><i class="bi bi-sliders me-2 text-primary"></i>Compression Level</h6>
                            
                            <div class="btn-group w-100 mb-3" role="group">
                                <input type="radio" class="btn-check" name="compressionLevel" id="comp-low" autocomplete="off" value="low">
                                <label class="btn btn-outline-success" for="comp-low">
                                    <strong>Low</strong><br><small>Higher Quality</small>
                                </label>
                              
                                <input type="radio" class="btn-check" name="compressionLevel" id="comp-med" autocomplete="off" value="med" checked>
                                <label class="btn btn-outline-success" for="comp-med">
                                    <strong>Medium</strong><br><small>Good Quality & Size</small>
                                </label>
                              
                                <input type="radio" class="btn-check" name="compressionLevel" id="comp-high" autocomplete="off" value="high">
                                <label class="btn btn-outline-success" for="comp-high">
                                    <strong>High</strong><br><small>Smallest Size</small>
                                </label>
                            </div>
                            
                            <div class="alert alert-info py-2 small mb-0">
                                <i class="bi bi-info-circle me-1"></i> Note: To safely minimize the file size on your device, texts and vector layers will be rasterized.
                            </div>
                        </div>

                        <!-- Progress Section -->
                        <div id="progress-container" class="mb-4 d-none">
                            <h6 class="text-muted mb-2 text-center" id="progress-text">Compressing...</h6>
                            <div class="progress" style="height: 15px;">
                                <div id="progress-bar" class="progress-bar bg-success progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;"></div>
                            </div>
                            <small class="text-center d-block mt-2 text-secondary" id="progress-detail"></small>
                        </div>

                        <!-- Actions -->
                        <div class="text-center">
                            <button type="button" id="btn-action" class="btn btn-lg px-5 shadow fw-bold text-white btn-success">
                                <i class="bi bi-arrows-angle-contract me-2" id="btn-icon"></i> 
                                <span id="btn-text">Compress PDF</span>
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

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
<script>pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const dropZone = document.getElementById('drop-zone');
        const fileInput = document.getElementById('file-input');
        const convertArea = document.getElementById('convert-area');
        const fileNameDiv = document.getElementById('file-name');
        const fileSizeInfo = document.getElementById('file-size-info');
        const btnRemove = document.getElementById('btn-remove');
        const btnAction = document.getElementById('btn-action');
        const btnSpinner = document.getElementById('btn-spinner');
        const errorMessage = document.getElementById('error-message');
        const progressContainer = document.getElementById('progress-container');
        const progressBar = document.getElementById('progress-bar');
        const progressText = document.getElementById('progress-text');
        const progressDetail = document.getElementById('progress-detail');

        let selectedFile = null;
        let originalSizeStr = "";

        dropZone.addEventListener('click', () => fileInput.click());
        dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.style.borderColor = '#198754'; });
        dropZone.addEventListener('dragleave', (e) => { e.preventDefault(); dropZone.style.borderColor = '#198754'; });
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.style.borderColor = '#198754';
            if (e.dataTransfer.files.length > 0) handleFile(e.dataTransfer.files[0]);
        });
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) handleFile(e.target.files[0]);
        });
        
        btnRemove.addEventListener('click', resetUI);
        
        function formatBytes(bytes, decimals = 2) {
            if (!+bytes) return '0 Bytes';
            const k = 1024, dm = decimals < 0 ? 0 : decimals, sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`;
        }

        function resetUI() {
            selectedFile = null;
            fileInput.value = '';
            convertArea.classList.add('d-none');
            dropZone.classList.remove('d-none');
            errorMessage.classList.add('d-none');
            progressContainer.classList.add('d-none');
            progressBar.style.width = '0%';
            btnAction.disabled = false;
        }

        function handleFile(file) {
            errorMessage.classList.add('d-none');
            if (file.type !== 'application/pdf') {
                showError('Please select a valid PDF file.');
                return;
            }
            selectedFile = file;
            fileNameDiv.textContent = file.name;
            originalSizeStr = formatBytes(file.size);
            fileSizeInfo.innerHTML = `Original Size: <strong>${originalSizeStr}</strong>`;
            
            dropZone.classList.add('d-none');
            convertArea.classList.remove('d-none');
        }

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
                progressContainer.classList.remove('d-none');
                progressBar.style.width = '0%';
                
                // Read levels
                const compLevel = document.querySelector('input[name="compressionLevel"]:checked').value;
                let scale = 1.5;
                let jpgQuality = 0.8;
                
                if (compLevel === 'low') { scale = 2.0; jpgQuality = 0.85; }
                else if (compLevel === 'high') { scale = 1.0; jpgQuality = 0.6; }

                progressText.textContent = "Loading Document...";
                const arrayBuffer = await selectedFile.arrayBuffer();
                const pdfDoc = await pdfjsLib.getDocument(new Uint8Array(arrayBuffer)).promise;
                const totalPages = pdfDoc.numPages;
                
                const { jsPDF } = window.jspdf;
                const outDoc = new jsPDF({ orientation: 'portrait', unit: 'px', format: 'a4' });
                
                for (let i = 1; i <= totalPages; i++) {
                    progressBar.style.width = `${((i-1)/totalPages)*100}%`;
                    progressText.textContent = `Compressing...`;
                    progressDetail.textContent = `Processing page ${i} of ${totalPages}`;
                    
                    const page = await pdfDoc.getPage(i);
                    const defaultViewport = page.getViewport({ scale: 1.0 });
                    const viewport = page.getViewport({ scale: scale }); // rendering scale
                    
                    const canvas = document.createElement('canvas');
                    const context = canvas.getContext('2d');
                    canvas.width = viewport.width;
                    canvas.height = viewport.height;
                    
                    await page.render({ canvasContext: context, viewport: viewport }).promise;
                    
                    // Compress to JPG
                    const imgData = canvas.toDataURL('image/jpeg', jpgQuality);
                    
                    // Add to jsPDF using original unmodified dimensions
                    if (i > 1) {
                        outDoc.addPage([defaultViewport.width, defaultViewport.height], defaultViewport.width > defaultViewport.height ? 'landscape' : 'portrait');
                        outDoc.setPage(i);
                    } else {
                        // Optional: first page sizing hack not needed for jsPDF but good to align
                    }
                    outDoc.addImage(imgData, 'JPEG', 0, 0, defaultViewport.width, defaultViewport.height, '', 'FAST');
                }

                progressText.textContent = "Finalizing Output...";
                progressBar.style.width = '100%';
                progressDetail.textContent = "Saving to your device...";
                
                const blob = outDoc.output('blob');
                const compressedSizeStr = formatBytes(blob.size);
                
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                const originalName = selectedFile.name.replace('.pdf', '');
                a.download = `${originalName}_compressed.pdf`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
                
                progressText.textContent = "Completed!";
                progressBar.classList.remove('progress-bar-animated');
                progressDetail.innerHTML = `<span class="text-success fw-bold">Reduced to: ${compressedSizeStr}</span> (from ${originalSizeStr})`;
                
            } catch (err) {
                console.error(err);
                if (err && err.message) {
                    showError('Error processing PDF: ' + err.message);
                } else {
                    showError('Error processing PDF: Unknown error.');
                }
            } finally {
                btnAction.disabled = false;
                btnSpinner.classList.add('d-none');
            }
        });
    });
</script>
@endsection
