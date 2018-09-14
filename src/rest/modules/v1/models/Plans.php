<?php
namespace rest\modules\v1\models;

use Yii;

/**
 * Class Plans
 * @package rest\modules\v1\models
 */
class Plans extends ElasticSearchModel
{
    public $id;
    public $titlesOrDescriptions;
    public $titlesOrDescriptionsStrict;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['id', 'titlesOrDescriptions'], 'string'],
            [
                'titlesOrDescriptionsStrict', 'boolean',
            ],
            [
                'titlesOrDescriptionsStrict', 'default', 'value' => 0,
            ]
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function fieldsFullText()
    {
        return array_merge(parent::fieldsFullText(), ['titlesOrDescriptions']);
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