<?php

declare(strict_types=1);

namespace Thesis\Postgres\ValueResolver;

use Thesis\StatementContext\ValueRecursiveResolver;
use Thesis\StatementContext\ValueResolver;
use Thesis\StatementContext\ValueResolver\Json;

/**
 * @implements ValueResolver<PgJson>
 */
final class PgJsonResolver implements ValueResolver
{
    public static function valueTypes(): array
    {
        return [PgJson::class];
    }

    /**
     * @param PgJson $value
     */
    public function resolve(mixed $value, ValueRecursiveResolver $resolver): string
    {
        return $resolver->resolve(new Json($value->value, $value->forceObject)).'::json';
    }
}
