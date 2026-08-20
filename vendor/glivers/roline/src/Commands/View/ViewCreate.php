<?php namespace Roline\Commands\View;

/**
 * ViewCreate Command
 *
 * Creates a complete view structure with layout, index, view, and create templates,
 * along with a CSS stylesheet. Uses professional stub templates that demonstrate
 * Rachie's template engine capabilities and best practices.
 *
 * What Gets Created:
 *   - View directory: application/views/{name}/
 *   - Layout template: application/views/{name}/layout.php
 *   - Index template: application/views/{name}/index.php
 *   - View template: application/views/{name}/view.php
 *   - Create template: application/views/{name}/create.php
 *   - Stylesheet: public/css/{name}.css
 *
 * Template Features:
 *   - Layout inheritance with @extends/@section/@yield
 *   - View helpers (Url::, Session::, Input::, Date::, CSRF::)
 *   - Flash message support
 *   - Form with CSRF protection
 *   - Empty state handling with @loopelse
 *   - Responsive CSS styling
 *
 * Safety Features:
 *   - Validates view name (required, valid characters)
 *   - Checks for existing directories to prevent overwriting
 *   - Ensures parent directories exist
 *   - Reports success with usage instructions
 *
 * Typical Workflow:
 *   1. Create complete view structure with stubs
 *   2. Developer customizes templates for their data model
 *   3. Render via View::render('viewname/index') in controllers
 *
 * Usage:
 *   php roline view:create products
 *   php roline view:create users
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

class ViewCreate extends ViewCommand
{
    /**
     * Get command description for listing
     *
     * @return string Brief command description
     */
    public function description()
    {
        return 'Create a complete view structure with templates';
    }

    /**
     * Get command usage syntax
     *
     * @return string Usage syntax showing required view name argument
     */
    public function usage()
    {
        return '<view|required>';
    }

    /**
     * Display detailed help information
     *
     * Shows required arguments, examples of view creation, what files/directories
     * get created, and details about the generated template structure.
     *
     * @return void
     */
    public function help()
    {
        parent::help();

        Output::info('Arguments:');
        Output::line('  <view|required>  Name of the view resource (e.g., products, users)');
        Output::line();

        Output::info('Examples:');
        Output::line('  php roline view:create products');
        Output::line('  php roline view:create users');
        Output::line();

        Output::info('Creates:');
        Output::line('  application/views/products/layout.php');
        Output::line('  application/views/products/index.php');
        Output::line('  application/views/products/show.php');
        Output::line('  application/views/products/create.php');
        Output::line('  application/views/products/edit.php');
        Output::line('  public/css/products.css');
        Output::line();

        Output::info('Generated Files:');
        Output::line('  - Layout with @extends/@section/@yield');
        Output::line('  - Templates with Rachie syntax and view helpers');
        Output::line('  - Clean, responsive CSS styling');
        Output::line('  - CSRF protection and flash messages');
        Output::line();
    }

    /**
     * Execute view structure creation
     *
     * Creates a complete view structure with layout, index, view, and create
     * templates, plus a CSS stylesheet. Uses professional stubs that demonstrate
     * Rachie's template engine and best practices.
     *
     * @param array $arguments Command arguments (view name required at index 0)
     * @return void Exits with status 1 on failure
     */
    public function execute($arguments)
    {
        // Validate and extract view name from arguments
        $name = $this->validateName($arguments[0] ?? null);

        // Check if view directory already exists to prevent overwriting
        if ($this->viewDirExists($name))
        {
            $path = $this->getViewDir($name);
            $this->error("View directory already exists: {$path}");
            exit(1);
        }

        // Build full view directory path
        $viewDir = $this->getViewDir($name);

        // Ensure parent views directory exists (creates application/views/ if needed)
        $this->ensureViewsDir();

        // Create the view directory structure
        $result = File::ensureDir($viewDir);

        if (!$result->success)
        {
            $this->error("Failed to create view directory: {$result->errorMessage}");
            exit(1);
        }

        // Define view files to create from stubs
        $viewFiles = [
            'layout' => 'layout.stub',
            'index' => 'index.stub',
            'show' => 'show.stub',
            'new' => 'new.stub',
            'edit' => 'edit.stub'
        ];

        $createdPaths = [];

        // Create each view file from its stub
        foreach ($viewFiles as $fileName => $stubFile) {
            $content = $this->processStub($stubFile, ucfirst($name));

            if ($content === false) {
                $this->error("Failed to read stub: {$stubFile}");
                exit(1);
            }

            $filePath = $this->getViewPath($viewDir, $fileName);
            $writeResult = File::write($filePath, $content);

            if (!$writeResult->success) {
                $this->error("Failed to create {$fileName}.php: {$writeResult->errorMessage}");
                exit(1);
            }

            $createdPaths[] = $filePath;
        }

        // Create CSS file in public/css/
        $cssResult = $this->createCssFile($name);
        if (!$cssResult['success']) {
            $this->error("Failed to create CSS file: {$cssResult['error']}");
            exit(1);
        }

        $createdPaths[] = $cssResult['path'];

        // Display success messages
        $this->line();
        $this->success("View structure created successfully!");
        $this->line();

        foreach ($createdPaths as $path) {
            $this->info("  ✓ {$path}");
        }

        $this->line();
        $this->info("Usage in controller:");
        $this->line("  View::render('{$name}/index');");
        $this->line("  View::render('{$name}/show', ['id' => \$id]);");
        $this->line();
    }

    /**
     * Process stub template with placeholder replacement
     *
     * Reads a stub file and replaces placeholders with actual resource names.
     * Supports filters: |lowercase, |singular, |uppercase
     *
     * @param string $stubFile Stub filename (e.g., 'layout.stub')
     * @param string $resourceName Resource name (e.g., 'Products')
     * @return string|false Processed content or false on failure
     */
    private function processStub($stubFile, $resourceName)
    {
        $stubPath = __DIR__ . '/../../../stubs/views/' . $stubFile;

        if (!file_exists($stubPath)) {
            return false;
        }

        $content = file_get_contents($stubPath);

        if ($content === false) {
            return false;
        }

        // Process all placeholder patterns with filters
        $content = $this->replacePlaceholders($content, $resourceName);

        return $content;
    }

    /**
     * Replace placeholders in content with resource-specific values
     *
     * Handles patterns like:
     * - {{ResourceName}} → "Products"
     * - {{ResourceName|lowercase}} → "products"
     * - {{ResourceName|singular}} → "Product"
     * - {{ResourceName|lowercase|singular}} → "product"
     *
     * @param string $content Content with placeholders
     * @param string $resourceName Resource name
     * @return string Content with placeholders replaced
     */
    private function replacePlaceholders($content, $resourceName)
    {
        // Match {{ResourceName}} with optional filters
        $pattern = '/\{\{ResourceName(\|[a-z]+)*\}\}/';

        return preg_replace_callback($pattern, function($matches) use ($resourceName) {
            $value = $resourceName;
            $filters = isset($matches[1]) ? explode('|', trim($matches[1], '|')) : [];

            foreach ($filters as $filter) {
                $value = $this->applyFilter($value, $filter);
            }

            return $value;
        }, $content);
    }

    /**
     * Apply filter to a value
     *
     * Supported filters:
     * - lowercase: Convert to lowercase
     * - singular: Singularize (basic: removes trailing 's')
     * - uppercase: Convert to uppercase
     *
     * @param string $value Value to filter
     * @param string $filter Filter name
     * @return string Filtered value
     */
    private function applyFilter($value, $filter)
    {
        switch ($filter) {
            case 'lowercase':
                return strtolower($value);

            case 'singular':
                // Simple singularization: remove trailing 's'
                return rtrim($value, 's');

            case 'uppercase':
                return strtoupper($value);

            case 'capitalize':
                return ucfirst(strtolower($value));

            default:
                return $value;
        }
    }

    /**
     * Create CSS file in public/css/ directory
     *
     * @param string $resourceName Resource name
     * @return array Result with 'success', 'path', and optional 'error'
     */
    private function createCssFile($resourceName)
    {
        $cssDir = 'public/css';
        $cssFile = strtolower($resourceName) . '.css';
        $cssPath = $cssDir . '/' . $cssFile;

        // Ensure public/css/ directory exists
        File::ensureDir($cssDir);

        // Read CSS stub
        $stubPath = __DIR__ . '/../../../stubs/views/styles.stub';

        if (!file_exists($stubPath)) {
            return ['success' => false, 'error' => 'CSS stub not found'];
        }

        $content = file_get_contents($stubPath);

        if ($content === false) {
            return ['success' => false, 'error' => 'Failed to read CSS stub'];
        }

        // Replace placeholders
        $content = $this->replacePlaceholders($content, $resourceName);

        // Write CSS file
        $result = File::write($cssPath, $content);

        if (!$result->success) {
            return ['success' => false, 'error' => $result->errorMessage];
        }

        return ['success' => true, 'path' => $cssPath];
    }
}
