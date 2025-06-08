<?php

declare(strict_types=1);

namespace Yohns\ProjectStructure\Exception;

/**
 * Base exception for all project structure related errors
 */
class ProjectStructureException extends \Exception {
	protected array $context = [];

	public function __construct(
		string $message = '',
		int $code = 0,
		?\Throwable $previous = null,
		array $context = []
	) {
		parent::__construct($message, $code, $previous);
		$this->context = $context;
	}

	public function getContext(): array {
		return $this->context;
	}

	public function setContext(array $context): void {
		$this->context = $context;
	}

	public function addContext(string $key, mixed $value): void {
		$this->context[$key] = $value;
	}
}
