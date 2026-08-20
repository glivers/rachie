<?php namespace Roline\Commands\Controller;

/**
 * ControllerComplete Command
 *
 * Convenience command designed to create complete MVC resource scaffolding in a single
 * operation by generating controller, model, and views together.
 *
 * Functionality:
 *   This command creates:
 *   - Controller file in application/controllers/
 *   - Model file in application/models/
 *   - View directory in application/views/ with standard templates:
 *     * layout.php (main layout)
 *     * index.php (list view)
 *     * show.php (single item view)
 *     * create.php (create form)
 *     * edit.php (edit form)
 *   - CSS file in public/css/
 *
 * Usage Example:
 *   php roline controller:complete Posts
 *
 *   Creates:
 *   - application/controllers/PostsController.php
 *   - application/models/PostsModel.php
 *   - application/views/posts/layout.php
 *   - application/views/posts/index.php
 *   - application/views/posts/show.php
 *   - application/views/posts/create.php
 *   - application/views/posts/edit.php
 *   - public/css/posts.css
 *
 * Advantages Over Individual Commands:
 *   Instead of running:
 *   - php roline controller:create Posts
 *   - php roline model:create Posts
 *   - php roline view:create posts
 *   - php roline view:add posts show
 *   - php roline view:add posts create
 *   - php roline view:add posts edit
 *
 *   Developer runs single command:
 *   - php roline controller:complete Posts
 *
 * Implementation:
 *   - Generates complete CRUD controller with getIndex, getNew, postNew,
 *     getShow, getEdit, postEdit, and getDelete methods
 *   - Creates model with example properties (title, description, status, priority)
 *   - Generates professional view templates with Rachie template syntax
 *   - Includes responsive CSS styling
 *   - Full CSRF protection and flash message support
 *
 * Use Cases:
 *   - Quick prototyping of new resources
 *   - Rapid application scaffolding
 *   - Teaching MVC patterns to new developers
 *   - Creating consistent file structure across resources
 *   - Speeding up development workflow
 *
 * Usage:
 *   php roline controller:complete Posts
 *   php roline controller:complete Users
 *   php roline controller:complete Products
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2050 Geoffrey Okongo
 * @category Roline
 * @package Roline\Commands\Controller
 * @link https://github.com/glivers/roline
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.0
 */

use Rackage\File;
use Rackage\Registry;

class ControllerComplete extends ControllerCommand
{
    /**
     * Get command description for listing
     *
     * @return string Brief command description
     */
    public function description()
    {
        return 'Create controller, model, and views together';
    }

    /**
     * Get command usage syntax
     *
     * @return string Usage syntax showing required resource name
     */
    public function usage()
    {
        return '<name|required>';
    }

    /**
     * Execute complete resource scaffolding
     *
     * Creates controller, model, and views in a single operation for rapid
     * MVC resource generation. Includes index, show, create, and edit views.
     *
     * @param array $arguments Command arguments (resource name at index 0)
     * @return void Exits with status 1 on failure
     */
    public function execute($arguments)
    {
        // Validate resource name
        if (empty($arguments[0])) {
            $this->error('Resource name is required!');
            $this->line();
            $this->info('Usage: php roline controller:complete <ResourceName>');
            $this->line();
            $this->info('Example: php roline controller:complete Posts');
            exit(1);
        }

        $name = $this->validateName($arguments[0]);

        $this->line();
        $this->info("Creating complete MVC scaffold for: {$name}");
        $this->line();

        // Track created files for error cleanup
        $createdFiles = [];

        try {
            // 1. Create Controller
            $this->info("1. Creating controller...");
            $controllerResult = $this->createController($name);
            if ($controllerResult['success']) {
                $createdFiles[] = $controllerResult['path'];
                $this->success("   ✓ {$controllerResult['path']}");
            } else {
                throw new \Exception($controllerResult['error']);
            }

            // 2. Create Model
            $this->info("2. Creating model...");
            $modelResult = $this->createModel($name);
            if ($modelResult['success']) {
                $createdFiles[] = $modelResult['path'];
                $this->success("   ✓ {$modelResult['path']}");
            } else {
                throw new \Exception($modelResult['error']);
            }

            // 3. Create Views
            $this->info("3. Creating views...");
            $viewsResult = $this->createViews($name);
            if ($viewsResult['success']) {
                $createdFiles = array_merge($createdFiles, $viewsResult['paths']);
                foreach ($viewsResult['paths'] as $path) {
                    $this->success("   ✓ {$path}");
                }
            } else {
                throw new \Exception($viewsResult['error']);
            }

            // Success summary
            $this->line();
            $this->success("Complete scaffold created successfully!");
            $this->line();
            $this->info("Next steps:");
            $this->info("  1. Add properties to model: php roline model:append {$name}");
            $this->info("  2. Create database table: php roline model:create-table {$name}");
            $this->info("  3. Implement controller methods");
            $this->info("  4. Customize view templates");
            $this->line();

        } catch (\Exception $e) {
            // Rollback on error - delete created files
            $this->line();
            $this->error("Failed to create scaffold: " . $e->getMessage());
            $this->line();

            if (!empty($createdFiles)) {
                $this->info("Rolling back created files...");
                foreach ($createdFiles as $file) {
                    if (file_exists($file)) {
                        unlink($file);
                        $this->line("  Deleted: {$file}");
                    }
                }
            }

            exit(1);
        }
    }

