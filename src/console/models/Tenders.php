<?php
namespace console\models;

use Yii;
use yii\db\Exception;
use yii\db\ActiveRecord;
use yii\web\ForbiddenHttpException;
use PDOException;

/**
 * Class Tenders
 * @package console\models
 */
class Tenders extends ActiveRecord
{
    const TYPE_PROZORRO = 'mtender1';

    private $elastic_type;

    /**
     * Tenders constructor.
     * @param array $config
     * @throws ForbiddenHttpException
     */
    public function __construct(array $config = [])
    {
        $this->elastic_type = Yii::$app->params['elastic_tenders_type'] ?? "";
        if (!$this->elastic_type) {
            throw new ForbiddenHttpException("Elastic params not set.");
        }

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%tenders}}';
    }

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
            ]
        ];
        $jsonMap = json_encode($mapArr);
        $elastic = new Elastic();
        $result = $elastic->mapping($jsonMap, $this->elastic_type);
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
        $elastic = new Elastic();
        while (true) {
            try {
                // block the update of selected records in the database
                $transaction = Yii::$app->db_tenders->beginTransaction();
                $tenders = Yii::$app->db_tenders->createCommand("SELECT * FROM tenders FOR UPDATE LIMIT {$limit} OFFSET {$offset}")->queryAll();
                $countBudgets = count($tenders);
                if (!$countBudgets) {
                    break;
                }
                $offset += $limit;
                foreach ($tenders as $tender) {
                    $docArr = $this->getDocForElastic($tender);
                    if (!empty($docArr)) {
                        $result = $elastic->indexTender($docArr, $this->elastic_type);

                        if ($result['code'] != 200 && $result['code'] != 201 && $result['code'] != 100) {
                            Yii::error("Elastic indexing budgets error. Http-code: " . $result['code'], 'sync-info');
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
            Yii::info("Updated {$countBudgets} tenders", 'console-msg');
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
        $jsonArr = json_decode($response, 1);

        if ($tender['cdu-v'] != self::TYPE_PROZORRO) {
            // ocds tender
            $records = $jsonArr['records'];
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
            $tender_id = $jsonArr['data']['id'];
            $title = $jsonArr['data']['title'] ?? '';
            $description = $jsonArr['data']['description'] ?? '';
            $docArr = [
                'tender_id' => $tender_id,
                'title' => $title,
                'description' => $description,
                'cdu-v' => $tender['cdu-v'],
            ];
        }
        return $docArr;
    }

}