<?php
namespace console\models;
use yii\db\ActiveRecord;
use Yii;
use yii\web\ForbiddenHttpException;

class Tenders extends ActiveRecord
{
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
     * indexing of tenders to elastic
     *
     * @return bool
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
            $tenders = self::find()->asArray()->limit($limit)->offset($offset)->all();
            $countBudgets = count($tenders);
            if (!$countBudgets) {
                break;
            }
            $offset += $limit;
            foreach ($tenders as $tender) {
                $docArr = $this->getDocForElastic($tender);
                if (!empty($docArr)) {
                    $elastic->indexDoc("tenders", $docArr);
                } else {
                    //@todo error
                }
            }
            Yii::info("Updated {$countBudgets} tenders", 'console-msg');
            // delay 0.3 sec
            usleep(300000);
        }
        return true;
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
        $records = $jsonArr['records'];
        $docArr = [];
        foreach ($records as $record) {
            if ($record['ocid'] == $tender['ocid']) {
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