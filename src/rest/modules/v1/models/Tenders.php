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
    public $entityId;
    public $title;
    public $description;
    public $tenderId;
    public $titlesOrDescriptions;
    public $titlesOrDescriptionsStrict;
    public $buyersRegions;
    public $deliveriesRegions;
    public $proceduresTypes;
    public $proceduresStatuses;
    public $amountFrom;
    public $amountTo;
    public $classifications;
    public $buyersNames;
    public $buyersIdentifiers;
    public $buyersTypes;
    public $buyersMainGeneralActivities;
    public $buyersMainSectoralActivities;
    public $periodPublishedFrom;
    public $periodPublishedTo;
    public $periodDeliveryFrom;
    public $periodDeliveryTo;
    public $periodEnquiryFrom;
    public $periodEnquiryTo;
    public $periodOfferFrom;
    public $periodOfferTo;
    public $periodAuctionFrom;
    public $periodAuctionTo;
    public $periodAwardFrom;
    public $periodAwardTo;

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
                    'title',
                    'description',
                    'tenderId',
                    'titlesOrDescriptions',
                    'buyersRegions',
                    'deliveriesRegions',
                    'proceduresTypes',
                    'proceduresStatuses',
                    'classifications',
                    'buyersNames',
                    'buyersIdentifiers',
                    'buyersTypes',
                    'buyersMainGeneralActivities',
                    'buyersMainSectoralActivities',
                    'periodPublishedFrom',
                    'periodPublishedTo',
                    'periodDeliveryFrom',
                    'periodDeliveryTo',
                    'periodEnquiryFrom',
                    'periodEnquiryTo',
                    'periodOfferFrom',
                    'periodOfferTo',
                    'periodAuctionFrom',
                    'periodAuctionTo',
                    'periodAwardFrom',
                    'periodAwardTo',
                ],
                'string',
            ],
            [
                [
                    'periodPublishedFrom',
                    'periodPublishedTo',
                    'periodDeliveryFrom',
                    'periodDeliveryTo',
                    'periodEnquiryFrom',
                    'periodEnquiryTo',
                    'periodOfferFrom',
                    'periodOfferTo',
                    'periodAuctionFrom',
                    'periodAuctionTo',
                    'periodAwardFrom',
                    'periodAwardTo',
                ],
                'datetime',
                'format' => 'php:' . \DateTime::RFC3339,
            ],
            [
                [
                    'buyersRegions',
                    'deliveriesRegions',
                    'proceduresTypes',
                    'proceduresStatuses',
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
            'title',
            'description',
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
            'periodPublishedFrom',
            'periodPublishedTo',
            'periodDeliveryFrom',
            'periodDeliveryTo',
            'periodEnquiryFrom',
            'periodEnquiryTo',
            'periodOfferFrom',
            'periodOfferTo',
            'periodAuctionFrom',
            'periodAuctionTo',
            'periodAwardFrom',
            'periodAwardTo',
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
        $this->index = Yii::$app->params['elastic_tenders_index'];
        $this->type = Yii::$app->params['elastic_tenders_type'];

        return parent::search($searchAttributes);
    }
}