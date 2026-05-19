<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpWord\Settings as WordSettings;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpPresentation\IOFactory as PresentationIOFactory;
use PhpOffice\PhpPresentation\PhpPresentation;
use PhpOffice\PhpPresentation\Shape\RichText as RichTextShape;
use Smalot\PdfParser\Parser as PdfParser;
use Mpdf\Mpdf;

class ConvertController extends Controller
{
    private const MAX_MB = 50;

    // ─────────────────────────────────────────────────────────────────────────
    //  PDF → Word (.docx)
    //  Uses smalot/pdfparser to extract text + phpoffice/phpword to write docx.
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

            // Parse PDF
            $parser  = new PdfParser();
            $pdf     = $parser->parseFile($inputPath);
            $pages   = $pdf->getPages();

            // Build Word document
            $phpWord = new \PhpOffice\PhpWord\PhpWord();
            $phpWord->getDefaultFontName('Times New Roman');
            $phpWord->setDefaultFontSize(12);

            $sectionStyle = [
                'marginTop'    => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(2.5),
                'marginBottom' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(2.5),
                'marginLeft'   => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(2.5),
                'marginRight'  => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(2.5),
            ];

            foreach ($pages as $i => $page) {
                $section = $phpWord->addSection($sectionStyle);

                $rawText = $page->getText();
                if (!$rawText) continue;

                // Split into paragraphs on blank lines or line breaks
                $paragraphs = preg_split('/\n{2,}/', trim($rawText));

                foreach ($paragraphs as $para) {
                    $para = trim(preg_replace('/[ \t]+/', ' ', $para));
                    if ($para === '') {
                        $section->addTextBreak();
                        continue;
                    }
                    // Detect heading heuristic: short line (<80 chars), all caps or ends without punctuation
                    $isHeading = (mb_strlen($para) < 80 &&
                        (mb_strtoupper($para) === $para || !preg_match('/[.,:;!?]$/', $para)));

                    if ($isHeading && mb_strlen($para) < 60) {
                        $section->addText($para, ['bold' => true, 'size' => 14]);
                    } else {
                        // Split into lines and add with spacing
                        $lines = explode("\n", $para);
                        $text  = implode(' ', array_map('trim', $lines));
                        $section->addText($text, ['size' => 12], ['spaceAfter' => 120]);
                    }
                }

                // Page break between pages (except last)
                if ($i < count($pages) - 1) {
                    $section->addPageBreak();
                }
            }

            if (count($phpWord->getSections()) === 0) {
                $phpWord->addSection($sectionStyle)->addText('(Documento sem texto extraível)');
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
            return response()->json(['error' => 'Formato não suportado. Use .docx, .doc, .odt ou .rtf.'], 422);
        }

        $tmpDir = $this->makeTmpDir();

        try {
            $inputName = 'input.' . $ext;
            $inputPath = $tmpDir . '/' . $inputName;
            $outputPath = $tmpDir . '/output.pdf';
            $file->move($tmpDir, $inputName);

            // Configure PhpWord to use mPDF as PDF renderer
            WordSettings::setPdfRendererName(WordSettings::PDF_RENDERER_MPDF);
            WordSettings::setPdfRendererPath(base_path('vendor/mpdf/mpdf'));

            $phpWord = WordIOFactory::load($inputPath);
            $writer  = WordIOFactory::createWriter($phpWord, 'PDF');
            $writer->save($outputPath);

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

            // Render each sheet as HTML, then convert via mPDF
            $writer = SpreadsheetIOFactory::createWriter($spreadsheet, 'Html');
            $writer->save($htmlPath);

            $mpdf = new Mpdf(['format' => 'A4-L', 'margin_top' => 10, 'margin_bottom' => 10,
                              'margin_left' => 8, 'margin_right' => 8]);
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
                    ? $spreadsheet->getActiveSheet()->setTitle('Page 1')
                    : $spreadsheet->createSheet()->setTitle('Page ' . ($i + 1));

                $lines = array_filter(array_map('trim', explode("\n", $page->getText())));
                $row   = 1;
                foreach ($lines as $line) {
                    // Split on 2+ spaces as a simple column separator heuristic
                    $cells = preg_split('/\s{2,}/', $line);
                    foreach ($cells as $col => $cell) {
                        $sheet->setCellValue([$col + 1, $row], trim($cell));
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
            $presentation->removeSlide(0); // remove default blank

            foreach ($pages as $i => $page) {
                $slide = $presentation->createSlide();
                $text  = trim($page->getText());
                if (!$text) continue;

                // Page label
                $label = $slide->createRichTextShape();
                $label->setWidth(680)->setHeight(36)->setOffsetX(30)->setOffsetY(16);
                $run = $label->getActiveParagraph()->createTextRun('Page ' . ($i + 1));
                $run->getFont()->setBold(true)->setSize(16)->setColor(
                    new \PhpOffice\PhpPresentation\Style\Color('FF333333')
                );

                // Body text
                $body = $slide->createRichTextShape();
                $body->setWidth(680)->setHeight(450)->setOffsetX(30)->setOffsetY(60);

                $isFirst = true;
                foreach (preg_split('/\n{2,}/', $text) as $para) {
                    $para = trim(preg_replace('/[ \t]+/', ' ', $para));
                    if (!$para) continue;
                    if (!$isFirst) $body->createParagraph();
                    $run = $body->getActiveParagraph()->createTextRun($para);
                    $run->getFont()->setSize(12)->setColor(
                        new \PhpOffice\PhpPresentation\Style\Color('FF111111')
                    );
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
            return response()->json(['error' => 'Unsupported format. Use .pptx or .odp.'], 422);
        }

        $tmpDir = $this->makeTmpDir();
        try {
            $inputPath  = $tmpDir . '/input.' . $ext;
            $outputPath = $tmpDir . '/output.pdf';
            $file->move($tmpDir, 'input.' . $ext);

            $presentation = PresentationIOFactory::load($inputPath);
            $mpdf = new Mpdf(['format' => 'A4-L', 'margin_top' => 15, 'margin_bottom' => 15,
                              'margin_left' => 15, 'margin_right' => 15]);

            for ($i = 0; $i < $presentation->getSlideCount(); $i++) {
                $slide = $presentation->getSlide($i);
                $html  = '<div style="font-family:Arial,sans-serif;padding:20px;">';
                $html .= '<p style="color:#999;font-size:10pt;margin:0 0 10pt">Slide ' . ($i + 1) . '</p>';

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
                        $html    .= "<p style=\"font-size:{$size}pt;{$bold}margin:4pt 0\">{$line}</p>";
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

        // Basic SSRF guard: block private/reserved IP ranges
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

            $mpdf = new Mpdf(['format' => 'A4', 'margin_top' => 12, 'margin_bottom' => 12,
                              'margin_left' => 10, 'margin_right' => 10]);
            $mpdf->simpleTables = true;
            $mpdf->setBasePath($url); // resolve relative image/CSS URLs
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
