# Session
Php package with session adapters:
* `\ZhukMax\Session\Adapter\File` - Store sessions in plain files

## Install

```console
composer require zhukmax/session
```

## Use

```php
use ZhukMax\Session\Adapter\File;

$session = new File([
        "id" => "my-app"
]);

$session->start();

$session->isStarted(); // true

$session->set("var", "value");

echo $session->get("var"); // value
```

## Licence

The Apache License Version 2.0. Please see [License File](license) for more information.
