<?php

declare(strict_types=1);

namespace App\Albums\Domain\Event;

use App\Albums\Domain\Entity\Album;
use App\Shared\Domain\Bus\DomainEvent;

final class AlbumCreated extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        private readonly string $name,
        string $eventId = null,
        string $occurredOn = null
    ) {
        parent::__construct($aggregateId, $eventId, $occurredOn);
    }

    public static function eventName(): string
    {
        return 'album.created';
    }

    public function toPrimitives(): array
    {
        return [
            'name' => $this->name,
        ];
    }

    public static function fromPrimitives(
        string $aggregateId,
        array $body,
        string $eventId,
        string $occurredOn
    ): DomainEvent {
        return new self($aggregateId, $body['name'], $eventId, $occurredOn);
    }

    public function name(): string
    {
        return $this->name;
    }

    public static function forAlbum(Album $album): self
    {
        return new self($album->albumId(), $album->nombre());
    }
}
