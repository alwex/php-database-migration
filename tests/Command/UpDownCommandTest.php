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

class UpDownCommandTest extends AbstractCommandTester
{
    public function setUp()
    {
        $this->cleanEnv();
        $this->createEnv();
        $this->initEnv();
    }

    public function tearDown()
    {
        $this->cleanEnv();
    }

    public function testUpAllPendingMigrations()
    {
        $this->createMigration('0', "CREATE TABLE test (id INTEGER, thevalue TEXT);",   "DROP TABLE test;");
        $this->createMigration('1', "INSERT INTO test VALUES (1, 'one');",              "DROP TABLE test;");
        $this->createMigration('2', "INSERT INTO test VALUES (2, 'two');",              "DROP TABLE test;");

        $application = new Application();
        $application->add(new UpCommand());

        $command = $application->find('migrate:up');
        $commandTester = new CommandTester($command);

        $commandTester->execute(array(
            'command' => $command->getName(),
            'env' => 'testing'
        ));

        $expected =<<<EXPECTED
connected
0/3 [>---------------------------] 0 % []
1/3 [=========>------------------] 33 % [migration]
2/3 [==================>---------] 66 % [migration]
3/3 [============================] 100 % [migration]

EXPECTED;

        $this->assertEquals($commandTester->getDisplay(), $expected);
    }

    public function testDownLastMigration()
    {
        $this->createMigration('0', "CREATE TABLE test (id INTEGER, thevalue TEXT);",   "DROP TABLE test;");
        $this->createMigration('1', "INSERT INTO test VALUES (1, 'one');",              "DROP TABLE test;");
        $this->createMigration('2', "INSERT INTO test VALUES (2, 'two');",              "DROP TABLE test;");

        $application = new Application();
        $application->add(new UpCommand());
        $application->add(new DownCommand());

        $command = $application->find('migrate:up');
        $commandTester = new CommandTester($command);

        $commandTester->execute(array(
            'command' => $command->getName(),
            'env' => 'testing'
        ));



        $command = $application->find('migrate:down');
        $commandTester = new CommandTester($command);

        /* @var $question QuestionHelper */
        $question = $command->getHelper('question');
        $question->setInputStream(InputStreamUtil::type("yes\n"));

        $commandTester->execute(array(
            'command' => $command->getName(),
            'env' => 'testing'
        ));

        $expected =<<<EXPECTED
connected
Are you sure? (yes/no) [no]: 0/1 [>---------------------------] 0 % []
1/1 [============================] 100 % [migration]

EXPECTED;

        $this->assertEquals($expected, $commandTester->getDisplay());
    }

    public function testUpOnly()
    {
        $this->createMigration('0', "CREATE TABLE test (id INTEGER, thevalue TEXT);",   "DROP TABLE test;");
        $this->createMigration('1', "INSERT INTO test VALUES (1, 'one');",              "DROP TABLE test;");
        $this->createMigration('2', "INSERT INTO test VALUES (2, 'two');",              "DROP TABLE test;");

        $application = new Application();
        $application->add(new UpCommand());
        $application->add(new StatusCommand());

        $command = $application->find('migrate:up');
        $commandTester = new CommandTester($command);

        $commandTester->execute(array(
            'command' => $command->getName(),
            'env' => 'testing',
            '--only' => '1'
        ));

        $expected =<<<EXPECTED
connected
0/1 [>---------------------------] 0 % []
1/1 [============================] 100 % [migration]

EXPECTED;

        $this->assertEquals($expected, $commandTester->getDisplay());

        $command = $application->find('migrate:status');
        $commandTester = new CommandTester($command);

        $commandTester->execute(array(
            'command' => $command->getName(),
            'env' => 'testing'
        ));

        $currentDate = date('Y-m-d H:i:s');

        $expected =<<<EXPECTED
connected
+----+---------+---------------------+-------------+
| id | version | applied at          | description |
+----+---------+---------------------+-------------+
| 0  |         |                     | migration   |
| 1  |         | $currentDate | migration   |
| 2  |         |                     | migration   |
+----+---------+---------------------+-------------+

EXPECTED;

        $this->assertEquals($expected, $commandTester->getDisplay());
    }
}