@extends('layouts.app')

@section('title', __('messages.edit_pdf') . ' - ToolPDF')

@section('content')
<style>
    .pdf-container {
        position: relative;
        overflow: auto;
        background-color: #f8f9fa;
        border: 2px dashed #adb5bd;
        border-radius: 8px;
        min-height: 400px;
        display: flex;
        justify-content: center;
        align-items: center;
        cursor: crosshair;
    }
    canvas#pdf-viewer {
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .text-marker {
        position: absolute;
        color: #0d6efd;
        font-weight: bold;
        transform: translate(-50%, -50%);
        pointer-events: none;
        text-shadow: 1px 1px 2px white;
        z-index: 10;
    }
</style>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow border-0">
                <div class="card-header bg-primary text-white py-3">
                    <h4 class="mb-0 fw-bold"><i class="bi bi-pencil-square me-2"></i>{{ __('messages.edit_pdf') }}</h4>
                </div>
                
                <div class="card-body p-4 bg-light">
                    <!-- Setup Area -->
                    <div id="drop-zone" class="drop-zone border rounded p-5 text-center bg-white shadow-sm mb-4" style="border: 2px dashed #0d6efd !important; cursor: pointer; transition: all 0.3s;">
                        <i class="bi bi-pencil-square mb-3 text-primary" style="font-size: 4rem;"></i>
                        <h5 class="fw-bold">{{ __('messages.edit_pdf') }}</h5>
                        <p class="text-muted">Click or drag & drop a PDF file here to add text</p>
                        <input type="file" id="file-input" class="d-none" accept=".pdf">
                    </div>

                    <!-- Processing/Convert Area -->
                    <div id="convert-area" class="d-none">
                        
                        <div class="d-flex justify-content-between align-items-center mb-4 p-3 bg-white border rounded shadow-sm">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-file-earmark-pdf fs-3 text-danger me-3"></i>
                                <div>
                                    <h6 class="mb-0 fw-bold text-truncate" style="max-width: 250px;" id="file-name">filename.pdf</h6>
                                    <small class="text-muted" id="page-info">Page 1 of 1</small>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button class="btn btn-sm btn-outline-secondary" id="btn-prev"><i class="bi bi-chevron-left"></i></button>
                                <span class="fw-bold px-2" id="current-page-num">1</span>
                                <button class="btn btn-sm btn-outline-secondary" id="btn-next"><i class="bi bi-chevron-right"></i></button>
                                <div class="vr mx-2"></div>
                                <button type="button" class="btn btn-outline-danger btn-sm rounded-circle px-2" id="btn-remove" title="Remove PDF">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>

                        <div class="row g-4">
                            <!-- Tools Column (Left) -->
                            <div class="col-md-4">
                                <div class="bg-white p-3 border rounded shadow-sm h-100">
                                    <h6 class="fw-bold mb-3 border-bottom pb-2"><i class="bi bi-cursor-text me-2"></i>Text Properties</h6>
                                    
                                    <div class="alert alert-info py-2 small mb-3">
                                        <i class="bi bi-info-circle-fill me-1"></i> Click anywhere on the PDF to place your text!
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-muted">Text to Add</label>
                                        <textarea id="text-input" class="form-control" rows="3" placeholder="Enter your text here..."></textarea>
                                    </div>
                                    
                                    <div class="row g-2 mb-3">
                                        <div class="col-6">
                                            <label class="form-label small fw-bold text-muted">Font Size</label>
                                            <input type="number" id="font-size" class="form-control" value="16" min="8" max="72">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-bold text-muted">Color</label>
                                            <input type="color" id="text-color" class="form-control form-control-color w-100" value="#000000" title="Choose color">
                                        </div>
                                    </div>

                                    <hr>

                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-muted">Selected Position (X, Y)</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">X:</span>
                                            <input type="text" id="pos-x" class="form-control" readonly value="0">
                                            <span class="input-group-text">Y:</span>
                                            <input type="text" id="pos-y" class="form-control" readonly value="0">
                                        </div>
                                        <button class="btn btn-sm btn-outline-secondary w-100 mt-2" id="btn-clear-pos">Clear Position</button>
                                    </div>

                                </div>
                            </div>
                            
                            <!-- Viewer Column (Right) -->
                            <div class="col-md-8">
                                <div class="pdf-container shadow-sm" id="pdf-container">
                                    <span class="text-muted" id="viewer-loading" style="display: none;">Loading page...</span>
                                    <canvas id="pdf-viewer"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="text-center mt-4 pt-3 border-top">
                            <button type="button" id="btn-action" class="btn btn-lg px-5 shadow fw-bold text-white btn-primary">
                                <i class="bi bi-check2-circle me-2" id="btn-icon"></i> 
                                <span id="btn-text">Save & Download PDF</span>
                                <span class="spinner-border spinner-border-sm d-none ms-2" role="status" id="btn-spinner"></span>
                            </button>
                        </div>
                        
                        <div id="error-message" class="alert alert-danger mt-3 d-none"></div>
                    </div>

                </div>
            </div>
            
            <div class="mt-4 text-center text-muted col-8 mx-auto">
                <p class="small"><i class="bi bi-shield-check text-success me-1"></i> Privacy guaranteed. Your edits and documents are processed entirely in your web browser. No files are uploaded.</p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <!-- SEO Article -->
            <div class="card shadow-sm border-0 bg-white">
                <div class="card-body p-4 p-md-5 text-start">
                    {!! __('messages.article_edit_pdf_content') !!}
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://unpkg.com/pdf-lib@1.17.1/dist/pdf-lib.min.js"></script>

