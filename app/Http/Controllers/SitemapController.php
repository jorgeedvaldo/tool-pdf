<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\Post;
use Illuminate\Http\Request;

class SitemapController extends Controller
{
    public function index()
    {
        $languages = ['en', 'es', 'fr', 'hi', 'pt', 'ru', 'zh'];
        return response()->view('sitemap_index', ['languages' => $languages])
                         ->header('Content-Type', 'text/xml');
    }

    public function show($lang)
    {
        $supportedLanguages = ['en', 'es', 'fr', 'hi', 'pt', 'ru', 'zh'];
        if (!in_array($lang, $supportedLanguages)) {
            abort(404);
        }

        $posts = Post::where('language', $lang)->get();
        $jobs = $lang === 'en' ? Job::all() : collect();
        
        $staticPages = [
            "{$lang}/about",
            "{$lang}/privacy",
            "{$lang}/terms",
            "{$lang}/legal",
            "{$lang}/blog"
        ];

        $tools = [
            "{$lang}/tool/sign-pdf",
            "{$lang}/tool/merge-pdf",
            "{$lang}/tool/split-pdf",
            "{$lang}/tool/reorganize-pdf",
            "{$lang}/tool/pdf-to-images",
            "{$lang}/tool/images-to-pdf",
            "{$lang}/tool/rotate-pdf",
            "{$lang}/tool/remove-pages",
            "{$lang}/tool/extract-pages",
            "{$lang}/tool/unlock-pdf",
            "{$lang}/tool/add-watermark",
            "{$lang}/tool/add-page-numbers",
            "{$lang}/tool/edit-pdf",
            "{$lang}/tool/overlay-pdfs",
        ];

        // Categories from home page
        $categories = [
            ['slug' => 'cat_manipulate'],
            ['slug' => 'cat_convert'],
            ['slug' => 'cat_image'],
            ['slug' => 'cat_security'],
            ['slug' => 'cat_manage'],
            ['slug' => 'cat_advanced'],
            ['slug' => 'cat_optimize'],
            ['slug' => 'cat_create'],
        ];

        return response()->view('sitemap', [
            'lang' => $lang,
            'posts' => $posts,
            'jobs' => $jobs,
            'staticPages' => $staticPages,
            'tools' => $tools,
            'categories' => $categories,
        ])->header('Content-Type', 'text/xml');
    }
}
