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
        $this->assertContains("my_migration", $this->execute("./migrate --generate my_migration"));
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