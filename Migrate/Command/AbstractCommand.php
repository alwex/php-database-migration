<?php
/**
 * User: aguidet
 * Date: 27/02/15
 * Time: 17:59
 */

namespace Migrate\Command;

use Symfony\Component\Console\Command\Command;

class AbstractCommand extends Command
{

    protected $mainDir;
    protected $environmentDir;
    protected $migrationDir;

    public function __construct()
    {
        $this->mainDir = getcwd() . '/.php-database-migration';
        $this->environmentDir = $this->mainDir . '/environments';
        $this->migrationDir = $this->mainDir . '/migrations';

        parent::__construct();
    }

    /**
     * @return string
     */
    public function getMainDir()
    {
        return $this->mainDir;
    }

    /**
     * @return string
     */
    public function getMigrationDir()
    {
        return $this->migrationDir;
    }

    /**
     * @return string
     */
    public function getEnvironmentDir()
    {
        return $this->environmentDir;
    }
}
