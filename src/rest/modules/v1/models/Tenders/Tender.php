<?php
namespace rest\modules\v1\models\Tenders;

use Yii;
use yii\db\ActiveRecord;

/**
 * Class Tender
 * @package common\models
 */
class Tender extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%tenders}}';
    }
}
