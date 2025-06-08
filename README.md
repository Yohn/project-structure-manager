# Project Structure Manager

A powerful PHP 8.3+ composer library for generating and creating project directory structures using Markdown templates.

> The tests are not currently functioning properly. <br>If I get time I'll trying to fix them. Pull requests are always welcome!

## Features

- **Generate STRUCTURE.md** - Scan any directory and create a markdown representation of its structure
- **Create from Templates** - Build project structures from predefined or custom markdown templates
- **CLI Interface** - Easy-to-use command line interface with tab completion
- **Template Variables** - Support for dynamic templates with variable substitution
- **Validation** - Built-in validation for structure files and templates
- **Dry Run Mode** - Preview what would be created before actually creating it
- **Extensible** - Object-oriented design following PHP 8.3+ best practices

## Installation

```bash
composer require yohns/project-structure-manager
```

## CLI Usage

### Generate Structure Command

```bash
./vendor/bin/project-structure generate [path] [options]
```

#### Arguments
- `path` - Directory to scan (default: current directory)

#### Options
- `--output`, `-o` - Output file path (default: `STRUCTURE.md`)
- `--exclude`, `-e` - Patterns to exclude (can be used multiple times)
- `--max-depth`, `-d` - Maximum directory depth (default: `10`)
- `--show-preview`, `-p` - Show preview before saving

#### Examples
```bash
# Basic generation
./vendor/bin/project-structure generate

# Custom output location
./vendor/bin/project-structure generate --output=docs/structure.md

# Exclude patterns
./vendor/bin/project-structure generate \
  --exclude=vendor \
  --exclude=node_modules \
  --exclude=.git \
  --exclude="*.log"

# Limit depth and preview
./vendor/bin/project-structure generate \
  --max-depth=5 \
  --show-preview

# Scan specific directory
./vendor/bin/project-structure generate /path/to/project \
  --output=/docs/project-structure.md
```

### Create Structure Command

```bash
./vendor/bin/project-structure create <structure-file> [options]
```

#### Arguments
- `structure-file` - Path to STRUCTURE.md file or template name

#### Options
- `--target`, `-t` - Target directory (default: current directory)
- `--template` - Treat input as template name
- `--variables`, `-v` - Template variables (key=value, can be used multiple times)
- `--dry-run` - Preview without creating
- `--force`, `-f` - Force creation/overwrite
- `--validate-only` - Only validate structure

#### Examples
```bash
# Create from file
./vendor/bin/project-structure create structure.md

# Target specific directory
./vendor/bin/project-structure create structure.md --target=new-project

# Dry run preview
./vendor/bin/project-structure create structure.md --dry-run

# Use template with variables
./vendor/bin/project-structure create php-library --template \
  --variables PROJECT_NAME=MyLibrary \
  --variables AUTHOR="John Doe" \
  --variables NAMESPACE=MyLib

# Validate only
./vendor/bin/project-structure create structure.md --validate-only

# Force creation
./vendor/bin/project-structure create structure.md \
  --target=existing-dir \
  --force
```

## PHP API Usage

### StructureGenerator

#### Constructor Options
```php
use Yohns\ProjectStructure\Service\StructureGenerator;

// Scan current directory
$generator = new StructureGenerator();

// Scan specific directory
$generator = new StructureGenerator('/path/to/scan');
```

#### Configuration Methods
```php
// Set exclude patterns
$generator->setExcludePatterns([
    'vendor',
    'node_modules',
    '.git',
    '.DS_Store',
    '*.tmp',
    '*.log',
    'cache/*',
    'temp*'
]);
```

#### Generation Methods
```php
// Generate structure (path, maxDepth)
$structure = $generator->generateStructure('', 10);

// Generate markdown
$markdown = $generator->generateMarkdown($structure);

// Save to file
$generator->saveToFile($markdown, 'custom-structure.md');
$generator->saveToFile($markdown, '/full/path/structure.md');
```

#### Complete Example
```php
$generator = new StructureGenerator('/my/project');
$generator->setExcludePatterns(['vendor', 'node_modules', '.git']);

$structure = $generator->generateStructure('', 8);
$markdown = $generator->generateMarkdown($structure);
$generator->saveToFile($markdown, 'docs/project-structure.md');
```

