<?php

declare(strict_types=1);

namespace Thesis\Postgres;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Thesis\Pdo\PdoStatementExecutor;
use Thesis\Result\ColumnTypeRegistry;
use Thesis\Result\Hydrator;
use Thesis\StatementContext\ValueResolverRegistry;
use Thesis\StatementExecutor\LoggingStatementExecutor;
use Thesis\Transaction\LoggingTransactionHandler;
use Thesis\Transaction\SqlTransactionHandler;
use Thesis\Transaction\TransactionContext;

final class PostgresDriver
{
    private LoggerInterface $logger;

    public function __construct(
        private ?Hydrator $hydrator = null,
        ?LoggerInterface $logger = null,
        private ?ColumnTypeRegistry $columnTypeRegistry = null,
        private ?ValueResolverRegistry $valueResolverRegistry = null,
        private array $options = [],
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * @psalm-pure
     * @internal
     * @psalm-internal Thesis\Postgres
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
        $statementExecutor = new LoggingStatementExecutor(new PdoStatementExecutor($pdo), $this->logger);

        return new PostgresConnection(
            $statementExecutor,
            new TransactionContext(
                new LoggingTransactionHandler(
                    new SqlTransactionHandler($statementExecutor),
                    $this->logger,
                ),
            ),
            static function (int $msTimeout) use ($pdo): ?Notify {
                $result = $pdo->pgsqlGetNotify(\PDO::FETCH_ASSOC, $msTimeout);

                if ($result === false) {
                    return null;
                }

                return new Notify($result['message'], $result['pid'], $result['payload'] ?? null);
            },
            $this->valueResolverRegistry,
            $this->hydrator,
            $this->columnTypeRegistry,
        );
    }
}
