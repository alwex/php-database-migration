<?php
/**
 * Created by PhpStorm.
 * User: aguidet
 * Date: 28/02/15
 * Time: 17:32
 */

namespace Migrate\Command;


use Migrate\Migration;
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

    /**
     * @return array(Migration)
     */
    public function getLocalMigrations()
    {
        $fileList = scandir($this->getMigrationDir());
        $fileList = ArrayUtil::filter($fileList);

        foreach ($fileList as $file) {
            $migration = Migration::createFromFile($file, $this->getMigrationDir());
            $migrations[$migration->getId()] = $migration;
        }

        ksort($migrations);

        return $migrations;
    }

    /**
     * @return array(Migration)
     */
    public function getRemoteMigrations()
    {
        $migrations = array();
        $result = $this->getDb()->query("SELECT * FROM {$this->getChangelogTable()} ORDER BY id");
        if ($result) {
            foreach ($result as $row) {
                $migration = Migration::createFromRow($row, $this->getMigrationDir());
                $migrations[$migration->getId()] = $migration;
            }

            ksort($migrations);
        }
        return $migrations;
    }

    /**
     * @return array(Migration)
     */
    public function getRemoteAndLocalMigrations()
    {
        $local = $this->getLocalMigrations();
        $remote = $this->getRemoteMigrations();

        foreach ($remote as $aRemote) {
            $local[$aRemote->getId()] = $aRemote;
        }

        ksort($local);

        return $local;
    }

    public function getToUpMigrations()
    {
        $locales = $this->getLocalMigrations();
        $remotes = $this->getRemoteMigrations();

        foreach ($remotes as $remote) {
            unset($locales[$remote->getId()]);
        }

        ksort($locales);

        return $locales;
    }

    public function saveToChangelog(Migration $migration)
    {
        $appliedAt = date('Y-m-d H:i:s');
        $sql = "INSERT INTO {$this->getChangelogTable()}
          (id, version, applied_at, description)
          VALUES
          ({$migration->getId()},'{$migration->getVersion()}','{$appliedAt}','{$migration->getDescription()}');
        ";
        $result = $this->getDb()->exec($sql);

        if (! $result) {
            throw new \RuntimeException("changelog table has not been initialized");
        }
    }
}