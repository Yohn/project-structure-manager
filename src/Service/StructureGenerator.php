<?php

declare(strict_types=1);

namespace Yohns\ProjectStructure\Service;

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Yohns\ProjectStructure\Model\DirectoryNode;
use Yohns\ProjectStructure\Model\FileNode;
use Yohns\ProjectStructure\Model\StructureNode;

class StructureGenerator {
	private Filesystem $filesystem;
	private array      $excludePatterns = [
		'vendor',
		'node_modules',
		'.git',
		'.DS_Store',
		'*.tmp',
		'*.log'
	];

	public function __construct(?string $rootPath = null) {
		$adapter = new LocalFilesystemAdapter($rootPath ?? getcwd());
		$this->filesystem = new Filesystem($adapter);
	}

	public function setExcludePatterns(array $patterns): void {
		$this->excludePatterns = $patterns;
	}

	public function generateStructure(string $path = '', int $maxDepth = 10): StructureNode {
		$listing = $this->filesystem->listContents($path, true);

		$root = new DirectoryNode(basename($path) ?: 'root', $path);

		foreach ($listing as $item) {
			if ($this->shouldExclude($item->path())) {
				continue;
			}

			$depth = substr_count($item->path(), '/');
			if ($depth > $maxDepth) {
				continue;
			}

			$pathParts = explode('/', $item->path());
			$this->addNodeToTree($root, $pathParts, $item->isFile());
		}

		return $root;
	}

	public function generateMarkdown(StructureNode $structure): string {
		$markdown = "# Project Structure\n\n";
		$markdown .= "```\n";
		$markdown .= $this->renderNode($structure, 0);
		$markdown .= "```\n\n";
		$markdown .= "Generated on: " . date('Y-m-d H:i:s') . "\n";

		return $markdown;
	}

	public function saveToFile(string $content, string $filename = 'STRUCTURE.md'): void {
		$this->filesystem->write($filename, $content);
	}

	public function shouldExcludeTest(string $path): bool {
		return $this->shouldExclude($path);
	}

	private function shouldExclude(string $path): bool {
		$basename = basename($path);

		foreach ($this->excludePatterns as $pattern) {
			// Check exact match against full path
			if ($path === $pattern) {
				return true;
			}

			// Check exact match against basename
			if ($basename === $pattern) {
				return true;
			}

			// Check if path starts with pattern (for directory exclusion)
			if (str_starts_with($path, $pattern . '/')) {
				return true;
			}

			// Check wildcard patterns
			if (fnmatch($pattern, $path) || fnmatch($pattern, $basename)) {
				return true;
			}
		}

		return false;
	}

	private function addNodeToTree(DirectoryNode $root, array $pathParts, bool $isFile): void {
		$current = $root;
		$pathPartsCount = count($pathParts);

		// Navigate/create directories
		for ($i = 0; $i < $pathPartsCount - 1; $i++) {
			$partName = $pathParts[$i];
			$existing = $current->findChild($partName);

			if ($existing === null) {
				$newDir = new DirectoryNode($partName, implode('/', array_slice($pathParts, 0, $i + 1)));
				$current->addChild($newDir);
				$current = $newDir;
			} elseif ($existing instanceof DirectoryNode) {
				$current = $existing;
			}
		}

		// Add the final file or directory
		$finalName = end($pathParts);
		if ($isFile) {
			$current->addChild(new FileNode($finalName, implode('/', $pathParts)));
		} else {
			$existing = $current->findChild($finalName);
			if ($existing === null) {
				$current->addChild(new DirectoryNode($finalName, implode('/', $pathParts)));
			}
		}
	}

	private function renderNode(StructureNode $node, int $depth): string {
		$output = '';

		if ($depth === 0) {
			// Root node - no prefix
			$output .= $node->getName();
		} else {
			// Build prefix for current depth
			$prefix = str_repeat('│   ', $depth - 1) . '├── ';
			$output .= $prefix . $node->getName();
		}

		if ($node instanceof FileNode) {
			$output .= "\n";
		} else {
			$output .= "/\n";

			if ($node instanceof DirectoryNode) {
				$children = $node->getChildren();
				foreach ($children as $index => $child) {
					$isLast = $index === count($children) - 1;
					$prefix = str_repeat('│   ', $depth) . ($isLast ? '└── ' : '├── ');

					$output .= $this->renderNodeWithPrefix($child, $depth + 1, $prefix, $isLast);
				}
			}
		}

		return $output;
	}

	private function renderNodeWithPrefix(StructureNode $node, int $depth, string $prefix, bool $isLast): string {
		$output = $prefix . $node->getName();

		if ($node instanceof FileNode) {
			$output .= "\n";
		} else {
			$output .= "/\n";

			if ($node instanceof DirectoryNode) {
				$children = $node->getChildren();
				foreach ($children as $index => $child) {
					$isChildLast = $index === count($children) - 1;

					// Calculate prefix for child based on current node's position
					$childPrefix = '';
					for ($i = 0; $i < $depth; $i++) {
						if ($i === $depth - 1) {
							$childPrefix .= $isLast ? '    ' : '│   ';
						} else {
							$childPrefix .= '│   ';
						}
					}
					$childPrefix .= $isChildLast ? '└── ' : '├── ';

					$output .= $this->renderNodeWithPrefix($child, $depth + 1, $childPrefix, $isChildLast);
				}
			}
		}

		return $output;
	}
}