<?php

declare(strict_types=1);

namespace Tests\Application;

use App\Albums\Application\DTO\CreateAlbumRequest;
use App\Dev\Seed\SeedHeroesService;
use App\Heroes\Application\DTO\CreateHeroRequest as CreateHeroRequestDto;
use PHPUnit\Framework\TestCase;
use Tests\Doubles\InMemoryAlbumRepository;
use Tests\Doubles\InMemoryHeroRepository;

class SeedHeroesServiceTest extends TestCase
{
    private InMemoryAlbumRepository $albumRepository;
    private InMemoryHeroRepository $heroRepository;
    private SeedHeroesService $seedService;
    private array $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = require dirname(__DIR__, 2) . '/src/bootstrap.php';

        $this->albumRepository = new InMemoryAlbumRepository();
        $this->heroRepository = new InMemoryHeroRepository();

        $this->container['albumRepository'] = $this->albumRepository;
        $this->container['heroRepository'] = $this->heroRepository;

        $this->seedService = new SeedHeroesService(
            $this->albumRepository,
            $this->heroRepository,
            $this->container['useCases']['createHero']
        );
    }

    private function createAlbum(string $name): void
    {
        $this->container['useCases']['createAlbum']->execute(new CreateAlbumRequest($name, null));
    }

    public function testSeedIfEmptyCreatesHeroesForExistingAlbums(): void
    {
        $this->createAlbum('Avengers');
        $this->createAlbum('X-Men');

        $this->seedService->seedIfEmpty();

        $heroes = $this->heroRepository->findAll();
        $this->assertCount(10, $heroes);
    }

    public function testSeedIfEmptyDoesNothingIfHeroesExist(): void
    {
        $this->createAlbum('Avengers');
        $useCase = $this->container['useCases']['createHero'];
        $albumId = $this->albumRepository->findAll()[0]->getId();
        $useCase->execute(new CreateHeroRequestDto($albumId, 'Some Hero', '', ''));

        $this->seedService->seedIfEmpty();

        $this->assertCount(1, $this->heroRepository->findAll());
    }

    public function testSeedForceAddsMissingHeroesWithoutDuplicating(): void
    {
        $this->createAlbum('Avengers');
        $albumId = $this->albumRepository->findAll()[0]->getId();

        // Pre-seed one hero
        $useCase = $this->container['useCases']['createHero'];
        $useCase->execute(new CreateHeroRequestDto($albumId, 'Iron Man', '', ''));

        $this->assertCount(1, $this->heroRepository->findAll());

        $createdCount = $this->seedService->seedForce();

        $this->assertEquals(4, $createdCount);
        $this->assertCount(5, $this->heroRepository->findAll());
    }
}
