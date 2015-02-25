<?php
namespace assets;

class AssetsManagerTest extends \PHPUnit_Framework_TestCase {

    public function testGetFilesFromDir()
    {
        $path   = realpath("./");
        $result = AssetsManager::getFilesFromDir($path, 'php');

        $this->assertInternalType('array', $result);
        $this->assertNotEmpty($result);
        foreach ($result as $file) {
            $this->assertStringEndsWith(".php", $file);
        }
    }

}
 
