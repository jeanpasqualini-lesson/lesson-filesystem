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

    /**
     * List of methods support string, array and traversable (e.g : ArrayObject)
     * - ....
     */

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

    private function getPath($file)
    {
        if($file instanceof vfsStreamContent) {
            $file = $file->url();
        }

        if (null !== $this->tmpDirectory) {
            return str_replace('vfs://root/', $this->tmpDirectory . '/', $file);
        }

        return $file;
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
        $this->assertTrue($this->filesystem->exists(['vfs://root/original.txt']));
        $this->assertTrue($this->filesystem->exists(new \ArrayObject(['vfs://root/original.txt'])));
    }

    public function testTouch()
    {
        $this->filesystem->touch('vfs://root/touchme.txt', $time = null, $atime = null);

        $this->assertFileExists('vfs://root/touchme.txt');
    }

    public function provideRemove()
    {
        yield ['vfs://root/one.txt'];
        yield [['vfs://root/one.txt']];
        yield [new \ArrayObject(['vfs://root/one.txt'])];
    }

    /**
     * @dataProvider provideRemove
     * @param $file
     */
    public function testRemove($file)
    {
        vfsStream::newFile('one.txt')->at($this->vfs);

        $this->assertFileExists('vfs://root/one.txt');
        $this->filesystem->remove($file);
        $this->assertFileNotExists('vfs://root/one.txt');
    }

    public function testChmod()
    {
        $this->markTestSkipped();
        vfsStream::newFile('test.txt', 0655)->at($this->vfs);

        $this->assertEquals(655, fileperms('vfs://root/test.txt'));
    }

    public function testChgrp()
    {
        $this->markTestSkipped();
    }

    public function testRename()
    {
        vfsStream::newFile('one.txt')->at($this->vfs);

        $this->assertFileExists('vfs://root/one.txt');
        $this->assertFileNotExists('vfs://root/two.txt');
        $this->filesystem->rename('vfs://root/one.txt', 'vfs://root/two.txt', $overwrite = false);
        $this->assertFileExists('vfs://root/two.txt');
        $this->assertFileNotExists('vfs://root/one.txt');
    }

    public function testSymlink()
    {
        vfsStream::newFile('one.txt')->withContent('one')->at($this->vfs);
        $this->useRealFileSystem();

        $this->filesystem->symlink(
            $this->getPath('vfs://root/one.txt'),
            $this->getPath('vfs://root/two.txt')
        );
        file_put_contents($this->getPath('vfs://root/two.txt'), 'onemodified');

        $this->assertEquals('onemodified', file_get_contents($this->getPath('vfs://root/one.txt')));
    }

    public function testMakePathRelative()
    {
        $this->assertEquals(
            '../.ssh/',
            $this->filesystem->makePathRelative('/home/user/php/.ssh', '/home/user/php/www')
        );

        $this->assertEquals(
            'web/',
            $this->filesystem->makePathRelative('/home/user/php/www/web', '/home/user/php/www')
        );
    }

    public function testMirror()
    {
        $folderA = vfsStream::newDirectory('folderA')->at($this->vfs);
        vfsStream::newFile('file.txt')->at($folderA);
        vfsStream::newDirectory('folder')->at($folderA);
        $folderB = vfsStream::newDirectory('folderB')->at($this->vfs);

        $this->filesystem->mirror($folderA->url(), $folderB->url());

        $folderAStructure = array_map(function(vfsStreamContent $streamContent)
        {
            return $streamContent->getName();
        }, $folderA->getChildren());
        $folderBStructure = array_map(function(vfsStreamContent $streamContent)
        {
            return $streamContent->getName();
        }, $folderB->getChildren());

        $this->assertEquals($folderAStructure, $folderBStructure);
    }

    public function testIsAbsolutePath()
    {
        $this->markTestSkipped();

        $this->assertFalse($this->filesystem->isAbsolutePath('/home/../home'));
    }

    public function testDumpFile()
    {
        $this->filesystem->dumpFile('vfs://root/content.txt', 'content');

        $this->assertFileExists('vfs://root/content.txt');
        $this->assertEquals('content', file_get_contents('vfs://root/content.txt'));
    }
}