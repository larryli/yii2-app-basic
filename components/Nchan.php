<?php

namespace app\components;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\helpers\Json;
use yii\httpclient\Client;
use yii\httpclient\CurlTransport;
use yii\httpclient\Exception;
use yii\httpclient\Request;

class Nchan extends Component
{
    /**
     * @var string Base url
     */
    public $baseUrl;
    /**
     * @var string Http Client Transport
     * @see https://github.com/yiisoft/yii2-httpclient/blob/master/docs/guide/usage-transports.md
     */
    public $transport = CurlTransport::class;
    /**
     * @var Client Http Client
     */
    private $_httpClient;

    /**
     * @throws InvalidConfigException
     */
    public function init()
    {
        if (empty($this->baseUrl)) {
            throw new InvalidConfigException('The ' . static::class . '::baseUrl value must be set.');
        }
        parent::init();
    }

    /**
     * @param string $id
     * @param mixed $message
     * @return mixed
     * @throws InvalidConfigException
     * @throws Exception
     */
    public function pub($id, $message)
    {
        if (!is_string($message)) {
            // 如果不是（已编码的）字符串，就使用 json 编码
            $message = Json::encode($message);
        }
        return $this->req($this->getHttpClient()->post(['pub', 'id' => $id], $message));
    }

    /**
     * @param Request $request
     * @return mixed
     * @throws Exception
     */
    protected function req($request)
    {
        $response = $request->addHeaders(['accept' => 'text/json'])->send();
        if ($response->isOk) {
            return $response->data;
        }
        return false;
    }

    /**
     * Get http client
     *
     * @return Client
     * @throws InvalidConfigException
     */
    protected function getHttpClient()
    {
        if (!is_object($this->_httpClient)) {
            $config = [
                'class' => Client::class,
                'baseUrl' => $this->baseUrl,
            ];
            if (!empty($this->transport)) {
                $config['transport'] = $this->transport;
            }
            /** @var Client _httpClient */
            $this->_httpClient = Yii::createObject($config);
        }
        return $this->_httpClient;
    }
}
