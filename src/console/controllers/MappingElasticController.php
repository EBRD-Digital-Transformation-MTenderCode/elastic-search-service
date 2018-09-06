<?php

namespace console\controllers;
use Yii;
use console\models\Budgets;
use console\models\Elastic;
use console\models\Tenders;
use yii\console\Controller;
use yii\web\HttpException;

class MappingElasticController extends Controller
{
    /**
     * @throws HttpException
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionIndex() {
        $elastic_url = Yii::$app->params['elastic_url'];
        $elastic_index = Yii::$app->params['elastic_budgets_index'];
        $elastic_type = Yii::$app->params['elastic_budgets_type'];
        $elastic = new Elastic($elastic_url, $elastic_index, $elastic_type);
        $result = $elastic->dropIndex();
        $budgets = new Budgets();
        $result = $budgets->elasticMapping();

        $elastic_index = Yii::$app->params['elastic_tenders_index'];
        $elastic_type = Yii::$app->params['elastic_tenders_type'];
        $elastic = new Elastic($elastic_url, $elastic_index, $elastic_type);
        $result = $elastic->dropIndex();
        $budgets = new Tenders();
        $result = $budgets->elasticMapping();
        Yii::info("Elastic mapping is complete", 'console-msg');
    }
}