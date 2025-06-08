<?php

declare(strict_types=1);

namespace Yohns\ProjectStructure\Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use Yohns\ProjectStructure\Model\DirectoryNode;
use Yohns\ProjectStructure\Model\FileNode;

class DirectoryNodeTest extends TestCase {
	private DirectoryNode $directory;

	protected function setUp(): void {
		parent::setUp();
		$this->directory = new DirectoryNode('test', '/test');
	}

	public function testGetTypeReturnsDirectory(): void {
		$this->assertEquals('directory', $this->directory->getType());
	}

	public function testAddChildIncreasesChildCount(): void {
		$file = new FileNode('test.txt', '/test/test.txt');

		$this->assertEquals(0, $this->directory->getChildCount());
		$this->directory->addChild($file);
		$this->assertEquals(1, $this->directory->getChildCount());
	}

	public function testGetChildrenSortsCorrectly(): void {
		// Add files and directories in random order
		$this->directory->addChild(new FileNode('zebra.txt', '/test/zebra.txt'));
		$this->directory->addChild(new DirectoryNode('alpha', '/test/alpha'));
		$this->directory->addChild(new FileNode('apple.txt', '/test/apple.txt'));
		$this->directory->addChild(new DirectoryNode('beta', '/test/beta'));

		$children = $this->directory->getChildren();

		// Directories should come first, then files, both alphabetically
		$this->assertEquals('alpha', $children[0]->getName());
		$this->assertEquals('beta', $children[1]->getName());
		$this->assertEquals('apple.txt', $children[2]->getName());
		$this->assertEquals('zebra.txt', $children[3]->getName());
	}

	public function testFindChildReturnsCorrectChild(): void {
		$file = new FileNode('found.txt', '/test/found.txt');
		$this->directory->addChild($file);

		$found = $this->directory->findChild('found.txt');
		$notFound = $this->directory->findChild('missing.txt');

		$this->assertSame($file, $found);
		$this->assertNull($notFound);
	}

	public function testHasChildrenReturnsTrueWhenChildrenExist(): void {
		$this->assertFalse($this->directory->hasChildren());

		$this->directory->addChild(new FileNode('test.txt', '/test/test.txt'));

		$this->assertTrue($this->directory->hasChildren());
	}

	public function testGetDirectoryCountCountsNestedDirectories(): void {
		$subDir1 = new DirectoryNode('sub1', '/test/sub1');
		$subDir2 = new DirectoryNode('sub2', '/test/sub2');
		$nestedDir = new DirectoryNode('nested', '/test/sub1/nested');

		$subDir1->addChild($nestedDir);
		$this->directory->addChild($subDir1);
		$this->directory->addChild($subDir2);

		$this->assertEquals(3, $this->directory->getDirectoryCount()); // sub1, sub2, nested
	}

	public function testGetFileCountCountsNestedFiles(): void {
		$file1 = new FileNode('file1.txt', '/test/file1.txt');
		$file2 = new FileNode('file2.txt', '/test/file2.txt');
		$subDir = new DirectoryNode('sub', '/test/sub');
		$nestedFile = new FileNode('nested.txt', '/test/sub/nested.txt');

		$subDir->addChild($nestedFile);
		$this->directory->addChild($file1);
		$this->directory->addChild($file2);
		$this->directory->addChild($subDir);

		$this->assertEquals(3, $this->directory->getFileCount()); // file1, file2, nested
	}
}