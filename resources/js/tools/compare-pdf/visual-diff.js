import pixelmatch from 'pixelmatch';

let _worker = null;
let _taskCounter = 0;
const _pendingTasks = new Map();

function getWorker() {
    if (!_worker) {
        try {
            _worker = new Worker(new URL('./workers/visual-diff.worker.js', import.meta.url), { type: 'module' });
            _worker.addEventListener('message', (e) => {
                const { taskId, ...result } = e.data;
                const resolve = _pendingTasks.get(taskId);
                if (resolve) {
                    _pendingTasks.delete(taskId);
                    resolve(result);
                }
            });
            _worker.addEventListener('error', () => {
                _worker = null;
            });
        } catch {
            _worker = null;
        }
    }
    return _worker;
}

function normalizeCanvas(canvas, width, height) {
    if (canvas.width === width && canvas.height === height) return canvas;
    const out = document.createElement('canvas');
    out.width = width;
    out.height = height;
    out.getContext('2d').drawImage(canvas, 0, 0);
    return out;
}

async function compareInWorker(dataA, dataB, width, height, threshold) {
    return new Promise((resolve, reject) => {
        const worker = getWorker();
        if (!worker) { reject(new Error('no worker')); return; }

        const taskId = ++_taskCounter;
        _pendingTasks.set(taskId, resolve);

        const bufA = dataA.buffer.slice(0);
        const bufB = dataB.buffer.slice(0);
        worker.postMessage({ taskId, dataA: bufA, dataB: bufB, width, height, threshold }, [bufA, bufB]);
    });
}

function compareOnMainThread(dataA, dataB, width, height, threshold) {
    const diffData = new Uint8ClampedArray(width * height * 4);
    const diffPixels = pixelmatch(dataA.data, dataB.data, diffData, width, height, { threshold, includeAA: false });
    const totalPixels = width * height;

    const diffCanvas = document.createElement('canvas');
    diffCanvas.width = width;
    diffCanvas.height = height;
    diffCanvas.getContext('2d').putImageData(new ImageData(diffData, width, height), 0, 0);

    return { diffCanvas, diffPixels, totalPixels, diffPercentage: (diffPixels / totalPixels) * 100, hasDifference: diffPixels > 0 };
}

export async function compareCanvases(canvasA, canvasB, options = {}) {
    const threshold = options.threshold ?? 0.1;
    const width = Math.max(canvasA.width, canvasB.width);
    const height = Math.max(canvasA.height, canvasB.height);

    const normA = normalizeCanvas(canvasA, width, height);
    const normB = normalizeCanvas(canvasB, width, height);

    const ctxA = normA.getContext('2d', { willReadFrequently: true });
    const ctxB = normB.getContext('2d', { willReadFrequently: true });

    const dataA = ctxA.getImageData(0, 0, width, height);
    const dataB = ctxB.getImageData(0, 0, width, height);

    try {
        const result = await compareInWorker(dataA.data, dataB.data, width, height, threshold);

        const diffCanvas = document.createElement('canvas');
        diffCanvas.width = width;
        diffCanvas.height = height;
        diffCanvas.getContext('2d').putImageData(new ImageData(new Uint8ClampedArray(result.diffData), width, height), 0, 0);

        return { diffCanvas, diffPixels: result.diffPixels, totalPixels: result.totalPixels, diffPercentage: result.diffPercentage, hasDifference: result.diffPixels > 0 };
    } catch {
        return compareOnMainThread(dataA, dataB, width, height, threshold);
    }
}
