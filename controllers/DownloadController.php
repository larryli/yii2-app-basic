<?php

namespace app\controllers;

use app\models\DownloadForm;
use Yii;
use yii\base\InvalidConfigException;
use yii\web\Controller;

class DownloadController extends Controller
{
    /**
     * @return mixed
     * @throws InvalidConfigException
     */
    public function actionIndex()
    {
        $model = new DownloadForm();
        if ($model->load(Yii::$app->request->post()) && $model->download()) {
            Yii::$app->session->setFlash('downloadForm', $model);

            return $this->refresh();
        }
        return $this->render('index', [
            'model' => $model,
        ]);
    }
}
