<?php

declare(strict_types=1);

namespace Tests\Application;

use App\Albums\Application\DTO\CreateAlbumRequest;
use PHPUnit\Framework\TestCase;
use Tests\Doubles\InMemoryAlbumRepository;
use Tests\Doubles\InMemoryHeroRepository;

class CreateHeroUseCaseHttpTest extends TestCase
{
    private array $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = require dirname(__DIR__, 2) . '/src/bootstrap.php';
        // Overwrite with in-memory repositories for tests
        $this->container['albumRepository'] = new InMemoryAlbumRepository();
        $this->container['heroRepository'] = new InMemoryHeroRepository();
        $this->container['notificationRepository'] = new \App\Notifications\Infrastructure\NotificationRepository(dirname(__DIR__, 2) . '/storage/test_notifications.log');
        $this->container['eventBus'] = new \App\Shared\Infrastructure\Bus\InMemoryEventBus();
    }

    private function createAlbum(string $name): string
    {
        $useCase = $this->container['useCases']['createAlbum'];
        $response = $useCase->execute(new CreateAlbumRequest($name, null));
        return $response->id;
    }

    public function testCreateHeroSuccessfully(): void
    {
        $albumId = $this->createAlbum('Test Album');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = "/albums/$albumId/heroes";
        $GLOBALS['mock_php_input'] = json_encode(['nombre' => 'Test Hero', 'imagen' => 'test.jpg', 'contenido' => 'Test content']);

        ob_start();
        route('POST', "/albums/$albumId/heroes", $this->container);
        $output = ob_get_clean();
        $response = json_decode($output, true);

        $this->assertEquals(201, http_response_code());
        $this->assertEquals('éxito', $response['estado']);
        $this->assertEquals('Test Hero', $response['datos']['nombre']);
    }

    public function testCreateHeroFailsWithMissingData(): void
    {
        $albumId = $this->createAlbum('Test Album');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = "/albums/$albumId/heroes";
        $GLOBALS['mock_php_input'] = json_encode(['nombre' => 'Test Hero']); // Missing imagen

        ob_start();
        route('POST', "/albums/$albumId/heroes", $this->container);
        $output = ob_get_clean();
        $response = json_decode($output, true);

        $this->assertEquals(422, http_response_code());
        $this->assertEquals('error', $response['estado']);
        $this->assertEquals('Los campos nombre e imagen son obligatorios.', $response['message']);
    }
}
