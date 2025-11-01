<?php

declare(strict_types=1);

namespace Creawebes\OpenAI\Controller;

use Creawebes\OpenAI\Service\OpenAIClient;

class OpenAIController
{
    public function chat(): void
    {
        header('Content-Type: application/json');

        $payload = file_get_contents('php://input') ?: '';
        $data = json_decode($payload, true);

        if (!is_array($data)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON payload']);
            return;
        }

        $messages = $data['messages'] ?? null;

        if (!is_array($messages)) {
            http_response_code(400);
            echo json_encode(['error' => 'Messages must be provided as an array']);
            return;
        }

        $model = isset($data['model']) ? (string) $data['model'] : null;

        try {
            $client = new OpenAIClient();
            $result = $client->chat($messages, $model);

            echo json_encode($result);
        } catch (\Throwable $exception) {
            http_response_code(500);
            echo json_encode(['error' => $exception->getMessage()]);
        }
    }
}
