<?php
namespace rest\modules\v1\models;

use Yii;
use common\components\validators\JsonListValidator;
use common\components\validators\JsonListDatePeriodValidator;

/**
 * Class Plans
 * @package rest\modules\v1\models
 */
class Plans extends ElasticSearchModel
{
    public $cdb;
    public $id;
    public $entityId;
    public $tenderId;
    public $titlesOrDescriptions;
    public $titlesOrDescriptionsStrict;
    public $buyersRegions;
    public $deliveriesRegions;
    public $proceduresTypes;
    public $amountFrom;
    public $amountTo;
    public $classifications;
    public $buyersNames;
    public $buyersIdentifiers;
    public $buyersTypes;
    public $buyersMainGeneralActivities;
    public $buyersMainSectoralActivities;
    public $periodPublished;
    public $periodDelivery;
    public $periodEnquiry;
    public $periodOffer;
    public $periodAuction;
    public $periodAward;
    public $pins;

    public $tags;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [
                [
                    'cdb',
                    'id',
                    'entityId',
                    'tenderId',
                    'titlesOrDescriptions',
                    'buyersRegions',
                    'deliveriesRegions',
                    'proceduresTypes',
                    'classifications',
                    'buyersNames',
                    'buyersIdentifiers',
                    'buyersTypes',
                    'buyersMainGeneralActivities',
                    'buyersMainSectoralActivities',
                    'periodPublished',
                    'periodDelivery',
                    'periodEnquiry',
                    'periodOffer',
                    'periodAuction',
                    'periodAward',
                    'pins',
                ],
                'string',
            ],
            [
                [
                    'periodPublished',
                    'periodDelivery',
                    'periodEnquiry',
                    'periodOffer',
                    'periodAuction',
                    'periodAward',
                ],
                JsonListDatePeriodValidator::className(),
                'skipOnEmpty' => true,
            ],
            [
                [
                    'buyersRegions',
                    'deliveriesRegions',
                    'proceduresTypes',
                    'classifications',
                    'buyersNames',
                    'buyersIdentifiers',
                    'buyersTypes',
                    'buyersMainGeneralActivities',
                    'buyersMainSectoralActivities',
                    'tags',
                    'pins',
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
            'periodPublished',
            'periodDelivery',
            'periodEnquiry',
            'periodOffer',
            'periodAuction',
            'periodAward',
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function fieldsSystem()
    {
        return array_merge(parent::fieldsSystem(), [
            'titlesOrDescriptionsStrict',
            'pin',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function search($searchAttributes, $sortAttribute = 'modifiedDate')
    {
        $this->index = Yii::$app->params['elastic_plans_index'];
        $this->type = Yii::$app->params['elastic_plans_type'];

        return parent::search($searchAttributes);
    }
}