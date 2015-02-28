<?php
/**
 * User: aguidet
 * Date: 27/02/15
 * Time: 17:17
 */

namespace Migrate\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateCommand extends AbstractComand {

    protected function configure()
    {
        $this
            ->setName('migrate:create')
            ->setDescription('Create an empty SQL migration file')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'SQL migration name'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');
        if ($name) {
            $text = 'Salut, '.$name;
        } else {
            $text = 'Salut';
        }

        if ($input->getOption('yell')) {
            $text = strtoupper($text);
        }

        $output->writeln($text);
    }

}