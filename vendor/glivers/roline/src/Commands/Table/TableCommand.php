<?php namespace Roline\Commands\Table;

/**
 * TableCommand - Base Class for Database Table Operations
 *
 * Abstract base class providing shared functionality for all table-related CLI
 * commands in Roline. Handles model class resolution, validation, table name
 * extraction, and docblock annotation parsing for schema definitions.
 *
 * Architecture:
 *   - Template Method Pattern - Subclasses implement execute() for specific operations
 *   - Reflection-Based - Uses PHP Reflection API to introspect model classes
 *   - Annotation-Driven - Reads @column docblock annotations for schema definitions
 *   - Static Model Pattern - Works with Rachie's static Model architecture
 *
 * Model Requirements:
 *   - Must exist in Models\ namespace with "Model" suffix (e.g., Models\PostsModel)
 *   - Must define protected static $table property with database table name
 *   - Schema defined via @column annotations on class properties
 *
 * Common Workflows:
 *   1. table:create  - Create table from model docblock schema
 *   2. table:update  - Update table structure (add/modify columns)
 *   3. table:delete  - Drop table from database
 *   4. table:rename  - Rename existing table
 *   5. table:schema  - Display table schema information
 *   6. table:export  - Export table structure as SQL
 *
 * Helper Methods Provided:
 *   - getModelClass()   - Resolve model class name from user input
 *   - validateModel()   - Verify model class exists, show creation hint if not
 *   - getTableName()    - Extract table name from model's $table property
 *   - parseDocblocks()  - Parse @column annotations into structured array
 *
 * Example Model with Schema Annotations:
 * ```php
 * class PostsModel extends Model {
 *     protected static $table = 'posts';
 *
 *     /** @column INT PRIMARY KEY AUTO_INCREMENT *\/
 *     public $id;
 *
 *     /** @column VARCHAR(255) NOT NULL *\/
 *     public $title;
 * }
 * ```
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2050 Geoffrey Okongo
 * @category Roline
 * @package Roline\Commands\Table
 * @link https://github.com/glivers/roline
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.0
 */

use Roline\Command;

abstract class TableCommand extends Command
{
    /**
     * Get model class name from model name
     *
     * Resolves a user-provided model name into a fully-qualified class name
     * in the Models\ namespace. Automatically strips "Model" suffix if provided
     * and re-appends it to ensure consistent naming (e.g., "Posts" or "PostsModel"
     * both resolve to "Models\PostsModel").
     *
     * @param string|null $name Model name (e.g., "Posts", "PostsModel", "Users")
     * @return string Fully-qualified class name (e.g., "Models\PostsModel")
     */
    protected function getModelClass($name)
    {
        // Validate model name is provided
        if (!$name)
        {
            $this->error('Model name is required');
            exit(1);
        }

        // Remove 'Model' suffix if user provided it (will be re-added)
        $name = str_replace('Model', '', $name);

        // Build fully-qualified class name with Models namespace and Model suffix
        return "Models\\{$name}Model";
    }

    /**
     * Validate that model class exists
     *
     * Checks if the specified model class is loaded and available. If not found,
     * displays an error with a helpful command to create the model. This ensures
     * table operations only run against valid, existing model classes.
     *
     * @param string $className Fully-qualified model class name
     * @return void Exits with status 1 if model doesn't exist
     */
    protected function validateModel($className)
    {
        // Check if model class is loaded via autoloader
        if (!class_exists($className))
        {
            // Extract base name for helpful error message
            $this->error("Model not found: {$className}");
            $this->info("Run: php roline model:create " . str_replace(['Models\\', 'Model'], '', $className));
            exit(1);
        }
    }

    /**
     * Get table name from model class
     *
     * Extracts the database table name from the model's protected static $table
     * property using reflection. All Rachie models must define this property to
     * specify which database table they represent.
     *
     * @param string $className Fully-qualified model class name
     * @return string Database table name
     */
    protected function getTableName($className)
    {
        // Use reflection to access protected static properties
        $reflection = new \ReflectionClass($className);
        $properties = $reflection->getStaticProperties();

        // Validate model defines required $table property
        if (!isset($properties['table']))
        {
            $this->error("Model {$className} does not define \$table property");
            exit(1);
        }

        // Return table name for database operations
        return $properties['table'];
    }

    /**
     * Parse docblock annotations from model class
     *
     * Extracts all @column annotations from model properties using reflection.
     * Each property with a @column docblock represents a database column, with
     * the annotation defining the column's SQL type and constraints (e.g.,
     * "@column VARCHAR(255) NOT NULL").
     *
     * @param string $className Fully-qualified model class name
     * @return array Array of column definitions with 'name' and 'docblock' keys
     */
    protected function parseDocblocks($className)
    {
        // Get reflection instance for model class
        $reflection = new \ReflectionClass($className);
        $properties = $reflection->getProperties();
        $columns = [];

        // Loop through all class properties looking for @column annotations
        foreach ($properties as $property)
        {
            // Get property's docblock comment
            $docComment = $property->getDocComment();

            // Check if docblock contains @column annotation
            if ($docComment && strpos($docComment, '@column') !== false)
            {
                // Add column definition to array
                $columns[] = [
                    'name' => $property->getName(),
                    'docblock' => $docComment,
                ];
            }
        }

        // Return all discovered column definitions
        return $columns;
    }
}
