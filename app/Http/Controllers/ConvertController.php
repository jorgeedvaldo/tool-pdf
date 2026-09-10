<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpPresentation\IOFactory as PresentationIOFactory;
use PhpOffice\PhpPresentation\PhpPresentation;
use PhpOffice\PhpPresentation\Shape\RichText as RichTextShape;
use Smalot\PdfParser\Parser as PdfParser;
use Mpdf\Mpdf;
use Mpdf\HTMLParserMode;

class ConvertController extends Controller
{
    private const MAX_MB = 50;

    // ─────────────────────────────────────────────────────────────────────────
    //  PDF → Word (.docx)
    //  Uses smalot/pdfparser to extract text + phpoffice/phpword to write docx.
    //  Applies structured text analysis: headings, lists, tables, paragraphs.
    // ─────────────────────────────────────────────────────────────────────────
    public function pdfToWord(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:' . (self::MAX_MB * 1024),
        ]);

        $tmpDir = $this->makeTmpDir();

        try {
            $inputPath  = $tmpDir . '/input.pdf';
            $outputPath = $tmpDir . '/output.docx';
            $request->file('file')->move($tmpDir, 'input.pdf');

            $parser = new PdfParser();
            $pdf    = $parser->parseFile($inputPath);
            $pages  = $pdf->getPages();

            $phpWord = new \PhpOffice\PhpWord\PhpWord();
            $phpWord->setDefaultFontName('Calibri');
            $phpWord->setDefaultFontSize(11);

            $twip = fn(float $cm) => \PhpOffice\PhpWord\Shared\Converter::cmToTwip($cm);
            $sectionStyle = [
                'marginTop'    => $twip(2.54),
                'marginBottom' => $twip(2.54),
                'marginLeft'   => $twip(2.54),
                'marginRight'  => $twip(2.54),
            ];

            $pageCount = count($pages);
            foreach ($pages as $i => $page) {
                $section = $phpWord->addSection($sectionStyle);
                $rawText = $page->getText();

                if (!trim($rawText)) {
                    $section->addText(
                        '(Page ' . ($i + 1) . ': no extractable text — may be a scanned image)',
                        ['italic' => true, 'color' => '888888', 'size' => 10]
                    );
                    if ($i < $pageCount - 1) $section->addPageBreak();
                    continue;
                }

                $blocks = $this->analyzeTextBlocks($rawText);
                $this->renderBlocksToSection($section, $blocks, $phpWord);

                if ($i < $pageCount - 1) $section->addPageBreak();
            }

            if (count($phpWord->getSections()) === 0) {
                $phpWord->addSection($sectionStyle)->addText('(No extractable text found in this PDF)');
            }

            $writer = WordIOFactory::createWriter($phpWord, 'Word2007');
            $writer->save($outputPath);

            $name = pathinfo($request->file('file')->getClientOriginalName(), PATHINFO_FILENAME);

            return response()->download($outputPath, $name . '.docx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])->deleteFileAfterSend(false);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        } finally {
            $this->scheduleTmpCleanup($tmpDir);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Word → PDF
    //  Reads .docx/.doc/.odt/.rtf with the matching PhpWord reader, then renders
    //  the intermediate HTML through mPDF one Word section at a time.
    // ─────────────────────────────────────────────────────────────────────────
    public function wordToPdf(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:' . (self::MAX_MB * 1024),
        ]);

        $file = $request->file('file');
        $ext  = strtolower($file->getClientOriginalExtension());

        if (!in_array($ext, ['docx', 'doc', 'odt', 'rtf'], true)) {
            return response()->json(['error' => 'Unsupported format. Use .docx, .doc, .odt or .rtf.'], 422);
        }

        $tmpDir = $this->makeTmpDir();

        try {
            $inputName  = 'input.' . $ext;
            $inputPath  = $tmpDir . '/' . $inputName;
            $outputPath = $tmpDir . '/output.pdf';
            $file->move($tmpDir, $inputName);

            // Long documents produce large intermediate HTML; mPDF parses it with
            // PCRE, which bails out silently on the default backtrack limit. mPDF
            // refuses to run with the limit disabled, so raise it instead.
            if ((int) ini_get('pcre.backtrack_limit') < 10000000) {
                @ini_set('pcre.backtrack_limit', '10000000');
            }
            // Rendering a few hundred pages takes tens of seconds and a few hundred
            // MB; without these, the request dies mid-render and the browser only
            // sees an empty non-JSON response.
            @set_time_limit(300);
            $memoryLimit = $this->memoryLimitInBytes();
            if ($memoryLimit > 0 && $memoryLimit < 512 * 1024 * 1024) {
                @ini_set('memory_limit', '512M');
            }

            // PhpWord's IOFactory::load() always uses the Word2007 reader unless a
            // reader is named, so an .odt silently produced an empty document and a
            // .doc/.rtf failed with a confusing ZIP error. Pick the reader from the
            // file's actual content, falling back to its extension.
            $reader  = $this->detectWordReader($inputPath, $ext);
            $phpWord = WordIOFactory::load($inputPath, $reader);

            if ($this->countWordElements($phpWord) === 0) {
                return response()->json([
                    'error' => 'This document appears to be empty, or its formatting could not be read.',
                ], 422);
            }

            $htmlPath = $tmpDir . '/document.html';
            WordIOFactory::createWriter($phpWord, 'HTML')->save($htmlPath);

            $mpdf = $this->renderWordHtmlToPdf(file_get_contents($htmlPath), $tmpDir, $phpWord);
            $mpdf->Output($outputPath, 'F');

            $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

            return response()->download($outputPath, $name . '.pdf', [
                'Content-Type' => 'application/pdf',
            ])->deleteFileAfterSend(false);

        } catch (\Throwable $e) {
            report($e);

            return response()->json(['error' => $this->wordToPdfErrorMessage($e, $ext)], 500);
        } finally {
            $this->scheduleTmpCleanup($tmpDir);
        }
    }

    /**
     * Choose the PhpWord reader that matches the file's real content. Users
     * routinely rename files, and every reader throws an opaque error when it is
     * handed a format it cannot parse.
     */
    private function detectWordReader(string $path, string $ext): string
    {
        $magic = (string) @file_get_contents($path, false, null, 0, 8);

        if (str_starts_with($magic, "PK\x03\x04")) {
            // Both .docx and .odt are ZIP containers; the entry names tell them apart.
            return $this->zipHasEntry($path, 'word/document.xml') ? 'Word2007' : 'ODText';
        }
        if (str_starts_with($magic, "\xD0\xCF\x11\xE0")) {
            return 'MsDoc'; // OLE2 compound file — legacy Word 97-2003
        }
        if (str_starts_with(ltrim($magic), '{\\rtf')) {
            return 'RTF';
        }

        return match ($ext) {
            'doc'   => 'MsDoc',
            'odt'   => 'ODText',
            'rtf'   => 'RTF',
            default => 'Word2007',
        };
    }

    private function zipHasEntry(string $path, string $entry): bool
    {
        if (!class_exists(\ZipArchive::class)) {
            return true; // cannot inspect; assume the common case (.docx)
        }

        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return false;
        }
        $found = $zip->locateName($entry) !== false;
        $zip->close();

        return $found;
    }

    /** @return int Bytes, or -1 when the limit is unlimited. */
    private function memoryLimitInBytes(): int
    {
        $limit = trim((string) ini_get('memory_limit'));
        if ($limit === '' || $limit === '-1') {
            return -1;
        }

        $value = (int) $limit;

        return match (strtolower(substr($limit, -1))) {
            'g'     => $value * 1024 * 1024 * 1024,
            'm'     => $value * 1024 * 1024,
            'k'     => $value * 1024,
            default => $value,
        };
    }

    private function countWordElements(\PhpOffice\PhpWord\PhpWord $phpWord): int
    {
        $count = 0;
        foreach ($phpWord->getSections() as $section) {
            $count += count($section->getElements());
        }

        return $count;
    }

    /**
     * Render the HTML produced by PhpWord into an mPDF instance.
     *
     * PhpWord emits one `<div style="page: pageN">` per Word section plus a
     * matching `@page pageN` rule holding that section's paper size, orientation
     * and margins. mPDF's named-page support does not cope with that markup and
     * restarts a page for every block it contains, which is what turned short
     * documents into PDFs thousands of pages long. The page setup is therefore
     * applied directly through mPDF's page API and the named-page CSS is dropped.
     */
    private function renderWordHtmlToPdf(string $html, string $tmpDir, \PhpOffice\PhpWord\PhpWord $phpWord): Mpdf
    {
        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $css = '';
        foreach (iterator_to_array($document->getElementsByTagName('style')) as $style) {
            $css .= $style->textContent . "\n";
        }
        $pageRules = $this->parseAtPageRules($css);
        // Page geometry is applied via AddPageByArray; the section breaks below
        // replace the `body > div + div` rule, whose divs are never written.
        $css = preg_replace('/@page\s+[A-Za-z0-9_-]+\s*\{[^}]*\}/i', '', $css);

        $sections = $this->splitHtmlIntoSections($document, $pageRules);

        $mpdf = $this->makeMpdfForSection($sections[0]['page'], $tmpDir);

        if (trim((string) $css) !== '') {
            $mpdf->WriteHTML($css, HTMLParserMode::HEADER_CSS);
        }

        // The HTML writer drops headers and footers entirely, so they are read
        // straight off the Word sections and attached to mPDF here.
        $chrome = $this->buildSectionChrome($phpWord);

        foreach ($sections as $index => $section) {
            $sectionChrome = $chrome[$section['word']] ?? ['header' => '', 'footer' => ''];

            // mPDF stamps a header when it opens a page but a footer when it
            // closes one, and AddPageByArray does both. Setting the header first
            // and the footer after therefore leaves the outgoing page with the
            // previous section's footer and gives the new page both of its own.
            // Passing '' clears the previous section's, which Word would not
            // carry over either.
            $mpdf->SetHTMLHeader($sectionChrome['header']);
            if ($index > 0) {
                $mpdf->AddPageByArray($section['page']);
            }
            $mpdf->SetHTMLFooter($sectionChrome['footer']);

            $this->writeNodesInChunks($mpdf, $document, $section['nodes']);
        }

        return $mpdf;
    }

    /**
     * Collect each Word section's header and footer as HTML, keyed by section
     * index.
     *
     * Word can also vary these by page — a distinct first page, or odd/even
     * pairs. mPDF reaches both only through its `@page` machinery, which takes
     * over page geometry for the whole document and re-breaks the pages, the
     * very behaviour that made these conversions run to thousands of pages. The
     * section's default variant is therefore used throughout, falling back to
     * another only when the section defines no default at all.
     *
     * @return array<int, array{header: string, footer: string}>
     */
    private function buildSectionChrome(\PhpOffice\PhpWord\PhpWord $phpWord): array
    {
        $writer = new \PhpOffice\PhpWord\Writer\HTML($phpWord);
        $chrome = [];

        foreach ($phpWord->getSections() as $index => $section) {
            $entry = ['header' => '', 'footer' => ''];
            $isDefault = ['header' => false, 'footer' => false];

            foreach (['header' => $section->getHeaders(), 'footer' => $section->getFooters()] as $slot => $parts) {
                foreach ($parts as $part) {
                    if ($isDefault[$slot]) {
                        continue; // the section's own default already won
                    }

                    $html = $this->renderHeaderFooter($part, $writer);
                    if ($html === '') {
                        continue;
                    }

                    $entry[$slot] = $html;
                    $isDefault[$slot] = $part->getType() === \PhpOffice\PhpWord\Element\Footer::AUTO;
                }
            }

            $chrome[$index] = $entry;
        }

        return $chrome;
    }

    /**
     * Render one header or footer container to HTML.
     *
     * @param \PhpOffice\PhpWord\Element\Footer $container
     */
    private function renderHeaderFooter($container, \PhpOffice\PhpWord\Writer\HTML $writer): string
    {
        $html = '';
        foreach ($container->getElements() as $element) {
            $html .= $this->renderHeaderFooterElement($element, $writer);
        }

        return trim($html);
    }

    private function renderHeaderFooterElement(object $element, \PhpOffice\PhpWord\Writer\HTML $writer): string
    {
        // A header paragraph containing fields is read back as a single
        // PreserveText holding the raw `{ PAGE }` macros, and PhpWord ships no
        // HTML writer for it or for Field, so both are rendered here.
        if ($element instanceof \PhpOffice\PhpWord\Element\PreserveText) {
            return $this->renderPreserveText($element);
        }
        if ($element instanceof \PhpOffice\PhpWord\Element\Field) {
            $placeholder = $this->wordFieldPlaceholder($element->getType());

            return $placeholder === '' ? '' : '<p>' . $placeholder . '</p>';
        }

        $writerClass = str_replace(
            'PhpOffice\\PhpWord\\Element',
            'PhpOffice\\PhpWord\\Writer\\HTML\\Element',
            get_class($element)
        );
        if (!class_exists($writerClass)) {
            return '';
        }

        return (string) (new $writerClass($writer, $element, false))->write();
    }

    private function renderPreserveText(\PhpOffice\PhpWord\Element\PreserveText $element): string
    {
        $segments = $element->getText();
        if (!is_array($segments)) {
            $segments = [(string) $segments];
        }

        $text = '';
        foreach ($segments as $segment) {
            if (preg_match('/^\{(.+)\}$/s', trim((string) $segment), $m)) {
                $text .= $this->wordFieldPlaceholder($m[1]);
                continue;
            }
            $text .= htmlspecialchars((string) $segment, ENT_QUOTES, 'UTF-8');
        }

        if (trim($text) === '') {
            return '';
        }

        $style = $element->getParagraphStyle();
        $alignment = is_object($style) && method_exists($style, 'getAlignment') ? $style->getAlignment() : null;

        return '<p' . ($alignment ? ' style="text-align: ' . $alignment . ';"' : '') . '>' . $text . '</p>';
    }

    /** Translate a Word field into the placeholder mPDF substitutes per page. */
    private function wordFieldPlaceholder(string $macro): string
    {
        $parts = preg_split('#[\s\\\\]+#', trim($macro)) ?: [];
        $name  = strtoupper($parts[0] ?? '');

        return match ($name) {
            'PAGE'                                     => '{PAGENO}',
            'NUMPAGES', 'SECTIONPAGES'                 => '{nb}',
            'DATE', 'CREATEDATE', 'SAVEDATE', 'PRINTDATE' => '{DATE j/n/Y}',
            'TIME'                                     => '{DATE H:i}',
            // Anything else (MERGEFIELD, REF, TOC…) has no per-page meaning here;
            // printing the raw macro would be worse than leaving it out.
            default                                    => '',
        };
    }

    /**
     * Group the body's children into one entry per Word section, each carrying the
     * page setup from its `@page` rule.
     *
     * `word` is the index of the Word section a group came from, used to look up
     * its header and footer; content outside any section div has none.
     *
     * @param  array<string, array<string, mixed>>  $pageRules
     * @return array<int, array{page: array<string, mixed>, nodes: array<int, \DOMNode>, word: int|null}>
     */
    private function splitHtmlIntoSections(\DOMDocument $document, array $pageRules): array
    {
        $body = $document->getElementsByTagName('body')->item(0);
        $topLevel = $body ? iterator_to_array($body->childNodes) : iterator_to_array($document->childNodes);

        $sections = [];
        $loose    = [];
        $wordIndex = 0;

        foreach ($topLevel as $node) {
            if ($node instanceof \DOMElement && strtolower($node->tagName) === 'div') {
                $name = null;
                if (preg_match('/page\s*:\s*([A-Za-z0-9_-]+)/i', (string) $node->getAttribute('style'), $m)) {
                    $name = strtolower($m[1]);
                }
                $sections[] = [
                    'page'  => $pageRules[$name] ?? [],
                    'nodes' => iterator_to_array($node->childNodes),
                    'word'  => $wordIndex++,
                ];
                continue;
            }
            if ($node instanceof \DOMText && trim($node->textContent) === '') {
                continue;
            }
            if (!($node instanceof \DOMElement && strtolower($node->tagName) === 'style')) {
                $loose[] = $node;
            }
        }

        if ($loose !== []) {
            array_unshift($sections, ['page' => reset($pageRules) ?: [], 'nodes' => $loose, 'word' => null]);
        }
        if ($sections === []) {
            $sections[] = ['page' => reset($pageRules) ?: [], 'nodes' => $topLevel, 'word' => 0];
        }

        return $sections;
    }

    /** @param array<string, mixed> $page */
    private function makeMpdfForSection(array $page, string $tmpDir): Mpdf
    {
        $config = [
            'tempDir'      => $tmpDir,
            'default_font' => 'dejavusans',
        ];
        foreach ([
            'margin-left'   => 'margin_left',
            'margin-right'  => 'margin_right',
            'margin-top'    => 'margin_top',
            'margin-bottom' => 'margin_bottom',
        ] as $cssProp => $option) {
            if (isset($page[$cssProp])) {
                $config[$option] = $page[$cssProp];
            }
        }

        $format = ($page['sheet-size'] ?? 'A4') . (($page['orientation'] ?? 'P') === 'L' ? '-L' : '');

        try {
            $mpdf = new Mpdf($config + ['format' => $format]);
        } catch (\Throwable $e) {
            $mpdf = new Mpdf($config + ['format' => 'A4']); // unrecognised Word paper size
        }

        // PhpWord inlines images as data URIs, but keep relative references from
        // resolving anywhere outside the request's own temporary directory.
        $mpdf->SetBasePath($tmpDir . '/');

        return $mpdf;
    }

    /**
     * Parse the `@page pageN { … }` rules PhpWord writes into mPDF page options.
     *
     * @return array<string, array<string, mixed>>
     */
    private function parseAtPageRules(string $css): array
    {
        if (!preg_match_all('/@page\s+([A-Za-z0-9_-]+)\s*\{([^}]*)\}/i', $css, $matches, PREG_SET_ORDER)) {
            return [];
        }

        $rules = [];
        foreach ($matches as $match) {
            $page = [];
            foreach (explode(';', $match[2]) as $declaration) {
                if (!str_contains($declaration, ':')) {
                    continue;
                }
                [$property, $value] = array_map('trim', explode(':', $declaration, 2));
                $property = strtolower($property);

                if ($property === 'size' || $property === 'sheet-size') {
                    foreach (preg_split('/\s+/', strtolower($value)) ?: [] as $token) {
                        if ($token === 'landscape') {
                            $page['orientation'] = 'L';
                        } elseif ($token === 'portrait') {
                            $page['orientation'] = 'P';
                        } elseif (str_ends_with($token, '-l')) {
                            $page['orientation'] = 'L';
                            $page['sheet-size']  = strtoupper(substr($token, 0, -2));
                        } elseif ($token !== '') {
                            $page['sheet-size'] = strtoupper($token);
                        }
                    }
                } elseif (in_array($property, ['margin-left', 'margin-right', 'margin-top', 'margin-bottom'], true)) {
                    $mm = $this->cssLengthToMm($value);
                    if ($mm !== null) {
                        $page[$property] = round($mm, 2);
                    }
                }
            }
            $rules[strtolower($match[1])] = $page;
        }

        return $rules;
    }

    private function cssLengthToMm(string $value): ?float
    {
        if (!preg_match('/^(-?[\d.]+)\s*(in|cm|mm|pt|pc|px)?$/i', trim($value), $m)) {
            return null;
        }

        $number = (float) $m[1];

        return match (strtolower($m[2] ?? 'px')) {
            'in'    => $number * 25.4,
            'cm'    => $number * 10,
            'mm'    => $number,
            'pt'    => $number * 25.4 / 72,
            'pc'    => $number * 25.4 / 6,
            default => $number * 25.4 / 96,
        };
    }

    /**
     * Feed a section to mPDF in batches of whole block elements, so PCRE never
     * sees the entire document at once and no chunk ever splits a tag.
     *
     * @param array<int, \DOMNode> $nodes
     */
    private function writeNodesInChunks(Mpdf $mpdf, \DOMDocument $document, array $nodes, int $maxChunkBytes = 200000): void
    {
        $buffer = '';

        foreach ($nodes as $node) {
            $fragment = $document->saveHTML($node);
            if ($fragment === false || $fragment === '') {
                continue;
            }

            if ($buffer !== '' && strlen($buffer) + strlen($fragment) > $maxChunkBytes) {
                $mpdf->WriteHTML($buffer, HTMLParserMode::HTML_BODY);
                $buffer = '';
            }
            $buffer .= $fragment;
        }

        if ($buffer !== '') {
            $mpdf->WriteHTML($buffer, HTMLParserMode::HTML_BODY);
        }
    }

    private function wordToPdfErrorMessage(\Throwable $e, string $ext): string
    {
        if ($e instanceof \PhpOffice\PhpWord\Exception\Exception || str_contains($e->getMessage(), 'archive failed to load')) {
            return "This .{$ext} file could not be read. It may be corrupted, password-protected, "
                 . 'or saved in a different format than its extension suggests.';
        }

        return $e->getMessage();
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Excel → PDF
    // ─────────────────────────────────────────────────────────────────────────
    public function excelToPdf(Request $request)
    {
        $request->validate(['file' => 'required|file|max:' . (self::MAX_MB * 1024)]);
        $file = $request->file('file');
        $ext  = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['xlsx', 'xls', 'ods', 'csv'], true)) {
            return response()->json(['error' => 'Unsupported format. Use .xlsx, .xls, .ods or .csv.'], 422);
        }

        $tmpDir = $this->makeTmpDir();
        try {
            $inputPath  = $tmpDir . '/input.' . $ext;
            $htmlPath   = $tmpDir . '/output.html';
            $outputPath = $tmpDir . '/output.pdf';
            $file->move($tmpDir, 'input.' . $ext);

            $spreadsheet = SpreadsheetIOFactory::load($inputPath);

            $writer = SpreadsheetIOFactory::createWriter($spreadsheet, 'Html');
            $writer->save($htmlPath);

            $mpdf = new Mpdf([
                'format'        => 'A4-L',
                'margin_top'    => 10,
                'margin_bottom' => 10,
                'margin_left'   => 8,
                'margin_right'  => 8,
                'default_font'  => 'dejavusans',
            ]);
            $mpdf->simpleTables = true;
            $mpdf->WriteHTML(file_get_contents($htmlPath));
            $mpdf->Output($outputPath, 'F');

            $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            return response()->download($outputPath, $name . '.pdf', ['Content-Type' => 'application/pdf'])
                             ->deleteFileAfterSend(false);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        } finally {
            $this->scheduleTmpCleanup($tmpDir);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  PDF → Excel
    //  Detects column structure via consistent spacing patterns.
    // ─────────────────────────────────────────────────────────────────────────
    public function pdfToExcel(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:pdf|max:' . (self::MAX_MB * 1024)]);

        $tmpDir = $this->makeTmpDir();
        try {
            $inputPath  = $tmpDir . '/input.pdf';
            $outputPath = $tmpDir . '/output.xlsx';
            $request->file('file')->move($tmpDir, 'input.pdf');

            $parser      = new PdfParser();
            $pdf         = $parser->parseFile($inputPath);
            $pages       = $pdf->getPages();
            $spreadsheet = new Spreadsheet();

            foreach ($pages as $i => $page) {
                $sheet = $i === 0
                    ? $spreadsheet->getActiveSheet()->setTitle('Page ' . ($i + 1))
                    : $spreadsheet->createSheet()->setTitle('Page ' . ($i + 1));

                $lines = array_filter(array_map('trim', explode("\n", $page->getText())));
                $row   = 1;
                foreach ($lines as $line) {
                    // Use tab or 2+ spaces as column separator
                    $cells = preg_split('/\t|\s{2,}/', $line);
                    foreach ($cells as $col => $cell) {
                        $cell = trim($cell);
                        // Auto-detect numeric values for proper cell type
                        if (is_numeric(str_replace([',', '.'], ['', '.'], $cell))) {
                            $sheet->setCellValue([$col + 1, $row], (float) str_replace(',', '.', $cell));
                        } else {
                            $sheet->setCellValue([$col + 1, $row], $cell);
                        }
                    }
                    // Auto-fit the header row
                    if ($row === 1) {
                        foreach ($cells as $col => $_) {
                            $sheet->getColumnDimensionByColumn($col + 1)->setAutoSize(true);
                        }
                    }
                    $row++;
                }
            }

            $writer = SpreadsheetIOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($outputPath);

            $name = pathinfo($request->file('file')->getClientOriginalName(), PATHINFO_FILENAME);
            return response()->download($outputPath, $name . '.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(false);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        } finally {
            $this->scheduleTmpCleanup($tmpDir);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  PDF → PowerPoint (.pptx)
    //  Detects title vs body text per page for better slide structure.
    // ─────────────────────────────────────────────────────────────────────────
    public function pdfToPpt(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:pdf|max:' . (self::MAX_MB * 1024)]);

        $tmpDir = $this->makeTmpDir();
        try {
            $inputPath  = $tmpDir . '/input.pdf';
            $outputPath = $tmpDir . '/output.pptx';
            $request->file('file')->move($tmpDir, 'input.pdf');

            $parser       = new PdfParser();
            $pdf          = $parser->parseFile($inputPath);
            $pages        = $pdf->getPages();
            $presentation = new PhpPresentation();
            $presentation->removeSlide(0);

            foreach ($pages as $i => $page) {
                $rawText = trim($page->getText());
                $slide   = $presentation->createSlide();

                if (!$rawText) {
                    $empty = $slide->createRichTextShape();
                    $empty->setWidth(680)->setHeight(36)->setOffsetX(30)->setOffsetY(16);
                    $empty->getActiveParagraph()->createTextRun('Page ' . ($i + 1))
                          ->getFont()->setBold(true)->setSize(14)
                          ->setColor(new \PhpOffice\PhpPresentation\Style\Color('FF333333'));
                    continue;
                }

                $lines  = array_values(array_filter(array_map('trim', explode("\n", $rawText))));
                $blocks = $this->analyzeTextBlocks($rawText);

                // First heading-like block becomes the slide title
                $titleText = null;
                $bodyBlocks = [];
                foreach ($blocks as $block) {
                    if ($titleText === null && in_array($block['type'], ['h1', 'h2', 'h3'], true)) {
                        $titleText = $block['text'];
                    } else {
                        $bodyBlocks[] = $block;
                    }
                }

                // Fallback: use first non-empty line as title
                if ($titleText === null && !empty($lines)) {
                    $titleText  = array_shift($lines);
                    $bodyBlocks = array_map(fn($l) => ['type' => 'paragraph', 'text' => $l], $lines);
                }

                // Title shape
                $titleShape = $slide->createRichTextShape();
                $titleShape->setWidth(680)->setHeight(52)->setOffsetX(30)->setOffsetY(16);
                $run = $titleShape->getActiveParagraph()->createTextRun($titleText ?? ('Page ' . ($i + 1)));
                $run->getFont()->setBold(true)->setSize(20)
                    ->setColor(new \PhpOffice\PhpPresentation\Style\Color('FF1a1a2e'));

                // Body shape
                $body    = $slide->createRichTextShape();
                $body->setWidth(680)->setHeight(430)->setOffsetX(30)->setOffsetY(80);
                $isFirst = true;

                foreach ($bodyBlocks as $block) {
                    $text = $block['text'] ?? '';
                    if (!trim($text)) continue;
                    if (!$isFirst) $body->createParagraph();

                    $prefix = match ($block['type']) {
                        'bullet'   => '• ',
                        'numbered' => '',
                        default    => '',
                    };

                    $run = $body->getActiveParagraph()->createTextRun($prefix . $text);
                    $fontSize = match ($block['type']) {
                        'h1'  => 16,
                        'h2'  => 14,
                        'h3'  => 12,
                        default => 11,
                    };
                    $isBold = in_array($block['type'], ['h1', 'h2', 'h3'], true);
                    $run->getFont()->setSize($fontSize)->setBold($isBold)
                        ->setColor(new \PhpOffice\PhpPresentation\Style\Color('FF222222'));
                    $isFirst = false;
                }
            }

            if ($presentation->getSlideCount() === 0) {
                $slide = $presentation->createSlide();
                $shape = $slide->createRichTextShape();
                $shape->setWidth(680)->setHeight(200)->setOffsetX(30)->setOffsetY(100);
                $shape->getActiveParagraph()->createTextRun('(No extractable text found)');
            }

            $writer = PresentationIOFactory::createWriter($presentation, 'PowerPoint2007');
            $writer->save($outputPath);

            $name = pathinfo($request->file('file')->getClientOriginalName(), PATHINFO_FILENAME);
            return response()->download($outputPath, $name . '.pptx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            ])->deleteFileAfterSend(false);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        } finally {
            $this->scheduleTmpCleanup($tmpDir);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  PowerPoint → PDF
    // ─────────────────────────────────────────────────────────────────────────
    public function pptToPdf(Request $request)
    {
        $request->validate(['file' => 'required|file|max:' . (self::MAX_MB * 1024)]);
        $file = $request->file('file');
        $ext  = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['pptx', 'ppt', 'odp'], true)) {
            return response()->json(['error' => 'Unsupported format. Use .pptx, .ppt or .odp.'], 422);
        }

        $tmpDir = $this->makeTmpDir();
        try {
            $inputPath  = $tmpDir . '/input.' . $ext;
            $outputPath = $tmpDir . '/output.pdf';
            $file->move($tmpDir, 'input.' . $ext);

            $presentation = PresentationIOFactory::load($inputPath);
            $mpdf = new Mpdf([
                'format'        => 'A4-L',
                'margin_top'    => 15,
                'margin_bottom' => 15,
                'margin_left'   => 15,
                'margin_right'  => 15,
                'default_font'  => 'dejavusans',
            ]);

            for ($i = 0; $i < $presentation->getSlideCount(); $i++) {
                $slide = $presentation->getSlide($i);
                $html  = '<div style="font-family:Arial,sans-serif;padding:20px;">';
                $html .= '<p style="color:#999;font-size:9pt;margin:0 0 8pt;border-bottom:1px solid #eee;padding-bottom:4pt">Slide ' . ($i + 1) . '</p>';

                foreach ($slide->getShapeCollection() as $shape) {
                    if (!($shape instanceof RichTextShape)) continue;
                    foreach ($shape->getParagraphs() as $para) {
                        $line = '';
                        foreach ($para->getRichTextElements() as $run) {
                            if (method_exists($run, 'getText')) $line .= htmlspecialchars($run->getText());
                        }
                        if (trim($line) === '') continue;
                        $elements = $para->getRichTextElements();
                        $font     = !empty($elements) ? $elements[0]->getFont() : null;
                        $size     = $font ? ($font->getSize() ?? 14) : 14;
                        $bold     = $font && $font->isBold() ? 'font-weight:bold;' : '';
                        $color    = 'color:#111;';
                        $html    .= "<p style=\"font-size:{$size}pt;{$bold}{$color}margin:4pt 0\">{$line}</p>";
                    }
                }
                $html .= '</div>';
                if ($i > 0) $mpdf->AddPage();
                $mpdf->WriteHTML($html);
            }

            $mpdf->Output($outputPath, 'F');
            $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            return response()->download($outputPath, $name . '.pdf', ['Content-Type' => 'application/pdf'])
                             ->deleteFileAfterSend(false);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        } finally {
            $this->scheduleTmpCleanup($tmpDir);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  HTML / Webpage → PDF
    // ─────────────────────────────────────────────────────────────────────────
    public function htmlToPdf(Request $request)
    {
        $request->validate(['url' => 'required|url|max:2048']);
        $url  = $request->input('url');

        // SSRF guard: block private/reserved IP ranges
        $host = parse_url($url, PHP_URL_HOST);
        if ($host) {
            $ip = gethostbyname($host);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                return response()->json(['error' => 'Internal or private URLs are not allowed.'], 422);
            }
        }

        $tmpDir = $this->makeTmpDir();
        try {
            $outputPath = $tmpDir . '/output.pdf';

            $client   = new \GuzzleHttp\Client(['timeout' => 20, 'allow_redirects' => ['max' => 5]]);
            $response = $client->get($url, ['headers' => ['User-Agent' => 'ToolPDF/1.0 (+https://toolpdf.org)']]);
            $html     = (string) $response->getBody();

            $mpdf = new Mpdf([
                'format'        => 'A4',
                'margin_top'    => 12,
                'margin_bottom' => 12,
                'margin_left'   => 10,
                'margin_right'  => 10,
                'default_font'  => 'dejavusans',
            ]);
            $mpdf->simpleTables = true;
            $mpdf->setBasePath($url);
            $mpdf->WriteHTML($html);
            $mpdf->Output($outputPath, 'F');

            $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($host ?? 'page'));
            return response()->download($outputPath, $slug . '.pdf', ['Content-Type' => 'application/pdf'])
                             ->deleteFileAfterSend(false);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Conversion failed: ' . $e->getMessage()], 500);
        } finally {
            $this->scheduleTmpCleanup($tmpDir);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Text analysis helpers (used by PDF→Word and PDF→PPT)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Analyse raw PDF text into typed blocks: h1/h2/h3, bullet, numbered, table, paragraph.
     * Inspired by pdfcraft's structured document analysis approach.
     */
    private function analyzeTextBlocks(string $rawText): array
    {
        $lines  = explode("\n", $rawText);
        $blocks = [];
        $buffer = [];

        $flush = function () use (&$buffer, &$blocks) {
            if (empty($buffer)) return;
            $text = trim(preg_replace('/\s+/', ' ', implode(' ', array_map('trim', $buffer))));
            if ($text !== '') {
                $blocks[] = ['type' => 'paragraph', 'text' => $text];
            }
            $buffer = [];
        };

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                $flush();
                continue;
            }

            // Bullet points: •, ·, ▪, ▸, -, * followed by a space
            if (preg_match('/^[•·▪▸\-\*]\s+(.+)$/u', $trimmed, $m)) {
                $flush();
                $blocks[] = ['type' => 'bullet', 'text' => trim($m[1])];
                continue;
            }

            // Numbered lists: "1.", "1)", "(a)", "a.", "a)"
            if (preg_match('/^(\d+[.)\]]|[a-z][.)]|\([a-z]\))\s+(.+)$/u', $trimmed, $m)) {
                $flush();
                $blocks[] = ['type' => 'numbered', 'text' => trim($m[2])];
                continue;
            }

            // Table row heuristic: 2+ columns separated by 2+ spaces/tabs
            $cols = preg_split('/\t|\s{2,}/', $trimmed);
            if (count($cols) >= 2) {
                $flush();
                $blocks[] = ['type' => '_table_row', 'cols' => $cols];
                continue;
            }

            // Heading heuristic: buffer is empty, line is short, no sentence-ending punctuation
            $len       = mb_strlen($trimmed);
            $noEndPunct = !preg_match('/[.,:;!?]$/', $trimmed);
            $isAllCaps  = $trimmed === mb_strtoupper($trimmed, 'UTF-8') && preg_match('/\p{Lu}/u', $trimmed);

            if (empty($buffer) && $noEndPunct && $len <= 90) {
                $flush();
                if ($isAllCaps && $len <= 60) {
                    $blocks[] = ['type' => 'h1', 'text' => $trimmed];
                } elseif ($len <= 50) {
                    $blocks[] = ['type' => 'h2', 'text' => $trimmed];
                } elseif ($len <= 75) {
                    $blocks[] = ['type' => 'h3', 'text' => $trimmed];
                } else {
                    $buffer[] = $trimmed;
                }
                continue;
            }

            $buffer[] = $trimmed;
        }

        $flush();

        return $this->mergeTableRows($blocks);
    }

    /**
     * Merge consecutive _table_row blocks into a single table block.
     * Isolated single rows fall back to paragraph.
     */
    private function mergeTableRows(array $blocks): array
    {
        $result   = [];
        $rowBatch = [];

        $flushRows = function () use (&$rowBatch, &$result) {
            if (empty($rowBatch)) return;
            if (count($rowBatch) >= 2) {
                $result[] = ['type' => 'table', 'rows' => array_column($rowBatch, 'cols')];
            } else {
                foreach ($rowBatch as $r) {
                    $result[] = ['type' => 'paragraph', 'text' => implode('  ', $r['cols'])];
                }
            }
            $rowBatch = [];
        };

        foreach ($blocks as $block) {
            if ($block['type'] === '_table_row') {
                $rowBatch[] = $block;
            } else {
                $flushRows();
                $result[] = $block;
            }
        }
        $flushRows();

        return $result;
    }

    /**
     * Render typed blocks into a PhpWord Section.
     */
    private function renderBlocksToSection(Section $section, array $blocks, \PhpOffice\PhpWord\PhpWord $phpWord): void
    {
        static $tableStyleRegistered = false;
        if (!$tableStyleRegistered) {
            $phpWord->addTableStyle('ToolPDFTable', [
                'borderSize'  => 4,
                'borderColor' => 'cccccc',
                'cellMargin'  => 80,
            ], [
                'bgColor' => 'f3f4f6',
                'bold'    => true,
            ]);
            $tableStyleRegistered = true;
        }

        foreach ($blocks as $block) {
            switch ($block['type']) {
                case 'h1':
                    $section->addText(
                        $block['text'],
                        ['bold' => true, 'size' => 18, 'name' => 'Calibri', 'color' => '1a1a2e'],
                        ['spaceAfter' => 200, 'spaceBefore' => 160]
                    );
                    break;

                case 'h2':
                    $section->addText(
                        $block['text'],
                        ['bold' => true, 'size' => 14, 'name' => 'Calibri', 'color' => '1e3a5f'],
                        ['spaceAfter' => 140, 'spaceBefore' => 100]
                    );
                    break;

                case 'h3':
                    $section->addText(
                        $block['text'],
                        ['bold' => true, 'size' => 12, 'name' => 'Calibri', 'color' => '2d5a87'],
                        ['spaceAfter' => 100, 'spaceBefore' => 60]
                    );
                    break;

                case 'bullet':
                    $section->addText(
                        '• ' . $block['text'],
                        ['size' => 11, 'name' => 'Calibri'],
                        ['spaceAfter' => 60, 'indent' => 360]
                    );
                    break;

                case 'numbered':
                    $section->addText(
                        $block['text'],
                        ['size' => 11, 'name' => 'Calibri'],
                        ['spaceAfter' => 60, 'indent' => 360]
                    );
                    break;

                case 'table':
                    $this->addWordTable($section, $block['rows']);
                    break;

                default: // paragraph
                    $section->addText(
                        $block['text'],
                        ['size' => 11, 'name' => 'Calibri'],
                        ['spaceAfter' => 120, 'lineHeight' => 1.5]
                    );
                    break;
            }
        }
    }

    /**
     * Add a simple bordered table to a Word section.
     * Splits available page width evenly across columns.
     */
    private function addWordTable(Section $section, array $rows): void
    {
        if (empty($rows)) return;

        $maxCols   = max(array_map('count', $rows));
        // A4 page with 2.54cm margins each side ≈ 9070 twips of usable width
        $cellWidth = (int) floor(9070 / max($maxCols, 1));

        $table = $section->addTable('ToolPDFTable');

        foreach ($rows as $ri => $cols) {
            $table->addRow();
            while (count($cols) < $maxCols) $cols[] = '';
            foreach ($cols as $ci => $cell) {
                $cellEl    = $table->addCell($cellWidth, $ri === 0 ? ['bgColor' => 'f3f4f6'] : []);
                $fontStyle = $ri === 0
                    ? ['bold' => true, 'size' => 10, 'name' => 'Calibri']
                    : ['size' => 10, 'name' => 'Calibri'];
                $cellEl->addText(trim((string) $cell), $fontStyle);
            }
        }

        $section->addTextBreak(1);
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function makeTmpDir(): string
    {
        $dir = sys_get_temp_dir() . '/toolpdf_' . bin2hex(random_bytes(8));
        mkdir($dir, 0755, true);
        return $dir;
    }

    private function scheduleTmpCleanup(string $dir): void
    {
        register_shutdown_function(function () use ($dir) {
            if (!is_dir($dir)) return;
            foreach (new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            ) as $item) {
                $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
            }
            rmdir($dir);
        });
    }
}
