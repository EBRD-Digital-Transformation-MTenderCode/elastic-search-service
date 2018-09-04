<?php
namespace console\models;
use yii\db\ActiveRecord;
use Yii;
use yii\db\Exception;
use yii\web\ForbiddenHttpException;
use PDOException;

class Budgets extends ActiveRecord
{
    private $elastic_type;

    /**
     * Budgets constructor.
     * @param array $config
     * @throws ForbiddenHttpException
     */
    public function __construct(array $config = [])
    {
        $this->elastic_type = Yii::$app->params['elastic_budgets_type'] ?? "";
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
        return '{{%budgets}}';
    }

    /**
     * @return mixed|\yii\db\Connection
     */
    public static function getDb()
    {
        return Yii::$app->db_budgets;
    }

    /**
     * @throws \yii\web\HttpException
     */
    public function elasticMapping()
    {
        Yii::info("Mapping budgets", 'console-msg');
        $mapArr = [
            'dynamic' => 'strict',
            'properties' => [
                'ocid' => ['type' => 'keyword'],
                'title' => ['type' => 'text'],
                'description' => ['type' => 'text'],
            ]
        ];
        $jsonMap = json_encode($mapArr);
        $elastic = new Elastic();
        $result = $elastic->mapping($jsonMap, $this->elastic_type);
        return $result;
    }

    /**
     * indexing of budgets to elastic
     *
     * @return bool
     * @throws \yii\web\ForbiddenHttpException
     * @throws \yii\web\HttpException
     */
    public function indexItemsToElastic()
    {
        Yii::info("Indexing budgets", 'console-msg');
        $limit = 25;
        $offset = 0;
        $elastic = new Elastic();
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
                    $docArr = $this->getDocForElastic($budget);
                    if (!empty($docArr)) {
                        $result = $elastic->indexBudget($docArr, $this->elastic_type);

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
            Yii::info("Updated {$countBudgets} budgets", 'console-msg');
            // delay 0.3 sec
            usleep(300000);
        }
        return true;
    }


    /**
     * getting from response-field of a document for elastic
     *
     * @param $budget
     * @return array
     */
    public function getDocForElastic($budget) {
        $response = $budget['response'];
        $jsonArr = json_decode($response, 1);
        $records = $jsonArr['records'];
        $docArr = [];
        foreach ($records as $record) {
            if ($record['ocid'] == $budget['ocid']) {
                $ocid = $record['ocid'];
                $title = ($record['compiledRelease']['tender']['title']) ?? "";
                $description = ($record['compiledRelease']['tender']['description']) ?? "";
                $docArr = ['ocid' => $ocid, 'title' => $title, 'description' => $description];
                break;
            }
        }
        return $docArr;
    }

}