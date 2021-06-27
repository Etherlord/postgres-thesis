<?php

declare(strict_types=1);

namespace Thesis\Postgres;

/**
 * @psalm-immutable
 */
final class PostgresDsn
{
    /**
     * @param array<string, string> $parameters
     */
    public function __construct(
        public ?string $host = null,
        public ?int $port = null,
        public ?string $user = null,
        public ?string $password = null,
        public ?string $databaseName = null,
        public array $parameters = [],
    ) {
    }
}
