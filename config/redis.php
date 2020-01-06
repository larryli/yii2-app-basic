<?php

return [
    'class' => yii\redis\Connection::class,
    'hostname' => getenv('REDIS_HOST') ?: 'localhost',
    'port' => 6379,
    'retries' => 1,
    'database' => getenv('REDIS_DB') ?: 0,
    'password' => getenv('REDIS_PASSWORD') ?: null,
];
