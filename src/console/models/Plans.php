<?php
namespace console\models;

use Yii;
use yii\db\Exception;
use yii\helpers\ArrayHelper;
use PDOException;

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
     * @throws \yii\web\HttpException
     */
    public function elasticMapping()
    {
        Yii::info("Mapping plans", 'console-msg');
        $mapArr = [
            'dynamic' => 'strict',
            '_all' => ['enabled' => false],
            'properties' => [
                'id' => ['type' => 'keyword'],
//                'tenderId' => ['type' => 'keyword'],
//                'title' => ['type' => 'text'],
                'description' => ['type' => 'text'],
                'cdu-v' => ['type' => 'keyword'],
//                'search' => ['type' => 'text'],
//                'buyerRegion' => ['type' => 'keyword'],
//                'procedureType' => ['type' => 'keyword'],
//                'procedureStatus' => ['type' => 'keyword'],
//                'budget' => ['type' => 'scaled_float', 'scaling_factor' => 100],
//                'classification' => ['type' => 'keyword'],
            ],
        ];
        $jsonMap = json_encode($mapArr);
        $url = Yii::$app->params['elastic_url'];
        $index = Yii::$app->params['elastic_plans_index'];
        $type = Yii::$app->params['elastic_plans_type'];
        $elastic = new Elastic($url, $index, $type);
        $result = $elastic->mapping($jsonMap);
        return $result;
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
        $elastic = new Elastic($url, $index, $type);
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
                    $docArr = $this->getDocForElastic($item, $cdu);
                    if (!empty($docArr)) {
                        $result = $elastic->indexPlan($docArr);

                        if ($result['code'] != 200 && $result['code'] != 201 && $result['code'] != 100) {
                            Yii::error("Elastic indexing plans error. Http-code: " . $result['code'], 'sync-info');
                            exit(0);
                        }

                    } else {
                        //@todo error
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

    /**
     * getting from response-field of a document for elastic
     *
     * @param $plan
     * @param $cdu
     * @return array
     */
    public function getDocForElastic($plan, $cdu) {
        $response = $plan['response'];
        $data = json_decode($response, 1);

        if (isset($cdu[$plan['cdu_id']]) && $cdu[$plan['cdu_id']] != self::TYPE_PROZORRO) {
            // ocds plan
//            $records = $data['records'];
//            $docArr = [];
//            foreach ($records as $record) {
//                if ($record['ocid'] == $tender['tender_id']) {
//                    $tender_id = $record['ocid'];
//                    $title = ($record['compiledRelease']['tender']['title']) ?? "";
//                    $description = ($record['compiledRelease']['tender']['description']) ?? "";
//                    $docArr = [
//                        'tenderId' => $tender_id,
//                        'title' => $title,
//                        'description' => $description,
//                        'cdu-v' => $cdu[$tender['cdu_id']] ?? '',
//                    ];
//
//                    break;
//                }
//            }
        } else {
            // prozorro plan
            //echo "<pre>" . print_r($data['data']['id'],1) . "</pre>"; die;
            $id = $data['data']['id'];
            //$description = $data['description'] ?? "";
            $docArr = [
                'id'              => $id,
                //'description'     => $description,
                'cdu-v'           => $cdu[$plan['cdu_id']] ?? '',
            ];
        }
        return $docArr;
    }
}