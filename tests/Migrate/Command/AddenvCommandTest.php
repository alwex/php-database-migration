<?php
/**
 * User: aguidet
 * Date: 27/02/15
 * Time: 17:21
 */

namespace Migrate\Command;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Helper\DialogHelper;

class AddenvCommandTest extends \PHPUnit_Framework_TestCase
{
    protected function getInputStream($input)
    {
        $stream = fopen('php://memory', 'r+', false);
        fputs($stream, $input);
        rewind($stream);

        return $stream;
    }

    public function testExecute()
    {
        $application = new Application();
        $application->add(new AddEnvCommand());

        $command = $application->find('migrate:addenv');
        $commandTester = new CommandTester($command);

        /* @var $dialog DialogHelper */
        $dialog = $command->getHelper('dialog');
        $dialog->setInputStream($this->getInputStream("a"));

        $commandTester->execute(array('command' => $command->getName()));

//        var_dump($commandTester->getDisplay());
    }
}