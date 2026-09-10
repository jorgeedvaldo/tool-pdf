<?php

namespace Tests\Unit;

use App\Http\Controllers\ConvertController;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * The Word → PDF endpoint used to hand every upload to PhpWord's Word2007
 * reader, which silently produced an empty document for .odt and an opaque ZIP
 * error for .doc/.rtf. The reader is now chosen from the file's own bytes.
 */
class WordReaderDetectionTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir() . '/reader_test_' . bin2hex(random_bytes(6));
        mkdir($this->dir, 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/*') ?: [] as $file) {
            unlink($file);
        }
        @rmdir($this->dir);
        parent::tearDown();
    }

    private function detect(string $path, string $ext): string
    {
        $method = new ReflectionMethod(ConvertController::class, 'detectWordReader');
        $method->setAccessible(true);

        return $method->invoke(new ConvertController(), $path, $ext);
    }

    private function writeZip(string $name, string $entry): string
    {
        $path = $this->dir . '/' . $name;
        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString($entry, '<xml/>');
        $zip->close();

        return $path;
    }

    private function writeRaw(string $name, string $bytes): string
    {
        $path = $this->dir . '/' . $name;
        file_put_contents($path, $bytes);

        return $path;
    }

    public function test_ooxml_zip_selects_the_word2007_reader(): void
    {
        $this->assertSame('Word2007', $this->detect($this->writeZip('a.docx', 'word/document.xml'), 'docx'));
    }

    public function test_opendocument_zip_selects_the_odtext_reader(): void
    {
        $this->assertSame('ODText', $this->detect($this->writeZip('a.odt', 'content.xml'), 'odt'));
    }

    public function test_ole2_header_selects_the_msdoc_reader(): void
    {
        $ole = "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1" . str_repeat("\0", 64);
        $this->assertSame('MsDoc', $this->detect($this->writeRaw('a.doc', $ole), 'doc'));
    }

    public function test_rtf_header_selects_the_rtf_reader(): void
    {
        $this->assertSame('RTF', $this->detect($this->writeRaw('a.rtf', '{\rtf1\ansi Hello}'), 'rtf'));
    }

    /** A .docx renamed to .doc must still be read as OOXML, and vice versa. */
    public function test_content_wins_over_a_misleading_extension(): void
    {
        $this->assertSame('Word2007', $this->detect($this->writeZip('renamed.doc', 'word/document.xml'), 'doc'));
        $this->assertSame('RTF', $this->detect($this->writeRaw('renamed.docx', '{\rtf1\ansi Hi}'), 'docx'));
    }

    /** Unrecognisable bytes fall back to the extension so the reader can report. */
    public function test_unknown_content_falls_back_to_the_extension(): void
    {
        $junk = $this->writeRaw('junk.odt', 'not a document at all');
        $this->assertSame('ODText', $this->detect($junk, 'odt'));
        $this->assertSame('Word2007', $this->detect($junk, 'docx'));
    }
}
