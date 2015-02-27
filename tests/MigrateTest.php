<?php
require_once dirname(__FILE__) . '/../Migrator.php';

class MigrateTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var PDO
     */
    private static $db;
    private static $dbName;
    private static $migrationPath;
    private static $changelog = 'versionning';

    public static function setUpBeforeClass() {
        self::$dbName = dirname(__FILE__) . '/test.sqlite';
        unlink(self::$dbName);

        self::$db = new PDO('sqlite:' . self::$dbName, '', '');
        self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$db->exec("create table " . self::$changelog . " (
                id numeric(20,0),
                applied_at character varying(25),
                version character varying(25),
                description character varying(255)
        )");

        self::$migrationPath = realpath(dirname(__FILE__)) . "/../migrations";

        if (! self::is_dir_empty(self::$migrationPath)) {
            exec('rm ' . self::$migrationPath . '/*');
        }
    }

    public function setUp() {
        self::$db = new PDO('sqlite:' . self::$dbName, '', '');
        self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public static function is_dir_empty($dir) {
        if (!is_readable($dir)) return NULL;
        return (count(scandir($dir)) == 2);
    }

    /**
     * @Test
     */
    public function testGetLocaleList() {
        touch(self::$migrationPath . "/20100101_add_table_1.sql");
        touch(self::$migrationPath . "/20110101_add_table_2.sql");

        $migrator = new Migrate();
        $migrationList = $migrator->getLocaleList();

        $expected = array(
                '20100101' => new Migration('20100101', null, 'add table 1', '20100101_add_table_1.sql'),
                '20110101' => new Migration('20110101', null, 'add table 2', '20110101_add_table_2.sql'),
        );

        unlink(self::$migrationPath . "/20100101_add_table_1.sql");
        unlink(self::$migrationPath . "/20110101_add_table_2.sql");

        $this->assertEquals($expected, $migrationList);
    }

    /**
     * @Test
     */
    public function testGetDbList() {
        self::$db->query("INSERT INTO " . self::$changelog . " (id, applied_at, description) VALUES (20100101, '2010-01-01', 'description 1')");
        self::$db->query("INSERT INTO " . self::$changelog . " (id, applied_at, description) VALUES (20100102, '2010-01-02', 'description 2')");
        self::$db->query("INSERT INTO " . self::$changelog . " (id, applied_at, description) VALUES (20100103, '2010-01-03', 'description 3')");

        $expected = array(
                '20100101' => new Migration('20100101', '2010-01-01', 'description 1', '20100101_description_1.sql', true),
                '20100102' => new Migration('20100102', '2010-01-02', 'description 2', '20100102_description_2.sql', true),
                '20100103' => new Migration('20100103', '2010-01-03', 'description 3', '20100103_description_3.sql', true),
        );

        $migrator = new Migrate();
        $migrator->setDb(self::$db);

        $migrationList = $migrator->getDbList();

        self::$db->query("DELETE FROM " . self::$changelog);

        $this->assertEquals($expected, $migrationList);

    }

    /**
     * @Test
     */
    public function testGetMigrationList() {

        touch(self::$migrationPath . "/20100101_add_table_1.sql");
        touch(self::$migrationPath . "/20100102_add_table_2.sql");

        touch(self::$migrationPath . "/20100100_description_0.sql");
        touch(self::$migrationPath . "/20100103_description_1.sql");
        touch(self::$migrationPath . "/20100104_description_2.sql");
        touch(self::$migrationPath . "/20100105_description_3.sql");

        self::$db->query("INSERT INTO " . self::$changelog . " (id, applied_at, description) VALUES (20100100, '2009-12-30', 'description 0')");
        self::$db->query("INSERT INTO " . self::$changelog . " (id, applied_at, description) VALUES (20100103, '2010-01-01', 'description 1')");
        self::$db->query("INSERT INTO " . self::$changelog . " (id, applied_at, description) VALUES (20100104, '2010-01-02', 'description 2')");
        self::$db->query("INSERT INTO " . self::$changelog . " (id, applied_at, description) VALUES (20100105, '2010-01-03', 'description 3')");

        $expected = array(
                '20100100' => new Migration('20100100', '2009-12-30', 'description 0', '20100100_description_0.sql', true),
                '20100101' => new Migration('20100101', null,         'add table 1',   '20100101_add_table_1.sql',   false),
                '20100102' => new Migration('20100102', null,          'add table 2',  '20100102_add_table_2.sql',   false),
                '20100103' => new Migration('20100103', '2010-01-01', 'description 1', '20100103_description_1.sql', true),
                '20100104' => new Migration('20100104', '2010-01-02', 'description 2', '20100104_description_2.sql', true),
                '20100105' => new Migration('20100105', '2010-01-03', 'description 3', '20100105_description_3.sql', true),
        );

        $migrator = new Migrate();
        $migrator->setDb(self::$db);
        $migrationList = $migrator->getMigrationList();

        self::$db->query("DELETE FROM " . self::$changelog);

        unlink(self::$migrationPath . "/20100101_add_table_1.sql");
        unlink(self::$migrationPath . "/20100102_add_table_2.sql");
        unlink(self::$migrationPath . "/20100100_description_0.sql");
        unlink(self::$migrationPath . "/20100103_description_1.sql");
        unlink(self::$migrationPath . "/20100104_description_2.sql");
        unlink(self::$migrationPath . "/20100105_description_3.sql");

        $this->assertEquals($expected, $migrationList);
    }

    /**
     * @Test
     */
    public function testDoGenerate() {
        $migrator = new Migrate();

        $migration = $migrator->doGenerate("my_generated_migration");

        $this->assertFileExists(self::$migrationPath . "/" . $migration->getSqlFile());

        unlink(self::$migrationPath . "/" . $migration->getSqlFile());
    }

    /**
     * @Test
     */
    public function testDoUpThenDown() {

        $sql = "create table test (id numeric(20,0), description character varying(255));";
        self::$db->exec($sql);

        $migrator = new Migrate();
        $migrator->setDb(self::$db);

        $migrationList = array();
        $migrationList[0] = $migrator->doGenerate("test_migration_0");
        usleep(50000);
        $migrationList[1] = $migrator->doGenerate("test_migration_1");
        usleep(50000);
        $migrationList[2] = $migrator->doGenerate("test_migration_2");
        usleep(50000);
        $migrationList[3] = $migrator->doGenerate("test_migration_3");
        usleep(50000);
        $migrationList[4] = $migrator->doGenerate("test_migration_4");

        foreach ($migrationList as $key => $aMigration) {
            /* @var $aMigration Migration */
            $this->assertFileExists(self::$migrationPath . "/" . $aMigration->getSqlFile());

            $sqlUp1 =<<<SQL
--// description
-- Migration SQL that makes the change goes here.
INSERT INTO test (id, description) VALUES ($key, 'migration $key');
-- @UNDO
-- SQL to undo the change goes here.
DELETE FROM test WHERE id = $key;
SQL;

            file_put_contents(self::$migrationPath . "/" . $aMigration->getSqlFile(), $sqlUp1);

        }


        // UP to 2
        $migrator->doUp($migrationList[2]->getId());

        $result = self::$db->query("SELECT COUNT(*) FROM test");
        $this->assertEquals("3", $result->fetch()['COUNT(*)']);
        $result->closeCursor();

        $result = self::$db->query("SELECT * FROM test");
        $this->assertEquals("migration 0", $result->fetch()['description']);
        $this->assertEquals("migration 1", $result->fetch()['description']);
        $this->assertEquals("migration 2", $result->fetch()['description']);
        $result->closeCursor();

        // UP to 2 again
        $migrator->doUp($migrationList[2]->getId());

        $result = self::$db->query("SELECT COUNT(*) FROM test");
        $this->assertEquals("3", $result->fetch()['COUNT(*)']);
        $result->closeCursor();

        $result = self::$db->query("SELECT * FROM test");
        $this->assertEquals("migration 0", $result->fetch()['description']);
        $this->assertEquals("migration 1", $result->fetch()['description']);
        $this->assertEquals("migration 2", $result->fetch()['description']);
        $result->closeCursor();

        // UP to 4
        $migrator->doUp($migrationList[4]->getId());

        $result = self::$db->query("SELECT COUNT(*) FROM test");
        $this->assertEquals("5", $result->fetch()['COUNT(*)']);
        $result->closeCursor();

        $result = self::$db->query("SELECT * FROM test");
        $this->assertEquals("migration 0", $result->fetch()['description']);
        $this->assertEquals("migration 1", $result->fetch()['description']);
        $this->assertEquals("migration 2", $result->fetch()['description']);
        $this->assertEquals("migration 3", $result->fetch()['description']);
        $this->assertEquals("migration 4", $result->fetch()['description']);
        $result->closeCursor();


        // DOWN to 3
        $migrator->doDown($migrationList[3]->getId());

        $result = self::$db->query("SELECT COUNT(*) FROM test");
        $this->assertEquals("3", $result->fetch()['COUNT(*)']);
        $result->closeCursor();

        $result = self::$db->query("SELECT * FROM test");
        $this->assertEquals("migration 0", $result->fetch()['description']);
        $this->assertEquals("migration 1", $result->fetch()['description']);
        $this->assertEquals("migration 2", $result->fetch()['description']);
        $result->closeCursor();

        // DOWN to 2
        $migrator->doDown($migrationList[2]->getId());

        $result = self::$db->query("SELECT COUNT(*) FROM test");
        $this->assertEquals("2", $result->fetch()['COUNT(*)']);
        $result->closeCursor();

        $result = self::$db->query("SELECT * FROM test");
        $this->assertEquals("migration 0", $result->fetch()['description']);
        $this->assertEquals("migration 1", $result->fetch()['description']);
        $result->closeCursor();

        // DOWN to 0
        $migrator->doDown($migrationList[0]->getId());

        $result = self::$db->query("SELECT COUNT(*) FROM test");
        $this->assertEquals("0", $result->fetch()['COUNT(*)']);
        $result->closeCursor();

        foreach ($migrationList as $aMigration) {
            /* @var $aMigration Migration */
            unlink(self::$migrationPath . "/" . $aMigration->getSqlFile());
        }

        $sql = "drop table test;";
        self::$db->exec($sql);
    }

    /**
     * @Test
     */
    public function testMixUpAndForceThenDownAndForce() {
        $sql = "create table test (id numeric(20,0), description character varying(255));";
        self::$db->exec($sql);

        $migrator = new Migrate();
        $migrator->setDb(self::$db);

        $migrationList = array();
        $migrationList[0] = $migrator->doGenerate("test_migration_0");
        usleep(50000);
        $migrationList[1] = $migrator->doGenerate("test_migration_1");
        usleep(50000);
        $migrationList[2] = $migrator->doGenerate("test_migration_2");
        usleep(50000);
        $migrationList[3] = $migrator->doGenerate("test_migration_3");
        usleep(50000);
        $migrationList[4] = $migrator->doGenerate("test_migration_4");

        foreach ($migrationList as $key => $aMigration) {
            /* @var $aMigration Migration */
            $this->assertFileExists(self::$migrationPath . "/" . $aMigration->getSqlFile());

            $sqlUp1 =<<<SQL
--// description
-- Migration SQL that makes the change goes here.
INSERT INTO test (id, description) VALUES ($key, 'migration $key');
-- @UNDO
-- SQL to undo the change goes here.
DELETE FROM test WHERE id = $key;
SQL;

            file_put_contents(self::$migrationPath . "/" . $aMigration->getSqlFile(), $sqlUp1);
        }



        // force UP 1 and 3
        $migrator->doUpForce($migrationList[1]->getId());
        $migrator->doUpForce($migrationList[3]->getId());

        $result = self::$db->query("SELECT COUNT(*) FROM test");
        $this->assertEquals("2", $result->fetch()['COUNT(*)']);
        $result->closeCursor();

        $result = self::$db->query("SELECT * FROM test");
        $this->assertEquals("migration 1", $result->fetch()['description']);
        $this->assertEquals("migration 3", $result->fetch()['description']);
        $result->closeCursor();

        // then UP to 4
        $migrator->doUp($migrationList[4]->getId());

        $result = self::$db->query("SELECT COUNT(*) FROM test");
        $this->assertEquals("5", $result->fetch()['COUNT(*)']);
        $result->closeCursor();

        $result = self::$db->query("SELECT * FROM test");
        $this->assertEquals("migration 1", $result->fetch()['description']);
        $this->assertEquals("migration 3", $result->fetch()['description']);
        $this->assertEquals("migration 0", $result->fetch()['description']);
        $this->assertEquals("migration 2", $result->fetch()['description']);
        $this->assertEquals("migration 4", $result->fetch()['description']);
        $result->closeCursor();

        // force down 1 and 3
        $migrator->doDownForce($migrationList[1]->getId());
        $migrator->doDownForce($migrationList[3]->getId());

        $result = self::$db->query("SELECT COUNT(*) FROM test");
        $this->assertEquals("3", $result->fetch()['COUNT(*)']);
        $result->closeCursor();

        $result = self::$db->query("SELECT * FROM test");
        $this->assertEquals("migration 0", $result->fetch()['description']);
        $this->assertEquals("migration 2", $result->fetch()['description']);
        $this->assertEquals("migration 4", $result->fetch()['description']);
        $result->closeCursor();


        // then down to 0
        $migrator->doDown($migrationList[0]->getId());

        $result = self::$db->query("SELECT COUNT(*) FROM test");
        $this->assertEquals("0", $result->fetch()['COUNT(*)']);
        $result->closeCursor();


        foreach ($migrationList as $aMigration) {
            /* @var $aMigration Migration */
            unlink(self::$migrationPath . "/" . $aMigration->getSqlFile());
        }

        $sql = "drop table test;";
        self::$db->exec($sql);
    }

    /**
     * @Test
     */
    public function testDoUpForceThenDoDownForce() {

        $sql = "create table test (id numeric(20,0), description character varying(255));";
        self::$db->exec($sql);

        $migrator = new Migrate();
        $migrator->setDb(self::$db);

        $migrationList = array();
        $migrationList[0] = $migrator->doGenerate("test_migration_0");
        usleep(50000);
        $migrationList[1] = $migrator->doGenerate("test_migration_1");
        usleep(50000);
        $migrationList[2] = $migrator->doGenerate("test_migration_2");
        usleep(50000);
        $migrationList[3] = $migrator->doGenerate("test_migration_3");
        usleep(50000);
        $migrationList[4] = $migrator->doGenerate("test_migration_4");

        foreach ($migrationList as $key => $aMigration) {
            /* @var $aMigration Migration */
            $this->assertFileExists(self::$migrationPath . "/" . $aMigration->getSqlFile());

            $sqlUp1 =<<<SQL
--// description
-- Migration SQL that makes the change goes here.
INSERT INTO test (id, description) VALUES ($key, 'migration $key');
-- @UNDO
-- SQL to undo the change goes here.
DELETE FROM test WHERE id = $key;
SQL;

            file_put_contents(self::$migrationPath . "/" . $aMigration->getSqlFile(), $sqlUp1);
        }

        $migrator->doUpForce($migrationList[2]->getId());

        $result = self::$db->query("SELECT COUNT(*) FROM test");
        $this->assertEquals("1", $result->fetch()['COUNT(*)']);
        $result->closeCursor();

        $result = self::$db->query("SELECT * FROM test");
        $this->assertEquals("migration 2", $result->fetch()['description']);
        $result->closeCursor();


        $migrator->doUpForce($migrationList[4]->getId());

        $result = self::$db->query("SELECT COUNT(*) FROM test");
        $this->assertEquals("2", $result->fetch()['COUNT(*)']);
        $result->closeCursor();

        $result = self::$db->query("SELECT * FROM test");
        $this->assertEquals("migration 2", $result->fetch()['description']);
        $this->assertEquals("migration 4", $result->fetch()['description']);
        $result->closeCursor();


        $migrator->doUpForce($migrationList[3]->getId());

        $result = self::$db->query("SELECT COUNT(*) FROM test");
        $this->assertEquals("3", $result->fetch()['COUNT(*)']);
        $result->closeCursor();

        $result = self::$db->query("SELECT * FROM test");
        $this->assertEquals("migration 2", $result->fetch()['description']);
        $this->assertEquals("migration 4", $result->fetch()['description']);
        $this->assertEquals("migration 3", $result->fetch()['description']);
        $result->closeCursor();

        $migrator->doDownForce($migrationList[3]->getId());

        $result = self::$db->query("SELECT COUNT(*) FROM test");
        $this->assertEquals("2", $result->fetch()['COUNT(*)']);
        $result->closeCursor();

        $result = self::$db->query("SELECT * FROM test");
        $this->assertEquals("migration 2", $result->fetch()['description']);
        $this->assertEquals("migration 4", $result->fetch()['description']);
        $result->closeCursor();

        $migrator->doDownForce($migrationList[2]->getId());

        $result = self::$db->query("SELECT COUNT(*) FROM test");
        $this->assertEquals("1", $result->fetch()['COUNT(*)']);
        $result->closeCursor();

        $result = self::$db->query("SELECT * FROM test");
        $this->assertEquals("migration 4", $result->fetch()['description']);
        $result->closeCursor();


        foreach ($migrationList as $aMigration) {
            /* @var $aMigration Migration */
            unlink(self::$migrationPath . "/" . $aMigration->getSqlFile());
        }

        $sql = "drop table test;";
        self::$db->exec($sql);
    }

    public function testDoStatus() {
        $sql = "create table test (id numeric(30,0), description character varying(255));";
        self::$db->exec($sql);

        $migrator = new Migrate();
        $migrator->setDb(self::$db);

        $migrationList = array();
        $migrationList[0] = $migrator->doGenerate("test_migration_0");
        usleep(200000);
        $migrationList[1] = $migrator->doGenerate("test_migration_1");
        usleep(200000);
        $migrationList[2] = $migrator->doGenerate("test_migration_2");
        usleep(200000);
        $migrationList[3] = $migrator->doGenerate("test_migration_3");
        usleep(200000);
        $migrationList[4] = $migrator->doGenerate("test_migration_4");

        foreach ($migrationList as $key => $aMigration) {
            /* @var $aMigration Migration */
            $this->assertFileExists(self::$migrationPath . "/" . $aMigration->getSqlFile());

            $sqlUp1 =<<<SQL
--// description
-- Migration SQL that makes the change goes here.
INSERT INTO test (id, description) VALUES ($key, 'migration $key');
-- @UNDO
-- SQL to undo the change goes here.
DELETE FROM test WHERE id = $key;
SQL;

            file_put_contents(self::$migrationPath . "/" . $aMigration->getSqlFile(), $sqlUp1);
        }

        $migrator->doUpForce($migrationList[0]->getId());
        $migrator->doUpForce($migrationList[2]->getId());
        $migrator->doUpForce($migrationList[4]->getId());

        $status = $migrator->doStatus();
//        print_r($status);

        foreach ($migrationList as $aMigration) {
            /* @var $aMigration Migration */
            unlink(self::$migrationPath . "/" . $aMigration->getSqlFile());
        }

        $sql = "drop table test;";
        self::$db->exec($sql);
    }
}