<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PdfWordConverterTest extends TestCase
{
    use RefreshDatabase;

    // ── GET page routes ───────────────────────────────────────────────────────

    public function test_pdf_to_word_route_returns_200()
    {
        $this->get('/en/tool/pdf-to-word')->assertStatus(200);
    }

    public function test_word_to_pdf_route_returns_200()
    {
        $this->get('/en/tool/word-to-pdf')->assertStatus(200);
    }

    public function test_pdf_to_word_locale_prefixes_work()
    {
        foreach (['en', 'es', 'fr', 'pt'] as $locale) {
            $this->get("/{$locale}/tool/pdf-to-word")
                 ->assertStatus(200, "Failed for locale: {$locale}");
        }
    }

    public function test_word_to_pdf_locale_prefixes_work()
    {
        foreach (['en', 'es', 'fr', 'pt'] as $locale) {
            $this->get("/{$locale}/tool/word-to-pdf")
                 ->assertStatus(200, "Failed for locale: {$locale}");
        }
    }

    // ── View content ──────────────────────────────────────────────────────────

    public function test_view_contains_drop_zone_and_convert_button()
    {
        $response = $this->get('/en/tool/pdf-to-word');
        $response->assertSee('cw-drop', false);
        $response->assertSee('cw-convert-btn', false);
        $response->assertSee('cw-progress', false);
        $response->assertSee('cw-error', false);
    }

    public function test_view_passes_initial_mode_to_js()
    {
        $this->get('/en/tool/pdf-to-word')
             ->assertSee('CW_INITIAL_MODE', false)
             ->assertSee('pdf-to-word', false);

        $this->get('/en/tool/word-to-pdf')
             ->assertSee('CW_INITIAL_MODE', false)
             ->assertSee('word-to-pdf', false);
    }

    public function test_view_passes_backend_routes_to_js()
    {
        $response = $this->get('/en/tool/pdf-to-word');
        $response->assertSee('CW_ROUTES', false);
        $response->assertSee('/convert/pdf-to-word', false);
        $response->assertSee('/convert/word-to-pdf', false);
    }

    public function test_view_includes_converter_script()
    {
        $this->get('/en/tool/pdf-to-word')->assertSee('convert-pdf-word.js', false);
    }

    // ── Named routes ──────────────────────────────────────────────────────────

    public function test_named_page_routes_exist()
    {
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('tool.pdf_to_word'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('tool.word_to_pdf'));
    }

    public function test_named_api_routes_exist()
    {
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('convert.pdf_to_word'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('convert.word_to_pdf'));
    }

    // ── Home page listing ─────────────────────────────────────────────────────

    public function test_home_page_lists_pdf_to_word_tool()
    {
        $this->get('/en/')->assertStatus(200)->assertSee('/tool/pdf-to-word', false);
    }

    public function test_home_page_lists_word_to_pdf_tool()
    {
        $this->get('/en/')->assertStatus(200)->assertSee('/tool/word-to-pdf', false);
    }

    // ── POST endpoint validation ──────────────────────────────────────────────

    public function test_pdf_to_word_endpoint_rejects_non_pdf()
    {
        $file = UploadedFile::fake()->create('document.txt', 10, 'text/plain');

        $this->postJson('/convert/pdf-to-word', ['file' => $file])
             ->assertStatus(422);
    }

    public function test_word_to_pdf_endpoint_rejects_unsupported_extension()
    {
        $file = UploadedFile::fake()->create('document.exe', 10, 'application/octet-stream');

        $this->postJson('/convert/word-to-pdf', ['file' => $file])
             ->assertStatus(422);
    }

    public function test_pdf_to_word_endpoint_requires_file()
    {
        $this->postJson('/convert/pdf-to-word', [])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['file']);
    }

    public function test_word_to_pdf_endpoint_requires_file()
    {
        $this->postJson('/convert/word-to-pdf', [])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['file']);
    }

    public function test_convert_routes_use_post_method()
    {
        // GET on POST-only routes should 405
        $this->get('/convert/pdf-to-word')->assertStatus(405);
        $this->get('/convert/word-to-pdf')->assertStatus(405);
    }
}
