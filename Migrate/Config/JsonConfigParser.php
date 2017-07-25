<?php

namespace Migrate\Config;

class JsonConfigParser extends BaseConfigParserInterface
{
    public function parse()
    {
        return json_decode(file_get_contents($this->configFile), true);
    }
}
