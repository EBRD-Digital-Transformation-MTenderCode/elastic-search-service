<?php
namespace rest\modules\v1\models;

use Yii;

/**
 * Class Decisions
 * @package rest\modules\v1\models
 */
class Decisions extends ElasticSearchModel
{
    public $id;
    public $NrProcedurii;

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
                ],
                'string',
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
        return array_merge(parent::fieldsRange(), []);
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
    public function search($searchAttributes, $sortAttribute = 'modifiedDate')
    {
        $this->index = Yii::$app->params['elastic_decisions_index'];
        $this->type = Yii::$app->params['elastic_decisions_type'];

        return parent::search($searchAttributes, 'timestamp');
    }
}