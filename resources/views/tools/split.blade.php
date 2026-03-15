@extends('layouts.app')

@section('title', __('messages.split_pdf') . ' - ToolPDF')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-retro border-warning mb-4">
                <div class="card-header bg-warning text-dark">
                    <h3 class="card-title mb-0"><i class="bi bi-layout-split me-2"></i>{{ __('messages.split_pdf') }}</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted">{{ __('messages.split_pdf_desc') }}</p>

                    <div id="drop-zone" class="border border-2 border-warning border-dashed rounded p-5 text-center mb-4 bg-light" style="cursor: pointer;">
                        <i class="bi bi-cloud-arrow-up display-1 text-warning mb-3"></i>
                        <h4>Select a PDF file</h4>
                        <p class="text-muted">or drop a PDF here</p>
                        <input type="file" id="file-input" accept="application/pdf" class="d-none">
                    </div>

                    <div id="file-info" class="alert alert-secondary d-none mb-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-file-pdf text-danger fs-4 me-2"></i>
                                <strong id="file-name"></strong>
                                <span class="badge bg-dark ms-2" id="page-count"></span>
                            </div>
                            <button class="btn btn-sm btn-outline-danger" id="btn-remove">X</button>
                        </div>
                    </div>

                    <div id="split-options" class="d-none mb-4 p-3 border rounded bg-light">
                        <h5 class="mb-3">{{ __('messages.split_mode') }}</h5>
                        
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="splitMode" id="mode-all" value="all" checked>
                            <label class="form-check-label" for="mode-all">
                                {{ __('messages.split_all_pages') }}
                            </label>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="splitMode" id="mode-range" value="range">
                            <label class="form-check-label" for="mode-range">
                                {{ __('messages.split_by_range') }}
                            </label>
                        </div>
                        
                        <div id="ranges-container" class="d-none">
                            <div id="ranges-list">
                                <!-- Ranges will be appended here -->
                            </div>
                            <button class="btn btn-sm btn-outline-primary mt-2" id="btn-add-range">
                                <i class="bi bi-plus-circle me-1"></i>{{ __('messages.add_range') }}
                            </button>
                        </div>
                    </div>

                    <div class="text-center">
                        <button id="btn-action" class="btn btn-lg btn-warning text-dark shadow-sm px-5 fw-bold" disabled>
                            <span id="btn-text"><i class="bi bi-scissors me-2"></i>{{ __('messages.split_pdf') }}</span>
                            <span id="btn-spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        </button>
                    </div>
                    
                    <p class="text-muted small text-center mt-3 d-none" id="dl-info">{{ __('messages.split_download_info') }}</p>

                    <div id="error-message" class="alert alert-danger mt-3 d-none"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<template id="range-template">
    <div class="row g-2 align-items-center mb-2 range-row">
        <div class="col-auto">
            <label class="col-form-label">{{ __('messages.range_start') }}</label>
        </div>
        <div class="col">
            <input type="number" class="form-control range-start" min="1" value="1">
        </div>
        <div class="col-auto">
            <label class="col-form-label">{{ __('messages.range_end') }}</label>
        </div>
        <div class="col">
            <input type="number" class="form-control range-end" min="1" value="1">
        </div>
        <div class="col-auto">
            <button class="btn btn-outline-danger btn-remove-range" title="{{ __('messages.remove_range') }}">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </div>
</template>

