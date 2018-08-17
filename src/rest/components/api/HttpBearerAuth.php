<?php

namespace rest\components\api;

use yii\filters\auth\HttpBearerAuth as BasicHttpBearerAuth;

/**
 * Class HttpBearerAuth
 */
class HttpBearerAuth extends BasicHttpBearerAuth
{
    /**
     * @inheritdoc
     */
    public function authenticate($user, $request, $response)
    {
        return parent::authenticate($user, $request, $response);
    }
}
