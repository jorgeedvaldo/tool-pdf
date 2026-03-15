@extends('layouts.app')

@section('title', __('messages.overlay_pdfs') . ' - ToolPDF')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-9">
            <div class="card shadow border-0">
                <div class="card-header bg-teal text-white py-3" style="background-color: #20c997;">
                    <h4 class="mb-0 fw-bold"><i class="bi bi-layers me-2"></i>{{ __('messages.overlay_pdfs') }}</h4>
                </div>
                
                <div class="card-body p-4 bg-light">
                    <!-- Setup Area -->
                    <div id="setup-area">
                        <div class="row g-4 mb-4">
                            <!-- Base PDF -->
                            <div class="col-md-6">
                                <label class="fw-bold mb-2">1. Base Document</label>
                                <div id="drop-zone-base" class="drop-zone border rounded p-4 text-center bg-white shadow-sm" style="border: 2px dashed #0d6efd !important; cursor: pointer;">
                                    <i class="bi bi-file-earmark-pdf fs-1 text-primary mb-2"></i>
                                    <h6>Select Base PDF</h6>
                                    <small class="text-muted d-block" id="base-name">No file selected</small>
                                    <input type="file" id="file-base" class="d-none" accept=".pdf">
                                </div>
                            </div>
                            <!-- Overlay PDF -->
                            <div class="col-md-6">
                                <label class="fw-bold mb-2">2. Overlay Document (e.g. Letterhead/Stamp)</label>
                                <div id="drop-zone-overlay" class="drop-zone border rounded p-4 text-center bg-white shadow-sm" style="border: 2px dashed #20c997 !important; cursor: pointer;">
                                    <i class="bi bi-file-earmark-medical fs-1 text-teal mb-2" style="color:#20c997;"></i>
                                    <h6>Select Overlay PDF</h6>
                                    <small class="text-muted d-block" id="overlay-name">No file selected</small>
                                    <input type="file" id="file-overlay" class="d-none" accept=".pdf">
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info py-2 small">
                            <i class="bi bi-info-circle-fill me-1"></i> The Overlay PDF will be stamped chronologically over the Base PDF. If the Overlay PDF has only 1 page, that page will be stamped on ALL pages of the Base PDF.
                        </div>

                        <div class="text-center mt-4">
                            <button type="button" id="btn-action" class="btn btn-lg px-5 shadow fw-bold text-white btn-success" disabled>
                                <i class="bi bi-layers-fill me-2" id="btn-icon"></i> 
                                <span id="btn-text">Overlay and Download</span>
                                <span class="spinner-border spinner-border-sm d-none ms-2" role="status" id="btn-spinner"></span>
                            </button>
                        </div>
                    </div>
                    
                    <div id="error-message" class="alert alert-danger mt-4 d-none"></div>
                </div>
            </div>
            
            <div class="mt-4 text-center text-muted col-8 mx-auto">
                <p class="small"><i class="bi bi-shield-check text-success me-1"></i> Privacy guaranteed. Files are combined entirely in your web browser. No files are uploaded.</p>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/pdf-lib@1.17.1/dist/pdf-lib.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const fileBase = document.getElementById('file-base');
        const fileOverlay = document.getElementById('file-overlay');
        const dzBase = document.getElementById('drop-zone-base');
        const dzOverlay = document.getElementById('drop-zone-overlay');
        const baseName = document.getElementById('base-name');
        const overlayName = document.getElementById('overlay-name');
        
        const btnAction = document.getElementById('btn-action');
        const btnSpinner = document.getElementById('btn-spinner');
        const errorMessage = document.getElementById('error-message');
        
        let baseFileObj = null;
        let overlayFileObj = null;

        // Base Dropzone
        dzBase.addEventListener('click', () => fileBase.click());
        dzBase.addEventListener('dragover', (e) => { e.preventDefault(); dzBase.style.backgroundColor = '#f1f8ff'; });
        dzBase.addEventListener('dragleave', (e) => { e.preventDefault(); dzBase.style.backgroundColor = 'white'; });
        dzBase.addEventListener('drop', (e) => {
            e.preventDefault();
            dzBase.style.backgroundColor = 'white';
            if (e.dataTransfer.files.length > 0) handleBase(e.dataTransfer.files[0]);
        });
        fileBase.addEventListener('change', (e) => {
            if (e.target.files.length > 0) handleBase(e.target.files[0]);
        });

        // Overlay Dropzone
        dzOverlay.addEventListener('click', () => fileOverlay.click());
        dzOverlay.addEventListener('dragover', (e) => { e.preventDefault(); dzOverlay.style.backgroundColor = '#eef8f5'; });
        dzOverlay.addEventListener('dragleave', (e) => { e.preventDefault(); dzOverlay.style.backgroundColor = 'white'; });
        dzOverlay.addEventListener('drop', (e) => {
            e.preventDefault();
            dzOverlay.style.backgroundColor = 'white';
            if (e.dataTransfer.files.length > 0) handleOverlay(e.dataTransfer.files[0]);
        });
        fileOverlay.addEventListener('change', (e) => {
            if (e.target.files.length > 0) handleOverlay(e.target.files[0]);
        });
        
        function handleBase(file) {
            if (file.type !== 'application/pdf') return;
            baseFileObj = file;
            baseName.textContent = file.name;
            baseName.classList.remove('text-muted');
            baseName.classList.add('text-primary');
            checkReady();
        }

        function handleOverlay(file) {
            if (file.type !== 'application/pdf') return;
            overlayFileObj = file;
            overlayName.textContent = file.name;
            overlayName.classList.remove('text-muted');
            overlayName.classList.add('text-success');
            checkReady();
        }

        function checkReady() {
            btnAction.disabled = !(baseFileObj && overlayFileObj);
        }

        function showError(msg) {
            errorMessage.textContent = msg;
            errorMessage.classList.remove('d-none');
        }

        btnAction.addEventListener('click', async () => {
            if (!baseFileObj || !overlayFileObj) return;
            
            errorMessage.classList.add('d-none');
            
            try {
                btnAction.disabled = true;
                btnSpinner.classList.remove('d-none');
                
                const baseBuffer = await baseFileObj.arrayBuffer();
                const overlayBuffer = await overlayFileObj.arrayBuffer();
                
                const { PDFDocument } = PDFLib;
                
                const basePdf = await PDFDocument.load(baseBuffer);
                const overlayPdf = await PDFDocument.load(overlayBuffer);
                
                const basePages = basePdf.getPages();
                const overlayPages = overlayPdf.getPages();
                const overlayPageCount = overlayPdf.getPageCount();
                
                // We embed all overlay pages into the base document context
                const embeddedOverlayPages = await basePdf.embedPdf(overlayPdf);
                
                // Loop through base pages and stamp the overlay
                for (let i = 0; i < basePages.length; i++) {
                    const basePage = basePages[i];
                    const { width, height } = basePage.getSize();
                    
                    // Determine which overlay page to use
                    // If overlay has only 1 page, always use index 0. 
                    // Otherwise, match sequentially until overlay runs out.
                    let overlayIndex = 0;
                    if (overlayPageCount > 1) {
                         overlayIndex = i < overlayPageCount ? i : overlayPageCount - 1;
                    }
                    
                    const stamp = embeddedOverlayPages[overlayIndex];
                    
                    // Draw the embedded page.
                    // By default, pdf-lib draws from bottom-left (0,0).
                    // We assume both PDFs share roughly similar dimensions, or we scale to fit.
                    // For overlay/letterhead, usually scaling to exactly fill the base page works best.
                    basePage.drawPage(stamp, {
                        x: 0,
                        y: 0,
                        width: width,
                        height: height,
                    });
                }
                
                const pdfBytes = await basePdf.save();
                
                const blob = new Blob([pdfBytes], { type: 'application/pdf' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                const originalName = baseFileObj.name.replace('.pdf', '');
                a.download = `${originalName}_layered.pdf`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
                
            } catch (err) {
                console.error(err);
                showError('Error combining PDFs: ' + err.message);
            } finally {
                btnAction.disabled = false;
                btnSpinner.classList.add('d-none');
            }
        });
    });
</script>
@endsection
