<?php

declare(strict_types=1);

namespace Yohns\ProjectStructure\Tests\Integration;

use Yohns\ProjectStructure\Tests\BaseTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Yohns\ProjectStructure\Command\GenerateStructureCommand;
use Yohns\ProjectStructure\Command\CreateFromStructureCommand;

class CLICommandTest extends BaseTestCase {
	private Application $application;

	protected function setUp(): void {
		parent::setUp();

		$this->application = new Application();
		$this->application->add(new GenerateStructureCommand());
		$this->application->add(new CreateFromStructureCommand());
	}

	public function testGenerateCommandCreatesStructureFile(): void {
		// Create test structure
		$this->createTestStructure();

		// Test generate command
		$command = $this->application->find('generate');
		$commandTester = new CommandTester($command);

		$commandTester->execute([
			'path' => $this->tempDir,
			'--output' => $this->tempDir . '/generated-structure.md',
			'--exclude' => ['vendor', 'node_modules']
		]);

		// Check command was successful
		$this->assertEquals(0, $commandTester->getStatusCode());

		// Check file was created
		$this->assertFileExists($this->tempDir . '/generated-structure.md');

		// Check content
		$content = file_get_contents($this->tempDir . '/generated-structure.md');
		$this->assertStringContainsString('# Project Structure', $content);
		$this->assertStringContainsString('src/', $content);
		$this->assertStringContainsString('tests/', $content);

		// Should exclude vendor
		$this->assertStringNotContainsString('vendor/', $content);
	}

	public function testGenerateCommandWithPreview(): void {
		$this->createTestStructure();

		$command = $this->application->find('generate');
		$commandTester = new CommandTester($command);

		// Use interactive input to simulate user choosing "no" to save
		$commandTester->setInputs(['no']);

		$commandTester->execute([
			'path' => $this->tempDir,
			'--show-preview' => true
		]);

		$output = $commandTester->getDisplay();
		$this->assertStringContainsString('Preview', $output);
		$this->assertStringContainsString('Operation cancelled', $output);
	}

	public function testCreateCommandFromFile(): void {
		// Create a structure file
		$structureContent = <<<'STRUCTURE'
# Test Project

```
test-project/
├── src/
│   ├── Service/
│   │   └── TestService.php
│   └── Model/
│       └── User.php
├── tests/
│   └── Unit/
│       └── ServiceTest.php
├── composer.json
└── README.md
```
STRUCTURE;

		$structureFile = $this->tempDir . '/structure.md';
		file_put_contents($structureFile, $structureContent);

		// Create target directory
		$targetDir = $this->tempDir . '/created-project';
		mkdir($targetDir, 0755, true);

		// Test create command
		$command = $this->application->find('create');
		$commandTester = new CommandTester($command);

		$commandTester->execute([
			'structure-file' => $structureFile,
			'--target' => $targetDir
		]);

		// Check command was successful
		$this->assertEquals(0, $commandTester->getStatusCode());

		// Verify structure was created
		$this->assertDirectoryExists($targetDir . '/test-project');
		$this->assertDirectoryExists($targetDir . '/test-project/src');
		$this->assertDirectoryExists($targetDir . '/test-project/src/Service');
		$this->assertDirectoryExists($targetDir . '/test-project/src/Model');
		$this->assertDirectoryExists($targetDir . '/test-project/tests/Unit');

		$this->assertFileExists($targetDir . '/test-project/src/Service/TestService.php');
		$this->assertFileExists($targetDir . '/test-project/src/Model/User.php');
		$this->assertFileExists($targetDir . '/test-project/tests/Unit/ServiceTest.php');
		$this->assertFileExists($targetDir . '/test-project/composer.json');
		$this->assertFileExists($targetDir . '/test-project/README.md');
	}

	public function testCreateCommandDryRun(): void {
		$structureContent = $this->getTestMarkdownStructure();
		$structureFile = $this->tempDir . '/structure.md';
		file_put_contents($structureFile, $structureContent);

		$targetDir = $this->tempDir . '/dry-run-project';
		mkdir($targetDir, 0755, true);

		$command = $this->application->find('create');
		$commandTester = new CommandTester($command);

		$commandTester->execute([
			'structure-file' => $structureFile,
			'--target' => $targetDir,
			'--dry-run' => true
		]);

		$output = $commandTester->getDisplay();
		$this->assertStringContainsString('Dry Run', $output);
		$this->assertStringContainsString('Would create', $output);
		$this->assertEquals(0, $commandTester->getStatusCode());

		// Verify nothing was actually created (except the target dir)
		$contents = scandir($targetDir);
		$this->assertEquals(['.', '..'], $contents);
	}

