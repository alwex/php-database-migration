<?php

namespace Migrate\Test\Command;

use SebastianBergmann\GlobalState\RuntimeException;

class EnvFormatsTest extends AbstractCommandTester
{
    public function testYamlFormat()
    {
        $this->createEnv('yml');
        $this->cleanEnv();
    }

    public function testJsonFormat()
    {
        $this->createEnv('json');
        $this->cleanEnv();
    }

    public function testPhpFormat()
    {
        $this->createEnv('php');
        $this->cleanEnv();
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Invalid file format: xml
     */
    public function testUnsupportedXmlFormat()
    {
        $this->createEnv('xml');
        $this->cleanEnv();
    }
}
