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
    public $titlesOrDescriptions;
    public $titlesOrDescriptionsStrict;
    public $buyerRegion;
    public $procedureType;
    public $procedureStatus;
    public $budgetFrom;
    public $budgetTo;
    public $classification;
    public $buyerName;
    public $buyerCode;
    public $publicationDateFrom;
    public $publicationDateTo;
    public $enquiryPeriodFrom;
    public $enquiryPeriodTo;
    public $tenderPeriodFrom;
    public $tenderPeriodTo;
    public $auctionPeriodFrom;
    public $auctionPeriodTo;
    public $awardPeriodFrom;
    public $awardPeriodTo;

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
                    'titlesOrDescriptions',
                    'buyerRegion',
                    'procedureType',
                    'procedureStatus',
                    'classification',
                    'buyerName',
                    'buyerCode',
                    'publicationDateFrom',
                    'publicationDateTo',
                    'enquiryPeriodFrom',
                    'enquiryPeriodTo',
                    'tenderPeriodFrom',
                    'tenderPeriodTo',
                    'auctionPeriodFrom',
                    'auctionPeriodTo',
                    'awardPeriodFrom',
                    'awardPeriodTo',
                ],
                'string',
            ],
            [
                [
                    'publicationDateFrom',
                    'publicationDateTo',
                    'enquiryPeriodFrom',
                    'enquiryPeriodTo',
                    'tenderPeriodFrom',
                    'tenderPeriodTo',
                    'auctionPeriodFrom',
                    'auctionPeriodTo',
                    'awardPeriodFrom',
                    'awardPeriodTo',
                ],
                'datetime',
                'format' => 'php:' . \DateTime::RFC3339,
            ],
            [
                [
                    'buyerRegion',
                    'procedureType',
                    'procedureStatus',
                    'classification',
                    'buyerName',
                    'buyerCode',
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
            'buyerName',
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function fieldsRange()
    {
        return array_merge(parent::fieldsRange(), [
            'budgetFrom',
            'budgetTo',
            'publicationDateFrom',
            'publicationDateTo',
            'enquiryPeriodFrom',
            'enquiryPeriodTo',
            'tenderPeriodFrom',
            'tenderPeriodTo',
            'auctionPeriodFrom',
            'auctionPeriodTo',
            'awardPeriodFrom',
            'awardPeriodTo',
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