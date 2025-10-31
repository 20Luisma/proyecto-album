<?php

declare(strict_types=1);

namespace App\AI;

use InvalidArgumentException;
use RuntimeException;

final class OpenAIComicGenerator
{
    private const STORY_MODEL = 'gpt-4o-mini';
    private const CHAT_COMPLETIONS_ENDPOINT = 'https://api.openai.com/v1/chat/completions';

    public function __construct(private readonly string $apiKey)
    {
    }

    public function isConfigured(): bool
    {
        return trim($this->apiKey) !== '';
    }

    /**
     * @param array<int, array{heroId: string, nombre: string, contenido: string, imagen: string}> $heroes
     * @return array{
     *   story: array{title: string, summary: string, panels: array<int, array{title: string, description: string, caption: string}>}
     * }
     */
    public function generateComic(array $heroes): array
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('OPENAI_API_KEY no está configurada. Añádela al entorno para habilitar la generación con IA.');
        }

        if ($heroes === []) {
            throw new InvalidArgumentException('Debes proporcionar al menos un héroe para generar el cómic.');
        }

        $story = $this->generateStory($heroes);

        return [
            'story' => [
                'title' => $story['title'] ?? 'Cómic generado con IA',
                'summary' => $story['summary'] ?? '',
                'panels' => $story['panels'] ?? [],
            ],
        ];
    }

    /**
     * @param array<int, array{heroId: string, nombre: string, contenido: string, imagen: string}> $heroes
     * @return array{title: string, summary: string, panels: array<int, array{title: string, description: string, caption: string}>}
     */
    private function generateStory(array $heroes): array
    {
        $heroDescriptions = array_map(
            static fn (array $hero): string => sprintf(
                "- %s: %s",
                $hero['nombre'],
                $hero['contenido'] !== '' ? $hero['contenido'] : 'Sin descripción disponible.'
            ),
            $heroes
        );

        $heroList = implode("\n", $heroDescriptions);

        $payload = [
            'model' => self::STORY_MODEL,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Eres un escritor y director creativo de cómics de Marvel. Generas historias épicas en español latino neutro.',
                ],
                [
                    'role' => 'user',
                    'content' => sprintf(
                        <<<PROMPT
Genera una sinopsis y viñetas para un cómic corto protagonizado por los siguientes héroes:
%s

Instrucciones:
- La historia debe tener un tono heroico y energético.
- Devuelve exactamente 3 viñetas numeradas, cada una con título breve, descripción narrativa y un caption corto con la línea de diálogo principal o onomatopeya.
- Toda la respuesta debe ser un objeto JSON que cumpla con el siguiente esquema:
{
  "title": "string",
  "summary": "string",
  "panels": [
    {
      "title": "string",
      "description": "string",
      "caption": "string"
    }
  ]
}
PROMPT,
                        $heroList
                    ),
                ],
            ],
            'response_format' => [
                'type' => 'json_object',
            ],
        ];

        $response = $this->postJson(self::CHAT_COMPLETIONS_ENDPOINT, $payload);
        
        $content = $response['choices'][0]['message']['content'] ?? '';

        /** @var array{title?: string, summary?: string, panels?: array<int, array{title?: string, description?: string, caption?: string}>} $decoded */
        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('La IA devolvió una estructura inesperada al generar la historia.');
        }

        return [
            'title' => (string) ($decoded['title'] ?? ''),
            'summary' => (string) ($decoded['summary'] ?? ''),
            'panels' => array_map(
                static fn (array $panel): array => [
                    'title' => (string) ($panel['title'] ?? ''),
                    'description' => (string) ($panel['description'] ?? ''),
                    'caption' => (string) ($panel['caption'] ?? ''),
                ],
                array_slice(
                    array_filter(
                        $decoded['panels'] ?? [],
                        static fn ($panel): bool => is_array($panel)
                    ),
                    0,
                    3
                )
            ),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function postJson(string $endpoint, array $payload): array
    {
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($body === false) {
            throw new RuntimeException('No se pudo codificar la petición para la IA.');
        }

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
        ];

        $curl = curl_init($endpoint);
        if ($curl === false) {
            throw new RuntimeException('No se pudo inicializar la petición HTTP.');
        }

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_TIMEOUT => 120,
        ]);

        $response = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($response === false) {
            throw new RuntimeException('Error al conectar con OpenAI: ' . $error);
        }

        /** @var mixed $decoded */
        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Respuesta no válida de OpenAI.');
        }

        if ($statusCode >= 400) {
            $message = $decoded['error']['message'] ?? 'La API de OpenAI devolvió un error.';
            throw new RuntimeException($message);
        }

        return $decoded;
    }
}

