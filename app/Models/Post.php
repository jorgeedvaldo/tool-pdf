<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'slug', 'language', 'description', 'image', 'thumbnail', 'og_image'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($post) {
            if (empty($post->slug)) {
                $post->slug = $post->generateSlug($post->title);
            }
        });

        static::updating(function ($post) {
            if (empty($post->slug)) {
                $post->slug = \Illuminate\Support\Str::slug($post->title);
            }
        });
    }

    protected static function booted()
    {
        static::created(function ($post) {
            if (empty($post->image)) {
                try {
                    $imageController = new \App\Http\Controllers\ArticleImageController();
                    $variants = $imageController->generate($post->title, $post->language ?? 'en');
                    $post->image     = $variants['image'];
                    $post->thumbnail = $variants['thumbnail'];
                    $post->og_image  = $variants['og_image'];
                    $post->saveQuietly();
                }
                catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Image generation failed: ' . $e->getMessage());
                }
            }
        });
    }

    private function generateSlug($title)
    {
        $base = Str::slug($title) ?: 'post';
        $slug = $base;
        $suffix = 2;
        while (static::whereSlug($slug)->exists()) {
            $slug = $base . '-' . $suffix++;
        }
        return $slug;
    }
}
