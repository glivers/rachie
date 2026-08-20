<?php namespace Roline\Commands\Model;

/**
 * ModelCommand - Base class for all model commands
 *
 * Provides shared functionality for model-related CLI commands including
 * name validation, path resolution, existence checking, directory setup,
 * and table name generation. All model commands (create, delete) extend
 * this class to avoid code duplication.
 *
 * Shared Functionality:
 *   - Name normalization (removes 'Model' suffix if provided)
 *   - Path resolution to application/models/
 *   - Existence validation
 *   - Directory creation
 *   - Table name pluralization
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2050 Geoffrey Okongo
 * @category Roline
 * @package Roline\Commands\Model
 * @link https://github.com/glivers/roline
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.0
 */

use Roline\Command;
use Rackage\File;

abstract class ModelCommand extends Command
{
    /**
     * Validate and normalize model name
     *
     * Ensures a model name is provided, capitalizes first letter, and removes
     * the 'Model' suffix if the user included it. This allows 'user', 'User',
     * 'UserModel' as valid input, all normalized to 'User'.
     *
     * @param string|null $name Model name from user input
     * @return string Normalized name without 'Model' suffix, first letter capitalized
     */
    protected function validateName($name)
    {
        if (!$name)
        {
            $this->error('Model name is required');
            exit(1);
        }

        // Capitalize first letter (allows lowercase input like 'user')
        $name = ucfirst($name);

        // Remove 'Model' suffix if user provided it
        // Example: 'UserModel' becomes 'User'
        return str_replace('Model', '', $name);
    }

    /**
     * Build full path to model file
     *
     * @param string $name Normalized model name (without 'Model' suffix)
     * @return string Full relative path (e.g., 'application/models/UserModel.php')
     */
    protected function getModelPath($name)
    {
        return "application/models/{$name}Model.php";
    }

    /**
     * Check if model file already exists
     *
     * @param string $name Normalized model name
     * @return bool True if model file exists, false otherwise
     */
    protected function modelExists($name)
    {
        $path = $this->getModelPath($name);
        return File::exists($path)->exists;
    }

    /**
     * Ensure the models directory exists
     *
     * Creates application/models/ directory if it doesn't exist.
     * Called before writing new model files.
     *
     * @return void
     */
    protected function ensureModelsDir()
    {
        File::ensureDir('application/models');
    }

    /**
     * Convert CamelCase to snake_case
     *
     * Transforms model names like 'UserProfile' to 'user_profile' for table naming.
     * Handles consecutive uppercase letters (acronyms) intelligently:
     * - 'ExportSQL' → 'export_sql' (not 'export_s_q_l')
     * - 'XMLParser' → 'xml_parser' (not 'x_m_l_parser')
     * - 'UserProfile' → 'user_profile'
     *
     * @param string $name CamelCase string
     * @return string snake_case string
     */
    protected function toSnakeCase($name)
    {
        // Insert underscore before uppercase letters that follow lowercase letters
        // This keeps acronyms together: ExportSQL → Export_SQL, XMLParser → XML_Parser
        $snake = preg_replace('/(?<=[a-z])([A-Z])/', '_$1', $name);

        // Also insert underscore before uppercase letter followed by lowercase (end of acronym)
        // XMLParser → XML_Parser, APIController → API_Controller
        $snake = preg_replace('/(?<=[A-Z])([A-Z])(?=[a-z])/', '_$1', $snake);

        return strtolower($snake);
    }

    /**
     * Generate plural table name from model name
     *
     * Converts model name to snake_case and applies English pluralization rules:
     * - Irregular plurals (person → people, child → children, etc.)
     * - Words ending in 'y' preceded by consonant (category → categories)
     * - Words ending in 's', 'x', 'z', 'ch', 'sh' (address → addresses)
     * - Words ending in 'f' or 'fe' (leaf → leaves, knife → knives)
     * - Default: add 's' (user → users, post → posts)
     *
     * @param string $name Model name (e.g., 'User', 'Category', 'UserProfile')
     * @return string Plural snake_case table name (e.g., 'users', 'categories', 'user_profiles')
     */
    protected function pluralize($name)
    {
        // Convert to snake_case first
        $snake = $this->toSnakeCase($name);

        // Handle compound words (split on underscores, pluralize last word)
        $parts = explode('_', $snake);
        $lastWord = array_pop($parts);

        // Irregular plurals map
        $irregulars = [
            'person' => 'people',
            'man' => 'men',
            'woman' => 'women',
            'child' => 'children',
            'tooth' => 'teeth',
            'foot' => 'feet',
            'mouse' => 'mice',
            'goose' => 'geese',
        ];

        // Check for irregular plurals
        if (isset($irregulars[$lastWord])) {
            $parts[] = $irregulars[$lastWord];
            return implode('_', $parts);
        }

        // Words ending in 'y' preceded by consonant: category → categories
        if (preg_match('/[^aeiou]y$/', $lastWord)) {
            $parts[] = substr($lastWord, 0, -1) . 'ies';
            return implode('_', $parts);
        }

        // Words ending in 's', 'x', 'z', 'ch', 'sh': address → addresses
        if (preg_match('/(s|x|z|ch|sh)$/', $lastWord)) {
            $parts[] = $lastWord . 'es';
            return implode('_', $parts);
        }

        // Words ending in 'f' or 'fe': leaf → leaves, knife → knives
        if (preg_match('/f$/', $lastWord)) {
            $parts[] = substr($lastWord, 0, -1) . 'ves';
            return implode('_', $parts);
        }
        if (preg_match('/fe$/', $lastWord)) {
            $parts[] = substr($lastWord, 0, -2) . 'ves';
            return implode('_', $parts);
        }

        // Default: add 's'
        $parts[] = $lastWord . 's';
        return implode('_', $parts);
    }
}
