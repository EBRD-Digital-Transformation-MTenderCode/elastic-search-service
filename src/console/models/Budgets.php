<?php
namespace console\models;

use Yii;
use yii\db\Exception;
use PDOException;
use ustudio\service_mandatory\components\elastic\ElasticComponent;

/**
 * Class Budgets
 * @package console\models
 */
class Budgets
{
    /**
     * @return mixed|\yii\db\Connection
     */
    public static function getDb()
    {
        return Yii::$app->db_budgets;
    }

    /**
     * @return array
     * @throws \ustudio\service_mandatory\components\elastic\ForbiddenHttpException
     * @throws \yii\web\HttpException
     */
    public function elasticMapping()
    {
        Yii::info("Mapping budgets", 'console-msg');
        $mapArr = [
            'dynamic' => 'strict',
            '_all' => ['enabled' => false],
            'properties' => [
                'ocid' => ['type' => 'keyword'],
                'title' => ['type' => 'text'],
                'description' => ['type' => 'text'],
            ]
        ];

        $jsonMap = json_encode($mapArr);
        $url = Yii::$app->params['elastic_url'];
        $index = Yii::$app->params['elastic_budgets_index'];
        $type = Yii::$app->params['elastic_budgets_type'];
        $elastic = new ElasticComponent($url, $index, $type);
        $result = $elastic->createMapping($jsonMap);
        return $result;
    }

    /**
     * indexing of budgets to elastic
     * @return bool
     * @throws \ustudio\service_mandatory\components\elastic\ForbiddenHttpException
     * @throws \yii\web\HttpException
     */
    public function reindexItemsToElastic()
    {
        Yii::info("Indexing budgets", 'console-msg');
        $limit = 25;
        $offset = 0;
        $url = Yii::$app->params['elastic_url'];
        $index = Yii::$app->params['elastic_budgets_index'];
        $type = Yii::$app->params['elastic_budgets_type'];
        $elastic = new ElasticComponent($url, $index, $type);
        while (true) {
            try {
                // block the update of selected records in the database
                $transaction = Yii::$app->db_budgets->beginTransaction();
                $budgets = Yii::$app->db_budgets->createCommand("SELECT * FROM budgets FOR UPDATE LIMIT {$limit} OFFSET {$offset}")->queryAll();
                $countBudgets = count($budgets);
                if (!$countBudgets) {
                    break;
                }
                $offset += $limit;
                foreach ($budgets as $budget) {
                    $elastic->indexBudget($budget);
                }
                $transaction->commit();
            } catch(PDOException $exception) {
                Yii::error("PDOException. " . $exception->getMessage(), 'console-msg');
                exit(0);
            } catch(Exception $exception) {
                Yii::error("DB exception. " . $exception->getMessage(), 'console-msg');
                exit(0);
            }
            Yii::info("Updated {$countBudgets} budgets", 'console-msg');
            // delay 0.3 sec
            usleep(300000);
        }
        return true;
    }
}