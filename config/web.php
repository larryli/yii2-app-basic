<?php

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
    ],
    'components' => [
        'cache' => require __DIR__ . '/cache.php',
        'db' => require __DIR__ . '/db.php',
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'mailer' => [
            'class' => yii\swiftmailer\Mailer::class,
            'useFileTransport' => getenv('SMTP_ENABLED') !== 'true',
        ],
        'nchan' => require __DIR__ . '/nchan.php',
        'queue' => require __DIR__ . '/queue.php',
        'redis' => require __DIR__ . '/redis.php',
        'request' => [
            'cookieValidationKey' => getenv('COOKIE_VALIDATION_KEY'),
            'trustedHosts' => ['10.1.0.0/16'], // k8s network
        ],
        'session' => [
            'class' => yii\web\DbSession::class,
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                '' => 'site/index',
                '<controller:[\w-]+>/<id:\d+>' => '<controller>/view',
                '<controller:[\w-]+>/<id:\d+>/<action:[\w-]+>' => '<controller>/<action>',
                '<controller:[\w-]+>' => '<controller>/index',
            ],
        ],
        'user' => [
            'identityClass' => app\models\User::class,
            'enableAutoLogin' => true,
        ],
    ],
    'params' => require __DIR__ . '/params.php',
];

if (file_exists(__DIR__ . '/asset-bundles.php')) {
    /** @noinspection PhpIncludeInspection */
    $config['components']['assetManager']['bundles'] = require __DIR__ . '/asset-bundles.php';
}

if (getenv('SMTP_ENABLED') === 'true') {
    $config['components']['mailer']['transport'] = [
        'class' => Swift_SmtpTransport::class,
        'host' => getenv('SMTP_HOST') ?: 'localhost',
        'username' => getenv('SMTP_USER') ?: '',
        'password' => getenv('SMTP_PASSWORD') ?: '',
        'port' => getenv('SMTP_PORT') ?: 25,
        'encryption' => (getenv('SMTP_TLS') === 'true') ? 'tls' : '',
    ];
}

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => yii\debug\Module::class,
        // uncomment the following to add your IP if you are not connecting from localhost.
        'allowedIPs' => [getenv('DEBUG_IP') ?: '127.0.0.1', '::1'],
        'panels' => [
            'queue' => yii\queue\debug\Panel::class,
        ],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => yii\gii\Module::class,
        // uncomment the following to add your IP if you are not connecting from localhost.
        'allowedIPs' => ['127.0.0.1', '::1'],
        'generators' => [
            'job' => [
                'class' => yii\queue\gii\Generator::class,
            ],
        ],
    ];
}

return $config;
