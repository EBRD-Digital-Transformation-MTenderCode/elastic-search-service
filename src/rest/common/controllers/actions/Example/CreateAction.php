<?php

namespace rest\common\controllers\actions\Example;

use Yii;
use rest\components\api\actions\Action;

class CreateAction extends Action
{
    public function run()
    {
        $param = Yii::$app->request->getBodyParams();
        $model = new DocModel();
        $model->scenario = 'create';
        $model->load($param, '');
        $result = $model->createDoc();
        return $result;
    }
}