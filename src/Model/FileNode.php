<?php

declare(strict_types=1);

namespace Yohns\ProjectStructure\Model;

class FileNode extends StructureNode {
	private ?string $content;
	private ?int    $size;

	public function __construct(string $name, string $path, ?string $content = null, ?int $size = null) {
		parent::__construct($name, $path);
		$this->content = $content;
		$this->size = $size;
	}

	public function getType(): string {
		return 'file';
	}

	public function getContent(): ?string {
		return $this->content;
	}

	public function setContent(string $content): void {
		$this->content = $content;
	}

	public function getSize(): ?int {
		return $this->size;
	}

	public function setSize(int $size): void {
		$this->size = $size;
	}

	public function getExtension(): string {
		return pathinfo($this->name, PATHINFO_EXTENSION);
	}

	public function getBasename(): string {
		return pathinfo($this->name, PATHINFO_FILENAME);
	}
}