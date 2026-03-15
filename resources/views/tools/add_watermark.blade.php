@extends('layouts.app')

@section('title', __('messages.add_watermark') . ' - ToolPDF')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow border-0">
                <div class="card-header bg-indigo text-white py-3" style="background-color: #6610f2;">
                    <h4 class="mb-0 fw-bold"><i class="bi bi-droplet me-2"></i>{{ __('messages.add_watermark') }}</h4>
                </div>
                
                <div class="card-body p-4 bg-light">
                    <!-- Setup Area -->
                    <div id="drop-zone" class="drop-zone border rounded p-5 text-center bg-white shadow-sm mb-4" style="border: 2px dashed #6610f2 !important; cursor: pointer; transition: all 0.3s;">
                        <i class="bi bi-droplet mb-3" style="font-size: 4rem; color: #6610f2;"></i>
                        <h5 class="fw-bold">{{ __('messages.add_watermark') }}</h5>
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
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-danger btn-sm rounded-circle px-2" id="btn-remove" title="Remove PDF">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>

                        <div class="bg-white p-4 border rounded shadow-sm mb-4">
                            <h6 class="fw-bold mb-3"><i class="bi bi-textarea-t me-2 text-primary"></i>Watermark Settings</h6>
                            
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold">{{ __('messages.watermark_text') }}</label>
                                <input type="text" id="watermark-text" class="form-control" placeholder="CONFIDENTIAL" value="CONFIDENTIAL">
                            </div>
                            
                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="form-label text-muted small fw-bold">Size</label>
                                    <input type="number" id="watermark-size" class="form-control" value="60">
                                </div>
                                <div class="col-6">
                                    <label class="form-label text-muted small fw-bold">Opacity (0.1 - 1.0)</label>
                                    <input type="number" id="watermark-opacity" class="form-control" value="0.3" min="0.1" max="1.0" step="0.1">
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="text-center">
                            <button type="button" id="btn-action" class="btn btn-lg px-5 shadow fw-bold text-white" style="background-color: #6610f2;">
                                <i class="bi bi-layer-forward me-2" id="btn-icon"></i> 
                                <span id="btn-text">Apply Watermark</span>
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

<script src="https://unpkg.com/pdf-lib@1.17.1/dist/pdf-lib.min.js"></script>
<!-- Fontkit for custom fonts in pdf-lib (optional, but good for robust rendering) -->
<script src="https://unpkg.com/@pdf-lib/fontkit/dist/fontkit.umd.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const dropZone = document.getElementById('drop-zone');
        const fileInput = document.getElementById('file-input');
        const convertArea = document.getElementById('convert-area');
        const fileNameDiv = document.getElementById('file-name');
        
        const wmTextInput = document.getElementById('watermark-text');
        const wmSizeInput = document.getElementById('watermark-size');
        const wmOpacityInput = document.getElementById('watermark-opacity');
        
        const btnRemove = document.getElementById('btn-remove');
        const btnAction = document.getElementById('btn-action');
        const btnSpinner = document.getElementById('btn-spinner');
        const errorMessage = document.getElementById('error-message');

        let selectedFile = null;

        dropZone.addEventListener('click', () => fileInput.click());
        dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.style.borderColor = '#6610f2'; });
        dropZone.addEventListener('dragleave', (e) => { e.preventDefault(); dropZone.style.borderColor = '#6610f2'; });
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            if (e.dataTransfer.files.length > 0) handleFile(e.dataTransfer.files[0]);
        });
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) handleFile(e.target.files[0]);
        });
        
        btnRemove.addEventListener('click', resetUI);

        function resetUI() {
            selectedFile = null;
            fileInput.value = '';
            convertArea.classList.add('d-none');
            dropZone.classList.remove('d-none');
            errorMessage.classList.add('d-none');
        }

        function handleFile(file) {
            errorMessage.classList.add('d-none');
            if (file.type !== 'application/pdf') {
                showError('Please select a valid PDF file.');
                return;
            }
            selectedFile = file;
            fileNameDiv.textContent = file.name;
            dropZone.classList.add('d-none');
            convertArea.classList.remove('d-none');
        }

        function showError(msg) {
            errorMessage.textContent = msg;
            errorMessage.classList.remove('d-none');
        }

        btnAction.addEventListener('click', async () => {
            if (!selectedFile) return;
            const text = wmTextInput.value;
            if (!text) return showError('Please enter watermark text.');
            
            errorMessage.classList.add('d-none');
            
            try {
                btnAction.disabled = true;
                btnSpinner.classList.remove('d-none');
                
                const arrayBuffer = await selectedFile.arrayBuffer();
                const { PDFDocument, rgb, degrees, StandardFonts } = PDFLib;
                
                const pdfDoc = await PDFDocument.load(arrayBuffer);
                const helveticaFont = await pdfDoc.embedFont(StandardFonts.HelveticaBold);
                
                const pages = pdfDoc.getPages();
                const size = parseInt(wmSizeInput.value) || 60;
                const opacity = parseFloat(wmOpacityInput.value) || 0.3;
                
                // Draw watermark on each page
                for (let i = 0; i < pages.length; i++) {
                    const page = pages[i];
                    const { width, height } = page.getSize();
                    
                    const textWidth = helveticaFont.widthOfTextAtSize(text, size);
                    const textHeight = helveticaFont.heightAtSize(size);
                    
                    // Center the watermark, diagonal
                    page.drawText(text, {
                        x: width / 2 - textWidth / 2,
                        y: height / 2 - textHeight / 2,
                        size: size,
                        font: helveticaFont,
                        color: rgb(0.5, 0.5, 0.5),
                        opacity: opacity,
                        rotate: degrees(45),
                    });
                }

                const pdfBytes = await pdfDoc.save();
                
                const blob = new Blob([pdfBytes], { type: 'application/pdf' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                const originalName = selectedFile.name.replace('.pdf', '');
                a.download = `${originalName}_watermarked.pdf`;
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
