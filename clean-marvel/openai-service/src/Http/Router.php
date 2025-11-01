<?php

declare(strict_types=1);

namespace Creawebes\OpenAI\Http;

use Creawebes\OpenAI\Controller\OpenAIController;
use Creawebes\OpenAI\Service\OpenAIChatService;

class Router
{
    public function handle(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        $this->applyCors();

        if (strtoupper($method) === 'OPTIONS') {
            http_response_code(200);
            return;
        }

        if (strtoupper($method) === 'POST' && $path === '/v1/chat') {
            $controller = new OpenAIController(new OpenAIChatService());
            $controller->chat();
            return;
        }

        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Not Found']);
    }

    private function applyCors(): void
    {
        $allowed = $_ENV['ALLOWED_ORIGINS'] ?? getenv('ALLOWED_ORIGINS') ?: '*';

        if ($allowed !== '*') {
            $origins = array_map('trim', explode(',', $allowed));
            $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
            $allowedOrigin = in_array($origin, $origins, true) ? $origin : $origins[0];
        } else {
            $allowedOrigin = '*';
        }

        header('Access-Control-Allow-Origin: ' . $allowedOrigin);
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Max-Age: 86400');
    }
}
