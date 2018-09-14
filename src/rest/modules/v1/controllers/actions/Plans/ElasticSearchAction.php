<?php
namespace rest\modules\v1\controllers\actions\Plans;

use Yii;
use rest\components\api\actions\Action;
use rest\modules\v1\models\Plans;

/**
 * Class SearchAction
 * @package rest\common\controllers\actions\Tender
 */
class ElasticSearchAction extends Action
{
    /**
     * @inheritdoc
     * @throws \ustudio\service_mandatory\ServiceException
     */
    public function run()
    {
        return (new Plans())->search(Yii::$app->request->get());
    }
}