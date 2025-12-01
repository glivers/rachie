<?php namespace Models;

use Rackage\Model;

/**
 * ExistingModel Model
 *
 * Represents a ExistingModel entity in the database.
 *
 * Define database columns as protected properties with @column annotations.
 * Run `php roline table:create ExistingModel` to create the table.
 * Run `php roline table:update ExistingModel` after changing schema.
 */
class ExistingModelModel extends Model
{
    /**
     * Database table name
     *
     * @var string
     */
    protected static $table = 'existingmodels';

    /**
     * Automatically manage timestamps
     *
     * Set to true to auto-update date_created and date_modified columns.
     *
     * @var bool
     */
    protected static $timestamps = true;

    // ==================== DATABASE SCHEMA ====================
    //
    // Define your table columns below as protected properties with @column annotation.
    //
    // Supported Types:
    //   Numeric: @int, @bigint, @decimal, @float, @double, @tinyint, @smallint, @mediumint
    //   String: @varchar, @char, @text, @mediumtext, @longtext
    //   Date/Time: @datetime, @date, @time, @timestamp, @year
    //   Special: @enum, @set, @boolean, @bool, @json, @autonumber, @uuid
    //   Binary: @blob, @mediumblob, @longblob
    //   Spatial: @point, @geometry, @linestring, @polygon
    //
    // Modifiers:
    //   @primary      - Primary key
    //   @unique       - Unique constraint
    //   @nullable     - Allow NULL values
    //   @unsigned     - Unsigned numbers only
    //   @default val  - Default value
    //   @index        - Add index
    //   @drop         - Mark for deletion (use with table:update)
    //   @rename old   - Rename from old column name (use with table:update)
    //
    // Examples:
    //   @varchar 255                              - VARCHAR(255) NOT NULL
    //   @int 11 @unsigned                         - INT(11) UNSIGNED NOT NULL
    //   @text @nullable                           - TEXT NULL
    //   @enum active,inactive,banned @default active
    //   @set read,write,delete,admin              - Multiple choice values
    //   @decimal 10,2                             - DECIMAL(10,2) for currency
    //   @json                                     - JSON column (MySQL 5.7+)
    //   @point                                    - Lat/long coordinates
    //
    // Run: php roline table:create ExistingModel

    /**
     * @column
     * @primary
     * @autonumber
     */
    protected $id;

    /**
     * @column
     * @datetime
     */
    protected $date_created;

    /**
     * @column
     * @datetime
     */
    protected $date_modified;

    // ==================== MODEL METHODS ====================

    // Add your business logic methods here
}
