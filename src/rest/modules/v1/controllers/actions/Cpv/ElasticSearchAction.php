<?php
namespace rest\modules\v1\controllers\actions\Cpv;

use Yii;
use rest\components\api\actions\Action;
use rest\modules\v1\models\Cpv;

/**
 * Class ElasticSearchAction
 * @package rest\modules\v1\controllers\actions\Cpv
 */
class ElasticSearchAction extends Action
{
    /**
     * @inheritdoc
     * @throws \ustudio\service_mandatory\ServiceException
     */
    public function run()
    {
        return (new Cpv())->search(Yii::$app->request->get());
    }
}