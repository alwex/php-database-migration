<?php
/**
 * Created by PhpStorm.
 * User: roman
 * Date: 7/24/17
 * Time: 09:47
 */

namespace Migrate\Config;

abstract class BaseConfigParser implements ConfigParser
{
    protected $configFile;

    public function __construct($configFile)
    {
        $this->configFile = $configFile;
    }

    abstract public function parse();
}
