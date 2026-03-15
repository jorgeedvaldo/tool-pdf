@extends('layouts.app')

@section('title', __('messages.sign_pdf') . ' - ToolPDF')

@section('content')
<style>
    .signature-container {
        border: 2px dashed #0d6efd;
        border-radius: 8px;
        background-color: #f8f9fa;
        position: relative;
        overflow: hidden;
    }
    canvas#signature-pad {
        width: 100%;
        height: 250px;
        cursor: crosshair;
        background-color: white;
    }
    .signature-placeholder {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: #adb5bd;
        pointer-events: none;
        font-size: 1.5rem;
        font-weight: bold;
        opacity: 0.5;
    }
</style>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-9">
            <div class="card shadow border-0">
                <div class="card-header bg-primary text-white py-3">
                    <h4 class="mb-0 fw-bold"><i class="bi bi-pen me-2"></i>{{ __('messages.sign_pdf') }}</h4>
                </div>
                
                <div class="card-body p-4 bg-light">
                    <!-- Setup Area -->
                    <div id="drop-zone" class="drop-zone border rounded p-5 text-center bg-white shadow-sm mb-4" style="border: 2px dashed #0d6efd !important; cursor: pointer; transition: all 0.3s;">
                        <i class="bi bi-pen mb-3 text-primary" style="font-size: 4rem;"></i>
                        <h5 class="fw-bold">{{ __('messages.sign_pdf') }}</h5>
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

                        <!-- Signature Pad Area -->
                        <div class="bg-white p-4 border rounded shadow-sm mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold mb-0"><i class="bi bi-vector-pen me-2 text-primary"></i>{{ __('messages.draw_signature') ?? 'Draw your signature' }}</h6>
                                <button class="btn btn-sm btn-outline-danger" id="btn-clear-sig"><i class="bi bi-eraser"></i> {{ __('messages.clear_signature') ?? 'Clear' }}</button>
                            </div>
                            
                            <div class="signature-container mb-3 shadow-sm">
                                <span class="signature-placeholder" id="sig-placeholder">Sign Here</span>
                                <canvas id="signature-pad"></canvas>
                            </div>

                            <div class="row g-3 mt-2 border-top pt-3">
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold">{{ __('messages.page_to_sign') ?? 'Page to sign' }}</label>
                                    <input type="number" id="sign-page" class="form-control" value="1" min="1">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold">{{ __('messages.position_to_sign') ?? 'Signature Position' }}</label>
                                    <select id="sign-position" class="form-select">
                                        <option value="bottom-right" selected>Bottom Right</option>
                                        <option value="bottom-left">Bottom Left</option>
                                        <option value="bottom-center">Bottom Center</option>
                                        <option value="top-right">Top Right</option>
                                        <option value="top-left">Top Left</option>
                                        <option value="center">Center</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="text-center">
                            <button type="button" id="btn-action" class="btn btn-lg px-5 shadow fw-bold text-white btn-primary">
                                <i class="bi bi-check2-circle me-2" id="btn-icon"></i> 
                                <span id="btn-text">{{ __('messages.save_signature') ?? 'Save & Apply' }}</span>
                                <span class="spinner-border spinner-border-sm d-none ms-2" role="status" id="btn-spinner"></span>
                            </button>
                        </div>
                        
                        <div id="error-message" class="alert alert-danger mt-3 d-none"></div>
                    </div>

                </div>
            </div>
            
            <div class="mt-4 text-center text-muted col-8 mx-auto">
                <p class="small"><i class="bi bi-shield-check text-success me-1"></i> Privacy guaranteed. Your signature and document are processed entirely in your web browser. No files are uploaded.</p>
            </div>
        </div>
    </div>
</div>

