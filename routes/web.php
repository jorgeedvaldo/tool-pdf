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

// Group all routes under a {locale} prefix
Route::group([
    'prefix' => '{locale}',
    'where' => ['locale' => '[a-zA-Z]{2}']
], function () {
    
    Route::get('/', function () {
        return view('home');
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

});
