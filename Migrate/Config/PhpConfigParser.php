<?php
/**
 * Created by PhpStorm.
 * User: roman
 * Date: 7/24/17
 * Time: 09:45
 */

namespace Migrate\Config;

class PhpConfigParser extends BaseConfigParser
{
    public function parse()
    {
        return include $this->configFile;
    }
}
