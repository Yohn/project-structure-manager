<?php

declare(strict_types=1);

namespace Yohns\ProjectStructure\Command;

use Yohns\ProjectStructure\Service\StructureCreator;
use Yohns\ProjectStructure\Exception\{StructureCreationException, TemplateException};
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument, InputInterface, InputOption};
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
	name: 'create',
	description: 'Create directory structure from a STRUCTURE.md file or template'
)]
class CreateFromStructureCommand extends Command {
	protected function configure(): void {
		$this
			->addArgument(
				'structure-file',
				InputArgument::REQUIRED,
				'Path to the STRUCTURE.md file or template name'
			)
			->addOption(
				'target',
				't',
				InputOption::VALUE_REQUIRED,
				'Target directory where structure should be created',
				'.'
			)
			->addOption(
				'template',
				null,
				InputOption::VALUE_NONE,
				'Treat the input as a template name instead of a file path'
			)
			->addOption(
				'variables',
				'v',
				InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
				'Template variables in format key=value',
				[]
			)
			->addOption(
				'dry-run',
				null,
				InputOption::VALUE_NONE,
				'Show what would be created without actually creating anything'
			)
			->addOption(
				'force',
				'f',
				InputOption::VALUE_NONE,
				'Force creation even if files already exist'
			)
			->addOption(
				'validate-only',
				null,
				InputOption::VALUE_NONE,
				'Only validate the structure without creating it'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$io = new SymfonyStyle($input, $output);

		$structureInput = $input->getArgument('structure-file');
		$targetDir = $input->getOption('target');
		$isTemplate = $input->getOption('template');
		$variableStrings = $input->getOption('variables');
		$dryRun = $input->getOption('dry-run');
		$force = $input->getOption('force');
		$validateOnly = $input->getOption('validate-only');

		// Parse variables
		$variables = $this->parseVariables($variableStrings);

		// Validate target directory
		if (!is_dir($targetDir)) {
			if (!$force) {
				$io->error("Target directory '{$targetDir}' does not exist. Use --force to create it.");
				return Command::FAILURE;
			}

			if (!mkdir($targetDir, 0755, true)) {
				$io->error("Failed to create target directory '{$targetDir}'.");
				return Command::FAILURE;
			}
		}

		$io->title('Project Structure Creator');

		if ($isTemplate) {
			$io->text("Using template: <info>{$structureInput}</info>");
		} else {
			$io->text("Reading structure from: <info>{$structureInput}</info>");
		}

		$io->text("Target directory: <info>{$targetDir}</info>");

		if (!empty($variables)) {
			$io->text("Template variables:");
			foreach ($variables as $key => $value) {
				$io->text("  <comment>{$key}</comment> = <info>{$value}</info>");
			}
		}

		try {
			$creator = new StructureCreator($targetDir);

			// Read or generate content
			if ($isTemplate) {
				$content = $this->getTemplateContent($structureInput, $variables, $creator);
			} else {
				if (!file_exists($structureInput)) {
					$io->error("Structure file '{$structureInput}' not found.");
					return Command::FAILURE;
				}
				$content = file_get_contents($structureInput);
			}

			// Validate structure
			$errors = $creator->validateStructure($content);
			if (!empty($errors)) {
				$io->error('Structure validation failed:');
				foreach ($errors as $error) {
					$io->text("  • {$error}");
				}
				return Command::FAILURE;
			}

			if ($validateOnly) {
				$io->success('Structure validation passed!');
				return Command::SUCCESS;
			}

			// Show preview in dry-run mode
			if ($dryRun) {
				$io->section('Dry Run - What would be created:');
			}

			// Create structure
			if ($isTemplate) {
				$created = $creator->createFromTemplate(
					$structureInput,
					$variables,
					$dryRun
				);
			} else {
				$created = $creator->createFromMarkdownContent($content, $dryRun);
			}

			// Display results
			$this->displayResults($io, $created, $dryRun);

			if ($dryRun) {
				$io->note('This was a dry run. Use without --dry-run to actually create the structure.');
			} else {
				$io->success('Structure created successfully!');
			}

		} catch (StructureCreationException $e) {
			$io->error("Structure creation failed: " . $e->getMessage());
			return Command::FAILURE;
		} catch (\Exception $e) {
			$io->error("Unexpected error: " . $e->getMessage());
			return Command::FAILURE;
		}

		return Command::SUCCESS;
	}

	private function parseVariables(array $variableStrings): array {
		$variables = [];

		foreach ($variableStrings as $variable) {
			if (strpos($variable, '=') === false) {
				continue;
			}

			[$key, $value] = explode('=', $variable, 2);
			$variables[trim($key)] = trim($value);
		}

		return $variables;
	}

	private function getTemplateContent(string $templateName, array $variables, StructureCreator $creator): string {
		// Templates are stored in the templates/ directory relative to the library
		$templatePath = "templates/{$templateName}.md";

		// Check if template exists in the current working directory first
		if (file_exists($templatePath)) {
			$templateContent = file_get_contents($templatePath);
		} else {
			// Fallback to built-in templates (assuming they're in vendor package)
			$builtInTemplatePath = __DIR__ . "/../../templates/{$templateName}.md";
			if (file_exists($builtInTemplatePath)) {
				$templateContent = file_get_contents($builtInTemplatePath);
			} else {
				throw TemplateException::templateNotFound($templateName);
			}
		}

		// Process template variables and conditionals
		return $this->processTemplateContent($templateContent, $variables);
	}

	private function processTemplateContent(string $content, array $variables): string {
		$processed = $content;

		// Replace simple variables {{VARIABLE_NAME}}
		foreach ($variables as $key => $value) {
			$processed = str_replace("{{" . $key . "}}", (string) $value, $processed);
		}

		// Handle conditional blocks {{if VARIABLE}}content{{/if}}
		$processed = preg_replace_callback(
			'/\{\{if\s+(\w+)\}\}(.*?)\{\{\/if\}\}/s',
			function ($matches) use ($variables) {
				$variableName = $matches[1];
				$conditionalContent = $matches[2];

				// Include content only if variable exists and is not empty
				return !empty($variables[$variableName]) ? $conditionalContent : '';
			},
			$processed
		);

		return $processed;
	}

	private function displayResults(SymfonyStyle $io, array $created, bool $dryRun): void {
		if (!empty($created['directories'])) {
			$action = $dryRun ? 'Would create directories' : 'Created directories';
			$io->section($action . ':');
			foreach ($created['directories'] as $dir) {
				$io->text("  📁 {$dir}");
			}
		}

		if (!empty($created['files'])) {
			$action = $dryRun ? 'Would create files' : 'Created files';
			$io->section($action . ':');
			foreach ($created['files'] as $file) {
				$io->text("  📄 {$file}");
			}
		}

		// Summary table
		$summary = [
			['Type', 'Count'],
			['Directories', (string) count($created['directories'])],
			['Files', (string) count($created['files'])],
			['Total Items', (string) (count($created['directories']) + count($created['files']))]
		];

		$io->table($summary[0], array_slice($summary, 1));
	}
}