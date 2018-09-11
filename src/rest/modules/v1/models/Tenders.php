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
    public $tender_id;
    public $search;
    public $search_strict;
    public $buyer_region;
    public $procedure_number;
    public $procedure_type;
    public $procedure_status;
    public $budget_from;
    public $budget_to;
    public $classification;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [
                [
                    'tender_id',
                    'search',
                    'buyer_region',
                    'procedure_number',
                    'procedure_type',
                    'procedure_status',
                    'classification',
                ],
                'string',
            ],
            [
                [
                    'buyer_region',
                    'procedure_type',
                    'procedure_status',
                    'classification'
                ],
                JsonListValidator::className(),
                'skipOnEmpty' => true,
            ],
            [
                [
                    'budget_from',
                    'budget_to',
                ],
                'double',
            ],
            [
                'search_strict',
                'boolean',
            ],
            [
                'search_strict',
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
        return array_merge(parent::fieldsFullText(), ['search']);
    }

    /**
     * @inheritdoc
     */
    public static function fieldsRange()
    {
        return array_merge(parent::fieldsRange(), []);
    }

    /**
     * @inheritdoc
     */
    public static function fieldsSystem()
    {
        return array_merge(parent::fieldsSystem(), ['search_strict']);
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