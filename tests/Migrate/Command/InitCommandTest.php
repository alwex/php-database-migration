<?php
/**
 * Created by PhpStorm.
 * User: aguidet
 * Date: 01/03/15
 * Time: 01:47
 */

namespace Migrate\Command;


use Migrate\Utils\InputStreamUtil;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class InitCommandTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        exec("rm -rf .php-database-migration");

        if (file_exists('migrate_test')) {
            exec("rm migrate_test");
        }
    }

    public function tearDown()
    {
        $this->setUp();
    }

    public function testExecute()
    {
        $application = new Application();
        $application->add(new AddEnvCommand());
        $application->add(new InitCommand());

        $command = $application->find('migrate:addenv');
        $commandTester = new CommandTester($command);

        /* @var $question QuestionHelper */
        $question = $command->getHelper('question');
        $question->setInputStream(InputStreamUtil::type("testing\n1\nmigrate_test\nlocalhost\n5432\naguidet\naguidet\nchangelog\nvim\n"));

        $commandTester->execute(array('command' => $command->getName()));

        $command = $application->find('migrate:init');
        $commandTester = new CommandTester($command);

        $commandTester->execute(array(
            'command' => $command->getName(),
            'env' => 'testing'
        ));

        $expected = "connected\nchangelog table (changelog) successfully created\n";

        $this->assertEquals($expected, $commandTester->getDisplay());

    }
}