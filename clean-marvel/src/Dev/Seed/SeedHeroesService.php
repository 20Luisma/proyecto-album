<?php

declare(strict_types=1);

namespace App\Dev\Seed;

use App\Albums\Domain\Repository\AlbumRepository;
use App\Heroes\Application\DTO\CreateHeroRequest;
use App\Heroes\Application\UseCase\CreateHeroUseCase;
use App\Heroes\Domain\Repository\HeroRepository;
use App\Shared\Util\Slugger;

class SeedHeroesService
{
    private const HEROES_DATA = [
        'Avengers' => [
            ['Iron Man', 'https://via.placeholder.com/800x500?text=Iron+Man', 'Armadura de alta tecnología, liderazgo y humor.'],
            ['Captain America', 'https://via.placeholder.com/800x500?text=Captain+America', 'Súper soldado con escudo indestructible.'],
            ['Thor', 'https://via.placeholder.com/800x500?text=Thor', 'Dios del trueno con Mjölnir.'],
            ['Black Widow', 'https://via.placeholder.com/800x500?text=Black+Widow', 'Espía experta y estratega.'],
            ['Hulk', 'https://via.placeholder.com/800x500?text=Hulk', 'Fuerza descomunal y gran corazón.'],
        ],
        'X-Men' => [
            ['Wolverine', 'https://via.placeholder.com/800x500?text=Wolverine', 'Garras de adamantium y regeneración.'],
            ['Cyclops', 'https://via.placeholder.com/800x500?text=Cyclops', 'Rayo óptico y liderazgo.'],
            ['Storm', 'https://via.placeholder.com/800x500?text=Storm', 'Control del clima con elegancia.'],
            ['Jean Grey', 'https://via.placeholder.com/800x500?text=Jean+Grey', 'Poder telepático y telequinesis.'],
            ['Beast', 'https://via.placeholder.com/800x500?text=Beast', 'Genio científico y acrobacia.'],
        ],
        'Guardians of the Galaxy' => [
            ['Star-Lord', 'https://via.placeholder.com/800x500?text=Star-Lord', 'Aventurero espacial con música.'],
            ['Gamora', 'https://via.placeholder.com/800x500?text=Gamora', 'Guerrera letal con honor.'],
            ['Drax', 'https://via.placeholder.com/800x500?text=Drax', 'Literal y poderoso.'],
            ['Rocket Raccoon', 'https://via.placeholder.com/800x500?text=Rocket', 'Ingeniero y piloto genial.'],
            ['Groot', 'https://via.placeholder.com/800x500?text=Groot', '‘Yo soy Groot’ y mucha voluntad.'],
        ],
    ];

    public function __construct(
        private readonly AlbumRepository $albumRepository,
        private readonly HeroRepository $heroRepository,
        private readonly CreateHeroUseCase $createHeroUseCase
    ) {
    }

    public function seedIfEmpty(): void
    {
        if (count($this->heroRepository->findAll()) > 0) {
            return;
        }

        $this->seed(false);
    }

    public function seedForce(): int
    {
        return $this->seed(true);
    }

    private function seed(bool $force): int
    {
        $createdCount = 0;
        $albums = $this->albumRepository->findAll();
        $existingHeroesSlugsByAlbum = [];
        if ($force) {
            $allHeroes = $this->heroRepository->findAll();
            foreach ($allHeroes as $hero) {
                $existingHeroesSlugsByAlbum[$hero->getAlbumId()][] = Slugger::slugify($hero->getName());
            }
        }

        foreach ($albums as $album) {
            $albumName = $album->getName();
            $heroDataForAlbum = null;

            foreach (self::HEROES_DATA as $key => $data) {
                if (strcasecmp($key, $albumName) === 0) {
                    $heroDataForAlbum = $data;
                    break;
                }
            }

            if ($heroDataForAlbum === null) {
                continue;
            }

            foreach ($heroDataForAlbum as $heroData) {
                [$name, $image, $content] = $heroData;
                $slug = Slugger::slugify($name);

                if ($force && isset($existingHeroesSlugsByAlbum[$album->getId()]) && in_array($slug, $existingHeroesSlugsByAlbum[$album->getId()])) {
                    continue;
                }

                $this->createHeroUseCase->execute(
                    new CreateHeroRequest($album->getId(), $name, $content, $image)
                );
                $createdCount++;
            }
        }
        return $createdCount;
    }
}
