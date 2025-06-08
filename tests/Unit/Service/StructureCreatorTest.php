<?php

declare(strict_types=1);

namespace Yohns\ProjectStructure\Tests\Unit\Service;

use Yohns\ProjectStructure\Tests\BaseTestCase;
use Yohns\ProjectStructure\Service\StructureCreator;
use Yohns\ProjectStructure\Exception\StructureCreationException;

class StructureCreatorTest extends BaseTestCase {
	private StructureCreator $creator;

	protected function setUp(): void {
		parent::setUp();
		$this->creator = new StructureCreator($this->tempDir);
	}

	public function testCreateFromMarkdownContentCreatesDryRun(): void {
		$markdown = $this->getTestMarkdownStructure();

		$result = $this->creator->createFromMarkdownContent($markdown, true);

		$this->assertIsArray($result);
		$this->assertArrayHasKey('directories', $result);
		$this->assertArrayHasKey('files', $result);
		$this->assertNotEmpty($result['directories']);
		$this->assertNotEmpty($result['files']);
	}

	public function testCreateFromMarkdownContentCreatesActualStructure(): void {
		$markdown = $this->getTestMarkdownStructure();

		$result = $this->creator->createFromMarkdownContent($markdown, false);

		// Verify directories were created
		foreach ($result['directories'] as $dir) {
			$this->assertDirectoryExists($this->tempDir . '/' . $dir);
		}

		// Verify files were created
		foreach ($result['files'] as $file) {
			$this->assertFileExists($this->tempDir . '/' . $file);
		}
	}

	public function testCreateFromMarkdownFileThrowsExceptionForMissingFile(): void {
		$this->expectException(StructureCreationException::class);
		$this->expectExceptionMessage("Structure file 'nonexistent.md' not found");

		$this->creator->createFromMarkdownFile('nonexistent.md');
	}

	public function testValidateStructureReturnsEmptyArrayForValidStructure(): void {
		$markdown = $this->getTestMarkdownStructure();

		$errors = $this->creator->validateStructure($markdown);

		$this->assertIsArray($errors);
		$this->assertEmpty($errors);
	}

	public function testValidateStructureDetectsInvalidCharacters(): void {
		$invalidMarkdown = <<<'MARKDOWN'
# Invalid Structure

```
project/
├── src/
│   └── file<invalid>.php
└── test|file.txt
```
MARKDOWN;

		$errors = $this->creator->validateStructure($invalidMarkdown);

		$this->assertNotEmpty($errors);
		$this->assertStringContainsString('Invalid characters', $errors[0]);
	}

	public function testValidateStructureDetectsReservedFilenames(): void {
		$reservedMarkdown = <<<'MARKDOWN'
# Reserved Names

```
project/
├── CON
├── PRN.txt
└── src/
    └── NUL.php
```
MARKDOWN;

		$errors = $this->creator->validateStructure($reservedMarkdown);

		$this->assertNotEmpty($errors);
		$foundReservedError = false;
		foreach ($errors as $error) {
			if (str_contains($error, 'Reserved filename')) {
				$foundReservedError = true;
				break;
			}
		}
		$this->assertTrue($foundReservedError);
	}

	public function testCreateFromTemplateWithVariables(): void {
		// Create a test template
		$templateContent = <<<'TEMPLATE'
# {{PROJECT_NAME}} Structure

```
{{PROJECT_NAME}}/
├── src/
│   └── {{MAIN_CLASS}}.php
{{if TESTING}}├── tests/
│   └── {{MAIN_CLASS}}Test.php{{/if}}
└── README.md
```
TEMPLATE;

		// Save template to filesystem
		file_put_contents($this->tempDir . '/templates/test-template.md', $templateContent);

		$variables = [
			'PROJECT_NAME' => 'MyProject',
			'MAIN_CLASS' => 'Application',
			'TESTING' => true
		];

		$creator = new StructureCreator($this->tempDir);
		$result = $creator->createFromTemplate('test-template', $variables, true);

		$this->assertContains('MyProject', $result['directories']);
		$this->assertContains('MyProject/src', $result['directories']);
		$this->assertContains('MyProject/tests', $result['directories']);
	}

	public function testProcessTemplateHandlesConditionals(): void {
		$templateContent = <<<'TEMPLATE'
# Test Structure

```
project/
├── src/
{{if DOCS}}├── docs/{{/if}}
{{if TESTING}}├── tests/{{/if}}
└── README.md
```
TEMPLATE;

		file_put_contents($this->tempDir . '/templates/conditional.md', $templateContent);

		// Test with DOCS enabled, TESTING disabled
		$variables = ['DOCS' => true, 'TESTING' => false];
		$creator = new StructureCreator($this->tempDir);
		$result = $creator->createFromTemplate('conditional', $variables, true);

		$this->assertContains('project/docs', $result['directories']);
		$this->assertNotContains('project/tests', $result['directories']);
	}
}