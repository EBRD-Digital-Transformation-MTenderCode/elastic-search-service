<?php

namespace console\controllers;
use console\models\Budgets;
use console\models\Elastic;
use console\models\Tenders;
use yii\console\Controller;
use Yii;
use yii\web\HttpException;

class ReindexElasticController extends Controller
{
    /**
     *
     */
    public function actionIndex()
    {
        try {
            $elastic = new Elastic();
            $elastic->dropIndex();

            // budgets
            $budgets = new Budgets();
            $result = $budgets->elasticMapping();
            if ((int)$result['code'] != 200) {
                Yii::error("Elastic mapping budgets error", 'console-msg');
                exit(0);
            }
            $budgets->indexItemsToElastic();

            // tenders
            $tenders = new Tenders();
            $result = $tenders->elasticMapping();
            if ((int)$result['code'] != 200) {
                Yii::error("Elastic mapping tenders error", 'console-msg');
                exit(0);
            }
            $tenders->indexItemsToElastic();

        } catch (HttpException $e) {
            Yii::error($e->getMessage(), 'console-msg');
            exit(0);
        }

        Yii::info("Elastic indexing is complete", 'console-msg');
    }
}