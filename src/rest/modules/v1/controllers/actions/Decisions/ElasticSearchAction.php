<?php
namespace rest\modules\v1\controllers\actions\Decisions;

use Yii;
use rest\components\api\actions\Action;
use rest\modules\v1\models\Decisions;

/**
 * Class SearchAction
 * @package rest\common\controllers\actions\Decisions
 */
class ElasticSearchAction extends Action
{
    /**
     * @inheritdoc
     * @throws \ustudio\service_mandatory\ServiceException
     */
    public function run()
    {
        return (new Decisions())->search(Yii::$app->request->get());
    }
}