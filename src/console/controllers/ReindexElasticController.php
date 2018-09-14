<?php

namespace console\controllers;
use console\models\Budgets;
use console\models\Elastic;
use console\models\Plans;
use console\models\Tenders;
use yii\console\Controller;
use Yii;
use yii\web\HttpException;

class ReindexElasticController extends Controller
{
    /**
     * reindex all indexes
     */
    public function actionAll()
    {
        $this->indexBudgets();

        $this->indexTenders();

        Yii::info("Elastic indexing is complete", 'console-msg');
    }

    /**
     * reindex budgets
     */
    public function actionBudgets()
    {
        try {
            $this->indexBudgets();
        } catch (HttpException $e) {
            Yii::error($e->getMessage(), 'console-msg');
            exit(0);
        }

        Yii::info("Elastic indexing Budgets is complete", 'console-msg');
    }

    /**
     *  reindex tenders
     */
    public function actionTenders()
    {
        try {
            $this->indexTenders();
        } catch (HttpException $e) {
            Yii::error($e->getMessage(), 'console-msg');
            exit(0);
        }

        Yii::info("Elastic indexing Tenders is complete", 'console-msg');
    }

    /**
     *  reindex plans
     */
    public function actionPlans()
    {
        try {
            $this->indexPlans();
        } catch (HttpException $e) {
            Yii::error($e->getMessage(), 'console-msg');
            exit(0);
        }

        Yii::info("Elastic indexing Plans is complete", 'console-msg');
    }

    /**
     *
     */
    private function indexBudgets()
    {
        $elastic_url = Yii::$app->params['elastic_url'];
        $elastic_index = Yii::$app->params['elastic_budgets_index'];
        $elastic_type = Yii::$app->params['elastic_budgets_type'];

        try {
            $elastic = new Elastic($elastic_url, $elastic_index, $elastic_type);
            $result = $elastic->dropIndex();

            if ((int)$result['code'] != 200 && (int)$result['code'] != 404) {
                Yii::error("Elastic index " . $elastic_index . " error. Code: " . $result['code'], 'console-msg');
                exit(0);
            }

            $budgets = new Budgets();
            $result = $budgets->elasticMapping();
            if ((int)$result['code'] != 200) {
                Yii::error("Elastic mapping " . $elastic_index . " error", 'console-msg');
                exit(0);
            }
            $budgets->indexItemsToElastic();

        } catch (HttpException $e) {
            Yii::error($e->getMessage(), 'console-msg');
            exit(0);
        }

    }

    /**
     *
     */
    private function indexTenders()
    {
        $elastic_url = Yii::$app->params['elastic_url'];
        $elastic_index = Yii::$app->params['elastic_tenders_index'];
        $elastic_type = Yii::$app->params['elastic_tenders_type'];

        try {
            $elastic = new Elastic($elastic_url, $elastic_index, $elastic_type);
            $result = $elastic->dropIndex();

            if ((int)$result['code'] != 200 && (int)$result['code'] != 404) {
                Yii::error("Elastic index " . $elastic_index . " error. Code: " . $result['code'], 'console-msg');
                exit(0);
            }

            $tenders = new Tenders();
            $result = $tenders->elasticMapping();
            if ((int)$result['code'] != 200) {
                Yii::error("Elastic mapping " . $elastic_index . " error", 'console-msg');
                exit(0);
            }

            $tenders->indexItemsToElastic();

        } catch (HttpException $e) {
            Yii::error($e->getMessage(), 'console-msg');
            exit(0);
        }
    }

    /**
     *
     */
    private function indexPlans()
    {
        $elastic_url = Yii::$app->params['elastic_url'];
        $elastic_index = Yii::$app->params['elastic_plans_index'];
        $elastic_type = Yii::$app->params['elastic_plans_type'];

        try {
            $elastic = new Elastic($elastic_url, $elastic_index, $elastic_type);
            $result = $elastic->dropIndex();

            if ((int)$result['code'] != 200 && (int)$result['code'] != 404) {
                Yii::error("Elastic index " . $elastic_index . " error. Code: " . $result['code'], 'console-msg');
                exit(0);
            }

            $plans = new Plans();
            $result = $plans->elasticMapping();
            if ((int)$result['code'] != 200) {
                Yii::error("Elastic mapping " . $elastic_index . " error", 'console-msg');
                exit(0);
            }

            $plans->indexItemsToElastic();

        } catch (HttpException $e) {
            Yii::error($e->getMessage(), 'console-msg');
            exit(0);
        }
    }

}