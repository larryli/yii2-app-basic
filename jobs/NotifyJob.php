<?php

namespace app\jobs;

use app\components\Nchan;
use Yii;
use yii\base\BaseObject;
use yii\base\InvalidConfigException;
use yii\httpclient\Exception;
use yii\queue\JobInterface;

class NotifyJob extends BaseObject implements JobInterface
{
    public $id;
    public $message;

    /**
     * @inheritDoc
     * @throws InvalidConfigException
     * @throws Exception
     */
    public function execute($queue)
    {
        /** @var Nchan $nchan */
        $nchan = Yii::$app->get('nchan');
        $nchan->pub($this->id, $this->message);
    }
}