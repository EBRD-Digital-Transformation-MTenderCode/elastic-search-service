<?php
namespace console\models;

use Yii;
use yii\db\Exception;
use yii\helpers\ArrayHelper;
use PDOException;
use ustudio\service_mandatory\components\elastic\ElasticComponent;

/**
 * Class Tenders
 * @package console\models
 */
class Tenders
{
    const TYPE_PROZORRO = 'mtender1';

    /**
     * @return mixed|\yii\db\Connection
     */
    public static function getDb()
    {
        return Yii::$app->db_tenders;
    }

    /**
     * @return array
     * @throws \ustudio\service_mandatory\components\elastic\ForbiddenHttpException
     * @throws \yii\web\HttpException
     */
    public function elasticMapping()
    {
        Yii::info("Mapping tenders", 'console-msg');
        $mapArr = [
            'dynamic' => 'strict',
            '_all' => ['enabled' => false],
            'properties' => [
                'id' => ['type' => 'keyword'],
                'tenderId' => ['type' => 'keyword'],
                'title' => ['type' => 'text'],
                'description' => ['type' => 'text'],
                'cdu-v' => ['type' => 'keyword'],
                'search' => ['type' => 'text'],
                'buyerRegion' => ['type' => 'keyword'],
                'procedureType' => ['type' => 'keyword'],
                'procedureStatus' => ['type' => 'keyword'],
                'budget' => ['type' => 'scaled_float', 'scaling_factor' => 100],
                'classification' => ['type' => 'keyword'],
            ],
        ];
        $jsonMap = json_encode($mapArr);
        $url = Yii::$app->params['elastic_url'];
        $index = Yii::$app->params['elastic_tenders_index'];
        $type = Yii::$app->params['elastic_tenders_type'];
        $elastic = new ElasticComponent($url, $index, $type);
        $result = $elastic->createMapping($jsonMap);
        return $result;
    }

    /**
     * indexing of tenders to elastic
     * @throws \ustudio\service_mandatory\components\elastic\ForbiddenHttpException
     * @throws \yii\web\HttpException
     */
    public function reindexItemsToElastic()
    {
        Yii::info("Indexing tenders", 'console-msg');
        $limit = 25;
        $offset = 0;
        $url = Yii::$app->params['elastic_url'];
        $index = Yii::$app->params['elastic_tenders_index'];
        $type = Yii::$app->params['elastic_tenders_type'];
        $elastic = new ElasticComponent($url, $index, $type);
        while (true) {
            try {
                // block the update of selected records in the database
                $transaction = Yii::$app->db_tenders->beginTransaction();
                $tenders = Yii::$app->db_tenders->createCommand("SELECT * FROM tenders FOR UPDATE LIMIT {$limit} OFFSET {$offset}")->queryAll();
                $cdu = ArrayHelper::map(Yii::$app->db_tenders->createCommand("SELECT * FROM cdu")->queryAll(), 'id', 'alias');
                $countTenders = count($tenders);
                if (!$countTenders) {
                    break;
                }
                $offset += $limit;
                foreach ($tenders as $tender) {
                    $docArr = $this->getDocForElastic($tender, $cdu);
                    if (!empty($docArr)) {
                        $result = $elastic->reindexTender($docArr);

                        if ($result['code'] != 200 && $result['code'] != 201 && $result['code'] != 100) {
                            Yii::error("Elastic indexing tenders error. Http-code: " . $result['code'], 'sync-info');
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
            Yii::info("Updated {$countTenders} tenders", 'console-msg');
            // delay 0.3 sec
            usleep(300000);
        }
    }

    /**
     * getting from response-field of a document for elastic
     *
     * @param $tender
     * @param $cdu
     * @return array
     */
    public function getDocForElastic($tender, $cdu) {
        $response = $tender['response'];
        $data = json_decode($response, 1);

        if (isset($cdu[$tender['cdu_id']]) && $cdu[$tender['cdu_id']] != self::TYPE_PROZORRO) {
            // ocds tender
            $records = $data['records'];
            $docArr = [];
            foreach ($records as $record) {
                if ($record['ocid'] == $tender['tender_id']) {
                    $tender_id = $record['ocid'];
                    $title = ($record['compiledRelease']['tender']['title']) ?? "";
                    $description = ($record['compiledRelease']['tender']['description']) ?? "";
                    $docArr = [
                        'tenderId' => $tender_id,
                        'title' => $title,
                        'description' => $description,
                        'cdu-v' => $cdu[$tender['cdu_id']] ?? '',
                    ];

                    break;
                }
            }
        } else {
            // prozorro tender
            $search = [];
            $classification = [];
            $id = '';
            $title = '';
            $description = '';
            $buyerRegion = '';
            $procedureType = '';
            $procedureStatus = '';
            $budget = '';
            $tenderId = $data['data']['id'];

            if (isset($data['data']['title']) && $data['data']['title']) {
                $title = $data['data']['title'];
                $search[] = $title;
            }

            if (isset($data['data']['description']) && $data['data']['description']) {
                $description = $data['data']['description'];
                $search[] = $description;
            }

            if (isset($data['data']['procuringEntity']['address']['region']) && $data['data']['procuringEntity']['address']['region']) {
                $buyerRegion = $data['data']['procuringEntity']['address']['region'];
            }

            if (isset($data['data']['tenderID']) && $data['data']['tenderID']) {
                $id = $data['data']['tenderID'];
            }

            if (isset($data['data']['procurementMethodType']) && $data['data']['procurementMethodType']) {
                $procedureType = $data['data']['procurementMethodType'];
            }

            if (isset($data['data']['status']) && $data['data']['status']) {
                $procedureStatus = $data['data']['status'];
            }

            if (isset($data['data']['value']['amount']) && $data['data']['value']['amount']) {
                $budget = $data['data']['value']['amount'];
            }

            if (isset($data['data']['status']) && $data['data']['status']) {
                $procedureStatus = $data['data']['status'];
            }

            if (isset($data['data']['lots']) && is_array($data['data']['lots'])) {
                foreach ($data['data']['lots'] as $lot) {
                    if (isset($lot['title']) && $lot['title']) {
                        $search[] = $lot['title'];
                    }

                    if (isset($lot['description']) && $lot['description']) {
                        $search[] = $lot['description'];
                    }
                }
            }

            if (isset($data['data']['items']) && is_array($data['data']['items'])) {
                foreach ($data['data']['items'] as $item) {
                    if (isset($item['description']) && $item['description']) {
                        $search[] = $item['description'];
                    }

                    if (isset($item['classification']['id']) && $item['classification']['id']) {
                        $classification[] = $item['classification']['id'];
                    }
                }
            }

            $docArr = [
                'id'              => $id,
                'tenderId'        => $tenderId,
                'title'           => $title,
                'description'     => $description,
                'cdu-v'           => $cdu[$tender['cdu_id']] ?? '',
                'search'          => $search,
                'buyerRegion'     => $buyerRegion,
                'procedureType'   => $procedureType,
                'procedureStatus' => $procedureStatus,
                'budget'          => $budget,
                'classification'  => $classification,
            ];
        }
        return $docArr;
    }
}