<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::create('/pt/tool/merge-pdf', 'GET');
$response = $kernel->handle($request);
$html = $response->getContent();

// Extract all JSON-LD blocks
preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $matches);

echo "Found " . count($matches[1]) . " JSON-LD blocks.\n";
foreach($matches[1] as $idx => $jsonString) {
    if (strpos($jsonString, 'NewsArticle') !== false) {
        echo "\n--- NewsArticle JSON-LD ---\n";
        echo trim($jsonString) . "\n";
    }
}
