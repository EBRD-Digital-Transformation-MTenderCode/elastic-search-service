<?php
use yii\db\Connection;

return [
    'components' => [
        'db_budgets' => [
            'class' => Connection::class,
            'dsn' => 'pgsql:host={{environments_DB_BUDGETS_HOST}};port=5432;dbname={{environments_DB_BUDGETS_NAME}}',
            'username' => '{{environments_DB_USERNAME}}',
            'password' => '{{environments_DB_PASSWORD}}',
            'charset' => 'utf8',
        ],
        'db_tenders' => [
            'class' => Connection::class,
            'dsn' => 'pgsql:host={{environments_DB_TENDERS_HOST}};port=5432;dbname={{environments_DB_TENDERS_NAME}}',
            'username' => '{{environments_DB_USERNAME}}',
            'password' => '{{environments_DB_PASSWORD}}',
            'charset' => 'utf8',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'viewPath' => '@common/mail',
            // send all mails to a file by default. You have to set
            // 'useFileTransport' to false and configure a transport
            // for the mailer to send real emails.
            'useFileTransport' => true,
        ],
    ],
];
