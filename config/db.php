<?php

$config =  [
    'class' => yii\db\Connection::class,
    'dsn' => 'mysql:host=' . (getenv('MYSQL_HOST') ?: 'localhost') . ';dbname=' . getenv('MYSQL_DB'),
    'username' => getenv('MYSQL_USER') ?: 'root',
    'password' => getenv('MYSQL_PASSWORD') ?: '',
    'charset' => 'utf8',
];

if (getenv('ENABLE_SCHEMA_CACHE') === 'true') {
    // Schema cache options (for production environment)
    $config['enableSchemaCache'] = true;
    $config['schemaCacheDuration'] = getenv('SCHEMA_CACHE_DURATION') ?: 60;
    $config['schemaCache'] = getenv('SCHEMA_CACHE') ?: 'cache';
}

return $config;
