<?php

declare(strict_types=1);

namespace Thesis\Postgres\ValueResolver;

use Thesis\StatementContext\ValueRecursiveResolver;
use Thesis\StatementContext\ValueResolver;
use Thesis\StatementContext\ValueResolver\Json;

/**
 * @implements ValueResolver<PgJsonb>
 */
final class PgJsonbResolver implements ValueResolver
{
    public static function valueTypes(): array
    {
        return [PgJsonb::class];
    }

    /**
     * @param PgJsonb $value
     */
    public function resolve(mixed $value, ValueRecursiveResolver $resolver): string
    {
        return $resolver->resolve(new Json($value->value, $value->forceObject)).'::jsonb';
    }
}
