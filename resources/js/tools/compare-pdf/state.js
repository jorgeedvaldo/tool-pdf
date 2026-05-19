export const state = {
    originalFile: null,
    modifiedFile: null,
    originalPdf: null,
    modifiedPdf: null,
    currentPage: 1,
    scale: 1.5,
    mode: 'visual',
    threshold: 0.1,
    syncScroll: true,
    useOCR: false,
    isComparing: false,
    cancelRequested: false,
    results: {
        pages: [],
        summary: null,
    },
};
