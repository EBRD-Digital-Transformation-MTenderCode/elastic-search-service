<?php
namespace rest\modules\v1\controllers\actions\Contracts;

use Yii;
use rest\components\api\actions\Action;
use rest\modules\v1\models\Contracts;

/**
 * Class SearchAction
 * @package rest\common\controllers\actions\Contract
 */
class ElasticSearchAction extends Action
{
    /**
     * @inheritdoc
     * @throws \ustudio\service_mandatory\ServiceException
     */
    public function run()
    {
        return (new Contracts())->search(Yii::$app->request->get());
    }
}