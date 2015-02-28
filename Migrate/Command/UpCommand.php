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

class UpCommand extends AbstractComand {

    protected function configure()
    {
        $this
            ->setName('migrate:up')
            ->setDescription('Saluez quelqu\'un')
            ->addArgument(
                'name',
                InputArgument::OPTIONAL,
                'Qui voulez-vous saluez?'
            )
            ->addOption(
                'yell',
                null,
                InputOption::VALUE_NONE,
                'Si défini, la réponse est affichée en majuscules'
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