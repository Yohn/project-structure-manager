<?php

declare(strict_types=1);

// Bootstrap file for PHPUnit tests

require_once __DIR__ . '/../vendor/autoload.php';

// Set timezone for consistent test results
date_default_timezone_set('UTC');

// Set error reporting for tests
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Ensure temp directory exists for tests
$tempDir = sys_get_temp_dir() . '/project_structure_tests';
if (!is_dir($tempDir)) {
	mkdir($tempDir, 0755, true);
}

// Clean up any leftover test directories
$iterator = new DirectoryIterator(sys_get_temp_dir());
foreach ($iterator as $fileInfo) {
	if ($fileInfo->isDot()) continue;

	if ($fileInfo->isDir() && str_starts_with($fileInfo->getFilename(), 'project_structure_test_')) {
		// Remove old test directories
		$testDir = $fileInfo->getPathname();
		if (is_dir($testDir)) {
			removeTestDirectory($testDir);
		}
	}
}

/**
 * Helper function to recursively remove test directories
 */
function removeTestDirectory(string $dir): void {
	if (!is_dir($dir)) {
		return;
	}

	$files = array_diff(scandir($dir), ['.', '..']);
	foreach ($files as $file) {
		$path = $dir . '/' . $file;
		if (is_dir($path)) {
			removeTestDirectory($path);
		} else {
			unlink($path);
		}
	}
	rmdir($dir);
}