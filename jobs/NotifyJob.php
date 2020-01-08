<?php

namespace app\jobs;

use app\components\Nchan;
use Yii;
use yii\base\BaseObject;
use yii\httpclient\Exception;
use yii\queue\JobInterface;

class NotifyJob extends BaseObject implements JobInterface
{
    public $id;
    public $message;

    /**
     * @inheritDoc
     * @throws Exception
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function execute($queue)
    {
        /** @var Nchan $nchan */
        /** @noinspection PhpUnhandledExceptionInspection */
        $nchan = Yii::$app->get('nchan');
        $nchan->pub($this->id, $this->message);
    }
}
