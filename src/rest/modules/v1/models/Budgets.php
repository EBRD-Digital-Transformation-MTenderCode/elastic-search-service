<?php
namespace rest\modules\v1\models;

use Yii;

/**
 * Class Budgets
 * @package rest\modules\v1\models
 */
class Budgets extends ElasticSearchModel
{
    public $title;
    public $description;
    public $ocid;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['ocid', 'title', 'description'], 'string'],
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function fieldsFullText()
    {
        return array_merge(parent::fieldsFullText(), ['title', 'description']);
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
    public function search($searchAttributes)
    {
        $this->index = Yii::$app->params['elastic_budgets_index'];
        $this->type = Yii::$app->params['elastic_budgets_type'];

        return parent::search($searchAttributes);
    }
}