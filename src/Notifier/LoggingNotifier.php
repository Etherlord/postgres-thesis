<?php

declare(strict_types=1);

namespace Thesis\Postgres\Notifier;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Thesis\Postgres\Notifier;
use Thesis\Postgres\Notify;

final class LoggingNotifier implements Notifier
{
    /**
     * @param LogLevel::* $level
     */
    public function __construct(
        private Notifier $notifier,
        private LoggerInterface $logger,
        private string $level = LogLevel::DEBUG,
    ) {
    }

    public function getNotify(int $timeoutMs = 0): ?Notify
    {
        $this->logger->log($this->level, 'Get notify with timeout {timeout_ms} ms.', [
            'timeout_ms' => $timeoutMs,
        ]);

        return $this->notifier->getNotify($timeoutMs);
    }
}