	public function testCreateCommandWithTemplate(): void {
		// Create templates directory and template
		$templatesDir = $this->tempDir . '/templates';
		mkdir($templatesDir, 0755, true);

		$templateContent = <<<'TEMPLATE'
# {{PROJECT_NAME}} Library

```
{{PROJECT_NAME}}/
├── src/
│   ├── {{MAIN_CLASS}}.php
│   └── Service/
│       └── {{PROJECT_NAME}}Service.php
{{if TESTING}}├── tests/
│   ├── Unit/
│   │   └── {{MAIN_CLASS}}Test.php
│   └── Integration/{{/if}}
{{if DOCS}}├── docs/
│   ├── api.md
│   └── examples/{{/if}}
├── composer.json
├── README.md
└── .gitignore
```

Generated for: {{PROJECT_NAME}}
{{if AUTHOR}}Author: {{AUTHOR}}{{/if}}
TEMPLATE;

		file_put_contents($templatesDir . '/library.md', $templateContent);

		$targetDir = $this->tempDir . '/template-project';
		mkdir($targetDir, 0755, true);

		$command = $this->application->find('create');
		$commandTester = new CommandTester($command);

		$commandTester->execute([
			'structure-file' => 'library',
			'--template' => true,
			'--target' => $targetDir,
			'--variables' => [
				'PROJECT_NAME=AwesomeLib',
				'MAIN_CLASS=Library',
				'AUTHOR=Test Author',
				'TESTING=true',
				'DOCS=false'
			]
		]);

		$this->assertEquals(0, $commandTester->getStatusCode());

		// Verify template variables were processed
		$this->assertDirectoryExists($targetDir . '/AwesomeLib');
		$this->assertDirectoryExists($targetDir . '/AwesomeLib/src');
		$this->assertDirectoryExists($targetDir . '/AwesomeLib/src/Service');
		$this->assertDirectoryExists($targetDir . '/AwesomeLib/tests');
		$this->assertDirectoryExists($targetDir . '/AwesomeLib/tests/Unit');

		// DOCS should not exist due to conditional
		$this->assertDirectoryDoesNotExist($targetDir . '/AwesomeLib/docs');

		$this->assertFileExists($targetDir . '/AwesomeLib/src/Library.php');
		$this->assertFileExists($targetDir . '/AwesomeLib/src/Service/AwesomeLibService.php');
		$this->assertFileExists($targetDir . '/AwesomeLib/tests/Unit/LibraryTest.php');
	}

	public function testCreateCommandValidationOnly(): void {
		$validStructure = $this->getTestMarkdownStructure();
		$structureFile = $this->tempDir . '/valid-structure.md';
		file_put_contents($structureFile, $validStructure);

		$command = $this->application->find('create');
		$commandTester = new CommandTester($command);

		$commandTester->execute([
			'structure-file' => $structureFile,
			'--validate-only' => true
		]);

		$output = $commandTester->getDisplay();
		$this->assertStringContainsString('validation passed', $output);
		$this->assertEquals(0, $commandTester->getStatusCode());
	}

	public function testCreateCommandValidationFailure(): void {
		$invalidStructure = <<<'STRUCTURE'
# Invalid Structure

```
project/
├── invalid<file>.php
├── CON
└── src/
    └── valid.php
```
STRUCTURE;

		$structureFile = $this->tempDir . '/invalid-structure.md';
		file_put_contents($structureFile, $invalidStructure);

		$command = $this->application->find('create');
		$commandTester = new CommandTester($command);

		$commandTester->execute([
			'structure-file' => $structureFile,
			'--validate-only' => true
		]);

		$output = $commandTester->getDisplay();
		$this->assertStringContainsString('validation failed', $output);
		$this->assertEquals(1, $commandTester->getStatusCode());
	}

	public function testGenerateCommandWithCustomExcludes(): void {
		$this->createTestStructure();

		// Add some files that should be excluded
		$excludedDirs = ['cache', 'logs', 'temp'];
		foreach ($excludedDirs as $dir) {
			mkdir($this->tempDir . '/' . $dir, 0755, true);
			file_put_contents($this->tempDir . '/' . $dir . '/test.txt', 'content');
		}

		$command = $this->application->find('generate');
		$commandTester = new CommandTester($command);

		$commandTester->execute([
			'path' => $this->tempDir,
			'--output' => $this->tempDir . '/custom-structure.md',
			'--exclude' => ['cache', 'logs', 'temp', '*.tmp']
		]);

		$this->assertEquals(0, $commandTester->getStatusCode());

		$content = file_get_contents($this->tempDir . '/custom-structure.md');
		$this->assertStringNotContainsString('cache/', $content);
		$this->assertStringNotContainsString('logs/', $content);
		$this->assertStringNotContainsString('temp/', $content);
	}

	public function testCreateCommandForce(): void {
		// Create existing target directory with content
		$targetDir = $this->tempDir . '/existing-project';
		mkdir($targetDir, 0755, true);
		file_put_contents($targetDir . '/existing.txt', 'existing content');

		$structureContent = $this->getTestMarkdownStructure();
		$structureFile = $this->tempDir . '/structure.md';
		file_put_contents($structureFile, $structureContent);

		$command = $this->application->find('create');
		$commandTester = new CommandTester($command);

		$commandTester->execute([
			'structure-file' => $structureFile,
			'--target' => $targetDir,
			'--force' => true
		]);

		$this->assertEquals(0, $commandTester->getStatusCode());

		// Should have created new structure alongside existing content
		$this->assertFileExists($targetDir . '/existing.txt');
		$this->assertDirectoryExists($targetDir . '/test-project');
	}

	public function testCommandErrorHandling(): void {
		// Test with non-existent structure file
		$command = $this->application->find('create');
		$commandTester = new CommandTester($command);

		$commandTester->execute([
			'structure-file' => '/nonexistent/structure.md'
		]);

		$this->assertEquals(1, $commandTester->getStatusCode());
		$output = $commandTester->getDisplay();
		$this->assertStringContainsString('not found', $output);
	}

	public function testGenerateCommandMaxDepth(): void {
		// Create deep structure
		$deepPath = $this->tempDir . '/level1/level2/level3/level4/level5';
		mkdir($deepPath, 0755, true);
		file_put_contents($deepPath . '/deep.txt', 'content');

		$command = $this->application->find('generate');
		$commandTester = new CommandTester($command);

		$commandTester->execute([
			'path' => $this->tempDir,
			'--output' => $this->tempDir . '/shallow-structure.md',
			'--max-depth' => '3'
		]);

		$this->assertEquals(0, $commandTester->getStatusCode());

		$content = file_get_contents($this->tempDir . '/shallow-structure.md');
		$this->assertStringNotContainsString('deep.txt', $content);
		$this->assertStringContainsString('level3/', $content);
	}
}