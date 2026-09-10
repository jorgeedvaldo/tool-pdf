<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpWord\PhpWord;
use Smalot\PdfParser\Parser as PdfParser;
use Tests\TestCase;

/**
 * End-to-end coverage for /convert/word-to-pdf.
 *
 * The regression these guard against: PhpWord's named-page CSS made mPDF start a
 * fresh page for every block, so a two-page document came out with tens of
 * thousands of blank pages, and .odt/.rtf/.doc uploads were read with the wrong
 * reader (empty output or an opaque ZIP error).
 */
class WordToPdfConversionTest extends TestCase
{
    private string $workDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workDir = sys_get_temp_dir() . '/wordpdf_test_' . bin2hex(random_bytes(6));
        mkdir($this->workDir, 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->workDir . '/*') ?: [] as $file) {
            unlink($file);
        }
        @rmdir($this->workDir);
        parent::tearDown();
    }

    /** Builds a small document: headings, styled runs, a table and a page break. */
    private function makeDocument(): PhpWord
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        $section->addTitle('Relatório de Teste', 1);
        $section->addText('Parágrafo com acentuação: ção, ã, é, ü.');
        $section->addText('Negrito', ['bold' => true]);

        $table = $section->addTable(['borderSize' => 6]);
        for ($row = 0; $row < 3; $row++) {
            $table->addRow();
            for ($col = 0; $col < 3; $col++) {
                $table->addCell(3000)->addText("R{$row} C{$col}");
            }
        }

        $section->addPageBreak();
        for ($i = 0; $i < 20; $i++) {
            $section->addText("Linha {$i}. " . str_repeat('lorem ipsum dolor sit amet ', 8));
        }

        return $phpWord;
    }

    private function writeFixture(string $writer, string $extension): string
    {
        $path = $this->workDir . '/fixture.' . $extension;
        WordIOFactory::createWriter($this->makeDocument(), $writer)->save($path);

        return $path;
    }

    private function convert(string $path, string $extension): string
    {
        $upload = new UploadedFile($path, 'fixture.' . $extension, null, null, true);

        $response = $this->post('/convert/word-to-pdf', ['file' => $upload]);
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');

        // BinaryFileResponse: read the file the controller handed to the client.
        $pdf = file_get_contents($response->baseResponse->getFile()->getPathname());
        $this->assertStringStartsWith('%PDF-', $pdf);

        return $pdf;
    }

    private function pageCount(string $pdf): int
    {
        return preg_match_all('#/Type\s*/Page[^s]#', $pdf);
    }

    public function test_docx_converts_to_a_pdf_with_a_realistic_page_count(): void
    {
        $pdf = $this->convert($this->writeFixture('Word2007', 'docx'), 'docx');

        $pages = $this->pageCount($pdf);
        $this->assertGreaterThan(0, $pages);
        $this->assertLessThan(
            20,
            $pages,
            "A short document must not explode into {$pages} pages."
        );
    }

    public function test_docx_conversion_preserves_text_tables_and_accents(): void
    {
        $path = $this->workDir . '/out.pdf';
        file_put_contents($path, $this->convert($this->writeFixture('Word2007', 'docx'), 'docx'));

        $text = (new PdfParser())->parseFile($path)->getText();

        $this->assertStringContainsString('Relatório de Teste', $text);
        $this->assertStringContainsString('acentuação', $text);
        $this->assertStringContainsString('R2 C2', $text); // last table cell
        $this->assertStringContainsString('Linha 19.', $text); // content after the page break
    }

    public function test_odt_is_read_with_the_odt_reader_and_is_not_empty(): void
    {
        $path = $this->workDir . '/out-odt.pdf';
        file_put_contents($path, $this->convert($this->writeFixture('ODText', 'odt'), 'odt'));

        $text = (new PdfParser())->parseFile($path)->getText();
        $this->assertStringContainsString('Relatório de Teste', $text);
        $this->assertLessThan(20, $this->pageCount(file_get_contents($path)));
    }

    public function test_rtf_is_read_with_the_rtf_reader(): void
    {
        $path = $this->workDir . '/out-rtf.pdf';
        file_put_contents($path, $this->convert($this->writeFixture('RTF', 'rtf'), 'rtf'));

        // Asserted on ASCII body text only: PhpWord's RTF reader emits no heading
        // styles and drops accented characters. The point here is that the file is
        // parsed at all, instead of failing with a ZIP error as it did when every
        // upload was handed to the Word2007 reader.
        $text = (new PdfParser())->parseFile($path)->getText();
        $this->assertStringContainsString('Linha 19.', $text);
        $this->assertStringContainsString('R2 C2', $text);
    }

    public function test_legacy_doc_is_read_with_the_msdoc_reader(): void
    {
        $sample = base_path('vendor/phpoffice/phpword/samples/resources/Sample_11_ReadWord97.doc');
        if (!is_file($sample)) {
            $this->markTestSkipped('PhpWord sample .doc not installed.');
        }

        // The endpoint moves the upload, so work on a copy.
        $path = $this->workDir . '/legacy.doc';
        copy($sample, $path);

        $pdf = $this->convert($path, 'doc');
        $this->assertGreaterThan(0, $this->pageCount($pdf));
    }

    public function test_per_section_orientation_is_applied(): void
    {
        $phpWord = new PhpWord();
        $phpWord->addSection(['orientation' => 'landscape'])->addText('Landscape');
        $phpWord->addSection()->addText('Portrait');

        $path = $this->workDir . '/sections.docx';
        WordIOFactory::createWriter($phpWord, 'Word2007')->save($path);

        $pdf = $this->convert($path, 'docx');

        $this->assertSame(2, $this->pageCount($pdf));
        // A4 in points: 595.28 x 841.89. Both orientations must be present.
        $this->assertMatchesRegularExpression('#/MediaBox\s*\[0 0 841\.\d+ 595\.\d+\]#', $pdf);
        $this->assertMatchesRegularExpression('#/MediaBox\s*\[0 0 595\.\d+ 841\.\d+\]#', $pdf);
    }

    public function test_unreadable_file_returns_a_helpful_json_error(): void
    {
        $path = $this->workDir . '/broken.docx';
        file_put_contents($path, 'this is definitely not a word document');

        $upload = new UploadedFile($path, 'broken.docx', null, null, true);

        $this->post('/convert/word-to-pdf', ['file' => $upload])
             ->assertStatus(500)
             ->assertJsonStructure(['error']);
    }
}