<script src="https://unpkg.com/pdf-lib@1.17.1/dist/pdf-lib.min.js"></script>
<!-- Using JSZip to optionally zip the split files could be good, but we'll download one by one for simplicity if low page count, or zip if we add jszip later. -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const dropZone = document.getElementById('drop-zone');
        const fileInput = document.getElementById('file-input');
        const fileInfo = document.getElementById('file-info');
        const fileName = document.getElementById('file-name');
        const pageCountSpan = document.getElementById('page-count');
        const btnRemove = document.getElementById('btn-remove');
        const btnAction = document.getElementById('btn-action');
        const btnText = document.getElementById('btn-text');
        const btnSpinner = document.getElementById('btn-spinner');
        const dlInfo = document.getElementById('dl-info');
        const errorMessage = document.getElementById('error-message');
        
        const splitOptions = document.getElementById('split-options');
        const modeAll = document.getElementById('mode-all');
        const modeRange = document.getElementById('mode-range');
        const rangesContainer = document.getElementById('ranges-container');
        const rangesList = document.getElementById('ranges-list');
        const btnAddRange = document.getElementById('btn-add-range');
        const rangeTemplate = document.getElementById('range-template');
        
        let selectedFile = null;
        let pdfDocCache = null;
        let totalPages = 0;

        // Toggle Split Modes
        modeAll.addEventListener('change', () => {
            if (modeAll.checked) {
                rangesContainer.classList.add('d-none');
            }
        });
        
        modeRange.addEventListener('change', () => {
            if (modeRange.checked) {
                rangesContainer.classList.remove('d-none');
                if (rangesList.children.length === 0) {
                    addRangeRow();
                }
            }
        });

        // Add Range
        btnAddRange.addEventListener('click', addRangeRow);

        function addRangeRow() {
            const clone = rangeTemplate.content.cloneNode(true);
            const row = clone.querySelector('.range-row');
            
            const inputStart = row.querySelector('.range-start');
            const inputEnd = row.querySelector('.range-end');
            
            // Set sensible defaults if available
            if (totalPages > 0) {
                inputStart.max = totalPages;
                inputEnd.max = totalPages;
            }
            
            row.querySelector('.btn-remove-range').addEventListener('click', function() {
                row.remove();
                if (rangesList.children.length === 0) {
                    addRangeRow(); // Must have at least one range
                }
            });
            
            rangesList.appendChild(row);
        }

        // File drag and drop logic
        dropZone.addEventListener('click', () => fileInput.click());

        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('border-primary');
        });

        dropZone.addEventListener('dragleave', (e) => {
            e.preventDefault();
            dropZone.classList.remove('border-primary');
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('border-primary');
            if (e.dataTransfer.files.length > 0) {
                handleFile(e.dataTransfer.files[0]);
            }
        });

        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                handleFile(e.target.files[0]);
            }
        });
        
        btnRemove.addEventListener('click', () => {
            selectedFile = null;
            pdfDocCache = null;
            totalPages = 0;
            fileInput.value = '';
            fileInfo.classList.add('d-none');
            dropZone.classList.remove('d-none');
            splitOptions.classList.add('d-none');
            btnAction.disabled = true;
            dlInfo.classList.add('d-none');
        });

        async function handleFile(file) {
            errorMessage.classList.add('d-none');
            
            if (file.type !== 'application/pdf') {
                showError('Please select a valid PDF file.');
                return;
            }

            try {
                // Pre-load to count pages
                const arrayBuffer = await file.arrayBuffer();
                const { PDFDocument } = PDFLib;
                pdfDocCache = await PDFDocument.load(arrayBuffer);
                totalPages = pdfDocCache.getPageCount();
                
                selectedFile = file;
                fileName.textContent = file.name;
                pageCountSpan.textContent = totalPages + ' Pages';
                
                fileInfo.classList.remove('d-none');
                dropZone.classList.add('d-none');
                splitOptions.classList.remove('d-none');
                btnAction.disabled = false;
                dlInfo.classList.remove('d-none');
                
                // Update inputs if they exist
                document.querySelectorAll('.range-start, .range-end').forEach(el => {
                    el.max = totalPages;
                });
                
            } catch (err) {
                showError('Could not process this PDF: ' + err.message);
            }
        }

        function showError(msg) {
            errorMessage.textContent = msg;
            errorMessage.classList.remove('d-none');
        }

        btnAction.addEventListener('click', async () => {
            if (!pdfDocCache) return;
            showError(""); // clear errors
            
            try {
                btnAction.disabled = true;
                btnSpinner.classList.remove('d-none');
                
                const { PDFDocument } = PDFLib;
                const zip = new JSZip();
                const originalName = selectedFile.name.replace('.pdf', '');

                if (modeAll.checked) {
                    // Split ALL pages
                    for (let i = 0; i < totalPages; i++) {
                        const newPdf = await PDFDocument.create();
                        const [copiedPage] = await newPdf.copyPages(pdfDocCache, [i]);
                        newPdf.addPage(copiedPage);
                        const pdfBytes = await newPdf.save();
                        zip.file(`${originalName}_page_${i+1}.pdf`, pdfBytes);
                    }
                } else {
                    // Split BY RANGE
                    let rangesValid = true;
                    const rows = rangesList.querySelectorAll('.range-row');
                    
                    for (let r = 0; r < rows.length; r++) {
                        const row = rows[r];
                        let start = parseInt(row.querySelector('.range-start').value);
                        let end = parseInt(row.querySelector('.range-end').value);
                        
                        // Validation
                        if (isNaN(start) || isNaN(end) || start < 1 || start > totalPages || end < 1 || end > totalPages || start > end) {
                            showError(`Invalid range at row ${r + 1}. Please ensure Start <= End, and pages are within 1 to ${totalPages}.`);
                            rangesValid = false;
                            break;
                        }
                        
                        // 0-indexed for pdf-lib
                        start = start - 1;
                        end = end - 1; 
                        
                        const indices = [];
                        for (let i = start; i <= end; i++) {
                            indices.push(i);
                        }
                        
                        const newPdf = await PDFDocument.create();
                        const copiedPages = await newPdf.copyPages(pdfDocCache, indices);
                        copiedPages.forEach(page => newPdf.addPage(page));
                        
                        const pdfBytes = await newPdf.save();
                        zip.file(`${originalName}_range_${start + 1}-${end + 1}.pdf`, pdfBytes);
                    }
                    
                    if (!rangesValid) {
                        return; // Stop processing
                    }
                }

                // Generate ZIP and Download
                const zipBlob = await zip.generateAsync({type: "blob"});
                const url = URL.createObjectURL(zipBlob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `${originalName}_split.zip`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
                
                btnRemove.click(); // reset UI on success
                
            } catch (err) {
                console.error(err);
                showError('Error during PDF split: ' + err.message);
            } finally {
                btnAction.disabled = false;
                btnSpinner.classList.add('d-none');
            }
        });
    });
</script>
@endsection
