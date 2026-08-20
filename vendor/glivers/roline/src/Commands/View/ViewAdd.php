<?php namespace Roline\Commands\View;

/**
 * ViewAdd Command
 *
 * Adds a new view file to an existing view directory in application/views/.
 * Creates additional template files beyond the default index.php for handling
 * different views within the same logical grouping (e.g., show, create, edit
 * views for a posts directory).
 *
 * What Gets Created:
 *   - View file: application/views/{directory}/{file}.php
 *
 * Prerequisites:
 *   - Target view directory must already exist
 *   - Created via view:create command first
 *
 * Generated Template:
 *   - HTML5 boilerplate with combined directory/file name in title
 *   - Example: "Posts - Show" for posts/show.php
 *   - Responsive viewport meta tag
 *   - Placeholder content area for customization
 *
 * Common Use Cases:
 *   - Add show.php to display single record
 *   - Add create.php for creation forms
 *   - Add edit.php for update forms
 *   - Add list.php for alternative list views
 *
 * Safety Features:
 *   - Validates directory exists before creating file
 *   - Checks for file conflicts to prevent overwriting
 *   - Provides helpful error messages with suggested fixes
 *   - Reports success with View::render() usage instructions
 *
 * Usage:
 *   php roline view:add posts show
 *   php roline view:add users profile
 *   php roline view:add blog create
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2050 Geoffrey Okongo
 * @category Roline
 * @package Roline\Commands\View
 * @link https://github.com/glivers/roline
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.0
 */

use Rackage\File;
use Roline\Output;

class ViewAdd extends ViewCommand
{
    /**
     * Get command description for listing
     *
     * @return string Brief command description
     */
    public function description()
    {
        return 'Add a view file to existing directory';
    }

    /**
     * Get command usage syntax
     *
     * @return string Usage syntax showing required directory and file arguments
     */
    public function usage()
    {
        return '<directory|required> <file|required>';
    }

    /**
     * Display detailed help information
     *
     * Shows required arguments (directory and file name), examples of adding
     * different view files, what gets created, and how to render the view in
     * a controller using View::render().
     *
     * @return void
     */
    public function help()
    {
        parent::help();

        Output::info('Arguments:');
        Output::line('  <directory|required>  Name of the existing view directory');
        Output::line('  <file|required>       Name of the view file to create (without .php)');
        Output::line();

        Output::info('Examples:');
        Output::line('  php roline view:add posts show');
        Output::line('  php roline view:add posts create');
        Output::line('  php roline view:add users profile');
        Output::line();

        Output::info('Creates:');
        Output::line('  application/views/posts/show.php');
        Output::line();

        Output::info('Usage in Controller:');
        Output::line('  View::render(\'posts.show\')');
        Output::line();
    }

    /**
     * Execute view file creation
     *
     * Adds a new view file to an existing view directory. Validates that the
     * directory exists, checks for file conflicts, generates HTML5 template
     * content, and writes the file to disk. Reports success with View::render()
     * usage instructions.
     *
     * @param array $arguments Command arguments (directory at index 0, file at index 1)
     * @return void Exits with status 1 on failure
     */
    public function execute($arguments)
    {
        // Validate and extract directory name from arguments
        $directory = $this->validateName($arguments[0] ?? null);

        // Check if view directory exists (must be created via view:create first)
        if (!$this->viewDirExists($directory))
        {
            $this->error("View directory not found: application/views/{$directory}");
            $this->info("Create it first with: php roline view:create {$directory}");
            exit(1);
        }

        // Validate file name argument is provided and not empty
        if (!isset($arguments[1]) || empty($arguments[1]))
        {
            $this->error('File name is required');
            $this->line('Usage: php roline view:add <directory> <file>');
            exit(1);
        }

        // Normalize file name to lowercase for consistency
        $fileName = strtolower($arguments[1]);

        // Build full paths to directory and target file
        $viewDir = $this->getViewDir($directory);
        $filePath = $this->getViewPath($viewDir, $fileName);

        // Check if file already exists to prevent overwriting
        if (File::exists($filePath)->exists)
        {
            $this->error("View file already exists: {$filePath}");
            exit(1);
        }

        // Generate HTML5 template content with directory and file names
        $content = $this->generateViewTemplate($directory, $fileName);

        // Write template content to disk
        $result = File::write($filePath, $content);

        if ($result->success)
        {
            // Display success message with file path and View::render() usage
            $this->success("View file created: {$filePath}");
            $this->info("Use: View::render('{$directory}.{$fileName}')");
        }
        else
        {
            $this->error("Failed to create view file: {$result->errorMessage}");
            exit(1);
        }
    }

    /**
     * Generate view template content
     *
     * Creates an HTML5 boilerplate template combining directory and file names
     * in the title and heading. For example, "posts" + "show" generates a page
     * titled "Posts - Show" with heading "Posts Show".
     *
     * @param string $directory Directory name (e.g., "posts", "users")
     * @param string $fileName File name (e.g., "show", "create", "edit")
     * @return string Complete HTML5 template content
     */
    private function generateViewTemplate($directory, $fileName)
    {
        // Capitalize directory and file names for display in title and heading
        $directoryCapitalized = ucfirst($directory);
        $fileCapitalized = ucfirst($fileName);

        // Generate HTML5 template with combined directory/file name
        return <<<TEMPLATE
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$directoryCapitalized} - {$fileCapitalized}</title>
</head>
<body>
    <h1>{$directoryCapitalized} {$fileCapitalized}</h1>

    <!-- Add your content here -->
</body>
</html>
TEMPLATE;
    }
}
