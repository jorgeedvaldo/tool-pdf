@extends('layouts.app')

@section('title', __('messages.protect_pdf') . ' - ToolPDF')

@section('content')
<style>
    .drop-zone { border: 2px dashed #6c757d !important; cursor: pointer; transition: all 0.3s; }
    .drop-zone:hover { background-color: #f8f9fa; }
</style>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card shadow border-0">
                <div class="card-header bg-secondary text-white py-3">
                    <h4 class="mb-0 fw-bold"><i class="bi bi-lock me-2"></i>{{ __('messages.protect_pdf') }}</h4>
                </div>
                
                <div class="card-body p-4 bg-light">
                    <!-- Setup Area -->
                    <div id="drop-zone" class="drop-zone border rounded p-5 text-center bg-white shadow-sm mb-4">
                        <i class="bi bi-lock mb-3 text-secondary" style="font-size: 4rem;"></i>
                        <h5 class="fw-bold">{{ __('messages.protect_pdf') }}</h5>
                        <p class="text-muted">Click or drag & drop a PDF file here</p>
                        <input type="file" id="file-input" class="d-none" accept=".pdf">
                    </div>

                    <!-- Processing/Convert Area -->
                    <div id="convert-area" class="d-none">
                        
                        <div class="d-flex justify-content-between align-items-center mb-4 p-3 bg-white border rounded shadow-sm">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-file-earmark-pdf fs-3 text-secondary me-3"></i>
                                <div>
                                    <h6 class="mb-0 fw-bold text-truncate" style="max-width: 250px;" id="file-name">filename.pdf</h6>
                                    <small class="text-muted" id="page-count">0 Pages</small>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-danger btn-sm rounded-circle px-2" id="btn-remove" title="Remove PDF">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>

                        <div class="bg-white p-4 border rounded shadow-sm mb-4">
                            <h6 class="fw-bold mb-3"><i class="bi bi-key me-2 text-warning"></i>Set Password</h6>
                            <p class="small text-muted mb-3">Enter the password you want to use to protect this PDF document.</p>
                            <input type="password" id="pdf-password" class="form-control mb-2" placeholder="{{ __('messages.password') }}">
                        </div>

                        <!-- Progress Bar Container -->
                        <div id="progress-container" class="mb-4 d-none">
                            <div class="d-flex justify-content-between mb-1">
                                <small class="text-muted" id="progress-text">Encrypting...</small>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div id="progress-bar" class="progress-bar bg-secondary progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;"></div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="text-center">
                            <button type="button" id="btn-action" class="btn btn-lg px-5 shadow fw-bold text-white btn-secondary">
                                <i class="bi bi-shield-lock-fill me-2" id="btn-icon"></i> 
                                <span id="btn-text">{{ __('messages.protect_pdf') }}</span>
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
        const pageCountSpan = document.getElementById('page-count');
        const passwordInput = document.getElementById('pdf-password');
        const btnRemove = document.getElementById('btn-remove');
        const btnAction = document.getElementById('btn-action');
        const btnSpinner = document.getElementById('btn-spinner');
        const errorMessage = document.getElementById('error-message');
        const progressContainer = document.getElementById('progress-container');
        const progressBar = document.getElementById('progress-bar');
        const progressText = document.getElementById('progress-text');

        let selectedFile = null;
        let pdfDoc = null;

        dropZone.addEventListener('click', () => fileInput.click());
        dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.style.borderColor = '#6c757d'; });
        dropZone.addEventListener('dragleave', (e) => { e.preventDefault(); dropZone.style.borderColor = '#6c757d'; });
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.style.borderColor = '#6c757d';
            if (e.dataTransfer.files.length > 0) handleFile(e.dataTransfer.files[0]);
        });
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) handleFile(e.target.files[0]);
        });
        
        btnRemove.addEventListener('click', resetUI);
        
        passwordInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') btnAction.click();
        });

        function resetUI() {
            selectedFile = null;
            pdfDoc = null;
            fileInput.value = '';
            passwordInput.value = '';
            convertArea.classList.add('d-none');
            dropZone.classList.remove('d-none');
            errorMessage.classList.add('d-none');
            progressContainer.classList.add('d-none');
            progressBar.style.width = '0%';
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
                passwordInput.focus();
                
                const arrayBuffer = await file.arrayBuffer();
                pdfDoc = await pdfjsLib.getDocument(new Uint8Array(arrayBuffer)).promise;
                pageCountSpan.textContent = pdfDoc.numPages + " Pages";
            } catch (err) {
                showError('Could not read PDF: ' + err.message);
                resetUI();
            }
        }

        function showError(msg) {
            errorMessage.textContent = msg;
            errorMessage.classList.remove('d-none');
        }

        btnAction.addEventListener('click', async () => {
            if (!selectedFile || !pdfDoc) return;
            const pw = passwordInput.value;
            if (!pw) {
                showError('Please enter a password to protect the document.');
                return;
            }
            
            errorMessage.classList.add('d-none');
            
            try {
                btnAction.disabled = true;
                btnSpinner.classList.remove('d-none');
                progressContainer.classList.remove('d-none');
                progressBar.style.width = '0%';
                progressText.textContent = "Encrypting PDF...";
                
                const totalPages = pdfDoc.numPages;
                const { jsPDF } = window.jspdf;
                
                const protectedDoc = new jsPDF({
                    orientation: 'portrait',
                    unit: 'px',
                    format: 'a4',
                    encryption: {
                        userPassword: pw,
                        ownerPassword: pw,
                        userPermissions: ["print", "modify", "copy", "annot-forms"]
                    }
                });
                
                for (let i = 1; i <= totalPages; i++) {
                    progressBar.style.width = `${(i/totalPages)*100}%`;
                    const page = await pdfDoc.getPage(i);
                    const viewport = page.getViewport({ scale: 2.0 });
                    
                    const canvas = document.createElement('canvas');
                    const context = canvas.getContext('2d');
                    canvas.width = viewport.width;
                    canvas.height = viewport.height;
                    
                    await page.render({ canvasContext: context, viewport: viewport }).promise;
                    const imgData = canvas.toDataURL('image/jpeg', 0.9);
                    
                    if (i > 1) {
                        protectedDoc.addPage([viewport.width, viewport.height], viewport.width > viewport.height ? 'landscape' : 'portrait');
                        protectedDoc.setPage(i);
                    } else {
                        // first page sets format
                    }
                    
                    protectedDoc.addImage(imgData, 'JPEG', 0, 0, viewport.width, viewport.height);
                }

                progressText.textContent = "Finalizing...";
                const blob = protectedDoc.output('blob');
                
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                const originalName = selectedFile.name.replace('.pdf', '');
                a.download = `${originalName}_protected.pdf`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
                
                setTimeout(resetUI, 1500);
                
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
