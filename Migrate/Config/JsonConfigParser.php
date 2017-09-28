<?php

namespace Migrate\Config;

class JsonConfigParser extends BaseConfigParser
{
    public function parse()
    {
        return json_decode(file_get_contents($this->configFile), true);
    }
}
