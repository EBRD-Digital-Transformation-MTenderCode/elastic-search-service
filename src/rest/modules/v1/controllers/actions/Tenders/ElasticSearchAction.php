<?php
namespace rest\modules\v1\controllers\actions\Tenders;

use rest\modules\v1\models\Tenders;
use Yii;
use rest\components\api\actions\Action;


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
        return (new Tenders())->search(Yii::$app->request->get());
    }
}