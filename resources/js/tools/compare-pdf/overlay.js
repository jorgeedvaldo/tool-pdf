export function createOverlayCanvas(canvasA, canvasB, options = {}) {
    const width = Math.max(canvasA?.width ?? 0, canvasB?.width ?? 0);
    const height = Math.max(canvasA?.height ?? 0, canvasB?.height ?? 0);

    const out = document.createElement('canvas');
    out.width = width;
    out.height = height;
    const ctx = out.getContext('2d');

    ctx.clearRect(0, 0, width, height);

    if (canvasA) {
        ctx.globalAlpha = options.opacityA ?? 0.5;
        ctx.globalCompositeOperation = 'source-over';
        ctx.drawImage(canvasA, 0, 0);
    }

    if (canvasB) {
        ctx.globalAlpha = options.opacityB ?? 0.5;
        ctx.globalCompositeOperation = options.blendMode ?? 'source-over';
        ctx.drawImage(canvasB, 0, 0);
    }

    ctx.globalAlpha = 1;
    ctx.globalCompositeOperation = 'source-over';

    return out;
}

export function renderOverlayToContainer(container, canvasA, canvasB, options = {}) {
    container.innerHTML = '';
    const canvas = createOverlayCanvas(canvasA, canvasB, options);
    canvas.style.maxWidth = '100%';
    canvas.style.height = 'auto';
    container.appendChild(canvas);
}
