// ═══════════════════════════════════════════════════════════════════════════
//  PDF ⇄ Word conversion tool  –  server-side via LibreOffice
//  Sends the file to the backend and downloads the converted result.
// ═══════════════════════════════════════════════════════════════════════════

const $ = id => document.getElementById(id);
const fmtBytes = b => b < 1048576 ? (b/1024).toFixed(1)+' KB' : (b/1048576).toFixed(1)+' MB';

let mode = (window.CW_INITIAL_MODE || 'pdf-to-word');
let selectedFile = null;

// ── Tabs ──────────────────────────────────────────────────────────────────────
document.querySelectorAll('.cw-tab').forEach(btn =>
    btn.addEventListener('click', () => setMode(btn.dataset.mode))
);

function setMode(m) {
    mode = m;
    document.querySelectorAll('.cw-tab').forEach(t =>
        t.classList.toggle('active', t.dataset.mode === m)
    );
    document.querySelectorAll('.cw-pane').forEach(p =>
        p.classList.toggle('d-none', p.dataset.mode !== m)
    );
    resetFile();
}
setMode(mode);

// ── Drop zones ────────────────────────────────────────────────────────────────
function setupDropZone(zoneId, inputId) {
    const zone = $(zoneId), input = $(inputId);
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
    const isWord = /\.(docx?|odt|rtf)$/i.test(file.name);

    if (mode === 'pdf-to-word' && !isPdf)  { showError('Selecione um ficheiro PDF válido.'); return; }
    if (mode === 'word-to-pdf' && !isWord) { showError('Selecione um ficheiro Word (.docx, .doc, .odt ou .rtf).'); return; }

    selectedFile = file;
    hideError();

    const isPw     = mode === 'pdf-to-word';
    const card     = $(isPw ? 'cw-pw-card'   : 'cw-wp-card');
    const zone     = $(isPw ? 'cw-pw-zone'   : 'cw-wp-zone');
    const nameEl   = $(isPw ? 'cw-pw-name'   : 'cw-wp-name');
    const sizeEl   = $(isPw ? 'cw-pw-size'   : 'cw-wp-size');
    const removeEl = $(isPw ? 'cw-pw-remove' : 'cw-wp-remove');

    nameEl.textContent = file.name;
    sizeEl.textContent = fmtBytes(file.size);
    zone.classList.add('d-none');
    card.classList.remove('d-none');
    $('cw-convert-btn').disabled = false;
    removeEl.onclick = resetFile;
}

function resetFile() {
    selectedFile = null;
    ['cw-pw-card','cw-wp-card'].forEach(id => $(id)?.classList.add('d-none'));
    ['cw-pw-zone','cw-wp-zone'].forEach(id => $(id)?.classList.remove('d-none'));
    ['cw-pw-input','cw-wp-input'].forEach(id => { const el=$(id); if(el) el.value=''; });
    $('cw-convert-btn').disabled = true;
    $('cw-progress').classList.add('d-none');
    hideError();
}

// ── UI helpers ────────────────────────────────────────────────────────────────
function showError(msg) { const e=$('cw-error'); e.textContent=msg; e.classList.remove('d-none'); }
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
        if (mode === 'pdf-to-word') await sendConvert('pdfToWord');
        else                        await sendConvert('wordToPdf');
    } catch (err) {
        console.error(err);
        showError('Erro: ' + err.message);
    } finally {
        $('cw-convert-btn').disabled = false;
    }
});

// ═══════════════════════════════════════════════════════════════════════════
//  Core: upload → backend (LibreOffice) → download result
// ═══════════════════════════════════════════════════════════════════════════
async function sendConvert(direction) {
    const isPw      = direction === 'pdfToWord';
    const url       = isPw ? window.CW_ROUTES.pdfToWord : window.CW_ROUTES.wordToPdf;
    const outExt    = isPw ? '.docx' : '.pdf';
    const baseName  = selectedFile.name.replace(/\.[^.]+$/, '');

    setProgress(10, 'A enviar ficheiro…');

    const body = new FormData();
    body.append('file', selectedFile);

    // Fake progress while the server works (we don't get real streaming progress)
    let pct = 10;
    const ticker = setInterval(() => {
        pct = Math.min(pct + 4, 88);
        setProgress(pct, pct < 40 ? 'A enviar ficheiro…' :
                         pct < 75 ? 'A converter com LibreOffice…' :
                                    'A finalizar…');
    }, 600);

    let response;
    try {
        response = await fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': window.CW_ROUTES.csrfToken },
            body,
        });
    } finally {
        clearInterval(ticker);
    }

    // Backend returns JSON on error, binary file on success
    const contentType = response.headers.get('Content-Type') || '';
    if (!response.ok || contentType.includes('application/json')) {
        const json = await response.json().catch(() => ({ error: 'Erro desconhecido.' }));
        throw new Error(json.error || `HTTP ${response.status}`);
    }

    setProgress(95, 'A descarregar…');
    const blob = await response.blob();
    setProgress(100, 'Concluído!');

    // Trigger browser download
    const a   = document.createElement('a');
    a.href     = URL.createObjectURL(blob);
    a.download = baseName + outExt;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    setTimeout(() => URL.revokeObjectURL(a.href), 10000);

    setTimeout(() => $('cw-progress').classList.add('d-none'), 2000);
}
