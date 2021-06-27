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
use Thesis\Transaction\TransactionIsolationLevel;

/**
 * @psalm-import-type Statement from Tsx
 */
final class PostgresConnection
{
    /**
     * @param callable(int): ?Notify $getNotify
     */
    public function __construct(
        private StatementExecutor $statementExecutor,
        private TransactionContext $transactionContext,
        private $getNotify,
        private ?ValueResolverRegistry $valueResolverRegistry = null,
        private ?Hydrator $hydrator = null,
        private ?ColumnTypeRegistry $columnTypeRegistry = null,
    ) {
    }

    /**
     * @param Statement $statement
     */
    public function execute(string|iterable|callable $statement): Result
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
            $executedStatement->debugData,
            $this->hydrator,
            $this->columnTypeRegistry,
        );
    }

    /**
     * @template T of mixed|void
     * @param callable(): T $operation
     * @return T
     */
    public function transactionally(callable $operation, ?TransactionIsolationLevel $isolationLevel = null)
    {
        return $this->transactionContext->transactionally($operation, $isolationLevel);
    }

    public function getNotify(int $msTimeout = 0): ?Notify
    {
        return ($this->getNotify)($msTimeout);
    }
}
