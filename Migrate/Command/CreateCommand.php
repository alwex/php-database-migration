<?php
/**
 * User: aguidet
 * Date: 27/02/15
 * Time: 17:17
 */

namespace Migrate\Command;

use Cocur\Slugify\Slugify;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class CreateCommand extends AbstractEnvCommand
{

    protected function configure()
    {
        $this
            ->setName('migrate:create')
            ->setDescription('Create a SQL migration');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->checkEnv();

        /* @var $questions QuestionHelper */
        $questions = $this->getHelperSet()->get('question');

        $descriptionQuestion = new Question("Please enter a description: ");
        $description = $questions->ask($input, $output, $descriptionQuestion);
        $editor=getenv("EDITOR");
        if (empty($editor)) $editor="vi";
        $editorQuestion = new Question("Please chose which editor to use <info>(default $editor)</info>: ", "$editor");
        $editor=$questions->ask($input, $output, $editorQuestion);

        $slugger = new Slugify();
        $filename = $slugger->slugify($description);
        $timestamp = str_pad(str_replace(".", "", microtime(true)), 14, "0");
        $filename = $timestamp . '_' . $filename . '.sql';

        if (file_exists($this->getMigrationDir() . '/../' .'migration.tpl')) {
            $templateFile = file_get_contents($this->getMigrationDir() . '/../' .'migration.tpl');
        } else {
            $templateFile = file_get_contents(__DIR__ . '/../../templates/migration.tpl');
        }
        $templateFile = str_replace('{DESCRIPTION}', $description, $templateFile);

        $migrationFullPath = $this->getMigrationDir() . '/' . $filename;
        file_put_contents($migrationFullPath, $templateFile);
        $output->writeln("<info>$migrationFullPath created</info>");

        if (!defined('PHPUNIT')) {
            system("$editor $migrationFullPath  > `tty`");
        }
    }

}
