<?php

declare(strict_types=1);

namespace Yohns\ProjectStructure\Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use Yohns\ProjectStructure\Model\FileNode;

class FileNodeTest extends TestCase {
	private FileNode $file;

	protected function setUp(): void {
		parent::setUp();
		$this->file = new FileNode('test.php', '/src/test.php', '<?php echo "test";', 100);
	}

	public function testGetTypeReturnsFile(): void {
		$this->assertEquals('file', $this->file->getType());
	}

	public function testGetContentReturnsCorrectContent(): void {
		$this->assertEquals('<?php echo "test";', $this->file->getContent());
	}

	public function testSetContentUpdatesContent(): void {
		$newContent = '<?php echo "updated";';
		$this->file->setContent($newContent);

		$this->assertEquals($newContent, $this->file->getContent());
	}

	public function testGetSizeReturnsCorrectSize(): void {
		$this->assertEquals(100, $this->file->getSize());
	}

	public function testSetSizeUpdatesSize(): void {
		$this->file->setSize(200);

		$this->assertEquals(200, $this->file->getSize());
	}

	public function testGetExtensionReturnsCorrectExtension(): void {
		$this->assertEquals('php', $this->file->getExtension());

		$jsFile = new FileNode('script.js', '/js/script.js');
		$this->assertEquals('js', $jsFile->getExtension());

		$noExtFile = new FileNode('README', '/README');
		$this->assertEquals('', $noExtFile->getExtension());
	}

	public function testGetBasenameReturnsCorrectBasename(): void {
		$this->assertEquals('test', $this->file->getBasename());

		$configFile = new FileNode('config.json', '/config/config.json');
		$this->assertEquals('config', $configFile->getBasename());

		$dotFile = new FileNode('.gitignore', '/.gitignore');
		$this->assertEquals('', $dotFile->getBasename());
	}

	public function testFileWithoutContentAndSize(): void {
		$simpleFile = new FileNode('simple.txt', '/simple.txt');

		$this->assertNull($simpleFile->getContent());
		$this->assertNull($simpleFile->getSize());
	}
}