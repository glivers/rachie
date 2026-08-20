<?php namespace Roline\Commands\Controller;

/**
 * ControllerCreate Command
 *
 * Generates a new controller class from a stub template with RESTful CRUD methods.
 * The generated controller includes HTTP method-prefixed actions (get, post, put,
 * delete, patch) and view rendering examples, ready for immediate use.
 *
 * Features:
 *   - Auto-adds 'Controller' suffix if not provided
 *   - Checks for existing controllers to prevent overwriting
 *   - Supports custom stub templates
 *   - Creates controllers directory if needed
 *
 * Generated Methods:
 *   - getIndex()    - List resources
 *   - getNew()      - Show create form
 *   - postNew()     - Store new resource
 *   - getShow($id)  - Show single resource
 *   - getEdit($id)  - Show edit form
 *   - postEdit($id) - Update resource
 *   - getDelete($id) - Delete resource
 *
 * Usage:
 *   php roline controller:create Posts
 *   php roline controller:create PostsController
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
use Roline\Output;

class ControllerCreate extends ControllerCommand
{
    public function description()
    {
        return 'Create a new controller class';
    }

    public function usage()
    {
        return '<Controller|required>';
    }

    public function help()
    {
        parent::help();

        Output::info('Arguments:');
        Output::line('  <Controller|required>  Name of the controller class');
        Output::line('                         The "Controller" suffix is added automatically');
        Output::line('                         Example: Posts becomes PostsController');
        Output::line();

        Output::info('Examples:');
        Output::line('  php roline controller:create Posts');
        Output::line('  php roline controller:create Users');
        Output::line('  php roline controller:create BlogPosts');
        Output::line();

        Output::info('Creates:');
        Output::line('  application/controllers/PostsController.php');
        Output::line();

        Output::info('Generated File:');
        Output::line('  - RESTful controller with CRUD methods (getIndex, getNew, postNew, etc.)');
        Output::line('  - HTTP method prefixes (get, post, put, delete, patch)');
        Output::line('  - View rendering examples');
        Output::line();
    }

    public function execute($arguments)
    {
        $name = $this->validateName($arguments[0] ?? null);

        // Prevent overwriting existing controllers
        if ($this->controllerExists($name))
        {
            $path = $this->getControllerPath($name);
            $this->error("Controller already exists: {$path}");
            exit(1);
        }

        // Load stub template - custom stubs take priority over defaults
        // This allows developers to customize generated code structure
        $customStubPath = getcwd() . '/application/database/stubs/controller.stub';
        $defaultStubPath = __DIR__ . '/../../../stubs/controller.stub';

        $stubPath = file_exists($customStubPath) ? $customStubPath : $defaultStubPath;

        $stub = File::read($stubPath);

        if (!$stub->success)
        {
            $this->error('Controller stub file not found');
            exit(1);
        }

        // Replace template placeholders with actual controller name
        $content = str_replace('{{ControllerName}}', $name, $stub->content);
        $content = str_replace('{{ControllerName|lowercase}}', strtolower($name), $content);

        // Replace metadata placeholders from settings
        $settings = Registry::settings();
        $content = str_replace('{{author}}', $settings['author'] ?? 'Your Name', $content);
        $content = str_replace('{{copyright}}', $settings['copyright'] ?? 'Copyright (c) ' . date('Y'), $content);
        $content = str_replace('{{license}}', $settings['license'] ?? 'MIT License', $content);
        $content = str_replace('{{version}}', $settings['version'] ?? '1.0.0', $content);

        // Ensure target directory exists before writing
        $this->ensureControllersDir();

        // Write the generated controller file
        $path = $this->getControllerPath($name);
        $result = File::write($path, $content);

        if ($result->success)
        {
            $this->success("Controller created: {$path}");
            $this->line();

            // Interactive prompt: ask if user wants to add custom methods
            $addMethods = $this->confirm("Would you like to add custom methods to this controller now?");

            if ($addMethods)
            {
                $this->addMethodsInteractively($path, $name);
            }
            else
            {
                $this->line();
                $this->info("You can add methods later using: php roline controller:append {$name}");
                $this->line();
            }
        }
        else
        {
            $this->error("Failed to create controller: {$result->errorMessage}");
            exit(1);
        }
    }

    /**
     * Interactively add methods to controller
     *
     * Prompts user for method names and HTTP verbs, then inserts methods
     * into the controller file.
     *
     * @param string $controllerPath Path to controller file
     * @param string $controllerName Controller name (without Controller suffix)
     * @return void
     */
    private function addMethodsInteractively($controllerPath, $controllerName)
    {
        $this->line();
        $this->info("Add custom methods (press Enter with empty name to finish):");
        $this->line();
        $this->info("HTTP verbs: get, post, put, delete, patch (leave empty for no prefix)");
        $this->line();

        $methods = [];

        while (true)
        {
            // Get method name
            $this->line("Method name (or press Enter to finish): ", false);
            $methodName = trim(fgets(STDIN));

            if (empty($methodName))
            {
                break;
            }

            // Get HTTP verb
            $this->line("HTTP verb [get]: ", false);
            $httpVerb = trim(fgets(STDIN));

            if (empty($httpVerb))
            {
                $httpVerb = 'get';
            }

            // Validate HTTP verb
            $validVerbs = ['get', 'post', 'put', 'delete', 'patch', ''];
            if (!in_array(strtolower($httpVerb), $validVerbs))
            {
                $this->error("  Invalid HTTP verb. Use: get, post, put, delete, patch, or leave empty");
                continue;
            }

            $fullMethodName = empty($httpVerb) ? $methodName : $httpVerb . ucfirst($methodName);

            $methods[] = [
                'name' => $methodName,
                'verb' => $httpVerb,
                'fullName' => $fullMethodName
            ];

            $this->success("  Added: {$fullMethodName}()");
        }

        if (!empty($methods))
        {
            // Read current controller content
            $content = file_get_contents($controllerPath);

            // Build methods code
            $methodsCode = "\n";
            foreach ($methods as $method)
            {
                $methodsCode .= "    /**\n";
                $methodsCode .= "     * {$method['fullName']} method\n";
                $methodsCode .= "     *\n";
                $methodsCode .= "     * @return void\n";
                $methodsCode .= "     */\n";
                $methodsCode .= "    public function {$method['fullName']}()\n";
                $methodsCode .= "    {\n";
                $methodsCode .= "        // TODO: Implement {$method['fullName']} logic\n";
                $methodsCode .= "    }\n\n";
            }

            // Insert before closing brace
            $content = preg_replace('/}\s*$/', $methodsCode . "}\n", $content);

            // Write back to file
            file_put_contents($controllerPath, $content);

            $this->line();
            $this->success("Added " . count($methods) . " methods to {$controllerName}Controller");
            $this->line();
            $this->info("Next steps:");
            $this->info("  1. Review the controller file: {$controllerPath}");
            $this->info("  2. Implement the method logic");
            $this->info("  3. Create corresponding views if needed");
            $this->line();
        }
        else
        {
            $this->line();
            $this->info("No methods added.");
            $this->line();
        }
    }
}
