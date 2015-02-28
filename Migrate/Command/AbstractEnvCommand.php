<?php
/**
 * Created by PhpStorm.
 * User: aguidet
 * Date: 28/02/15
 * Time: 17:32
 */

namespace Migrate\Command;


use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AbstractEnvCommand extends AbstractComand {

    /**
     * @var \PDO
     */
    private $db;

    /**
     * @return \PDO
     */
    public function getDb()
    {
        return $this->db;
    }

    protected function init(InputInterface $input, OutputInterface $output)
    {
        $configDirectory = array(getcwd() . '.php-database-migration/environments');
        $locator = new FileLocator($configDirectory);
        $envFile = $locator->locate($input->getOption('env'));

//        $this->db = new \PDO('', $username, $password, array());
    }
}