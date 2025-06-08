<?php

declare(strict_types=1);

namespace Yohns\ProjectStructure\Model;

use Yohns\ProjectStructure\Model\StructureNode;

class DirectoryNode extends StructureNode {
	/** @var StructureNode[] */
	private array $children = [];

	public function getType(): string {
		return 'directory';
	}

	public function addChild(StructureNode $child): void {
		$this->children[] = $child;
	}

	/** @return StructureNode[] */
	public function getChildren(): array {
		// Sort children: directories first, then files, both alphabetically
		usort($this->children, function (StructureNode $a, StructureNode $b) {
			if ($a->getType() !== $b->getType()) {
				return $a->getType() === 'directory' ? -1 : 1;
			}
			return strcasecmp($a->getName(), $b->getName());
		});

		return $this->children;
	}

	public function findChild(string $name): ?StructureNode {
		foreach ($this->children as $child) {
			if ($child->getName() === $name) {
				return $child;
			}
		}
		return null;
	}

	public function hasChildren(): bool {
		return !empty($this->children);
	}

	public function getChildCount(): int {
		return count($this->children);
	}

	public function getDirectoryCount(): int {
		$count = 0;
		foreach ($this->children as $child) {
			if ($child instanceof DirectoryNode) {
				$count++;
				$count += $child->getDirectoryCount();
			}
		}
		return $count;
	}

	public function getFileCount(): int {
		$count = 0;
		foreach ($this->children as $child) {
			if ($child instanceof FileNode) {
				$count++;
			} elseif ($child instanceof DirectoryNode) {
				$count += $child->getFileCount();
			}
		}
		return $count;
	}
}
