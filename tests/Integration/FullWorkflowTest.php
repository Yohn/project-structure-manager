<?php

declare(strict_types=1);

namespace Yohns\ProjectStructure\Tests\Integration;

use Yohns\ProjectStructure\Tests\BaseTestCase;
use Yohns\ProjectStructure\Service\StructureGenerator;
use Yohns\ProjectStructure\Service\StructureCreator;

class FullWorkflowTest extends BaseTestCase {
	public function testCompleteGenerateAndRecreateWorkflow(): void {
		// Step 1: Create a test project structure
		$this->createTestStructure();

		// Add some additional files for testing
		$additionalFiles = [
			'src/Service/TestService.php' => "<?php\n\nclass TestService {}\n",
			'src/Model/User.php' => "<?php\n\nclass User {}\n",
			'tests/Unit/ServiceTest.php' => "<?php\n\nclass ServiceTest {}\n",
			'docs/api.md' => "# API Documentation\n\nContent here.\n"
		];

		foreach ($additionalFiles as $path => $content) {
			$fullPath = $this->tempDir . '/' . $path;
			$dir = dirname($fullPath);
			if (!is_dir($dir)) {
				mkdir($dir, 0755, true);
			}
			file_put_contents($fullPath, $content);
		}

		// Step 2: Generate structure markdown
		$generator = new StructureGenerator($this->tempDir);
		$generator->setExcludePatterns(['vendor', '.git']);

		$structure = $generator->generateStructure();
		$markdown = $generator->generateMarkdown($structure);

		// Verify markdown contains expected content
		$this->assertStringContainsString('src/', $markdown);
		$this->assertStringContainsString('tests/', $markdown);
		$this->assertStringContainsString('TestService.php', $markdown);
		$this->assertStringContainsString('User.php', $markdown);

		// Step 3: Create new structure from generated markdown in different location
		$newProjectDir = $this->tempDir . '_recreated';
		mkdir($newProjectDir, 0755, true);

		$creator = new StructureCreator($newProjectDir);
		$result = $creator->createFromMarkdownContent($markdown);

		// Step 4: Verify the recreated structure matches original
		$this->assertNotEmpty($result['directories']);
		$this->assertNotEmpty($result['files']);

		// Check that key directories exist
		$this->assertDirectoryExists($newProjectDir . '/src');
		$this->assertDirectoryExists($newProjectDir . '/src/Service');
		$this->assertDirectoryExists($newProjectDir . '/src/Model');
		$this->assertDirectoryExists($newProjectDir . '/tests');
		$this->assertDirectoryExists($newProjectDir . '/tests/Unit');

		// Check that key files exist
		$this->assertFileExists($newProjectDir . '/composer.json');
		$this->assertFileExists($newProjectDir . '/README.md');
		$this->assertFileExists($newProjectDir . '/.gitignore');

		// Clean up
		$this->removeDirectory($newProjectDir);
	}

	public function testTemplateWorkflow(): void {
		// Create template directory
		$templateDir = $this->tempDir . '/templates';
		mkdir($templateDir, 0755, true);

		// Create a test template
		$templateContent = <<<'TEMPLATE'
# {{PROJECT_NAME}} Structure

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
│   └── README.md{{/if}}
├── composer.json
└── .gitignore
```

Generated for: {{PROJECT_NAME}}
{{if AUTHOR}}Author: {{AUTHOR}}{{/if}}
TEMPLATE;

		file_put_contents($templateDir . '/custom-template.md', $templateContent);

		// Create target directory
		$targetDir = $this->tempDir . '/new-project';
		mkdir($targetDir, 0755, true);

		// Create structure from template
		$creator = new StructureCreator($targetDir);
		$variables = [
			'PROJECT_NAME' => 'AwesomeProject',
			'MAIN_CLASS' => 'Application',
			'AUTHOR' => 'Test Developer',
			'TESTING' => true,
			'DOCS' => false // Test conditional exclusion
		];

		$result = $creator->createFromTemplate('custom-template', $variables);

		// Verify structure was created correctly
		$this->assertDirectoryExists($targetDir . '/AwesomeProject');
		$this->assertDirectoryExists($targetDir . '/AwesomeProject/src');
		$this->assertDirectoryExists($targetDir . '/AwesomeProject/src/Service');
		$this->assertDirectoryExists($targetDir . '/AwesomeProject/tests');
		$this->assertDirectoryExists($targetDir . '/AwesomeProject/tests/Unit');
		$this->assertDirectoryExists($targetDir . '/AwesomeProject/tests/Integration');

		// DOCS should not exist due to conditional
		$this->assertDirectoryDoesNotExist($targetDir . '/AwesomeProject/docs');

		// Check files exist
		$this->assertFileExists($targetDir . '/AwesomeProject/src/Application.php');
		$this->assertFileExists($targetDir . '/AwesomeProject/src/Service/AwesomeProjectService.php');
		$this->assertFileExists($targetDir . '/AwesomeProject/tests/Unit/ApplicationTest.php');

		// Clean up
		$this->removeDirectory($targetDir);
	}

	public function testValidationAndErrorHandling(): void {
		// Test with invalid markdown structure
		$invalidMarkdown = <<<'MARKDOWN'
# Invalid Structure

```
project/
├── invalid<file>.php
├── CON
└── src/
    └── file.php
```
MARKDOWN;

		$creator = new StructureCreator($this->tempDir);
		$errors = $creator->validateStructure($invalidMarkdown);

		$this->assertNotEmpty($errors);

		// Should contain error about invalid characters
		$hasInvalidCharsError = false;
		$hasReservedNameError = false;

		foreach ($errors as $error) {
			if (str_contains($error, 'Invalid characters')) {
				$hasInvalidCharsError = true;
			}
			if (str_contains($error, 'Reserved filename')) {
				$hasReservedNameError = true;
			}
		}

		$this->assertTrue($hasInvalidCharsError);
		$this->assertTrue($hasReservedNameError);
	}

	public function testExcludePatterns(): void {
		// Create structure with items that should be excluded
		$excludedItems = [
			'vendor/autoload.php',
			'node_modules/package/index.js',
			'.git/config',
			'cache/temp.tmp',
			'logs/debug.log',
			'.DS_Store'
		];

		foreach ($excludedItems as $item) {
			$fullPath = $this->tempDir . '/' . $item;
			$dir = dirname($fullPath);
			if (!is_dir($dir)) {
				mkdir($dir, 0755, true);
			}
			file_put_contents($fullPath, 'content');
		}

		// Generate structure with default excludes
		$generator = new StructureGenerator($this->tempDir);
		$structure = $generator->generateStructure();
		$markdown = $generator->generateMarkdown($structure);

		// Verify excluded items are not in the output
		$this->assertStringNotContainsString('vendor/', $markdown);
		$this->assertStringNotContainsString('node_modules/', $markdown);
		$this->assertStringNotContainsString('.git/', $markdown);
		$this->assertStringNotContainsString('.DS_Store', $markdown);
		$this->assertStringNotContainsString('temp.tmp', $markdown);
		$this->assertStringNotContainsString('debug.log', $markdown);
	}
}