<?php
/**
 * Created by PhpStorm.
 * User: aguidet
 * Date: 01/03/15
 * Time: 02:15
 */

namespace Migrate\Command;


use Migrate\Test\Command\AbstractCommandTester;
use Migrate\Utils\InputStreamUtil;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Tester\CommandTester;

define('PHPUNIT', true);

class CreateCommandTest extends AbstractCommandTester
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

    public function testExecute()
    {
        $application = new Application();
        $application->add(new CreateCommand());

        $command = $application->find('migrate:create');
        $commandTester = new CommandTester($command);
        /* @var $question QuestionHelper */
        $question = $command->getHelper('question');
        $question->setInputStream(InputStreamUtil::type("je suis une super migration &&&ééé\n\n:x\n"));

        $commandTester->execute(array('command' => $command->getName()));

        $matches = array();
        preg_match('/.*: (.*) created/', $commandTester->getDisplay(), $matches);

        $fileName = $matches[1];

        $this->assertFileExists($fileName);
        $content = file_get_contents($fileName);
        $expected =<<<EXPECTED
-- // je suis une super migration &&&ééé\n-- Migration SQL that makes the change goes here.\n\n-- @UNDO\n-- SQL to undo the change goes here.\n
EXPECTED;

        $this->assertEquals($expected, $content);
    }
}
