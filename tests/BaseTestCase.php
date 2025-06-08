<?php

declare(strict_types=1);

namespace Yohns\ProjectStructure\Tests;

use PHPUnit\Framework\TestCase;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;

abstract class BaseTestCase extends TestCase {
	protected Filesystem $filesystem;
	protected string $tempDir;

	protected function setUp(): void {
		parent::setUp();

		// Create temporary directory for tests
		$this->tempDir = sys_get_temp_dir() . '/project_structure_test_' . uniqid();
		mkdir($this->tempDir, 0755, true);

		// Set up in-memory filesystem for isolated testing
		$adapter = new InMemoryFilesystemAdapter();
		$this->filesystem = new Filesystem($adapter);
	}

	protected function tearDown(): void {
		// Clean up temporary directory
		if (is_dir($this->tempDir)) {
			$this->removeDirectory($this->tempDir);
		}

		parent::tearDown();
	}

	protected function createTestStructure(): void {
		$structure = [
			'src/',
			'src/Service/',
			'src/Model/',
			'src/Exception/',
			'src/Command/',
			'tests/',
			'tests/Unit/',
			'tests/Integration/',
			'vendor/',
			'vendor/autoload.php',
			'composer.json',
			'README.md',
			'.gitignore',
		];

		foreach ($structure as $path) {
			if (str_ends_with($path, '/')) {
				// Directory
				$dirPath = rtrim($path, '/');
				if (!is_dir($this->tempDir . '/' . $dirPath)) {
					mkdir($this->tempDir . '/' . $dirPath, 0755, true);
				}
			} else {
				// File
				$filePath = $this->tempDir . '/' . $path;
				$dir = dirname($filePath);
				if (!is_dir($dir)) {
					mkdir($dir, 0755, true);
				}
				file_put_contents($filePath, $this->getDefaultContent($path));
			}
		}
	}

	protected function getDefaultContent(string $path): string {
		return match (pathinfo($path, PATHINFO_EXTENSION)) {
			'php' => "<?php\n\ndeclare(strict_types=1);\n",
			'json' => "{\n\t\"name\": \"test\"\n}\n",
			'md' => "# Test\n\nContent here.\n",
			default => "Test content\n"
		};
	}

	protected function removeDirectory(string $dir): void {
		if (!is_dir($dir)) {
			return;
		}

		$files = array_diff(scandir($dir), ['.', '..']);
		foreach ($files as $file) {
			$path = $dir . '/' . $file;
			if (is_dir($path)) {
				$this->removeDirectory($path);
			} else {
				unlink($path);
			}
		}
		rmdir($dir);
	}

	protected function assertFileStructureMatches(array $expected, string $rootPath): void {
		foreach ($expected as $path) {
			$fullPath = $rootPath . '/' . ltrim($path, '/');
			if (str_ends_with($path, '/')) {
				$this->assertDirectoryExists($fullPath, "Directory {$path} should exist");
			} else {
				$this->assertFileExists($fullPath, "File {$path} should exist");
			}
		}
	}

	protected function getTestMarkdownStructure(): string {
		return <<<'MARKDOWN'
# Test Project Structure

```
test-project/
├── src/
│   ├── Service/
│   │   └── TestService.php
│   ├── Model/
│   │   └── TestModel.php
│   └── Exception/
│       └── TestException.php
├── tests/
│   ├── Unit/
│   │   └── ServiceTest.php
│   └── Integration/
├── composer.json
├── README.md
└── .gitignore
```

Generated for testing purposes.
MARKDOWN;
	}
}