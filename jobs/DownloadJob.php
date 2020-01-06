<?php

namespace app\jobs;

use yii\base\BaseObject;
use yii\queue\JobInterface;

class DownloadJob extends BaseObject implements JobInterface
{
    public $url;

    public function execute($queue)
    {
        file_put_contents(tempnam(sys_get_temp_dir(), 'download_'), file_get_contents($this->url));
    }
}
