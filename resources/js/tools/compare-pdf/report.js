export function generateReport(state) {
    const { results, originalFile, modifiedFile, originalPdf, modifiedPdf } = state;
    const { pages, summary } = results;

    return {
        meta: {
            originalFile: originalFile?.name ?? 'Unknown',
            modifiedFile: modifiedFile?.name ?? 'Unknown',
            comparedAt: new Date().toISOString(),
            originalPages: originalPdf?.numPages ?? 0,
            modifiedPages: modifiedPdf?.numPages ?? 0,
        },
        summary,
        pages: pages.map(p => ({
            pageNumber: p.pageNumber,
            status: p.status,
            diffPercentage: p.visual?.diffPercentage ?? 0,
            addedWords: p.text?.addedWords ?? 0,
            removedWords: p.text?.removedWords ?? 0,
        })),
    };
}

export function exportAsJSON(report) {
    _downloadBlob(
        new Blob([JSON.stringify(report, null, 2)], { type: 'application/json' }),
        'pdf-comparison-report.json'
    );
}

export function exportAsHTML(report) {
    _downloadBlob(
        new Blob([_buildHTMLReport(report)], { type: 'text/html' }),
        'pdf-comparison-report.html'
    );
}

function _downloadBlob(blob, filename) {
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

function _statusBadge(status) {
    const colors = { changed: '#E5322D', unchanged: '#22c55e', added: '#3b82f6', removed: '#f59e0b' };
    return `<span style="color:${colors[status] ?? '#666'};font-weight:bold">${status}</span>`;
}

function _buildHTMLReport(report) {
    const s = report.summary ?? {};
    const rows = report.pages
        .map(p => `<tr>
            <td style="padding:8px 12px;border-bottom:1px solid #eee">Page ${p.pageNumber}</td>
            <td style="padding:8px 12px;border-bottom:1px solid #eee">${_statusBadge(p.status)}</td>
            <td style="padding:8px 12px;border-bottom:1px solid #eee">${(p.diffPercentage ?? 0).toFixed(2)}%</td>
            <td style="padding:8px 12px;border-bottom:1px solid #eee">+${p.addedWords} / -${p.removedWords}</td>
        </tr>`)
        .join('');

    return `<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>PDF Comparison Report</title>
<style>
  body{font-family:Arial,sans-serif;max-width:900px;margin:0 auto;padding:24px;color:#222}
  .hdr{background:#E5322D;color:#fff;padding:24px;border-radius:8px;margin-bottom:24px}
  .hdr h1{margin:0 0 8px}
  .grid{display:grid;grid-template-columns:repeat(2,1fr);gap:16px;margin-bottom:24px}
  .stat{background:#f5f5f5;padding:16px;border-radius:8px}
  .stat-val{font-size:28px;font-weight:700;color:#E5322D}
  table{width:100%;border-collapse:collapse}
  th{text-align:left;padding:10px 12px;background:#f5f5f5;font-size:13px}
</style>
</head>
<body>
<div class="hdr">
  <h1>PDF Comparison Report</h1>
  <p style="margin:0;opacity:.85">Original: <strong>${report.meta.originalFile}</strong> &nbsp;|&nbsp; Modified: <strong>${report.meta.modifiedFile}</strong></p>
  <p style="margin:4px 0 0;opacity:.7;font-size:13px">Generated: ${new Date(report.meta.comparedAt).toLocaleString()}</p>
</div>
<div class="grid">
  <div class="stat"><div class="stat-val">${report.meta.originalPages}</div>Original Pages</div>
  <div class="stat"><div class="stat-val">${report.meta.modifiedPages}</div>Modified Pages</div>
  <div class="stat"><div class="stat-val">${s.changedPages ?? 0}</div>Changed Pages</div>
  <div class="stat"><div class="stat-val">${(s.avgDiffPercentage ?? 0).toFixed(1)}%</div>Avg. Visual Difference</div>
</div>
<h2>Page Details</h2>
<table>
  <thead><tr><th>Page</th><th>Status</th><th>Visual Diff</th><th>Text Changes</th></tr></thead>
  <tbody>${rows}</tbody>
</table>
</body>
</html>`;
}
