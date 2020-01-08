<?php

namespace app\controllers;

use app\models\UploadForm;
use Yii;
use yii\web\Controller;
use yii\web\UploadedFile;

/***
 * @noinspection PhpUnused
 */
class UploadController extends Controller
{
    /**
     * @return mixed
     */
    public function actionIndex()
    {
        $model = new UploadForm();
        if (Yii::$app->request->isPost) {
            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
            if ($model->upload()) {
                Yii::$app->session->setFlash('uploadForm', $model);

                return $this->refresh();
            }
        }
        return $this->render('index', [
            'model' => $model,
        ]);
    }
}
