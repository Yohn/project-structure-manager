<?php

declare(strict_types=1);

namespace Yohns\ProjectStructure\Tests\Unit\Service;

use Yohns\ProjectStructure\Tests\BaseTestCase;
use Yohns\ProjectStructure\Service\StructureGenerator;
use Yohns\ProjectStructure\Model\DirectoryNode;
use Yohns\ProjectStructure\Model\FileNode;

class StructureGeneratorTest extends BaseTestCase {
	private StructureGenerator $generator;

	protected function setUp(): void {
		parent::setUp();
		$this->createTestStructure();
		$this->generator = new StructureGenerator($this->tempDir);
	}

	public function testGenerateStructureReturnsDirectoryNode(): void {
		$structure = $this->generator->generateStructure();

		$this->assertInstanceOf(DirectoryNode::class, $structure);
	}

	public function testGenerateMarkdownReturnsString(): void {
		$structure = $this->generator->generateStructure();
		$markdown = $this->generator->generateMarkdown($structure);

		$this->assertIsString($markdown);
		$this->assertStringContainsString('# Project Structure', $markdown);
		$this->assertStringContainsString('```', $markdown);
	}

	public function testSetExcludePatternsFiltersCorrectly(): void {
		$this->generator->setExcludePatterns(['vendor', '*.json']);

		$this->assertTrue($this->generator->shouldExcludeTest('vendor'));
		$this->assertTrue($this->generator->shouldExcludeTest('vendor/autoload.php'));
		$this->assertTrue($this->generator->shouldExcludeTest('composer.json'));
		$this->assertFalse($this->generator->shouldExcludeTest('src'));
		$this->assertFalse($this->generator->shouldExcludeTest('README.md'));
	}

	public function testExcludePatternsHandleWildcards(): void {
		$this->generator->setExcludePatterns(['*.tmp', '*.log', 'cache/*']);

		$this->assertTrue($this->generator->shouldExcludeTest('temp.tmp'));
		$this->assertTrue($this->generator->shouldExcludeTest('debug.log'));
		$this->assertTrue($this->generator->shouldExcludeTest('cache/data.txt'));
		$this->assertFalse($this->generator->shouldExcludeTest('file.php'));
	}

	public function testExcludePatternsHandleDirectories(): void {
		$this->generator->setExcludePatterns(['.git', 'node_modules']);

		$this->assertTrue($this->generator->shouldExcludeTest('.git'));
		$this->assertTrue($this->generator->shouldExcludeTest('.git/config'));
		$this->assertTrue($this->generator->shouldExcludeTest('node_modules'));
		$this->assertTrue($this->generator->shouldExcludeTest('node_modules/package'));
	}

	public function testGenerateStructureRespectsMaxDepth(): void {
		// Create a deep structure
		$deepPath = $this->tempDir . '/level1/level2/level3/level4';
		mkdir($deepPath, 0755, true);
		file_put_contents($deepPath . '/deep.txt', 'content');

		$generator = new StructureGenerator($this->tempDir);
		$structure = $generator->generateStructure('', 2);

		// Should not include files deeper than max depth
		$markdown = $generator->generateMarkdown($structure);
		$this->assertStringNotContainsString('deep.txt', $markdown);
	}

	public function testMarkdownContainsExpectedStructure(): void {
		$structure = $this->generator->generateStructure();
		$markdown = $this->generator->generateMarkdown($structure);

		$this->assertStringContainsString('src/', $markdown);
		$this->assertStringContainsString('tests/', $markdown);
		$this->assertStringContainsString('composer.json', $markdown);
		$this->assertStringContainsString('README.md', $markdown);
	}

	public function testSaveToFileCreatesFile(): void {
		$content = "# Test Structure\n\nContent here.";
		$filename = 'test-structure.md';

		$this->generator->saveToFile($content, $filename);

		$this->assertFileExists($this->tempDir . '/' . $filename);
		$this->assertEquals($content, file_get_contents($this->tempDir . '/' . $filename));
	}
}