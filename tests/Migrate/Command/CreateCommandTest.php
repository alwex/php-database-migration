<?php
/**
 * Created by PhpStorm.
 * User: aguidet
 * Date: 01/03/15
 * Time: 02:15
 */

namespace Migrate\Command;


use Migrate\Utils\InputStreamUtil;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Tester\CommandTester;

define('PHPUNIT', true);

class CreateCommandTest extends \PHPUnit_Framework_TestCase
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
        $application->add(new CreateCommand());

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

        $command = $application->find('migrate:create');
        $commandTester = new CommandTester($command);
        /* @var $question QuestionHelper */
        $question = $command->getHelper('question');
        $question->setInputStream(InputStreamUtil::type("je suis une super migration &&&ééé\n\n:x\n"));

        $commandTester->execute(array('command' => $command->getName()));

        $matches = array();
        preg_match('/.*: (.*) created/', $commandTester->getDisplay(), $matches);

        $fileName = $matches[1];

        $content = file_get_contents($fileName);
        $expected =<<<EXPECTED
--// je suis une super migration &&&ééé\n-- Migration SQL that makes the change goes here.\n\n-- @UNDO\n-- SQL to undo the change goes here.\n
EXPECTED;

        $this->assertEquals($expected, $content);
    }
}