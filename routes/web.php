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
