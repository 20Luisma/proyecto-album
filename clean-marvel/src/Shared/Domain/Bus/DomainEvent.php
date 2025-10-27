<?php

declare(strict_types=1);

namespace App\Shared\Domain\Bus;

use DateTimeImmutable;
use RuntimeException;

abstract class DomainEvent
{
    private string $eventId;
    private string $aggregateId;
    private DateTimeImmutable $occurredOn;

    public function __construct(?string $eventId, string $aggregateId, ?DateTimeImmutable $occurredOn)
    {
        $this->eventId = $eventId ?? self::generateUuid();
        $this->aggregateId = $aggregateId;
        $this->occurredOn = $occurredOn ?? new DateTimeImmutable();
    }

    public function eventId(): string
    {
        return $this->eventId;
    }

    public function aggregateId(): string
    {
        return $this->aggregateId;
    }

    public function occurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }

    abstract public static function eventName(): string;

    /**
     * @return array<string, mixed>
     */
    abstract public function toPrimitives(): array;

    private static function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        $segments = [
            bin2hex(substr($data, 0, 4)),
            bin2hex(substr($data, 4, 2)),
            bin2hex(substr($data, 6, 2)),
            bin2hex(substr($data, 8, 2)),
            bin2hex(substr($data, 10, 6)),
        ];

        return implode('-', $segments);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromPrimitives(array $data): static
    {
        throw new RuntimeException('fromPrimitives must be implemented in the concrete event.');
    }
}
