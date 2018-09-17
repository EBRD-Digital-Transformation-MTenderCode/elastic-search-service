<?php
namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\web\HttpException;
use ustudio\service_mandatory\components\elastic\ElasticComponent;

/**
 * Class MappingElasticController
 * @package console\controllers
 */
class MappingElasticController extends Controller
{
    /**
     * @throws HttpException
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionAll() {

        $this->actionBudgets();
        $this->actionTenders();
        $this->actionPlans();

        Yii::info("Elastic mapping is complete", 'console-msg');
    }

    /**
     * @throws HttpException
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionBudgets()
    {
        $elastic_url = Yii::$app->params['elastic_url'];
        $elastic_index = Yii::$app->params['elastic_budgets_index'];
        $elastic_type = Yii::$app->params['elastic_budgets_type'];
        $elastic = new ElasticComponent($elastic_url, $elastic_index, $elastic_type);
        $elastic->dropIndex();
        $result = $elastic->budgetsMapping();

        if ((int)$result['code'] != 200) {
            Yii::error("Elastic mapping " . $elastic_index . " error", 'console-msg');
            exit(0);
        }

        Yii::info("Budgets mapping is complete", 'console-msg');
    }

    /**
     * @throws HttpException
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionTenders()
    {
        $elastic_url = Yii::$app->params['elastic_url'];
        $elastic_index = Yii::$app->params['elastic_tenders_index'];
        $elastic_type = Yii::$app->params['elastic_tenders_type'];
        $elastic = new ElasticComponent($elastic_url, $elastic_index, $elastic_type);
        $elastic->dropIndex();
        $result = $elastic->tendersMapping();

        if ((int)$result['code'] != 200) {
            Yii::error("Elastic mapping " . $elastic_index . " error", 'console-msg');
            exit(0);
        }

        Yii::info("Tenders mapping is complete", 'console-msg');
    }

    /**
     * @throws HttpException
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionPlans()
    {
        $elastic_url = Yii::$app->params['elastic_url'];
        $elastic_index = Yii::$app->params['elastic_plans_index'];
        $elastic_type = Yii::$app->params['elastic_plans_type'];
        $elastic = new ElasticComponent($elastic_url, $elastic_index, $elastic_type);
        $elastic->dropIndex();
        $result = $elastic->plansMapping();

        if ((int)$result['code'] != 200) {
            Yii::error("Elastic mapping " . $elastic_index . " error", 'console-msg');
            exit(0);
        }

        Yii::info("Plans mapping is complete", 'console-msg');
    }

}