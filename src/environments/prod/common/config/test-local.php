<?php
return yii\helpers\ArrayHelper::merge(
    require(__DIR__ . '/main.php'),
    require(__DIR__ . '/main-local.php'),
    require(__DIR__ . '/test.php'),
    [
        'components' => [
            'db' => [
                'dsn' => 'pgsql:host={{environments_DB_HOST}};dbname={{environments_DB_NAME_TEST}}',
            ]
        ],
    ]
);
