<?php

declare(strict_types=1);

namespace Yohns\ProjectStructure\Command;

use Yohns\ProjectStructure\Service\StructureGenerator;
use Yohns\ProjectStructure\Model\DirectoryNode;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument, InputInterface, InputOption};
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
	name: 'generate',
	description: 'Generate a STRUCTURE.md file from the current directory structure'
)]
class GenerateStructureCommand extends Command {
	protected function configure(): void {
		$this
			->addArgument(
				'path',
				InputArgument::OPTIONAL,
				'Path to scan for structure (defaults to current directory)',
				'.'
			)
			->addOption(
				'output',
				'o',
				InputOption::VALUE_REQUIRED,
				'Output file name',
				'STRUCTURE.md'
			)
			->addOption(
				'exclude',
				'e',
				InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
				'Patterns to exclude from the structure',
				[] // Don't set defaults here
			)
			->addOption(
				'max-depth',
				'd',
				InputOption::VALUE_REQUIRED,
				'Maximum directory depth to scan',
				'10'
			)
			->addOption(
				'show-preview',
				'p',
				InputOption::VALUE_NONE,
				'Show preview of the structure before saving'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$io = new SymfonyStyle($input, $output);

		$path = $input->getArgument('path');
		$outputFile = $input->getOption('output');
		$cliExcludes = $input->getOption('exclude');
		$maxDepth = (int) $input->getOption('max-depth');
		$showPreview = $input->getOption('show-preview');

		// Merge CLI excludes with defaults
		$defaultExcludes = ['vendor', 'node_modules', '.git', '.DS_Store', '*.tmp', '*.log'];
		$excludePatterns = array_merge($defaultExcludes, $cliExcludes);

		// Validate path
		if (!is_dir($path)) {
			$io->error("Directory '{$path}' does not exist.");
			return Command::FAILURE;
		}

		$io->title('Project Structure Generator');
		$io->text("Scanning directory: <info>{$path}</info>");
		$io->text("Max depth: <info>{$maxDepth}</info>");
		$io->text("Exclude patterns: <comment>" . implode(', ', $excludePatterns) . "</comment>");

		try {
			$generator = new StructureGenerator($path);
			$generator->setExcludePatterns($excludePatterns);

			$io->text('Analyzing directory structure...');
			$structure = $generator->generateStructure('', $maxDepth);

			$markdown = $generator->generateMarkdown($structure);

			if ($showPreview) {
				$io->section('Preview');
				$io->block($markdown, null, 'fg=cyan');

				if (!$io->confirm('Do you want to save this structure?', true)) {
					$io->success('Operation cancelled.');
					return Command::SUCCESS;
				}
			}

			$generator->saveToFile($markdown, $outputFile);

			$directoryCount = 0;
			$fileCount = 0;

			if ($structure instanceof DirectoryNode) {
				$directoryCount = $structure->getDirectoryCount();
				$fileCount = $structure->getFileCount();
			}

			$stats = [
				['Metric', 'Count'],
				['Total Directories', (string) $directoryCount],
				['Total Files', (string) $fileCount],
				['Output File', $outputFile]
			];

			$io->table($stats[0], array_slice($stats, 1));
			$io->success("Structure generated successfully and saved to '{$outputFile}'");

		} catch (\Exception $e) {
			$io->error("Failed to generate structure: " . $e->getMessage());
			return Command::FAILURE;
		}

		return Command::SUCCESS;
	}
}