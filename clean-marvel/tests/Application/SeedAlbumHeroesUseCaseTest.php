<?php

declare(strict_types=1);

namespace Tests\Application;

use App\Albums\Domain\Entity\Album;
use App\Heroes\Application\UseCase\SeedAlbumHeroesUseCase;
use App\Shared\Infrastructure\Bus\InMemoryEventBus;
use PHPUnit\Framework\TestCase;
use Tests\Doubles\InMemoryAlbumRepository;
use Tests\Doubles\InMemoryHeroRepository;

final class SeedAlbumHeroesUseCaseTest extends TestCase
{
    public function testItSeedsFiveHeroesForAlbum(): void
    {
        $albumRepository = new InMemoryAlbumRepository();
        $heroRepository = new InMemoryHeroRepository();
        $eventBus = new InMemoryEventBus();

        $albumRepository->seed(Album::create('album-1', 'Marvel Studios'));

        $useCase = new SeedAlbumHeroesUseCase($albumRepository, $heroRepository, $eventBus);
        $heroes = $useCase->execute('album-1');

        self::assertCount(5, $heroes);
        self::assertCount(5, $heroRepository->byAlbum('album-1'));
        self::assertSame('album-1', $heroes[0]['albumId']);
    }

    public function testItFailsWhenAlbumDoesNotExist(): void
    {
        $albumRepository = new InMemoryAlbumRepository();
        $heroRepository = new InMemoryHeroRepository();
        $eventBus = new InMemoryEventBus();

        $useCase = new SeedAlbumHeroesUseCase($albumRepository, $heroRepository, $eventBus);

        $this->expectExceptionMessage('Álbum no encontrado.');
        $useCase->execute('missing');
    }
}
