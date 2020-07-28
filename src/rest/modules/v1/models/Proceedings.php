<?php
namespace rest\modules\v1\models;

use Yii;
use common\components\validators\JsonListDatePeriodValidator;

/**
 * Class Proceedings
 * @package rest\modules\v1\models
 */
class Proceedings extends ElasticSearchModel
{
    public $ocid;
    public $date;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [
                [
                    'ocid',
                    'date',
                ],
                'string',
            ],
            [
                [
                    'date',
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
        return array_merge(parent::fieldsRange(), ['date']);
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
        $this->index = Yii::$app->params['elastic_proceedings_index'];
        $this->type = Yii::$app->params['elastic_proceedings_type'];
        $this->sortAttribute = 'date';
        $this->sortOrder = 'asc';

        return parent::search($searchAttributes);
    }
}