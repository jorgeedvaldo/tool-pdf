// ═══════════════════════════════════════════════════════════════════════════
//  Flatten PDF  –  browser-side via pdf-lib (window.PDFLib)
// ═══════════════════════════════════════════════════════════════════════════

const $ = id => document.getElementById(id);
const fmtBytes = b => b < 1048576 ? (b / 1024).toFixed(1) + ' KB' : (b / 1048576).toFixed(1) + ' MB';

let selectedFile = null;

// ── Drop zone ─────────────────────────────────────────────────────────────
const zone  = $('fp-zone');
const input = $('fp-input');

zone.addEventListener('click', () => input.click());

zone.addEventListener('dragover', e => {
    e.preventDefault();
    zone.classList.add('fp-drag-over');
});

zone.addEventListener('dragleave', () => zone.classList.remove('fp-drag-over'));

zone.addEventListener('drop', e => {
    e.preventDefault();
    zone.classList.remove('fp-drag-over');
    if (e.dataTransfer.files[0]) onFileSelected(e.dataTransfer.files[0]);
});

input.addEventListener('change', e => {
    if (e.target.files[0]) onFileSelected(e.target.files[0]);
});

// ── File selection ─────────────────────────────────────────────────────────
function onFileSelected(file) {
    const ext = file.name.slice(file.name.lastIndexOf('.')).toLowerCase();
    if (ext !== '.pdf' && file.type !== 'application/pdf') {
        showError('Please select a valid PDF file.');
        return;
    }

    selectedFile = file;
    hideError();

    $('fp-name').textContent = file.name;
    $('fp-size').textContent = fmtBytes(file.size);
    zone.classList.add('d-none');
    $('fp-card').classList.remove('d-none');
    $('fp-btn').disabled = false;
}

// ── Remove button ─────────────────────────────────────────────────────────
$('fp-remove').addEventListener('click', resetFile);

function resetFile() {
    selectedFile = null;
    input.value = '';
    $('fp-card').classList.add('d-none');
    zone.classList.remove('d-none');
    $('fp-btn').disabled = true;
    $('fp-progress').classList.add('d-none');
    hideError();
}

// ── UI helpers ─────────────────────────────────────────────────────────────
function showError(msg) {
    const el = $('fp-error');
    el.textContent = msg;
    el.classList.remove('d-none');
}

function hideError() {
    $('fp-error').classList.add('d-none');
}

function setProgress(pct, msg) {
    $('fp-progress').classList.remove('d-none');
    $('fp-progress-bar').style.width = pct + '%';
    if (msg !== undefined) $('fp-progress-msg').textContent = msg;
}

// ── Flatten logic ─────────────────────────────────────────────────────────
function downloadBytes(bytes, filename) {
    const blob = new Blob([bytes], { type: 'application/pdf' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    setTimeout(() => URL.revokeObjectURL(url), 10000);
}

async function flattenPdf(file) {
    const { PDFDocument } = PDFLib;
    const ab = await file.arrayBuffer();
    const pdfDoc = await PDFDocument.load(ab);
    const form = pdfDoc.getForm();
    form.flatten();
    const bytes = await pdfDoc.save();
    downloadBytes(bytes, file.name.replace(/\.pdf$/i, '_flattened.pdf'));
}

// ── Convert button ─────────────────────────────────────────────────────────
$('fp-btn').addEventListener('click', async () => {
    if (!selectedFile) return;

    hideError();
    $('fp-btn').disabled = true;

    setProgress(10, 'Loading PDF…');

    // Fake progress ticker while pdf-lib works
    let pct = 10;
    const ticker = setInterval(() => {
        pct = Math.min(pct + 5, 88);
        const msg = pct < 40 ? 'Loading PDF…'
                  : pct < 75 ? 'Flattening fields…'
                  :             'Almost done…';
        setProgress(pct, msg);
    }, 600);

    try {
        await flattenPdf(selectedFile);

        clearInterval(ticker);
        setProgress(100, 'Done!');
        setTimeout(() => $('fp-progress').classList.add('d-none'), 2000);
    } catch (err) {
        clearInterval(ticker);
        $('fp-progress').classList.add('d-none');
        console.error(err);
        showError('Error flattening PDF: ' + (err.message || 'Unknown error.'));
    } finally {
        $('fp-btn').disabled = false;
    }
});
