<?php

namespace rest\common\controllers\actions\Example;

use rest\components\api\actions\Action;

class ViewAllAction extends Action
{

    public function run()
    {
        print_r('here');
        die();
        return $result;
    }

}