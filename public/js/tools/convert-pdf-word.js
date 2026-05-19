// ═══════════════════════════════════════════════════════════════════════════
//  PDF ⇄ Word conversion tool
//  PDF → RTF   : pdf.js (text extraction) + pure-JS RTF builder (no library)
//  DOCX → PDF  : mammoth.js (parse .docx → HTML) + html2canvas + jsPDF
//  All libraries loaded from cdnjs — no unpkg/jsdelivr dependency.
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

// ── File handling ─────────────────────────────────────────────────────────────
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
setupDropZone('cw-pw-zone', 'cw-pw-input');
setupDropZone('cw-wp-zone', 'cw-wp-input');

function onFileSelected(file) {
    const isPdf  = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
    const isDocx = file.name.toLowerCase().endsWith('.docx');

    if (mode === 'pdf-to-word' && !isPdf)  { showError('Selecione um ficheiro PDF.'); return; }
    if (mode === 'word-to-pdf' && !isDocx) { showError('Selecione um ficheiro .docx. O formato .doc antigo não é suportado.'); return; }

    selectedFile = file;
    hideError();

    const card     = mode === 'pdf-to-word' ? $('cw-pw-card')   : $('cw-wp-card');
    const zone     = mode === 'pdf-to-word' ? $('cw-pw-zone')   : $('cw-wp-zone');
    const nameEl   = mode === 'pdf-to-word' ? $('cw-pw-name')   : $('cw-wp-name');
    const sizeEl   = mode === 'pdf-to-word' ? $('cw-pw-size')   : $('cw-wp-size');
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

// ── UI helpers ────────────────────────────────────────────────────────────────
function showError(msg) { const e = $('cw-error'); e.textContent = msg; e.classList.remove('d-none'); }
function hideError()    { $('cw-error').classList.add('d-none'); }

function setProgress(pct, msg) {
    $('cw-progress').classList.remove('d-none');
    $('cw-progress-bar').style.width = pct + '%';
    $('cw-progress-msg').textContent = msg || '';
}

// ── Convert button ────────────────────────────────────────────────────────────
$('cw-convert-btn').addEventListener('click', async () => {
    if (!selectedFile) return;
    $('cw-convert-btn').disabled = true;
    hideError();
    try {
        if (mode === 'pdf-to-word') await convertPdfToRtf(selectedFile);
        else                        await convertWordToPdf(selectedFile);
    } catch (err) {
        console.error(err);
        showError('Erro na conversão: ' + err.message);
    } finally {
        $('cw-convert-btn').disabled = false;
    }
});

// ═══════════════════════════════════════════════════════════════════════════
//  PDF → RTF  (pure JavaScript — no external library required)
//  RTF opens natively in Microsoft Word, LibreOffice Writer, and WordPad.
// ═══════════════════════════════════════════════════════════════════════════
function escapeRtf(str) {
    let out = '';
    for (const ch of str) {
        const code = ch.charCodeAt(0);
        if      (ch === '\\') out += '\\\\';
        else if (ch === '{' ) out += '\\{';
        else if (ch === '}' ) out += '\\}';
        else if (code > 127 ) out += `\\u${code}?`;   // Unicode escape, '?' is RTF fallback char
        else                  out += ch;
    }
    return out;
}

async function convertPdfToRtf(file) {
    setProgress(5, 'A carregar PDF…');
    const ab  = await file.arrayBuffer();
    const pdf = await pdfjsLib.getDocument({ data: new Uint8Array(ab) }).promise;

    let body = '';

    for (let p = 1; p <= pdf.numPages; p++) {
        setProgress(5 + Math.round((p / pdf.numPages) * 85),
            `A processar página ${p} de ${pdf.numPages}…`);

        const page = await pdf.getPage(p);
        const vp   = page.getViewport({ scale: 1 });
        const tc   = await page.getTextContent();

        // ── Group text items into lines by Y baseline ─────────────────────
        const lines = [];
        for (const it of tc.items) {
            if (!it.str || !it.str.trim()) continue;
            const y  = it.transform[5];
            const x  = it.transform[4];
            const fs = Math.abs(it.transform[0]) || Math.abs(it.transform[3]) || 12;
            let line = lines.find(l => Math.abs(l.y - y) < fs * 0.6);
            if (!line) { line = { y, items: [] }; lines.push(line); }
            line.items.push({ x, str: it.str, fontSize: fs });
        }
        lines.sort((a, b) => b.y - a.y);
        lines.forEach(l => l.items.sort((a, b) => a.x - b.x));

        // ── Build paragraphs ──────────────────────────────────────────────
        const paragraphs = [];
        let curr = null, prevY = null, prevFS = null;

        for (const line of lines) {
            const text  = line.items.map(it => it.str).join('').replace(/\s+/g, ' ').trim();
            if (!text) { if (curr) { paragraphs.push(curr); curr = null; } continue; }

            const avgFS  = line.items.reduce((a, b) => a + b.fontSize, 0) / line.items.length;
            const gap    = prevY != null ? prevY - line.y : 0;
            const isNew  = !curr || (prevFS && (gap > prevFS * 1.7 || Math.abs(avgFS - prevFS) > 2));

            // Alignment detection
            const lineLeft  = line.items[0].x;
            const lineRight = line.items[line.items.length - 1].x +
                (line.items[line.items.length - 1].str.length * avgFS * 0.5);
            const lineMid = (lineLeft + lineRight) / 2;
            let align = 'l';
            if (Math.abs(lineMid - vp.width / 2) < vp.width * 0.06 && lineLeft > vp.width * 0.18)
                align = 'c';
            else if (lineRight > vp.width * 0.85 && lineLeft > vp.width * 0.4)
                align = 'r';

            if (isNew) { curr = { text: '', align, fontSize: avgFS }; paragraphs.push(curr); }
            curr.text += (curr.text ? ' ' : '') + text;
            prevY = line.y; prevFS = avgFS;
        }

        // ── Emit RTF paragraphs ───────────────────────────────────────────
        for (const par of paragraphs) {
            const q  = par.align === 'c' ? '\\qc' : par.align === 'r' ? '\\qr' : '\\ql';
            const fs = Math.max(16, Math.round(par.fontSize * 2)); // RTF uses half-points
            body += `\\pard${q}\\f0\\fs${fs} ${escapeRtf(par.text)}\\par\n`;
        }

        if (p < pdf.numPages) body += '\\page\n';
    }

    setProgress(95, 'A gerar ficheiro…');

    const rtf  = `{\\rtf1\\ansi\\deff0\n` +
                 `{\\fonttbl{\\f0\\froman\\fcharset0 Times New Roman;}}\n` +
                 `{\\colortbl ;\\red0\\green0\\blue0;}\n` +
                 body +
                 `}`;
    const blob = new Blob([rtf], { type: 'application/rtf' });
    setProgress(100, 'Pronto.');
    downloadBlob(blob, file.name.replace(/\.pdf$/i, '') + '.rtf');
    setTimeout(() => $('cw-progress').classList.add('d-none'), 1500);
}

// ═══════════════════════════════════════════════════════════════════════════
//  WORD (.docx) → PDF
//  mammoth.js → HTML  |  html2canvas → canvas  |  jsPDF → PDF
//  All three libraries loaded from cdnjs (same CDN as pdf.js).
// ═══════════════════════════════════════════════════════════════════════════
async function convertWordToPdf(file) {
    if (!window.mammoth)   throw new Error('mammoth não carregou. Verifique a ligação e recarregue.');
    if (!window.html2canvas) throw new Error('html2canvas não carregou. Verifique a ligação e recarregue.');
    if (!window.jspdf)     throw new Error('jsPDF não carregou. Verifique a ligação e recarregue.');

    setProgress(5, 'A ler ficheiro Word…');
    const ab = await file.arrayBuffer();

    setProgress(20, 'A extrair conteúdo…');
    const result = await mammoth.convertToHtml(
        { arrayBuffer: ab },
        { styleMap: [
            "p[style-name='Heading 1'] => h1:fresh",
            "p[style-name='Heading 2'] => h2:fresh",
            "p[style-name='Heading 3'] => h3:fresh",
            "p[style-name='Heading 4'] => h4:fresh",
            "p[style-name='Title']     => h1.doc-title:fresh",
            "p[style-name='Quote']     => blockquote",
        ]}
    );
    const html = (result.value || '').trim();
    if (!html) throw new Error('O documento parece estar vazio ou sem texto extraível.');

    setProgress(40, 'A montar página…');

    // Build A4-width container. position:absolute + left:-9999px keeps it
    // off-screen but fully laid-out — html2canvas captures it via element ref.
    const container = document.createElement('div');
    container.style.cssText = [
        'width:794px', 'padding:56px 64px', 'background:#fff', 'color:#111',
        "font-family:'Times New Roman',serif", 'font-size:12pt', 'line-height:1.6',
        'box-sizing:border-box', 'position:absolute', 'left:-9999px', 'top:0',
    ].join(';');

    // Embed styles directly so they're scoped to the container
    const style = document.createElement('style');
    style.textContent = `
        .cw-rt h1{font-size:22pt;margin:0 0 10pt;font-weight:700;line-height:1.2}
        .cw-rt h2{font-size:16pt;margin:16pt 0 8pt;font-weight:700}
        .cw-rt h3{font-size:13pt;margin:13pt 0 6pt;font-weight:700}
        .cw-rt h4{font-size:11pt;margin:10pt 0 5pt;font-weight:700}
        .cw-rt p{margin:0 0 8pt}
        .cw-rt ul,.cw-rt ol{margin:0 0 8pt 22pt}
        .cw-rt li{margin-bottom:3pt}
        .cw-rt table{border-collapse:collapse;margin:0 0 10pt;width:100%}
        .cw-rt td,.cw-rt th{border:1px solid #555;padding:4pt 7pt;vertical-align:top}
        .cw-rt img{max-width:100%;height:auto;display:block}
        .cw-rt blockquote{margin:0 0 8pt 18pt;padding-left:10pt;border-left:3px solid #999;color:#555;font-style:italic}
        .cw-rt strong{font-weight:700}
        .cw-rt em{font-style:italic}
    `;
    const inner = document.createElement('div');
    inner.className = 'cw-rt';
    inner.innerHTML = html;
    container.appendChild(style);
    container.appendChild(inner);
    document.body.appendChild(container);

    // Wait for embedded images
    await Promise.all(Array.from(container.querySelectorAll('img')).map(img =>
        img.complete ? Promise.resolve()
                     : new Promise(r => { img.onload = img.onerror = r; })));

    setProgress(60, 'A renderizar…');

    let canvas;
    try {
        canvas = await html2canvas(container, {
            scale: 2,
            useCORS: true,
            backgroundColor: '#ffffff',
            logging: false,
            width: 794,
            windowWidth: 794,
        });
    } finally {
        document.body.removeChild(container);
    }

    setProgress(80, 'A gerar PDF…');

    const { jsPDF } = window.jspdf;
    const pdf  = new jsPDF({ unit: 'pt', format: 'a4', orientation: 'portrait', compress: true });
    const pgW  = pdf.internal.pageSize.getWidth();
    const pgH  = pdf.internal.pageSize.getHeight();
    const imgW = pgW;
    const imgH = canvas.height * pgW / canvas.width;
    const imgData = canvas.toDataURL('image/jpeg', 0.93);

    // Slice canvas across pages
    let sliceY = 0;
    while (sliceY < imgH) {
        if (sliceY > 0) pdf.addPage();
        pdf.addImage(imgData, 'JPEG', 0, -sliceY, imgW, imgH);
        sliceY += pgH;
    }

    setProgress(100, 'Pronto.');
    pdf.save(file.name.replace(/\.docx$/i, '') + '.pdf');
    setTimeout(() => $('cw-progress').classList.add('d-none'), 1500);
}

// ── Download helper ───────────────────────────────────────────────────────────
function downloadBlob(blob, filename) {
    const url = URL.createObjectURL(blob);
    const a   = document.createElement('a');
    a.href = url; a.download = filename;
    document.body.appendChild(a); a.click();
    document.body.removeChild(a);
    setTimeout(() => URL.revokeObjectURL(url), 5000);
}
