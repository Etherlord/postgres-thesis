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
    private const DEFAULT_CURSOR_LIMIT = 1000;

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
     * @return Result<int, array>
     * @throws \Thesis\StatementExecutor\StatementExecutionException
     */
    public function execute(string|\Generator|callable $statement): Result
    {
        [$resolvedStatement, $parameters] = Tsx::resolve($statement, $this->valueResolverRegistry);
        $executedStatement = $this->statementExecutor->execute($resolvedStatement, $parameters);

        return Result::create(
            $executedStatement->rows,
            $executedStatement->affectedRowsNumber,
            $this->hydrator,
            $this->columnTypeRegistry,
        );
    }

    /**
     * @param Statement $statement
     * @return Result<int, array>
     * @throws \Thesis\StatementExecutor\StatementExecutionException
     */
    public function cursor(string|\Generator|callable $statement, int $limit = self::DEFAULT_CURSOR_LIMIT): Result
    {
        $cursorName = uniqid('thesis_cursor_');
        $this->execute(static fn (Tsx $tsx): string => "declare {$cursorName} cursor for {$tsx->embed($statement)}");
        $fetchStatement = "fetch {$limit} from {$cursorName}";

        return Result::create(
            (function () use ($limit, $fetchStatement): \Generator {
                do {
                    $i = 0;
                    $rows = $this
                        ->statementExecutor
                        ->execute($fetchStatement)
                        ->rows
                    ;

                    foreach ($rows as $row) {
                        yield $row;
                        ++$i;
                    }
                } while ($i >= $limit);
            })(),
            0,
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
