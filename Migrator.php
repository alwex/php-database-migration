<?php
class Migration {

    private $id;
    private $appliedAt;
    private $description;
    private $sqlFile;
    private $applied;
    private $version;

    public function Migration($id = null, $appliedAt = null, $description = null, $sqlFile = null, $applied = false, $version = null) {
        $this->setId($id);
        $this->setAppliedAt($appliedAt);
        $this->setDescription($description);
        $this->setSqlFile($sqlFile);
        $this->setApplied($applied);
        $this->setVersion($version);
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
     * @param string $version
     * @return Migration
     */
    public function setVersion($version) {
        $this->version = $version;
        return $this;
    }

    /**
     * @return string
     */
    public function getVersion() {
        return $this->version;
    }

    /**
     * @param boolean $applied
     * @return Migration
     */
    public function setApplied($applied) {
        $this->applied = $applied;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isApplied() {
        return $this->applied;
    }

    /**
     * @param string $sqlFile
     * @return Migration
     */
    public function setSqlFile($sqlFile) {
        $this->sqlFile = $sqlFile;
        return $this;
    }

    /**
     * @return string
     */
    public function getSqlFile() {
        return $this->sqlFile;
    }

    /**
     * @param string $id
     * @return Migration
     */
    public function setId($id) {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param string $appliedAt
     * @return Migration
     */
    public function setAppliedAt($appliedAt) {
        $this->appliedAt = $appliedAt;
        return $this;
    }

    /**
     * @return string
     */
    public function getAppliedAt() {
        return $this->appliedAt;
    }

    /**
     * @param string $description
     * @return Migration
     */
    public function setDescription($description) {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription() {
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
    const ACTION_FORCE = "force";

    // modes
    const MODE_VERBOSE = "verbose";

    // options
    const OPTION_VERSION = "version";

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
            "transactional::",
            "verbose::",
            "version::"
    );

    private $environmentPath = "environments";
    private $migrationPath = "migrations";
    private $defaultEnv= "development";

    private $shortOptions = "";
    private $options = null;
    private $action = null;
    private $version = "";

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
    public function setEnvironementPath($environmentPath) {
        $this->environmentPath = $environmentPath;
        return $this;
    }

    /**
     * @return string
     */
    public function getEnvironementPath() {
        return $this->environmentPath;
    }

    /**
     * @param string $migrationPath
     * @return Migrate
     */
    public function setMigrationPath($migrationPath) {
        $this->migrationPath = $migrationPath;
        return $this;
    }

    /**
     * @return string
     */
    public function getMigrationPath() {
        return $this->migrationPath;
    }

    /**
     * @param string $defaultEnv
     * @return Migrate
     */
    public function setDefaultEnv($defaultEnv) {
        $this->defaultEnv = $defaultEnv;
        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultEnv() {
        return $this->defaultEnv;
    }

    /**
     * @param PDO $db
     * @return Migrate
     */
    public function setDb($db) {
        $this->db = $db;
        return $this;
    }

    /**
     * @return PDO
     */
    public function getDb() {
        return $this->db;
    }

    /**
     * @return array
     */
    public function getLongOptions() {
        return $this->longOptions;
    }

    /**
     * @return string
     */
    public function getShortOptions() {
        return $this->shortOptions;
    }

    /**
     * @param array $options
     * @return Migrate
     */
    public function setOptions($options) {
        $this->options = $options;
        return $this;
    }

    /**
     * @return array
     */
    public function getOptions() {
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
    public function setAction($action) {
        $this->action = $action;
        return $this;
    }

    /**
     * @return string
     */
    public function getAction() {
        return $this->action;
    }

    /**
     * @ return string
     */
    public function getVersion() {
        return $this->version;
    }

    /**
     * get version
     */
    public function initVersion() {
        $options = $this->getOptions();
        $config = $this->getConfig();

        $autoDetectVersion = "undefined";
        if (isset($config[self::OPTION_VERSION])) {
            $autoDetectVersion = system($config[self::OPTION_VERSION]);
        }

        $version = (isset($options[self::OPTION_VERSION])) ? $options[self::OPTION_VERSION] : $autoDetectVersion;

        $this->version = $version;
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

        // init version
        $this->initVersion();
    }

    /**
     * initialize db connection
     */
    public function connect() {
        $config = $this->getConfig();
        $db = new PDO($config['url'], $config['username'], $config['password']);
        $db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
        $this->setDb($db);
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

        $output = '';

        try {

            $options = $this->getOptions();
            // the good action is executed
            switch ($this->getAction()) {

                case self::ACTION_STATUS:
                    $this->connect();
                    $output = $this->doStatus();
                    break;

                case self::ACTION_GENERATE:
                    $migration = $this->doGenerate($options[self::ACTION_GENERATE]);
                    $output = "\n"
                            . $migration->getDescription() . "\n"
                                    . "migration file created migrations/" . $migration->getSqlFile()
                                    . "\n";

                    $config = $this->getConfig();
                    $editor = (isset($config["editor"])) ? $config["editor"] : 'vim';
                    system($editor . " migrations/". $migration->getSqlFile() ." > `tty`");

                    break;

                case self::ACTION_UP:
                    $this->connect();
                    $upTo = $options[self::ACTION_UP];
                    $migrationId = (! $upTo) ? 0 : $upTo;

                    if (isset($options[self::ACTION_FORCE])) {
                        $output = $this->doUpForce($migrationId);
                    } else {
                        $output = $this->doUp($migrationId);
                    }
                    break;

                case self::ACTION_DOWN:
                    $this->connect();
                    $downTo = $options[self::ACTION_DOWN];
                    $migrationId = (! $downTo) ? 0 : $downTo;

                    if (isset($options[self::ACTION_FORCE])) {
                        $output = $this->doDownForce($migrationId);
                    } else {
                        $output = $this->doDown($migrationId);
                    }
                    break;

                default:
                    $output = $this->doHelp();
            }

        } catch (Exception $e) {

            echo $e->getCode() . " " .$e->getMessage() . "\n";

        }

        print_r($output);
    }

    /**
     *
     */
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

    /**
     *
     */
    public function getDbList() {
        $sqlResult = $this->getDb()->query('SELECT * FROM changelog ORDER BY id');

        $migrationList = array();
        foreach ($sqlResult as $row) {
            $migrationList[$row['id']] = new Migration(
                    $row['id'],
                    $row['applied_at'],
                    $row['description'],
                    $row['id'] . '_' . str_replace(' ', '_', $row['description']) . '.sql',
                    true,
                    $row['version']
            );
        }

        $sqlResult->closeCursor();

        ksort($migrationList);

        return $migrationList;
    }

    /**
     *
     * @param unknown $sort
     */
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
     */
    public function doHelp() {
        return file_get_contents('templates/help.txt');
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

    /**
     *
     * @param unknown $migration
     */
    private function up(Migration $migration) {
        $options = $this->getOptions();

        $migration->setVersion($this->getVersion());

        // begin transaction
        $this->getDb()->beginTransaction();

        $date = date("Y-m-d H:i:s");
        // apply migration up
        $this->getDb()->exec($migration->getSqlUp());

        // get the SQL return code
        $sqlReturnCode = $this->getDb()->errorCode();

        // insert into changelog
        $this->getDb()->exec(
                "INSERT INTO changelog (id, applied_at, description, version) VALUES ("
                . $migration->getId() . ", '"
                . $date . "', '"
                . $migration->getDescription() . "', '"
                . $migration->getVersion() . "')"
        );

        if ($sqlReturnCode != '0') {
            // rollback the migration
            $this->getDb()->rollBack();

            $errorInfo = $this->getDb()->errorInfo();
            $errorMessage = "UP Migration failure " . $migration->getSqlFile() . "\n"
                    . $migration->getSqlDown() . "\n"
                            . "[" . $errorInfo[0] . "][" . $errorInfo[1] . "] " . $errorInfo[2] . "\n";

            throw new Exception($errorMessage);
        }

        // commit the migration
        $this->getDb()->commit();

        $output = "up success " . $migration->getSqlFile() . "\n";
        if (isset($options[self::MODE_VERBOSE])) {
            $output .= "========================================\n";
            $output .= $migration->getSqlUp();
            $output .= "\n";
        }

        print_r($output);
    }

    /**
     *
     * @param unknown $migration
     */
    private function down(Migration $migration) {
        $options = $this->getOptions();

        // begin transaction
        $this->getDb()->beginTransaction();

        // apply migration up
        $this->getDb()->exec($migration->getSqlDown());

        // get the SQL return code
        $sqlReturnCode = $this->getDb()->errorCode();

        // insert into changelog
        $this->getDb()->exec(
                "DELETE FROM changelog WHERE id = " . $migration->getId()
        );

        if ($sqlReturnCode != '0') {
            // rollback the migration
            $this->getDb()->rollBack();

            $errorInfo = $this->getDb()->errorInfo();
            $errorMessage = "DOWN Migration failure " . $migration->getSqlFile() . "\n"
                    . $migration->getSqlDown() . "\n"
                            . "[" . $errorInfo[0] . "][" . $errorInfo[1] . "] " . $errorInfo[2] . "\n";

            throw new Exception($errorMessage);
        }

        // commit the migration
        $this->getDb()->commit();

        $output = "down success " . $migration->getSqlFile() . "\n";
        if (isset($options[self::MODE_VERBOSE])) {
            $output .= "========================================\n";
            $output .= $migration->getSqlDown();
            $output .= "\n";
        }

        print_r($output);
    }

    /**
     *
     * @param unknown $migrationId
     */
    public function doUpForce($migrationId) {
        $migrationList = $this->getMigrationList();

        /* @var $theMigration Migration */
        $theMigration = $migrationList[$migrationId];

        if (! $theMigration->isApplied()) {
            $this->up($theMigration);
        } else {
            print_r("migration have already been applied\n");
        }
    }

    /**
     *
     * @param unknown $migrationId
     */
    public function doDownForce($migrationId) {
        $migrationList = $this->getMigrationList();

        /* @var $theMigration Migration */
        $theMigration = $migrationList[$migrationId];

        if ($theMigration->isApplied()) {
            $this->down($theMigration);
        } else {
            print_r("migration have not been applied\n");
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

                if (!$migrationId) {
                    break;
                }
            }

            // get out if migration is reached
            // and unapplied
            if (intval($aMigration->getId()) === intval($migrationId)) {
                break;
            }
        }
    }

    public function doStatus() {
        $migrationList = $this->getMigrationList(SORT_ASC);

        $status = "\n";
        $status .= "ID              Applied At           Version           Description\n";
        $status .= "===========================================================================\n";
        foreach ($migrationList as $aMigration) {
            /* @var $aMigration Migration */
            $appliedAt = ($aMigration->getAppliedAt() != null) ? $aMigration->getAppliedAt() : 'pending...' ;
            $migrationId = $aMigration->getId();
            $migrationDate = str_pad($appliedAt, 19, ' ');
            $version = str_pad($aMigration->getVersion(), 16, ' ');
            $migrationDescription = $aMigration->getDescription();

            $status .= $migrationId . "  "
                    . $migrationDate . "  "
                            . $version . "  "
                                    . $migrationDescription . "\n";
        }

        return $status;
    }
}

?>