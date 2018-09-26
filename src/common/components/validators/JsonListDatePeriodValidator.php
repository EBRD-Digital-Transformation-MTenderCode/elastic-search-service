<?php
namespace common\components\validators;

use yii\debug\panels\ProfilingPanel;
use yii\validators\Validator;
use yii\validators\DateValidator;

/**
 * Class JsonListDatePeriodValidator
 * with conversion model attribute from json string to array
 */
class JsonListDatePeriodValidator extends Validator
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

        if (!is_array($list) || empty($list)) {
            $model->addError($attribute, 'Data should has list value');
            return null;
        }

        if (count($list) == 2) {
            $validator = new DateValidator();
            $validator->format = 'php:' . \DateTime::RFC3339;
            foreach ($list as $item) {
                if (is_string($item) && ($item == '' || $validator->validate($item))) {
                    $result[] = $item;
                } else {
                    $hasError = true;
                }
            }
        } else {
            $hasError = true;
        }

        if ($hasError) {
            $model->addError($attribute, 'Data should has list of two dates value');
        } else {
            $model->$attribute = $result;
        }
    }
}