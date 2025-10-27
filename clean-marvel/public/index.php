<?php

declare(strict_types=1);

use App\Albums\Application\DTO\CreateAlbumRequest;
use App\Albums\Application\DTO\UpdateAlbumRequest;
use App\Heroes\Application\DTO\CreateHeroRequest;
use App\Heroes\Application\DTO\UpdateHeroRequest;
use App\Shared\Http\JsonResponse;

require_once __DIR__ . '/../vendor/autoload.php';

$container = require __DIR__ . '/../src/bootstrap.php';

if (!defined('ALBUM_UPLOAD_DIR')) {
    define('ALBUM_UPLOAD_DIR', __DIR__ . '/uploads/albums');
}
if (!defined('ALBUM_UPLOAD_URL_PREFIX')) {
    define('ALBUM_UPLOAD_URL_PREFIX', '/uploads/albums/');
}
if (!defined('ALBUM_COVER_MAX_BYTES')) {
    define('ALBUM_COVER_MAX_BYTES', 5 * 1024 * 1024);
}

ensureDirectory(ALBUM_UPLOAD_DIR);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($path === '/' || $path === '') {
    header('Location: /albums.html');
    exit;
}

if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    route($method, $path, $container);
} catch (InvalidArgumentException $exception) {
    JsonResponse::error($exception->getMessage(), 422);
} catch (Throwable $exception) {
    JsonResponse::error('Error inesperado: ' . $exception->getMessage(), 500);
}

function route(string $method, string $path, array $container): void
{
    switch ($method) {
        case 'GET':
            handleGet($path, $container);
            break;
        case 'POST':
            handlePost($path, $container);
            break;
        case 'PUT':
            handlePut($path, $container);
            break;
        case 'DELETE':
            handleDelete($path, $container);
            break;
        default:
            JsonResponse::error('Método no permitido.', 405);
    }
}

function handleGet(string $path, array $container): void
{
    if ($path === '/albums') {
        $data = $container['useCases']['listAlbums']->execute();
        JsonResponse::success($data);
        return;
    }

    if (preg_match('#^/albums/([A-Za-z0-9\-]+)/heroes$#', $path, $matches) === 1) {
        $albumId = $matches[1];
        $data = $container['useCases']['listHeroes']->execute($albumId);
        JsonResponse::success($data);
        return;
    }

    if ($path === '/notifications') {
        $data = $container['useCases']['listNotifications']->execute();
        JsonResponse::success($data);
        return;
    }

    if (preg_match('#^/heroes/([A-Za-z0-9\-]+)$#', $path, $matches) === 1) {
        try {
            $data = $container['useCases']['findHero']->execute($matches[1]);
            JsonResponse::success($data);
            return;
        } catch (InvalidArgumentException $exception) {
            JsonResponse::error($exception->getMessage(), 404);
            return;
        }
    }

    JsonResponse::error('Endpoint no encontrado.', 404);
}

function handlePost(string $path, array $container): void
{
    if (preg_match('#^/albums/([A-Za-z0-9\-]+)/cover$#', $path, $matches) === 1) {
        handleAlbumCoverUpload($matches[1], $container);
        return;
    }

    if ($path === '/admin/seed-all') {
        // Dev endpoint to force seed heroes. Access with ?key=dev
        if (($_GET['key'] ?? null) !== 'dev') {
            JsonResponse::error('Unauthorized', 403);
            return;
        }
        $createdCount = $container['seedHeroesService']->seedForce();
        JsonResponse::success(['created' => $createdCount], 201);
        return;
    }

    if ($path === '/albums') {
        $payload = body();
        $nombre = trim((string)($payload['nombre'] ?? ''));
        $coverImage = array_key_exists('coverImage', $payload) ? (string)$payload['coverImage'] : null;

        $response = $container['useCases']['createAlbum']->execute(new CreateAlbumRequest($nombre, $coverImage));
        JsonResponse::success($response->toArray(), 201);
        return;
    }

    if (preg_match('#^/albums/([A-Za-z0-9\-]+)/heroes$#', $path, $matches) === 1) {
        $payload = body();
        $albumId = $matches[1];
        $nombre = trim((string)($payload['nombre'] ?? ''));
        $contenido = (string)($payload['contenido'] ?? '');
        $imagen = trim((string)($payload['imagen'] ?? ''));

        if (empty($nombre) || empty($imagen)) {
            JsonResponse::error('Los campos nombre e imagen son obligatorios.', 422);
            return;
        }

        $useCase = $container['useCases']['createHero'];
        $response = $useCase->execute(new CreateHeroRequest($albumId, $nombre, $contenido, $imagen));
        JsonResponse::success($response->toArray(), 201);
        return;
    }

    JsonResponse::error('Endpoint no encontrado.', 404);
}

