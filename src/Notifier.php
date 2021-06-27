<?php

declare(strict_types=1);

namespace Thesis\Postgres;

interface Notifier
{
    public function getNotify(int $timeoutMs = 0): ?Notify;
}
