<?php

declare(strict_types=1);

namespace Thesis\Postgres;

use Thesis\Result\ColumnTypeRegistry;
use Thesis\Result\Hydrator;
use Thesis\Result\Result;
use Thesis\StatementContext\Tsx;
use Thesis\StatementContext\ValueResolverRegistry;
use Thesis\StatementExecutor\StatementExecutor;
use Thesis\Transaction\TransactionContext;
use Thesis\Transaction\TransactionIsolationLevels;

/**
 * @psalm-import-type Statement from Tsx
 */
final class PostgresConnection
{
    public function __construct(
        private StatementExecutor $statementExecutor,
        private TransactionContext $transactionContext,
        private Notifier $notifier,
        private ?ValueResolverRegistry $valueResolverRegistry = null,
        private ?Hydrator $hydrator = null,
        private ?ColumnTypeRegistry $columnTypeRegistry = null,
    ) {
    }

    /**
     * @param Statement $statement
     * @throws \Thesis\StatementExecutor\StatementExecutionException
     */
    public function execute(string|\Generator|callable $statement): Result
    {
        $executedStatement = $this->statementExecutor->execute(
            ...Tsx::resolve(
                $statement,
                $this->valueResolverRegistry,
            ),
        );

        return Result::create(
            $executedStatement->rows,
            $executedStatement->affectedRowsNumber,
            $this->hydrator,
            $this->columnTypeRegistry,
        );
    }

    /**
     * @template T of mixed|void
     * @param callable(): T $operation
     * @param ?TransactionIsolationLevels::* $isolationLevel
     * @return T
     */
    public function transactionally(callable $operation, ?string $isolationLevel = null)
    {
        return $this->transactionContext->transactionally($operation, $isolationLevel);
    }

    public function getNotify(int $timeoutMs = 0): ?Notify
    {
        return $this->notifier->getNotify($timeoutMs);
    }
}
