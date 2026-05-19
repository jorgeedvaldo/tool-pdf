// ═══════════════════════════════════════════════════════════════════════════
//  Generic single-file conversion tool  –  server-side
//  Config is injected via window.CG_CONFIG by the Blade view.
// ═══════════════════════════════════════════════════════════════════════════

const $ = id => document.getElementById(id);
const fmtBytes = b => b < 1048576 ? (b / 1024).toFixed(1) + ' KB' : (b / 1048576).toFixed(1) + ' MB';

const cfg = window.CG_CONFIG;

/** Accepted extensions parsed from the accept string, e.g. ".xlsx,.xls" → [".xlsx", ".xls"] */
const acceptedExts = cfg.accept
    .split(',')
    .map(s => s.trim().toLowerCase())
    .filter(s => s.startsWith('.'));

let selectedFile = null;

// ── Drop zone ─────────────────────────────────────────────────────────────────
const zone  = $('cg-zone');
const input = $('cg-input');

zone.addEventListener('click', () => input.click());

zone.addEventListener('dragover', e => {
    e.preventDefault();
    zone.classList.add('cg-drag-over');
});

zone.addEventListener('dragleave', () => zone.classList.remove('cg-drag-over'));

zone.addEventListener('drop', e => {
    e.preventDefault();
    zone.classList.remove('cg-drag-over');
    if (e.dataTransfer.files[0]) onFileSelected(e.dataTransfer.files[0]);
});

input.addEventListener('change', e => {
    if (e.target.files[0]) onFileSelected(e.target.files[0]);
});

// ── File selection ────────────────────────────────────────────────────────────
function onFileSelected(file) {
    const ext = file.name.slice(file.name.lastIndexOf('.')).toLowerCase();
    if (acceptedExts.length && !acceptedExts.includes(ext)) {
        showError('Invalid file type. Accepted formats: ' + acceptedExts.join(', '));
        return;
    }

    selectedFile = file;
    hideError();

    $('cg-name').textContent = file.name;
    $('cg-size').textContent = fmtBytes(file.size);
    zone.classList.add('d-none');
    $('cg-card').classList.remove('d-none');
    $('cg-convert-btn').disabled = false;
}

// ── Remove button ─────────────────────────────────────────────────────────────
$('cg-remove').addEventListener('click', resetFile);

function resetFile() {
    selectedFile = null;
    input.value = '';
    $('cg-card').classList.add('d-none');
    zone.classList.remove('d-none');
    $('cg-convert-btn').disabled = true;
    $('cg-progress').classList.add('d-none');
    hideError();
}

// ── UI helpers ────────────────────────────────────────────────────────────────
function showError(msg) {
    const el = $('cg-error');
    el.textContent = msg;
    el.classList.remove('d-none');
}

function hideError() {
    $('cg-error').classList.add('d-none');
}

function setProgress(pct, msg) {
    $('cg-progress').classList.remove('d-none');
    $('cg-progress-bar').style.width = pct + '%';
    if (msg !== undefined) $('cg-progress-msg').textContent = msg;
}

// ── Convert button ────────────────────────────────────────────────────────────
$('cg-convert-btn').addEventListener('click', () => {
    if (!selectedFile) return;
    $('cg-convert-btn').disabled = true;
    hideError();
    sendConvert(selectedFile).finally(() => {
        $('cg-convert-btn').disabled = false;
    });
});

// ═══════════════════════════════════════════════════════════════════════════
//  Core: upload → backend → download result
// ═══════════════════════════════════════════════════════════════════════════
async function sendConvert(file) {
    const baseName  = file.name.replace(/\.[^.]+$/, '');
    const outName   = baseName + cfg.outputExt;

    setProgress(10, 'Uploading file…');

    const body = new FormData();
    body.append('file', file);

    // Fake progress ticker while the server works
    let pct = 10;
    const ticker = setInterval(() => {
        pct = Math.min(pct + 3, 88);
        const msg = pct < 35 ? 'Uploading file…'
                  : pct < 75 ? 'Converting…'
                  :             'Almost done…';
        setProgress(pct, msg);
    }, 500);

    let response;
    try {
        response = await fetch(cfg.route, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': cfg.csrfToken },
            body,
        });
    } finally {
        clearInterval(ticker);
    }

    const contentType = response.headers.get('Content-Type') || '';

    if (!response.ok || contentType.includes('application/json')) {
        const json = await response.json().catch(() => ({ error: 'An unknown error occurred.' }));
        setProgress(0, '');
        $('cg-progress').classList.add('d-none');
        showError(json.error || `Server error (HTTP ${response.status})`);
        return;
    }

    setProgress(95, 'Preparing download…');
    const blob = await response.blob();
    setProgress(100, 'Done!');

    // Trigger browser download
    const a   = document.createElement('a');
    a.href     = URL.createObjectURL(blob);
    a.download = outName;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    setTimeout(() => URL.revokeObjectURL(a.href), 10000);

    setTimeout(() => $('cg-progress').classList.add('d-none'), 2000);
}
