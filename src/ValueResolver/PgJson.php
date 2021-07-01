<?php

declare(strict_types=1);

namespace Thesis\Postgres\ValueResolver;

use Thesis\StatementContext\ValueResolver\Json;

final class PgJson
{
    /**
     * @param Json::* $forceObject
     */
    public function __construct(
        public mixed $value,
        public int $forceObject = Json::NEVER,
    ) {
    }
}
