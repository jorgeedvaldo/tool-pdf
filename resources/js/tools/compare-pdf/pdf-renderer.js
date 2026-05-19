export async function renderPageToCanvas(pdf, pageNumber, scale = 1.5) {
    const page = await pdf.getPage(pageNumber);
    const viewport = page.getViewport({ scale });

    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d', { willReadFrequently: true });

    canvas.width = viewport.width;
    canvas.height = viewport.height;

    await page.render({ canvasContext: context, viewport }).promise;

    return { canvas, context, width: viewport.width, height: viewport.height };
}

export async function extractPageText(pdf, pageNumber) {
    const page = await pdf.getPage(pageNumber);
    const textContent = await page.getTextContent();
    return textContent.items
        .map(item => item.str)
        .join(' ')
        .replace(/\s+/g, ' ')
        .trim();
}
