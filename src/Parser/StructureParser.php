<?php

declare(strict_types=1);

namespace Yohns\ProjectStructure\Parser;

use Yohns\ProjectStructure\Exception\ParseException;

class StructureParser {
	private const TREE_SYMBOLS = ['├──', '└──', '│', '─'];
	private const DIRECTORY_INDICATORS = ['/', '\\'];

	public function parseMarkdown(string $content): array {
		$lines = explode("\n", $content);
		$inCodeBlock = false;
		$structure = [];
		$pathStack = [];

		foreach ($lines as $lineNumber => $line) {
			// Check for code block boundaries
			if (preg_match('/^```/', $line)) {
				$inCodeBlock = !$inCodeBlock;
				continue;
			}

			// Only process lines inside code blocks
			if (!$inCodeBlock) {
				continue;
			}

			// Skip empty lines
			if (trim($line) === '') {
				continue;
			}

			try {
				$parsed = $this->parseLine($line, $lineNumber + 1);
				if ($parsed !== null) {
					// Build full path using pathStack
					$pathStack = array_slice($pathStack, 0, $parsed['depth']);
					$pathStack[] = $parsed['name'];

					$fullPath = implode('/', $pathStack);

					$structure[] = [
						'path'    => $fullPath,
						'type'    => $parsed['type'],
						'name'    => $parsed['name'],
						'depth'   => $parsed['depth'],
						'content' => $this->extractContent($parsed['name'])
					];
				}
			} catch (ParseException $e) {
				throw new ParseException("Line {$lineNumber}: " . $e->getMessage());
			}
		}

		return $this->validateAndCleanStructure($structure);
	}

	private function parseLine(string $line, int $lineNumber): ?array {
		// Remove common tree drawing characters and normalize
		$cleanLine = $this->cleanTreeSymbols($line);

		// Calculate depth based on indentation
		$depth = $this->calculateDepth($line);

		// Extract the item name
		$name = trim($cleanLine);

		if (empty($name)) {
			return null;
		}

		// Determine if it's a directory or file
		$isDirectory = $this->isDirectory($name);

		// Clean the name (remove trailing slashes for directories)
		if ($isDirectory) {
			$name = rtrim($name, '/\\');
		}

		return [
			'name'  => $name,
			'depth' => $depth,
			'type'  => $isDirectory ? 'directory' : 'file',
			'line'  => $lineNumber
		];
	}

	private function cleanTreeSymbols(string $line): string {
		// Remove Unicode box drawing characters and spaces
		$cleaned = $line;

		// Remove patterns like │   or ├── or └──
		$patterns = [
			'/^\xe2\x94\x82\s*/',                    // │ followed by spaces
			'/^\xe2\x94\x9c\xe2\x94\x80\xe2\x94\x80\s*/', // ├──
			'/^\xe2\x94\x94\xe2\x94\x80\xe2\x94\x80\s*/', // └──
		];

		do {
			$before = $cleaned;
			foreach ($patterns as $pattern) {
				$cleaned = preg_replace($pattern, '', $cleaned);
			}
		} while ($cleaned !== $before);

		return $cleaned;
	}

	private function calculateDepth(string $line): int {
		$depth = 0;
		$pos = 0;

		// Count each occurrence of │ (U+2502) as one depth level
		while (($pos = strpos($line, "\xe2\x94\x82", $pos)) !== false) {
			$depth++;
			$pos += 3; // Move past the │ character
		}

		return $depth;
	}

	private function isDirectory(string $name): bool {
		// Check if ends with directory indicators
		foreach (self::DIRECTORY_INDICATORS as $indicator) {
			if (str_ends_with($name, $indicator)) {
				return true;
			}
		}

		// Check if it has no extension and doesn't contain special file characters
		$hasExtension = strpos(basename($name), '.') !== false;

		// Common file patterns
		$filePatterns = [
			'/\.(php|js|css|html|md|txt|json|xml|yml|yaml|ini|conf|log)$/i',
			'/^\./',  // Hidden files
			'/\.(lock|dist|min)$/i'
		];

		foreach ($filePatterns as $pattern) {
			if (preg_match($pattern, $name)) {
				return false;
			}
		}

		// If no clear indicators, assume directory if no extension
		return !$hasExtension;
	}

	private function buildFullPath(array $pathStack, array $parsed): array {
		// Trim pathStack to current depth
		$pathStack = array_slice($pathStack, 0, $parsed['depth']);

		// Add current item name
		$pathStack[] = $parsed['name'];

		$fullPath = implode('/', $pathStack);

		return [
			'path'    => $fullPath,
			'type'    => $parsed['type'],
			'name'    => $parsed['name'],
			'depth'   => $parsed['depth'],
			'content' => $this->extractContent($parsed['name'])
		];
	}

	private function updatePathStack(array &$pathStack, array $parsed): void {
		// Trim to current depth
		$pathStack = array_slice($pathStack, 0, $parsed['depth']);

		// Add current directory to stack (for building child paths)
		if ($parsed['type'] === 'directory') {
			$pathStack[] = $parsed['name'];
		}
	}

	private function extractContent(string $name): ?string {
		// Check for inline content specification
		if (preg_match('/\s*\[(.+?)\]$/', $name, $matches)) {
			return $matches[1];
		}

		// Default content for common file types
		$extension = pathinfo($name, PATHINFO_EXTENSION);

		return match (strtolower($extension)) {
			'php'         => "<?php\n\ndeclare(strict_types=1);\n",
			'js'          => "'use strict';\n",
			'css'         => "/* Stylesheet */\n",
			'html'        => "<!DOCTYPE html>\n<html>\n<head>\n\t<title>Page Title</title>\n</head>\n<body>\n\n</body>\n</html>\n",
			'md'          => "# Title\n\nContent here.\n",
			'json'        => "{\n\t\n}\n",
			'yml', 'yaml' => "# Configuration\n",
			'txt'         => "",
			default       => null
		};
	}

	private function validateAndCleanStructure(array $structure): array {
		$validated = [];
		$seenPaths = [];

		foreach ($structure as $item) {
			// Check for duplicates
			if (in_array($item['path'], $seenPaths)) {
				continue;
			}

			// Validate path
			if (empty($item['path']) || $item['path'] === '.') {
				continue;
			}

			$seenPaths[] = $item['path'];
			$validated[] = $item;
		}

		// Sort by path to ensure directories are created before their contents
		usort($validated, function ($a, $b) {
			return strcmp($a['path'], $b['path']);
		});

		return $validated;
	}
}