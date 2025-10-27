<?php

declare(strict_types=1);

namespace App\Heroes\Application\UseCase;

use App\Heroes\Application\DTO\HeroResponse;
use App\Heroes\Domain\Repository\HeroRepository;
use InvalidArgumentException;

final class FindHeroUseCase
{
    public function __construct(private readonly HeroRepository $repository)
    {
    }

    public function execute(string $heroId): array
    {
        $hero = $this->repository->find($heroId);

        if ($hero === null) {
            throw new InvalidArgumentException('Héroe no encontrado.');
        }

        return HeroResponse::fromHero($hero)->toArray();
    }
}
