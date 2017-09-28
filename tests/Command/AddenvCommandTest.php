<?php
/**
 * User: aguidet
 * Date: 27/02/15
 * Time: 17:21
 */

namespace Migrate\Command;

use Migrate\Enum\Directory;
use Migrate\Utils\InputStreamUtil;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Tester\CommandTester;
use Migrate\Test\Command\AbstractCommandTester;

class AddenvCommandTest extends AbstractCommandTester
{
    public function setUp()
    {
        $this->cleanEnv();
    }

    public function tearDown()
    {
        $this->cleanEnv();
    }

    public function testExecute()
    {
        $application = new Application();
        $application->add(new AddEnvCommand());

        $command = $application->find('migrate:addenv');
        $commandTester = new CommandTester($command);

        $pdoDrivers = pdo_drivers();
        $driverKey = array_search('sqlite', $pdoDrivers);

        $driverSelect = '';
        foreach ($pdoDrivers as $key => $driver) {
            $driverSelect .= "  [$key] $driver\n";
        }

        /* @var $question QuestionHelper */
        $question = $command->getHelper('question');
        $question->setInputStream(InputStreamUtil::type("testing\n$driverKey\nmigrate_test\nlocalhost\n5432\naguidet\naguidet\nutf8\nchangelog\nvim\n"));

        $commandTester->execute(array('command' => $command->getName()));

        $expected = "Please enter the name of the new environment (default dev): Please chose your pdo driver\n$driverSelect > 0\nPlease enter the database name (or the database file location): Please enter the database host (if needed): Please enter the database port (if needed): Please enter the database user name (if needed): Please enter the database user password (if needed): Please enter the changelog table (default changelog): Please enter the text editor to use by default (default vim): ";
        
        $this->assertRegExp('/Please enter the name of the new environment/', $commandTester->getDisplay());

        $envDir = Directory::getEnvPath();

        $expected = <<<EXPECTED
connection:
    host:     localhost
    driver:   sqlite
    port:     5432
    username: aguidet
    password: aguidet
    database: migrate_test
    charset:  utf8

changelog: changelog
default_editor: vim

EXPECTED;

        $fileContent = file_get_contents($envDir . '/testing.yml');

        $this->assertEquals($expected, $fileContent);
    }

}
