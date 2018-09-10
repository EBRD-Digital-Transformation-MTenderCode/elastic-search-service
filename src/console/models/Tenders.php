<?php
namespace console\models;

use Yii;
use yii\db\Exception;
use PDOException;

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
     * @throws \yii\web\HttpException
     */
    public function elasticMapping()
    {
        Yii::info("Mapping tenders", 'console-msg');
        $mapArr = [
            'dynamic' => 'strict',
            'properties' => [
                'tender_id' => ['type' => 'keyword'],
                'title' => ['type' => 'text'],
                'description' => ['type' => 'text'],
                'cdu-v' => ['type' => 'keyword'],
                'search' => ['type' => 'text'],
            ]
        ];
        $jsonMap = json_encode($mapArr);
        $url = Yii::$app->params['elastic_url'];
        $index = Yii::$app->params['elastic_tenders_index'];
        $type = Yii::$app->params['elastic_tenders_type'];
        $elastic = new Elastic($url, $index, $type);
        $result = $elastic->mapping($jsonMap);
        return $result;
    }

    /**
     * indexing of tenders to elastic
     *
     * @throws \yii\web\ForbiddenHttpException
     * @throws \yii\web\HttpException
     */
    public function indexItemsToElastic()
    {
        Yii::info("Indexing tenders", 'console-msg');
        $limit = 25;
        $offset = 0;
        $url = Yii::$app->params['elastic_url'];
        $index = Yii::$app->params['elastic_tenders_index'];
        $type = Yii::$app->params['elastic_tenders_type'];
        $elastic = new Elastic($url, $index, $type);
        while (true) {
            try {
                // block the update of selected records in the database
                $transaction = Yii::$app->db_tenders->beginTransaction();
                $tenders = Yii::$app->db_tenders->createCommand('SELECT t.*, c.alias AS "cdu-v" FROM tenders t LEFT JOIN cdu c ON c.id = t.cdu_id FOR UPDATE LIMIT {$limit} OFFSET {$offset}')->queryAll();
                $countTenders = count($tenders);
                if (!$countTenders) {
                    break;
                }
                $offset += $limit;
                foreach ($tenders as $tender) {
                    $docArr = $this->getDocForElastic($tender);
                    if (!empty($docArr)) {
                        $result = $elastic->indexTender($docArr);

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
     * @return array
     */
    public function getDocForElastic($tender) {
        $response = $tender['response'];
        $data = json_decode($response, 1);

        if ($tender['cdu-v'] != self::TYPE_PROZORRO) {
            // ocds tender
            $records = $data['records'];
            $docArr = [];
            foreach ($records as $record) {
                if ($record['ocid'] == $tender['tender_id']) {
                    $tender_id = $record['ocid'];
                    $title = ($record['compiledRelease']['tender']['title']) ?? "";
                    $description = ($record['compiledRelease']['tender']['description']) ?? "";
                    $docArr = [
                        'tender_id' => $tender_id,
                        'title' => $title,
                        'description' => $description,
                        'cdu-v' => $tender['cdu-v'],
                    ];

                    break;
                }
            }
        } else {
            // prozorro tender
            $search = [];
            $title = '';
            $description = '';
            $tender_id = $data['data']['id'];

            if (isset($data['data']['title']) && $data['data']['title']) {
                $title = $data['data']['title'];
                $search[] = $title;
            }

            if (isset($data['data']['description']) && $data['data']['description']) {
                $description = $data['data']['description'];
                $search[] = $description;
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
                }
            }

            $docArr = [
                'tender_id' => $tender_id,
                'title' => $title,
                'description' => $description,
                'cdu-v' => $tender['cdu-v'],
                'search' => $search,
            ];
        }
        return $docArr;
    }
}