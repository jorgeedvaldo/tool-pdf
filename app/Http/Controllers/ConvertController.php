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
    //  Uses phpoffice/phpword to load the .docx, then renders via mPDF.
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

            $phpWord = WordIOFactory::load($inputPath);
            $htmlPath = $tmpDir . '/document.html';

            // PhpWord's PDF writer passes the complete generated HTML to mPDF in
            // one WriteHTML() call. Large documents can exceed PHP's
            // pcre.backtrack_limit while mPDF parses that string, so generate the
            // intermediate HTML ourselves and feed it to mPDF incrementally.
            WordIOFactory::createWriter($phpWord, 'HTML')->save($htmlPath);

            $mpdf = new Mpdf([
                'tempDir'      => $tmpDir,
                'default_font' => 'dejavusans',
            ]);
            $mpdf->SetBasePath($tmpDir . '/');
            $this->writeHtmlInChunks($mpdf, file_get_contents($htmlPath));
            $mpdf->Output($outputPath, 'F');

            $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

            return response()->download($outputPath, $name . '.pdf', [
                'Content-Type' => 'application/pdf',
            ])->deleteFileAfterSend(false);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        } finally {
            $this->scheduleTmpCleanup($tmpDir);
        }
    }

    /**
     * Send generated Word HTML to mPDF without presenting PCRE with one very
     * large subject. DOM nodes are preferred as natural chunk boundaries; an
     * unusually large node is split only between HTML tokens.
     */
    private function writeHtmlInChunks(Mpdf $mpdf, string $html, int $maxChunkBytes = 250000): void
    {
        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $styles = '';
        foreach ($document->getElementsByTagName('style') as $style) {
            $styles .= $style->textContent . "\n";
        }
        if ($styles !== '') {
            $mpdf->WriteHTML($styles, HTMLParserMode::HEADER_CSS);
        }

        $body = $document->getElementsByTagName('body')->item(0);
        $nodes = $body ? iterator_to_array($body->childNodes) : iterator_to_array($document->childNodes);
        $buffer = '';

        foreach ($nodes as $node) {
            if ($node instanceof \DOMElement && strtolower($node->tagName) === 'style') {
                continue;
            }

            $fragment = $document->saveHTML($node);
            if ($fragment === false || $fragment === '') {
                continue;
            }

            if (strlen($buffer) + strlen($fragment) <= $maxChunkBytes) {
                $buffer .= $fragment;
                continue;
            }

            if ($buffer !== '') {
                $mpdf->WriteHTML($buffer, HTMLParserMode::HTML_BODY);
                $buffer = '';
            }

            if (strlen($fragment) <= $maxChunkBytes) {
                $buffer = $fragment;
                continue;
            }

            foreach ($this->splitHtmlFragment($fragment, $maxChunkBytes) as $chunk) {
                $mpdf->WriteHTML($chunk, HTMLParserMode::HTML_BODY);
            }
        }

        if ($buffer !== '') {
            $mpdf->WriteHTML($buffer, HTMLParserMode::HTML_BODY);
        }
    }

    /** @return array<int, string> */
    private function splitHtmlFragment(string $html, int $maxChunkBytes): array
    {
        $tokens = preg_split('/(<[^>]+>)/s', $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $chunks = [];
        $buffer = '';

        foreach ($tokens ?: [$html] as $token) {
            while (strlen($token) > $maxChunkBytes) {
                if ($buffer !== '') {
                    $chunks[] = $buffer;
                    $buffer = '';
                }
                $chunks[] = substr($token, 0, $maxChunkBytes);
                $token = substr($token, $maxChunkBytes);
            }

            if (strlen($buffer) + strlen($token) > $maxChunkBytes) {
                $chunks[] = $buffer;
                $buffer = '';
            }
            $buffer .= $token;
        }

        if ($buffer !== '') {
            $chunks[] = $buffer;
        }

        return $chunks;
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
