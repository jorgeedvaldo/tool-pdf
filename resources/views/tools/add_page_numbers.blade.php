@extends('layouts.app')

@section('title', __('messages.add_page_numbers') . ' - ToolPDF')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow border-0">
                <div class="card-header bg-secondary text-white py-3">
                    <h4 class="mb-0 fw-bold"><i class="bi bi-123 me-2"></i>{{ __('messages.add_page_numbers') }}</h4>
                </div>
                
                <div class="card-body p-4 bg-light">
                    <!-- Setup Area -->
                    <div id="drop-zone" class="drop-zone border rounded p-5 text-center bg-white shadow-sm mb-4" style="border: 2px dashed #6c757d !important; cursor: pointer; transition: all 0.3s;">
                        <i class="bi bi-123 mb-3 text-secondary" style="font-size: 4rem;"></i>
                        <h5 class="fw-bold">{{ __('messages.add_page_numbers') }}</h5>
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

                        <div class="bg-white p-4 border rounded shadow-sm mb-4">
                            <h6 class="fw-bold mb-3"><i class="bi bi-sliders me-2 text-secondary"></i>Settings</h6>
                            
                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="form-label text-muted small fw-bold">Position</label>
                                    <select id="pn-position" class="form-select">
                                        <option value="bottom-center" selected>Bottom Center</option>
                                        <option value="bottom-right">Bottom Right</option>
                                        <option value="bottom-left">Bottom Left</option>
                                        <option value="top-center">Top Center</option>
                                        <option value="top-right">Top Right</option>
                                        <option value="top-left">Top Left</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label text-muted small fw-bold">First Page Number</label>
                                    <input type="number" id="pn-start" class="form-control" value="1" min="1">
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="text-center">
                            <button type="button" id="btn-action" class="btn btn-lg px-5 shadow fw-bold text-white btn-secondary">
                                <i class="bi bi-check2-circle me-2" id="btn-icon"></i> 
                                <span id="btn-text">Add Page Numbers</span>
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
<script src="https://unpkg.com/@pdf-lib/fontkit/dist/fontkit.umd.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const dropZone = document.getElementById('drop-zone');
        const fileInput = document.getElementById('file-input');
        const convertArea = document.getElementById('convert-area');
        const fileNameDiv = document.getElementById('file-name');
        
        const pnPositionSelect = document.getElementById('pn-position');
        const pnStartInput = document.getElementById('pn-start');
        
        const btnRemove = document.getElementById('btn-remove');
        const btnAction = document.getElementById('btn-action');
        const btnSpinner = document.getElementById('btn-spinner');
        const errorMessage = document.getElementById('error-message');

        let selectedFile = null;

        dropZone.addEventListener('click', () => fileInput.click());
        dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.style.borderColor = '#6c757d'; });
        dropZone.addEventListener('dragleave', (e) => { e.preventDefault(); dropZone.style.borderColor = '#6c757d'; });
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

        async function handleFile(file) {
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
            
            errorMessage.classList.add('d-none');
            
            try {
                btnAction.disabled = true;
                btnSpinner.classList.remove('d-none');
                
                const arrayBuffer = await selectedFile.arrayBuffer();
                const { PDFDocument, rgb, StandardFonts } = PDFLib;
                
                const pdfDoc = await PDFDocument.load(arrayBuffer);
                const helveticaFont = await pdfDoc.embedFont(StandardFonts.Helvetica);
                
                const pages = pdfDoc.getPages();
                let startNumber = parseInt(pnStartInput.value) || 1;
                const position = pnPositionSelect.value;
                const fontSize = 12;
                const margin = 30; // 30 units from the edge
                
                // Add page number on each page
                for (let i = 0; i < pages.length; i++) {
                    const page = pages[i];
                    const { width, height } = page.getSize();
                    const text = String(startNumber + i);
                    const textWidth = helveticaFont.widthOfTextAtSize(text, fontSize);
                    
                    let x, y;
                    
                    // Logic to position roughly based on size and margin
                    if (position.includes('bottom')) {
                        y = margin;
                    } else {
                        y = height - margin - fontSize;
                    }
                    
                    if (position.includes('left')) {
                        x = margin;
                    } else if (position.includes('right')) {
                        x = width - margin - textWidth;
                    } else {
                        // center
                        x = width / 2 - textWidth / 2;
                    }
                    
                    page.drawText(text, {
                        x: x,
                        y: y,
                        size: fontSize,
                        font: helveticaFont,
                        color: rgb(0, 0, 0),
                    });
                }

                const pdfBytes = await pdfDoc.save();
                
                const blob = new Blob([pdfBytes], { type: 'application/pdf' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                const originalName = selectedFile.name.replace('.pdf', '');
                a.download = `${originalName}_numbered.pdf`;
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
