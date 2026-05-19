// ═══════════════════════════════════════════════════════════════════════════
//  Repair PDF  –  browser-side via pdf-lib (window.PDFLib)
//  Re-loads and re-saves the PDF to fix minor corruption / truncation.
// ═══════════════════════════════════════════════════════════════════════════

const $ = id => document.getElementById(id);
const fmtBytes = b => b < 1048576 ? (b / 1024).toFixed(1) + ' KB' : (b / 1048576).toFixed(1) + ' MB';

let selectedFile = null;

// ── Drop zone ─────────────────────────────────────────────────────────────
const zone  = $('rp-zone');
const input = $('rp-input');

zone.addEventListener('click', () => input.click());

zone.addEventListener('dragover', e => {
    e.preventDefault();
    zone.classList.add('rp-drag-over');
});

zone.addEventListener('dragleave', () => zone.classList.remove('rp-drag-over'));

zone.addEventListener('drop', e => {
    e.preventDefault();
    zone.classList.remove('rp-drag-over');
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

    $('rp-name').textContent = file.name;
    $('rp-size').textContent = fmtBytes(file.size);
    zone.classList.add('d-none');
    $('rp-card').classList.remove('d-none');
    $('rp-btn').disabled = false;
}

// ── Remove button ─────────────────────────────────────────────────────────
$('rp-remove').addEventListener('click', resetFile);

function resetFile() {
    selectedFile = null;
    input.value = '';
    $('rp-card').classList.add('d-none');
    zone.classList.remove('d-none');
    $('rp-btn').disabled = true;
    $('rp-progress').classList.add('d-none');
    hideError();
}

// ── UI helpers ─────────────────────────────────────────────────────────────
function showError(msg) {
    const el = $('rp-error');
    el.textContent = msg;
    el.classList.remove('d-none');
}

function hideError() {
    $('rp-error').classList.add('d-none');
}

function setProgress(pct, msg) {
    $('rp-progress').classList.remove('d-none');
    $('rp-progress-bar').style.width = pct + '%';
    if (msg !== undefined) $('rp-progress-msg').textContent = msg;
}

// ── Repair logic ──────────────────────────────────────────────────────────
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

async function repairPdf(file) {
    const { PDFDocument } = PDFLib;
    const ab = await file.arrayBuffer();
    // ignoreEncryption allows loading some corrupted/encrypted PDFs
    const pdfDoc = await PDFDocument.load(ab, { ignoreEncryption: true, updateMetadata: false });
    const bytes = await pdfDoc.save();
    downloadBytes(bytes, file.name.replace(/\.pdf$/i, '_repaired.pdf'));
}

// ── Convert button ─────────────────────────────────────────────────────────
$('rp-btn').addEventListener('click', async () => {
    if (!selectedFile) return;

    hideError();
    $('rp-btn').disabled = true;

    setProgress(10, 'Loading PDF…');

    // Fake progress ticker while pdf-lib works
    let pct = 10;
    const ticker = setInterval(() => {
        pct = Math.min(pct + 5, 88);
        const msg = pct < 40 ? 'Loading PDF…'
                  : pct < 75 ? 'Repairing structure…'
                  :             'Almost done…';
        setProgress(pct, msg);
    }, 600);

    try {
        await repairPdf(selectedFile);

        clearInterval(ticker);
        setProgress(100, 'Done!');
        setTimeout(() => $('rp-progress').classList.add('d-none'), 2000);
    } catch (err) {
        clearInterval(ticker);
        $('rp-progress').classList.add('d-none');
        console.error(err);
        showError('Error repairing PDF: ' + (err.message || 'Unknown error.'));
    } finally {
        $('rp-btn').disabled = false;
    }
});
