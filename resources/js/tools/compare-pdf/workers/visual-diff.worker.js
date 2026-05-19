import pixelmatch from 'pixelmatch';

self.addEventListener('message', (e) => {
    const { taskId, dataA, dataB, width, height, threshold } = e.data;

    const diffData = new Uint8ClampedArray(width * height * 4);

    const diffPixels = pixelmatch(
        new Uint8ClampedArray(dataA),
        new Uint8ClampedArray(dataB),
        diffData,
        width,
        height,
        { threshold, includeAA: false }
    );

    const totalPixels = width * height;

    self.postMessage(
        { taskId, diffData: diffData.buffer, diffPixels, totalPixels, diffPercentage: (diffPixels / totalPixels) * 100 },
        [diffData.buffer]
    );
});
