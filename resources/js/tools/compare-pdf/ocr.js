let _tesseractWorker = null;

export async function performOCR(canvas, onProgress) {
    if (!_tesseractWorker) {
        const { createWorker } = await import('tesseract.js');
        _tesseractWorker = await createWorker('eng', 1, {
            logger: m => onProgress && typeof m.progress === 'number' && onProgress(m.progress),
        });
    }

    const { data: { text } } = await _tesseractWorker.recognize(canvas);
    return text.replace(/\s+/g, ' ').trim();
}

export async function terminateOCR() {
    if (_tesseractWorker) {
        await _tesseractWorker.terminate();
        _tesseractWorker = null;
    }
}
