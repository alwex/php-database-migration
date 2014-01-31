<?php
class Migration {

    private $id;
    private $appliedAt;
    private $description;
    private $sqlFile;
    private $applied;

    public function Migration($id = null, $appliedAt = null, $description = null, $sqlFile = null, $applied = false) {
        $this->setId($id);
        $this->setAppliedAt($appliedAt);
        $this->setDescription($description);
        $this->setSqlFile($sqlFile);
        $this->setApplied($applied);
    }

    public function getSql() {
        return file_get_contents(realpath(dirname(__FILE__)) . "/migrations/" . $this->getSqlFile());
    }

    public function getSqlUp() {
        $sql = $this->getSql();
        $upDown = explode("@UNDO", $sql);
        return $upDown[0];
    }

    public function getSqlDown() {
        $sql = $this->getSql();
        $upDown = explode("@UNDO", $sql);
        return $upDown[1];
    }

    /**
     * @param boolean $applied
     * @return Migration
     */
    public function setApplied($applied)
    {
        $this->applied = $applied;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isApplied()
    {
        return $this->applied;
    }

    /**
     * @param string $sqlFile
     * @return Migration
     */
    public function setSqlFile($sqlFile)
    {
        $this->sqlFile = $sqlFile;
        return $this;
    }

    /**
     * @return string
     */
    public function getSqlFile()
    {
        return $this->sqlFile;
    }

    /**
     * @param string $id
     * @return Migration
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $appliedAt
     * @return Migration
     */
    public function setAppliedAt($appliedAt)
    {
        $this->appliedAt = $appliedAt;
        return $this;
    }

    /**
     * @return string
     */
    public function getAppliedAt()
    {
        return $this->appliedAt;
    }

    /**
     * @param string $description
     * @return Migration
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

}

/**
 * @author aguidet
 */
class Migrate {

    /**
     * @var PDO
     */
    private $db;

    // actions
    const ACTION_STATUS = "status";
    const ACTION_GENERATE = "generate";
    const ACTION_UP = "up";
    const ACTION_DOWN = "down";
    const ACTION_HELP = "help";

    private $longOptions = array(
            // init required options
            "init::",
            "driver:",
            "database:",
            "host:",
            "login:",
            "password:",
            "changelog:",
            // other actions
            "status::",
            "generate:",
            "up::",
            "down::",
            "env:",
            "force::",
            "transactional::"
    );

    private $environmentPath = "environments";
    private $migrationPath = "migrations";
    private $defaultEnv= "development";

    private $shortOptions = "";
    private $options = null;
    private $action = null;

    /**
     * configuration from ini
     *
     * @var unknown
     */
    private $config;

    /**
     * @param string $environmentPath
     * @return Migrate
     */
    public function setEnvironementPath($environmentPath)
    {
        $this->environmentPath = $environmentPath;
        return $this;
    }

    /**
     * @return string
     */
    public function getEnvironementPath()
    {
        return $this->environmentPath;
    }

    /**
     * @param string $migrationPath
     * @return Migrate
     */
    public function setMigrationPath($migrationPath)
    {
        $this->migrationPath = $migrationPath;
        return $this;
    }

    /**
     * @return string
     */
    public function getMigrationPath()
    {
        return $this->migrationPath;
    }

    /**
     * @param string $defaultEnv
     * @return Migrate
     */
    public function setDefaultEnv($defaultEnv)
    {
        $this->defaultEnv = $defaultEnv;
        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultEnv()
    {
        return $this->defaultEnv;
    }

    /**
     * @param PDO $db
     * @return Migrate
     */
    public function setDb($db)
    {
        $this->db = $db;
        return $this;
    }

    /**
     * @return PDO
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * @return array
     */
    public function getLongOptions()
    {
        return $this->longOptions;
    }

    /**
     * @return string
     */
    public function getShortOptions()
    {
        return $this->shortOptions;
    }

    /**
     * @param array $options
     * @return Migrate
     */
    public function setOptions($options)
    {
        $this->options = $options;
        return $this;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return array
     */
    private function getConfig() {
        return $this->config;
    }

    /**
     *
     * @param unknown $config
     * @return Migrate
     */
    private function setConfig($config) {
        $this->config = $config;
        return $this;
    }

    /**
     * @param string $action
     * @return Migrate
     */
    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     *
     */
    public function __construct() {

        // set raw options to the object
        $this->setOptions(getopt(
                $this->getShortOptions(),
                $this->getLongOptions()
        ));

        // always parse options
        // before calling run
        $this->parseOptions();

        // load environnement configuration
        $this->loadConfiguration();
    }

    /**
     * initialize class parameters
     */
    private function init() {

    }

    /**
     * options are parsed and the migrator
     * is correctly initialized
     */
    private function parseOptions() {

        $this->setEnvironementPath(realpath(dirname(__FILE__)) . '/environments');
        $this->setMigrationPath(realpath(dirname(__FILE__)) . '/migrations');

        // default action help
        $action = self::ACTION_HELP;

        $options = $this->getOptions();

        // determine the required action
        if (array_key_exists(self::ACTION_STATUS, $options)) {
            $action = self::ACTION_STATUS;
        } else if (array_key_exists(self::ACTION_GENERATE, $options)) {
            $action = self::ACTION_GENERATE;
        } else if (array_key_exists(self::ACTION_UP, $options)) {
            $action = self::ACTION_UP;
        } else if (array_key_exists(self::ACTION_DOWN, $options)) {
            $action = self::ACTION_DOWN;
        }

        // check the env
        if (!array_key_exists("env", $options)) {
            $options["env"] = $this->getDefaultEnv();
        }

        $this->setOptions($options);
        $this->setAction($action);
    }

    /**
     * load configuration based on the
     * "env" arguments
     */
    public function loadConfiguration() {

        $options = $this->getOptions();

        $env = null;
        if (array_key_exists("env", $options)) {
            if (!file_exists($this->getEnvironementPath() . "/" . $options["env"] . ".ini")) {
                exit($this->getEnvironementPath() . "/" . $options["env"] . ".ini not found\n");
            } else {
                $env = $options["env"];
            }
        }

        $config = parse_ini_file($this->getEnvironementPath() . "/" . $env . ".ini");
        $this->setConfig($config);
    }

    /**
     * execute the command
     */
    public function run() {

        var_dump($this->getOptions());
        var_dump($this->getConfig());

        // the good action is executed
        switch ($this->getAction()) {
            case self::ACTION_STATUS:
                $this->doStatus();
                break;
            case self::ACTION_GENERATE:
                $this->doGenerate();
                break;
            case self::ACTION_UP:
                $this->doUp();
                break;
            case self::ACTION_DOWN:
                $this->doDown();
                break;
            default:
                echo "\nunknown action\n";
                exit(2);
        }
    }

    public function getLocaleList() {

        $fileList = array_diff(
                scandir($this->getMigrationPath(), SCANDIR_SORT_ASCENDING),
                array('..', '.')
        );

        $fileList = array_values(array_filter($fileList));

        $migrationList = array();
        foreach ($fileList as $aLine) {
            $aMigration = new Migration();

            $filename = $aLine;
            $exploded = explode('_', $aLine);
            $id = $exploded[0];

            $aLine = str_replace($id, '', $aLine);
            $aLine = str_replace('.sql', '', $aLine);

            $description = trim(str_replace('_', ' ', $aLine));

            $aMigration->setSqlFile($filename);
            $aMigration->setId($id);
            $aMigration->setDescription($description);
            $aMigration->setApplied(false);

            $migrationList[$id] = $aMigration;
        }

        ksort($migrationList);

        return $migrationList;
    }

    public function getDbList() {
        $sqlResult = $this->getDb()->query('SELECT * FROM changelog ORDER BY id');

        $migrationList = array();
        foreach ($sqlResult as $row) {
            $migrationList[$row['id']] = new Migration(
                    $row['id'],
                    $row['applied_at'],
                    $row['description'],
                    $row['id'] . '_' . str_replace(' ', '_', $row['description']) . '.sql',
                    true
            );
        }

        $sqlResult->closeCursor();

        ksort($migrationList);

        return $migrationList;
    }

    public function getMigrationList($sort = SORT_ASC) {
        $localeList = $this->getLocaleList();
        $dbList = $this->getDbList();

        $migrationList = $dbList + $localeList;

        if ($sort == SORT_ASC) {
            ksort($migrationList);
        } else {
            krsort($migrationList);
        }

        return $migrationList;
    }

    /**
     *
     * @param unknown $migrationName
     * @param string $sqlUp
     * @param string $sqlDown
     * @return Migration
     */
    public function doGenerate($migrationName, $sqlUp = "", $sqlDown = "") {

        $description = str_replace('_', ' ', $migrationName);
        $template = file_get_contents(realpath(dirname(__FILE__)) . "/templates/migration.txt");
        $template = str_replace('{SQL_UP}', $sqlUp, $template);
        $template = str_replace('{SQL_DOWN}', $sqlDown, $template);
        $template = str_replace('{DESCRIPTION}', $description, $template);

        $timestamp = str_pad(str_replace(".", "", microtime(true)), 14, "0");
        $migrationFileName = $timestamp . "_" . $migrationName . ".sql";

        file_put_contents($this->getMigrationPath() . "/" . $migrationFileName, $template);

        $migration = new Migration();
        $migration->setId($timestamp)
            ->setSqlFile($migrationFileName)
            ->setDescription($description)
            ->setApplied(false)
            ->setAppliedAt(null);

        return $migration;
    }

    private function up(Migration $migration) {
        $date = date("Y-m-d H:i:s");
        // apply migration up
        $this->getDb()->exec($migration->getSqlUp());

        // insert into changelog
        $this->getDb()->exec(
                "INSERT INTO changelog (id, applied_at, description) VALUES (" . $migration->getId() . ", '" . $date . "', '" . $migration->getDescription() . "')"
        );
    }

    private function down(Migration $migration) {
        // apply migration up
        $this->getDb()->exec($migration->getSqlDown());

        // insert into changelog
        $this->getDb()->exec(
                "DELETE FROM changelog WHERE id = " . $migration->getId()
        );
    }

    public function doUpForce($migrationId) {
        $migrationList = $this->getMigrationList();

        /* @var $theMigration Migration */
        $theMigration = $migrationList[$migrationId];

        if (! $theMigration->isApplied()) {
            $this->up($theMigration);
        } else {
            print_r("migration already applied");
        }
    }

    public function doDownForce($migrationId) {
        $migrationList = $this->getMigrationList();

        /* @var $theMigration Migration */
        $theMigration = $migrationList[$migrationId];

        if ($theMigration->isApplied()) {
            $this->down($theMigration);
        } else {
            print_r("migration have not been applied");
        }
    }

    /**
     * run all migration up to the provided id
     *
     * @param string $migrationId
     */
    public function doUp($migrationId) {
        $migrationList = $this->getMigrationList(SORT_ASC);

        foreach ($migrationList as $aMigration) {
            /* @var $aMigration Migration */

            if (! $aMigration->isApplied()) {

                $this->up($aMigration);

                print_r("migration up success");
            } else {
                print_r("migration skiped");
            }

            // get out if migration is reached
            // and applied
            if (intval($aMigration->getId()) === intval($migrationId)) {
                break;
            }
        }
    }

    /**
     * undoes all migration til the specified id (included)
     *
     * @param string $migrationId
     */
    public function doDown($migrationId) {

        $migrationList = $this->getMigrationList(SORT_DESC);

        foreach ($migrationList as $aMigration) {
            /* @var $aMigration Migration */

            if ($aMigration->isApplied()) {

                $this->down($aMigration);

                print_r("migration down success");
            } else {
                print_r("migration skiped");
            }

            // get out if migration is reached
            // and applied
            if (intval($aMigration->getId()) === intval($migrationId)) {
                break;
            }
        }
    }

    public function doStatus() {
        $migrationList = $this->getMigrationList(SORT_ASC);

        $status = "\n";
        $status .= "ID              Applied At           Description\n";
        $status .= "=========================================================\n";
        foreach ($migrationList as $aMigration) {
            /* @var $aMigration Migration */
            $migrationId = $aMigration->getId();
            $migrationDate = str_pad($aMigration->getAppliedAt(), 19);
            $migrationDescription = $aMigration->getDescription();

            $status .= $migrationId . "  "
                    . $migrationDate . "  "
                    . $migrationDescription . "\n";
        }

        return $status;
    }
}

?>