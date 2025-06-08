<?php

namespace Yohns\ProjectStructure\Exception;

use Yohns\ProjectStructure\Exception\ProjectStructureException;

/**
 * Exception for errors during structure creation
 */
class StructureCreationException extends ProjectStructureException {
	public static function directoryCreationFailed(string $path, string $reason = ''): self {
		$message = "Failed to create directory '{$path}'";
		if ($reason) {
			$message .= ": {$reason}";
		}

		return new self($message, 0, null, [
			'type'   => 'directory_creation',
			'path'   => $path,
			'reason' => $reason
		]);
	}

	public static function fileCreationFailed(string $path, string $reason = ''): self {
		$message = "Failed to create file '{$path}'";
		if ($reason) {
			$message .= ": {$reason}";
		}

		return new self($message, 0, null, [
			'type'   => 'file_creation',
			'path'   => $path,
			'reason' => $reason
		]);
	}

	public static function permissionDenied(string $path): self {
		return new self(
			"Permission denied when creating '{$path}'",
			0,
			null,
			[
				'type' => 'permission_denied',
				'path' => $path
			]
		);
	}

	public static function pathAlreadyExists(string $path): self {
		return new self(
			"Path '{$path}' already exists",
			0,
			null,
			[
				'type' => 'path_exists',
				'path' => $path
			]
		);
	}

	public static function invalidTargetDirectory(string $path): self {
		return new self(
			"Target directory '{$path}' is not valid or accessible",
			0,
			null,
			[
				'type' => 'invalid_target',
				'path' => $path
			]
		);
	}
}
