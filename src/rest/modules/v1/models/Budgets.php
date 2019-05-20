<?php
namespace rest\modules\v1\models;

use Yii;
use common\components\validators\JsonListValidator;
use common\components\validators\JsonListDatePeriodValidator;

/**
 * Class Budgets
 * @package rest\modules\v1\models
 */
class Budgets extends ElasticSearchModel
{
    public $id;
    public $entityId;
    public $titlesOrDescriptions;
    public $titlesOrDescriptionsStrict;
    public $buyersRegions;
    public $budgetStatuses;
    public $amountFrom;
    public $amountTo;
    public $classifications;
    public $periodPlanning;
    public $buyersNames;
    public $buyersIdentifiers;
    public $buyersTypes;
    public $buyersMainGeneralActivities;
    public $buyersMainSectoralActivities;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [
                [
                    'id',
                    'entityId',
                    'titlesOrDescriptions',
                    'buyersRegions',
                    'budgetStatuses',
                    'classifications',
                    'periodPlanning',
                    'buyersNames',
                    'buyersIdentifiers',
                    'buyersTypes',
                    'buyersMainGeneralActivities',
                    'buyersMainSectoralActivities',
                ],
                'string',
            ],
            [
                [
                    'periodPlanning',
                ],
                JsonListDatePeriodValidator::className(),
                'skipOnEmpty' => true,
            ],
            [
                [
                    'buyersRegions',
                    'budgetStatuses',
                    'classifications',
                    'buyersNames',
                    'buyersIdentifiers',
                    'buyersTypes',
                    'buyersMainGeneralActivities',
                    'buyersMainSectoralActivities',
                ],
                JsonListValidator::className(),
                'skipOnEmpty' => true,
            ],
            [
                [
                    'amountFrom',
                    'amountTo',
                ],
                'double',
            ],
            [
                'titlesOrDescriptionsStrict',
                'boolean',
                'trueValue' => 'true',
                'falseValue' => 'false',
                'strict' => true,
            ],
            [
                'titlesOrDescriptionsStrict',
                'default',
                'value' => 'false',
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function fieldsFullText()
    {
        return array_merge(parent::fieldsFullText(), [
            'titlesOrDescriptions',
            'buyersNames',
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function fieldsRange()
    {
        return array_merge(parent::fieldsRange(), [
            'amountFrom',
            'amountTo',
            'periodPlanning',
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function fieldsSystem()
    {
        return array_merge(parent::fieldsSystem(), ['titlesOrDescriptionsStrict']);
    }

    /**
     * @inheritdoc
     */
    public function search($searchAttributes, $sortAttribute = 'modifiedDate')
    {
        $this->index = Yii::$app->params['elastic_budgets_index'];
        $this->type = Yii::$app->params['elastic_budgets_type'];

        return parent::search($searchAttributes);
    }
}