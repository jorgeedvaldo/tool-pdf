<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    @foreach($languages as $lang)
    <sitemap>
        <loc>{{ url('/sitemap/'.$lang.'.xml') }}</loc>
        <lastmod>{{ date('c') }}</lastmod>
    </sitemap>
    @endforeach
</sitemapindex>
