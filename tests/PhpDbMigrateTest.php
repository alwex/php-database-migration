<?php
class PhpDbMigrateTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    private static $dbName;

    /**
     * @var PDO
     */
    private static $db;

    public static function setUpBeforeClass()
    {
        self::$dbName = dirname(__FILE__) . '/test.sqlite';
        self::$db = new PDO('sqlite:' . self::$dbName, '', '');
        self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$db->exec("create table changelog (
                id numeric(20,0),
                applied_at character varying(25),
                description character varying(255)
        )");
    }

    public function setUp()
    {
        self::$db = new PDO('sqlite:' . self::$dbName, '', '');
        self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public static function tearDownAfterClass()
    {
        unlink(self::$dbName);
    }

    private function execute($command)
    {
        $out = null;
        exec('cd ' . dirname(__FILE__) . '/.. && ./migrate --env=test ' . $command, $out);

        $stringOut = "";
        foreach ($out as $aLine)
        {
            $stringOut .= $aLine;
        }

        return $stringOut;
    }

    private function parseResult($dbResult, $search)
    {
        $found = "";
        foreach ($dbResult as $row) {
            if (isset($row[$search])) {
                $found = $row[$search];
            }
        }

        return $found;
    }

    /**
     * helper to get auto generated migration filename
     */
    private function getMigrationNameFromOutput($output)
    {
        $output = preg_replace('/.*migration: /', '', $output);
        $output = preg_replace('/\.sql.*/', '.sql', $output);

        return dirname(__FILE__) . '/../' . $output;
    }

    /**
     * helper to get auto generated migration id
     */
    private function getMigrationIdFromOutput($output)
    {
        $output = preg_replace('/.*migration: migrations\//', '', $output);
        $output = preg_replace('/\.sql.*/', '.sql', $output);

        $exploded = explode('_', $output);

        return $exploded[0];
    }

    public function testStatus_emptyList()
    {
        $out =  $this->execute('--status');
        $this->assertContains('Applied At', $out);
    }

    /**
     *
     */
    public function testGenerate()
    {
        $out = $this->execute('--generate first_migration');
        $this->assertContains('first_migration.sql', $out);
        $fileName = $this->getMigrationNameFromOutput($out);

        $this->assertFileExists($fileName);

        $migrationContent = file_get_contents($fileName);
        $migrationExpected =<<<MIGRATION
--// first migration\n-- Migration SQL that makes the change goes here.\n\n-- @UNDO\n-- SQL to undo the change goes here.\n
MIGRATION;

        $this->assertEquals($migrationExpected, $migrationContent);

        unlink($fileName);
    }

    /**
     *
     */
    public function testMigrateUpDown()
    {
        // generate migration
        $out = $this->execute('--generate create_table_test1');
        $fileName1 = $this->getMigrationNameFromOutput($out);
        $this->assertFileExists($fileName1);

        $migration1 =<<<MIGRATION
--// first migration
-- Migration SQL that makes the change goes here.

CREATE TABLE test1 (
    id numeric(20,0),
    description character varying(255)
);

-- @UNDO
-- SQL to undo the change goes here.

DROP TABLE test1;
MIGRATION;

        file_put_contents($fileName1, $migration1);
        $out = $this->execute('--up');

        $this->assertContains("SUCCESS", $out);
        $result = self::$db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='test1';");

        $this->assertEquals('test1', $this->parseResult($result, 'name'));

        $out = $this->execute('--down');
        $this->assertContains("DROP TABLE test1", $out);

        unlink($fileName1);
    }

    /**
     *
     */
    public function testMigrateUp_withTwoMigrations()
    {
        $out = $this->execute('--generate create_table_test1');
        $fileName1 = $this->getMigrationNameFromOutput($out);
        $this->assertFileExists($fileName1);

        $migration1 =<<<MIGRATION
--// first migration
-- Migration SQL that makes the change goes here.

CREATE TABLE test1 (
    id numeric(20,0),
    description character varying(255)
);

-- @UNDO
-- SQL to undo the change goes here.

DROP TABLE test1;
MIGRATION;

        file_put_contents($fileName1, $migration1);

        sleep(1);

        $out = $this->execute('--generate insert_into_test1_value1');
        $fileName2 = $this->getMigrationNameFromOutput($out);
        $this->assertFileExists($fileName2);

        $migration2 =<<<MIGRATION
--// first migration
-- Migration SQL that makes the change goes here.

INSERT INTO test1 (id, description) VALUES (1, 'value1');

-- @UNDO
-- SQL to undo the change goes here.

DELETE FROM test1 WHERE id = 1;
MIGRATION;

        file_put_contents($fileName2, $migration2);

        $out = $this->execute('--up');

        $this->assertContains("SUCCESS", $out);
        $this->assertNotContains("ERROR", $out);
        $this->assertContains("CREATE TABLE test1", $out);
        $this->assertContains("INSERT INTO test1 (id, description) VALUES (1, 'value1')", $out);

        $result = self::$db->query("SELECT * FROM test1;");
        $found = $this->parseResult($result, 'description');

        $this->assertEquals('value1', $found);

        $out = $this->execute('--status');

        $this->assertNotContains("Pending", $out);

        $out = $this->execute('--down');
        $this->assertContains("DELETE FROM test1", $out);

        $out = $this->execute('--down');
        $this->assertContains("DROP", $out);

        @unlink($fileName1);
        @unlink($fileName2);
    }

    /**
     *
     */
    public function testMigrateUp_withTwoMigrationsOneByOne()
    {
        $out = $this->execute('--generate create_table_test1');
        $fileName1 = $this->getMigrationNameFromOutput($out);
        $migrationId1 = $this->getMigrationIdFromOutput($out);

        $this->assertFileExists($fileName1);

        $migration1 =<<<MIGRATION
--// first migration
-- Migration SQL that makes the change goes here.

CREATE TABLE test1 (
    id numeric(20,0),
    description character varying(255)
);

-- @UNDO
-- SQL to undo the change goes here.

DROP TABLE test1;
MIGRATION;

        file_put_contents($fileName1, $migration1);

        sleep(1);

        $out = $this->execute('--generate insert_into_test1_value1');
        $fileName2 = $this->getMigrationNameFromOutput($out);
        $this->assertFileExists($fileName2);

        $migration2 =<<<MIGRATION
--// first migration
-- Migration SQL that makes the change goes here.

INSERT INTO test1 (id, description) VALUES (1, 'value1');

-- @UNDO
-- SQL to undo the change goes here.

DELETE FROM test1 WHERE id = 1;
MIGRATION;

        file_put_contents($fileName2, $migration2);

        $out = $this->execute('--up=' . $migrationId1);

        $this->assertContains("SUCCESS", $out);
        $this->assertNotContains("ERROR", $out);
        $this->assertContains("CREATE TABLE test1", $out);

        $out = $this->execute('--status');
        $this->assertContains("Pending", $out);

        $out = $this->execute('--up');

        $this->assertContains("SUCCESS", $out);
        $this->assertNotContains("ERROR", $out);
        $this->assertContains("INSERT INTO test1 (id, description) VALUES (1, 'value1')", $out);

        $result = self::$db->query("SELECT * FROM test1;");
        $found = $this->parseResult($result, 'description');

        $this->assertEquals('value1', $found);

        $out = $this->execute('--status');
        $this->assertNotContains("Pending", $out);

        $out = $this->execute('--down=' . $migrationId1);
        $out = $this->execute('--status');
        $this->assertContains("Pending", $out);

        @unlink($fileName1);
        @unlink($fileName2);
    }
}