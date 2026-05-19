import { state } from './state.js';
import { loadPdfFromFile } from './pdf-loader.js';
import { renderPageToCanvas, extractPageText } from './pdf-renderer.js';
import { compareCanvases } from './visual-diff.js';
import { compareTexts } from './text-diff.js';
import { generateReport, exportAsJSON, exportAsHTML } from './report.js';
import { initUI, updateProgress, showResults, updatePageView } from './ui.js';
import { COMPARE_PDF_CONFIG } from './utils.js';
import { performOCR } from './ocr.js';

document.addEventListener('DOMContentLoaded', () => {
    initUI(state, {
        onCompare:    startComparison,
        onPageChange: navigatePage,
        onModeChange: mode => { state.mode = mode; },
        onExport:     handleExport,
        onCancel:     () => { state.cancelRequested = true; },
    });
});

// ════════════════════════════════════════════════════════════════════════════
// COMPARISON PIPELINE
// ════════════════════════════════════════════════════════════════════════════

async function startComparison() {
    if (!state.originalFile || !state.modifiedFile || state.isComparing) return;

    state.isComparing    = true;
    state.cancelRequested = false;
    state.results         = { pages: [], summary: null };

    document.getElementById('cmp-results-area')?.classList.add('d-none');
    document.getElementById('cmp-progress-area')?.classList.remove('d-none');

    try {
        updateProgress({ message: 'Loading PDFs…', percent: 2 });

        [state.originalPdf, state.modifiedPdf] = await Promise.all([
            loadPdfFromFile(state.originalFile),
            loadPdfFromFile(state.modifiedFile),
        ]);

        const origTotal  = state.originalPdf.numPages;
        const modTotal   = state.modifiedPdf.numPages;
        const totalPages = Math.max(origTotal, modTotal);

        if (totalPages > COMPARE_PDF_CONFIG.maxPagesWithoutWarning) {
            const ok = confirm(
                `This document has ${totalPages} pages. Comparison may take a while. Continue?`
            );
            if (!ok) { state.isComparing = false; return; }
        }

        for (let i = 1; i <= totalPages; i++) {
            if (state.cancelRequested) break;

            const percent = Math.round(((i - 1) / totalPages) * 90) + 5;
            updateProgress({
                message: `Comparing page ${i} of ${totalPages}…`,
                percent, current: i, total: totalPages,
            });

            state.results.pages.push(await _comparePage(i, origTotal, modTotal));
        }

        _buildSummary();
        updateProgress({ message: state.cancelRequested ? 'Cancelled.' : 'Done!', percent: 100 });

        if (!state.cancelRequested) {
            state.currentPage = 1;
            showResults(state);
        }

    } catch (err) {
        console.error('Comparison error:', err);
        updateProgress({ message: `Error: ${err.message}`, percent: 0, error: true });
    } finally {
        state.isComparing = false;
    }
}

// ─── Per-page comparison ──────────────────────────────────────────────────────
async function _comparePage(pageNum, origTotal, modTotal) {
    const hasOrig = pageNum <= origTotal;
    const hasMod  = pageNum <= modTotal;

    if (!hasOrig) return _pageAdded(pageNum);
    if (!hasMod)  return _pageRemoved(pageNum);

    const [origR, modR] = await Promise.all([
        renderPageToCanvas(state.originalPdf, pageNum, state.scale),
        renderPageToCanvas(state.modifiedPdf, pageNum, state.scale),
    ]);

    let origText = '', modText = '';
    if (state.useOCR) {
        [origText, modText] = await Promise.all([
            performOCR(origR.canvas).catch(() => ''),
            performOCR(modR.canvas).catch(() => ''),
        ]);
    } else {
        [origText, modText] = await Promise.all([
            extractPageText(state.originalPdf, pageNum),
            extractPageText(state.modifiedPdf, pageNum),
        ]);
    }

    const visualResult = await compareCanvases(origR.canvas, modR.canvas, { threshold: state.threshold });
    const textResult   = compareTexts(origText, modText);

    const status = (visualResult.diffPercentage > 0.5 || textResult.hasDifference) ? 'changed' : 'unchanged';

    return {
        pageNumber: pageNum,
        status,
        visual: {
            diffPixels:     visualResult.diffPixels,
            totalPixels:    visualResult.totalPixels,
            diffPercentage: visualResult.diffPercentage,
            diffCanvas:     visualResult.diffCanvas,
            originalCanvas: origR.canvas,
            modifiedCanvas: modR.canvas,
        },
        text: {
            originalText: origText,
            modifiedText: modText,
            addedWords:   textResult.addedWords,
            removedWords: textResult.removedWords,
            htmlDiff:     textResult.htmlDiff,
        },
        dimensions: {
            original: { width: origR.width, height: origR.height },
            modified: { width: modR.width,  height: modR.height  },
        },
    };
}

