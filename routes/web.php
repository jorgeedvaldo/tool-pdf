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

    Route::get('/tool/merge-pdf', function () {
        return view('tools.merge');
    })->name('tool.merge_pdf');

    Route::get('/tool/split-pdf', function () {
        return view('tools.split');
    })->name('tool.split_pdf');
    
});
