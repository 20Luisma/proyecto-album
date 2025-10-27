<?php

declare(strict_types=1);

namespace App\Albums\Domain\Event;

use App\Albums\Domain\Entity\Album;
use App\Shared\Domain\Bus\DomainEvent;
use DateTimeImmutable;

final class AlbumUpdated extends DomainEvent
{
    public function __construct(
        ?string $eventId,
        string $aggregateId,
        ?DateTimeImmutable $occurredOn,
        private readonly string $nombre,
        private readonly ?string $coverImage
    ) {
        parent::__construct($eventId, $aggregateId, $occurredOn);
    }

    public static function eventName(): string
    {
        return 'album.updated';
    }

    /**
     * @return array{albumId: string, nombre: string, coverImage: ?string, occurredOn: string, eventId: string}
     */
    public function toPrimitives(): array
    {
        return [
            'albumId' => $this->aggregateId(),
            'nombre' => $this->nombre,
            'coverImage' => $this->coverImage,
            'occurredOn' => $this->occurredOn()->format(DATE_ATOM),
            'eventId' => $this->eventId(),
        ];
    }

    /**
     * @param array{albumId: string, nombre: string, coverImage: ?string, occurredOn: string, eventId?: string} $data
     */
    public static function fromPrimitives(array $data): static
    {
        return new self(
            $data['eventId'] ?? null,
            $data['albumId'],
            new DateTimeImmutable($data['occurredOn']),
            $data['nombre'],
            $data['coverImage'] ?? null
        );
    }

    public static function fromAlbum(Album $album): self
    {
        return new self(
            null,
            $album->albumId(),
            null,
            $album->nombre(),
            $album->coverImage()
        );
    }

    public function nombre(): string
    {
        return $this->nombre;
    }

    public function coverImage(): ?string
    {
        return $this->coverImage;
    }
}
