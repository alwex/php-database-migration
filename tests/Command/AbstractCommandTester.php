<?php
/**
 * User: aguidet
 * Date: 02/03/15
 * Time: 15:19
 */

namespace Migrate\Test\Command;

use Migrate\Command\AddEnvCommand;
use Migrate\Command\InitCommand;
use Migrate\Enum\Directory;
use Migrate\Utils\InputStreamUtil;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Tester\CommandTester;

class AbstractCommandTester extends \PHPUnit_Framework_TestCase
{
    public static $env = 'testing';
    public static $driver = 'sqlite';
    public static $bddName = 'migrate_test';
    public static $username = 'aguidet';
    public static $password = 'aguidet';
    public static $host = 'localhost';
    public static $port = '5432';

    public function cleanEnv()
    {
        exec("rm -rf .php-database-migration");

        if (file_exists('test.sqlite')) {
            exec("rm test.sqlite");
        }
    }

    public function createEnv($format = 'yml')
    {
        $application = new Application();
        $application->add(new AddEnvCommand());

        $command = $application->find('migrate:addenv');
        $commandTester = new CommandTester($command);

        $pdoDrivers = pdo_drivers();
        $driverKey = array_search('sqlite', $pdoDrivers);

        /* @var $question QuestionHelper */
        $question = $command->getHelper('question');
        $question->setInputStream(InputStreamUtil::type("testing\n$driverKey\ntest.sqlite\n\n\n\n\n\nchangelog\nvim\n"));

        $commandTester->execute(array('command' => $command->getName(), 'format' => $format));
    }

    public function initEnv()
    {
        $application = new Application();
        $application->add(new InitCommand());

        $command = $application->find('migrate:init');
        $commandTester = new CommandTester($command);

        $commandTester->execute(array(
            'command' => $command->getName(),
            'env' => 'testing'
        ));
    }

    public function createMigration($timestamp, $sqlUp, $sqlDown)
    {
        $filename = Directory::getMigrationsPath() . '/' . $timestamp . '_migration.sql';

        $content =<<<SQL
--// unit testing migration
-- Migration SQL that makes the change goes here.
$sqlUp

-- @UNDO
-- SQL to undo the change goes here.
$sqlDown

SQL;

        file_put_contents($filename, $content);
    }
}