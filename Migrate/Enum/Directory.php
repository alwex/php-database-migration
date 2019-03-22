<?php
/**
 * Created by PhpStorm.
 * User: aguidet
 * Date: 01/03/15
 * Time: 01:41
 */

namespace Migrate\Enum;

class Directory
{

    public static $appDirectory = '.php-database-migration';

    public static function getEnvPath()
    {
        return self::$appDirectory . '/environments';
    }

    public static function getMigrationsPath()
    {
        return self::$appDirectory . '/migrations';
    }
}
