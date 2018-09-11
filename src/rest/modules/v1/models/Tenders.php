<?php
namespace rest\modules\v1\models;

use Yii;
use yii\base\Model;

/**
 * Class Tenders
 * @package rest\modules\v1\models
 */
class Tenders extends Model
{
    public $tender_id;
    public $title;
    public $description;
    public $search;
    public $buyer_region;
    public $procedure_number;
    public $procedure_type;
    public $procedure_status;
    public $budget_from;
    public $budget_to;
    public $classification;
    public $pageSize;
    public $page;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [
                [
                    'tender_id',
                    'title',
                    'description',
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
                    'budget_from',
                    'budget_to',
                ],
                'double',
            ],
            [
                [
                    'pageSize',
                    'page',
                ],
                'integer',
                'min' => 1,
            ],
        ];
    }

    /**
     * @param $params
     * @return \rest\components\dataProviders\ArrayWithoutSortDataProvider
     * @throws \ustudio\service_mandatory\ServiceException
     */
    public function search($params)
    {
        $index = Yii::$app->params['elastic_tenders_index'];
        $type = Yii::$app->params['elastic_tenders_type'];

        $this->setAttributes($params);
        $searchAttributes = array_diff($this->getAttributes(), ['']);
        $elasticSearch = new ElasticSearchModel();
        return $elasticSearch->search($searchAttributes, $index, $type);
    }
}