<?php
/**
 * Created by PhpStorm.
 * User: roman
 * Date: 7/24/17
 * Time: 09:49
 */

namespace Migrate\Config;

class ConfigLocator
{
    public static $SUPPORTED_PARSERS = array(
        'yml' => '\Migrate\Config\YamlConfigParser',
        'json' => '\Migrate\Config\JsonConfigParser',
        'php' => '\Migrate\Config\PhpConfigParser'
    );

    private $configPath;

    public function __construct($configPath)
    {
        $this->configPath = $configPath;
    }

    public function locate($nameWithoutExt)
    {
        foreach (array_keys(self::$SUPPORTED_PARSERS) as $format) {
            $path = $this->configPath . '/' . $nameWithoutExt . '.' . $format;
            if (file_exists($path)) {
                $parserClass = self::$SUPPORTED_PARSERS[$format];
                return new $parserClass($path);
            }
        }
        throw new \RuntimeException(
            sprintf('Environment file %s does not exist or file not supported', $nameWithoutExt)
        );
    }
}
