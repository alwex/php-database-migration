<?php
/**
 * User: aguidet
 * Date: 27/02/15
 * Time: 17:21
 */

namespace Migrate\Command;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class InitCommandTest extends \PHPUnit_Framework_TestCase
{
    public function testExecute()
    {
        $application = new Application();
        $application->add(new InitCommand());

        $command = $application->find('migrate:init');

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        var_dump($commandTester->getDisplay());
    }
}