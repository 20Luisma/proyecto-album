<?php

declare(strict_types=1);

use App\AI\OpenAIComicGenerator;
use App\Albums\Application\DTO\CreateAlbumRequest;
use App\Albums\Application\DTO\UpdateAlbumRequest;
use App\Heroes\Application\DTO\CreateHeroRequest;
use App\Heroes\Application\DTO\UpdateHeroRequest;
use App\Dev\Test\PhpUnitTestRunner;
use App\Shared\Http\JsonResponse;

require_once __DIR__ . '/../vendor/autoload.php';

$container = require __DIR__ . '/../src/bootstrap.php';

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

    ensureDirectory(ALBUM_UPLOAD_DIR);

    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if (handleHtmlRoutes($method, $path)) {
        return;
    }

    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');

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
    if (wantsHtmlResponse()) {
        renderNotFound();
        return;
    }

    if ($path === '/albums') {
        $data = $container['useCases']['listAlbums']->execute();
        JsonResponse::success($data);
        return;
    }

    if ($path === '/heroes') {
        $data = $container['useCases']['listHeroes']->execute();
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
    if ($path === '/dev/tests/run') {
        $testRunner = $container['devTools']['testRunner'] ?? null;
        if (!$testRunner instanceof PhpUnitTestRunner) {
            JsonResponse::error('Runner de tests no disponible.', 500);
            return;
        }

        $result = $testRunner->run();

        if (($result['status'] ?? null) === 'skipped') {
            JsonResponse::error($result['message'] ?? 'La ejecución de tests está deshabilitada.', 403);
            return;
        }

        if (($result['status'] ?? null) === 'error') {
            JsonResponse::error($result['message'] ?? 'Error al ejecutar la suite de tests.', 500);
            return;
        }

        JsonResponse::success($result);
        return;
    }

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

    if ($path === '/comics/generate') {
        handleComicGeneration($container);
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

function handleComicGeneration(array $container): void
{
    $payload = body();
    $heroIds = $payload['heroIds'] ?? [];

    if (!is_array($heroIds) || $heroIds === []) {
        JsonResponse::error('Selecciona al menos un héroe para generar el cómic.', 422);
        return;
    }

    $heroRepository = $container['heroRepository'] ?? null;
    if ($heroRepository === null) {
        JsonResponse::error('Repositorio de héroes no disponible.', 500);
        return;
    }

    $heroes = [];
    foreach ($heroIds as $heroId) {
        if (!is_string($heroId) || trim($heroId) === '') {
            continue;
        }
        $hero = $heroRepository->find($heroId);
        if ($hero === null) {
            continue;
        }
        $heroes[] = [
            'heroId' => $hero->heroId(),
            'nombre' => $hero->nombre(),
            'contenido' => $hero->contenido(),
            'imagen' => $hero->imagen(),
        ];
    }

    if ($heroes === []) {
        JsonResponse::error('No se encontraron héroes válidos para generar el cómic.', 404);
        return;
    }

    $generator = $container['ai']['comicGenerator'] ?? null;
    if (!$generator instanceof OpenAIComicGenerator || !$generator->isConfigured()) {
        JsonResponse::error('La generación con IA no está disponible. Configura OPENAI_API_KEY.', 503);
        return;
    }

    try {
        $result = $generator->generateComic($heroes);
        JsonResponse::success($result, 201);
    } catch (InvalidArgumentException $exception) {
        JsonResponse::error($exception->getMessage(), 422);
    } catch (RuntimeException $exception) {
        JsonResponse::error($exception->getMessage(), 502);
    } catch (Throwable $exception) {
        JsonResponse::error('No se pudo generar el cómic con IA: ' . $exception->getMessage(), 502);
    }
}

function handleHtmlRoutes(string $method, string $path): bool
{
    if ($method !== 'GET') {
        return false;
    }

    $normalizedPath = $path === '' ? '/' : $path;
    $viewMap = [
        '/' => 'albums',
        '/albums' => 'albums',
        '/heroes' => 'heroes',
        '/comic' => 'comic',
    ];

    if (!array_key_exists($normalizedPath, $viewMap)) {
        return false;
    }

    if ($normalizedPath === '/') {
        renderView($viewMap[$normalizedPath]);
        return true;
    }

    if (wantsHtmlResponse()) {
        renderView($viewMap[$normalizedPath]);
        return true;
    }

    return false;
}

function wantsHtmlResponse(): bool
{
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    return stripos($accept, 'text/html') !== false;
}

function renderView(string $view): void
{
    $viewFile = __DIR__ . '/../views/' . $view . '.php';
    if (!is_file($viewFile)) {
        http_response_code(500);
        echo 'Vista no encontrada.';
        return;
    }

    require $viewFile;
}

function renderNotFound(): void
{
    http_response_code(404);
    $pageTitle = '404 — Recurso no encontrado';
    $additionalStyles = [];
    require __DIR__ . '/../views/header.php';
    ?>
    <main class="site-main">
      <div class="max-w-3xl mx-auto py-16 px-4 text-center space-y-6">
        <h1 class="text-5xl font-bold text-white">404</h1>
        <p class="text-lg text-gray-300 leading-relaxed">La ruta solicitada no existe o se encuentra temporalmente inactiva.</p>
        <a href="/albums" class="btn btn-primary inline-flex items-center gap-2 mx-auto">Volver al inicio</a>
      </div>
    </main>
    <?php
    $scripts = [];
    require __DIR__ . '/../views/footer.php';
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
