<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class NewToolsTest extends TestCase
{
    use RefreshDatabase;

    // ── GET page routes return 200 ────────────────────────────────────────────

    public function test_new_tool_pages_return_200()
    {
        $routes = [
            '/en/tool/pdf-to-excel',
            '/en/tool/excel-to-pdf',
            '/en/tool/pdf-to-ppt',
            '/en/tool/ppt-to-pdf',
            '/en/tool/html-to-pdf',
            '/en/tool/flatten-pdf',
            '/en/tool/repair-pdf',
        ];

        foreach ($routes as $route) {
            $this->get($route)->assertStatus(200, "Failed: {$route}");
        }
    }

    public function test_new_tools_work_across_locales()
    {
        foreach (['en', 'es', 'pt', 'fr'] as $locale) {
            $this->get("/{$locale}/tool/excel-to-pdf")->assertStatus(200);
            $this->get("/{$locale}/tool/html-to-pdf")->assertStatus(200);
            $this->get("/{$locale}/tool/flatten-pdf")->assertStatus(200);
        }
    }

    // ── Named routes exist ────────────────────────────────────────────────────

    public function test_named_page_routes_exist()
    {
        $names = [
            'tool.pdf_to_excel', 'tool.excel_to_pdf',
            'tool.pdf_to_ppt',   'tool.ppt_to_pdf',
            'tool.html_to_pdf',  'tool.flatten_pdf',
            'tool.repair_pdf',
        ];

        foreach ($names as $name) {
            $this->assertTrue(
                \Illuminate\Support\Facades\Route::has($name),
                "Named route missing: {$name}"
            );
        }
    }

    public function test_named_post_routes_exist()
    {
        $names = [
            'convert.pdf_to_excel', 'convert.excel_to_pdf',
            'convert.pdf_to_ppt',   'convert.ppt_to_pdf',
            'convert.html_to_pdf',
        ];

        foreach ($names as $name) {
            $this->assertTrue(
                \Illuminate\Support\Facades\Route::has($name),
                "Named POST route missing: {$name}"
            );
        }
    }

    // ── View content ──────────────────────────────────────────────────────────

    public function test_generic_convert_pages_have_required_elements()
    {
        foreach (['/en/tool/pdf-to-excel', '/en/tool/excel-to-pdf',
                  '/en/tool/pdf-to-ppt',   '/en/tool/ppt-to-pdf'] as $url) {
            $response = $this->get($url);
            $response->assertSee('cg-zone',        false);
            $response->assertSee('cg-convert-btn', false);
            $response->assertSee('cg-progress',    false);
            $response->assertSee('cg-error',       false);
            $response->assertSee('CG_CONFIG',      false);
            $response->assertSee('convert-generic.js', false);
        }
    }

    public function test_html_to_pdf_has_url_input()
    {
        $response = $this->get('/en/tool/html-to-pdf');
        $response->assertSee('hp-url',        false);
        $response->assertSee('hp-convert-btn',false);
        $response->assertSee('HP_ROUTE',      false);
        $response->assertSee('html-to-pdf.js',false);
    }

    public function test_flatten_pdf_loads_pdflib()
    {
        $response = $this->get('/en/tool/flatten-pdf');
        $response->assertSee('fp-zone',      false);
        $response->assertSee('fp-btn',       false);
        $response->assertSee('pdf-lib',      false);
        $response->assertSee('flatten-pdf.js', false);
    }

    public function test_repair_pdf_loads_pdflib()
    {
        $response = $this->get('/en/tool/repair-pdf');
        $response->assertSee('rp-zone',     false);
        $response->assertSee('rp-btn',      false);
        $response->assertSee('pdf-lib',     false);
        $response->assertSee('repair-pdf.js', false);
    }

    // ── Home page lists new tools ─────────────────────────────────────────────

    public function test_home_page_lists_all_new_tools()
    {
        $response = $this->get('/en/');
        $response->assertStatus(200);

        foreach ([
            '/tool/pdf-to-excel', '/tool/excel-to-pdf',
            '/tool/pdf-to-ppt',   '/tool/ppt-to-pdf',
            '/tool/html-to-pdf',  '/tool/flatten-pdf',
            '/tool/repair-pdf',
        ] as $path) {
            $response->assertSee($path, false, "Home missing: {$path}");
        }
    }

    // ── POST endpoint validation ──────────────────────────────────────────────

    public function test_excel_to_pdf_rejects_wrong_extension()
    {
        $file = UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf');
        $this->postJson('/convert/excel-to-pdf', ['file' => $file])->assertStatus(422);
    }

    public function test_pdf_to_excel_requires_pdf()
    {
        $file = UploadedFile::fake()->create('sheet.xlsx', 10,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->postJson('/convert/pdf-to-excel', ['file' => $file])->assertStatus(422);
    }

    public function test_ppt_to_pdf_rejects_wrong_extension()
    {
        $file = UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf');
        $this->postJson('/convert/ppt-to-pdf', ['file' => $file])->assertStatus(422);
    }

    public function test_html_to_pdf_requires_url()
    {
        $this->postJson('/convert/html-to-pdf', [])->assertStatus(422)->assertJsonValidationErrors(['url']);
    }

    public function test_html_to_pdf_rejects_invalid_url()
    {
        $this->postJson('/convert/html-to-pdf', ['url' => 'not-a-url'])->assertStatus(422);
    }

    public function test_html_to_pdf_rejects_private_ip()
    {
        // localhost / private IP should be blocked by SSRF guard
        $this->postJson('/convert/html-to-pdf', ['url' => 'http://127.0.0.1/secret'])
             ->assertStatus(422);
    }

    public function test_new_post_routes_reject_get()
    {
        foreach ([
            '/convert/pdf-to-excel', '/convert/excel-to-pdf',
            '/convert/pdf-to-ppt',   '/convert/ppt-to-pdf',
            '/convert/html-to-pdf',
        ] as $path) {
            $this->get($path)->assertStatus(405, "Should be POST-only: {$path}");
        }
    }
}