function handlePut(string $path, array $container): void
{
    if (preg_match('#^/albums/([A-Za-z0-9\-]+)$#', $path, $matches) === 1) {
        $payload = body();
        $nombre = array_key_exists('nombre', $payload) ? (string)$payload['nombre'] : null;
        $coverProvided = array_key_exists('coverImage', $payload);
        $coverImage = $coverProvided ? ($payload['coverImage'] ?? null) : null;

        $useCase = $container['useCases']['updateAlbum'];
        $response = $useCase->execute(new UpdateAlbumRequest($matches[1], $nombre, $coverImage, $coverProvided));
        JsonResponse::success($response->toArray());
        return;
    }

    if (preg_match('#^/heroes/([A-Za-z0-9\\-]+)$#', $path, $matches) === 1) {
        $payload = body();
        $nombre = array_key_exists('nombre', $payload) ? (string)$payload['nombre'] : null;
        $contenido = array_key_exists('contenido', $payload) ? (string)$payload['contenido'] : null;
        $imagen = array_key_exists('imagen', $payload) ? (string)$payload['imagen'] : null;

        $useCase = $container['useCases']['updateHero'];
        $response = $useCase->execute(new UpdateHeroRequest($matches[1], $nombre, $contenido, $imagen));
        JsonResponse::success($response->toArray());
        return;
    }

    JsonResponse::error('Endpoint no encontrado.', 404);
}

function handleDelete(string $path, array $container): void
{
    if (preg_match('#^/albums/([A-Za-z0-9\-]+)$#', $path, $matches) === 1) {
        try {
            $container['useCases']['deleteAlbum']->execute($matches[1]);
            JsonResponse::success(['message' => 'Álbum eliminado.']);
            return;
        } catch (InvalidArgumentException $exception) {
            JsonResponse::error($exception->getMessage(), 404);
            return;
        }
    }

    if ($path === '/notifications') {
        try {
            $container['useCases']['clearNotifications']->execute();
            JsonResponse::success(['message' => 'Notificaciones limpiadas']);
            return;
        } catch (Throwable $exception) {
            JsonResponse::error('No se pudieron limpiar las notificaciones.', 500);
            return;
        }
    }

    if (preg_match('#^/heroes/([A-Za-z0-9\-]+)$#', $path, $matches) === 1) {
        try {
            $container['useCases']['deleteHero']->execute($matches[1]);
            JsonResponse::success(['message' => 'Héroe eliminado.']);
            return;
        } catch (InvalidArgumentException $exception) {
            JsonResponse::error($exception->getMessage(), 404);
            return;
        }
    }

    JsonResponse::error('Endpoint no encontrado.', 404);
}

function body(): array
{
    if (defined('PHPUNIT_RUNNING') && isset($GLOBALS['mock_php_input'])) {
        $raw = $GLOBALS['mock_php_input'];
    } else {
        $raw = file_get_contents('php://input');
    }

    if ($raw === false || trim($raw) === '') {
        return [];
    }

    $decoded = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
        JsonResponse::error('JSON inválido', 400);
        exit;
    }

    return $decoded;
}

function handleAlbumCoverUpload(string $albumId, array $container): void
{
    if (($container['albumRepository']->find($albumId) ?? null) === null) {
        JsonResponse::error('Álbum no encontrado.', 404);
        return;
    }

    if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
        JsonResponse::error('Archivo no proporcionado.', 400);
        return;
    }

    $file = $_FILES['file'];
    $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error !== UPLOAD_ERR_OK) {
        JsonResponse::error('Error al subir el archivo.', 400);
        return;
    }

    $size = (int)($file['size'] ?? 0);
    if ($size <= 0) {
        JsonResponse::error('Archivo inválido.', 400);
        return;
    }

    if ($size > ALBUM_COVER_MAX_BYTES) {
        JsonResponse::error('El archivo excede el tamaño permitido (5MB).', 413);
        return;
    }

    $originalName = (string)($file['name'] ?? '');
    $extension = strtolower((string)pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

    if (!in_array($extension, $allowedExtensions, true)) {
        JsonResponse::error('Formato de archivo no permitido.', 400);
        return;
    }

    $tmpPath = (string)($file['tmp_name'] ?? '');
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        JsonResponse::error('Archivo temporal no válido.', 400);
        return;
    }

    ensureDirectory(ALBUM_UPLOAD_DIR);

    $sanitizedAlbumId = preg_replace('/[^A-Za-z0-9\-]/', '', $albumId) ?: $albumId;
    $filename = sprintf('%s-%s.%s', $sanitizedAlbumId, bin2hex(random_bytes(6)), $extension);
    $destination = rtrim(ALBUM_UPLOAD_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($tmpPath, $destination)) {
        JsonResponse::error('No se pudo guardar el archivo.', 500);
        return;
    }

    $coverUrl = ALBUM_UPLOAD_URL_PREFIX . $filename;

    $updateUseCase = $container['useCases']['updateAlbum'];
    $updateUseCase->execute(new UpdateAlbumRequest($albumId, null, $coverUrl, true));

    JsonResponse::success([
        'albumId' => $albumId,
        'coverImage' => $coverUrl,
    ], 201);
}

function ensureDirectory(string $path): void
{
    if (!is_dir($path)) {
        mkdir($path, 0777, true);
    }
}
