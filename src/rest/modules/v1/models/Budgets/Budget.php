<?php
namespace rest\modules\v1\models\Budgets;

use Yii;
use yii\db\ActiveRecord;

/**
 * Class Budgets
 * @package common\models\Budgets
 */
class Budget extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%budgets}}';
    }
}
