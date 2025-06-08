<?php

declare(strict_types=1);

namespace Yohns\ProjectStructure\Model;

abstract class StructureNode {
	protected string $name;
	protected string $path;

	public function __construct(string $name, string $path) {
		$this->name = $name;
		$this->path = $path;
	}

	public function getName(): string {
		return $this->name;
	}

	public function getPath(): string {
		return $this->path;
	}

	abstract public function getType(): string;
}