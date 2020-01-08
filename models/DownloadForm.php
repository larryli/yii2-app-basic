<?php

namespace app\models;

use app\jobs\DownloadJob;
use Yii;
use yii\base\Model;
use yii\queue\Queue;

/**
 * Class DownloadForm
 * @property $id
 */
class DownloadForm extends Model
{
    public $url;
    private $_id;

    public function rules()
    {
        return [
            [['url'], 'required'],
            ['url', 'url'],
        ];
    }

    /**
     * @return bool
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function download()
    {
        if ($this->validate()) {
            /** @var Queue $queue */
            /** @noinspection PhpUnhandledExceptionInspection */
            $queue = Yii::$app->get('queue');
            $this->_id = $queue->push(new DownloadJob([
                'url' => $this->url,
            ]));

            return true;
        }
        return false;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->_id;
    }
}
