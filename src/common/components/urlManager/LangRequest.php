<?php
namespace common\components\urlManager;

use Yii;
use yii\web\Request;
use yii\helpers\ArrayHelper;

class LangRequest extends Request
{
    protected function resolvePathInfo()
    {

        $get = Yii::$app->getRequest()->get();
        $post = Yii::$app->getRequest()->post();

        $_language = (
            (!empty($post['language'])) ? $post['language'] :
            (!empty($get['language']) ? $get['language'] : Yii::$app->urlManager->default_language)
            );

        if(ArrayHelper::isIn($_language, Yii::$app->urlManager->languages)) {
            Yii::$app->language = $_language;
        }
        else {

            throw new \yii\web\HttpException(422, "Error don't language in MDM / Ошибка данного языка нет в МДМ");
        }



        return parent::resolvePathInfo();
    }
}