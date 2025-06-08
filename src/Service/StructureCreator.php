<?php

declare(strict_types=1);

namespace Yohns\ProjectStructure\Service;

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Yohns\ProjectStructure\Parser\StructureParser;
use Yohns\ProjectStructure\Exception\StructureCreationException;

class StructureCreator {
	private Filesystem      $filesystem;
	private StructureParser $parser;

	public function __construct(?string $rootPath = null) {
		$adapter = new LocalFilesystemAdapter($rootPath ?? getcwd());
		$this->filesystem = new Filesystem($adapter);
		$this->parser = new StructureParser();
	}

	public function createFromMarkdownFile(string $structureFile, bool $dryRun = false): array {
		if (!$this->filesystem->fileExists($structureFile)) {
			throw new StructureCreationException("Structure file '{$structureFile}' not found");
		}

		$content = $this->filesystem->read($structureFile);
		return $this->createFromMarkdownContent($content, $dryRun);
	}

	public function createFromMarkdownContent(string $content, bool $dryRun = false): array {
		$structure = $this->parser->parseMarkdown($content);

		$created = [
			'directories' => [],
			'files'       => []
		];

		foreach ($structure as $item) {
			if ($item['type'] === 'directory') {
				$this->createDirectory($item['path'], $dryRun);
				$created['directories'][] = $item['path'];
			} elseif ($item['type'] === 'file') {
				$this->createFile($item['path'], $item['content'] ?? '', $dryRun);
				$created['files'][] = $item['path'];
			}
		}

		return $created;
	}

	public function createFromTemplate(string $templateName, array $variables = [], bool $dryRun = false): array {
		$templatePath = "templates/{$templateName}.md";

		if (!$this->filesystem->fileExists($templatePath)) {
			throw new StructureCreationException("Template '{$templateName}' not found");
		}

		$templateContent = $this->filesystem->read($templatePath);
		$processedContent = $this->processTemplate($templateContent, $variables);

		return $this->createFromMarkdownContent($processedContent, $dryRun);
	}

	private function createDirectory(string $path, bool $dryRun): void {
		if ($dryRun) {
			return;
		}

		try {
			if (!$this->filesystem->directoryExists($path)) {
				// Ensure parent directories exist first
				$parentDir = dirname($path);
				if ($parentDir !== '.' && $parentDir !== $path && !$this->filesystem->directoryExists($parentDir)) {
					$this->createDirectory($parentDir, false);
				}

				$this->filesystem->createDirectory($path);
			}
		} catch (\Exception $e) {
			throw StructureCreationException::directoryCreationFailed($path, $e->getMessage());
		}
	}

	private function createFile(string $path, string $content, bool $dryRun): void {
		if ($dryRun) {
			return;
		}

		try {
			// Ensure parent directory exists
			$directory = dirname($path);
			if ($directory !== '.' && $directory !== $path && !$this->filesystem->directoryExists($directory)) {
				$this->createDirectory($directory, false);
			}

			$this->filesystem->write($path, $content);
		} catch (\Exception $e) {
			throw StructureCreationException::fileCreationFailed($path, $e->getMessage());
		}
	}

	private function processTemplate(string $template, array $variables): string {
		$processed = $template;

		foreach ($variables as $key => $value) {
			$processed = str_replace("{{$key}}", (string) $value, $processed);
		}

		// Handle conditional blocks
		$processed = preg_replace_callback(
			'/\{\{if\s+(\w+)\}\}(.*?)\{\{\/if\}\}/s',
			function ($matches) use ($variables) {
				$variable = $matches[1];
				$content = $matches[2];

				return !empty($variables[$variable]) ? $content : '';
			},
			$processed
		);

		return $processed;
	}

	public function validateStructure(string $content): array {
		$errors = [];

		try {
			$structure = $this->parser->parseMarkdown($content);
		} catch (\Exception $e) {
			$errors[] = "Parse error: " . $e->getMessage();
			return $errors;
		}

		// Validate paths
		foreach ($structure as $item) {
			if (empty($item['path'])) {
				$errors[] = "Empty path found in structure";
				continue;
			}

			// Check for invalid characters
			if (preg_match('/[<>:"|?*]/', $item['path'])) {
				$errors[] = "Invalid characters in path: {$item['path']}";
			}

			// Check for reserved names (Windows)
			$basename = basename($item['path']);
			$reserved = ['CON', 'PRN', 'AUX', 'NUL', 'COM1', 'COM2', 'COM3', 'COM4', 'COM5', 'COM6', 'COM7', 'COM8', 'COM9', 'LPT1', 'LPT2', 'LPT3', 'LPT4', 'LPT5', 'LPT6', 'LPT7', 'LPT8', 'LPT9'];
			if (in_array(strtoupper($basename), $reserved)) {
				$errors[] = "Reserved filename: {$item['path']}";
			}
		}

		return $errors;
	}
}