### StructureCreator

#### Constructor Options
```php
use Yohns\ProjectStructure\Service\StructureCreator;

// Create in current directory
$creator = new StructureCreator();

// Create in specific directory
$creator = new StructureCreator('/target/path');
```

#### Creation Methods
```php
// From markdown file
$result = $creator->createFromMarkdownFile('structure.md', $dryRun = false);

// From markdown content
$content = file_get_contents('structure.md');
$result = $creator->createFromMarkdownContent($content, $dryRun = false);

// From template
$variables = ['PROJECT_NAME' => 'MyApp', 'AUTHOR' => 'John Doe'];
$result = $creator->createFromTemplate('php-library', $variables, $dryRun = false);
```

#### Validation
```php
// Validate structure
$errors = $creator->validateStructure($content);
if (empty($errors)) {
    $result = $creator->createFromMarkdownContent($content);
} else {
    foreach ($errors as $error) {
        echo "Error: {$error}\n";
    }
}
```

#### Complete Example
```php
$creator = new StructureCreator('/new/project');

// Validate first
$content = file_get_contents('my-structure.md');
$errors = $creator->validateStructure($content);

if (empty($errors)) {
    // Dry run preview
    $preview = $creator->createFromMarkdownContent($content, true);
    echo "Would create " . count($preview['files']) . " files\n";

    // Create for real
    $result = $creator->createFromMarkdownContent($content, false);
    echo "Created " . count($result['files']) . " files\n";
}
```

## Configuration Options Reference

### Exclude Patterns
```php
// Default patterns
$defaultExcludes = [
    'vendor',       // Composer dependencies
    'node_modules', // NPM dependencies
    '.git',         // Git repository
    '.DS_Store',    // macOS system files
    '*.tmp',        // Temporary files
    '*.log'         // Log files
];

// Pattern types
$patterns = [
    'exact-name',           // Exact directory/file name
    '*.extension',          // File extension wildcard
    'prefix*',              // Prefix wildcard
    '*suffix',              // Suffix wildcard
    'dir/*',                // Directory contents
    '.hidden',              // Hidden files/directories
];
```

### Template Variables
```php
// Simple substitution
$variables = [
    'PROJECT_NAME' => 'MyProject',
    'AUTHOR' => 'John Doe',
    'NAMESPACE' => 'MyProject\\Core',
    'MAIN_CLASS' => 'Application'
];

// Conditional variables
$variables = [
    'DOCS' => true,         // {{if DOCS}}content{{/if}}
    'LICENSE' => 'MIT',     // {{if LICENSE}}content{{/if}}
    'TESTING' => false      // Empty = false condition
];
```

### File Content Templates
Default content by extension:
- `.php` - PHP declaration header
- `.js` - 'use strict' directive
- `.html` - Basic HTML structure
- `.json` - Empty JSON object `{}`
- `.md` - Basic markdown structure
- `.yml/.yaml` - YAML comment header
- `.css` - CSS comment header

### Validation Rules
- Path length limits
- Invalid characters check
- Reserved filename detection
- Duplicate path prevention
- Directory depth limits
- File permission validation

## Built-in Templates

### php-library
Standard PHP library structure with PSR-4 autoloading.

**Variables:**
- `PROJECT_NAME` (required)
- `NAMESPACE` (optional)
- `MAIN_CLASS` (optional)
- `AUTHOR` (optional)
- `DESCRIPTION` (optional)

### web-app
Basic web application with MVC structure.

**Variables:**
- `PROJECT_NAME` (required)
- `FRAMEWORK` (optional)
- `DATABASE` (optional)

## Error Handling

The library provides specific exception types:

- `StructureCreationException` - File/directory creation errors
- `ParseException` - Markdown parsing errors
- `ValidationException` - Structure validation errors
- `TemplateException` - Template processing errors
- `FilesystemException` - File system access errors

## Requirements

- PHP 8.3+
- Composer
- Extensions: json, mbstring

## Dependencies

- **symfony/console** - CLI framework
- **league/flysystem** - Filesystem abstraction
- **league/commonmark** - Markdown processing
- **symfony/filesystem** - File operations

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes following PSR-12 standards
4. Add tests for new functionality
5. Submit a pull request

## License

MIT License - see LICENSE file for details.