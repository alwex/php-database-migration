<?php
use Migrate\Migration;

/**
 * @author tellim
 *
 *
 */
class MigrationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Migration
     */
    var $migration;

    protected function setUp()
    {
        parent::setUp();
        $this->migration = new Migration();
    }


    public function testLoad() {

        $this->migration->setFile('rollout.sql');
        $this->migration->load(__DIR__.'/data/feature-1/');

        $expected =<<<EXPECTED

-- Test that file inclusion works correctly
-- Migration SQL that makes the change goes here.
update db1.table1 set foo = 'bar';
update db1.table2 set foo = 'bar';
update db2.table1 set foo = 'bar';


EXPECTED;

        $this->assertEquals($expected, $this->migration->getSqlUp() );

        $expected =<<<EXPECTED


-- SQL to undo the change goes here.
update db1.table1 set foo = 'foo';
update db1.table2 set foo = 'foo';
update db2.table1 set foo = 'foo';
EXPECTED;
        $this->assertEquals($expected, $this->migration->getSqlDown() );

    }
}