<?php

declare(strict_types=1);

namespace App\Heroes\Domain\Event;

use App\Heroes\Domain\Entity\Hero;
use App\Shared\Domain\Bus\DomainEvent;
use DateTimeImmutable;

final class HeroCreated extends DomainEvent
{
    public function __construct(
        ?string $eventId,
        string $aggregateId,
        ?DateTimeImmutable $occurredOn,
        private readonly string $albumId,
        private readonly string $albumName,
        private readonly string $name,
        private readonly string $slug,
        private readonly string $image
    ) {
        parent::__construct($eventId, $aggregateId, $occurredOn);
    }

    public static function eventName(): string
    {
        return 'hero.created';
    }

    /**
     * @return array{heroId: string, albumId: string, albumName: string, name: string, slug: string, image: string, occurredOn: string}
     */
    public function toPrimitives(): array
    {
        return [
            'heroId' => $this->aggregateId(),
            'albumId' => $this->albumId,
            'albumName' => $this->albumName,
            'name' => $this->name,
            'slug' => $this->slug,
            'image' => $this->image,
            'occurredOn' => $this->occurredOn()->format(DATE_ATOM),
        ];
    }

    /**
     * @param array{heroId: string, albumId: string, albumName: string, name: string, slug: string, image: string, occurredOn: string, eventId?: string} $data
     */
    public static function fromPrimitives(array $data): static
    {
        return new self(
            $data['eventId'] ?? null,
            $data['heroId'],
            new DateTimeImmutable($data['occurredOn']),
            $data['albumId'],
            $data['albumName'],
            $data['name'],
            $data['slug'],
            $data['image']
        );
    }

    public static function forHero(Hero $hero, string $albumName): self
    {
        return new self(
            null,
            $hero->heroId(),
            null,
            $hero->albumId(),
            $albumName,
            $hero->name(),
            $hero->slug(),
            $hero->image()
        );
    }

    public function albumId(): string
    {
        return $this->albumId;
    }

    public function albumName(): string
    {
        return $this->albumName;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function image(): string
    {
        return $this->image;
    }
}
