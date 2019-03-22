<?php
/**
 * Created by PhpStorm.
 * User: aguidet
 * Date: 28/02/15
 * Time: 17:32
 */

namespace Migrate\Command;

use Migrate\Config\ConfigLocator;
use Migrate\Migration;
use Migrate\Utils\ArrayUtil;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AbstractEnvCommand extends AbstractCommand
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

    protected function checkEnv()
    {
        if (!file_exists(getcwd() . '/.php-database-migration/environments')) {
            throw new \RuntimeException("you are not in an initialized php-database-migration directory");
        }
    }

    protected function init(InputInterface $input, OutputInterface $output, $env = null)
    {
        $configDirectory = getcwd() . '/.php-database-migration/environments';
        $configLocator = new ConfigLocator($configDirectory);

        if ($env === null) {
            $env = $input->getArgument('env');
        }

        $parser = $configLocator->locate($env);

        $conf = $parser->parse();

        $this->config = $conf;

        $driver = ArrayUtil::get($conf['connection'], 'driver');
        $port = ArrayUtil::get($conf['connection'], 'port');
        $host = ArrayUtil::get($conf['connection'], 'host');
        $dbname = ArrayUtil::get($conf['connection'], 'database');
        $username = ArrayUtil::get($conf['connection'], 'username');
        $password = ArrayUtil::get($conf['connection'], 'password');
        $charset = ArrayUtil::get($conf['connection'], 'charset');

        $uri = $driver;

        if ($driver == 'sqlite') {
            $uri .= ":$dbname";
        } else {
            $uri .= ($dbname === null) ? '' : ":dbname=$dbname";
            $uri .= ($host === null) ? '' : ";host=$host";
            $uri .= ($port === null) ? '' : ";port=$port";
            $uri .= ($charset === null) ? '' : ";charset=$charset";
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
     * @param bool      $changeLogOnly
     */
    public function executeUpMigration(Migration $migration, $changeLogOnly = false)
    {
        $this->getDb()->beginTransaction();

        if ($changeLogOnly === false) {
            $result = $this->getDb()->exec($migration->getSqlUp());

            if ($result === false) {
                // error while executing the migration
                $errorInfo = "";
                $errorInfos = $this->getDb()->errorInfo();
                foreach ($errorInfos as $line) {
                    $errorInfo .= "\n$line";
                }
                $this->getDb()->rollBack();
                throw new \RuntimeException(sprintf(
                    "migration error, some SQL may be wrong\n\nid: %s\nfile: %s\n %s",
                    $migration->getId(),
                    $migration->getFile(),
                    $errorInfo
                ));
            }
        }

        $this->saveToChangelog($migration);
        $this->getDb()->commit();
    }

    /**
     * @param Migration $migration
     * @param bool      $changeLogOnly
     */
    public function executeDownMigration(Migration $migration, $changeLogOnly = false)
    {
        $this->getDb()->beginTransaction();

        if ($changeLogOnly === false) {
            $result = $this->getDb()->exec($migration->getSqlDown());

            if ($result === false) {
                // error while executing the migration
                $errorInfo = "";
                $errorInfos = $this->getDb()->errorInfo();
                foreach ($errorInfos as $line) {
                    $errorInfo .= "\n$line";
                }
                $this->getDb()->rollBack();
                throw new \RuntimeException(sprintf(
                    "migration error, some SQL may be wrong\n\nid: %s\nfile: %s\n",
                    $migration->getId(),
                    $migration->getFile(),
                    $errorInfo
                ));
            }
        }
        $this->removeFromChangelog($migration);
        $this->getDb()->commit();
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
        if ($only !== null) {
            if (! array_key_exists($only, $toExecute)) {
                throw new \RuntimeException("Impossible to execute migration $only!");
            }
            $theMigration = $toExecute[$only];
            $toExecute = array($theMigration->getId() => $theMigration);
        }

        $to = $input->getOption('to');
        if ($to !== null) {
            if (! array_key_exists($to, $toExecute)) {
                throw new \RuntimeException("Target migration $to does not exist or has already been executed/downed!");
            }

            $temp = $toExecute;
            $toExecute = array();
            foreach ($temp as $migration) {
                $toExecute[$migration->getId()] = $migration;
                if ($migration->getId() == $to) {
                    break;
                }
            }
        } elseif ($down && count($toExecute) > 1) {
            // WARNING DOWN SPECIAL TREATMENT
            // we dont want all the database to be downed because
            // of a bad command!
            $theMigration = array_shift($toExecute);
            $toExecute = array($theMigration->getId() => $theMigration);
        }

        return $toExecute;
    }
}
