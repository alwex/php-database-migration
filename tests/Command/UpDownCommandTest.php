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

    /**
     * @expectedException \RuntimeException
     */
    public function testUpMigrationWithError()
    {
        $this->createMigration('3', "SELECT ;",   "SELECT ;");
        $command = self::$application->find('migrate:up');
        $commandTester = new CommandTester($command);

        $commandTester->execute(array(
            'command' => $command->getName(),
            'env' => 'testing'
        ));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testDownMigrationWithError()
    {
        $this->createMigration('3', "SELECT 1;",   "SELECT ;");


        $command = self::$application->find('migrate:up');
        $commandTester = new CommandTester($command);

        $commandTester->execute(array(
            'command' => $command->getName(),
            'env' => 'testing'
        ));

        $command = self::$application->find('migrate:down');
        $commandTester = new CommandTester($command);

        /* @var $question QuestionHelper */
        $question = $command->getHelper('question');
        $question->setInputStream(InputStreamUtil::type("yes\n"));

        $commandTester->execute(array(
            'command' => $command->getName(),
            'env' => 'testing'
        ));
    }

    public function testUpAllPendingMigrations()
    {

        $command = self::$application->find('migrate:up');
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

        $this->assertEquals($expected, $commandTester->getDisplay());
    }

    public function testDownLastMigration()
    {
        $command = self::$application->find('migrate:up');
        $commandTester = new CommandTester($command);

        $commandTester->execute(array(
            'command' => $command->getName(),
            'env' => 'testing'
        ));



        $command = self::$application->find('migrate:down');
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
        $command = self::$application->find('migrate:up');
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

        $command = self::$application->find('migrate:status');
        $commandTester = new CommandTester($command);

        $currentDate = date('Y-m-d H:i:s');

        $commandTester->execute(array(
            'command' => $command->getName(),
            'env' => 'testing'
        ));


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

    public function testDownOnly()
    {
        $command = self::$application->find('migrate:up');
        $commandTester = new CommandTester($command);

        $commandTester->execute(array(
            'command' => $command->getName(),
            'env' => 'testing'
        ));

        $command = self::$application->find('migrate:down');
        $commandTester = new CommandTester($command);

        /* @var $question QuestionHelper */
        $question = $command->getHelper('question');
        $question->setInputStream(InputStreamUtil::type("yes\n"));

        $commandTester->execute(array(
            'command' => $command->getName(),
            'env' => 'testing',
            '--only' => '1'
        ));

        $expected =<<<EXPECTED
connected
Are you sure? (yes/no) [no]: 0/1 [>---------------------------] 0 % []
1/1 [============================] 100 % [migration]

EXPECTED;

        $this->assertEquals($expected, $commandTester->getDisplay());

        $command = self::$application->find('migrate:status');
        $commandTester = new CommandTester($command);

        $currentTime = time();
        $validDates = array();
        foreach (range(-1, 1) as $i) {
            $validDates[] = date('Y-m-d H:i:s', $currentTime + $i);
        }
        $dateRegex = '(' . implode('|', $validDates) . ') *';

        $commandTester->execute(array(
            'command' => $command->getName(),
            'env' => 'testing'
        ));

        $expected =<<<'EXPECTED'
connected
+----+---------+---------------------+-------------+
| id | version | applied at          | description |
+----+---------+---------------------+-------------+
| 0  |         | DATE_REGEX          | migration   |
| 1  |         |                     | migration   |
| 2  |         | DATE_REGEX          | migration   |
+----+---------+---------------------+-------------+

EXPECTED;

        $pattern = '/^' . preg_quote($expected, '/') . '$/';
        $pattern = preg_replace('/DATE_REGEX */', $dateRegex, $pattern);

        $this->assertRegExp($pattern, $commandTester->getDisplay());

    }

    public function testUpTo()
    {
        $command = self::$application->find('migrate:up');
        $commandTester = new CommandTester($command);

        $commandTester->execute(array(
            'command' => $command->getName(),
            'env' => 'testing',
            '--to' => '1'
        ));

        $expected =<<<EXPECTED
connected
0/2 [>---------------------------] 0 % []
1/2 [==============>-------------] 50 % [migration]
2/2 [============================] 100 % [migration]

EXPECTED;

        $this->assertEquals($expected, $commandTester->getDisplay());

        $command = self::$application->find('migrate:status');
        $commandTester = new CommandTester($command);

        $currentDate = date('Y-m-d H:i:s');

        $commandTester->execute(array(
            'command' => $command->getName(),
            'env' => 'testing'
        ));


        $expected =<<<EXPECTED
connected
+----+---------+---------------------+-------------+
| id | version | applied at          | description |
+----+---------+---------------------+-------------+
| 0  |         | $currentDate | migration   |
| 1  |         | $currentDate | migration   |
| 2  |         |                     | migration   |
+----+---------+---------------------+-------------+

EXPECTED;

        $this->assertEquals($expected, $commandTester->getDisplay());
    }

    public function testDownTo()
    {
        $command = self::$application->find('migrate:up');
        $commandTester = new CommandTester($command);

        $commandTester->execute(array(
            'command' => $command->getName(),
            'env' => 'testing',
        ));

        $command = self::$application->find('migrate:down');
        $commandTester = new CommandTester($command);

        /* @var $question QuestionHelper */
        $question = $command->getHelper('question');
        $question->setInputStream(InputStreamUtil::type("yes\n"));

        $commandTester->execute(array(
            'command' => $command->getName(),
            'env' => 'testing',
            '--to' => '1'
        ));

        $expected =<<<EXPECTED
connected
Are you sure? (yes/no) [no]: 0/2 [>---------------------------] 0 % []
1/2 [==============>-------------] 50 % [migration]
2/2 [============================] 100 % [migration]

EXPECTED;

        $this->assertEquals($expected, $commandTester->getDisplay());

        $command = self::$application->find('migrate:status');
        $commandTester = new CommandTester($command);

        $currentDate = date('Y-m-d H:i:s');

        $commandTester->execute(array(
            'command' => $command->getName(),
            'env' => 'testing'
        ));


        $expected =<<<EXPECTED
connected
+----+---------+---------------------+-------------+
| id | version | applied at          | description |
+----+---------+---------------------+-------------+
| 0  |         | $currentDate | migration   |
| 1  |         |                     | migration   |
| 2  |         |                     | migration   |
+----+---------+---------------------+-------------+

EXPECTED;

        $this->assertEquals($expected, $commandTester->getDisplay());
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage you are not in an initialized php-database-migration directory
     */
    public function testUpInANotInitializedDirectory()
    {
        $this->cleanEnv();

        $command = self::$application->find('migrate:up');
        $commandTester = new CommandTester($command);

        $commandTester->execute(array(
            'command' => $command->getName(),
            'env' => 'testing',
        ));

        $command = self::$application->find('migrate:down');
        $commandTester = new CommandTester($command);

        $commandTester->execute(array(
            'command' => $command->getName(),
            'env' => 'testing',
            '--to' => '1'
        ));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage you are not in an initialized php-database-migration directory
     */
    public function testDownInANotInitializedDirectory()
    {
        $this->cleanEnv();

        $command = self::$application->find('migrate:down');
        $commandTester = new CommandTester($command);

        $commandTester->execute(array(
            'command' => $command->getName(),
            'env' => 'testing',
        ));

        $command = self::$application->find('migrate:down');
        $commandTester = new CommandTester($command);

        $commandTester->execute(array(
            'command' => $command->getName(),
            'env' => 'testing',
            '--to' => '1'
        ));
    }
}