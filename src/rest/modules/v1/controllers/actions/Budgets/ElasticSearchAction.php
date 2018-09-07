<?php
namespace rest\modules\v1\controllers\actions\Budgets;

use rest\modules\v1\models\Budgets\Budgets;
use Yii;
use rest\components\api\actions\Action;


/**
 * Class ViewAction
 * @package rest\common\controllers\actions\Budgets
 */
class ElasticSearchAction extends Action
{
    /**
     * @inheritdoc
     * @throws \ustudio\service_mandatory\ServiceException
     */
    public function run()
    {
        //return (new BudgetSearch())->search(Yii::$app->request->get());
        return (new Budgets())->search(Yii::$app->request->get());
    }
}