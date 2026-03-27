<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'slug', 'language', 'description', 'image'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($post) {
            $post->slug = $post->generateSlug($post->title, $post->id);
            $post->save(); // NOTE: this was here but calling save() inside creating() causes infinite loops or double inserts
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
                    $post->image = $imageController->generate($post->title);
                    $post->saveQuietly();
                }
                catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Image generation failed: ' . $e->getMessage());
                }
            }
        });
    }

    private function generateSlug($title, $id)
    {
        if (static::whereSlug($slug = Str::slug($title))->exists()) {
            $max = static::whereTitle($title)->latest('id');
            $slug = $slug . '-' . $id;
        }
        return $slug;
    }
}
