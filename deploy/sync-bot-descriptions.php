<?php

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

app(App\Services\TelegramService::class)->syncPublicDescriptions();

$fa = app(App\Services\TelegramService::class)->forLanguage('fa')->request('getMyDescription', ['language_code' => 'fa']);
$en = app(App\Services\TelegramService::class)->forLanguage('en')->request('getMyDescription', ['language_code' => 'en']);

echo 'FA: '.data_get($fa?->json(), 'result.description', 'n/a').PHP_EOL;
echo 'EN: '.data_get($en?->json(), 'result.description', 'n/a').PHP_EOL;