async function _pageAdded(pageNum) {
    const modR    = await renderPageToCanvas(state.modifiedPdf, pageNum, state.scale);
    const modText = state.useOCR
        ? await performOCR(modR.canvas).catch(() => '')
        : await extractPageText(state.modifiedPdf, pageNum);

    return {
        pageNumber: pageNum, status: 'added',
        visual: { diffPixels: 0, totalPixels: 0, diffPercentage: 100, diffCanvas: null, originalCanvas: null, modifiedCanvas: modR.canvas },
        text:   { originalText: '', modifiedText: modText, addedWords: modText.split(/\s+/).filter(Boolean).length, removedWords: 0, htmlDiff: `<span class="pdf-diff-added">${modText}</span>` },
        dimensions: { original: { width: 0, height: 0 }, modified: { width: modR.width, height: modR.height } },
    };
}

async function _pageRemoved(pageNum) {
    const origR    = await renderPageToCanvas(state.originalPdf, pageNum, state.scale);
    const origText = state.useOCR
        ? await performOCR(origR.canvas).catch(() => '')
        : await extractPageText(state.originalPdf, pageNum);

    return {
        pageNumber: pageNum, status: 'removed',
        visual: { diffPixels: 0, totalPixels: 0, diffPercentage: 100, diffCanvas: null, originalCanvas: origR.canvas, modifiedCanvas: null },
        text:   { originalText: origText, modifiedText: '', addedWords: 0, removedWords: origText.split(/\s+/).filter(Boolean).length, htmlDiff: `<span class="pdf-diff-removed">${origText}</span>` },
        dimensions: { original: { width: origR.width, height: origR.height }, modified: { width: 0, height: 0 } },
    };
}

function _buildSummary() {
    const pages = state.results.pages;
    const avgDiff = pages.length
        ? pages.reduce((s, p) => s + (p.visual?.diffPercentage ?? 0), 0) / pages.length
        : 0;

    state.results.summary = {
        totalPages:      pages.length,
        changedPages:    pages.filter(p => p.status === 'changed').length,
        unchangedPages:  pages.filter(p => p.status === 'unchanged').length,
        addedPages:      pages.filter(p => p.status === 'added').length,
        removedPages:    pages.filter(p => p.status === 'removed').length,
        avgDiffPercentage: avgDiff,
    };
}

// ════════════════════════════════════════════════════════════════════════════
// NAVIGATION
// ════════════════════════════════════════════════════════════════════════════

function navigatePage(direction) {
    const pages = state.results.pages;
    if (!pages.length) return;

    if (direction === 'prev') {
        state.currentPage = Math.max(state.currentPage - 1, 1);
    } else if (direction === 'next') {
        state.currentPage = Math.min(state.currentPage + 1, pages.length);
    } else if (direction === 'next-diff') {
        // Find the next page after currentPage that is not unchanged
        const nextIdx = pages.findIndex((p, i) => i >= state.currentPage && p.status !== 'unchanged');
        if (nextIdx !== -1) state.currentPage = nextIdx + 1;
    } else if (typeof direction === 'number') {
        state.currentPage = Math.max(1, Math.min(direction, pages.length));
    }

    updatePageView(state);
}

// ════════════════════════════════════════════════════════════════════════════
// EXPORT
// ════════════════════════════════════════════════════════════════════════════

function handleExport(format) {
    const report = generateReport(state);
    if (format === 'json') exportAsJSON(report);
    else if (format === 'html') exportAsHTML(report);
}
