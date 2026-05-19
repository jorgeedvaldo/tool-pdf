<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpWord\Settings as WordSettings;
use Smalot\PdfParser\Parser as PdfParser;

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
