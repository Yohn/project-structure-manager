<?php
namespace Yohns\ProjectStructure\Exception;

use Yohns\ProjectStructure\Exception\ProjectStructureException;

/**
 * Exception for filesystem-related errors
 */
class FilesystemException extends ProjectStructureException {
	public static function pathNotFound(string $path): self {
		return new self(
			"Path '{$path}' not found",
			0,
			null,
			[
				'type' => 'path_not_found',
				'path' => $path
			]
		);
	}

	public static function notReadable(string $path): self {
		return new self(
			"Path '{$path}' is not readable",
			0,
			null,
			[
				'type' => 'not_readable',
				'path' => $path
			]
		);
	}

	public static function notWritable(string $path): self {
		return new self(
			"Path '{$path}' is not writable",
			0,
			null,
			[
				'type' => 'not_writable',
				'path' => $path
			]
		);
	}

	public static function diskSpaceExceeded(string $path, int $requiredBytes, int $availableBytes): self {
		return new self(
			"Insufficient disk space at '{$path}'. Required: {$requiredBytes} bytes, Available: {$availableBytes} bytes",
			0,
			null,
			[
				'type'            => 'disk_space_exceeded',
				'path'            => $path,
				'required_bytes'  => $requiredBytes,
				'available_bytes' => $availableBytes
			]
		);
	}
}