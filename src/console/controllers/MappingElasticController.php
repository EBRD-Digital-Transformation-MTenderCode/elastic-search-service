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
     * @throws \ustudio\service_mandatory\components\elastic\ForbiddenHttpException
     */
    public function actionIndex() {
        $elastic_url = Yii::$app->params['elastic_url'];
        $elastic_index = Yii::$app->params['elastic_budgets_index'];
        $elastic_type = Yii::$app->params['elastic_budgets_type'];
        $elastic = new ElasticComponent($elastic_url, $elastic_index, $elastic_type);
        $elastic->dropIndex();
        $elastic->budgetsMapping();

        $elastic_index = Yii::$app->params['elastic_tenders_index'];
        $elastic_type = Yii::$app->params['elastic_tenders_type'];
        $elastic = new ElasticComponent($elastic_url, $elastic_index, $elastic_type);
        $elastic->dropIndex();
        $elastic->tendersMapping();

        Yii::info("Elastic mapping is complete", 'console-msg');
    }
}