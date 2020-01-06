<?php

return [
    'class' => yii\queue\redis\Queue::class,
    'as log' => yii\queue\LogBehavior::class,
    'as notify' => app\behaviors\NotifyBehavior::class,
    'channel' => getenv('QUEUE_CHANNEL') ?: 'queue',
];