<!-- Scripts required -->
<script src="https://unpkg.com/pdf-lib@1.17.1/dist/pdf-lib.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const dropZone = document.getElementById('drop-zone');
        const fileInput = document.getElementById('file-input');
        const convertArea = document.getElementById('convert-area');
        const fileNameDiv = document.getElementById('file-name');
        const pageCountSpan = document.getElementById('page-count');
        
        const signPageInput = document.getElementById('sign-page');
        const signPositionSelect = document.getElementById('sign-position');
        
        const btnRemove = document.getElementById('btn-remove');
        const btnAction = document.getElementById('btn-action');
        const btnSpinner = document.getElementById('btn-spinner');
        const errorMessage = document.getElementById('error-message');
        const sigPlaceholder = document.getElementById('sig-placeholder');
        const btnClearSig = document.getElementById('btn-clear-sig');

        let selectedFile = null;
        let totalPages = 0;
        let signaturePad = null;

        // Initialize Signature Pad
        const canvas = document.getElementById('signature-pad');
        
        function resizeCanvas() {
            const ratio =  Math.max(window.devicePixelRatio || 1, 1);
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            canvas.getContext("2d").scale(ratio, ratio);
            if (signaturePad) signaturePad.clear();
        }
        
        window.addEventListener("resize", resizeCanvas);
        resizeCanvas();

        signaturePad = new SignaturePad(canvas, {
            penColor: "rgb(0, 0, 128)", // Dark blue ink
            backgroundColor: "rgba(255, 255, 255, 0)" // Transparent background
        });

        signaturePad.addEventListener("beginStroke", () => {
            sigPlaceholder.style.display = 'none';
        });

        btnClearSig.addEventListener('click', () => {
            signaturePad.clear();
            sigPlaceholder.style.display = 'block';
        });

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

        function resetUI() {
            selectedFile = null;
            fileInput.value = '';
            convertArea.classList.add('d-none');
            dropZone.classList.remove('d-none');
            errorMessage.classList.add('d-none');
            signaturePad.clear();
            sigPlaceholder.style.display = 'block';
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
                
                // Read purely to get page count
                const arrayBuffer = await file.arrayBuffer();
                const { PDFDocument } = PDFLib;
                const pdfDoc = await PDFDocument.load(arrayBuffer, { ignoreEncryption: true });
                totalPages = pdfDoc.getPageCount();
                pageCountSpan.textContent = totalPages + ' Pages';
                signPageInput.max = totalPages;
                
                // Show UI
                dropZone.classList.add('d-none');
                convertArea.classList.remove('d-none');
                
                // Re-init canvas sizes now that it is visible
                setTimeout(resizeCanvas, 50);

            } catch (err) {
                console.error(err);
                showError('Could not read this PDF. It may be broken or heavily encrypted.');
                resetUI();
            }
        }

        function showError(msg) {
            errorMessage.textContent = msg;
            errorMessage.classList.remove('d-none');
        }

        btnAction.addEventListener('click', async () => {
            if (!selectedFile) return;
            if (signaturePad.isEmpty()) {
                return showError("Please draw your signature first.");
            }
            
            let targetPage = parseInt(signPageInput.value);
            if (isNaN(targetPage) || targetPage < 1 || targetPage > totalPages) {
                return showError(`Invalid page number. Must be between 1 and ${totalPages}.`);
            }
            
            errorMessage.classList.add('d-none');
            
            try {
                btnAction.disabled = true;
                btnSpinner.classList.remove('d-none');
                
                // 1. Get Signature as PNG Data URL
                const sigDataUrl = signaturePad.toDataURL("image/png");
                
                // 2. Load PDF
                const arrayBuffer = await selectedFile.arrayBuffer();
                const { PDFDocument } = PDFLib;
                const pdfDoc = await PDFDocument.load(arrayBuffer);
                
                // 3. Embed PNG
                const pngImage = await pdfDoc.embedPng(sigDataUrl);
                
                // Scale signature down (standard signature size approx 150-200px width equivalent)
                const sigDims = pngImage.scale(0.3); // Configurable depending on exact canvas size
                
                // 4. Get Target Page (0-indexed)
                const pages = pdfDoc.getPages();
                const page = pages[targetPage - 1];
                const { width, height } = page.getSize();
                
                const pos = signPositionSelect.value;
                const margin = 50;
                
                let drawX = 0;
                let drawY = 0;
                
                // X coordinates
                if (pos.includes('left')) drawX = margin;
                else if (pos.includes('right')) drawX = width - sigDims.width - margin;
                else drawX = (width / 2) - (sigDims.width / 2); // center
                
                // Y coordinates
                if (pos.includes('bottom')) drawY = margin;
                else if (pos.includes('top')) drawY = height - sigDims.height - margin;
                else drawY = (height / 2) - (sigDims.height / 2); // center

                // 5. Draw the image
                page.drawImage(pngImage, {
                    x: drawX,
                    y: drawY,
                    width: sigDims.width,
                    height: sigDims.height,
                });

                // 6. Save and Download
                const pdfBytes = await pdfDoc.save();
                
                const blob = new Blob([pdfBytes], { type: 'application/pdf' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                const originalName = selectedFile.name.replace('.pdf', '');
                a.download = `${originalName}_signed.pdf`;
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
