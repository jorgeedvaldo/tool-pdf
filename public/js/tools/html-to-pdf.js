// ═══════════════════════════════════════════════════════════════════════════
//  HTML / Webpage to PDF  –  server-side conversion
//  Config injected via window.HP_ROUTE and window.HP_CSRF by the Blade view.
// ═══════════════════════════════════════════════════════════════════════════

const $ = id => document.getElementById(id);

const urlInput   = $('hp-url');
const convertBtn = $('hp-convert-btn');
const progressEl = $('hp-progress');
const progressBar = $('hp-progress-bar');
const progressMsg = $('hp-progress-msg');
const errorEl    = $('hp-error');

// ── Enable button when URL is non-empty ───────────────────────────────────
urlInput.addEventListener('input', () => {
    convertBtn.disabled = urlInput.value.trim() === '';
});

urlInput.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !convertBtn.disabled) convertBtn.click();
});

// ── UI helpers ────────────────────────────────────────────────────────────
function showError(msg) {
    errorEl.textContent = msg;
    errorEl.classList.remove('d-none');
}

function hideError() {
    errorEl.classList.add('d-none');
}

function setProgress(pct, msg) {
    progressEl.classList.remove('d-none');
    progressBar.style.width = pct + '%';
    if (msg !== undefined) progressMsg.textContent = msg;
}

function resetProgress() {
    progressEl.classList.add('d-none');
    progressBar.style.width = '0%';
}

// ── Convert button ────────────────────────────────────────────────────────
convertBtn.addEventListener('click', () => {
    const raw = urlInput.value.trim();

    if (!raw.match(/^https?:\/\/.+/i)) {
        showError('Please enter a valid URL starting with http:// or https://');
        return;
    }

    hideError();
    convertBtn.disabled = true;

    doConvert(raw).finally(() => {
        convertBtn.disabled = urlInput.value.trim() === '';
    });
});

// ═══════════════════════════════════════════════════════════════════════════
//  Core: POST url → backend → download PDF
// ═══════════════════════════════════════════════════════════════════════════
async function doConvert(url) {
    setProgress(10, 'Sending request…');

    // Fake progress ticker while the server works
    let pct = 10;
    const ticker = setInterval(() => {
        pct = Math.min(pct + 3, 88);
        const msg = pct < 35 ? 'Sending request…'
                  : pct < 75 ? 'Rendering page…'
                  :             'Almost done…';
        setProgress(pct, msg);
    }, 600);

    let response;
    try {
        response = await fetch(window.HP_ROUTE, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.HP_CSRF,
            },
            body: JSON.stringify({ url }),
        });
    } finally {
        clearInterval(ticker);
    }

    const contentType = response.headers.get('Content-Type') || '';

    if (!response.ok || contentType.includes('application/json')) {
        const json = await response.json().catch(() => ({ error: 'An unknown error occurred.' }));
        resetProgress();
        showError(json.error || `Server error (HTTP ${response.status})`);
        return;
    }

    setProgress(95, 'Preparing download…');
    const blob = await response.blob();
    setProgress(100, 'Done!');

    // Derive filename from hostname
    let filename = 'page.pdf';
    try {
        const hostname = new URL(url).hostname.replace(/^www\./, '');
        if (hostname) filename = hostname + '.pdf';
    } catch (_) { /* keep default */ }

    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    setTimeout(() => URL.revokeObjectURL(a.href), 10000);

    setTimeout(() => progressEl.classList.add('d-none'), 2000);
}
