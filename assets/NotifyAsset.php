<?php

namespace app\assets;

use yii\web\AssetBundle;
use yii\web\JqueryAsset;

class NotifyAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $js = [
        'js/notify.js',
    ];
    public $depends = [
        JqueryAsset::class,
        NchanAsset::class,
    ];
}