    /**
     * Create controller file
     *
     * @param string $name Controller name (without Controller suffix)
     * @return array Result with 'success', 'path', and optional 'error'
     */
    private function createController($name)
    {
        $path = $this->getControllerPath($name);

        if ($this->controllerExists($name)) {
            return ['success' => false, 'error' => "Controller already exists: {$path}"];
        }

        // Use existing stub
        $customStubPath = getcwd() . '/application/database/stubs/controller.stub';
        $defaultStubPath = __DIR__ . '/../../../stubs/controller.stub';
        $stubPath = file_exists($customStubPath) ? $customStubPath : $defaultStubPath;

        $stub = File::read($stubPath);
        if (!$stub->success) {
            return ['success' => false, 'error' => 'Controller stub file not found'];
        }

        $content = str_replace('{{ControllerName}}', $name, $stub->content);
        $content = str_replace('{{ControllerName|lowercase}}', strtolower($name), $content);

        // Replace metadata placeholders from settings
        $settings = Registry::settings();
        $content = str_replace('{{author}}', $settings['author'] ?? 'Your Name', $content);
        $content = str_replace('{{copyright}}', $settings['copyright'] ?? 'Copyright (c) ' . date('Y'), $content);
        $content = str_replace('{{license}}', $settings['license'] ?? 'MIT License', $content);
        $content = str_replace('{{version}}', $settings['version'] ?? '1.0.0', $content);

        $this->ensureControllersDir();
        $result = File::write($path, $content);

        if (!$result->success) {
            return ['success' => false, 'error' => $result->errorMessage];
        }

        return ['success' => true, 'path' => $path];
    }

    /**
     * Create model file
     *
     * @param string $name Model name (without Model suffix)
     * @return array Result with 'success', 'path', and optional 'error'
     */
    private function createModel($name)
    {
        $modelPath = "application/models/{$name}Model.php";

        if (File::exists($modelPath)->exists) {
            return ['success' => false, 'error' => "Model already exists: {$modelPath}"];
        }

        // Use existing stub
        $customStubPath = getcwd() . '/application/database/stubs/model.stub';
        $defaultStubPath = __DIR__ . '/../../../stubs/model.stub';
        $stubPath = file_exists($customStubPath) ? $customStubPath : $defaultStubPath;

        $stub = File::read($stubPath);
        if (!$stub->success) {
            return ['success' => false, 'error' => 'Model stub file not found'];
        }

        // Use improved pluralize from ModelCommand
        $tableName = strtolower($name) . 's'; // Simple pluralization for now

        $content = str_replace('{{ModelName}}', $name, $stub->content);
        $content = str_replace('{{TableName}}', $tableName, $content);

        // Replace metadata placeholders from settings
        $settings = Registry::settings();
        $content = str_replace('{{author}}', $settings['author'] ?? 'Your Name', $content);
        $content = str_replace('{{copyright}}', $settings['copyright'] ?? 'Copyright (c) ' . date('Y'), $content);
        $content = str_replace('{{license}}', $settings['license'] ?? 'MIT License', $content);
        $content = str_replace('{{version}}', $settings['version'] ?? '1.0.0', $content);

        File::ensureDir('application/models');
        $result = File::write($modelPath, $content);

        if (!$result->success) {
            return ['success' => false, 'error' => $result->errorMessage];
        }

        return ['success' => true, 'path' => $modelPath];
    }

    /**
     * Create view files (layout, index, show, create, edit) and CSS
     *
     * Uses professional stub templates that demonstrate Rachie's
     * template engine capabilities and best practices.
     *
     * @param string $name Resource name for views
     * @return array Result with 'success', 'paths' array, and optional 'error'
     */
    private function createViews($name)
    {
        $viewsDir = 'application/views/' . strtolower($name);
        $paths = [];

        // Ensure view directory exists
        File::ensureDir($viewsDir);

        // Define view files to create from stubs
        $viewFiles = [
            'layout' => 'layout.stub',
            'index' => 'index.stub',
            'show' => 'show.stub',
            'new' => 'new.stub',
            'edit' => 'edit.stub'
        ];

        // Create each view file from its stub
        foreach ($viewFiles as $fileName => $stubFile) {
            $content = $this->processStub($stubFile, $name);

            if ($content === false) {
                return ['success' => false, 'error' => "Failed to read stub: {$stubFile}"];
            }

            $viewPath = "{$viewsDir}/{$fileName}.php";

            if (file_exists($viewPath)) {
                return ['success' => false, 'error' => "View already exists: {$viewPath}"];
            }

            $result = File::write($viewPath, $content);
            if (!$result->success) {
                return ['success' => false, 'error' => $result->errorMessage];
            }

            $paths[] = $viewPath;
        }

        // Create CSS file
        $cssResult = $this->createCssFile($name);
        if (!$cssResult['success']) {
            return ['success' => false, 'error' => $cssResult['error']];
        }

        $paths[] = $cssResult['path'];

        return ['success' => true, 'paths' => $paths];
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
     * - capitalize: Capitalize first letter
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
