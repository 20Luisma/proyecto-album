<?php

declare(strict_types=1);

use Src\Shared\Http\Router;

require_once __DIR__ . '/../vendor/autoload.php';

$container = require __DIR__ . '/../src/bootstrap.php';

if (!function_exists('route')) {
    /**
     * @param array<string, mixed> $container
     */
    function route(string $method, string $path, array $container): void
    {
        (new Router($container))->handle($method, $path);
    }
}

if (!defined('SKIP_HTTP_BOOT')) {
    if (!defined('ALBUM_UPLOAD_DIR')) {
        define('ALBUM_UPLOAD_DIR', __DIR__ . '/uploads/albums');
    }
    if (!defined('ALBUM_UPLOAD_URL_PREFIX')) {
        define('ALBUM_UPLOAD_URL_PREFIX', '/uploads/albums/');
    }
    if (!defined('ALBUM_COVER_MAX_BYTES')) {
        define('ALBUM_COVER_MAX_BYTES', 5 * 1024 * 1024);
    }

    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($method === 'OPTIONS') {
        http_response_code(204);
        exit;
    }

    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

    (new Router($container))->handle($method, $path);
}
