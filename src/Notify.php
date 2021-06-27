<?php

declare(strict_types=1);

namespace Thesis\Postgres;

/**
 * @psalm-immutable
 */
final class Notify
{
    public function __construct(
        public string $message,
        public int $pid,
        public ?string $payload,
    ) {
    }
}
