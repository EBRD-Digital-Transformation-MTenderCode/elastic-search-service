<?php
namespace rest\modules\v1\models;

use Yii;
use common\components\validators\JsonListValidator;

/**
 * Class Plans
 * @package rest\modules\v1\models
 */
class Plans extends ElasticSearchModel
{
    public $id;
    public $entityId;
    public $proceduresTypes;
    public $amountFrom;
    public $amountTo;
    public $titlesOrDescriptions;
    public $titlesOrDescriptionsStrict;
    public $classifications;
    public $periodTenderFrom;
    public $periodDeliveryFrom;
    public $periodDeliveryTo;
    public $buyersNames;
    public $buyersIdentifiers;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['id', 'entityId', 'titlesOrDescriptions', 'proceduresTypes', 'buyersNames', 'buyersIdentifiers'], 'string'],
            [
                'titlesOrDescriptionsStrict', 'boolean',
                'trueValue' => 'true',
                'falseValue' => 'false',
                'strict' => true,
            ],
            [
                ['proceduresTypes', 'classifications', 'buyersNames', 'buyersIdentifiers'],
                JsonListValidator::className(),
                'skipOnEmpty' => true,
            ],
            [['amountFrom', 'amountTo'], 'double'],
            ['titlesOrDescriptionsStrict', 'default', 'value' => 'false'],
            [
                [
                    'periodTenderFrom', 'periodDeliveryFrom', 'periodDeliveryTo'
                ],
                'datetime',
                'format' => 'php:' . \DateTime::RFC3339,
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function fieldsFullText()
    {
        return array_merge(parent::fieldsFullText(), ['titlesOrDescriptions', 'buyersNames',]);
    }

    /**
     * @inheritdoc
     */
    public static function fieldsRange()
    {
        return array_merge(parent::fieldsRange(), [
            'amountFrom',
            'amountTo',
            'periodTenderFrom',
            'periodDeliveryFrom',
            'periodDeliveryTo',
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
    public function search($searchAttributes)
    {
        $this->index = Yii::$app->params['elastic_plans_index'];
        $this->type = Yii::$app->params['elastic_plans_type'];

        return parent::search($searchAttributes);
    }
}