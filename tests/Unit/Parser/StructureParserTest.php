<?php

declare(strict_types=1);

namespace Yohns\ProjectStructure\Tests\Unit\Parser;

use Yohns\ProjectStructure\Tests\BaseTestCase;
use Yohns\ProjectStructure\Parser\StructureParser;
use Yohns\ProjectStructure\Exception\ParseException;

class StructureParserTest extends BaseTestCase {
	private StructureParser $parser;

	protected function setUp(): void {
		parent::setUp();
		$this->parser = new StructureParser();
	}

	public function testParseMarkdownReturnsArray(): void {
		$markdown = $this->getTestMarkdownStructure();

		$result = $this->parser->parseMarkdown($markdown);

		$this->assertIsArray($result);
		$this->assertNotEmpty($result);
	}

	public function testParseMarkdownIdentifiesDirectoriesAndFiles(): void {
		$markdown = <<<'MARKDOWN'
# Test Structure

```
project/
├── src/
│   └── Service.php
├── tests/
└── README.md
```
MARKDOWN;

		$result = $this->parser->parseMarkdown($markdown);

		// Find directory entries
		$directories = array_filter($result, fn($item) => $item['type'] === 'directory');
		$files = array_filter($result, fn($item) => $item['type'] === 'file');

		$this->assertNotEmpty($directories);
		$this->assertNotEmpty($files);

		// Check specific items
		$srcFound = false;
		$serviceFound = false;

		foreach ($result as $item) {
			if ($item['name'] === 'src' && $item['type'] === 'directory') {
				$srcFound = true;
			}
			if ($item['name'] === 'Service.php' && $item['type'] === 'file') {
				$serviceFound = true;
			}
		}

		$this->assertTrue($srcFound);
		$this->assertTrue($serviceFound);
	}

	public function testParseMarkdownCalculatesCorrectDepth(): void {
		$markdown = <<<'MARKDOWN'
# Depth Test

```
root/
├── level1/
│   ├── level2/
│   │   └── deep.txt
│   └── file1.txt
└── file0.txt
```
MARKDOWN;

		$result = $this->parser->parseMarkdown($markdown);

		foreach ($result as $item) {
			switch ($item['name']) {
				case 'root':
					$this->assertEquals(0, $item['depth']);
					break;
				case 'level1':
					$this->assertEquals(1, $item['depth']);
					break;
				case 'level2':
					$this->assertEquals(2, $item['depth']);
					break;
				case 'deep.txt':
					$this->assertEquals(3, $item['depth']);
					break;
				case 'file1.txt':
					$this->assertEquals(2, $item['depth']);
					break;
				case 'file0.txt':
					$this->assertEquals(1, $item['depth']);
					break;
			}
		}
	}

	public function testParseMarkdownBuildsCorrectPaths(): void {
		$markdown = <<<'MARKDOWN'
# Path Test

```
project/
├── src/
│   ├── Service/
│   │   └── TestService.php
│   └── Model.php
└── README.md
```
MARKDOWN;

		$result = $this->parser->parseMarkdown($markdown);

		$expectedPaths = [
			'project',
			'project/src',
			'project/src/Service',
			'project/src/Service/TestService.php',
			'project/src/Model.php',
			'project/README.md'
		];

		$actualPaths = array_column($result, 'path');

		foreach ($expectedPaths as $expectedPath) {
			$this->assertContains($expectedPath, $actualPaths);
		}
	}

	public function testParseMarkdownHandlesFileExtensions(): void {
		$markdown = <<<'MARKDOWN'
# Extension Test

```
files/
├── script.php
├── style.css
├── data.json
├── config.yml
└── notes.txt
```
MARKDOWN;

		$result = $this->parser->parseMarkdown($markdown);

		$files = array_filter($result, fn($item) => $item['type'] === 'file');

		$this->assertNotEmpty($files);

		foreach ($files as $file) {
			$this->assertStringContainsString('.', $file['name']);
		}
	}

	public function testParseMarkdownGeneratesDefaultContent(): void {
		$markdown = <<<'MARKDOWN'
# Content Test

```
src/
├── test.php
├── style.css
├── data.json
└── README.md
```
MARKDOWN;

		$result = $this->parser->parseMarkdown($markdown);

		foreach ($result as $item) {
			if ($item['type'] === 'file') {
				switch (pathinfo($item['name'], PATHINFO_EXTENSION)) {
					case 'php':
						$this->assertStringContainsString('<?php', $item['content']);
						break;
					case 'css':
						$this->assertStringContainsString('/*', $item['content']);
						break;
					case 'json':
						$this->assertEquals("{\n\t\n}\n", $item['content']);
						break;
					case 'md':
						$this->assertStringContainsString('# Title', $item['content']);
						break;
				}
			}
		}
	}

	public function testParseMarkdownThrowsExceptionForInvalidFormat(): void {
		$invalidMarkdown = "No code blocks here";

		$result = $this->parser->parseMarkdown($invalidMarkdown);

		// Should return empty array when no code blocks found
		$this->assertEmpty($result);
	}

	public function testParseMarkdownIgnoresContentOutsideCodeBlocks(): void {
		$markdown = <<<'MARKDOWN'
# Header outside code block

This text should be ignored.

```
project/
└── file.txt
```

This text should also be ignored.
MARKDOWN;

		$result = $this->parser->parseMarkdown($markdown);

		$this->assertCount(2, $result); // Only project/ and file.txt
	}

	public function testParseMarkdownHandlesEmptyLines(): void {
		$markdown = <<<'MARKDOWN'
# Test with empty lines

```
project/

├── src/

│   └── file.php

└── README.md

```
MARKDOWN;

		$result = $this->parser->parseMarkdown($markdown);

		$this->assertNotEmpty($result);
		$names = array_column($result, 'name');
		$this->assertContains('project', $names);
		$this->assertContains('src', $names);
		$this->assertContains('file.php', $names);
		$this->assertContains('README.md', $names);
	}
}