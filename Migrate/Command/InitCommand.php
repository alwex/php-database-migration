<?php
/**
 * User: aguidet
 * Date: 27/02/15
 * Time: 17:13
 */

namespace Migrate\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitCommand extends AbstractEnvCommand
{

    protected function configure()
    {
        $this
            ->setName('migrate:init')
            ->setDescription('Create the changelog table on your environment database')
            ->addArgument(
                'env',
                InputArgument::REQUIRED,
                'Environment'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->init($input, $output);

        $changelog = $this->getChangelogTable();

        $this->getDb()->exec(
            "
            CREATE table $changelog
            (
                id numeric(20,0),
                applied_at character varying(25),
                version character varying(25),
                description character varying(255)
            )
        "
        );

        $output->writeln("changelog table ($changelog) successfully created");
    }
}
