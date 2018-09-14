<?php
namespace console\controllers;

use Yii;
use yii\web\HttpException;
use yii\console\Controller;
use console\models\Budgets;
use console\models\Tenders;
use ustudio\service_mandatory\components\elastic\ElasticComponent;

/**
 * Class ReindexElasticController
 * @package console\controllers
 */
class ReindexElasticController extends Controller
{
    /**
     * reindex all indexes
     */
    public function actionAll()
    {
        $this->reindexBudgets();

        $this->reindexTenders();

        Yii::info("Elastic indexing is complete", 'console-msg');
    }

    /**
     * reindex budgets
     */
    public function actionBudgets()
    {
        try {
            $this->reindexBudgets();
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
            $this->reindexTenders();
        } catch (HttpException $e) {
            Yii::error($e->getMessage(), 'console-msg');
            exit(0);
        }

        Yii::info("Elastic indexing Tenders is complete", 'console-msg');
    }

    /**
     *
     */
    private function reindexBudgets()
    {
        $elastic_url = Yii::$app->params['elastic_url'];
        $elastic_index = Yii::$app->params['elastic_budgets_index'];
        $elastic_type = Yii::$app->params['elastic_budgets_type'];

        try {
            $elastic = new ElasticComponent($elastic_url, $elastic_index, $elastic_type);
            $result = $elastic->dropIndex();

            if ((int)$result['code'] != 200 && (int)$result['code'] != 404) {
                Yii::error("Elastic index " . $elastic_index . " error. Code: " . $result['code'], 'console-msg');
                exit(0);
            }

            $result = $elastic->budgetsMapping();

            if ((int)$result['code'] != 200) {
                Yii::error("Elastic mapping " . $elastic_index . " error", 'console-msg');
                exit(0);
            }

            $budgets = new Budgets();
            $budgets->reindexItemsToElastic();
        } catch (HttpException $e) {
            Yii::error($e->getMessage(), 'console-msg');
            exit(0);
        }
    }

    private function reindexTenders()
    {
        $elastic_url = Yii::$app->params['elastic_url'];
        $elastic_index = Yii::$app->params['elastic_tenders_index'];
        $elastic_type = Yii::$app->params['elastic_tenders_type'];

        try {
            $elastic = new ElasticComponent($elastic_url, $elastic_index, $elastic_type);
            $result = $elastic->dropIndex();

            if ((int)$result['code'] != 200 && (int)$result['code'] != 404) {
                Yii::error("Elastic index " . $elastic_index . " error. Code: " . $result['code'], 'console-msg');
                exit(0);
            }

            $result = $elastic->tendersMapping();

            if ((int)$result['code'] != 200) {
                Yii::error("Elastic mapping " . $elastic_index . " error", 'console-msg');
                exit(0);
            }

            $tenders = new Tenders();
            $tenders->reindexItemsToElastic();

        } catch (HttpException $e) {
            Yii::error($e->getMessage(), 'console-msg');
            exit(0);
        }
    }
}