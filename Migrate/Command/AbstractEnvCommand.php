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

    protected static $progressBarFormat = '%current%/%max% [%bar%] %percent% % [%message%]';

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

        if ($driver == 'sqlite') {
            $uri .= ":$dbname";
        }  else {
            $uri .= ($dbname == null) ? '' : ":dbname=$dbname";
            $uri .= ($host == null) ? '' : ";host=$host";
            $uri .= ($port == null) ? '' : ";port=$port";
        }
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

        $migrations = array();
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

    public function getToDownMigrations()
    {
        $locales = $this->getLocalMigrations();
        $remotes = $this->getRemoteMigrations();

        ksort($remotes);

        $remotes = array_reverse($remotes, true);

        return $remotes;
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

    public function removeFromChangelog(Migration $migration)
    {
        $sql = "DELETE FROM {$this->getChangelogTable()} WHERE id = {$migration->getId()}";
        $result = $this->getDb()->exec($sql);
        if (! $result) {
            throw new \RuntimeException("Impossible to delete migration from changelog table");
        }
    }

    /**
     * @param Migration $migration
     * @param bool $changeLogOnly
     */
    public function executeUpMigration(Migration $migration, $changeLogOnly = false)
    {
        if ($changeLogOnly === false) {
            $this->getDb()->query($migration->getSqlUp());
        }
        $this->saveToChangelog($migration);
    }

    /**
     * @param Migration $migration
     * @param bool $changeLogOnly
     */
    public function executeDownMigration(Migration $migration, $changeLogOnly = false)
    {
        if ($changeLogOnly === false) {
            $this->getDb()->query($migration->getSqlDown());
        }
        $this->removeFromChangelog($migration);
    }

    protected function filterMigrationsToExecute(InputInterface $input, OutputInterface $output)
    {

        $down = false;

        $toExecute = array();
        if (strpos($this->getName(), 'up') > 0) {
            $toExecute = $this->getToUpMigrations();
        } else {
            $down = true;
            $toExecute = $this->getToDownMigrations();
        }

        $only = $input->getOption('only');
        if ($only != null) {
            if (! array_key_exists($only, $toExecute)) {
                throw new \RuntimeException("Impossible to execute migration $only!");
            }
            $theMigration = $toExecute[$only];
            $toExecute = array($theMigration->getId() => $theMigration);
        }

        $to = $input->getOption('to');
        if ($to != null) {
            if (! array_key_exists($to, $toExecute)) {
                throw new \RuntimeException("Target migration $to does not exists or has already been executed/downed!");
            }

            $temp = $toExecute;
            $toExecute = array();
            foreach ($temp as $migration) {
                $toExecute[$migration->getId()] = $migration;
                if ($migration->getId() == $to) {
                    break;
                }
            }

        } else if ($down && count($toExecute) > 1) {
            // WARNING DOWN SPECIAL TREATMENT
            // we dont want all the database to be downed because
            // of a bad command!
            $theMigration = array_shift($toExecute);
            $toExecute = array($theMigration->getId() => $theMigration);
        }

        return $toExecute;
    }
}