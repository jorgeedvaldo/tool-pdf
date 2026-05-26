<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Redirect the root domain to the default language (English) or session language
Route::get('/', function () {
    $locale = session('locale', 'en');
    return redirect('/' . $locale);
});

Route::get('/linkstorage', function () {
    // Cria o link simbólico (storage -> public)
    Artisan::call('storage:link');
    Artisan::call('migrate');
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    Artisan::call('optimize:clear');
    
    return 'Symlink criado: <pre>' . Artisan::output() . '</pre>';
});

// Group all routes under a {locale} prefix
Route::group([
    'prefix' => '{locale}',
    'where' => ['locale' => '[a-zA-Z]{2}']
], function () {
    
    Route::get('/', function () {
        $locale = session('locale', 'en');
        $recentPosts = \App\Models\Post::where('language', $locale)
                           ->orderBy('created_at', 'desc')
                           ->take(3)
                           ->get();
        return view('home', compact('recentPosts'));
    })->name('home');

    Route::get('/tool/sign-pdf', function () {
        return view('tools.sign_pdf');
    })->name('tool.sign_pdf');

    Route::get('/tool/merge-pdf', function () {
        return view('tools.merge');
    })->name('tool.merge_pdf');

    Route::get('/tool/split-pdf', function () {
        return view('tools.split');
    })->name('tool.split_pdf');
    
    Route::get('/tool/compress-pdf', function () {
        return view('tools.compress_pdf');
    })->name('tool.compress_pdf');
    
    Route::get('/tool/reorganize-pdf', function () {
        return view('tools.reorganize');
    })->name('tool.reorganize_pages');
    
    Route::get('/tool/pdf-to-images', function () {
        return view('tools.pdf_to_images');
    })->name('tool.pdf_to_images');
    
    Route::get('/tool/images-to-pdf', function () {
        return view('tools.images_to_pdf');
    })->name('tool.images_to_pdf');

    Route::get('/tool/rotate-pdf', function () {
        return view('tools.rotate_pages');
    })->name('tool.rotate_pages');

    Route::get('/tool/remove-pages', function () {
        return view('tools.remove_pages');
    })->name('tool.remove_pages');

    Route::get('/tool/extract-pages', function () {
        return view('tools.extract_pages');
    })->name('tool.extract_pages');

    Route::get('/tool/protect-pdf', function () {
        return view('tools.protect_pdf');
    })->name('tool.protect_pdf');

    Route::get('/tool/unlock-pdf', function () {
        return view('tools.unlock_pdf');
    })->name('tool.unlock_pdf');

    Route::get('/tool/add-watermark', function () {
        return view('tools.add_watermark');
    })->name('tool.add_watermark');

    Route::get('/tool/add-page-numbers', function () {
        return view('tools.add_page_numbers');
    })->name('tool.add_page_numbers');

    Route::get('/tool/edit-pdf', function () {
        return view('tools.edit_pdf');
    })->name('tool.edit_pdf');

    Route::get('/tool/overlay-pdfs', function () {
        return view('tools.overlay_pdfs');
    })->name('tool.overlay_pdfs');

    Route::get('/tool/pdf-ocr', function () {
        return view('tools.pdf_ocr');
    })->name('tool.ocr_pdf');

    Route::get('/tool/compare-pdf', function () {
        return view('tools.compare_pdf');
    })->name('tool.compare_pdf');

    Route::get('/tool/pdf-to-word', function () {
        return view('tools.convert_pdf_word', ['mode' => 'pdf-to-word']);
    })->name('tool.pdf_to_word');

    Route::get('/tool/word-to-pdf', function () {
        return view('tools.convert_pdf_word', ['mode' => 'word-to-pdf']);
    })->name('tool.word_to_pdf');

    Route::get('/tool/pdf-to-excel', function () {
        return view('tools.convert_generic', ['config' => [
            'title'       => 'PDF to Excel',
            'title_desc'  => 'Extract data from PDF into an editable spreadsheet.',
            'accept'      => '.pdf,application/pdf',
            'accept_label'=> 'PDF file',
            'route'       => 'convert.pdf_to_excel',
            'output_ext'  => '.xlsx',
            'icon_color'  => 'success',
            'icon_class'  => 'bi-file-earmark-spreadsheet',
            'badges'      => ['⚡ Server-side', '🗑️ Auto-deleted'],
        ]]);
    })->name('tool.pdf_to_excel');

    Route::get('/tool/excel-to-pdf', function () {
        return view('tools.convert_generic', ['config' => [
            'title'       => 'Excel to PDF',
            'title_desc'  => 'Convert Excel spreadsheets to PDF. Supports .xlsx, .xls, .ods and .csv.',
            'accept'      => '.xlsx,.xls,.ods,.csv',
            'accept_label'=> 'Excel or spreadsheet file (.xlsx, .xls, .ods, .csv)',
            'route'       => 'convert.excel_to_pdf',
            'output_ext'  => '.pdf',
            'icon_color'  => 'success',
            'icon_class'  => 'bi-file-earmark-spreadsheet',
            'badges'      => ['⚡ Server-side', '🗑️ Auto-deleted'],
        ]]);
    })->name('tool.excel_to_pdf');

    Route::get('/tool/pdf-to-ppt', function () {
        return view('tools.convert_generic', ['config' => [
            'title'       => 'PDF to PowerPoint',
            'title_desc'  => 'Convert PDF pages to editable PowerPoint slides.',
            'accept'      => '.pdf,application/pdf',
            'accept_label'=> 'PDF file',
            'route'       => 'convert.pdf_to_ppt',
            'output_ext'  => '.pptx',
            'icon_color'  => 'warning',
            'icon_class'  => 'bi-file-earmark-slides',
            'badges'      => ['⚡ Server-side', '🗑️ Auto-deleted'],
        ]]);
    })->name('tool.pdf_to_ppt');

    Route::get('/tool/ppt-to-pdf', function () {
        return view('tools.convert_generic', ['config' => [
            'title'       => 'PowerPoint to PDF',
            'title_desc'  => 'Convert PowerPoint presentations to PDF. Supports .pptx and .odp.',
            'accept'      => '.pptx,.ppt,.odp',
            'accept_label'=> 'PowerPoint file (.pptx, .odp)',
            'route'       => 'convert.ppt_to_pdf',
            'output_ext'  => '.pdf',
            'icon_color'  => 'warning',
            'icon_class'  => 'bi-file-earmark-slides',
            'badges'      => ['⚡ Server-side', '🗑️ Auto-deleted'],
        ]]);
    })->name('tool.ppt_to_pdf');

    Route::get('/tool/html-to-pdf', function () {
        return view('tools.html_to_pdf');
    })->name('tool.html_to_pdf');

    Route::get('/tool/flatten-pdf', function () {
        return view('tools.flatten_pdf');
    })->name('tool.flatten_pdf');

    Route::get('/tool/repair-pdf', function () {
        return view('tools.repair_pdf');
    })->name('tool.repair_pdf');

    // ── New tools ─────────────────────────────────────────────────────────────

    Route::get('/tool/workflow-editor', function () {
        return view('tools.workflow_editor');
    })->name('tool.workflow_editor');

    Route::get('/tool/pdf-to-grayscale', function () {
        return view('tools.pdf_to_grayscale');
    })->name('tool.pdf_to_grayscale');

    Route::get('/tool/add-header-footer', function () {
        return view('tools.add_header_footer');
    })->name('tool.add_header_footer');

    Route::get('/tool/reverse-pages', function () {
        return view('tools.reverse_pages');
    })->name('tool.reverse_pages');

    Route::get('/tool/txt-to-pdf', function () {
        return view('tools.txt_to_pdf');
    })->name('tool.txt_to_pdf');

    Route::get('/tool/n-up-pdf', function () {
        return view('tools.n_up_pdf');
    })->name('tool.n_up_pdf');

    Route::get('/tool/extract-images', function () {
        return view('tools.extract_images');
    })->name('tool.extract_images');

    Route::get('/tool/markdown-to-pdf', function () {
        return view('tools.markdown_to_pdf');
    })->name('tool.markdown_to_pdf');

    Route::get('/tool/view-pdf', function () {
        return view('tools.view_pdf');
    })->name('tool.view_pdf');

    // Info Pages
    Route::get('/about', function () {
        return view('pages.about');
    })->name('pages.about');

    Route::get('/privacy', function () {
        return view('pages.privacy');
    })->name('pages.privacy');

    Route::get('/terms', function () {
        return view('pages.terms');
    })->name('pages.terms');

    Route::get('/legal', function () {
        return view('pages.legal');
    })->name('pages.legal');

    // Blog Pages
    Route::get('/blog', [\App\Http\Controllers\PostController::class, 'index'])->name('blog.index');
    Route::get('/blog/{slug}', [\App\Http\Controllers\PostController::class, 'show'])->name('blog.show');
});

