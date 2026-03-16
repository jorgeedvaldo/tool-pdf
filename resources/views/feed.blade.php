<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<rss version="2.0">
    <channel>
        <title>ToolPDF Blog</title>
        <link>{{ url('/en') }}</link>
        <description>Últimas notícias e atualizações - ToolPDF</description>
        <language>pt</language>
        <pubDate>{{ now()->toRfc2822String() }}</pubDate>

        @foreach($posts as $post)
        <item>
            <title>{{ htmlspecialchars($post->title) }}</title>
            <link>{{ url('/' . $post->language . '/blog/' . $post->slug) }}</link>
            <description><![CDATA[{{ strip_tags($post->description) }}]]></description>
            <pubDate>{{ \Carbon\Carbon::parse($post->created_at)->toRfc2822String() }}</pubDate>
            <guid>{{ url('/' . $post->language . '/blog/' . $post->slug) }}</guid>
        </item>
        @endforeach
    </channel>
</rss>
