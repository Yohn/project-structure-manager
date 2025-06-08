<?php

namespace Yohns\ProjectStructure\Exception;

use Yohns\ProjectStructure\Exception\ProjectStructureException;

/**
 * Exception for validation errors
 */
class ValidationException extends ProjectStructureException {
	public static function invalidPath(string $path, string $reason = ''): self {
		$message = "Invalid path '{$path}'";
		if ($reason) {
			$message .= ": {$reason}";
		}

		return new self($message, 0, null, [
			'type'   => 'invalid_path',
			'path'   => $path,
			'reason' => $reason
		]);
	}

	public static function reservedFilename(string $filename): self {
		return new self(
			"Reserved filename '{$filename}' cannot be used",
			0,
			null,
			[
				'type'     => 'reserved_filename',
				'filename' => $filename
			]
		);
	}

	public static function duplicatePath(string $path): self {
		return new self(
			"Duplicate path found: '{$path}'",
			0,
			null,
			[
				'type' => 'duplicate_path',
				'path' => $path
			]
		);
	}

	public static function invalidCharacters(string $path, array $invalidChars): self {
		return new self(
			"Path '{$path}' contains invalid characters: " . implode(', ', $invalidChars),
			0,
			null,
			[
				'type'               => 'invalid_characters',
				'path'               => $path,
				'invalid_characters' => $invalidChars
			]
		);
	}

	public static function pathTooLong(string $path, int $maxLength): self {
		return new self(
			"Path '{$path}' exceeds maximum length of {$maxLength} characters",
			0,
			null,
			[
				'type'          => 'path_too_long',
				'path'          => $path,
				'max_length'    => $maxLength,
				'actual_length' => strlen($path)
			]
		);
	}

	public static function depthExceeded(string $path, int $maxDepth): self {
		return new self(
			"Path '{$path}' exceeds maximum depth of {$maxDepth}",
			0,
			null,
			[
				'type'         => 'depth_exceeded',
				'path'         => $path,
				'max_depth'    => $maxDepth,
				'actual_depth' => substr_count($path, '/')
			]
		);
	}
}
