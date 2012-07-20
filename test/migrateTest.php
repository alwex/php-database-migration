<?php 
class MigrateTest extends PHPUnit_Framework_TestCase {


    public function execute($cmd)
    {
        $out = null;
        $stringOut = "";
        exec($cmd, $out);
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

        $this->assertContains("No command specified", $this->execute("../migrate --help"));
        $this->assertContains("Usage" ,$this->execute("../migrate --help"));
    }

    /**
     * @test
     */
    public function generateTest()
    {

        var_dump($this->execute("../migrate --generate my_migration"));
        $this->assertContains("No command specified", $this->execute("../migrate --generate my_migration"));
        $this->assertContains("Usage" ,$this->execute("../migrate --help"));
    }

    /**
     * @test
     */
    public function statusTest()
    {

        var_dump($this->execute("../migrate --status "));
        $this->assertContains("No command specified", $this->execute("../migrate --status --env=test"));
    }
}
?>