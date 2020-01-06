<?php

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model app\models\DownloadForm */

use app\assets\NchanAsset;
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$success = Yii::$app->session->hasFlash('downloadForm');
if ($success) {
    NchanAsset::register($this);
    $model = Yii::$app->session->getFlash('downloadForm');
    $js = <<< EOF
var sub = new NchanSubscriber('/notify?id={$model->id}', {
    subscriber: ['eventsource', 'websocket', 'longpoll'],
    reconnect: 'persist',
    shared: true
});
sub.on('message', function(message, message_metadata) {
    if (message == 'done') {
        $('#download-result').text('Download completed.');
    } else if (message == 'error') {
        $('#download-result').text('Download error.');
    } else {
        $('#download-result').text(message);
    }
});
sub.on('error', function(code, message) {
    $('#download-result').text(message);
});
sub.start();
EOF;
    $this->registerJs($js);
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
