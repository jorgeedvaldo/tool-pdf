// ═══════════════════════════════════════════════════════════════════════════
//  PDF ⇄ Word conversion tool
//  PDF → DOCX  : pdf.js (text extraction) + docx (build .docx)
//  DOCX → PDF  : mammoth.js (parse .docx → HTML) + html2pdf.js (render to PDF)
// ═══════════════════════════════════════════════════════════════════════════

const $ = id => document.getElementById(id);
const fmtBytes = b => b < 1048576 ? (b/1024).toFixed(1) + ' KB' : (b/1048576).toFixed(1) + ' MB';

let mode = (window.CW_INITIAL_MODE || 'pdf-to-word');
let selectedFile = null;

// ── Tabs ─────────────────────────────────────────────────────────────────────
document.querySelectorAll('.cw-tab').forEach(btn => {
    btn.addEventListener('click', () => setMode(btn.dataset.mode));
});

function setMode(newMode) {
    mode = newMode;
    document.querySelectorAll('.cw-tab').forEach(t => t.classList.toggle('active', t.dataset.mode === newMode));
    document.querySelectorAll('.cw-pane').forEach(p => p.classList.toggle('d-none', p.dataset.mode !== newMode));
    resetFile();
}

setMode(mode);

// ── File handling (works for both modes) ─────────────────────────────────────
function setupDropZone(zoneId, inputId) {
    const zone  = $(zoneId);
    const input = $(inputId);
    if (!zone || !input) return;
    zone.addEventListener('click', () => input.click());
    zone.addEventListener('dragover',  e => { e.preventDefault(); zone.classList.add('cw-drag-over'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('cw-drag-over'));
    zone.addEventListener('drop', e => {
        e.preventDefault(); zone.classList.remove('cw-drag-over');
        if (e.dataTransfer.files[0]) onFileSelected(e.dataTransfer.files[0]);
    });
    input.addEventListener('change', e => e.target.files[0] && onFileSelected(e.target.files[0]));
}
setupDropZone('cw-pw-zone',  'cw-pw-input');
setupDropZone('cw-wp-zone',  'cw-wp-input');

function onFileSelected(file) {
    const isPdf  = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
    const isDocx = file.name.toLowerCase().endsWith('.docx');

    if (mode === 'pdf-to-word' && !isPdf) {
        showError('Selecione um ficheiro PDF.'); return;
    }
    if (mode === 'word-to-pdf' && !isDocx) {
        showError('Selecione um ficheiro .docx (Word). .doc antigo não é suportado.'); return;
    }

    selectedFile = file;
    hideError();

    const card    = mode === 'pdf-to-word' ? $('cw-pw-card') : $('cw-wp-card');
    const zone    = mode === 'pdf-to-word' ? $('cw-pw-zone') : $('cw-wp-zone');
    const nameEl  = mode === 'pdf-to-word' ? $('cw-pw-name') : $('cw-wp-name');
    const sizeEl  = mode === 'pdf-to-word' ? $('cw-pw-size') : $('cw-wp-size');
    const removeEl = mode === 'pdf-to-word' ? $('cw-pw-remove') : $('cw-wp-remove');

    nameEl.textContent = file.name;
    sizeEl.textContent = fmtBytes(file.size);
    zone.classList.add('d-none');
    card.classList.remove('d-none');
    $('cw-convert-btn').disabled = false;
    removeEl.onclick = resetFile;
}

function resetFile() {
    selectedFile = null;
    ['cw-pw-card', 'cw-wp-card'].forEach(id => $(id)?.classList.add('d-none'));
    ['cw-pw-zone', 'cw-wp-zone'].forEach(id => $(id)?.classList.remove('d-none'));
    ['cw-pw-input', 'cw-wp-input'].forEach(id => { const el = $(id); if (el) el.value = ''; });
    $('cw-convert-btn').disabled = true;
    $('cw-progress').classList.add('d-none');
    hideError();
}

// ── UI helpers ───────────────────────────────────────────────────────────────
function showError(msg) {
    const e = $('cw-error');
    e.textContent = msg; e.classList.remove('d-none');
}
function hideError() { $('cw-error').classList.add('d-none'); }

function setProgress(pct, msg) {
    $('cw-progress').classList.remove('d-none');
    $('cw-progress-bar').style.width = pct + '%';
    $('cw-progress-msg').textContent = msg || '';
}

// ── Convert button ───────────────────────────────────────────────────────────
$('cw-convert-btn').addEventListener('click', async () => {
    if (!selectedFile) return;
    $('cw-convert-btn').disabled = true;
    hideError();
    try {
        if (mode === 'pdf-to-word') await convertPdfToWord(selectedFile);
        else                        await convertWordToPdf(selectedFile);
    } catch (err) {
        console.error(err);
        showError('Erro na conversão: ' + err.message);
    } finally {
        $('cw-convert-btn').disabled = false;
    }
});

// ═══════════════════════════════════════════════════════════════════════════
//  PDF → WORD
// ═══════════════════════════════════════════════════════════════════════════
async function convertPdfToWord(file) {
    setProgress(5, 'A carregar PDF…');
    const ab = await file.arrayBuffer();
    const pdf = await pdfjsLib.getDocument({ data: new Uint8Array(ab) }).promise;
    const includeImages = $('cw-include-images')?.checked;

    if (!window.docx) {
        throw new Error('Biblioteca docx não carregou. Verifique sua conexão e recarregue a página.');
    }
    const { Document, Packer, Paragraph, TextRun, AlignmentType, PageBreak, ImageRun } = window.docx;

    const sections = [];
    for (let p = 1; p <= pdf.numPages; p++) {
        setProgress(5 + Math.round((p / pdf.numPages) * 80), `A processar página ${p} de ${pdf.numPages}…`);
        const page = await pdf.getPage(p);
        const vp = page.getViewport({ scale: 1 });
        const tc = await page.getTextContent();

        // Group items into lines by Y baseline (tolerant of small jitter)
        const lines = [];
        for (const it of tc.items) {
            if (!it.str) continue;
            const y = it.transform[5];                              // baseline Y in PDF pts (origin bottom-left)
            const x = it.transform[4];
            const fs = Math.hypot(it.transform[0], it.transform[1]); // font size pt
            // find a line within fs * 0.5 of this baseline
            let line = lines.find(l => Math.abs(l.y - y) < fs * 0.6);
            if (!line) { line = { y, items: [] }; lines.push(line); }
            line.items.push({ x, y, str: it.str, fontSize: fs, font: it.fontName });
        }
        // Sort lines top-down (PDF Y decreases downward in our reading order)
        lines.sort((a, b) => b.y - a.y);
        // Sort items within each line left-to-right
        lines.forEach(l => l.items.sort((a, b) => a.x - b.x));

        // Build paragraphs — join consecutive lines that are close (< 1.7 × avg fontSize)
        const paragraphs = [];
        let curr = null;
        let prevY = null, prevFS = null;
        for (const line of lines) {
            const text = line.items.map(it => it.str).join('').replace(/\s+/g, ' ').trim();
            if (!text) {
                if (curr) { paragraphs.push(curr); curr = null; }
                continue;
            }
            const avgFS = line.items.reduce((a, b) => a + b.fontSize, 0) / line.items.length;
            const gap = prevY != null ? prevY - line.y : 0;
            // New paragraph when line gap > 1.7× the previous font size or when font size jumps
            const isNewParagraph = !curr || (prevFS && (gap > prevFS * 1.7 || Math.abs(avgFS - prevFS) > 2));
            // Detect alignment: middle of line vs page width
            const lineLeft  = line.items[0].x;
            const lineRight = line.items[line.items.length - 1].x +
                (line.items[line.items.length - 1].str.length * avgFS * 0.5);
            const lineMid = (lineLeft + lineRight) / 2;
            let align = AlignmentType.LEFT;
            if (Math.abs(lineMid - vp.width / 2) < vp.width * 0.06 && lineLeft > vp.width * 0.18) align = AlignmentType.CENTER;
            else if (vp.width - lineRight < vp.width * 0.06 && lineLeft > vp.width * 0.3) align = AlignmentType.RIGHT;

            if (isNewParagraph) {
                curr = { runs: [], align, fontSize: avgFS };
                paragraphs.push(curr);
            }
            // Append text (with space if continuation)
            curr.runs.push(new TextRun({
                text: (curr.runs.length ? ' ' : '') + text,
                size: Math.max(16, Math.round(avgFS * 2)), // docx uses half-points
            }));
            prevY = line.y; prevFS = avgFS;
        }

        const children = paragraphs.map(par => new Paragraph({
            alignment: par.align,
            children: par.runs.length ? par.runs : [new TextRun('')],
            spacing: { after: 120 },
        }));
        if (children.length === 0) children.push(new Paragraph({ children: [new TextRun('')] }));

        // Optionally embed page image
        if (includeImages) {
            try {
                const imgVp = page.getViewport({ scale: 1.5 });
                const c = document.createElement('canvas');
                c.width = imgVp.width; c.height = imgVp.height;
                await page.render({ canvasContext: c.getContext('2d'), viewport: imgVp }).promise;
                const blob = await new Promise(res => c.toBlob(res, 'image/png'));
                const buf  = await blob.arrayBuffer();
                children.unshift(new Paragraph({
                    children: [new ImageRun({
                        data: buf,
                        transformation: { width: 580, height: Math.round(580 * imgVp.height / imgVp.width) },
                    })],
                    spacing: { after: 200 },
                }));
            } catch (e) { console.warn('Falha ao embutir imagem da página', p, e); }
        }

        sections.push({
            properties: { page: { margin: { top: 720, right: 720, bottom: 720, left: 720 } } },
            children,
        });
    }

    setProgress(90, 'A gerar ficheiro Word…');
    const doc = new Document({
        creator: 'ToolPDF', title: file.name.replace(/\.pdf$/i, ''),
        sections,
    });
    const blob = await Packer.toBlob(doc);
    setProgress(100, 'Pronto.');
    downloadBlob(blob, file.name.replace(/\.pdf$/i, '') + '.docx');
    setTimeout(() => $('cw-progress').classList.add('d-none'), 1500);
}

// ═══════════════════════════════════════════════════════════════════════════
//  WORD (.docx) → PDF
// ═══════════════════════════════════════════════════════════════════════════
async function convertWordToPdf(file) {
    setProgress(5, 'A ler ficheiro Word…');
    const ab = await file.arrayBuffer();
    setProgress(25, 'A extrair conteúdo do documento…');

    // Convert .docx → HTML (mammoth preserves headings, bold/italic, lists, tables, images)
    const result = await mammoth.convertToHtml(
        { arrayBuffer: ab },
        {
            styleMap: [
                "p[style-name='Heading 1'] => h1:fresh",
                "p[style-name='Heading 2'] => h2:fresh",
                "p[style-name='Heading 3'] => h3:fresh",
                "p[style-name='Heading 4'] => h4:fresh",
                "p[style-name='Title'] => h1.title:fresh",
                "p[style-name='Quote'] => blockquote",
            ],
        }
    );
    const html = (result.value || '').trim();
    if (!html) {
        throw new Error('O documento Word parece estar vazio ou sem conteúdo textual extraível.');
    }

    setProgress(45, 'A montar página…');

    // Build a printable container styled like an A4 document.
    // Position off-screen to the right (NOT with opacity:0 — that would make
    // html2canvas capture transparent pixels and produce a blank PDF).
    const container = document.createElement('div');
    container.id = 'cw-render-target';
    container.style.cssText = `
        width: 794px; padding: 48px 56px; background: #ffffff; color: #111;
        font-family: 'Times New Roman', Times, serif; font-size: 12pt; line-height: 1.5;
        box-sizing: border-box; position: fixed; top: 0; left: 100vw;
        pointer-events: none; z-index: -1;
    `;
    container.innerHTML = `
        <style>
            #cw-render-target h1 { font-size: 24pt; margin: 0 0 12pt; font-weight: 700; }
            #cw-render-target h2 { font-size: 18pt; margin: 18pt 0 10pt; font-weight: 700; }
            #cw-render-target h3 { font-size: 14pt; margin: 14pt 0 8pt; font-weight: 700; }
            #cw-render-target h4 { font-size: 12pt; margin: 12pt 0 8pt; font-weight: 700; }
            #cw-render-target p  { margin: 0 0 9pt; text-align: justify; }
            #cw-render-target ul, #cw-render-target ol { margin: 0 0 9pt 24pt; }
            #cw-render-target li { margin-bottom: 4pt; }
            #cw-render-target table { border-collapse: collapse; margin: 0 0 10pt; }
            #cw-render-target table td, #cw-render-target table th { border: 1px solid #444; padding: 4pt 6pt; vertical-align: top; }
            #cw-render-target img { max-width: 100%; height: auto; }
            #cw-render-target blockquote { margin: 0 0 9pt 20pt; padding-left: 12pt; border-left: 3px solid #888; color: #444; font-style: italic; }
            #cw-render-target a { color: #0d6efd; text-decoration: underline; }
            #cw-render-target strong { font-weight: 700; }
            #cw-render-target em { font-style: italic; }
        </style>
        ${html}
    `;
    document.body.appendChild(container);

    // Wait for any embedded images to load
    await Promise.all(Array.from(container.querySelectorAll('img')).map(img =>
        img.complete ? Promise.resolve()
                     : new Promise(res => { img.onload = img.onerror = res; })));

    setProgress(65, 'A gerar PDF…');

    const opts = {
        margin: 0,
        filename: file.name.replace(/\.docx$/i, '') + '.pdf',
        image:    { type: 'jpeg', quality: 0.96 },
        html2canvas: {
            scale: 2, useCORS: true, logging: false, backgroundColor: '#ffffff',
            width: 794, windowWidth: 794,
        },
        jsPDF:    { unit: 'pt', format: 'a4', orientation: 'portrait', compress: true },
        pagebreak: { mode: ['avoid-all', 'css', 'legacy'] },
    };

    try {
        await html2pdf().set(opts).from(container).save();
        setProgress(100, 'Pronto.');
    } finally {
        container.remove();
    }
    setTimeout(() => $('cw-progress').classList.add('d-none'), 1500);
}

// ── Download helper ──────────────────────────────────────────────────────────
function downloadBlob(blob, filename) {
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = filename;
    document.body.appendChild(a); a.click();
    document.body.removeChild(a);
    setTimeout(() => URL.revokeObjectURL(url), 5000);
}
