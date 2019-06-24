<?php
namespace rest\modules\v1\models;

use Yii;
use common\components\validators\JsonListDatePeriodValidator;

/**
 * Class Decisions
 * @package rest\modules\v1\models
 */
class Decisions extends ElasticSearchModel
{
    public $id;
    public $NrProcedurii;
    public $periodRegistered;

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
                    'periodRegistered',
                ],
                'string',
            ],
            [
                [
                    'periodRegistered',
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
        return array_merge(parent::fieldsRange(), ['periodRegistered']);
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
        $this->index = Yii::$app->params['elastic_decisions_index'];
        $this->type = Yii::$app->params['elastic_decisions_type'];
        $this->sortAttribute = 'registrationDate';
        $this->sortOrder = 'asc';

        return parent::search($searchAttributes);
    }
}