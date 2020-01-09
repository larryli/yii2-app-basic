<?php
/**
 * Configuration file for the "yii asset" console command.
 */

// In the console environment, some path aliases may not exist. Please define these:
Yii::setAlias('@webroot', __DIR__ . '/../web');
Yii::setAlias('@web', '/');

return [
    // Adjust command/callback for JavaScript files compressing:
    'jsCompressor' => 'php -d display_errors=0 -d error_reporting=0 $COMPOSER_HOME/vendor/bin/minifyjs {from} > {to}',
    // Adjust command/callback for CSS files compressing:
    'cssCompressor' => 'php -d display_errors=0 -d error_reporting=0 $COMPOSER_HOME/vendor/bin/minifycss {from} > {to}',
    // Whether to delete asset source after compression:
    'deleteSource' => false,
    // The list of asset bundles to compress:
    'bundles' => [
        app\assets\AppAsset::class,
        app\assets\NotifyAsset::class,
        yii\bootstrap\BootstrapAsset::class,
        yii\bootstrap\BootstrapPluginAsset::class,
        yii\captcha\CaptchaAsset::class,
        yii\grid\GridViewAsset::class,
        yii\validators\ValidationAsset::class,
        yii\widgets\ActiveFormAsset::class,
    ],
    // Asset bundle for compression output:
    'targets' => [
        'all' => [
            'class' => yii\web\AssetBundle::class,
            'basePath' => '@webroot',
            'baseUrl' => '@web',
            'js' => '{hash}.js',
            'css' => '{hash}.css',
        ],
    ],
    // Asset manager configuration:
    'assetManager' => [
        'basePath' => '@webroot/assets',
        'baseUrl' => '@web/assets',
        'bundles' => [
            yii\bootstrap\BootstrapAsset::class => [
                'basePath' => '@webroot',
                'baseUrl' => '@web',
                'sourcePath' => null,
            ],
            yii\bootstrap\BootstrapPluginAsset::class => [
                'basePath' => '@webroot',
                'baseUrl' => '@web',
                'sourcePath' => null,
            ],
        ],
    ],
];