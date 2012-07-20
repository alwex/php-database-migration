<?php 
class MigrateTest extends PHPUnit_Framework_TestCase {


    public function execute($cmd)
    {
        $out = null;
        $stringOut = "";
        exec("cd .. && " . $cmd, $out);
        foreach ($out as $aLine)
        {
            $stringOut .= $aLine;
        }

        return $stringOut;
    }

    /**
     * @test
     */
    public function helpTest()
    {
        $this->assertContains("No command specified", $this->execute("./migrate --help"));
        $this->assertContains("Usage" ,$this->execute("./migrate --help"));
    }

    /**
     * @test
     */
    public function generateTest()
    {
        $migrationOutput = $this->execute("./migrate --generate my_migration");
        $migrationOutput = str_replace("\033[1;32mmigration: ", "", $migrationOutput);
        $migrationOutput = str_replace("\033[0m", "", $migrationOutput);
        $this->assertContains("my_migration", $migrationOutput);
        $this->assertContains("Pending", $this->execute("./migrate --status --env=test"));
        $this->assertContains("my migration", $this->execute("./migrate --status --env=test"));
    }

    /**
     * @test
     */
    public function statusTest()
    {
        $this->assertContains("description", $this->execute("./migrate --status --env=test"));
    }
}
?>