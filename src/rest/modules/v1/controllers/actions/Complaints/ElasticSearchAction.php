<?php
namespace rest\modules\v1\controllers\actions\Complaints;

use Yii;
use rest\components\api\actions\Action;
use rest\modules\v1\models\Complaints;

/**
 * Class SearchAction
 * @package rest\common\controllers\actions\Complaints
 */
class ElasticSearchAction extends Action
{
    /**
     * @inheritdoc
     * @throws \ustudio\service_mandatory\ServiceException
     */
    public function run()
    {
        return (new Complaints())->search(Yii::$app->request->get());
    }
}