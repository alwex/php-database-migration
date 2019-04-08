<?php
/**
 * User: aguidet
 * Date: 02/03/15
 * Time: 17:08
 */

namespace Command;


use Migrate\Command\DownCommand;
use Migrate\Command\StatusCommand;
use Migrate\Command\UpCommand;
use Migrate\Test\Command\AbstractCommandTester;
use Migrate\Utils\InputStreamUtil;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Tester\CommandTester;

class OptionTest extends AbstractCommandTester
{
    public static $application;

    public function setUp()
    {
        $this->cleanEnv();
        $this->createEnv();
        $this->initEnv();

        $this->createMigration('0', "CREATE TABLE test (id INTEGER, thevalue TEXT);",   "DROP TABLE test;");
        $this->createMigration('1', "SELECT 1",                                         "DELETE FROM test WHERE id = 1;");
        $this->createMigration('2', "INSERT INTO test VALUES (2, 'two');",              "DELETE FROM test WHERE id = 2;");

        self::$application = new Application();
        self::$application->add(new UpCommand());
        self::$application->add(new DownCommand());
        self::$application->add(new StatusCommand());
    }

    public function tearDown()
    {
        $this->cleanEnv();
    }

    public function testUpAllPendingMigrationsInMinimal()
    {
        $command = self::$application->find('migrate:up');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command' => $command->getName(),
            'env' => 'minimal',
            '--database' => 'migrate_test',
            '--driver' => 'sqlite'
        ));

        $expected =<<<EXPECTED
connected
0/3 [>---------------------------] 0 % []
1/3 [=========>------------------] 33 % [migration]
2/3 [==================>---------] 66 % [migration]
3/3 [============================] 100 % [migration]

EXPECTED;

        $this->assertEquals($expected, $commandTester->getDisplay());
    }



        public function testDownLastMigrationInMinimal()
    {

        $command = self::$application->find('migrate:down');
        $commandTester = new CommandTester($command);

        /* @var $question QuestionHelper */
        $question = $command->getHelper('question');
        $question->setInputStream(InputStreamUtil::type("yes\n"));

        $commandTester->execute(array(
            'command' => $command->getName(),
            'env' => 'testing',
            '--database' => 'migrate_test',
            '--driver' => 'sqlite'
        ));

        $expected =<<<EXPECTED
connected
Are you sure? (yes/no) [no]: your database is already up to date

EXPECTED;

        $this->assertEquals($expected, $commandTester->getDisplay());
    }




}
