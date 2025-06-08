<?php
namespace Yohns\ProjectStructure\Exception;

use Yohns\ProjectStructure\Exception\ProjectStructureException;

/**
 * Exception for errors during markdown parsing
 */
class ParseException extends ProjectStructureException {
	public static function invalidMarkdownFormat(int $lineNumber = 0, string $line = ''): self {
		$message = "Invalid markdown format";
		if ($lineNumber > 0) {
			$message .= " at line {$lineNumber}";
		}
		if ($line) {
			$message .= ": '{$line}'";
		}

		return new self($message, 0, null, [
			'type'         => 'invalid_format',
			'line_number'  => $lineNumber,
			'line_content' => $line
		]);
	}

	public static function codeBlockNotFound(): self {
		return new self(
			"No code block found in markdown content. Structure must be inside ```code blocks```",
			0,
			null,
			[
				'type' => 'missing_code_block'
			]
		);
	}

	public static function invalidTreeStructure(int $lineNumber, string $reason): self {
		return new self(
			"Invalid tree structure at line {$lineNumber}: {$reason}",
			0,
			null,
			[
				'type'        => 'invalid_tree',
				'line_number' => $lineNumber,
				'reason'      => $reason
			]
		);
	}

	public static function unsupportedTreeSymbol(string $symbol, int $lineNumber): self {
		return new self(
			"Unsupported tree symbol '{$symbol}' at line {$lineNumber}",
			0,
			null,
			[
				'type'        => 'unsupported_symbol',
				'symbol'      => $symbol,
				'line_number' => $lineNumber
			]
		);
	}

	public static function emptyStructure(): self {
		return new self(
			"Empty or invalid structure found in markdown",
			0,
			null,
			[
				'type' => 'empty_structure'
			]
		);
	}
}
