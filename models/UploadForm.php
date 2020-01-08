<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\web\UploadedFile;

/**
 * Class UploadForm
 * @property string $src
 */
class UploadForm extends Model
{
    /**
     * @var UploadedFile
     */
    public $imageFile;

    /**
     * @inheritDoc
     */
    public function rules()
    {
        return [
            [['imageFile'], 'required'],
            [['imageFile'], 'file', 'skipOnEmpty' => false, 'extensions' => 'png, jpg'],
        ];
    }

    /**
     * @return bool
     */
    public function upload()
    {
        if ($this->validate()) {
            $this->imageFile->saveAs(Yii::getAlias('@webroot/uploads/') . $this->imageFile->baseName . '.' . $this->imageFile->extension);
            return true;
        }
        return false;
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getSrc()
    {
        return Yii::getAlias('@web/uploads/') . $this->imageFile->baseName . '.' . $this->imageFile->extension;
    }
}
