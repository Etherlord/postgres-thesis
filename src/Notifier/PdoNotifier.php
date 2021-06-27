<?php

declare(strict_types=1);

namespace Thesis\Postgres\Notifier;

use Thesis\Postgres\Notifier;
use Thesis\Postgres\Notify;

final class PdoNotifier implements Notifier
{
    public function __construct(
        private \PDO $pdo,
    ) {
    }

    public function getNotify(int $timeoutMs = 0): ?Notify
    {
        $result = $this->pdo->pgsqlGetNotify(\PDO::FETCH_ASSOC, $timeoutMs);

        if ($result === false) {
            return null;
        }

        return new Notify($result['message'], $result['pid'], $result['payload'] ?? null);
    }
}
