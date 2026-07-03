<?php
declare(strict_types=1);

use App\Kernel;
use Symfony\Component\HttpFoundation\Request;

require dirname(__DIR__).'/vendor/autoload.php';

$dotenv = new Symfony\Component\Dotenv\Dotenv();
$envFile = dirname(__DIR__).'/.env';
if (file_exists($envFile)) {
    $dotenv->loadEnv($envFile);
}

$kernel = new Kernel($_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? 'dev', (bool) ($_SERVER['APP_DEBUG'] ?? $_ENV['APP_DEBUG'] ?? true));
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);

