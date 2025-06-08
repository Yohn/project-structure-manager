#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Yohns\ProjectStructure\Service\StructureGenerator;
use Yohns\ProjectStructure\Service\StructureCreator;
use Yohns\ProjectStructure\Parser\StructureParser;
use Yohns\ProjectStructure\Model\DirectoryNode;

echo "🧪 Testing Project Structure Manager\n";
echo "=====================================\n\n";

// Test 1: Generate structure from current directory
echo "Test 1: Generating structure from current directory...\n";
try {
	$generator = new StructureGenerator('.');
	$generator->setExcludePatterns(['vendor', 'node_modules', '.git', 'test-output']);

	$structure = $generator->generateStructure('', 3); // Limit depth to 3
	$markdown = $generator->generateMarkdown($structure);

	echo "✅ Structure generated successfully!\n";
	echo "📊 Statistics:\n";
	if ($structure instanceof DirectoryNode) {
		echo "   - Directories: " . $structure->getDirectoryCount() . "\n";
		echo "   - Files: " . $structure->getFileCount() . "\n";
	}
	echo "\n";

	// Save to test file
	$generator->saveToFile($markdown, 'test-structure.md');
	echo "💾 Saved to test-structure.md\n\n";

} catch (Exception $e) {
	echo "❌ Error: " . $e->getMessage() . "\n\n";
}

// Test 2: Create a test structure from markdown
echo "Test 2: Creating structure from markdown...\n";
$testMarkdown = <<<'MD'
# Test Project Structure

```
test-project/
├── src/
│   ├── Controller/
│   │   └── HomeController.php
│   ├── Model/
│   │   └── User.php
│   └── Service/
│       └── EmailService.php
├── composer.json
├── README.md
└── .gitignore
```
MD;

try {
	// Create test output directory
	if (!is_dir('test-output')) {
		mkdir('test-output', 0755, true);
	}

	$creator = new StructureCreator('test-output');

	// First validate
	$errors = $creator->validateStructure($testMarkdown);
	if (!empty($errors)) {
		echo "❌ Validation errors:\n";
		foreach ($errors as $error) {
			echo "   - {$error}\n";
		}
	} else {
		echo "✅ Structure validation passed!\n";

		// Create with dry run first
		echo "🔍 Dry run preview:\n";
		$dryRunResult = $creator->createFromMarkdownContent($testMarkdown, true);
		echo "   Would create " . count($dryRunResult['directories']) . " directories\n";
		echo "   Would create " . count($dryRunResult['files']) . " files\n";

		// Create for real
		$result = $creator->createFromMarkdownContent($testMarkdown, false);
		echo "✅ Structure created successfully!\n";
		echo "📁 Created " . count($result['directories']) . " directories\n";
		echo "📄 Created " . count($result['files']) . " files\n";
	}
	echo "\n";

} catch (Exception $e) {
	echo "❌ Error: " . $e->getMessage() . "\n\n";
}

// Test 3: Test parser directly
echo "Test 3: Testing parser with complex structure...\n";
$complexStructure = <<<'MD'
```
my-app/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   │   └── LoginController.php
│   │   │   └── HomeController.php
│   │   └── Middleware/
│   └── Models/
├── resources/
│   ├── views/
│   └── assets/
│       ├── css/
│       └── js/
├── storage/
│   ├── logs/
│   └── cache/
└── tests/
    ├── Feature/
    └── Unit/
```
MD;

try {
	$parser = new StructureParser();
	$parsed = $parser->parseMarkdown($complexStructure);

	echo "✅ Parsed " . count($parsed) . " items successfully!\n";
	echo "📋 Sample parsed items:\n";
	foreach (array_slice($parsed, 0, 5) as $item) {
		echo "   - {$item['type']}: {$item['path']}\n";
	}
	echo "\n";

} catch (Exception $e) {
	echo "❌ Parser error: " . $e->getMessage() . "\n\n";
}

echo "🎉 All tests completed!\n";
echo "Check the 'test-output' directory to see created structure.\n";