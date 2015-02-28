<?php
/**
 * User: aguidet
 * Date: 27/02/15
 * Time: 17:13
 */

namespace Migrate\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpCommand extends AbstractEnvCommand {

    protected function configure()
    {
        $this
            ->setName('migrate:up')
            ->setDescription('Execute all waiting migration up to [to] option if precised')
            ->addArgument(
                'env',
                InputArgument::REQUIRED,
                'Environment'
            )
            ->addOption(
                'to'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->init($input, $output);

        $toUp = $this->getToUpMigrations();

        if (count($toUp) == 0) {

            $output->writeln("your database is already up to date");

        } else {

            $progress = new ProgressBar($output, count($toUp));

            $progress->setFormat('%current%/%max% [%bar%] %percent% % %memory% [%message%]');
            $progress->setMessage('');
            $progress->start();

            /* @var $migration \Migrate\Migration */
            foreach ($toUp as $migration) {
                $progress->setMessage
                ($migration->getDescription());
                $this->getDb()->query($migration->getSqlUp());
                $this->saveToChangelog($migration);
                $progress->advance();
            }

            $progress->finish();
            $output->writeln("");
        }
    }

}