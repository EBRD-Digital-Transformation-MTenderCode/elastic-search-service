<?php
namespace rest\modules\v1\models;

use Yii;
use common\components\validators\JsonListValidator;

/**
 * Class Tenders
 * @package rest\modules\v1\models
 */
class Tenders extends ElasticSearchModel
{
    public $id;
    public $title;
    public $description;
    public $tenderId;
    public $titelsOrDescriptions;
    public $titelsOrDescriptionsStrict;
    public $buyerRegion;
    public $procedureNumber;
    public $procedureType;
    public $procedureStatus;
    public $budgetFrom;
    public $budgetTo;
    public $classification;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [
                [
                    'id',
                    'title',
                    'description',
                    'tenderId',
                    'titelsOrDescriptions',
                    'buyerRegion',
                    'procedureType',
                    'procedureStatus',
                    'classification',
                ],
                'string',
            ],
            [
                [
                    'buyerRegion',
                    'procedureType',
                    'procedureStatus',
                    'classification'
                ],
                JsonListValidator::className(),
                'skipOnEmpty' => true,
            ],
            [
                [
                    'budgetFrom',
                    'budgetTo',
                ],
                'double',
            ],
            [
                'titelsOrDescriptionsStrict',
                'boolean',
            ],
            [
                'titelsOrDescriptionsStrict',
                'default',
                'value' => 0,
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function fieldsFullText()
    {
        return array_merge(parent::fieldsFullText(), ['titelsOrDescriptions', 'title', 'description']);
    }

    /**
     * @inheritdoc
     */
    public static function fieldsRange()
    {
        return array_merge(parent::fieldsRange(), ['budgetFrom', 'budgetTo']);
    }

    /**
     * @inheritdoc
     */
    public static function fieldsSystem()
    {
        return array_merge(parent::fieldsSystem(), ['titelsOrDescriptionsStrict']);
    }

    /**
     * @inheritdoc
     */
    public function search($searchAttributes)
    {
        $this->index = Yii::$app->params['elastic_tenders_index'];
        $this->type = Yii::$app->params['elastic_tenders_type'];

        return parent::search($searchAttributes);
    }
}