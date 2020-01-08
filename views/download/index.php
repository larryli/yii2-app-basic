<?php

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model app\models\DownloadForm */

use app\assets\NotifyAsset;
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$success = Yii::$app->session->hasFlash('downloadForm');
if ($success) {
    NotifyAsset::register($this);
    $model = Yii::$app->session->getFlash('downloadForm');
    $this->registerJs("notify('/notify?id={$model->id}', '#download-result')");
}

$this->title = 'Download';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-contact">
    <h1><?= Html::encode($this->title) ?></h1>

    <?php if ($success): ?>
        <div class="alert alert-success">
            Start downloading <code><?= $model->url ?></code>
        </div>

        <p id="download-result">
            Please wait for download...
        </p>

    <?php else: ?>

        <p>
            Download from url.
        </p>

        <div class="row">
            <div class="col-lg-5">

                <?php $form = ActiveForm::begin(['id' => 'download-form']); ?>

                    <?= $form->field($model, 'url')->input('url', ['autofocus' => true]) ?>

                    <div class="form-group">
                        <?= Html::submitButton('Download', ['class' => 'btn btn-primary', 'name' => 'download-button']) ?>
                    </div>

                <?php ActiveForm::end(); ?>

            </div>
        </div>

    <?php endif; ?>
</div>
