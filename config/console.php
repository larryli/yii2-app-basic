<?php

$config = [
    'id' => 'basic-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log', 'queue'],
    'controllerNamespace' => 'app\commands',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
        '@tests' => '@app/tests',
    ],
    'components' => [
        'cache' => require __DIR__ . '/cache.php',
        'db' => require __DIR__ . '/db.php',
        'log' => [
            'targets' => [
                [
                    'class' => yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'nchan' => require __DIR__ . '/nchan.php',
        'queue' => require __DIR__ . '/queue.php',
        'redis' => require __DIR__ . '/redis.php',
    ],
    'params' => require __DIR__ . '/params.php',
    'controllerMap' => [
        'migrate' => [
            'class' => yii\console\controllers\MigrateController::class,
            'migrationPath' => [
                '@app/migrations',
                '@yii/web/migrations',
            ],
        ],
    ],
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => yii\gii\Module::class,
    ];
}

return $config;
