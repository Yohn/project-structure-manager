<?php

namespace Yohns\ProjectStructure\Exception;

use Yohns\ProjectStructure\Exception\ProjectStructureException;

/**
 * Exception for template-related errors
 */
class TemplateException extends ProjectStructureException {
	public static function templateNotFound(string $templateName): self {
		return new self(
			"Template '{$templateName}' not found",
			0,
			null,
			[
				'type'          => 'template_not_found',
				'template_name' => $templateName
			]
		);
	}

	public static function invalidTemplateFormat(string $templateName, string $reason = ''): self {
		$message = "Invalid template format for '{$templateName}'";
		if ($reason) {
			$message .= ": {$reason}";
		}

		return new self($message, 0, null, [
			'type'          => 'invalid_template_format',
			'template_name' => $templateName,
			'reason'        => $reason
		]);
	}

	public static function missingRequiredVariable(string $variableName, string $templateName = ''): self {
		$message = "Required template variable '{$variableName}' is missing";
		if ($templateName) {
			$message .= " for template '{$templateName}'";
		}

		return new self($message, 0, null, [
			'type'          => 'missing_variable',
			'variable_name' => $variableName,
			'template_name' => $templateName
		]);
	}

	public static function variableProcessingError(string $variableName, string $error): self {
		return new self(
			"Error processing template variable '{$variableName}': {$error}",
			0,
			null,
			[
				'type'          => 'variable_processing_error',
				'variable_name' => $variableName,
				'error'         => $error
			]
		);
	}

	public static function templateReadError(string $templatePath, string $reason = ''): self {
		$message = "Failed to read template from '{$templatePath}'";
		if ($reason) {
			$message .= ": {$reason}";
		}

		return new self($message, 0, null, [
			'type'          => 'template_read_error',
			'template_path' => $templatePath,
			'reason'        => $reason
		]);
	}

	public static function invalidConditionalSyntax(string $condition, int $lineNumber = 0): self {
		$message = "Invalid conditional syntax: '{$condition}'";
		if ($lineNumber > 0) {
			$message .= " at line {$lineNumber}";
		}

		return new self($message, 0, null, [
			'type'        => 'invalid_conditional',
			'condition'   => $condition,
			'line_number' => $lineNumber
		]);
	}
}
