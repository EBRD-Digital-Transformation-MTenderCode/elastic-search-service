<?php
namespace console\models;

use Yii;
use yii\db\Exception;
use yii\helpers\ArrayHelper;
use PDOException;
use ustudio\service_mandatory\components\elastic\ElasticComponent;

/**
 * Class Plans
 * @package console\models
 */
class Plans
{
    const TYPE_PROZORRO = 'mtender1';

    /**
     * @return mixed|\yii\db\Connection
     */
    public static function getDb()
    {
        return Yii::$app->db_plans;
    }

    /**
     * indexing of plans to elastic
     *
     * @throws \yii\web\ForbiddenHttpException
     * @throws \yii\web\HttpException
     */
    public function indexItemsToElastic()
    {
        Yii::info("Indexing plans", 'console-msg');
        $limit = 25;
        $offset = 0;
        $url = Yii::$app->params['elastic_url'];
        $index = Yii::$app->params['elastic_plans_index'];
        $type = Yii::$app->params['elastic_plans_type'];
        $elastic = new ElasticComponent($url, $index, $type);
        while (true) {
            try {
                // block the update of selected records in the database
                $db = self::getDb();
                $transaction = $db->beginTransaction();
                $items = $db->createCommand("SELECT * FROM plans FOR UPDATE LIMIT {$limit} OFFSET {$offset}")->queryAll();
                $cdu = ArrayHelper::map($db->createCommand("SELECT * FROM cdu")->queryAll(), 'id', 'alias');

                $countItems = count($items);
                if (!$countItems) {
                    break;
                }
                $offset += $limit;
                foreach ($items as $item) {
                    $cduV = $cdu[$item['cdu_id']] ?? '';
                    if ($cduV != self::TYPE_PROZORRO) {

                    } else {
                        $elastic->indexPlanPrz($item, $cduV);
                    }
                }
                $transaction->commit();
            } catch(PDOException $exception) {
                Yii::error("PDOException. " . $exception->getMessage(), 'console-msg');
                exit(0);
            } catch(Exception $exception) {
                Yii::error("DB exception. " . $exception->getMessage(), 'console-msg');
                exit(0);
            }
            Yii::info("Updated {$countItems} plans", 'console-msg');
            // delay 0.3 sec
            usleep(300000);
        }
    }
}