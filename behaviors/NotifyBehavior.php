<?php

namespace app\behaviors;

use app\jobs\DownloadJob;
use app\jobs\NotifyJob;
use Yii;
use yii\base\Behavior;
use yii\base\InvalidConfigException;
use yii\queue\ExecEvent;
use yii\queue\Queue;

class NotifyBehavior extends Behavior
{
    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            Queue::EVENT_AFTER_EXEC => 'afterExec',
            Queue::EVENT_AFTER_ERROR => 'afterError',
        ];
    }

    /**
     * @param ExecEvent $event
     * @throws InvalidConfigException
     */
    public function afterExec(ExecEvent $event)
    {
        if ($this->isDownloadJob($event) && !empty($event->id)) {
            $this->notify($event->id, 'done');
        }
    }

    /**
     * @param ExecEvent $event
     * @throws InvalidConfigException
     */
    public function afterError(ExecEvent $event)
    {
        if ($this->isDownloadJob($event) && !empty($event->id)) {
            $this->notify($event->id, 'error');
        }
    }

    /**
     * @param ExecEvent $event
     * @return bool
     */
    protected function isDownloadJob($event)
    {
        return is_a($event->job, DownloadJob::class);
    }

    /**
     * @param string $id
     * @param string $message
     * @throws InvalidConfigException
     */
    protected function notify($id, $message)
    {
        /** @var Queue $queue */
        $queue = Yii::$app->get('queue');
        $queue->push(new NotifyJob([
            'id' => $id,
            'message' => $message,
        ]));
    }
}
