<?php
namespace rest\modules\v1\models;

use Yii;
use common\components\validators\JsonListDatePeriodValidator;

/**
 * Class Complaints
 * @package rest\modules\v1\models
 */
class Complaints extends ElasticSearchModel
{
    public $id;
    public $NrProcedurii;
    public $periodTimestamp;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [
                [
                    'id',
                    'NrProcedurii',
                    'periodTimestamp',
                ],
                'string',
            ],
            [
                [
                    'periodTimestamp',
                ],
                JsonListDatePeriodValidator::className(),
                'skipOnEmpty' => true,
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function fieldsFullText()
    {
        return array_merge(parent::fieldsFullText(), []);
    }

    /**
     * @inheritdoc
     */
    public static function fieldsRange()
    {
        return array_merge(parent::fieldsRange(), ['periodTimestamp']);
    }

    /**
     * @inheritdoc
     */
    public static function fieldsSystem()
    {
        return array_merge(parent::fieldsSystem(), []);
    }

    /**
     * @inheritdoc
     */
    public function search($searchAttributes)
    {
        $this->index = Yii::$app->params['elastic_complaints_index'];
        $this->type = Yii::$app->params['elastic_complaints_type'];
        $this->sortAttribute = 'timestamp';
        $this->sortOrder = 'asc';

        return parent::search($searchAttributes);
    }
}