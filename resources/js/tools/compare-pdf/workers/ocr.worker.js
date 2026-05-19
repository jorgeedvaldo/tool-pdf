// OCR worker — Tesseract.js manages its own internal workers,
// so this module simply re-exports the API for dynamic import isolation.
export { createWorker } from 'tesseract.js';
