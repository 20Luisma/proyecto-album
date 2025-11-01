<?php

declare(strict_types=1);

namespace Src\Shared\Http;

use App\AI\OpenAIComicGenerator;
use App\Dev\Seed\SeedHeroesService;
use App\Dev\Test\PhpUnitTestRunner;
use App\Shared\Http\JsonResponse;
use Src\Controllers\AdminController;
use Src\Controllers\AlbumController;
use Src\Controllers\ComicController;
use Src\Controllers\DevController;
use Src\Controllers\HeroController;
use Src\Controllers\Http\Request;
use Src\Controllers\NotificationController;
use Src\Controllers\PageController;
use Throwable;

final class Router
{
    /**
     * @param array<string, mixed> $container
     */
    public function __construct(private readonly array $container)
    {
    }

    public function handle(string $method, string $path): void
    {
        $pageController = $this->pageController();

        if ($pageController->renderIfHtmlRoute($method, $path)) {
            return;
        }

        if ($method === 'GET' && Request::wantsHtml()) {
            $pageController->renderNotFound();
            return;
        }

        try {
            if (!$this->dispatch($method, $path)) {
                JsonResponse::error('Endpoint no encontrado.', 404);
            }
        } catch (Throwable $exception) {
            JsonResponse::error('Error inesperado: ' . $exception->getMessage(), 500);
        }
    }

    private function dispatch(string $method, string $path): bool
    {
        return match ($method) {
            'GET' => $this->handleGet($path),
            'POST' => $this->handlePost($path),
            'PUT' => $this->handlePut($path),
            'DELETE' => $this->handleDelete($path),
            default => $this->methodNotAllowed(),
        };
    }

    private function handleGet(string $path): bool
    {
        if ($path === '/albums') {
            $this->albumController()->index();
            return true;
        }

        if ($path === '/heroes') {
            $this->heroController()->index();
            return true;
        }

        if (preg_match('#^/albums/([A-Za-z0-9\-]+)/heroes$#', $path, $matches) === 1) {
            $this->heroController()->listByAlbum($matches[1]);
            return true;
        }

        if ($path === '/notifications') {
            $this->notificationController()->index();
            return true;
        }

        if (preg_match('#^/heroes/([A-Za-z0-9\-]+)$#', $path, $matches) === 1) {
            $this->heroController()->show($matches[1]);
            return true;
        }

        return false;
    }

    private function handlePost(string $path): bool
    {
        if ($path === '/dev/tests/run') {
            $this->devController()->runTests();
            return true;
        }

        if (preg_match('#^/albums/([A-Za-z0-9\-]+)/cover$#', $path, $matches) === 1) {
            $this->albumController()->uploadCover($matches[1]);
            return true;
        }

        if ($path === '/admin/seed-all') {
            $adminController = $this->adminController();
            if ($adminController === null) {
                JsonResponse::error('Servicio de seed no disponible.', 500);
                return true;
            }

            $adminController->seedAll();
            return true;
        }

        if ($path === '/comics/generate') {
            $this->comicController()->generate();
            return true;
        }

        if ($path === '/albums') {
            $this->albumController()->store();
            return true;
        }

        if (preg_match('#^/albums/([A-Za-z0-9\-]+)/heroes$#', $path, $matches) === 1) {
            $this->heroController()->store($matches[1]);
            return true;
        }

        return false;
    }

    private function handlePut(string $path): bool
    {
        if (preg_match('#^/albums/([A-Za-z0-9\-]+)$#', $path, $matches) === 1) {
            $this->albumController()->update($matches[1]);
            return true;
        }

        if (preg_match('#^/heroes/([A-Za-z0-9\-]+)$#', $path, $matches) === 1) {
            $this->heroController()->update($matches[1]);
            return true;
        }

        return false;
    }

    private function handleDelete(string $path): bool
    {
        if (preg_match('#^/albums/([A-Za-z0-9\-]+)$#', $path, $matches) === 1) {
            $this->albumController()->destroy($matches[1]);
            return true;
        }

        if ($path === '/notifications') {
            $this->notificationController()->clear();
            return true;
        }

        if (preg_match('#^/heroes/([A-Za-z0-9\-]+)$#', $path, $matches) === 1) {
            $this->heroController()->destroy($matches[1]);
            return true;
        }

        return false;
    }

    private function methodNotAllowed(): bool
    {
        JsonResponse::error('Método no permitido.', 405);

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    private function useCases(): array
    {
        /** @var array<string, mixed> $useCases */
        $useCases = $this->container['useCases'] ?? [];

        return $useCases;
    }

    private ?AlbumController $albumController = null;

    private function albumController(): AlbumController
    {
        if ($this->albumController === null) {
            $useCases = $this->useCases();

            $this->albumController = new AlbumController(
                $useCases['listAlbums'],
                $useCases['createAlbum'],
                $useCases['updateAlbum'],
                $useCases['deleteAlbum'],
                $useCases['findAlbum'],
            );
        }

        return $this->albumController;
    }

    private ?HeroController $heroController = null;

    private function heroController(): HeroController
    {
        if ($this->heroController === null) {
            $useCases = $this->useCases();

            $this->heroController = new HeroController(
                $useCases['listHeroes'],
                $useCases['createHero'],
                $useCases['updateHero'],
                $useCases['deleteHero'],
                $useCases['findHero'],
            );
        }

        return $this->heroController;
    }

    private ?NotificationController $notificationController = null;

    private function notificationController(): NotificationController
    {
        if ($this->notificationController === null) {
            $useCases = $this->useCases();

            $this->notificationController = new NotificationController(
                $useCases['listNotifications'],
                $useCases['clearNotifications'],
            );
        }

        return $this->notificationController;
    }

    private ?ComicController $comicController = null;

    private function comicController(): ComicController
    {
        if ($this->comicController === null) {
            $useCases = $this->useCases();

            $generator = $this->container['ai']['comicGenerator'] ?? null;
            if (!$generator instanceof OpenAIComicGenerator) {
                $generator = new OpenAIComicGenerator();
            }

            $this->comicController = new ComicController($generator, $useCases['findHero']);
        }

        return $this->comicController;
    }

    private ?DevController $devController = null;

    private function devController(): DevController
    {
        if ($this->devController === null) {
            $testRunner = $this->container['devTools']['testRunner'] ?? null;
            if (!$testRunner instanceof PhpUnitTestRunner) {
                $testRunner = PhpUnitTestRunner::fromEnvironment(dirname(__DIR__, 3));
            }

            $this->devController = new DevController($testRunner);
        }

        return $this->devController;
    }

    private ?AdminController $adminController = null;
    private bool $adminControllerInitialized = false;

    private function adminController(): ?AdminController
    {
        if (!$this->adminControllerInitialized) {
            $seedService = $this->container['seedHeroesService'] ?? null;
            if ($seedService instanceof SeedHeroesService) {
                $this->adminController = new AdminController($seedService);
            }
            $this->adminControllerInitialized = true;
        }

        return $this->adminController;
    }

    private ?PageController $pageController = null;

    private function pageController(): PageController
    {
        if ($this->pageController === null) {
            $this->pageController = new PageController();
        }

        return $this->pageController;
    }
}
