<?php
/**
 * Created by PhpStorm.
 * User: aguidet
 * Date: 28/02/15
 * Time: 17:32
 */

namespace Migrate\Command;


use Migrate\Utils\ArrayUtil;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class AbstractEnvCommand extends AbstractComand
{

    /**
     * @var \PDO
     */
    private $db;

    /**
     * @var array
     */
    private $config;

    /**
     * @return \PDO
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    public function getChangelogTable()
    {
        return ArrayUtil::get($this->getConfig(), 'changelog');
    }

    protected function init(InputInterface $input, OutputInterface $output, $env = null)
    {
        $configDirectory = array(getcwd() . '/.php-database-migration/environments');
        $locator = new FileLocator($configDirectory);

        if ($env == null) {
            $env = $input->getArgument('env');
        }

        $envFile = $locator->locate($env . '.yml');

        $loader = new Yaml();
        $conf = $loader->parse($envFile);

        $this->config = $conf;

        $driver = ArrayUtil::get($conf['connection'], 'driver');
        $port = ArrayUtil::get($conf['connection'], 'port');
        $host = ArrayUtil::get($conf['connection'], 'host');
        $dbname = ArrayUtil::get($conf['connection'], 'database');
        $username = ArrayUtil::get($conf['connection'], 'username');
        $password = ArrayUtil::get($conf['connection'], 'password');

        $uri = $driver;
        $uri .= ($dbname == null) ?: ":dbname=$dbname";
        $uri .= ($host == null) ?: ";host=$host";
        $uri .= ($port == null) ?: ";port=$port";

        $this->db = new \PDO(
            $uri,
            $username,
            $password,
            array()
        );

        $output->writeln('<info>connected</info>');
    }
}