<?php

declare(strict_types=1);

namespace Thesis\Postgres;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Thesis\Postgres\Notifier\LoggingNotifier;
use Thesis\Postgres\Notifier\PdoNotifier;
use Thesis\Result\ColumnTypeRegistry;
use Thesis\Result\Hydrator;
use Thesis\StatementContext\ValueResolverRegistry;
use Thesis\StatementExecutor\LoggingStatementExecutor;
use Thesis\StatementExecutor\PdoStatementExecutor;
use Thesis\StatementExecutor\TimeDebuggingStatementExecutor;
use Thesis\Transaction\SqlTransactionHandler;
use Thesis\Transaction\TransactionContext;

final class PostgresPdoDriver
{
    private LoggerInterface $logger;

    public function __construct(
        ?LoggerInterface $logger = null,
        private ?ValueResolverRegistry $valueResolverRegistry = null,
        private ?Hydrator $hydrator = null,
        private ?ColumnTypeRegistry $columnTypeRegistry = null,
        private array $options = [],
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * @psalm-pure
     */
    public static function generatePdoDsn(PostgresDsn $dsn): string
    {
        $pdoDsn = 'pgsql:';

        if ($dsn->host !== null) {
            $pdoDsn .= 'host='.$dsn->host.';';
        }

        if ($dsn->port !== null) {
            $pdoDsn .= 'port='.$dsn->port.';';
        }

        if ($dsn->user !== null) {
            $pdoDsn .= 'user='.$dsn->user.';';
        }

        if ($dsn->password !== null) {
            $pdoDsn .= 'password='.$dsn->password.';';
        }

        if ($dsn->databaseName !== null) {
            $pdoDsn .= 'dbname='.$dsn->databaseName.';';
        }

        foreach ($dsn->parameters as $parameter => $value) {
            $pdoDsn .= $parameter.'='.$value.';';
        }

        return $pdoDsn;
    }

    public function connect(PostgresDsn $dsn): PostgresConnection
    {
        $pdo = new \PDO(self::generatePdoDsn($dsn), options: $this->options);

        $statementExecutor = new LoggingStatementExecutor(
            new TimeDebuggingStatementExecutor(
                new PostgresErrorResolvingStatementExecutor(
                    new PdoStatementExecutor($pdo),
                ),
            ),
            $this->logger,
        );

        return new PostgresConnection(
            $statementExecutor,
            new TransactionContext(new SqlTransactionHandler([$statementExecutor, 'execute'])),
            new LoggingNotifier(new PdoNotifier($pdo), $this->logger),
            $this->valueResolverRegistry,
            $this->hydrator,
            $this->columnTypeRegistry,
        );
    }
}
