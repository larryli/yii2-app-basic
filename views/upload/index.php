<?php

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model app\models\UploadForm */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$success = Yii::$app->session->hasFlash('uploadForm');
if ($success) {
    $model = Yii::$app->session->getFlash('uploadForm');
}

$this->title = 'Upload';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-contact">
    <h1><?= Html::encode($this->title) ?></h1>

    <?php if ($success): ?>
        <div class="alert alert-success">
            Upload <code><?= $model->imageFile->name ?></code> succeeded.
        </div>

        <p id="upload-result">
            <?= Html::img($model->src, ['alt' => $model->imageFile->name]) ?>
        </p>

    <?php else: ?>

        <p>
            Upload image file.
        </p>

        <div class="row">
            <div class="col-lg-5">

                <?php $form = ActiveForm::begin(['id' => 'upload-form']); ?>

                <?= $form->field($model, 'imageFile')->fileInput() ?>

                <div class="form-group">
                    <?= Html::submitButton('Upload', ['class' => 'btn btn-primary', 'name' => 'upload-button']) ?>
                </div>

                <?php ActiveForm::end(); ?>

            </div>
        </div>

    <?php endif; ?>
</div>
