<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PdfWordConverterTest extends TestCase
{
    use RefreshDatabase;

    public function test_pdf_to_word_route_returns_200()
    {
        $response = $this->get('/en/tool/pdf-to-word');
        $response->assertStatus(200);
    }

    public function test_word_to_pdf_route_returns_200()
    {
        $response = $this->get('/en/tool/word-to-pdf');
        $response->assertStatus(200);
    }

    public function test_pdf_to_word_view_contains_expected_elements()
    {
        $response = $this->get('/en/tool/pdf-to-word');

        $response->assertSee('pdf-to-word', false);
        $response->assertSee('cw-drop', false);
        $response->assertSee('cw-convert-btn', false);
    }

    public function test_word_to_pdf_view_contains_expected_elements()
    {
        $response = $this->get('/en/tool/word-to-pdf');

        $response->assertSee('word-to-pdf', false);
        $response->assertSee('cw-drop', false);
        $response->assertSee('cw-convert-btn', false);
    }

    public function test_pdf_to_word_loads_required_js_libraries()
    {
        $response = $this->get('/en/tool/pdf-to-word');

        // pdf.js for text extraction
        $response->assertSee('pdf.min.js', false);
        // docx.js UMD for Word document generation
        $response->assertSee('docx@6.5.0', false);
        // The converter script itself
        $response->assertSee('convert-pdf-word.js', false);
    }

    public function test_word_to_pdf_loads_required_js_libraries()
    {
        $response = $this->get('/en/tool/word-to-pdf');

        // mammoth for .docx parsing
        $response->assertSee('mammoth', false);
        // html2pdf for rendering to PDF
        $response->assertSee('html2pdf', false);
        // The converter script itself
        $response->assertSee('convert-pdf-word.js', false);
    }

    public function test_pdf_to_word_initial_mode_is_set()
    {
        $response = $this->get('/en/tool/pdf-to-word');
        $response->assertSee('CW_INITIAL_MODE', false);
        $response->assertSee('pdf-to-word', false);
    }

    public function test_word_to_pdf_initial_mode_is_set()
    {
        $response = $this->get('/en/tool/word-to-pdf');
        $response->assertSee('CW_INITIAL_MODE', false);
        $response->assertSee('word-to-pdf', false);
    }

    public function test_home_page_lists_pdf_to_word_tool()
    {
        $response = $this->get('/en/');
        $response->assertStatus(200);
        $response->assertSee('/tool/pdf-to-word', false);
    }

    public function test_home_page_lists_word_to_pdf_tool()
    {
        $response = $this->get('/en/');
        $response->assertStatus(200);
        $response->assertSee('/tool/word-to-pdf', false);
    }

    public function test_pdf_to_word_route_name_resolves()
    {
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('tool.pdf_to_word'));
    }

    public function test_word_to_pdf_route_name_resolves()
    {
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('tool.word_to_pdf'));
    }

    public function test_pdf_to_word_locale_prefixes_work()
    {
        foreach (['en', 'es', 'fr', 'pt'] as $locale) {
            $response = $this->get("/{$locale}/tool/pdf-to-word");
            $response->assertStatus(200, "Failed for locale: {$locale}");
        }
    }

    public function test_word_to_pdf_locale_prefixes_work()
    {
        foreach (['en', 'es', 'fr', 'pt'] as $locale) {
            $response = $this->get("/{$locale}/tool/word-to-pdf");
            $response->assertStatus(200, "Failed for locale: {$locale}");
        }
    }

    public function test_pdf_to_word_has_include_images_option()
    {
        $response = $this->get('/en/tool/pdf-to-word');
        $response->assertSee('cw-include-images', false);
    }

    public function test_converter_view_has_progress_bar()
    {
        $response = $this->get('/en/tool/pdf-to-word');
        $response->assertSee('cw-progress', false);
    }

    public function test_converter_view_has_error_display()
    {
        $response = $this->get('/en/tool/pdf-to-word');
        $response->assertSee('cw-error', false);
    }
}
