<?php

namespace Test;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Symfony\Component\Filesystem\Filesystem;

class FilesystemTest extends \PHPUnit_Framework_TestCase
{
    /** @var vfsStreamDirectory */
    private $vfs;
    /** @var Filesystem */
    private $filesystem;

    public function setUp()
    {
        $this->vfs = vfsStream::setup();
        $this->filesystem = new Filesystem();
    }

    public function testMkdir()
    {
        $this->filesystem->mkdir('vfs://root/foo');
        $this->assertFileExists('vfs://root/foo');
    }
}