// Jobs are English only, so they are outside the localized group
Route::get('/jobs', [\App\Http\Controllers\JobController::class, 'index'])->name('jobs.index');
Route::get('/jobs/{slug}', [\App\Http\Controllers\JobController::class, 'show'])->name('jobs.show');

Route::get('/sitemap.xml', [\App\Http\Controllers\SitemapController::class, 'index']);
Route::get('/sitemap/{lang}.xml', [\App\Http\Controllers\SitemapController::class, 'show'])->where('lang', '[a-zA-Z]{2}');
Route::get('/feed', [\App\Http\Controllers\FeedController::class, 'index']);

// Server-side conversion endpoints (pure PHP — no system binaries required)
Route::post('/convert/pdf-to-word',  [\App\Http\Controllers\ConvertController::class, 'pdfToWord'])->name('convert.pdf_to_word');
Route::post('/convert/word-to-pdf',  [\App\Http\Controllers\ConvertController::class, 'wordToPdf'])->name('convert.word_to_pdf');
Route::post('/convert/pdf-to-excel', [\App\Http\Controllers\ConvertController::class, 'pdfToExcel'])->name('convert.pdf_to_excel');
Route::post('/convert/excel-to-pdf', [\App\Http\Controllers\ConvertController::class, 'excelToPdf'])->name('convert.excel_to_pdf');
Route::post('/convert/pdf-to-ppt',   [\App\Http\Controllers\ConvertController::class, 'pdfToPpt'])->name('convert.pdf_to_ppt');
Route::post('/convert/ppt-to-pdf',   [\App\Http\Controllers\ConvertController::class, 'pptToPdf'])->name('convert.ppt_to_pdf');
Route::post('/convert/html-to-pdf',  [\App\Http\Controllers\ConvertController::class, 'htmlToPdf'])->name('convert.html_to_pdf');
