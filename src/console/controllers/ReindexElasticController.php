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
            $result = $elastic->dropIndex();
            $budgets = new Budgets();
            $result = $budgets->elasticMapping();
            $result = $budgets->indexItemsToElastic();

//            if ($result['code'] != 200 && $result['code'] != 201 && $result['code'] != 100) {
//                Yii::error("Elastic indexing error. Http-code: " . $result['code'], 'sync-info');
//                return $result;
//            }


            $tenders = new Tenders();
            $result = $tenders->elasticMapping();
            $result = $tenders->indexItemsToElastic();
        } catch (HttpException $e) {
            Yii::error($e->getMessage(), 'console-msg');
            exit(0);
        }
    }
}