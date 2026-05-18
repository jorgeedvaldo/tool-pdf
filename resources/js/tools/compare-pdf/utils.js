export const COMPARE_PDF_CONFIG = {
    defaultScale: 1.5,
    previewScale: 0.6,
    maxScale: 3,
    defaultThreshold: 0.1,
    maxPagesWithoutWarning: 100,
    maxFileSizeMB: 150,
    enableOCR: true,
    enableWorkers: true,
};

export function formatBytes(bytes, decimals = 2) {
    if (!+bytes) return '0 Bytes';
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`;
}

export function sanitizeHTML(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

export function clamp(value, min, max) {
    return Math.min(Math.max(value, min), max);
}
