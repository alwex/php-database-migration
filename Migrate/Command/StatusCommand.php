<?php
/**
 * Created by PhpStorm.
 * User: aguidet
 * Date: 28/02/15
 * Time: 18:14
 */

namespace Migrate\Command;


use Migrate\Migration;
use Migrate\Utils\ArrayUtil;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StatusCommand extends AbstractEnvCommand
{

    protected function configure()
    {
        $this
            ->setName('migrate:status')
            ->setDescription('Display the current status of the specified environment')
            ->addArgument(
                'env',
                InputArgument::REQUIRED,
                'Environment'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->init($input, $output);

        $changelog = $this->getChangelogTable();

        $migrations = array();
        $result = $this->getDb()->query("SELECT * FROM $changelog ORDER BY id");
        foreach ($result as $row) {
            $migration = Migration::createFromRow($row);
            $migrations[$migration->getId()] = $migration;
        }

        $fileList = scandir($this->getMigrationDir());
        $fileList = ArrayUtil::filter($fileList);

        foreach ($fileList as $file) {
            $migration = Migration::createFromFile($file);
            $migrations[$migration->getId()] = $migration;
        }

        ksort($migrations);

        $table = new Table($output);
        $table->setHeaders(array('id', 'version', 'applied at', 'description'));
        /* @var $migration Migration */
        foreach ($migrations as $migration) {
            $table->addRow($migration->toArray());
        }

        $table->render();
    }
}