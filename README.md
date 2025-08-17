# Light PHP Framework

This is a very lightweight PHP framework aimed at maximum performance and resource optimization.

```php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

(new \app\lib\web\App(
    di: new Psa\Base\Container(require_once __DIR__ . '/config/web.php')
)->run();

```
