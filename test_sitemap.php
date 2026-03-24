<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request1 = Illuminate\Http\Request::create('/sitemap.xml', 'GET');
$response1 = $kernel->handle($request1);
echo "--- /sitemap.xml ---\n";
echo substr($response1->getContent(), 0, 500) . "...\n";

$request2 = Illuminate\Http\Request::create('/sitemap/en.xml', 'GET');
$response2 = $kernel->handle($request2);
echo "--- /sitemap/en.xml ---\n";
echo substr($response2->getContent(), 0, 800) . "...\n";

$request3 = Illuminate\Http\Request::create('/sitemap/pt.xml', 'GET');
$response3 = $kernel->handle($request3);
echo "--- /sitemap/pt.xml ---\n";
echo substr($response3->getContent(), 0, 800) . "...\n";
