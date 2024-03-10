<?php

declare(strict_types=1);

namespace Thesis\Postgres;

use Thesis\StatementExecutor\Exception\UniqueConstraintViolationException;
use Thesis\StatementExecutor\Exception\UnresolvedException;
use Thesis\StatementExecutor\ExecutedStatement;
use Thesis\StatementExecutor\StatementExecutor;

final class PostgresErrorResolvingStatementExecutor implements StatementExecutor
{
    public function __construct(
        private StatementExecutor $statementExecutor,
    ) {
    }

    public function execute(string $statement, array $parameters = []): ExecutedStatement
    {
        try {
            return $this->statementExecutor->execute($statement, $parameters);
        } catch (UnresolvedException $exception) {
            throw match ((string) $exception->errorCode) {
                '23505' => new UniqueConstraintViolationException($exception->getMessage(), $exception->errorCode, $exception->getPrevious()),
                default => $exception,
            };
        }
    }
}
