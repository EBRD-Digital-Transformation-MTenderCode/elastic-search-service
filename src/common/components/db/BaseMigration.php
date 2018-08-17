<?php
namespace common\components\db;

use yii\db\Schema;
use yii\db\Migration;

class BaseMigration extends Migration
{

    const TABLE_OPTIONS = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';

}