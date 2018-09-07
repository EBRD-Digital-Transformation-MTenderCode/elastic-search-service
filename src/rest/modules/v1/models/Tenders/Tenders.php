<?php

namespace rest\modules\v1\models\Tenders;


use rest\modules\v1\ElasticSearchModel;
use yii\base\Model;
use Yii;

class Tenders extends Model
{
    public $tender_id;
    public $title;
    public $description;
    public $search;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['tender_id', 'title', 'description', 'search'], 'string'],
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