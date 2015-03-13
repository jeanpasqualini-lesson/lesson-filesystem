<?php
/**
 * Created by PhpStorm.
 * User: darkilliant
 * Date: 3/13/15
 * Time: 3:49 AM
 */
namespace Test;

use Interfaces\TestInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\LockHandler;

class MainTest implements TestInterface
{
    /** @var  Filesystem */
    private $filesystem;

    public function __construct()
    {
        $this->filesystem = new Filesystem();
    }

    public function runTest()
    {
        $this->testMkdir();

        $this->testExist();

        $this->testTouch();

        $this->testCopy();

        $this->testSymlink();

        $this->testDumpFile();

        $this->testMirror();

        echo PHP_EOL;

        $this->testLockHandler();

        $this->testLockHandler();
    }

    private function getCacheDirectory()
    {
        return ROOT_DIR.DIRECTORY_SEPARATOR."cache";
    }

    public function testMkdir()
    {
        try
        {
            $this->filesystem->mkdir($this->getTestMkdirDirectory());
        }
        catch(IOException $e)
        {
            echo "Error : ".$e->getPath();
        }
    }


    public function testLockHandler()
    {
        $cacheDir = $this->getCacheDirectory();

        $lockHandler = new LockHandler("process.lock", $cacheDir.DIRECTORY_SEPARATOR);

        if(!$lockHandler->lock())
        {
            echo "test already locked".PHP_EOL;
        }
        else
        {
            echo "test not already locked".PHP_EOL;
        }
    }

    private function getTestMkdirDirectory()
    {
        return $this->getCacheDirectory().DIRECTORY_SEPARATOR."mkdirtest";
    }

    public function testExist()
    {
        echo var_export($this->filesystem->exists($this->getTestMkdirDirectory(), true));
    }

    public function testTouch()
    {
        $this->filesystem->touch($this->getTestMkdirDirectory().DIRECTORY_SEPARATOR."touchTest.txt");
    }

    public function testCopy()
    {
        $mkdirTestDirectory = $this->getTestMkdirDirectory();

        $source = $mkdirTestDirectory.DIRECTORY_SEPARATOR."touchTest.txt";

        $destination = $mkdirTestDirectory.DIRECTORY_SEPARATOR."touchTest2.txt";

        $this->filesystem->copy($source, $destination);
    }

    public function testSymlink()
    {
        $mkdirTestDirectory = $this->getTestMkdirDirectory();

        $source = $mkdirTestDirectory.DIRECTORY_SEPARATOR."touchTest.txt";

        $destination = $mkdirTestDirectory.DIRECTORY_SEPARATOR."touchTest3.txt";

        $this->filesystem->symlink($source, $destination);
    }

    public function testDumpFile()
    {
        $mkdirTestDirectory = $this->getTestMkdirDirectory();

        $this->filesystem->dumpFile($mkdirTestDirectory.DIRECTORY_SEPARATOR."touchTest.txt", uniqid());

        $this->filesystem->dumpFile($mkdirTestDirectory.DIRECTORY_SEPARATOR."touchTest2.txt", uniqid());
    }

    public function testMirror()
    {
        $source = $this->getTestMkdirDirectory();

        $destination = $source.DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."testmirror";

        $this->filesystem->mirror($source, $destination);
    }
}