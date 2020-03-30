<?php

require_once './vendor/autoload.php';

define('ROOT_PATH', __DIR__);

$app = require_once __DIR__ . '/app/bootstrap.php';

$app->start();

