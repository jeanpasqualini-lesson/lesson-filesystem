<?php

namespace Test;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamContent;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamFile;
use Symfony\Component\Filesystem\Filesystem;

class FilesystemTest extends \PHPUnit_Framework_TestCase
{
    /** @var vfsStreamDirectory */
    private $vfs;
    /** @var Filesystem */
    private $filesystem;
    /** @var string */
    private $tmpDirectory;

    public function setUp()
    {
        $this->vfs = vfsStream::setup();
        $this->filesystem = new Filesystem();
    }

    public function tearDown()
    {
        $this->filesystem->remove($this->tmpDirectory);
    }

    public function testMkdir()
    {
        $this->filesystem->mkdir('vfs://root/foo');
        $this->assertFileExists('vfs://root/foo');
    }

    public function provideCopy()
    {
        $timestamp = time();

        // Not override is target is too recent
        yield array_values([
           'source' => vfsStream::newFile('original.txt')
               ->withContent('body')
               ->lastModified($timestamp + 1),
           'target' => vfsStream::newFile('target.txt')
               ->withContent('')
               ->lastModified($timestamp + 2),
           'expected_target_content' => '',
           'override_newer_file' => false
        ]);

        // Force override same is target is too recent
        yield array_values([
           'source' => vfsStream::newFile('original.txt')
               ->withContent('body')
               ->lastModified($timestamp + 1),
           'target' => vfsStream::newFile('target.txt')
               ->withContent('')
               ->lastModified($timestamp + 2),
           'expected_target_content' => 'body',
           'override_newer_file' => true
        ]);
    }

    private function useRealFileSystem()
    {
        $this->tmpDirectory = sys_get_temp_dir() . '/'.uniqid('lesson-filesystem-');
        $this->filesystem->mkdir($this->tmpDirectory);
        $this->filesystem->mirror($this->vfs->url(), $this->tmpDirectory);
    }

    private function getPath(vfsStreamContent $content)
    {
        if (null !== $this->tmpDirectory) {
            return str_replace('vfs://root/', $this->tmpDirectory . '/', $content->url());
        }

        return $content->url();
    }

    /**
     * @dataProvider provideCopy
     * @param vfsStreamFile $source
     * @param vfsStreamFile $target
     * @param string $expected
     */
    public function testCopy(vfsStreamFile $source, vfsStreamFile $target, string $expected, bool $overideNewerFile = false)
    {
        $source->at($this->vfs);
        $target->at($this->vfs);

        $this->useRealFileSystem();

        $this->filesystem->copy(
            $this->getPath($source),
            $this->getPath($target),
            $overideNewerFile
        );

        $this->assertEquals($expected, file_get_contents($this->getPath($target)));
    }

    public function testExists()
    {
        vfsStream::newFile('original.txt')->at($this->vfs);

        $this->assertTrue($this->filesystem->exists('vfs://root/original.txt'));
    }
}