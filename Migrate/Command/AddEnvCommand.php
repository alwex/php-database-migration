<?php
/**
 * User: aguidet
 * Date: 27/02/15
 * Time: 17:41
 */

namespace Migrate\Command;

use Migrate\Config\ConfigLocator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

class AddEnvCommand extends AbstractEnvCommand
{

    protected function configure()
    {
        $this
            ->setName('migrate:addenv')
            ->setDescription('Initialise an environment to work with php db migrate')
            ->addArgument(
                'format',
                InputArgument::OPTIONAL,
                'Environment file format: (yml, json or php), default: yml'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $format = $input->getArgument('format');
        $supportedFormats = array_keys(ConfigLocator::$SUPPORTED_PARSERS);

        if (is_null($format)) {
            $format = 'yml';
        }

        if (!in_array($format, $supportedFormats)) {
            throw new \RuntimeException(sprintf('Invalid file format: %s', $format));
        }

        // init directories
        if (! file_exists($this->getMainDir())) {
            mkdir($this->getMainDir());
        }

        if (! file_exists($this->getEnvironmentDir())) {
            mkdir($this->getEnvironmentDir());
        }

        if (! file_exists($this->getMigrationDir())) {
            mkdir($this->getMigrationDir());
        }

        $drivers = pdo_drivers();

        /* @var $questions QuestionHelper */
        $questions = $this->getHelperSet()->get('question');

        $envQuestion = new Question("Please enter the name of the new environment <info>(default dev)</info>: ", "dev");
        $envName = $questions->ask($input, $output, $envQuestion);

        $envConfigFile = $this->getEnvironmentDir() . '/' . $envName . '.' . $format;
        if (file_exists($envConfigFile)) {
            throw new \InvalidArgumentException("environment [$envName] is already defined!");
        }

        $driverQuestion = new ChoiceQuestion("Please chose your pdo driver", $drivers);
        $driver = $questions->ask($input, $output, $driverQuestion);

        $dbNameQuestion = new Question("Please enter the database name (or the database file location): ", "~");
        $dbName = $questions->ask($input, $output, $dbNameQuestion);

        $dbHostQuestion = new Question("Please enter the database host (if needed): ", "~");
        $dbHost = $questions->ask($input, $output, $dbHostQuestion);

        $dbPortQuestion = new Question("Please enter the database port (if needed): ", "~");
        $dbPort = $questions->ask($input, $output, $dbPortQuestion);

        $dbUserNameQuestion = new Question("Please enter the database user name (if needed): ", "~");
        $dbUserName = $questions->ask($input, $output, $dbUserNameQuestion);

        $dbUserPasswordQuestion = new Question("Please enter the database user password (if needed): ", "~");
        $dbUserPassword = $questions->ask($input, $output, $dbUserPasswordQuestion);

        $dbCharsetQuestion = new Question("Please enter the database charset (if needed): ", "~");
        $dbCharset = $questions->ask($input, $output, $dbCharsetQuestion);

        $changelogTableQuestion = new Question(
            "Please enter the changelog table <info>(default changelog)</info>: ",
            "changelog"
        );
        $changelogTable = $questions->ask($input, $output, $changelogTableQuestion);

        $defaultEditorQuestion = new Question(
            "Please enter the text editor to use by default <info>(default vim)</info>: ",
            "vim"
        );
        $defaultEditor = $questions->ask($input, $output, $defaultEditorQuestion);

        $confTemplate = file_get_contents(__DIR__ . '/../../templates/env.' . $format . '.tpl');
        $confTemplate = str_replace('{DRIVER}', $driver, $confTemplate);
        $confTemplate = str_replace('{HOST}', $dbHost, $confTemplate);
        $confTemplate = str_replace('{PORT}', $dbPort, $confTemplate);
        $confTemplate = str_replace('{USERNAME}', $dbUserName, $confTemplate);
        $confTemplate = str_replace('{PASSWORD}', $dbUserPassword, $confTemplate);
        $confTemplate = str_replace('{DATABASE}', $dbName, $confTemplate);
        $confTemplate = str_replace('{CHARSET}', $dbCharset, $confTemplate);
        $confTemplate = str_replace('{CHANGELOG}', $changelogTable, $confTemplate);
        $confTemplate = str_replace('{EDITOR}', $defaultEditor, $confTemplate);

        file_put_contents($envConfigFile, $confTemplate);
    }
}
