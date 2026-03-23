@extends('layouts.app')

@section('title', __('messages.merge_pdf') . ' - ToolPDF')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-retro border-primary mb-4">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title mb-0"><i class="bi bi-file-earmark-plus me-2"></i>{{ __('messages.merge_pdf') }}</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted">{{ __('messages.merge_pdf_desc') }}</p>

                    <div id="drop-zone" class="border border-2 border-primary border-dashed rounded p-5 text-center mb-4 bg-light" style="cursor: pointer;">
                        <i class="bi bi-cloud-arrow-up display-1 text-primary mb-3"></i>
                        <h4>Select PDF files</h4>
                        <p class="text-muted">or drop PDFs here</p>
                        <input type="file" id="file-input" multiple accept="application/pdf" class="d-none">
                    </div>

                    <ul id="file-list" class="list-group mb-4"></ul>

                    <div class="text-center">
                        <button id="btn-action" class="btn btn-lg btn-primary shadow-sm px-5" disabled>
                            <span id="btn-text"><i class="bi bi-play-fill me-2"></i>{{ __('messages.merge_pdf') }}</span>
                            <span id="btn-spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        </button>
                    </div>

                    <div id="error-message" class="alert alert-danger mt-3 d-none"></div>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <!-- SEO Article -->
            <div class="card shadow-sm border-0 bg-white">
                <div class="card-body p-4 p-md-5 text-start">
                    {!! __('messages.article_merge_content') !!}
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
        const fileList = document.getElementById('file-list');
        const btnAction = document.getElementById('btn-action');
        const btnText = document.getElementById('btn-text');
        const btnSpinner = document.getElementById('btn-spinner');
        const errorMessage = document.getElementById('error-message');
        
        let selectedFiles = [];

        dropZone.addEventListener('click', () => fileInput.click());

        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('bg-white');
            dropZone.classList.remove('bg-light');
        });

        dropZone.addEventListener('dragleave', (e) => {
            e.preventDefault();
            dropZone.classList.add('bg-light');
            dropZone.classList.remove('bg-white');
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.add('bg-light');
            dropZone.classList.remove('bg-white');
            handleFiles(e.dataTransfer.files);
        });

        fileInput.addEventListener('change', (e) => {
            handleFiles(e.target.files);
            fileInput.value = ''; // Reset input to allow selecting the same file again if needed
        });

        function handleFiles(files) {
            errorMessage.classList.add('d-none');
            const newFiles = Array.from(files).filter(file => file.type === 'application/pdf');
            
            if (newFiles.length === 0) {
                showError('Please select valid PDF files.');
                return;
            }

            selectedFiles = [...selectedFiles, ...newFiles];
            updateFileList();
        }

        function updateFileList() {
            fileList.innerHTML = '';
            
            selectedFiles.forEach((file, index) => {
                const li = document.createElement('li');
                li.className = 'list-group-item d-flex justify-content-between align-items-center shadow-sm mb-2 rounded border';
                li.innerHTML = `
                    <div class="text-truncate">
                        <i class="bi bi-file-pdf text-danger me-2"></i>
                        ${file.name}
                    </div>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-secondary me-1" onclick="moveUp(${index})"><i class="bi bi-arrow-up"></i></button>
                        <button type="button" class="btn btn-sm btn-outline-secondary me-2" onclick="moveDown(${index})"><i class="bi bi-arrow-down"></i></button>
                        <button type="button" class="btn btn-sm btn-danger" onclick="removeFile(${index})"><i class="bi bi-x"></i></button>
                    </div>
                `;
                fileList.appendChild(li);
            });

            btnAction.disabled = selectedFiles.length < 2;
        }

        window.removeFile = function(index) {
            selectedFiles.splice(index, 1);
            updateFileList();
        }

        window.moveUp = function(index) {
            if (index > 0) {
                const temp = selectedFiles[index - 1];
                selectedFiles[index - 1] = selectedFiles[index];
                selectedFiles[index] = temp;
                updateFileList();
            }
        }

        window.moveDown = function(index) {
            if (index < selectedFiles.length - 1) {
                const temp = selectedFiles[index + 1];
                selectedFiles[index + 1] = selectedFiles[index];
                selectedFiles[index] = temp;
                updateFileList();
            }
        }

        function showError(msg) {
            errorMessage.textContent = msg;
            errorMessage.classList.remove('d-none');
        }

        btnAction.addEventListener('click', async () => {
            if (selectedFiles.length < 2) return;
            
            try {
                btnAction.disabled = true;
                btnSpinner.classList.remove('d-none');
                
                const { PDFDocument } = PDFLib;
                const mergedPdf = await PDFDocument.create();

                for (let file of selectedFiles) {
                    const arrayBuffer = await file.arrayBuffer();
                    const pdf = await PDFDocument.load(arrayBuffer);
                    const copiedPages = await mergedPdf.copyPages(pdf, pdf.getPageIndices());
                    copiedPages.forEach((page) => mergedPdf.addPage(page));
                }

                const mergedPdfFile = await mergedPdf.save();
                
                // Create download link
                const blob = new Blob([mergedPdfFile], { type: 'application/pdf' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'merged_document.pdf';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
                
                // Reset UI
                selectedFiles = [];
                updateFileList();
                
            } catch (err) {
                console.error(err);
                showError('Error during PDF merge: ' + err.message);
            } finally {
                btnAction.disabled = false;
                btnSpinner.classList.add('d-none');
            }
        });
    });
</script>
@endsection
