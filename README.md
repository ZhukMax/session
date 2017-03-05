# Session
Php package with session adapters:
* `\ZhukMax\Session\File` - Store session in plain files
* `\ZhukMax\Session\Redis` - Store session in Redis
* `\ZhukMax\Session\Sql` - Store session in Sql Data Base like Mysql etc.

## Install

```console
composer require zhukmax/session
```

## Use

#### Simple use:
```php
<?php

use ZhukMax\Session\File as Session;

$session = new Session([
        "id" => "my-app"
]);

$session->start();

$session->isStarted(); // true

$session->set("var", "value");

echo $session->get("var"); // value
```

#### PDO-store (Mysql, PostgreSql, Sqlite, ODBC) use:
```sql
CREATE TABLE `sessions` (
  `id` VARCHAR(35) NOT NULL,
  `data` text NOT NULL,
  `created_at` INT unsigned NOT NULL,
  `modified_at` INT unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
);
```
```php
<?php

use ZhukMax\Session\Sql as Session;

$session = new Session([
    'dsn'      => 'mysql:dbname=testdb;host=127.0.0.1',
    'user'     => 'username',
    'password' => 'simple-pass',
    'table'    => 'sessions',
    'column'   => ['id' => 'id'],
    'id'       => 'my-app'
]);

$session->start();

$session->set("var", "value");

echo $session->get("var"); // value
```

## Licence

The Apache License Version 2.0. Please see [License File](license) for more information.
