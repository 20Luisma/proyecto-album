<?php

declare(strict_types=1);

$baseDir = __DIR__ . '/..';
$autoload = $baseDir . '/vendor/autoload.php';

if (!file_exists($autoload)) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'error' => 'Dependencias del microservicio ausentes. Ejecuta `composer install` dentro de `openai-service/`.'
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return;
}

require $autoload;

try {
    Dotenv\Dotenv::createImmutable($baseDir)->safeLoad();
} catch (\Throwable $exception) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'error' => 'No se pudo cargar la configuración del microservicio: ' . $exception->getMessage(),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return;
}

$router = new Creawebes\OpenAI\Http\Router();
$router->handle($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
