@extends('layouts.app')

@section('title', __('messages.ocr_pdf') . ' - ToolPDF')

@section('content')
<style>
    .drop-zone { border: 2px dashed #0d6efd !important; cursor: pointer; transition: all 0.3s; }
    .drop-zone:hover { background-color: #f8f9fa; }
    .image-preview { width: 100%; height: auto; max-height: 200px; object-fit: contain; }
</style>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow border-0">
                <div class="card-header bg-primary text-white py-3">
                    <h4 class="mb-0 fw-bold"><i class="bi bi-file-earmark-font me-2"></i>{{ __('messages.ocr_pdf') }}</h4>
                </div>
                
                <div class="card-body p-4 bg-light">
                    <!-- Setup Area -->
                    <div id="drop-zone" class="drop-zone border rounded p-5 text-center bg-white shadow-sm mb-4">
                        <i class="bi bi-file-earmark-font mb-3 text-primary" style="font-size: 4rem;"></i>
                        <h5 class="fw-bold">{{ __('messages.ocr_pdf') }}</h5>
                        <p class="text-muted">Click or drag & drop a PDF file here</p>
                        <input type="file" id="file-input" class="d-none" accept=".pdf">
                    </div>

                    <!-- Processing/Convert Area -->
                    <div id="convert-area" class="d-none">
                        <div class="d-flex justify-content-between align-items-center mb-4 p-3 bg-white border rounded shadow-sm">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-file-earmark-pdf fs-3 text-primary me-3"></i>
                                <div>
                                    <h6 class="mb-0 fw-bold text-truncate" style="max-width: 250px;" id="file-name">filename.pdf</h6>
                                    <small class="text-muted"><span id="page-count">0</span> Pages</small>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-danger btn-sm rounded-circle px-2" id="btn-remove" title="Remove PDF">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>

                        <!-- Options -->
                        <div class="bg-white p-4 border rounded shadow-sm mb-4">
                            <h6 class="fw-bold mb-3">OCR Options</h6>
                            <div class="mb-3">
                                <label class="form-label text-muted">Document Language:</label>
                                <select id="ocr-lang" class="form-select">
                                    <option value="eng">English</option>
                                    <option value="por">Portuguese</option>
                                    <option value="spa">Spanish</option>
                                    <option value="fra">French</option>
                                    <option value="deu">German</option>
                                </select>
                            </div>
                        </div>

                        <!-- Progress Bar (Visible during processing) -->
                        <div id="progress-container" class="mb-4 d-none text-center">
                            <h6 class="text-muted mb-2" id="progress-text">Processing...</h6>
                            <div class="progress" style="height: 15px;">
                                <div id="progress-bar" class="progress-bar bg-primary progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;"></div>
                            </div>
                            <small class="text-secondary mt-2 d-block" id="progress-detail"></small>
                        </div>

                        <!-- Actions -->
                        <div class="text-center">
                            <button type="button" id="btn-action" class="btn btn-lg px-5 shadow fw-bold text-white btn-primary">
                                <i class="bi bi-magic me-2"></i> 
                                <span id="btn-text">Start OCR</span>
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
<script src="https://unpkg.com/pdf-lib@1.17.1/dist/pdf-lib.min.js"></script>
<script src="https://unpkg.com/tesseract.js@v4.1.1/dist/tesseract.min.js"></script>
<script async src="https://docs.opencv.org/4.8.0/opencv.js" onload="window.cvReady = true;"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const dropZone = document.getElementById('drop-zone');
        const fileInput = document.getElementById('file-input');
        const convertArea = document.getElementById('convert-area');
        const fileNameDiv = document.getElementById('file-name');
        const pageCountSpan = document.getElementById('page-count');
        const btnRemove = document.getElementById('btn-remove');
        const btnAction = document.getElementById('btn-action');
        const btnSpinner = document.getElementById('btn-spinner');
        const btnText = document.getElementById('btn-text');
        const errorMessage = document.getElementById('error-message');
        const progressContainer = document.getElementById('progress-container');
        const progressBar = document.getElementById('progress-bar');
        const progressText = document.getElementById('progress-text');
        const progressDetail = document.getElementById('progress-detail');
        const ocrLang = document.getElementById('ocr-lang');

        let selectedFile = null;

        dropZone.addEventListener('click', () => fileInput.click());
        dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.style.borderColor = '#0d6efd'; });
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
            progressContainer.classList.add('d-none');
            progressBar.classList.replace('bg-success', 'bg-primary');
            progressBar.style.width = '0%';
            btnAction.disabled = false;
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
                
                // Fast check page count
                const arrayBuffer = await file.arrayBuffer();
                const pdfDoc = await pdfjsLib.getDocument(new Uint8Array(arrayBuffer)).promise;
                pageCountSpan.textContent = pdfDoc.numPages;

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

        btnAction.addEventListener('click', async () => {
            if (!selectedFile) return;
            errorMessage.classList.add('d-none');
            
            try {
                btnAction.disabled = true;
                btnSpinner.classList.remove('d-none');
                btnText.textContent = "Processing...";
                progressContainer.classList.remove('d-none');
                progressBar.classList.replace('bg-success', 'bg-primary');
                
                const arrayBuffer = await selectedFile.arrayBuffer();
                const sourcePdf = await pdfjsLib.getDocument(new Uint8Array(arrayBuffer)).promise;
                const totalPages = sourcePdf.numPages;

                const { PDFDocument, rgb } = PDFLib;
                const newPdfDoc = await PDFDocument.create();
                
                const lang = ocrLang.value;

                progressText.textContent = "Initializing OCR engine...";
                const worker = await Tesseract.createWorker({
                    logger: m => {
                        if (m.status === 'recognizing text') {
                            const p = Math.round(m.progress * 100);
                            progressDetail.textContent = `OCR Progress: ${p}%`;
                        }
                    }
                });
                await worker.loadLanguage(lang);
                await worker.initialize(lang);

                for (let i = 1; i <= totalPages; i++) {
                    progressText.textContent = `Processing page ${i} of ${totalPages}...`;
                    progressBar.style.width = `${((i-1)/totalPages)*100}%`;
                    
                    const page = await sourcePdf.getPage(i);
                    const viewport = page.getViewport({ scale: 2.0 });
                    const canvas = document.createElement('canvas');
                    const context = canvas.getContext('2d');
                    canvas.width = viewport.width;
                    canvas.height = viewport.height;

                    await page.render({ canvasContext: context, viewport: viewport }).promise;
                    
                    // OpenCV Enhancement
                    if (window.cvReady && typeof cv !== 'undefined' && cv.Mat) {
                        try {
                            progressDetail.textContent = `Enhancing image quality for page ${i}...`;
                            // Give UI time to update
                            await new Promise(r => setTimeout(r, 10));
                            
                            let src = cv.imread(canvas);
                            cv.cvtColor(src, src, cv.COLOR_RGBA2GRAY, 0);
                            
                            // Increase contrast and threshold
                            cv.threshold(src, src, 150, 255, cv.THRESH_BINARY);
                            
                            // Replace canvas image
                            cv.imshow(canvas, src);
                            src.delete();
                        } catch(e) {
                            console.warn("OpenCV enhancement failed:", e);
                        }
                    }

                    const dataUrl = canvas.toDataURL('image/jpeg', 1.0);

                    const jpgImageBytes = await fetch(dataUrl).then(res => res.arrayBuffer());
                    const jpgImage = await newPdfDoc.embedJpg(jpgImageBytes);
                    
                    const pdfPage = newPdfDoc.addPage([jpgImage.width, jpgImage.height]);
                    pdfPage.drawImage(jpgImage, {
                        x: 0,
                        y: 0,
                        width: jpgImage.width,
                        height: jpgImage.height,
                    });

                    progressDetail.textContent = `Running OCR on page ${i}...`;
                    const { data: { words } } = await worker.recognize(dataUrl);
                    
                    const font = await newPdfDoc.embedFont(PDFLib.StandardFonts.Helvetica);
                    
                    words.forEach(word => {
                        const bbox = word.bbox;
                        const text = word.text;
                        
                        const x = bbox.x0;
                        const y = jpgImage.height - bbox.y1;
                        const width = bbox.x1 - bbox.x0;
                        const height = bbox.y1 - bbox.y0;
                        
                        const fontSize = height;
                        
                        // heuristic to avoid drawing zero height/width text
                        if (fontSize > 1) {
                            pdfPage.drawText(text, {
                                x: x,
                                y: y + height * 0.2,
                                size: fontSize,
                                font: font,
                                color: rgb(0,0,0),
                                opacity: 0, 
                            });
                        }
                    });

                    progressBar.style.width = `${(i/totalPages)*100}%`;
                }

                await worker.terminate();
                
                progressText.textContent = "Finalizing PDF...";
                progressDetail.textContent = "";
                const pdfBytes = await newPdfDoc.save();
                
                const blob = new Blob([pdfBytes], { type: 'application/pdf' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                const originalName = selectedFile.name.replace('.pdf', '');
                a.download = `${originalName}_ocr.pdf`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
                
                progressText.textContent = "Done!";
                progressBar.style.width = '100%';
                progressBar.classList.replace('bg-primary', 'bg-success');
                
            } catch (err) {
                console.error(err);
                if (err && err.message) {
                    showError('Error processing PDF: ' + err.message);
                } else if (typeof err === 'string') {
                    showError('Error processing PDF: ' + err);
                } else {
                    showError('Error processing PDF: Unknown error occurred.');
                }
            } finally {
                btnAction.disabled = false;
                btnSpinner.classList.add('d-none');
                btnText.textContent = "Start OCR";
            }
        });
    });
</script>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            @if(Lang::has('messages.article_ocr_pdf_content'))
            <div class="card shadow-sm border-0 bg-white">
                <div class="card-body p-4 p-md-5 text-start">
                    {!! __('messages.article_ocr_pdf_content') !!}
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