<script>
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

    document.addEventListener('DOMContentLoaded', function() {
        const dropZone = document.getElementById('drop-zone');
        const fileInput = document.getElementById('file-input');
        const convertArea = document.getElementById('convert-area');
        const fileNameDiv = document.getElementById('file-name');
        const errorMessage = document.getElementById('error-message');
        
        const btnRemove = document.getElementById('btn-remove');
        const btnAction = document.getElementById('btn-action');
        const btnSpinner = document.getElementById('btn-spinner');
        
        const btnPrev = document.getElementById('btn-prev');
        const btnNext = document.getElementById('btn-next');
        const pageNumSpan = document.getElementById('current-page-num');
        const pageInfoSpan = document.getElementById('page-info');

        const pdfViewer = document.getElementById('pdf-viewer');
        const pdfContainer = document.getElementById('pdf-container');
        const viewerLoading = document.getElementById('viewer-loading');

        const inputTextInput = document.getElementById('text-input');
        const inputFontSize = document.getElementById('font-size');
        const inputTextColor = document.getElementById('text-color');
        const posXInput = document.getElementById('pos-x');
        const posYInput = document.getElementById('pos-y');
        const btnClearPos = document.getElementById('btn-clear-pos');

        let selectedFile = null;
        let pdfDoc = null;
        let pageNum = 1;
        let totalPages = 0;
        let currentViewport = null;
        
        // Internal PDF coordinates for pdf-lib mapping
        let targetPdfX = 0;
        let targetPdfY = 0;
        let isPositionSelected = false;

        // Visual Marker
        let marker = document.createElement('div');
        marker.className = 'text-marker d-none';
        marker.innerHTML = '<i class="bi bi-geo-alt-fill fs-3 text-danger"></i>';
        pdfContainer.appendChild(marker);

        // Drop zone logic
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
        
        btnClearPos.addEventListener('click', () => {
            isPositionSelected = false;
            posXInput.value = '0';
            posYInput.value = '0';
            marker.classList.add('d-none');
        });

        function resetUI() {
            selectedFile = null;
            pdfDoc = null;
            fileInput.value = '';
            convertArea.classList.add('d-none');
            dropZone.classList.remove('d-none');
            errorMessage.classList.add('d-none');
            const ctx = pdfViewer.getContext('2d');
            ctx.clearRect(0, 0, pdfViewer.width, pdfViewer.height);
            marker.classList.add('d-none');
            isPositionSelected = false;
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
                
                const arrayBuffer = await file.arrayBuffer();
                const typedarray = new Uint8Array(arrayBuffer);

                pdfDoc = await pdfjsLib.getDocument(typedarray).promise;
                totalPages = pdfDoc.numPages;
                pageNum = 1;
                
                dropZone.classList.add('d-none');
                convertArea.classList.remove('d-none');
                
                renderPage(pageNum);

            } catch (err) {
                console.error(err);
                showError('Could not read this PDF. It may be broken or heavily encrypted.');
                resetUI();
            }
        }

        async function renderPage(num) {
            viewerLoading.style.display = 'block';
            pageNumSpan.textContent = num;
            pageInfoSpan.textContent = `Page ${num} of ${totalPages}`;
            
            btnPrev.disabled = num <= 1;
            btnNext.disabled = num >= totalPages;

            try {
                const page = await pdfDoc.getPage(num);
                // Scale to fit width nicely. E.g. 1.2 or 1.5
                const desiredWidth = pdfContainer.clientWidth ? pdfContainer.clientWidth - 40 : 600; 
                const viewport1 = page.getViewport({ scale: 1 });
                const scale = desiredWidth / viewport1.width;
                currentViewport = page.getViewport({ scale: scale > 2 ? 2 : (scale < 0.5 ? 0.5 : scale) }); // sane limits

                const ctx = pdfViewer.getContext('2d');
                pdfViewer.width = currentViewport.width;
                pdfViewer.height = currentViewport.height;

                const renderContext = {
                    canvasContext: ctx,
                    viewport: currentViewport
                };
                
                await page.render(renderContext).promise;
                
                // Clear active position marker when jumping pages to prevent confusion
                btnClearPos.click();

            } catch(e) {
                console.error(e);
            } finally {
                viewerLoading.style.display = 'none';
            }
        }

        btnPrev.addEventListener('click', () => { if (pageNum <= 1) return; pageNum--; renderPage(pageNum); });
        btnNext.addEventListener('click', () => { if (pageNum >= totalPages) return; pageNum++; renderPage(pageNum); });

        // Map Click on Canvas to PDF Coordinates
        pdfViewer.addEventListener('click', (e) => {
            if (!currentViewport) return;

            const rect = pdfViewer.getBoundingClientRect();
            // Mouse position relative to the canvas CSS bounds
            let cssX = e.clientX - rect.left;
            let cssY = e.clientY - rect.top;

            // Map CSS to Canvas internal size
            let scaleX = pdfViewer.width / rect.width;
            let scaleY = pdfViewer.height / rect.height;
            
            let canvasX = cssX * scaleX;
            let canvasY = cssY * scaleY;

            // Map canvas points to PDF Points using the viewport transform matrix logic
            // Because pdf.js sets Y=0 at TOP, but pdf-lib sets Y=0 at BOTTOM.
            // Let's get the absolute points using reverse viewport translation:
            const pdfPoint = currentViewport.convertToPdfPoint(canvasX, canvasY);
            
            targetPdfX = pdfPoint[0];
            targetPdfY = pdfPoint[1]; // Viewport convertToPdfPoint automatically handles the Y inversion!
            
            posXInput.value = targetPdfX.toFixed(2);
            posYInput.value = targetPdfY.toFixed(2);
            isPositionSelected = true;

            // Position visual marker relative to the container scroll/position.
            marker.classList.remove('d-none');
            // Marker top/left within the relative container includes canvas offset
            marker.style.left = (pdfViewer.offsetLeft + cssX) + 'px';
            marker.style.top = (pdfViewer.offsetTop + cssY) + 'px';
        });

        // Convert HEX to RGB decimal array [0-1]
        function hexToRgb(hex) {
            let r = 0, g = 0, b = 0;
            if (hex.length == 7) {
                r = parseInt(hex.substring(1,3), 16) / 255;
                g = parseInt(hex.substring(3,5), 16) / 255;
                b = parseInt(hex.substring(5,7), 16) / 255;
            }
            return {r, g, b};
        }

        function showError(msg) {
            errorMessage.textContent = msg;
            errorMessage.classList.remove('d-none');
        }

        btnAction.addEventListener('click', async () => {
            if (!selectedFile) return;
            const textContent = inputTextInput.value.trim();
            
            if (!textContent) {
                return showError("Please enter the text you want to add.");
            }
            if (!isPositionSelected) {
                return showError("Please click on the document where you want the text to appear.");
            }
            
            errorMessage.classList.add('d-none');
            
            try {
                btnAction.disabled = true;
                btnSpinner.classList.remove('d-none');
                
                const arrayBuffer = await selectedFile.arrayBuffer();
                const { PDFDocument, rgb, StandardFonts } = PDFLib;
                
                const pdfLibDoc = await PDFDocument.load(arrayBuffer);
                const helveticaFont = await pdfLibDoc.embedFont(StandardFonts.Helvetica);
                
                const pages = pdfLibDoc.getPages();
                
                // pageNum is 1-indexed
                const targetPage = pages[pageNum - 1];
                
                const fontSize = parseFloat(inputFontSize.value) || 16;
                const userColor = hexToRgb(inputTextColor.value);

                // Split text by newlines and draw each line
                const lines = textContent.split('\n');
                const lineHeight = helveticaFont.heightAtSize(fontSize) * 1.2;

                for (let i = 0; i < lines.length; i++) {
                    const line = lines[i];
                    targetPage.drawText(line, {
                        x: targetPdfX,
                        // pdf-lib's origin is bottom-left. 
                        // targetPdfY points to the top of the text block as chosen by user.
                        // We must subtract line heights to move down, and subtract the font height itself to align tops.
                        y: targetPdfY - (helveticaFont.heightAtSize(fontSize)) - (i * lineHeight),
                        size: fontSize,
                        font: helveticaFont,
                        color: rgb(userColor.r, userColor.g, userColor.b),
                    });
                }

                const pdfBytes = await pdfLibDoc.save();
                
                const blob = new Blob([pdfBytes], { type: 'application/pdf' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                const originalName = selectedFile.name.replace('.pdf', '');
                a.download = `${originalName}_edited.pdf`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
                
            } catch (err) {
                console.error(err);
                showError('Error editing PDF: ' + err.message);
            } finally {
                btnAction.disabled = false;
                btnSpinner.classList.add('d-none');
            }
        });
    });
</script>
@endsection
