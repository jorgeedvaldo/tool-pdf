@extends('layouts.app')

@section('title', __('messages.unlock_pdf') . ' - ToolPDF')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card shadow border-0">
                <div class="card-header bg-success text-white py-3">
                    <h4 class="mb-0 fw-bold"><i class="bi bi-unlock me-2"></i>{{ __('messages.unlock_pdf') }}</h4>
                </div>
                
                <div class="card-body p-4 bg-light">
                    <!-- Setup Area -->
                    <div id="drop-zone" class="drop-zone border rounded p-5 text-center bg-white shadow-sm mb-4" style="border: 2px dashed #198754 !important; cursor: pointer; transition: all 0.3s;">
                        <i class="bi bi-unlock mb-3 text-success" style="font-size: 4rem;"></i>
                        <h5 class="fw-bold">{{ __('messages.unlock_pdf') }}</h5>
                        <p class="text-muted">Click or drag & drop a protected PDF file here</p>
                        <input type="file" id="file-input" class="d-none" accept=".pdf">
                    </div>

                    <!-- Processing/Convert Area -->
                    <div id="convert-area" class="d-none">
                        
                        <div class="d-flex justify-content-between align-items-center mb-4 p-3 bg-white border rounded shadow-sm">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-file-earmark-lock fs-3 text-danger me-3"></i>
                                <div>
                                    <h6 class="mb-0 fw-bold text-truncate" style="max-width: 250px;" id="file-name">filename.pdf</h6>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-danger btn-sm rounded-circle px-2" id="btn-remove" title="Remove PDF">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>

                        <div class="bg-white p-4 border rounded shadow-sm mb-4">
                            <h6 class="fw-bold mb-3"><i class="bi bi-key me-2 text-warning"></i>{{ __('messages.enter_password') }}</h6>
                            <p class="small text-muted mb-3">You must enter the correct password to unlock and save the decrypted PDF.</p>
                            <input type="password" id="pdf-password" class="form-control mb-2" placeholder="{{ __('messages.password') }}">
                        </div>

                        <!-- Actions -->
                        <div class="text-center">
                            <button type="button" id="btn-action" class="btn btn-lg px-5 shadow fw-bold text-white btn-success">
                                <i class="bi bi-unlock-fill me-2" id="btn-icon"></i> 
                                <span id="btn-text">{{ __('messages.unlock_pdf') }}</span>
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const dropZone = document.getElementById('drop-zone');
        const fileInput = document.getElementById('file-input');
        const convertArea = document.getElementById('convert-area');
        const fileNameDiv = document.getElementById('file-name');
        const passwordInput = document.getElementById('pdf-password');
        const btnRemove = document.getElementById('btn-remove');
        const btnAction = document.getElementById('btn-action');
        const btnSpinner = document.getElementById('btn-spinner');
        const errorMessage = document.getElementById('error-message');

        let selectedFile = null;

        dropZone.addEventListener('click', () => fileInput.click());
        dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.style.borderColor = '#198754'; });
        dropZone.addEventListener('dragleave', (e) => { e.preventDefault(); dropZone.style.borderColor = '#198754'; });
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            if (e.dataTransfer.files.length > 0) handleFile(e.dataTransfer.files[0]);
        });
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) handleFile(e.target.files[0]);
        });
        
        btnRemove.addEventListener('click', resetUI);
        
        // Listen for enter key in password
        passwordInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') btnAction.click();
        });

        function resetUI() {
            selectedFile = null;
            fileInput.value = '';
            passwordInput.value = '';
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
            passwordInput.focus();
        }

        function showError(msg) {
            errorMessage.textContent = msg;
            errorMessage.classList.remove('d-none');
        }

        btnAction.addEventListener('click', async () => {
            if (!selectedFile) return;
            const pw = passwordInput.value;
            if (!pw) {
                showError('Please enter the password first.');
                return;
            }
            
            errorMessage.classList.add('d-none');
            
            try {
                btnAction.disabled = true;
                btnSpinner.classList.remove('d-none');
                
                const arrayBuffer = await selectedFile.arrayBuffer();
                const { PDFDocument } = PDFLib;
                
                // Load with password. Will throw an error if wrong.
                const pdfDoc = await PDFDocument.load(arrayBuffer, { password: pw });
                
                // Saving it normally creates a decrypted, unlocked PDF
                const pdfBytes = await pdfDoc.save();
                
                const blob = new Blob([pdfBytes], { type: 'application/pdf' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                const originalName = selectedFile.name.replace('.pdf', '');
                a.download = `${originalName}_unlocked.pdf`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
                
                setTimeout(resetUI, 1500);
                
            } catch (err) {
                console.error(err);
                if(err.message.includes('password') || err.message.includes('encrypted')) {
                    showError('Incorrect password or file is heavily encrypted beyond standard support.');
                } else {
                    showError('Error processing PDF: ' + err.message);
                }
            } finally {
                btnAction.disabled = false;
                btnSpinner.classList.add('d-none');
            }
        });
    });
</script>
@endsection
