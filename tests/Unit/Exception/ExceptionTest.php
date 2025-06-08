<?php

declare(strict_types=1);

namespace Yohns\ProjectStructure\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Yohns\ProjectStructure\Exception\{
	ProjectStructureException,
	StructureCreationException,
	TemplateException,
	ValidationException,
	FilesystemException,
	ParseException
};

class ExceptionTest extends TestCase {
	public function testProjectStructureExceptionWithContext(): void {
		$context = ['type' => 'test', 'value' => 123];
		$exception = new ProjectStructureException('Test message', 0, null, $context);

		$this->assertEquals('Test message', $exception->getMessage());
		$this->assertEquals($context, $exception->getContext());
	}

	public function testProjectStructureExceptionCanAddContext(): void {
		$exception = new ProjectStructureException('Test');
		$exception->addContext('key', 'value');

		$this->assertEquals(['key' => 'value'], $exception->getContext());
	}

	public function testStructureCreationExceptionStaticMethods(): void {
		$dirException = StructureCreationException::directoryCreationFailed('/test/dir', 'Permission denied');
		$this->assertStringContainsString('Failed to create directory', $dirException->getMessage());
		$this->assertEquals('directory_creation', $dirException->getContext()['type']);

		$fileException = StructureCreationException::fileCreationFailed('/test/file.txt');
		$this->assertStringContainsString('Failed to create file', $fileException->getMessage());
		$this->assertEquals('file_creation', $fileException->getContext()['type']);

		$permissionException = StructureCreationException::permissionDenied('/protected');
		$this->assertStringContainsString('Permission denied', $permissionException->getMessage());
		$this->assertEquals('permission_denied', $permissionException->getContext()['type']);

		$existsException = StructureCreationException::pathAlreadyExists('/existing');
		$this->assertStringContainsString('already exists', $existsException->getMessage());
		$this->assertEquals('path_exists', $existsException->getContext()['type']);
	}

	public function testTemplateExceptionStaticMethods(): void {
		$notFoundException = TemplateException::templateNotFound('missing-template');
		$this->assertStringContainsString('not found', $notFoundException->getMessage());
		$this->assertEquals('template_not_found', $notFoundException->getContext()['type']);

		$formatException = TemplateException::invalidTemplateFormat('bad-template', 'Invalid syntax');
		$this->assertStringContainsString('Invalid template format', $formatException->getMessage());
		$this->assertEquals('invalid_template_format', $formatException->getContext()['type']);

		$variableException = TemplateException::missingRequiredVariable('PROJECT_NAME', 'test-template');
		$this->assertStringContainsString('Required template variable', $variableException->getMessage());
		$this->assertEquals('missing_variable', $variableException->getContext()['type']);
	}

	public function testValidationExceptionStaticMethods(): void {
		$pathException = ValidationException::invalidPath('/invalid<path>', 'Contains invalid characters');
		$this->assertStringContainsString('Invalid path', $pathException->getMessage());
		$this->assertEquals('invalid_path', $pathException->getContext()['type']);

		$reservedException = ValidationException::reservedFilename('CON');
		$this->assertStringContainsString('Reserved filename', $reservedException->getMessage());
		$this->assertEquals('reserved_filename', $reservedException->getContext()['type']);

		$duplicateException = ValidationException::duplicatePath('/duplicate');
		$this->assertStringContainsString('Duplicate path', $duplicateException->getMessage());
		$this->assertEquals('duplicate_path', $duplicateException->getContext()['type']);

		$charsException = ValidationException::invalidCharacters('/test', ['<', '>']);
		$this->assertStringContainsString('invalid characters', $charsException->getMessage());
		$this->assertEquals('invalid_characters', $charsException->getContext()['type']);

		$lengthException = ValidationException::pathTooLong('/very/long/path', 50);
		$this->assertStringContainsString('exceeds maximum length', $lengthException->getMessage());
		$this->assertEquals('path_too_long', $lengthException->getContext()['type']);

		$depthException = ValidationException::depthExceeded('/deep/path', 3);
		$this->assertStringContainsString('exceeds maximum depth', $depthException->getMessage());
		$this->assertEquals('depth_exceeded', $depthException->getContext()['type']);
	}

	public function testFilesystemExceptionStaticMethods(): void {
		$notFoundException = FilesystemException::pathNotFound('/missing');
		$this->assertStringContainsString('not found', $notFoundException->getMessage());
		$this->assertEquals('path_not_found', $notFoundException->getContext()['type']);

		$readableException = FilesystemException::notReadable('/protected');
		$this->assertStringContainsString('not readable', $readableException->getMessage());
		$this->assertEquals('not_readable', $readableException->getContext()['type']);

		$writableException = FilesystemException::notWritable('/readonly');
		$this->assertStringContainsString('not writable', $writableException->getMessage());
		$this->assertEquals('not_writable', $writableException->getContext()['type']);

		$spaceException = FilesystemException::diskSpaceExceeded('/full', 1000, 500);
		$this->assertStringContainsString('Insufficient disk space', $spaceException->getMessage());
		$this->assertEquals('disk_space_exceeded', $spaceException->getContext()['type']);
	}

	public function testParseExceptionStaticMethods(): void {
		$formatException = ParseException::invalidMarkdownFormat(10, 'bad line');
		$this->assertStringContainsString('Invalid markdown format', $formatException->getMessage());
		$this->assertEquals('invalid_format', $formatException->getContext()['type']);

		$codeBlockException = ParseException::codeBlockNotFound();
		$this->assertStringContainsString('No code block found', $codeBlockException->getMessage());
		$this->assertEquals('missing_code_block', $codeBlockException->getContext()['type']);

		$treeException = ParseException::invalidTreeStructure(5, 'Bad indentation');
		$this->assertStringContainsString('Invalid tree structure', $treeException->getMessage());
		$this->assertEquals('invalid_tree', $treeException->getContext()['type']);

		$symbolException = ParseException::unsupportedTreeSymbol('*', 3);
		$this->assertStringContainsString('Unsupported tree symbol', $symbolException->getMessage());
		$this->assertEquals('unsupported_symbol', $symbolException->getContext()['type']);

		$emptyException = ParseException::emptyStructure();
		$this->assertStringContainsString('Empty or invalid structure', $emptyException->getMessage());
		$this->assertEquals('empty_structure', $emptyException->getContext()['type']);
	}
}