<?php
/**
 * User: aguidet
 * Date: 27/02/15
 * Time: 17:13
 */

namespace Migrate\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InitCommand extends AbstractComand {

    protected function configure()
    {
        $this
            ->setName('migrate:init')
            ->setDescription('Initialise your project to work with php db migrate')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $drivers = \PDO::getAvailableDrivers();
        $dialog = $this->getHelperSet()->get('dialog');

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




//        $name = $input->getArgument('name');
//        if ($name) {
//            $text = 'Salut, '.$name;
//        } else {
//            $text = 'Salut';
//        }
//
//        if ($input->getOption('yell')) {
//            $text = strtoupper($text);
//        }
//
//        $output->writeln($text);
    }

}