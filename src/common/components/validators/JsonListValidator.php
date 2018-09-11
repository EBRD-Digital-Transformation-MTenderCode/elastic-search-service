<?php
namespace common\components\validators;

use yii\validators\Validator;

/**
 * Class JsonListValidator
 * with conversion model attribute from json string to array
 */
class JsonListValidator extends Validator
{
    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute)
    {
        if (!is_string($model->$attribute)) {
            $model->addError($attribute, ucfirst($attribute) . ' could not be empty');
        }

        $result = [];
        $hasError = false;
        $list = json_decode($model->$attribute);

        if (json_last_error()) {
            $model->addError($attribute, 'Data should has JSON value');
            return null;
        }

        if (is_array($list) && !empty($list)) {
            foreach ($list as $item) {
                if (!is_string($item)) {
                    $hasError = true;
                } else {
                    $result[] = $item;
                }
            }
        } else {
            $hasError = true;
        }

        if ($hasError) {
            $model->addError($attribute, 'Data should has list value');
        } else {
            $model->$attribute = $result;
        }
    }
}