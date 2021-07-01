# Thesis

## Basic usage

```php
use Thesis\Postgres\PostgresPdoDriver;
use Thesis\Postgres\PostgresDsn;
use Thesis\StatementContext\Tsx;

$driver = new PostgresPdoDriver();
$connection = $driver->connect(
    new PostgresDsn(
        host: 'localhost',
        port: 5432,
        user: 'user',
        password: 'password',
        databaseName: 'application',
    )
);

$userId = 'df2d4e8e-d3d7-442b-9415-28aee4d7ab28';

$connection
    ->execute(
        static fn (Tsx $tsx): string => <<<SQL
            select first_name
            from users
            where user_id = {$tsx($userId)}
            SQL
    )
    ->rowColumn('first_name')
    ->fetch(static fn() => throw new \Exception(sprintf(
        'User with id %s was not found.',
        $userId
    )))
;
```
