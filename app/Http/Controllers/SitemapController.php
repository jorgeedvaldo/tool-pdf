<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\Post;
use Illuminate\Http\Request;

class SitemapController extends Controller
{
    public function index()
    {
        $posts = Post::all();
        $jobs = Job::all();
        
        // You can define explicit static pages here, or fetch them dynamically
        $staticPages = [
            'en/about',
            'pt/about',
            'en/privacy',
            'pt/privacy',
            'en/terms',
            'pt/terms',
            'en/legal',
            'pt/legal',
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
            'posts' => $posts,
            'jobs' => $jobs,
            'staticPages' => $staticPages,
            'categories' => $categories,
        ])->header('Content-Type', 'text/xml');
    }
}
