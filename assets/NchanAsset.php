<?php

namespace app\assets;

use yii\web\AssetBundle;

class NchanAsset extends AssetBundle
{
    /**
     * @var string
     */
    public $sourcePath = '@npm/nchan';
    /**
     * @var array
     */
    public $js = ['NchanSubscriber.js'];

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->publishOptions['only'] = ['*.js'];
        parent::init();
    }
}
