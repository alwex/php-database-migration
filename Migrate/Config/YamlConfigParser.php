<?php
/**
 * Created by PhpStorm.
 * User: roman
 * Date: 7/24/17
 * Time: 09:41
 */

namespace Migrate\Config;

use Symfony\Component\Yaml\Yaml;

class YamlConfigParser extends BaseConfigParser
{
    public function parse()
    {
        return Yaml::parse(file_get_contents($this->configFile));
    }
}
