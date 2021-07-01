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

    public function execute(string $statement, array $parameters = [], bool $debug = false): ExecutedStatement
    {
        try {
            return $this->statementExecutor->execute($statement, $parameters, $debug);
        } catch (UnresolvedException $exception) {
            throw match ((string) $exception->errorCode) {
                '23505' => new UniqueConstraintViolationException($exception->getMessage(), $exception->errorCode, $exception->getPrevious()),
                default => $exception,
            };
        }
    }
}
