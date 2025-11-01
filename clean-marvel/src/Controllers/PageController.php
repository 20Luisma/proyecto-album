<?php

declare(strict_types=1);

namespace Src\Controllers;

use Src\Controllers\Http\Request;

final class PageController
{
    private const VIEW_MAP = [
        '/' => 'albums',
        '/albums' => 'albums',
        '/heroes' => 'heroes',
        '/comic' => 'comic',
    ];

    public function renderIfHtmlRoute(string $method, string $path): bool
    {
        if ($method !== 'GET') {
            return false;
        }

        $normalizedPath = $path === '' ? '/' : $path;

        if (!array_key_exists($normalizedPath, self::VIEW_MAP)) {
            return false;
        }

        if ($normalizedPath === '/' || Request::wantsHtml()) {
            $this->render(self::VIEW_MAP[$normalizedPath]);
            return true;
        }

        return false;
    }

    public function renderNotFound(): void
    {
        http_response_code(404);
        $pageTitle = '404 — Recurso no encontrado';
        $additionalStyles = [];
        require $this->viewPath('header');
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
        require $this->viewPath('footer');
    }

    private function render(string $view): void
    {
        $viewFile = $this->viewPath($view);
        if (!is_file($viewFile)) {
            http_response_code(500);
            echo 'Vista no encontrada.';
            return;
        }

        require $viewFile;
    }

    private function viewPath(string $view): string
    {
        $root = dirname(__DIR__, 2);

        return $root . '/views/' . $view . '.php';
    }
}
