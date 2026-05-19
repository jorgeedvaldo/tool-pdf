import pixelmatch from 'https://cdn.jsdelivr.net/npm/pixelmatch@6.0.0/+esm';

self.onmessage = ({ data }) => {
    if (data.type !== 'visual-diff') return;

    const { id, dataA, dataB, w, h, threshold } = data;

    try {
        const diffOut = new Uint8ClampedArray(w * h * 4);
        const changed = pixelmatch(dataA, dataB, diffOut, w, h, {
            threshold,
            includeAA: false,
            diffColor: [229, 50, 45],
            alpha: 0.3,
        });
        self.postMessage(
            { type: 'result', id, diffOut, ratio: changed / (w * h) },
            [diffOut.buffer]
        );
    } catch (err) {
        // Send back a graceful error so the main thread promise resolves
        self.postMessage({ type: 'error', id, message: err.message });
    }
};
