<?php namespace Roline\Commands\Model;

/**
 * ModelDelete Command
 *
 * Safely deletes an existing model class file with user confirmation.
 * This is a destructive operation that permanently removes the model file
 * from the filesystem - use with caution.
 *
 * Safety Features:
 *   - Validates model exists before attempting deletion
 *   - Requires explicit user confirmation
 *   - Provides clear feedback on success/failure
 *   - Shows full file path before deletion
 *
 * Note: This only deletes the model file - it does NOT drop the database
 * table or modify any data. Use table:delete for database operations.
 *
 * Usage:
 *   php roline model:delete Post
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2050 Geoffrey Okongo
 * @category Roline
 * @package Roline\Commands\Model
 * @link https://github.com/glivers/roline
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.0
 */

use Rackage\File;

class ModelDelete extends ModelCommand
{
    public function description()
    {
        return 'Delete an existing model';
    }

    public function usage()
    {
        return '<Model|required>';
    }

    public function execute($arguments)
    {
        $name = $this->validateName($arguments[0] ?? null);

        // Verify model exists before attempting deletion
        if (!$this->modelExists($name))
        {
            $this->error("Model not found: {$name}Model");
            exit(1);
        }

        $path = $this->getModelPath($name);

        // Display warning and request confirmation
        $this->line();
        $this->info("You are about to delete: {$path}");
        $confirmed = $this->confirm("Are you sure you want to delete this model?");

        if (!$confirmed)
        {
            $this->info("Deletion cancelled.");
            exit(0);
        }

        // Perform file deletion
        $result = File::delete($path);

        if ($result->success)
        {
            $this->success("Model deleted: {$path}");
        }
        else
        {
            $this->error("Failed to delete model: {$result->errorMessage}");
            exit(1);
        }
    }
}
