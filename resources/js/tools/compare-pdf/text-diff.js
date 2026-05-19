import { diffWords } from 'diff';
import { sanitizeHTML } from './utils.js';

export function compareTexts(textA, textB) {
    const differences = diffWords(textA || '', textB || '');

    let addedWords = 0;
    let removedWords = 0;
    let htmlDiff = '';

    for (const part of differences) {
        const words = part.value.trim().split(/\s+/).filter(Boolean).length;

        if (part.added) {
            addedWords += words;
            htmlDiff += `<span class="pdf-diff-added">${sanitizeHTML(part.value)}</span>`;
        } else if (part.removed) {
            removedWords += words;
            htmlDiff += `<span class="pdf-diff-removed">${sanitizeHTML(part.value)}</span>`;
        } else {
            htmlDiff += `<span class="pdf-diff-unchanged">${sanitizeHTML(part.value)}</span>`;
        }
    }

    return { addedWords, removedWords, htmlDiff, hasDifference: addedWords > 0 || removedWords > 0 };
}
