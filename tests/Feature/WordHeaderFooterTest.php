<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpWord\PhpWord;
use Smalot\PdfParser\Parser as PdfParser;
use Tests\TestCase;

/**
 * PhpWord's HTML writer emits no headers or footers at all, so they used to
 * vanish from every converted PDF. They are now read off the Word sections and
 * attached to mPDF directly.
 */
class WordHeaderFooterTest extends TestCase
{
    private string $workDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workDir = sys_get_temp_dir() . '/wordhf_test_' . bin2hex(random_bytes(6));
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

    private function convert(PhpWord $phpWord, string $name): string
    {
        $source = $this->workDir . '/' . $name . '.docx';
        WordIOFactory::createWriter($phpWord, 'Word2007')->save($source);

        $response = $this->post('/convert/word-to-pdf', [
            'file' => new UploadedFile($source, $name . '.docx', null, null, true),
        ]);
        $response->assertStatus(200);

        $pdf = $this->workDir . '/' . $name . '.pdf';
        copy($response->baseResponse->getFile()->getPathname(), $pdf);

        return $pdf;
    }

    /** @return array<int, string> Text of each page, in order. */
    private function pageTexts(string $pdf): array
    {
        $pages = [];
        foreach ((new PdfParser())->parseFile($pdf)->getPages() as $page) {
            // The embedded font substitutes typographic ligatures, so "confidencial"
            // comes back as "con<ﬁ>dencial". Undo them before matching.
            $pages[] = str_replace(
                ['ﬁ', 'ﬂ', 'ﬀ', 'ﬃ', 'ﬄ'],
                ['fi', 'fl', 'ff', 'ffi', 'ffl'],
                $page->getText()
            );
        }

        return $pages;
    }

    private function longSection(PhpWord $phpWord, array $style = [])
    {
        $section = $phpWord->addSection($style);
        for ($i = 0; $i < 60; $i++) {
            $section->addText("Corpo linha {$i}. " . str_repeat('texto ', 12));
        }

        return $section;
    }

    public function test_header_and_footer_appear_on_every_page(): void
    {
        $phpWord = new PhpWord();
        $section = $this->longSection($phpWord);
        $section->addHeader()->addText('CABEÇALHO Empresa XPTO');
        $section->addFooter()->addText('RODAPE confidencial');

        $pages = $this->pageTexts($this->convert($phpWord, 'simple'));

        $this->assertGreaterThan(1, count($pages), 'Fixture should span several pages.');
        foreach ($pages as $number => $text) {
            $this->assertStringContainsString('CABEÇALHO Empresa XPTO', $text, "Missing header on page {$number}.");
            $this->assertStringContainsString('RODAPE confidencial', $text, "Missing footer on page {$number}.");
        }
    }

    /** Word page-number fields must become live per-page numbers, not literals. */
    public function test_page_number_fields_are_resolved_per_page(): void
    {
        $phpWord = new PhpWord();
        $section = $this->longSection($phpWord);
        $section->addFooter()->addPreserveText('Pagina {PAGE} de {NUMPAGES}', null, ['alignment' => 'center']);

        $pages = $this->pageTexts($this->convert($phpWord, 'fields'));
        $total = count($pages);

        foreach ($pages as $index => $text) {
            $this->assertStringContainsString('Pagina ' . ($index + 1) . ' de ' . $total, $text);
        }
        $this->assertStringNotContainsString('{PAGE}', implode('', $pages));
    }

    /** Fields written inside a text run come back as one PreserveText element. */
    public function test_fields_inside_a_header_text_run_are_resolved(): void
    {
        $phpWord = new PhpWord();
        $section = $this->longSection($phpWord);
        $run = $section->addHeader()->addTextRun(['alignment' => 'right']);
        $run->addText('Relatorio pag. ');
        $run->addField('PAGE');
        $run->addText(' / ');
        $run->addField('NUMPAGES');

        $pages = $this->pageTexts($this->convert($phpWord, 'runfields'));
        $total = count($pages);

        foreach ($pages as $index => $text) {
            $this->assertStringContainsString('Relatorio pag. ' . ($index + 1) . ' / ' . $total, $text);
        }
    }

    public function test_each_section_keeps_its_own_header_and_footer(): void
    {
        $phpWord = new PhpWord();

        $first = $this->longSection($phpWord);
        $first->addHeader()->addText('HEADER SEC1');
        $first->addFooter()->addText('RODAPE SEC1');

        $second = $this->longSection($phpWord);
        $second->addHeader()->addText('HEADER SEC2');
        $second->addFooter()->addText('RODAPE SEC2');

        // A third section without either must not inherit the second's.
        $phpWord->addSection()->addText('Seccao final sem cabecalho');

        $pages = $this->pageTexts($this->convert($phpWord, 'sections'));
        $joined = implode("\n", $pages);

        $this->assertStringContainsString('HEADER SEC1', $joined);
        $this->assertStringContainsString('HEADER SEC2', $joined);

        // No page may carry two sections' chrome, and the last page carries none.
        foreach ($pages as $number => $text) {
            $this->assertFalse(
                str_contains($text, 'HEADER SEC1') && str_contains($text, 'HEADER SEC2'),
                "Page {$number} mixes both sections' headers."
            );
        }

        $last = end($pages);
        $this->assertStringContainsString('Seccao final sem cabecalho', $last);
        $this->assertStringNotContainsString('HEADER SEC', $last);
        $this->assertStringNotContainsString('RODAPE SEC', $last);
    }

    /** A header must not push the page count off a cliff (the old failure mode). */
    public function test_headers_do_not_inflate_the_page_count(): void
    {
        $phpWord = new PhpWord();
        $section = $this->longSection($phpWord);
        $section->addHeader()->addText('CABEÇALHO');
        $section->addFooter()->addPreserveText('{PAGE}/{NUMPAGES}');

        $pages = $this->pageTexts($this->convert($phpWord, 'sane'));

        $this->assertGreaterThan(0, count($pages));
        $this->assertLessThan(20, count($pages));
    }
}
