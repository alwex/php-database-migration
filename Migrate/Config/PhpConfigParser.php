<?php
/**
 * Created by PhpStorm.
 * User: roman
 * Date: 7/24/17
 * Time: 09:45
 */

namespace Migrate\Config;

class PhpConfigParser extends BaseConfigParserInterface
{
    public function parse()
    {
        return require $this->configFile;
    }
}
