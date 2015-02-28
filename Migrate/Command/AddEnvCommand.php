<?php
/**
 * User: aguidet
 * Date: 27/02/15
 * Time: 17:41
 */

namespace Migrate\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AddEnvCommand extends AbstractComand {

    protected function configure()
    {
        $this
            ->setName('migrate:addenv')
            ->setDescription('Initialise your project to work with php db migrate')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $drivers = pdo_drivers();
        $dialog = $this->getHelperSet()->get('dialog');

        $envName = $dialog->ask(
            $output,
            "<info>Environment name:</info>\n",
            '',
            ['dev', 'test', 'preprod', 'prod']
        );

        $driver = $dialog->ask(
            $output,
            "<info>Please chose your pdo driver:</info>\n<comment>" . str_replace(',', ' , ', str_replace('"', '', json_encode($drivers))) . "</comment>\n",
            '',
            $drivers
        );

        $dbName = $dialog->ask(
            $output,
            "<info>database name:</info>\n",
            ''
        );

        $dbHost = $dialog->ask(
            $output,
            "<info>database host:</info>\n",
            ''
        );

        $dbPost = $dialog->ask(
            $output,
            "<info>database port:</info>\n",
            ''
        );

        $dbUserName = $dialog->ask(
            $output,
            "<info>database user name:</info>\n",
            ''
        );

        $dbUserPassword = $dialog->ask(
            $output,
            "<info>database user password:</info>\n",
            ''
        );

        if(! file_exists($this->getMainDir())) {
            mkdir($this->getMainDir());
        }

        if(! file_exists($this->getEnvironmentDir())) {
            mkdir($this->getEnvironmentDir());
        }

        if(! file_exists($this->getMigrationDir())) {
            mkdir($this->getMigrationDir());
        }

        $envConfigFile = $this->getEnvironmentDir() . '/' . $envName . '.ini';
        if (! file_exists($envConfigFile) && $envName != null) {
            touch($envConfigFile);
            $output->writeln("environment $envName added");
        } else {
            $output->writeln("<error>environment [$envName] already configured or invalid</error>");
        }

    }

}