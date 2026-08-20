# Roline CLI Toolkit

Command-line interface for Rachie Framework - Generate code, manage databases, scaffold resources.

---

## Quick Start

Roline is already installed with Rachie. Start using it immediately:

**Create a model with database columns:**

```bash
php roline model:create Product
```

This creates `application/models/ProductModel.php`:

```php
<?php namespace Models;

/**
 * Product Model
 *
 * Represents products in the catalog with stock tracking and timestamps.
 * Manages product inventory, availability, and catalog operations.
 *
 * @author Your Name
 * @copyright Copyright (c) 2026
 * @license MIT License
 * @version 1.0.0
 */

use Rackage\Model;

class ProductModel extends Model
{
    // ==================== MODEL PROPERTIES ====================

    /**
     * Database table name for this model
     *
     * Defines which database table this model maps to. Used by the
     * Query builder for all database operations on this model's records.
     *
     * @var string
     */
    protected static $table = 'products';

    // ==================== TABLE COLUMNS ====================

    /**
     * Unique product identifier
     * @column
     * @primary
     * @autonumber
     */
    protected $id;

    /**
     * Product name or title
     * @column
     * @varchar 255
     */
    protected $name;

    /**
     * Stock quantity available
     * @column
     * @int
     * @default 0
     */
    protected $quantity;

    /**
     * When the product was added to catalog
     * @column
     * @datetime
     */
    protected $created_at;

    /**
     * When the product details were last updated
     * @column
     * @datetime
     */
    protected $updated_at;

    // ==================== MODEL METHODS ====================

    // Add your business logic methods here
}
```

**What just happened?**

Roline generated a complete model file with your database schema defined in annotations. Your model IS your schema - no separate migration files.

**What is Roline?**

Roline is the CLI toolkit bundled with Rachie Framework. It helps you:
- Generate models, controllers, and views with working code
- Define database schemas using `@column` annotations in your models
- Create and update database tables automatically from your model annotations
- Scaffold complete resources in seconds

Your database schema lives in your model properties. Need to add a column? Add a property with `@column`. Need to change a column? Update the annotation. Roline reads your annotations and handles the SQL.

**Add more columns to your model:**

```bash
php roline model:append Product
```

Interactive prompts guide you through adding properties with proper annotations.

**Generate the database table:**

```bash
php roline model:table-create Product
```

Roline reads your `@column` annotations and creates the `products` table with all columns, indexes, and constraints.

**That's it.** Your model is your schema. No separate migration files.

---

## What You'll Learn

**Part 1: Getting Started**
- [Installation & Setup](#installation--setup)
- [Your First Model](#your-first-model)
- [Command Overview](#command-overview)

**Part 2: Model Commands**
- [model:create](#modelcreate)
- [model:delete](#modeldelete)
- [model:append](#modelappend)
- [model:table-create](#modelcreate-table)
- [model:table-update](#modelupdate-table)
- [model:table-drop](#modeldrop-table)
- [model:table-rename](#modelrename-table)
- [model:table-schema](#modeltable-schema)
- [model:table-empty](#modelempty-table)
- [model:table-reset](#modeltable-reset)
- [model:table-export](#modelexport-table)

**Part 3: Controller Commands**
- [controller:create](#controllercreate)
- [controller:append](#controllerappend)
- [controller:delete](#controllerdelete)
- [controller:complete](#controllercomplete)

**Part 4: View Commands**
- [view:create](#viewcreate)
- [view:add](#viewadd)
- [view:delete](#viewdelete)

**Part 5: Table Commands**
- [table:create](#tablecreate)
- [table:copy](#tablecopy)
- [table:delete](#tabledelete)
- [table:rename](#tablerename)
- [table:schema](#tableschema)
- [table:empty](#tableempty)
- [table:reset](#tablereset)
- [table:export](#tableexport)
- [table:partition](#tablepartition)
- [table:unpartition](#tableunpartition)

**Part 6: Migration Commands**
- [migration:make](#migrationmake)
- [migration:run](#migrationrun)
- [migration:rollback](#migrationrollback)
- [migration:status](#migrationstatus)

**Part 7: Database Commands**
- [db:list](#dblist)
- [db:tables](#dbtables)
- [db:create](#dbcreate)
- [db:drop](#dbdrop)
- [db:table-drops](#dbdrop-tables)
- [db:reset](#dbreset)
- [db:schema](#dbschema)
- [db:seed](#dbseed)
- [db:export](#dbexport)
- [db:import](#dbimport)

**Part 8: Cache Commands**
- [cleanup:cache](#cleanupcache)
- [cleanup:views](#cleanupviews)
- [cleanup:logs](#cleanuplogs)
- [cleanup:sessions](#cleanupsessions)
- [cleanup:all](#cleanupall)

**Part 9: Utility Commands**
- [list](#list)
- [help](#help)
- [version](#version)

**Part 10: Reference**
- [Model Annotations](#model-annotations)
- [Advanced Features](#advanced-features)
- [Troubleshooting](#troubleshooting)

---

## Installation & Setup

Roline is bundled with Rachie. When you install Rachie, you get Roline automatically.

**Install Rachie:**

```bash
composer create-project glivers/rachie my-project
cd my-project
```

**Or clone from GitHub:**

```bash
git clone https://github.com/glivers/rachie
cd rachie
composer install
```

**Verify Roline is installed:**

```bash
php roline
```

You'll see the Roline command list. You're ready to go.

**Update Roline (updates all packages):**

```bash
composer update
```

This updates Rachie, Rackage, and Roline together.

### Important: Configure Your Project Settings

Before generating code, update `config/settings.php` with your details. Roline uses these when scaffolding controllers and models:

```php
return [
    'author' => 'Your Name',                    // Appears in @author tags
    'copyright' => 'Copyright (c) 2026',        // Appears in @copyright tags
    'license' => 'MIT License',                 // Appears in @license tags
    'version' => '1.0.0',                       // Appears in @version tags
];
```

Generated files will include these in their docblocks. Update this immediately after installation to avoid generic placeholders in your code.

### Working Directory

**All Roline commands must be run from your project root** - the directory containing the `roline` file, `application/`, `public/`, `config/`, and `vendor/`.

```bash
# ✅ CORRECT: From project root
cd /path/to/your-project
php roline model:create User

# ❌ WRONG: From subdirectories
cd /path/to/your-project/application
php roline model:create User    # This won't work
```

If you get "Command not found" or path errors, check you're in the project root directory.

---

## Your First Model

Let's build a complete User model with authentication fields, step by step.

**Create the model:**

```bash
php roline model:create User
```

**Edit the generated file** (`application/models/UserModel.php`):

```php
<?php namespace Models;

/**
 * User Model
 *
 * Represents authenticated users in the system.
 * Handles user accounts, authentication, and profile data.
 *
 * @author Your Name
 * @copyright Copyright (c) 2026
 * @license MIT License
 * @version 1.0.0
 */

use Rackage\Model;

class UserModel extends Model
{
    // ==================== MODEL PROPERTIES ====================

    /**
     * Database table name for this model
     *
     * @var string
     */
    protected static $table = 'users';

    /**
     * Enable automatic timestamp management
     *
     * @var bool
     */
    protected static $timestamps = true;

    // ==================== TABLE COLUMNS ====================

    /**
     * Unique user identifier
     * @column
     * @primary
     * @autonumber
     */
    protected $id;

    /**
     * User's full name
     * @column
     * @varchar 255
     */
    protected $name;

    /**
     * Unique email address for login
     * @column
     * @varchar 255
     * @unique
     */
    protected $email;

    /**
     * Hashed password
     * @column
     * @varchar 255
     */
    protected $password;

    /**
     * Account status flag
     * @column
     * @tinyint
     * @default 1
     */
    protected $is_active;

    /**
     * When the user registered
     * @column
     * @datetime
     */
    protected $created_at;

    /**
     * When the user profile was last updated
     * @column
     * @datetime
     */
    protected $updated_at;
}
```

**Generate the database table:**

```bash
php roline model:table-create User
```

Output:
```
Creating table: users

✓ Table created successfully
  - 7 columns created
  - 1 unique index created (email)
  - Primary key set (id)
```

**Use your model in a controller:**

```php
<?php namespace Controllers;

use Rackage\Controller;
use Models\UserModel;

class AuthController extends Controller
{
    public function register()
    {
        UserModel::save([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => password_hash('secret', PASSWORD_DEFAULT)
        ]);
    }

    public function getUsers()
    {
        $users = UserModel::where('is_active', 1)->all();
        return $users;
    }
}
```

**That's your first model.** Schema defined in annotations, table created automatically, ready to use.

---

## Command Overview

List all available commands:

```bash
php roline list
```

Output shows commands grouped by category:

```
✓ Roline - Rachie Command Line Interface

→ Controller Commands:
  controller:create <Controller|required>
  controller:append <Controller|required> <method|required>
  controller:delete <Controller|required>
  controller:complete <name|required>

→ Model Commands:
  model:create <Model|required>
  model:delete <Model|required>
  model:append <Model|required>
  model:table-create <Model|required>
  model:table-update <Model|required>
  model:table-drop <Model|required>
  model:table-rename <Model|required> <new_table_name|required>
  model:table-schema <Model|required>
  model:table-empty <Model|required>
  model:table-export <Model|required> <file|optional>

→ View Commands:
  view:create <view|required>
  view:add <directory|required> <file|required>
  view:delete <view|required>

→ Table Commands:
  table:create <tablename|required> [--sql=file]
  table:copy <source|required> <destination|required> [--empty]
  table:delete <tablename|required>
  table:rename <old_tablename|required> <new_tablename|required>
  table:schema <tablename|required>
  table:export <tablename|required> <file|optional>
  table:partition <table|required> <type(column)|required> <count|required>
  table:unpartition <table|required>

→ Migration Commands:
  migration:make <name|required>
  migration:run
  migration:rollback <steps|optional>
  migration:status

→ Database Commands:
  db:list
  db:tables [database]
  db:create [database] [--if-not-exists]
  db:drop [database]
  db:table-drops
  db:reset
  db:schema
  db:seed <seeder|optional>
  db:export <file|optional>
  db:import <file|required>

→ Cache Commands:
  cleanup:cache
  cleanup:views
  cleanup:logs
  cleanup:sessions
  cleanup:all

→ Utility Commands:
  list
  help
  version

Use 'php roline <command> --help' for detailed information.
```

---

## Best Practices & Command Selection

Before diving into commands, let's make sure you're using the right ones. This will save you from debugging headaches later.

### ⚠️ The Golden Rule: Use the Right Command Family

**If your table has a model, ALWAYS use `model:` commands. Never mix `model:` and `table:` commands on the same table.**

Here's why this matters:

- `model:` commands read your model annotations and keep everything in sync
- `table:` commands work directly on the database, completely bypassing your model
- Mixing them causes schema drift - your model thinks one thing, the database has another
- Migrations won't capture changes you made with `table:` commands
- You'll get confusing "column not found" errors when your model expects columns that don't exist

**Use `model:` commands for:** (This is 99% of your tables)

```bash
php roline model:table-create User
php roline model:table-update User
php roline model:table-schema User
php roline model:table-export User
```

**Use `table:` commands ONLY for:**

- Legacy tables you haven't created models for yet
- Temporary tables you're experimenting with
- Simple lookup/reference tables that don't need model logic
- One-off operations on tables without models

```bash
php roline table:create temp_import_data
php roline table:schema legacy_customer_archive
php roline table:delete old_temp_table
```

**Use `db:` commands for:**

- Database-level operations affecting all tables at once
- Creating or dropping entire databases
- Full database exports, imports, or resets

```bash
php roline db:export          # Exports your entire database
php roline db:tables          # Lists all tables
php roline db:create test_db  # Creates a new database
```

### Model Name Format

When you run `model:` commands, use your model name without the "Model" suffix:

```bash
# ✅ RECOMMENDED - Just the name
php roline model:create User      # Creates UserModel.php
php roline model:table-create User

# ✅ ALSO WORKS - With "Model" suffix (auto-stripped)
php roline model:table-update UserModel

# ✅ ALSO WORKS - Case insensitive
php roline model:create user
php roline model:table-update USER

# 💡 CONVENTION - Use PascalCase for readability
User, Post, Product, OrderItem (not user, post, product)
```

**Note:** While `UserModel` works (Roline strips the suffix automatically), we recommend using just `User` for consistency and brevity.

### Naming Conventions: Models vs Tables

**Critical to understand**: Roline follows standard conventions for model and table names.

**Model Names** - Singular, PascalCase:
```bash
User         → Creates UserModel.php
Product      → Creates ProductModel.php
OrderItem    → Creates OrderItemModel.php
BlogPost     → Creates BlogPostModel.php
```

**Table Names** - Plural, snake_case (auto-generated):
```bash
User         → Table: users
Product      → Table: products
OrderItem    → Table: order_items       # Compound names get snake_cased
BlogPost     → Table: blog_posts
UserRole     → Table: user_roles
```

**What happens automatically:**
1. Model names stay singular (User → UserModel.php)
2. Table names get pluralized (User → 'users')
3. Compound names get snake_cased (OrderItem → 'order_items', then pluralized)

**Custom Table Names** - When you need specific naming:

Want to use a specific table name instead of the auto-generated one? You have two options:

```bash
# Option A: Specify table name during creation
php roline model:create Product inventory    # ProductModel with table 'inventory'
php roline model:create Data datum           # DataModel with table 'datum'
php roline model:create BlogPost posts       # BlogPostModel with table 'posts'

# Option B: Create normally, then rename table later
php roline model:create Product              # Creates with table 'products'
php roline model:table-rename Product inventory   # Renames to 'inventory' (updates model too)
```

Both approaches work. Use Option A when you know the table name upfront. Use Option B when you decide to change it later.

**Important**: The second argument to `model:create` is the TABLE name (lowercase), not another model name.

### Database Command Defaults

The `db:` commands work on the database specified in your `config/database.php` file by default. You can override this by providing a database name:

```bash
# Works on your configured database
php roline db:create       # Creates the database from config
php roline db:export       # Exports your configured database
php roline db:tables       # Lists tables in your configured database

# Works on a specific database you name
php roline db:create staging_db
php roline db:tables staging_db
php roline db:drop test_db
```

This is super helpful when managing multiple environments (development, staging, testing).

### The Correct Migration Workflow

This trips up a lot of people: **Always update your database BEFORE creating migrations.**

```bash
# ✅ CORRECT ORDER:
1. Edit your model annotations (add/remove/modify @column properties)
2. php roline model:table-update User    # Apply changes to database first
3. php roline migration:make add_email   # Now capture the diff for deployment

# ❌ WRONG ORDER - Your migration will be empty or incomplete:
1. Edit model annotations
2. php roline migration:make add_email   # Diff shows nothing - no DB changes yet!
3. php roline model:table-update User
```

**Why?** The `migration:make` command compares your current database state with the previous migration's snapshot. If you haven't applied your model changes to the database yet, there's nothing to diff - the migration will be empty or won't capture your changes.

Think of it this way: migrations are recordings of changes you already made, not instructions for future changes.

### Common Mistakes to Avoid

Let's look at some pitfalls you'll want to steer clear of:

```bash
# ❌ DON'T: Use table: commands when you have a model
php roline table:create users        # But you have UserModel.php!
php roline table:schema products     # Use model:table-schema Product instead

# ✅ DO: Use model: commands consistently
php roline model:table-create User
php roline model:table-schema Product

# ❌ DON'T: Create migration before updating your database
Edit UserModel.php → migration:make → model:table-update

# ✅ DO: Update database first, then create migration to capture it
Edit UserModel.php → model:table-update → migration:make

# ❌ DON'T: Mix both command families on the same table
php roline model:table-create User
php roline table:rename users people  # Now your model thinks the table is still 'users'!

# ✅ DO: Use model: commands exclusively for model-backed tables
php roline model:table-create User
php roline model:table-rename User people  # Updates both database AND model $table property
```

### Quick Decision Guide

Not sure which command to use? Ask yourself:

**"Does this table have a model file in `application/models/`?"**

- **YES** → Use `model:` commands (model:table-create, model:table-update, etc.)
- **NO** → Use `table:` commands (table:create, table:schema, etc.)

**"Am I working with the entire database or just one table?"**

- **Entire database** → Use `db:` commands (db:export, db:reset, etc.)
- **One table** → See question above

When in doubt, use `model:` commands. They're safer because they read from your model's source of truth.

---

## Model Commands

Model commands help you create and manage your data models and database tables. In Rachie, your model file IS your database schema - you define columns using `@column` annotations in model properties, and Roline reads these annotations to create or update your tables automatically. This eliminates the need for separate migration files and keeps your schema definition close to your model code.

These commands handle the complete lifecycle: creating model files, adding properties interactively, generating tables from annotations, updating existing tables when you change your model, and managing table data (viewing structure, exporting, emptying, or dropping tables).

**Note**: Generated models are starting templates with properly structured schema definitions. You'll need to add your business logic methods, relationships, validation, and custom query methods specific to your application.

### model:create

Generates your model file with the table name and basic structure ready. Use this when starting a new resource in your application. The command can add properties interactively right after creation, or you can add them later with `model:append` or by editing the file directly. Your model properties with `@column` annotations become your database schema.

**Syntax:**
```bash
php roline model:create <Model> [table]
```

**Standard usage** (auto-generates table name):

```bash
php roline model:create Product
```

Output:
```
Model created: application/models/ProductModel.php
Table name: products (auto-pluralized)
```

**Specify your own table name** (optional):

```bash
php roline model:create Product inventory
php roline model:create Data datum
php roline model:create BlogPost posts
```

Output:
```
Model created: application/models/ProductModel.php
Table name: inventory (custom)
```

Use a custom table name when you have specific naming requirements or preferences. Otherwise, Roline generates one automatically.

**Alternative approach**: Create with auto-generated table name, then use `model:table-rename` later if needed (see below).

**Interactive property creation**: After creating the model, you'll be prompted to add properties interactively. This quickly scaffolds basic properties with name and type - you'll still need to edit the model file to add descriptions, additional annotations (@unique, @nullable, etc.), and refine data types.

### model:append

Adds new properties with `@column` annotations to your existing model through interactive prompts. Use this when extending your data model with additional fields. Each property you add here can later become a database column with `model:table-update`. Saves you from manually writing annotations.

Add properties to existing model interactively:

```bash
php roline model:append Product
```

Interactive prompts:
```
Adding properties to ProductModel

Enter property details (press Enter with empty name to finish):

Property name (or press Enter to finish): price
Property type [varchar(255)]: decimal(10,2)
  Added: $price (decimal(10,2))

Property name (or press Enter to finish): stock
Property type [varchar(255)]: int
  Added: $stock (int)

Property name (or press Enter to finish):

Added 2 properties to ProductModel

Next steps:
  1. Review the model file: application/models/ProductModel.php
  2. Update the table: php roline model:table-update Product
```

Inserts properties with `@column` annotations before the MODEL METHODS section.

### model:table-create

Reads your model's `@column` annotations and creates the database table. **Warning**: This DROPS the existing table first, so all data is permanently lost. Use this only for new tables or when you're certain you want to recreate from scratch. For existing tables with data, use `model:table-update` instead.

Creates database table from model annotations:

```bash
php roline model:table-create Product
```

Output:
```
Creating table from Model: Models\ProductModel
  Table name: products

WARNING: This will DROP the existing 'products' table if it exists!
         All data will be lost!

Are you sure you want to create this table? (y/n): y

Creating table 'products'...

Table 'products' created successfully!
```

**Important**: This DROPS existing tables. Use `model:table-update` for safe updates.

### model:table-update

The safe way to sync your database with model changes. Compares your model's `@column` annotations with the actual database table and generates ALTER TABLE statements to add new columns, change data types (like `@varchar(255)` to `@text`), or modify constraints. Your data stays intact. Use special annotations like `@drop` and `@rename` to explicitly handle column deletions and renames. This is what you'll use most often after initial table creation.

Safely update existing table from model changes:

```bash
php roline model:table-update Product
```

Output:
```
Updating table 'products' from Model: Models\ProductModel

Table 'products' updated successfully!
```

Use special annotations:
- `@drop` - Mark column for deletion
- `@rename old_name` - Rename column

If dropping or renaming columns, you'll see:

```
⚠ PENDING CHANGES:

RENAMES (data preserved):
  - old_price → price

DROPS (data will be lost):
  - obsolete_column (marked with @drop)

Apply these changes? (y/n):
```

### model:table-schema

Displays your database table structure using your model name. Use this to verify what's actually in the database, check if your `model:table-update` changes were applied correctly, or debug schema mismatches. Shows columns, data types, indexes, and keys. Same output as `table:schema` but you pass your model name instead of the table name.

View table structure:

```bash
php roline model:table-schema Product
```

Output:
```
=================================================
  TABLE: products
=================================================

Columns:

  COLUMN               TYPE                      NULL       KEY             DEFAULT
  ---------------------------------------------------------------------------------
  id                   int(11)                   NO         PRI             NULL
  name                 varchar(255)              NO                         NULL
  price                decimal(10,2)             YES                        NULL
  stock                int(11)                   YES                        0
  created_at           datetime                  YES                        NULL
  updated_at           datetime                  YES                        NULL

Indexes:

  INDEX NAME                COLUMN          UNIQUE     TYPE
  ---------------------------------------------------------------------------
  PRIMARY                   id              YES        BTREE

=================================================
```

### model:delete

Removes the model class file from your filesystem. Use this when you've deleted a feature or resource from your application. This only deletes the PHP file - your database table remains untouched. Use `model:table-drop` if you need to remove the table as well.

Delete a model file:

```bash
php roline model:delete Product
```

Output:
```
You are about to delete: application/models/ProductModel.php
Are you sure you want to delete this model? (y/n): y

Model deleted: application/models/ProductModel.php
```

**Note**: This only deletes the model file, not the database table. Use `model:table-drop` to delete the table.

### model:rename

Renames a model file and updates the class name inside the file. Use this when you need to rename a model to better reflect its purpose or fix naming conventions. The command handles the file renaming and class name update automatically.

**Note**: This command only renames the model file and class name. You'll need to manually update any references to the old model name in controllers, views, or other files.

Rename a model:

```bash
php roline model:rename Todos Todo
```

Output:
```
Model renamed successfully!
  Old: application/models/TodosModel.php
  New: application/models/TodoModel.php
```

The command updates both the filename and the class name inside the file. You can provide names with or without the "Model" suffix.

### model:table-drop

Completely destroys your database table and all its data forever. Use this when removing obsolete features or cleaning up development databases. The command requires double confirmation because this cannot be undone. Your model file remains - this only affects the database. Similar to `table:delete` but works with your model name.

Permanently drop a database table:

```bash
php roline model:table-drop Product
```

Output:
```
╔═══════════════════════════════════════════════════════════╗
║                     DANGER ZONE                           ║
╚═══════════════════════════════════════════════════════════╝

You are about to PERMANENTLY DELETE table: products
ALL DATA in this table will be LOST FOREVER!
This action CANNOT be undone!

Type the table name to confirm deletion: products (y/n): y

Are you ABSOLUTELY SURE you want to delete 'products'? (y/n): y

Dropping table 'products'...

Table 'products' has been dropped.
```

**Important**: This permanently deletes the table and ALL data. Cannot be undone.

### model:table-rename

Renames your database table AND automatically updates your model's `$table` property to match. Use this when refactoring table names or fixing incorrect auto-pluralization. This is a one-command operation that keeps everything in sync.

**Why use this instead of manual rename:**
- Updates BOTH database table name AND model's `$table` property
- No data loss - all table data is preserved during rename
- Prevents sync issues - manually renaming requires changing two places (database + model file)
- If you forget to update either one, queries break with "table not found" errors

Rename database table and update model:

```bash
php roline model:table-rename Product items
```

Output:
```
Renaming table:
  From: products
  To:   items

Model $table property will be updated automatically.

Are you sure you want to rename this table? (y/n): y

Renaming table in database...
Table renamed successfully!

Updating model $table property...
Model $table property updated!

Table rename complete!
```

**What changes:**
- Database table name: `products` → `items`
- Model `$table` property: `'products'` → `'items'`
- All existing data preserved

**What stays the same:**
- Model file name: `ProductModel.php` (unchanged)
- Model class name: `ProductModel` (unchanged)

**Note**: Second argument is the new TABLE name (lowercase, e.g., 'items' not 'Item').

### model:table-empty

Wipes all data from your table while keeping the table structure intact. Use this for clearing test data, resetting development databases, or removing all records before a fresh import. Uses DELETE (not TRUNCATE) so foreign keys are respected and auto-increment counters stay as-is.

If you want to reset auto-increment use `php roline model:table-reset ModelName`

Delete all rows from table:

```bash
php roline model:table-empty Product
```

Output:
```
WARNING: You are about to delete ALL rows from table: products
         Current row count: 1523
         This action CANNOT be undone!

Note: Table structure will be preserved (unlike DROP TABLE)
      Auto-increment counter will NOT be reset (unlike TRUNCATE)

Are you sure you want to empty this table? (y/n): y

Emptying table 'products'...

Table 'products' has been emptied.
All rows deleted. Table structure preserved.
```

Uses DELETE (not TRUNCATE) - respects foreign keys, preserves auto-increment counter.

### model:table-reset

Resets your table by truncating all data AND resetting the auto-increment counter back to 1. Use this when you need a completely fresh start with IDs starting from 1 again. Unlike `model:table-empty` which preserves the auto-increment counter, this gives you a clean slate. Much faster than DELETE for large tables but temporarily disables foreign key checks.

Reset table and auto-increment:

```bash
php roline model:table-reset Product
```

Output:
```
WARNING: You are about to TRUNCATE table: products
         Current row count: 1523
         Auto-increment will RESET to 1!
         This action CANNOT be undone!

Note: Table structure will be preserved
      This is FASTER than DELETE but resets auto-increment

Are you sure you want to reset this table? (y/n): y

Resetting table 'products'...

Table 'products' has been reset.
All rows deleted. Auto-increment reset to 1.
```

**Difference from model:table-empty:**
- `model:table-empty` - Uses DELETE (slow, safe, preserves auto-increment)
- `model:table-reset` - Uses TRUNCATE (fast, resets auto-increment to 1)

Uses TRUNCATE - faster but resets auto-increment. May fail if table has foreign key references.

### model:table-export

Dumps your table's data to a file for backups, data migration, or sharing. Automatically detects format from file extension (.sql for SQL INSERT statements, .csv for spreadsheets). Use this before risky operations, when moving data between environments, or creating test fixtures. Same as `table:export` but works with model names.

Export table data to SQL or CSV:

```bash
php roline model:table-export Product
```

Auto-generates filename with timestamp:
```
Exporting table 'products'...

Export complete!

Format: SQL
Location: application/storage/exports/products_2026-01-08_143022.sql
```

Export to specific file:

```bash
php roline model:table-export Product products_backup.sql
php roline model:table-export Product products.csv
```

Format detected from file extension (.sql or .csv). Files saved to `application/storage/exports/`.

---

## Controller Commands

Controller commands help you create and manage HTTP request handlers in your application. Controllers coordinate between your models and views - they receive requests, fetch data from models, and pass it to views for rendering. Rachie uses method prefixes (get, post, put, delete) to automatically route HTTP verbs to the right controller methods.

These commands generate controllers with RESTful CRUD methods already scaffolded, let you add custom methods interactively, and provide the complete MVC scaffold (controller + model + views) in one command for rapid prototyping.

**Note**: Generated controllers are scaffolds with empty methods and commented examples showing common patterns. You'll need to implement the actual logic: data validation, model operations, error handling, authorization checks, and business rules specific to your application.

### controller:create

Generates your controller file with RESTful CRUD methods already scaffolded as starting templates. Use this when starting a new feature or resource - it saves you from manually creating the file and typing boilerplate structure. The command can add custom methods interactively, or you can add them later with `controller:append` or by editing directly.

**Note**: The generated controller uses `TodoModel` as a placeholder in all methods. You'll want to replace `TodoModel` with your actual model class name (e.g., `PostModel`, `UserModel`).

Create a new controller:

```bash
php roline controller:create Posts
```

Output:
```
Controller created: application/controllers/PostsController.php

Would you like to add custom methods to this controller now? (y/n):
```

If you answer `y`, it prompts for method names interactively. If `n`:
```
You can add methods later using: php roline controller:append Posts
```

Generated controller includes RESTful CRUD methods: `getIndex()`, `getNew()`, `postNew()`, `getShow($id)`, `getEdit($id)`, `postEdit($id)`, `getDelete($id)`. Each method demonstrates common patterns using `TodoModel` as a placeholder.

### controller:append

Adds a new method template to your existing controller. Use this when extending your controller with additional actions beyond the standard CRUD operations (like `getPublished`, `getArchive`, `getExport`). Auto-generates the method skeleton with GET prefix and proper view path - you still need to implement the actual functionality inside the method.

Add a method to existing controller:

```bash
php roline controller:append Posts published
```

Output:
```
Method 'getPublished' added to PostsController
View path: posts.published
```

Generates:
```php
/**
 * Handle published action
 *
 * @return void
 */
public function getPublished()
{
    View::render('posts.published');
}
```

Method inserted before closing brace with GET prefix.

### controller:delete

Removes the controller class file from your filesystem. Use this when removing features from your application. This only deletes the PHP file - routes in your routing configuration and related view files remain. You'll need to clean those up manually if needed.

Delete a controller file:

```bash
php roline controller:delete Posts
```

Output:
```
You are about to delete: application/controllers/PostsController.php
Are you sure you want to delete this controller? (y/n): y

Controller deleted: application/controllers/PostsController.php
```

**Note**: This only deletes the controller file, not routes or views.

### controller:rename

Renames a controller file and updates the class name inside the file. Use this when you need to rename a controller to better match your resources or fix naming conventions. The command handles the file renaming and class name update automatically.

**Note**: This command only renames the controller file and class name. You'll need to manually update any routes, view paths, redirects, or other references to the old controller name.

Rename a controller:

```bash
php roline controller:rename Todos Todo
```

Output:
```
Controller renamed successfully!
  Old: application/controllers/TodosController.php
  New: application/controllers/TodoController.php
```

The command updates both the filename and the class name inside the file. You can provide names with or without the "Controller" suffix.

### controller:complete

Creates a complete MVC scaffold in one command - controller, model, and views all together as starting templates. Use this for rapid prototyping or when starting a brand new resource from scratch. Generates the file structure and boilerplate you need to start building: controller with empty CRUD methods, model with basic structure, view templates (layout, index, show, create), and CSS file. You'll still need to add your properties to the model, implement controller logic, and customize the views for your specific application. If any step fails, it rolls back automatically.

Create controller, model, and views together:

```bash
php roline controller:complete Posts
```

Output:
```
Creating complete MVC scaffold for: Posts

1. Creating controller...
   ✓ application/controllers/PostsController.php

2. Creating model...
   ✓ application/models/PostsModel.php

3. Creating views...
   ✓ application/views/posts/layout.php
   ✓ application/views/posts/index.php
   ✓ application/views/posts/show.php
   ✓ application/views/posts/create.php
   ✓ public/css/posts.css

Complete scaffold created successfully!

Next steps:
  1. Add properties to model: php roline model:append Posts
  2. Create database table: php roline model:table-create Posts
  3. Implement controller methods
  4. Customize view templates
```

Creates complete MVC resource in one command. If any step fails, rolls back created files.

---

## View Commands

View commands help you create and organize your HTML templates. Views render the user interface by displaying data passed from controllers using Rachie's template engine with features like layout inheritance (`@extends`), sections (`@section/@yield`), loops (`@loopelse`), and conditionals (`@if`).

These commands generate complete view structures with layout files, individual templates (index, show, create), and accompanying CSS files, following a consistent organization pattern that makes your views easy to maintain and extend.

**Note**: Generated views are template starting points with example markup demonstrating Rachie's template syntax and common patterns. You'll need to customize the HTML, add your actual content, style the CSS, handle your specific data fields, and build the UI/UX appropriate for your application.

### view:create

Generates a complete view directory structure with template files demonstrating Rachie's template engine syntax (layout with @extends/@section/@yield, index with @loopelse, show, create with CSRF forms) and a CSS file. Use this when starting a new feature - it saves you from manually creating files and typing HTML boilerplate. The templates include example markup showing common patterns like flash messages, form handling, and empty states. You'll need to customize the HTML for your actual data fields, style the CSS, and build the UI/UX specific to your application.

Create complete view structure:

```bash
php roline view:create posts
```

Output:
```
View structure created successfully!

  ✓ application/views/posts/layout.php
  ✓ application/views/posts/index.php
  ✓ application/views/posts/show.php
  ✓ application/views/posts/create.php
  ✓ application/views/posts/edit.php
  ✓ public/css/posts.css

Usage in controller:
  View::render('posts/index');
  View::render('posts/show', ['id' => $id]);
```

Creates directory with layout, index, show, create, and edit templates, plus CSS file with responsive styling.

### view:add

Adds a single view file to an existing view directory with basic HTML5 boilerplate. Use this when you need additional templates beyond the standard set (like edit.php, profile.php, or custom action views). The generated file is a simple starting point - just the HTML structure with a heading and comment placeholder. You'll need to add your actual content, template syntax, and styling.

Add view file to existing directory:

```bash
php roline view:add posts edit
```

Output:
```
View file created: application/views/posts/edit.php
Use: View::render('posts.edit')
```

Generates basic HTML5 template:
```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Posts - Edit</title>
</head>
<body>
    <h1>Posts Edit</h1>

    <!-- Add your content here -->
</body>
</html>
```

### view:delete

Permanently removes an entire view directory and all template files inside. Use this when removing features from your application or cleaning up unused views. This is destructive and cannot be undone. After deleting the view directory, you'll be prompted to optionally delete the associated CSS file in public/css/.

Delete entire view directory:

```bash
php roline view:delete posts
```

Output:
```
You are about to delete: application/views/posts
This will remove the directory and ALL files inside.
Are you sure you want to delete this view directory? (y/n): y

View directory deleted: application/views/posts

Also delete public/css/posts.css? (y/n): y
CSS file deleted: public/css/posts.css
```

**Warning**: Permanently deletes directory and all files inside. Cannot be undone. CSS deletion is optional.

### view:rename

Renames a view directory and its associated CSS file. Use this when you need to rename views to better match your resources or fix naming conventions. The command handles both the directory and CSS file renaming automatically.

**Note**: This command only renames the view directory and CSS file. You'll need to manually update any View::render() calls in controllers or routes that reference the old view name.

Rename a view directory:

```bash
php roline view:rename todos todo
```

Output:
```
View directory renamed successfully!
  Old: application/views/todos
  New: application/views/todo

CSS file renamed successfully!
  Old: public/css/todos.css
  New: public/css/todo.css
```

The command renames both the view directory and the associated CSS file in public/css/. All view files inside the directory remain unchanged.

---

## Table Commands

Table commands work directly with database tables without requiring model files. Use these for quick table operations, prototyping, lookup tables, or managing tables that don't need models. Unlike model commands that read `@column` annotations, these commands interact with the database schema directly.

**⚠️ Important:** Only use `table:` commands on tables that **don't have models**. If your table has a model in `application/models/`, always use `model:` commands instead. Mixing `table:` and `model:` commands on the same table causes schema drift and breaks your migrations. See [Best Practices](#best-practices--command-selection) for details.

These commands handle creating tables interactively or from SQL files, copying table structures, viewing schemas, exporting data, and managing table partitioning for large datasets.

### table:create

Creates database tables directly without requiring a model file. Use this when you need quick tables for lookups, reference data, or prototyping, or when working with legacy databases that don't follow your model conventions. Unlike `model:table-create` which reads `@column` annotations from your model, this command builds tables interactively or from SQL files.

Create table directly (no model):

```bash
php roline table:create categories
```

Interactive prompts:
```
Creating table: categories

Define columns (press Enter with empty name to finish):

Column name (or press Enter to finish): id
Column type [VARCHAR(255)]: INT
Allow NULL? (y/n): n
Default value (or press Enter for none):
Is this the primary key? (y/n): y
Auto increment? (y/n): y
  Added: id (INT)

Column name (or press Enter to finish): name
Column type [VARCHAR(255)]:
Allow NULL? (y/n): n
Default value (or press Enter for none):
Is this the primary key? (y/n): n
  Added: name (VARCHAR(255))

Column name (or press Enter to finish):

SQL Preview:
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

Create this table? (y/n): y

Creating table...

Table 'categories' created successfully!
```

Or from SQL file:

```bash
php roline table:create users --sql=schema.sql
```

### table:copy

Duplicates a table within the same database, copying both structure and data. Use this for creating backups before risky operations, generating test tables from production data, or creating table variants for experiments. Faster than export/import since data never leaves MySQL. Alternative to `model:table-export` + `table:create` when you need quick copies.

Copy table structure and data:

```bash
php roline table:copy users users_backup
```

Output:
```
Copying table:
  From: users
  To:   users_backup
  Rows: ~1,234 (estimate)

Creating table structure...
Copying data...
  Copied 1,234 rows

Table copied successfully!

New table: users_backup
```

Copy structure only (no data):

```bash
php roline table:copy users users_test --empty
```

### table:delete

Permanently drops a database table by name, without needing a model file. Use this for removing temporary tables, cleaning up test databases, or deleting obsolete tables that were never associated with models. Similar to `model:table-drop` but works directly with table names. Destructive operation - all data permanently lost.

Drop table by name:

```bash
php roline table:delete old_users
```

Output:
```
WARNING: This will permanently drop table 'old_users'!
         All data will be lost!

Are you sure you want to drop this table? (y/n): y

Dropping table 'old_users'...

Table 'old_users' dropped successfully!
```

### table:rename

Renames a database table using RENAME TABLE statement. Use this for tables without models or when you only need to change the database table name without updating model files. If you have a model, use `model:table-rename` instead, which renames the table AND updates the model's `$table` property automatically.

Rename database table:

```bash
php roline table:rename old_users new_users
```

Output:
```
Renaming table:
  From: old_users
  To:   new_users

Proceed with rename? (y/n): y

Renaming table...

Table renamed successfully!
```

**Note**: Does NOT update model files. Use `model:table-rename` if you have a model.

### table:schema

Displays database table structure directly by table name, without requiring a model file. Use this to inspect tables created by other tools, legacy databases, or tables without models. Shows columns, data types, indexes, and keys. Identical functionality to `model:table-schema` but works with table names instead of model names.

Display table structure:

```bash
php roline table:schema users
```

Output:
```
=================================================
  TABLE: users
=================================================

Columns:

  COLUMN               TYPE                      NULL       KEY             DEFAULT
  ---------------------------------------------------------------------------------
  id                   int(11)                   NO         PRI             NULL
  name                 varchar(255)              NO                         NULL
  email                varchar(255)              NO         UNI             NULL
  password             varchar(255)              NO                         NULL
  is_active            tinyint(1)                YES                        1
  created_at           datetime                  YES                        NULL
  updated_at           datetime                  YES                        NULL

Indexes:

  INDEX NAME                COLUMN          UNIQUE     TYPE
  ---------------------------------------------------------------------------
  PRIMARY                   id              YES        BTREE
  email                     email           YES        BTREE

=================================================
```

### table:empty

Deletes all rows from a table while preserving the table structure and auto-increment counter. Works directly with table names (no model required). Use this for clearing data from tables without models, or when you need to respect foreign key constraints. Unlike TRUNCATE, this uses DELETE so it's safer but slower.

Delete all rows from table:

```bash
php roline table:empty users
```

Output:
```
WARNING: You are about to delete ALL rows from table: users
         Current row count: 845
         This action CANNOT be undone!

Note: Table structure will be preserved (unlike DROP TABLE)
      Auto-increment counter will NOT be reset (unlike TRUNCATE)

Are you sure you want to empty this table? (y/n): y

Emptying table 'users'...

Table 'users' has been emptied.
All rows deleted. Table structure preserved.
```

Uses DELETE - respects foreign keys, preserves auto-increment. Same as `model:table-empty` but for tables without models.

### table:reset

Resets a table by truncating all data and resetting the auto-increment counter to 1. Works directly with table names (no model required). Use this when you need a completely fresh start with IDs beginning at 1. Much faster than DELETE for large tables. Temporarily disables foreign key checks.

Reset table and auto-increment:

```bash
php roline table:reset users
```

Output:
```
WARNING: You are about to TRUNCATE table: users
         Current row count: 845
         Auto-increment will RESET to 1!
         This action CANNOT be undone!

Note: Table structure will be preserved
      This is FASTER than DELETE but resets auto-increment

Are you sure you want to reset this table? (y/n): y

Resetting table 'users'...

Table 'users' has been reset.
All rows deleted. Auto-increment reset to 1.
```

**Difference from table:empty:**
- `table:empty` - Uses DELETE (slow, safe, preserves auto-increment)
- `table:reset` - Uses TRUNCATE (fast, resets auto-increment to 1)

Uses TRUNCATE - faster than DELETE but resets auto-increment. Same as `model:table-reset` but for tables without models.

### table:export

Exports table data to SQL or CSV format by table name, without requiring a model. Use this for backing up tables that don't have models, exporting legacy data, or creating SQL dumps for tables created with `table:create`. Identical functionality to `model:table-export` but accepts table names instead of model names.

Export table to SQL or CSV:

```bash
php roline table:export users
```

Auto-generates filename:
```
Exporting table 'users'...

Export complete!

Format: SQL
Location: application/storage/exports/users_2026-01-08_143022.sql
```

Export with specific filename:

```bash
php roline table:export users users_backup.sql
php roline table:export products products.csv
```

Format detected from extension. Same functionality as `model:table-export`.

### table:partition

Adds MySQL partitioning to existing tables for improved query performance on massive datasets (100M+ rows). Uses safe copy-swap method that keeps original table live during the process, minimizing downtime. Use this when queries on large tables are slow even with proper indexes, or when you need to manage data by ranges/hash buckets for archival. Partition column must be part of the primary key.

Add partitioning to large table:

```bash
php roline table:partition links hash(source) 32
```

Output:
```
Copying table:
  From: links
  To:   links (partitioned)
  Rows: ~50,000,000 (estimate)

Process:
  1. Creates new partitioned table
  2. Copies data in batches (original stays live)
  3. Brief lock to swap tables
  4. Drops old table

Proceed? (y/n): y

Creating partitioned table...
Copying data in batches...
  → 10,000,000 rows copied...
  → 20,000,000 rows copied...
  ...
Swapping tables...

Table partitioned successfully!
```

Uses copy-swap method - safe for production with minimal lock time. Partition column must be part of PRIMARY KEY.

### table:unpartition

Removes MySQL partitioning from tables, converting them back to standard non-partitioned tables. Use this when partitioning is no longer needed (data size reduced, schema changed) or when you need to modify the partition scheme (unpartition first, then re-partition differently). Uses same safe copy-swap method as `table:partition`, keeping original table live during conversion.

Remove partitioning from table:

```bash
php roline table:unpartition links
```

Output:
```
Removing partitioning from: links
Current: PARTITION BY HASH(source) PARTITIONS 32

Process:
  1. Creates new non-partitioned table
  2. Copies data in batches (original stays live)
  3. Brief lock to swap tables
  4. Drops old partitioned table

Proceed? (y/n): y

Creating non-partitioned table...
Copying data in batches...
  → 10,000,000 rows copied...
  → 20,000,000 rows copied...
  ...
Swapping tables...

Partitioning removed successfully!
```

---

## Part 6: Migration Commands

Migration commands manage database schema changes across environments through version-controlled migration files. When you create or modify tables using model or table commands, Roline captures those changes as migrations - PHP files with `up()` and `down()` methods containing SQL to apply and revert changes. This lets you version control your schema changes alongside code, deploy confidently to production, and roll back problematic changes when needed.

Migrations work by comparing your current database schema with snapshots saved from previous migrations. When you run `migration:make`, Roline detects what changed (new tables, added columns, modified indexes) and generates the SQL automatically. Run `migration:run` to apply pending migrations in other environments. Use `migration:status` to see what's been run and what's pending. Use `migration:rollback` if you need to undo the last batch. Each migration is tracked in a database table to prevent double-execution and enable rollback functionality.

### migration:make

Captures current database changes as a versioned migration file by comparing your database schema with the last migration snapshot. Use this after creating or modifying tables with model or table commands to record those changes for deployment to other environments. Generates both the migration file (with `up()` and `down()` SQL) and a schema snapshot for the next comparison. The migration name should describe what changed (like "add_email_to_users" or "create_products_table").

**⚠️ Critical Workflow:** Always run `model:table-update` (or `model:table-create`) BEFORE running `migration:make`. The migration captures changes that already exist in your database, not pending model changes. If you create a migration before updating your database, the migration will be empty or incomplete. See [Migration Workflow](#the-correct-migration-workflow) for details.

Create migration after making database changes:

```bash
php roline migration:make add_status_to_posts
```

Output:
```
Reading current database schema...
Comparing with previous state...

Migration created successfully!

Migration: application/database/migrations/2025_01_15_143022_add_status_to_posts.php
Schema:    application/database/schemas/2025_01_15_143022_add_status_to_posts.json
```

Generated migration file structure:

```php
<?php

use Rackage\Registry;

/**
 * Add Status To Posts
 *
 * Migration: add_status_to_posts
 * Created: 2025-01-15 14:30:22
 *
 * This migration was auto-generated by comparing your current database
 * schema with the previous migration state.
 */

/**
 * Run the migration (apply changes)
 */
function up()
{
    $db = Registry::get('database-sync');
    $query = $db->query();

    $query->transaction();

    try {
    $db->execute("
        ALTER TABLE posts
        ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'draft'
    ");

        $query->commit();

    } catch (Exception $e) {
        $query->rollback();
        throw $e;
    }
}

/**
 * Reverse the migration (rollback changes)
 */
function down()
{
    $db = Registry::get('database-sync');
    $query = $db->query();

    $query->transaction();

    try {
    $db->execute("
        ALTER TABLE posts
        DROP COLUMN status
    ");

        $query->commit();

    } catch (Exception $e) {
        $query->rollback();
        throw $e;
    }
}
```

Timestamp prefix prevents filename conflicts. Requires database changes to exist before running - errors if no changes detected since last migration. Use `table:create`, `table:update`, or `model:table-create`/`model:table-update` first to make schema changes, then capture them with `migration:make`.

### migration:run

Executes all pending migrations that haven't been run yet, applying their `up()` SQL to update the database schema. Use this after pulling code with new migration files, or when deploying to staging/production to sync the database with your codebase. Migrations run sequentially in timestamp order (oldest first), and each is tracked in a database table to prevent double-execution. Stops immediately if any migration fails.

Run all pending migrations:

```bash
php roline migration:run
```

Output:
```
Running 3 migration(s)...

  → 2025_01_15_120000_create_users.php
  ✓ 2025_01_15_120000_create_users.php
  → 2025_01_15_130000_add_email_verification.php
  ✓ 2025_01_15_130000_add_email_verification.php
  → 2025_01_15_143022_add_status_to_posts.php
  ✓ 2025_01_15_143022_add_status_to_posts.php

Ran 3 migration(s) successfully!
```

If no pending migrations:
```
No pending migrations.
```

Each successfully-run migration is recorded in the migrations tracking table with a batch number. If a migration fails, the command stops and shows the error - you'll need to fix the migration file and run again (already-successful migrations are skipped). Use `migration:status` before running to see which migrations will execute.

### migration:rollback

Reverts previously executed migrations by running their `down()` methods in reverse chronological order (newest first). Use this to undo problematic migrations in development, roll back failed deployments, or temporarily revert schema changes. By default rolls back the last batch (all migrations from the most recent `migration:run`). Optional steps parameter lets you roll back multiple batches.

Roll back last batch of migrations:

```bash
php roline migration:rollback
```

Output:
```
Rolling back 2 migration(s)...

  → 2025_01_15_143022_add_status_to_posts.php
  ✓ 2025_01_15_143022_add_status_to_posts.php
  → 2025_01_15_130000_add_email_verification.php
  ✓ 2025_01_15_130000_add_email_verification.php

Rolled back 2 migration(s) successfully!
```

Roll back multiple batches:

```bash
php roline migration:rollback 3
```

Output:
```
Rolling back 5 migration(s)...

  → 2025_01_15_143022_add_status_to_posts.php
  ✓ 2025_01_15_143022_add_status_to_posts.php
  → 2025_01_15_130000_add_email_verification.php
  ✓ 2025_01_15_130000_add_email_verification.php
  → 2025_01_15_120000_create_users.php
  ✓ 2025_01_15_120000_create_users.php
  (continues for all migrations in last 3 batches...)

Rolled back 5 migration(s) successfully!
```

If no migrations to rollback:
```
No migrations to rollback.
```

Successfully rolled-back migrations are removed from the tracking table and can be run again. Rollback only reverts schema changes - it does NOT restore deleted data. The `down()` method must properly reverse the `up()` method changes. Stops immediately if any rollback fails. Test rollbacks on development database first and backup before rolling back on production.

### migration:status

Shows comprehensive overview of all migrations - which have been executed and which are pending. Use this before running migrations to see what will execute, after deployment to verify all migrations ran, or when debugging to understand database migration state. Read-only command that makes no changes.

Check migration status:

```bash
php roline migration:status
```

Output (mixed state):
```
=================================================
              MIGRATION STATUS
=================================================

Ran Migrations (3):

  ✓ 2025_01_15_120000_create_users.php
  ✓ 2025_01_15_130000_add_email_verification.php
  ✓ 2025_01_15_135000_create_posts.php

Pending Migrations (2):

  ⏳ 2025_01_15_140000_add_status_to_posts.php
  ⏳ 2025_01_15_145000_create_comments.php

=================================================
```

Output (up to date):
```
=================================================
              MIGRATION STATUS
=================================================

Ran Migrations (5):

  ✓ 2025_01_15_120000_create_users.php
  ✓ 2025_01_15_130000_add_email_verification.php
  ✓ 2025_01_15_135000_create_posts.php
  ✓ 2025_01_15_140000_add_status_to_posts.php
  ✓ 2025_01_15_145000_create_comments.php

Pending Migrations (0):
  Database is up to date!

=================================================
```

Safe to run anytime without side effects. Queries the migrations tracking table for executed migrations and scans the migrations directory for all files to determine pending migrations. Use before `migration:run` to preview what will execute, or after to confirm all migrations applied successfully.

---

## Part 7: Database Commands

Database commands operate at the database level rather than individual tables - managing entire databases, listing database information, bulk operations across all tables, and data import/export. These commands work directly with MySQL without requiring model files.

**Database Configuration:** Most `db:` commands work on the database specified in your `config/database.php` file by default. You can override this by providing a database name as an argument (e.g., `db:tables staging_db` or `db:create test_db`). See [Database Command Defaults](#database-command-defaults) for examples.

Use these for database-wide operations. Most require careful handling and include confirmation prompts for destructive actions like dropping databases or truncating all tables.

### db:list

Shows all databases on your MySQL server with table counts and table names. Use this to see available databases and quickly assess their size. Shows first few table names for each database, then truncates with '+N more' for databases with many tables. Current database from config marked with asterisk.

List all databases:

```bash
php roline db:list
```

Output:
```
Databases on localhost:

  rachie *
    8 tables: users, posts, comments, categories, +4 more

  testdb
    3 tables: products, orders, customers

  myapp_staging
    (empty)

  * = current database from config
```

Excludes system databases (information_schema, mysql, performance_schema, sys). Current database from config marked with asterisk.

### db:tables

Lists all tables in a database with row counts. Use this to see what tables exist and their approximate sizes. Can query specific database or use config default. Shows formatted output with aligned columns and total row count summary.

List tables in current database:

```bash
php roline db:tables
```

List tables in specific database:

```bash
php roline db:tables myapp_staging
```

Output:
```
Database: rachie

  users              1,245 rows
  posts              8,932 rows
  comments          15,673 rows
  categories            12 rows
  tags                  45 rows

5 tables, ~25,907 total rows
```

Row counts are estimates from MySQL's information_schema. Actual counts may vary slightly for InnoDB tables.

### db:create

Creates a new database with UTF8MB4 charset and collation. Use this for initial project setup or creating test/staging databases. Supports `--if-not-exists` flag to skip if already exists. Can specify database name as argument or uses config. Shows next steps (migration:run, db:seed) after creation.

Create database from config:

```bash
php roline db:create
```

Create specific database:

```bash
php roline db:create myapp_test
```

Output:
```
Creating database: myapp_test
Charset: utf8mb4
Collation: utf8mb4_unicode_ci

Database 'myapp_test' created successfully!

Next steps:
  php roline migration:run     # Run migrations
  php roline db:seed           # Seed data
```

Errors if database already exists unless `--if-not-exists` flag provided. Database must not exist or command fails.

### db:drop

Drops entire database permanently with triple confirmation system. Use with extreme caution - this deletes everything. Shows table count before confirmation. Three separate confirmation steps: yes/no, type exact database name, final yes/no. Lists everything that will be deleted (tables, views, procedures, functions).

Drop database:

```bash
php roline db:drop
```

Output:
```
!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
WARNING: This will DROP THE ENTIRE DATABASE!
!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

Database: rachie
Tables: 8

This will PERMANENTLY DELETE:
  - The database itself
  - ALL tables and their data
  - ALL views, procedures, functions
  - ALL stored routines

This action CANNOT be undone!
You will need to recreate the database manually!

Are you ABSOLUTELY sure you want to DROP database 'rachie'? (y/n): y

To confirm, please type the database name: rachie
Database name: rachie

FINAL WARNING: Drop the entire database now? (y/n): y

Dropping database...

Database 'rachie' has been dropped!

Recreate with: php roline db:create rachie
```

Triple confirmation: yes/no, type database name, final yes/no. NEVER use on production without backup.

### db:table-drops

Drops all tables in database but keeps the database itself. Use for resetting to clean state before migrations. Lists all tables before confirmation. Disables foreign key checks during operation for safe deletion. Continues through errors to drop as many tables as possible, shows success count.

Drop all tables:

```bash
php roline db:table-drops
```

Output:
```
WARNING: This will DROP ALL tables in the database!

Database: rachie
Tables to drop: 8

  → users
  → posts
  → comments
  → categories
  → tags
  → sessions
  → migrations
  → cache

This action CANNOT be undone!

Are you ABSOLUTELY sure you want to drop ALL rachie tables? (y/n): y

To confirm, please type the database name: rachie
Database name: rachie

Final confirmation. Drop all tables now? (y/n): y

Dropping tables...

  → Dropping users...
  → Dropping posts...
  → Dropping comments...
  → Dropping categories...
  → Dropping tags...
  → Dropping sessions...
  → Dropping migrations...
  → Dropping cache...

Successfully dropped 8 tables!

Database 'rachie' is now empty.
```

Triple confirmation like `db:drop`. Disables foreign key checks during operation for safe deletion.

### db:reset

Empties all tables using TRUNCATE while preserving structures. Faster than DELETE and resets auto-increment counters. Disables foreign key checks during operation. Continues through errors, shows count of successfully truncated tables. Single confirmation (not triple like drop commands).

Empty all tables:

```bash
php roline db:reset
```

Output:
```
WARNING: This will TRUNCATE ALL tables (delete all data)!

Database: rachie
Tables to truncate: 8

  → users
  → posts
  → comments
  → categories
  → tags
  → sessions
  → migrations
  → cache

This action CANNOT be undone!
Note: Table structures preserved, auto-increment counters reset

Are you sure you want to TRUNCATE ALL tables in rachie? (y/n): y

Truncating tables...

  → Truncating users...
  → Truncating posts...
  → Truncating comments...
  → Truncating categories...
  → Truncating tags...
  → Truncating sessions...
  → Truncating migrations...
  → Truncating cache...

Successfully truncated 8 tables!

All data deleted from 'rachie'. Auto-increment counters reset.
```

TRUNCATE is faster than DELETE for clearing large tables. Cannot be rolled back even in transactions.

### db:schema

Shows complete database schema including all tables, columns, types, keys, and indexes. Use for documentation or schema review. Read-only command, safe to run anytime. Shows foreign keys with ON DELETE/UPDATE actions, check constraints, and partitioning if configured. Formatted output with borders and indentation for readability.

View entire database schema:

```bash
php roline db:schema
```

Output:
```
Reading database schema...

=================================================
           DATABASE SCHEMA
=================================================

Total Tables: 3

TABLE: users
--------------------------------------------------
  id INT(11) NOT NULL AUTO_INCREMENT
  username VARCHAR(50) NOT NULL
  email VARCHAR(100) NOT NULL
  password VARCHAR(255) NOT NULL
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

  PRIMARY KEY: id

  INDEXES:
    idx_username: username UNIQUE
    idx_email: email UNIQUE

TABLE: posts
--------------------------------------------------
  id INT(11) NOT NULL AUTO_INCREMENT
  user_id INT(11) NOT NULL
  title VARCHAR(200) NOT NULL
  body TEXT
  status VARCHAR(20) NOT NULL DEFAULT 'draft'
  published_at TIMESTAMP

  PRIMARY KEY: id

  INDEXES:
    idx_user_id: user_id
    idx_status: status

  FOREIGN KEYS:
    fk_posts_user_id: user_id → users(id) [ON DELETE CASCADE]

TABLE: comments
--------------------------------------------------
  id INT(11) NOT NULL AUTO_INCREMENT
  post_id INT(11) NOT NULL
  user_id INT(11) NOT NULL
  body TEXT NOT NULL
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

  PRIMARY KEY: id

  INDEXES:
    idx_post_id: post_id
    idx_user_id: user_id

=================================================
```

Read-only command that makes no changes. Shows full schema for all tables in database.

### db:seed

Runs database seeder classes to populate tables with test/sample data. Use after fresh migrations to add development data. Can run all seeders or specify one by name. DatabaseSeeder always runs first when running all (master seeder convention). Continues through errors, shows success/fail count at end. Seeders must be in application/database/seeders/ directory.

Run all seeders:

```bash
php roline db:seed
```

Run specific seeder:

```bash
php roline db:seed Users
```

Output (all seeders):
```
Running all seeders...

Running seeder: DatabaseSeeder...
✓ DatabaseSeeder completed

Running seeder: UsersSeeder...
✓ UsersSeeder completed

Running seeder: PostsSeeder...
✓ PostsSeeder completed

All seeders completed successfully! (3 seeders)
```

Output (specific seeder):
```
Running seeder: UsersSeeder...
✓ UsersSeeder completed
```

Seeders must be in `application/database/seeders/` directory. DatabaseSeeder runs first when running all seeders.

### db:export

Exports entire database to SQL file with streaming (no memory limit - handles any size). Shows live progress with row counts as tables export. Auto-generates timestamped filename if not provided. Batches INSERT statements (1000 rows per statement) for fast import. Can run while other applications access the database. Saves to application/storage/exports/ directory.

Export with auto-generated filename:

```bash
php roline db:export
```

Export with custom filename:

```bash
php roline db:export mybackup.sql
```

Output:
```
Exporting database: rachie

Tables to export: 8

  → Exporting users... (1,245 rows)
  → Exporting posts... (8,932 rows)
  → Exporting comments... (15,673 rows)
  → Exporting categories... (12 rows)
  → Exporting tags... (45 rows)
  → Exporting sessions... (0 rows)
  → Exporting migrations... (15 rows)
  → Exporting cache... (2,341 rows)

Database exported successfully!

Location: /path/to/application/storage/exports/rachie_backup_2025-01-15_143022.sql
```

Exports include DROP TABLE, CREATE TABLE, and INSERT statements. Prompts before overwriting existing files.

### db:import

Imports SQL dump by streaming file line by line (no memory limit - handles any size). Shows live progress updating every 100 statements. Shows duration timing when complete. Smart path resolution checks multiple locations (exact path, current directory, exports folder). Stops on first error with statement and line number. Database must exist before importing.

Import SQL file:

```bash
php roline db:import backup.sql
```

Import with full path:

```bash
php roline db:import /path/to/database_dump.sql
```

Output:
```
Importing database: rachie
Source file: /path/to/backup.sql
File size: 145.3 MB

  → Importing... (100 statements)
  → Importing... (200 statements)
  → Importing... (15,847 statements)

Database imported successfully!

Executed: 15,847 statements
Duration: 2 minutes 34 seconds
```

Compatible with mysqldump, db:export, and standard SQL dumps. Shows file size before starting (KB/MB/GB).

---

## Part 8: Cache Commands

Cache commands clean up temporary files, cached data, compiled views, log files, and sessions. These commands help you reclaim disk space, troubleshoot caching issues, and maintain your application. Most operate on the vault directory structure where Rachie stores temporary data.

Use these during development when debugging caching issues, after deployments to force cache refresh, or during maintenance to clear old files. Some commands like cleanup:sessions log out all users, so run those during maintenance windows.

### cleanup:cache

Clears application cache based on your configured driver (file, memcached, redis). Use this when you need to force cache refresh after code changes or when debugging cache-related issues. Shows your current cache configuration before clearing. For file driver, clears vault/cache/ directory. For memcached/redis, provides connection commands to flush manually. Requires confirmation before clearing.

Clear application cache:

```bash
php roline cleanup:cache
```

Output:
```
Current Cache Configuration:
  Status: Enabled
  Driver: file
  Path: vault/cache

What will be cleared:
  - All files in: vault/cache/

Note: This only clears cache. Use 'cleanup:views' to clear compiled views.

Are you sure you want to clear the cache? (y/n): y

Clearing cache...
Cleared: Application Cache

Cache cleared successfully!
```

For memcached or redis drivers, shows manual flush commands instead of clearing directly. Skips non-existent directories without error.

### cleanup:views

Clears orphaned compiled view templates from vault/tmp/ directory. Use this after debugging view errors or when you notice many old compiled files accumulating. Rachie compiles templates to temporary files during rendering and normally deletes them afterward, but errors can leave orphaned files. Your source views in application/views/ are never touched - only temporary compiled files are removed. Safe to run anytime without data loss.

Clear compiled view templates:

```bash
php roline cleanup:views
```

Output:
```
What will be cleared:
  - vault/tmp/ (Compiled view templates)

Note: This only clears compiled views, not your source views.

Are you sure you want to clear compiled views? (y/n): y

Clearing compiled views...
Cleared: Compiled view templates

Compiled views cleared successfully!
```

Views will automatically recompile on next request. Source files in application/views/ remain untouched.

### cleanup:logs

Truncates error log files to zero bytes without deleting them. Use this when your log files have grown large and are slowing down debugging or consuming excessive disk space. Shows current log file sizes before clearing to help you decide if clearing is needed. Logs are truncated (emptied), not deleted, so logging continues to work normally after clearing. Consider backing up important logs before running if you need historical error data.

Clear error logs:

```bash
php roline cleanup:logs
```

Output:
```
What will be cleared:
  - vault/logs/error.log (Application error log) - 245.67 MB

Are you sure you want to clear log files? (y/n): y

Clearing log files...
Cleared: Application error log

Log files cleared successfully!
```

Truncation is permanent - log content cannot be recovered. Log files continue to work and accept new entries after clearing.

### cleanup:sessions

Clears all PHP session files from vault/sessions/ directory. Use this during scheduled maintenance windows or after security incidents requiring session invalidation. This is destructive - ALL users will be logged out immediately and lose shopping carts, form data, and session state. Never run during active business hours. Shows warning about logging out all users and requires explicit confirmation before executing.

Clear all session files:

```bash
php roline cleanup:sessions
```

Output:
```
What will be cleared:
  - vault/sessions/ (Session files)

WARNING: This will log out ALL active users!

Are you sure you want to clear all sessions? (y/n): y

Clearing sessions...
Cleared: Session files

Sessions cleared successfully!
All users have been logged out.
```

Users must log in again to continue using the application. Use when session storage is consuming excessive disk space or during maintenance.

### cleanup:all

Runs all cleanup operations in sequence: cache, compiled views, logs, and sessions. Use this for comprehensive cleanup during scheduled maintenance windows or after major deployments. Shows complete list of what will be cleared across all categories before execution. ALL users will be logged out when sessions are cleared. Shows log file sizes before truncation. Reports progress for each cleanup step and provides summary of successes/failures at the end.

Run all cleanup operations:

```bash
php roline cleanup:all
```

Output:
```
Cleanup All - Complete System Cleanup

The following will be cleared:

Cache:
  - vault/cache/ (Application cache)

Compiled Views:
  - vault/tmp/ (Compiled view templates)

Log Files:
  - vault/logs/error.log (Application error log) - 245.67 MB

Sessions:
  - vault/sessions/ (Session files)

WARNING: This will log out ALL active users!

Are you sure you want to run ALL cleanup operations? (y/n): y

Running all cleanup operations...

1. Clearing cache...
  Cleared: Application cache

2. Clearing compiled views...
  Cleared: Compiled view templates

3. Clearing log files...
  Cleared: Application error log

4. Clearing sessions...
  Cleared: Session files

All cleanup operations completed successfully!
Total items cleared: 4

All users have been logged out.
```

Useful for fresh starts, troubleshooting caching issues, or reclaiming disk space. Never run during active business hours.

---

## Part 9: Utility Commands

Utility commands provide information about Roline itself - listing available commands, showing help, and displaying version information. These are reference commands you'll use when learning Roline or when you need quick reminders about command syntax.

Use these when you're getting started with Roline, when you forget command names, or when you need version information for bug reports or compatibility checking.

### list

Lists all available Roline commands organized by category with usage syntax and descriptions. Use this when you want to see what commands are available or when you forget a command name. This is the default command - running `php roline` without arguments shows the list. Commands are grouped into categories (Controller, Model, View, Table, Migration, Database, Cache, Utility) with color-coded usage syntax showing required and optional arguments. Shows usage examples at the bottom.

List all commands:

```bash
php roline list
```

Or simply:

```bash
php roline
```

Output:
```
Roline - Rachie Command Line Interface

Controller Commands:
  controller:create <name|required>               Create a new controller
  controller:append <name|required>               Add method to controller
  controller:delete <name|required>               Delete controller file
  controller:complete <name|required>             Create controller, model, and views together

Model Commands:
  model:create <name|required>                    Create a new model
  model:append <name|required>                    Add properties to model
  model:delete <name|required>                    Delete model file
  model:table-create <name|required>              Create database table from model
  model:table-update <name|required>              Update table from model changes
  model:table-drop <name|required>                Drop table associated with model
  model:table-rename <name|required> <new>        Rename model's table
  model:table-schema <name|required>              Show model's table structure
  model:table-empty <name|required>               Delete all rows (preserves auto-increment)
  model:table-reset <name|required>               Reset table (TRUNCATE, resets auto-increment)
  model:table-export <name|required>              Export table to SQL file

View Commands:
  view:create <view|required>                     Create a complete view structure with templates
  view:add <view|required> <name|required>        Add single view file to existing view
  view:delete <view|required>                     Delete entire view directory

Table Commands:
  table:create <name|required>                    Create database table directly
  table:copy <source> <destination>               Copy table structure and data
  table:delete <name|required>                    Drop database table
  table:rename <old> <new>                        Rename database table
  table:schema <name|required>                    Show table structure
  table:empty <name|required>                     Delete all rows (preserves auto-increment)
  table:reset <name|required>                     Reset table (TRUNCATE, resets auto-increment)
  table:export <name|required>                    Export table to SQL
  table:partition <table> <type> <count>          Add partitioning to table
  table:unpartition <table>                       Remove table partitioning

Migration Commands:
  migration:make <name|required>                  Create a new migration file
  migration:run                                   Run pending migrations
  migration:rollback <steps|optional>             Rollback last migration batch
  migration:status                                Show migration status (ran and pending)

Database Commands:
  db:list                                         List all databases with table counts
  db:tables [database]                            List all tables with row counts
  db:create [database] [--if-not-exists]          Create the database
  db:drop [database]                              Drop the entire database
  db:table-drops                                  Drop all database tables (keeps database)
  db:reset                                        Empty all tables (TRUNCATE, resets auto-increment)
  db:schema                                       Show full database schema
  db:seed <seeder|optional>                       Run database seeders
  db:export <file|optional>                       Export entire database
  db:import <file|required>                       Import SQL dump file into database

Cache Commands:
  cleanup:cache                                   Clear application cache
  cleanup:views                                   Clear compiled view temporary files
  cleanup:logs                                    Clear error log files
  cleanup:sessions                                Clear old session files
  cleanup:all                                     Run all cleanup operations (cache, views, logs, sessions)

Utility Commands:
  list                                            List all available commands
  help                                            Show help information
  version                                         Show version information

Usage:
  php roline <command> [arguments]
  php roline <command> --help

Examples:
  php roline controller:create Posts
  php roline model:create Post
  php roline table:create posts
```

Arguments in angle brackets with color coding indicate required (`<name|required>`) or optional (`[database]`) parameters. Run any command with `--help` flag for detailed help.

**Example using --help flag:**

```bash
php roline model:create --help
```

Output:
```
Roline - Rachie Command Line Interface

model:create <name|required>

Create a new model

Creates a new model class file in application/models/ with schema definition
using @column annotations. Models extend Rackage\Model and include static
methods for database operations.

Arguments:
  <name|required>  Model name (singular, PascalCase, e.g., Post, User)

Examples:
  php roline model:create Post
  php roline model:create User

Creates:
  application/models/PostModel.php
  application/models/UserModel.php

Generated Model:
  - Namespace: Models\
  - Extends: Rackage\Model
  - Property: protected static $table (auto-pluralized)
  - Property: protected static $columns = [] (for schema)
  - Property examples with @column annotations
  - Docblock with metadata

Documentation: https://rachie.dev/docs/models

Note: Models are starting templates - add your business logic methods,
relationships, validation, and custom queries specific to your application.
```

### help

Shows quick help information with usage syntax and common examples. Use this when you need a brief reminder of how to use Roline commands without seeing the full command list. Points you to `php roline list` for the complete list of available commands. This is a lightweight alternative to the list command when you just need basic usage information.

Show help:

```bash
php roline help
```

Output:
```
Roline - Rachie Command Line Interface

Usage:
  php roline <command> [arguments]

Examples:
  php roline controller:create Posts
  php roline model:create Post
  php roline table:create Posts
  php roline cache:clear

Run "php roline list" to see all available commands.
```

For detailed help on specific commands, use `php roline <command> --help`.

### version

Displays version information for Roline CLI and Rachie Framework. Use this when reporting bugs, checking compatibility, or verifying your installation. Helpful for support requests and ensuring you're running the expected versions. Shows both Roline CLI version and Rachie Framework version on separate lines.

Show version:

```bash
php roline version
```

Output:
```
Roline CLI v1.0.0
Rachie Framework v2.0
```

Include this information when reporting issues or asking for help with Roline commands.

---

## Part 10: Model Annotations Reference

Model annotations define your database schema using docblock comments on model properties. Each property with `@column` becomes a database column. Annotations control data types, constraints, indexes, and relationships. The ModelParser reads these annotations and generates SQL statements for table creation and updates.

**Critical Rule:** Column properties must be **non-static** instance properties. Static properties (like `$table`, `$timestamps`) are skipped because they're class-level configuration, not record data.

```php
// ❌ WRONG - Will be ignored by parser
protected static $username;

// ✅ CORRECT - Will create column
protected $username;
```

**Formatting Rule: One Annotation Per Line**

Always write each annotation on its own line within the docblock. Never compress multiple annotations onto a single line.

```php
// ❌ WRONG - Terrible visual experience, hard to scan/edit
/** @column @varchar 255 @unique @index @comment "User email" */
protected $email;

// ✅ CORRECT - Each annotation on its own line
/**
 * User email address for login
 * @column
 * @varchar 255
 * @unique
 * @index
 * @comment "User email"
 */
protected $email;
```

**Why this matters:**
- Easier to scan and understand at a glance
- Simpler to add/remove/modify individual annotations
- Better for code reviews and diffs
- Standard PHP docblock convention
- Maintains visual consistency across your models

This formatting applies to ALL annotations including constraints (`@nullable`, `@unsigned`), indexes (`@index`, `@fulltext`), relationships (`@foreign`, `@ondelete`), and modifiers (`@rename`, `@drop`).

**Documentation Rule: Always Include a Description**

Every property docblock must start with a human-readable description before the annotations. This explains what the column represents in business terms.

```php
// ❌ WRONG - No description, just annotations
/**
 * @column
 * @varchar 255
 * @unique
 */
protected $email;

// ✅ CORRECT - Description explains the column's purpose
/**
 * User email address for login and notifications
 * @column
 * @varchar 255
 * @unique
 */
protected $email;

// ✅ ALSO GOOD - More detailed description
/**
 * Order status in the fulfillment pipeline
 * @column
 * @enum pending,processing,shipped,delivered,cancelled
 * @default pending
 * @index
 */
protected $status;
```

**Why this matters:**
- Property names can be ambiguous (`$status` - status of what? order? payment? user?)
- Provides business context that annotations alone cannot convey
- Shows in IDE tooltips when hovering over the property
- Standard PHPDoc convention - description comes before tags
- Helps other developers (and AI tools) understand intent
- Makes code self-documenting without needing external wiki/docs

### Quick Reference

| | | | | |
|----------|----------|----------|----------|----------|
| **Numeric** | **String** | **Date/Time** | **Special** | **Binary** |
| [@tinyint](#tinyint) | [@char](#char) | [@date](#date) | [@boolean](#boolean) | [@blob](#blob) |
| [@smallint](#smallint) | [@varchar](#varchar) | [@time](#time) | [@enum](#enum) | [@mediumblob](#mediumblob) |
| [@mediumint](#mediumint) | [@text](#text) | [@year](#year) | [@set](#set) | [@longblob](#longblob) |
| [@int](#int) | [@mediumtext](#mediumtext) | [@datetime](#datetime) | [@json](#json) | **Spatial** |
| [@bigint](#bigint) | [@longtext](#longtext) | [@timestamp](#timestamp) | [@autonumber](#autonumber) | [@point](#point) |
| [@decimal](#decimal) | **Modifiers** | **Constraints** | [@uuid](#uuid) | [@linestring](#linestring) |
| [@float](#float) | [@comment](#comment) | [@primary](#primary) | **Indexes** | [@polygon](#polygon) |
| [@double](#double) | [@tablecomment](#tablecomment) | [@unique](#unique) | [@index](#index) | [@geometry](#geometry) |
| | [@after](#after) | [@nullable](#nullable) | [@fulltext](#fulltext) | **Relationships** |
| | [@first](#first) | [@unsigned](#unsigned) | [@composite](#composite) | [@foreign](#foreign) |
| | [@drop](#drop) | [@default](#default) | [@compositeUnique](#compositeUnique) | [@ondelete](#ondelete) |
| | [@rename](#rename) | [@check](#check) | [@partition](#partition) | [@onupdate](#onupdate) |

### Numeric Types

#### @tinyint

**Syntax:** `@tinyint` or `@tinyint length`

**Example:**

```php
/**
 * User age (0-255 range is sufficient)
 * @column
 * @tinyint
 * @unsigned
 */
protected $age;
```

**Resulting SQL:** `age TINYINT(4) UNSIGNED NOT NULL`

**When to use:** Small whole numbers, flags, status codes, small counters. Range: -128 to 127 (or 0 to 255 if unsigned). Perfect for boolean-like values, single-digit numbers, percentages.

**Notes:**
- Default length is 4 if not specified
- Only 1 byte of storage (smallest integer type)
- For true boolean flags, use `@boolean` annotation instead (which internally uses TINYINT(1))

#### @smallint

**Syntax:** `@smallint` or `@smallint length`

**Example:**

```php
/**
 * Product inventory count (larger than tinyint range)
 * @column
 * @smallint
 * @unsigned
 * @default 0
 */
protected $stock_quantity;
```

**Resulting SQL:** `stock_quantity SMALLINT(6) UNSIGNED NOT NULL DEFAULT 0`

**When to use:** Medium-range whole numbers. Range: -32,768 to 32,767 (or 0 to 65,535 if unsigned). Good for inventory counts, port numbers, moderate counters.

**Notes:**
- Default length is 6 if not specified
- Takes 2 bytes of storage
- Sweet spot between TINYINT (too small) and INT (unnecessary overhead)

#### @mediumint

**Syntax:** `@mediumint` or `@mediumint length`

**Example:**

```php
/**
 * Daily page views (millions of views)
 * @column
 * @mediumint
 * @unsigned
 * @default 0
 */
protected $daily_views;
```

**Resulting SQL:** `daily_views MEDIUMINT(9) UNSIGNED NOT NULL DEFAULT 0`

**When to use:** Large counters that don't need full INT range. Range: -8,388,608 to 8,388,607 (or 0 to 16,777,215 if unsigned). Good for high-volume counters, moderately large datasets.

**Notes:**
- Default length is 9 if not specified
- Takes 3 bytes of storage (vs 4 bytes for INT)
- Saves 25% storage compared to INT when you don't need the full range

#### @int

**Syntax:** `@int` or `@int length`

**Example:**

```php
/**
 * Product quantity in stock
 * @column
 * @int
 * @unsigned
 * @default 0
 */
protected $quantity;
```

**Resulting SQL:** `quantity INT(11) UNSIGNED NOT NULL DEFAULT 0`

**When to use:** Whole numbers like counts, quantities, IDs, ages, years. Range: -2,147,483,648 to 2,147,483,647 (or 0 to 4,294,967,295 if unsigned). Most common integer type.

**Notes:**
- Default length is 11 if not specified
- Takes 4 bytes of storage
- Use `@unsigned` for positive-only values (doubles the upper range)
- For auto-incrementing IDs, use `@autonumber` instead (automatically sets type to INT, adds UNSIGNED, AUTO_INCREMENT, and PRIMARY KEY)

#### @bigint

**Syntax:** `@bigint` or `@bigint length`

**Example:**

```php
/**
 * Total views counter (can grow very large)
 * @column
 * @bigint
 * @unsigned
 * @default 0
 */
protected $total_views;
```

**Resulting SQL:** `total_views BIGINT(20) UNSIGNED NOT NULL DEFAULT 0`

**When to use:** Very large whole numbers that exceed INT range. Common for counters, timestamps as integers, large IDs. Range: -9,223,372,036,854,775,808 to 9,223,372,036,854,775,807 (or 0 to 18,446,744,073,709,551,615 if unsigned).

**Notes:**
- Default length is 20 if not specified
- Takes 8 bytes of storage (vs 4 bytes for INT)
- Use when you expect values to exceed 4 billion (unsigned INT limit)

#### @decimal

**Syntax:** `@decimal precision,scale`

**Example:**

```php
/**
 * Product price with cents
 * @column
 * @decimal 10,2
 * @unsigned
 * @default 0.00
 */
protected $price;
```

**Resulting SQL:** `price DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0.00`

**When to use:** Exact decimal values where precision matters - prices, monetary amounts, percentages, coordinates. Never use FLOAT/DOUBLE for money due to rounding errors.

**Notes:**
- **Precision** (first number): Total digits (before + after decimal point)
- **Scale** (second number): Digits after decimal point
- Example: DECIMAL(10,2) stores up to 99999999.99
- Default is DECIMAL(10,2) if not specified
- REQUIRED format: `@decimal 10,2` (with comma, no spaces in some parsers)
- Always use DECIMAL for money, never FLOAT or DOUBLE

#### @float

**Syntax:** `@float`

**Example:**

```php
/**
 * Temperature reading with decimal precision
 * @column
 * @float
 */
protected $temperature;
```

**Resulting SQL:** `temperature FLOAT NOT NULL`

**When to use:** Approximate decimal numbers where slight precision loss is acceptable - scientific measurements, coordinates (when high precision not critical), calculated values. Faster than DECIMAL for math operations.

**Notes:**
- Takes 4 bytes of storage
- Approximate values (can have rounding errors)
- Precision: ~7 decimal digits
- **Never use for money** - use @decimal instead
- Good for large datasets where storage/speed matters more than exact precision

#### @double

**Syntax:** `@double`

**Example:**

```php
/**
 * Geographic coordinate (latitude/longitude)
 * @column
 * @double
 */
protected $latitude;
```

**Resulting SQL:** `latitude DOUBLE NOT NULL`

**When to use:** High-precision approximate decimal numbers - geographic coordinates, scientific calculations, statistical data. More precise than FLOAT when you need 15+ decimal digits.

**Notes:**
- Takes 8 bytes of storage (double the size of FLOAT)
- Approximate values (can have rounding errors but less than FLOAT)
- Precision: ~15 decimal digits
- **Never use for money** - use @decimal instead
- Use when FLOAT precision isn't enough but DECIMAL's exactness isn't required

### String Types

#### @char

**Syntax:** `@char` or `@char length`

**Example:**

```php
/**
 * Two-letter country code (fixed length)
 * @column
 * @char 2
 * @default US
 */
protected $country_code;
```

**Resulting SQL:** `country_code CHAR(2) NOT NULL DEFAULT 'US'`

**When to use:** Fixed-length strings where every value has exactly the same number of characters - country codes, state abbreviations, fixed codes, checksums. CHAR pads shorter values with spaces to reach the defined length.

**Notes:**
- Default length is 255 if not specified
- Takes exactly N bytes of storage (where N is the length)
- Faster than VARCHAR for fixed-length data because MySQL doesn't need to store the length
- Maximum length: 255 characters
- Avoid for variable-length data - wastes space with padding

#### @varchar

**Syntax:** `@varchar length`

**Example:**

```php
/**
 * User's email address
 * @column
 * @varchar 255
 * @unique
 */
protected $email;
```

**Resulting SQL:** `email VARCHAR(255) NOT NULL UNIQUE`

**When to use:** Variable-length strings up to 255 characters - names, emails, titles, URLs, usernames. Most common string type for short to medium text. Only uses the storage needed for the actual content plus 1-2 bytes for length.

**Notes:**
- Default length is 255 if not specified
- Takes actual string length + 1 byte (if length ≤ 255) or + 2 bytes (if length > 255) for storage
- Maximum length: 65,535 characters (but use TEXT types for content over 255)
- More efficient than CHAR for variable-length data
- Can be indexed and searched efficiently

#### @text

**Syntax:** `@text`

**Example:**

```php
/**
 * Article content or long description
 * @column
 * @text
 * @nullable
 */
protected $description;
```

**Resulting SQL:** `description TEXT NULL`

**When to use:** Long text content that exceeds VARCHAR's practical limit - articles, descriptions, comments, formatted content. Ideal for content between 256 and 65,535 characters (64 KB).

**Notes:**
- No length parameter needed (fixed at 64 KB maximum)
- Takes actual string length + 2 bytes for storage
- Cannot have a default value in MySQL
- Can be indexed with prefix length: `@index` with manual prefix specification
- Use @fulltext for full-text search on TEXT columns

#### @mediumtext

**Syntax:** `@mediumtext`

**Example:**

```php
/**
 * Large article body with rich content
 * @column
 * @mediumtext
 */
protected $article_body;
```

**Resulting SQL:** `article_body MEDIUMTEXT NOT NULL`

**When to use:** Very large text content - full articles, documentation, serialized data, large HTML content. Holds up to 16 MB of text, suitable for content too large for TEXT but not requiring LONGTEXT's massive capacity.

**Notes:**
- Maximum size: 16,777,215 characters (16 MB)
- Takes actual string length + 3 bytes for storage
- Cannot have a default value in MySQL
- Use when TEXT's 64 KB limit is too small
- Good middle ground between TEXT and LONGTEXT

#### @longtext

**Syntax:** `@longtext`

**Example:**

```php
/**
 * Complete book content or massive data dump
 * @column
 * @longtext
 */
protected $full_content;
```

**Resulting SQL:** `full_content LONGTEXT NOT NULL`

**When to use:** Extremely large text content - complete books, extensive logs, large JSON/XML documents, comprehensive data exports. Holds up to 4 GB of text. Use sparingly as it impacts performance.

**Notes:**
- Maximum size: 4,294,967,295 characters (4 GB)
- Takes actual string length + 4 bytes for storage
- Cannot have a default value in MySQL
- Significantly impacts query performance when selecting this column
- Consider storing large content in files instead and keeping only file paths in the database
- Use only when you genuinely need to store massive text data

---

### Date/Time Types

Date and time types store temporal data like dates, times, timestamps, and years. Choose the appropriate type based on what precision you need and how you'll query the data.

#### @date

**Syntax:** `@date`

**Example:**

```php
/**
 * User's birth date
 * @column
 * @date
 * @nullable
 */
protected $birth_date;
```

**Resulting SQL:** `birth_date DATE NULL`

**When to use:** Birth dates, anniversaries, deadlines, or any date-only data where you don't need time-of-day precision.

**Notes:**
- Stores dates in `YYYY-MM-DD` format (e.g., `2025-12-31`)
- Range: `1000-01-01` to `9999-12-31`
- Storage: 3 bytes
- No timezone information stored
- Use @datetime if you also need to track the time

#### @time

**Syntax:** `@time`

**Example:**

```php
/**
 * Store opening time
 * @column
 * @time
 * @default 09:00:00
 */
protected $opening_time;
```

**Resulting SQL:** `opening_time TIME NOT NULL DEFAULT '09:00:00'`

**When to use:** Business hours, recurring daily events, duration tracking, or when you only care about time-of-day.

**Notes:**
- Stores time in `HH:MM:SS` format (e.g., `14:30:00`)
- Range: `-838:59:59` to `838:59:59` (can represent durations up to ~35 days)
- Storage: 3 bytes
- Can store negative values for representing time differences
- Use @datetime if you need both date and time

#### @year

**Syntax:** `@year`

**Example:**

```php
/**
 * Year the vehicle was manufactured
 * @column
 * @year
 * @nullable
 */
protected $manufacture_year;
```

**Resulting SQL:** `manufacture_year YEAR NULL`

**When to use:** Graduation years, manufacture years, publication years, or any data where only the year matters.

**Notes:**
- Stores years as 4-digit values (e.g., `2025`)
- Range: `1901` to `2155`
- Storage: 1 byte (very efficient)
- MySQL deprecated 2-digit YEAR format, only 4-digit supported
- Use @date if you need month/day precision

#### @datetime

**Syntax:** `@datetime`

**Example:**

```php
/**
 * When the article was published
 * @column
 * @datetime
 * @nullable
 */
protected $published_at;
```

**Resulting SQL:** `published_at DATETIME NULL`

**When to use:** Timestamps for created_at/updated_at fields, scheduled publication times, or event start/end times.

**Notes:**
- Stores in `YYYY-MM-DD HH:MM:SS` format (e.g., `2025-01-08 14:30:00`)
- Range: `1000-01-01 00:00:00` to `9999-12-31 23:59:59`
- Storage: 5 bytes, no timezone information stored
- Does not auto-update on record changes
- Use @timestamp if you need auto-updating behavior

#### @timestamp

**Syntax:** `@timestamp`

**Example:**

```php
/**
 * Timestamp when record was created
 * @column
 * @timestamp
 * @default CURRENT_TIMESTAMP
 */
protected $created_at;
```

**Resulting SQL:** `created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP`

**When to use:** Record creation timestamps, last modification timestamps, audit trails, or when you need automatic timestamp behavior.

**Notes:**
- Range: `1970-01-01 00:00:01` UTC to `2038-01-19 03:14:07` UTC (32-bit limit)
- Storage: 4 bytes
- Can use `@default CURRENT_TIMESTAMP` for automatic insertion time
- Timezone-aware: MySQL converts between server timezone and UTC for storage
- Use @datetime if you need dates before 1970 or after 2038

---

### Binary Types

Binary types store raw binary data like images, PDFs, files, or encrypted content. Choose based on the maximum file size you need to store.

#### @blob

**Syntax:** `@blob`

**Example:**

```php
/**
 * User profile picture
 * @column
 * @blob
 * @nullable
 */
protected $profile_image;
```

**Resulting SQL:** `profile_image BLOB NULL`

**When to use:** Small binary files like thumbnails, icons, small images, or binary data under 64 KB.

**Notes:**
- Storage: Up to 64 KB (65,535 bytes)
- Cannot have a default value
- Consider storing files on disk and keeping file paths in the database instead
- Indexes are not supported on BLOB columns
- Can significantly impact query performance

#### @mediumblob

**Syntax:** `@mediumblob`

**Example:**

```php
/**
 * PDF document attachment
 * @column
 * @mediumblob
 * @nullable
 */
protected $document;
```

**Resulting SQL:** `document MEDIUMBLOB NULL`

**When to use:** Medium-sized files like documents, high-res images, or binary data between 64 KB and 16 MB.

**Notes:**
- Storage: Up to 16 MB (16,777,215 bytes)
- Cannot have a default value
- Better to store files externally for files larger than a few MB
- Not indexable
- Loading this column loads the entire binary content

#### @longblob

**Syntax:** `@longblob`

**Example:**

```php
/**
 * Video file or large binary data
 * @column
 * @longblob
 * @nullable
 */
protected $video_file;
```

**Resulting SQL:** `video_file LONGBLOB NULL`

**When to use:** Large binary files up to 4 GB - videos, large documents, backups, or archives.

**Notes:**
- Storage: Up to 4 GB (4,294,967,295 bytes)
- Cannot have a default value
- Storing large files in the database is generally not recommended
- Use external file storage (disk, S3, CDN) for better performance
- Significantly impacts database backup and query performance

---

### Spatial Types

Spatial types store geometric and geographic data for mapping, GIS applications, and location-based features. Requires MySQL spatial extension support.

#### @point

**Syntax:** `@point`

**Example:**

```php
/**
 * Store or restaurant location
 * @column
 * @point
 * @nullable
 */
protected $location;
```

**Resulting SQL:** `location POINT NULL`

**When to use:** Single coordinate points representing locations on a map - store locations, pins, markers, or GPS coordinates.

**Notes:**
- Stores a single X,Y coordinate pair (latitude/longitude)
- Can use spatial indexes for fast proximity searches
- Use functions like `ST_GeomFromText()` to insert: `POINT(40.7128 -74.0060)`
- Query with spatial functions like `ST_Distance()` for distance calculations
- Requires MySQL spatial extension enabled

#### @linestring

**Syntax:** `@linestring`

**Example:**

```php
/**
 * Delivery route or path
 * @column
 * @linestring
 * @nullable
 */
protected $route;
```

**Resulting SQL:** `route LINESTRING NULL`

**When to use:** Paths, routes, roads, or any connected sequence of points - delivery routes, hiking trails, road segments.

**Notes:**
- Stores an ordered sequence of points forming a line
- Useful for representing paths, routes, or boundaries
- Insert with `ST_GeomFromText('LINESTRING(0 0, 1 1, 2 2)')`
- Can calculate length with `ST_Length()` function
- Supports spatial indexing for efficient queries

#### @polygon

**Syntax:** `@polygon`

**Example:**

```php
/**
 * Delivery coverage area
 * @column
 * @polygon
 * @nullable
 */
protected $coverage_area;
```

**Resulting SQL:** `coverage_area POLYGON NULL`

**When to use:** Areas, boundaries, regions, or zones - delivery zones, property boundaries, service areas, geofences.

**Notes:**
- Stores a closed shape defined by connected points
- Use for area-based queries like "does this point fall within this zone?"
- Insert with `ST_GeomFromText('POLYGON((0 0, 4 0, 4 4, 0 4, 0 0))')`
- Query with `ST_Contains()` or `ST_Within()` for containment checks
- First and last points must be identical to close the shape

#### @geometry

**Syntax:** `@geometry`

**Example:**

```php
/**
 * Generic spatial data
 * @column
 * @geometry
 * @nullable
 */
protected $geo_data;
```

**Resulting SQL:** `geo_data GEOMETRY NULL`

**When to use:** Generic spatial column that can store any geometry type - points, lines, polygons, or mixed spatial data.

**Notes:**
- Most flexible spatial type, can store any geometry
- Use when you need to store different geometry types in the same column
- Slightly less efficient than specific types for specialized queries
- Supports all spatial functions and indexes
- Useful for complex or mixed geographic data

---

### Special Types

Special types provide convenience features or handle specific data formats that don't fit into standard categories.

#### @boolean / @bool

**Syntax:** `@boolean` or `@bool`

**Example:**

```php
/**
 * Is the user account active
 * @column
 * @boolean
 */
protected $is_active;
```

**Resulting SQL:** `is_active TINYINT(1) NOT NULL DEFAULT 0`

**When to use:** True/false flags, yes/no values, active/inactive status, or any binary state.

**Notes:**
- Internally stored as TINYINT(1) with values 0 (false) or 1 (true)
- Automatically defaults to 0 if no @default specified
- Both @boolean and @bool work identically
- More readable than using TINYINT directly for boolean logic
- Use with conditional checks: `if ($user->is_active) { ... }`

#### @enum

**Syntax:** `@enum value1,value2,value3`

**Example:**

```php
/**
 * User account status
 * @column
 * @enum active,inactive,suspended,banned
 * @default active
 */
protected $status;
```

**Resulting SQL:** `status ENUM('active','inactive','suspended','banned') NOT NULL DEFAULT 'active'`

**When to use:** Fixed set of string options where a record can have exactly one value - status fields, categories, types, priorities.

**Notes:**
- Values must be comma-separated with no spaces around commas
- Can only store one of the defined values
- More storage-efficient than VARCHAR for fixed options (stored as integers internally)
- MySQL validates values automatically
- Maximum 65,535 distinct values allowed

#### @set

**Syntax:** `@set value1,value2,value3`

**Example:**

```php
/**
 * User permissions (can have multiple)
 * @column
 * @set read,write,delete,admin
 * @nullable
 */
protected $permissions;
```

**Resulting SQL:** `permissions SET('read','write','delete','admin') NULL`

**When to use:** Multiple selection from a fixed set of options - permissions, features, tags, flags that can be combined.

**Notes:**
- Similar to ENUM but allows storing multiple values at once
- Values stored as comma-separated: `'read,write'` or `'read,write,admin'`
- More efficient than creating junction tables for simple multi-selects
- Maximum 64 distinct members allowed
- Use `FIND_IN_SET()` function to query specific values

#### @json

**Syntax:** `@json`

**Example:**

```php
/**
 * User settings or metadata
 * @column
 * @json
 * @nullable
 */
protected $settings;
```

**Resulting SQL:** `settings JSON NULL`

**When to use:** Flexible schema data, configuration objects, metadata, nested structures, or API responses.

**Notes:**
- Stores and validates JSON documents
- MySQL automatically validates JSON format on insert/update
- Can query nested fields with JSON functions: `JSON_EXTRACT()`, `->` operator
- More flexible than fixed columns for evolving data structures
- Requires MySQL 5.7.8 or later

#### @autonumber

**Syntax:** `@autonumber`

**Example:**

```php
/**
 * Auto-incrementing primary key
 * @column
 * @autonumber
 */
protected $id;
```

**Resulting SQL:** `id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY`

**When to use:** Primary keys for most tables - automatically generates unique sequential IDs.

**Notes:**
- Automatically sets: INT(11), UNSIGNED, AUTO_INCREMENT, PRIMARY KEY
- You don't need to add @primary, @unsigned, or @int manually
- Database generates the value automatically on insert
- Most common way to create primary keys
- Use @uuid if you need globally unique non-sequential keys

#### @uuid

**Syntax:** `@uuid`

**Example:**

```php
/**
 * Globally unique identifier
 * @column
 * @uuid
 */
protected $id;
```

**Resulting SQL:** `id CHAR(36) NOT NULL PRIMARY KEY`

**When to use:** Primary keys when you need distributed systems compatibility, globally unique IDs, or want to avoid sequential numbering.

**Notes:**
- Automatically sets: CHAR(36), PRIMARY KEY
- Format: `550e8400-e29b-41d4-a716-446655440000` (36 characters with dashes)
- You must generate the UUID in your application code before inserting
- Use PHP's `uniqid()` or `Ramsey\Uuid` library to generate values
- Larger storage and slower indexing than @autonumber

---

### Modifiers

Modifiers add metadata, control column positioning, or manage schema changes during migrations.

#### @comment

**Syntax:** `@comment "your comment text"`

**Example:**

```php
/**
 * User's email address
 * @column
 * @varchar 255
 * @unique
 * @comment "Used for login and notifications"
 */
protected $email;
```

**Resulting SQL:** `email VARCHAR(255) NOT NULL UNIQUE COMMENT 'Used for login and notifications'`

**When to use:** Add documentation directly to your database schema - explain business logic, data sources, validation rules, or special considerations.

**Notes:**
- Quotes around the comment text are optional but recommended
- Comment appears in database tools and SHOW CREATE TABLE output
- Helps other developers understand column purpose
- Visible in database administration tools
- Maximum length varies by MySQL version (typically 1024 characters)

#### @tablecomment

**Syntax:** `@tablecomment "your table description"` (in class docblock)

**Example:**

```php
/**
 * User Model
 *
 * @tablecomment "Stores user account data and authentication credentials"
 */
class UserModel extends Model
{
    // ... properties
}
```

**Resulting SQL:** `CREATE TABLE users (...) COMMENT='Stores user account data and authentication credentials'`

**When to use:** Document the purpose of entire tables in your database schema.

**Notes:**
- Applied to class docblock, not property docblock
- Appears in SHOW CREATE TABLE and database documentation
- Helps document overall table purpose
- Visible in database administration tools
- Different from column comments - this is table-level

#### @after

**Syntax:** `@after column_name`

**Example:**

```php
/**
 * Middle name field
 * @column
 * @varchar 100
 * @nullable
 * @after first_name
 */
protected $middle_name;
```

**Resulting SQL:** `ALTER TABLE users ADD COLUMN middle_name VARCHAR(100) NULL AFTER first_name`

**When to use:** Control column ordering when adding new columns to existing tables with `php roline model:update`.

**Notes:**
- Only works with ALTER TABLE operations (model:update command)
- Ignored during initial table creation (model:table-create)
- Places the new column immediately after the specified column
- Useful for keeping related columns together visually
- Cannot be used with @first

#### @first

**Syntax:** `@first`

**Example:**

```php
/**
 * Legacy ID field
 * @column
 * @int
 * @first
 */
protected $legacy_id;
```

**Resulting SQL:** `ALTER TABLE users ADD COLUMN legacy_id INT NOT NULL FIRST`

**When to use:** Position a new column as the first column in the table during ALTER TABLE operations.

**Notes:**
- Only works with ALTER TABLE operations (model:update command)
- Ignored during initial table creation
- Places column at the beginning of the table
- Cannot be used with @after
- Rarely needed - column order doesn't affect functionality

#### @drop

**Syntax:** `@drop`

**Example:**

```php
/**
 * Deprecated field - marking for deletion
 * @column
 * @drop
 */
protected $old_field;
```

**Resulting SQL:** `ALTER TABLE users DROP COLUMN old_field`

**When to use:** Remove columns from existing tables when running `php roline model:update`.

**Notes:**
- Only works with model:update command
- Permanently deletes the column and all its data
- Keep the property in model temporarily just to mark it for deletion
- After running update, you can remove the property entirely from the model
- Cannot be undone - backup data before dropping columns

#### @rename

**Syntax:** `@rename old_column_name`

**Example:**

```php
/**
 * Email address (renamed from email_addr)
 * @column
 * @varchar 255
 * @rename email_addr
 */
protected $email;
```

**Resulting SQL:** `ALTER TABLE users CHANGE COLUMN email_addr email VARCHAR(255) NOT NULL`

**When to use:** Rename existing columns while preserving data when running `php roline model:update`.

**Notes:**
- Only works with model:update command
- Preserves existing data during rename
- The property name is the NEW name, @rename value is the OLD name
- Updates any dependent indexes and foreign keys automatically
- More efficient than drop + recreate

---

### Constraints

Constraints enforce data integrity rules and define column behavior at the database level.

#### @primary

**Syntax:** `@primary`

**Example:**

```php
/**
 * Custom primary key
 * @column
 * @varchar 50
 * @primary
 */
protected $user_code;
```

**Resulting SQL:** `user_code VARCHAR(50) NOT NULL PRIMARY KEY`

**When to use:** Mark a column as the primary key when you need a non-numeric or custom primary key (use @autonumber for standard auto-incrementing IDs).

**Notes:**
- Only one primary key allowed per table
- Primary key columns are automatically indexed
- Cannot contain NULL values (automatically NOT NULL)
- Use @autonumber instead for standard auto-incrementing integer IDs
- Primary keys uniquely identify each record

#### @unique

**Syntax:** `@unique`

**Example:**

```php
/**
 * User's email address
 * @column
 * @varchar 255
 * @unique
 */
protected $email;
```

**Resulting SQL:** `email VARCHAR(255) NOT NULL UNIQUE`

**When to use:** Enforce uniqueness on columns like emails, usernames, slugs, or any value that must be unique across all records.

**Notes:**
- Prevents duplicate values in the column
- Automatically creates an index on the column
- NULL values are allowed (unless you omit @nullable)
- Multiple NULL values are permitted (NULL != NULL in SQL)
- Use for natural unique identifiers

#### @nullable

**Syntax:** `@nullable`

**Example:**

```php
/**
 * User's middle name
 * @column
 * @varchar 100
 * @nullable
 */
protected $middle_name;
```

**Resulting SQL:** `middle_name VARCHAR(100) NULL`

**When to use:** Allow NULL values when a column is optional or the data might not always be available.

**Notes:**
- By default, all columns are NOT NULL without this annotation
- NULL is different from empty string ('')
- Useful for optional fields like middle names, phone numbers, descriptions
- Primary keys cannot be nullable
- Consider whether empty string or NULL better represents "no value"

#### @unsigned

**Syntax:** `@unsigned`

**Example:**

```php
/**
 * Product quantity (never negative)
 * @column
 * @int
 * @unsigned
 * @default 0
 */
protected $quantity;
```

**Resulting SQL:** `quantity INT(11) UNSIGNED NOT NULL DEFAULT 0`

**When to use:** Numeric columns that should never be negative - quantities, counts, ages, prices, IDs.

**Notes:**
- Only works with numeric types: INT, BIGINT, TINYINT, SMALLINT, MEDIUMINT, DECIMAL, FLOAT, DOUBLE
- Doubles the positive range by removing negative values
- INT UNSIGNED range: 0 to 4,294,967,295 (vs -2.1B to +2.1B signed)
- Use for any naturally non-negative values
- Attempting to store negative values will cause an error

#### @default

**Syntax:** `@default value`

**Example:**

```php
/**
 * User account status
 * @column
 * @enum active,inactive,suspended
 * @default active
 */
protected $status;
```

**Resulting SQL:** `status ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active'`

**When to use:** Set a default value that's automatically used when no value is provided during insert.

**Notes:**
- String values don't need quotes in the annotation
- Use CURRENT_TIMESTAMP for automatic timestamps: `@default CURRENT_TIMESTAMP`
- NULL is a valid default: columns without @default and without @nullable get no default
- Cannot set defaults on TEXT/BLOB columns in MySQL
- Reduces the need to specify values for every column

#### @check

**Syntax:** `@check expression`

**Example:**

```php
/**
 * Product price (must be positive)
 * @column
 * @decimal 10,2
 * @check price > 0
 */
protected $price;
```

**Resulting SQL:** `price DECIMAL(10,2) NOT NULL CHECK (price > 0)`

**When to use:** Enforce custom validation rules at the database level - price ranges, date constraints, conditional logic.

**Notes:**
- Requires MySQL 8.0.16 or later
- Expression is evaluated on INSERT and UPDATE
- Can reference the column itself or use complex expressions
- Use for business rules that must ALWAYS be true
- Examples: `age >= 18`, `start_date < end_date`, `quantity BETWEEN 0 AND 1000`

---

### Indexes

Indexes improve query performance by creating fast lookup structures. Use for columns frequently used in WHERE, JOIN, or ORDER BY clauses.

#### @index

**Syntax:** `@index` or `@index custom_name`

**Example:**

```php
/**
 * User's last name
 * @column
 * @varchar 100
 * @index
 */
protected $last_name;
```

**Resulting SQL:** `last_name VARCHAR(100) NOT NULL, INDEX last_name_index (last_name)`

**When to use:** Speed up queries that search, filter, or sort by this column.

**Notes:**
- Automatically creates an index named `{column}_index`
- Can optionally specify a custom index name: `@index idx_lastname`
- Improves SELECT performance but slows down INSERT/UPDATE slightly
- Use on foreign keys, frequently searched columns, or ORDER BY columns
- Primary keys and unique columns are automatically indexed

#### @fulltext

**Syntax:** `@fulltext`

**Example:**

```php
/**
 * Article content for search
 * @column
 * @text
 * @fulltext
 */
protected $content;
```

**Resulting SQL:** `content TEXT NOT NULL, FULLTEXT fulltext_content (content)`

**When to use:** Enable natural language search on TEXT or VARCHAR columns - article content, descriptions, comments.

**Notes:**
- Only works with TEXT and VARCHAR columns
- Enables MATCH() AGAINST() full-text search queries
- Better than LIKE '%keyword%' for searching large text content
- Requires MyISAM or InnoDB storage engine (InnoDB default in MySQL 5.6+)
- Indexes entire words, ignores common stop words

#### @composite

**Syntax:** `@composite (col1, col2, col3)` or `@composite idx_name (col1, col2, col3)` (in class docblock)

**Example:**

```php
/**
 * User Model
 *
 * @composite (last_name, first_name)
 * @composite idx_city_state (city, state)
 */
class UserModel extends Model
{
    // ... properties
}
```

**Resulting SQL:** `INDEX idx_last_name_first_name (last_name, first_name), INDEX idx_city_state (city, state)`

**When to use:** Index multiple columns together when queries frequently filter or sort by them in combination.

**Notes:**
- Applied to class docblock, not property docblock
- Column order matters - leftmost columns are most important
- Query benefits only if it uses the leftmost columns first
- Auto-generates name `idx_{col1}_{col2}` if no custom name provided
- More efficient than multiple single-column indexes for combined queries

#### @compositeUnique

**Syntax:** `@compositeUnique (col1, col2)` or `@compositeUnique unq_name (col1, col2)` (in class docblock)

**Example:**

```php
/**
 * Product Model
 *
 * @compositeUnique (sku, warehouse_id)
 */
class ProductModel extends Model
{
    // ... properties
}
```

**Resulting SQL:** `UNIQUE INDEX unq_sku_warehouse_id (sku, warehouse_id)`

**When to use:** Enforce uniqueness across multiple columns together - same SKU can exist in different warehouses, but not twice in the same warehouse.

**Notes:**
- Applied to class docblock, not property docblock
- Prevents duplicate combinations of the specified columns
- Auto-generates name `unq_{col1}_{col2}` if no custom name provided
- All columns in the combination must be unique together
- Different from multiple @unique annotations (which make each column individually unique)

#### @partition

**Syntax:** `@partition hash(column) count` or `@partition key(column) count` (in class docblock)

**Example:**

```php
/**
 * Log Model
 *
 * @partition hash(user_id) 16
 */
class LogModel extends Model
{
    // ... properties
}
```

**Resulting SQL:** `CREATE TABLE logs (...) PARTITION BY HASH(user_id) PARTITIONS 16`

**When to use:** Improve performance on very large tables by dividing data into smaller physical partitions.

**Notes:**
- Applied to class docblock, not property docblock
- HASH partitioning distributes data evenly across N partitions
- KEY partitioning uses MySQL's internal hashing function
- Best for tables with millions of records
- Improves query performance when filtering by the partition column

---

### Relationships

Relationships define how tables connect through foreign keys, maintaining referential integrity across your database.

#### @foreign

**Syntax:** `@foreign table(column)`

**Example:**

```php
/**
 * ID of the user who created this post
 * @column
 * @int
 * @unsigned
 * @foreign users(id)
 * @ondelete CASCADE
 */
protected $user_id;
```

**Resulting SQL:** `user_id INT UNSIGNED NOT NULL, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE`

**When to use:** Create relationships between tables - link posts to users, orders to customers, comments to articles.

**Notes:**
- Format is `table(column)` - the table and column this foreign key references
- Referenced column must be a primary key or unique key
- Column types must match exactly (INT to INT, VARCHAR(50) to VARCHAR(50))
- Enforces referential integrity - can't reference non-existent records
- Use with @ondelete and @onupdate to control cascading behavior

#### @ondelete

**Syntax:** `@ondelete ACTION`

**Example:**

```php
/**
 * Order's customer ID
 * @column
 * @int
 * @unsigned
 * @foreign customers(id)
 * @ondelete RESTRICT
 */
protected $customer_id;
```

**Resulting SQL:** `customer_id INT UNSIGNED NOT NULL, FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT`

**When to use:** Control what happens when a referenced record is deleted.

**Notes:**
- CASCADE: Automatically delete this record when the referenced record is deleted
- RESTRICT: Prevent deletion of referenced record if it has dependent records
- SET NULL: Set this column to NULL when referenced record is deleted (requires @nullable)
- NO ACTION: Same as RESTRICT (MySQL default)
- Most common: CASCADE for dependent data, RESTRICT for important references

#### @onupdate

**Syntax:** `@onupdate ACTION`

**Example:**

```php
/**
 * Product category ID
 * @column
 * @int
 * @unsigned
 * @foreign categories(id)
 * @onupdate CASCADE
 */
protected $category_id;
```

**Resulting SQL:** `category_id INT UNSIGNED NOT NULL, FOREIGN KEY (category_id) REFERENCES categories(id) ON UPDATE CASCADE`

**When to use:** Control what happens when a referenced record's primary key is updated.

**Notes:**
- CASCADE: Automatically update this column when the referenced key changes
- RESTRICT: Prevent updates to referenced key if it has dependent records
- SET NULL: Set this column to NULL when referenced key changes (requires @nullable)
- NO ACTION: Same as RESTRICT (MySQL default)
- CASCADE is most common - keeps relationships intact when keys change

---

## Advanced Features

### Full Model Example

Here's a production-ready model showing multiple annotations working together:

```php
<?php namespace Models;

/**
 * User Model
 *
 * Manages user accounts with authentication, roles, and activity tracking.
 * Implements soft deletes and automatic timestamps.
 *
 * @tablecomment "User accounts with authentication and profile data"
 * @composite idx_name (last_name, first_name)
 * @composite idx_location (city, state)
 * @compositeUnique unq_email_deleted (email, deleted_at)
 */

use Rackage\Model;

class UserModel extends Model
{
    protected static $table = 'users';
    protected static $timestamps = true;

    // ==================== PRIMARY KEY ====================

    /**
     * Unique user identifier
     * @column
     * @autonumber
     */
    protected $id;

    // ==================== AUTHENTICATION ====================

    /**
     * User's email address for login
     * @column
     * @varchar 255
     * @unique
     * @comment "Used for authentication and notifications"
     */
    protected $email;

    /**
     * Hashed password
     * @column
     * @varchar 255
     * @comment "Bcrypt hash - never store plain text"
     */
    protected $password;

    /**
     * Password reset token
     * @column
     * @varchar 100
     * @nullable
     * @index
     */
    protected $reset_token;

    /**
     * When reset token expires
     * @column
     * @datetime
     * @nullable
     */
    protected $reset_expires;

    // ==================== PROFILE ====================

    /**
     * User's first name
     * @column
     * @varchar 100
     */
    protected $first_name;

    /**
     * User's last name
     * @column
     * @varchar 100
     */
    protected $last_name;

    /**
     * Phone number with country code
     * @column
     * @varchar 20
     * @nullable
     */
    protected $phone;

    /**
     * City name
     * @column
     * @varchar 100
     * @nullable
     */
    protected $city;

    /**
     * Two-letter state code
     * @column
     * @char 2
     * @nullable
     */
    protected $state;

    /**
     * Birth date for age verification
     * @column
     * @date
     * @nullable
     * @check birth_date < CURDATE()
     */
    protected $birth_date;

    // ==================== ROLES & STATUS ====================

    /**
     * User role level
     * @column
     * @enum user,moderator,admin
     * @default user
     * @index
     */
    protected $role;

    /**
     * Account status
     * @column
     * @enum active,inactive,suspended,banned
     * @default active
     * @index
     */
    protected $status;

    /**
     * User permissions (multi-select)
     * @column
     * @set read,write,delete,manage_users,manage_settings
     * @nullable
     */
    protected $permissions;

    // ==================== ACTIVITY TRACKING ====================

    /**
     * Last successful login
     * @column
     * @datetime
     * @nullable
     */
    protected $last_login;

    /**
     * Failed login attempts counter
     * @column
     * @tinyint
     * @unsigned
     * @default 0
     */
    protected $failed_logins;

    /**
     * Account lockout time
     * @column
     * @datetime
     * @nullable
     */
    protected $locked_until;

    // ==================== METADATA ====================

    /**
     * User preferences and settings
     * @column
     * @json
     * @nullable
     */
    protected $settings;

    /**
     * Email verification status
     * @column
     * @boolean
     * @default 0
     */
    protected $email_verified;

    /**
     * When email was verified
     * @column
     * @datetime
     * @nullable
     */
    protected $verified_at;

    // ==================== SOFT DELETES ====================

    /**
     * Soft delete timestamp
     * @column
     * @datetime
     * @nullable
     * @index
     * @comment "NULL = active, timestamp = deleted"
     */
    protected $deleted_at;

    // ==================== TIMESTAMPS ====================

    /**
     * When the user account was created
     * @column
     * @datetime
     */
    protected $created_at;

    /**
     * When the user account was last updated
     * @column
     * @datetime
     */
    protected $updated_at;
}
```

**Best Practices Shown:**

1. **Organized Sections** - Group related columns with comment headers
2. **Comprehensive Comments** - Explain the purpose and constraints of each column
3. **Proper Indexing** - Composite indexes on frequently queried combinations
4. **Appropriate Types** - ENUM for fixed options, SET for multi-select, JSON for flexible data
5. **Security** - CHECK constraints for birth_date, password hashing note
6. **Soft Deletes** - deleted_at pattern for recoverable deletions
7. **Audit Trail** - Track login activity, verification, timestamps
8. **Unique Constraints** - Email uniqueness considering soft deletes

### Schema Management Strategies

**When to use model:table-create vs model:table-update:**

- `model:table-create` - First time creating a table from scratch
- `model:table-update` - Modifying existing tables (add/remove/rename columns)

**Safe migration workflow:**

```bash
# 1. Check current schema state
php roline model:table-schema User

# 2. Update your model annotations

# 3. Apply changes
php roline model:table-update User

# 4. Verify the result
php roline model:table-schema User
```

**Adding columns safely:**

```php
// Add new nullable columns first
/**
 * New optional field
 * @column
 * @varchar 100
 * @nullable
 */
protected $new_field;
```

Then run `model:table-update`. Later, if needed, remove `@nullable` and add `@default`.

**Renaming columns without data loss:**

```php
// Change property name and add @rename
/**
 * Email address (renamed from email_addr)
 * @column
 * @varchar 255
 * @rename email_addr
 */
protected $email;
```

Run `model:table-update` to preserve data during rename.

### Performance Optimization

**Index Strategy:**

- **Always index:** Foreign keys, frequently filtered columns, ORDER BY columns
- **Consider composite indexes:** When filtering by multiple columns together
- **Avoid over-indexing:** Each index slows INSERT/UPDATE operations
- **Use FULLTEXT:** For searching large text content, not LIKE '%term%'

**Column Type Selection:**

- Use smallest type that fits your data (TINYINT vs INT vs BIGINT)
- CHAR for fixed-length data (country codes, state abbreviations)
- VARCHAR for variable-length strings up to 255 characters
- TEXT only for content over 255 characters
- ENUM for fixed options (more efficient than VARCHAR + CHECK)

**Partitioning Large Tables:**

For tables with millions of records:

```php
/**
 * Log Model
 *
 * @partition hash(user_id) 32
 */
class LogModel extends Model
{
    // Distributes data across 32 partitions for faster queries
}
```

### Common Patterns

**Timestamps Pattern:**

```php
protected static $timestamps = true;

/**
 * @column
 * @datetime
 */
protected $created_at;

/**
 * @column
 * @datetime
 */
protected $updated_at;
```

**Soft Deletes Pattern:**

```php
/**
 * @column
 * @datetime
 * @nullable
 * @index
 */
protected $deleted_at;

// Query: WHERE deleted_at IS NULL (active records)
```

**Status Tracking Pattern:**

```php
/**
 * @column
 * @enum pending,processing,completed,failed
 * @default pending
 * @index
 */
protected $status;
```

**Audit Fields Pattern:**

```php
/**
 * @column
 * @int
 * @unsigned
 * @foreign users(id)
 * @nullable
 */
protected $created_by;

/**
 * @column
 * @int
 * @unsigned
 * @foreign users(id)
 * @nullable
 */
protected $updated_by;
```

---

## Troubleshooting

### Common Errors

#### "Column not found" Error

**Problem:** Database schema doesn't match model annotations.

```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'email' in 'field list'
```

**Solutions:**

```bash
# Check what's in the database
php roline model:table-schema User

# Compare with your model annotations
# Then update the database to match
php roline model:table-update User
```

**Cause:** You added `@column` annotation but haven't run `model:table-update` yet.

---

#### Foreign Key Constraint Fails

**Problem:** Can't create foreign key relationship.

```
SQLSTATE[HY000]: General error: 1215 Cannot add foreign key constraint
```

**Solutions:**

1. **Check column types match exactly:**

```php
// Parent table (users)
/**
 * @column
 * @autonumber
 */
protected $id;  // Creates: INT(11) UNSIGNED

// Child table (posts) - MUST match exactly
/**
 * @column
 * @int
 * @unsigned  // Don't forget this!
 * @foreign users(id)
 */
protected $user_id;
```

2. **Ensure referenced column exists:**
   - Run `php roline model:table-create User` BEFORE creating Posts table
   - Foreign keys can only reference existing columns

3. **Verify referenced column is indexed:**
   - Foreign keys must reference PRIMARY KEY or UNIQUE columns

---

#### Schema Mismatch After Update

**Problem:** Table doesn't reflect your annotation changes.

**Solutions:**

```bash
# Drop and recreate table (DESTROYS DATA - dev only!)
php roline model:table-drop User
php roline model:table-create User

# Or update safely (preserves data)
php roline model:table-update User
```

**If column rename not working:**

```php
// Make sure you used @rename annotation
/**
 * @column
 * @varchar 255
 * @rename old_column_name  // This is required!
 */
protected $new_column_name;
```

---

#### Type Mismatch Errors

**Problem:** Data doesn't fit in column type.

```
SQLSTATE[22001]: String data, right truncated
```

**Solutions:**

1. **Increase column length:**

```php
// Before
/**
 * @column
 * @varchar 50
 */
protected $email;

// After
/**
 * @column
 * @varchar 255
 */
protected $email;
```

2. **Use correct type for data:**

```php
// Wrong - price needs decimals
/**
 * @column
 * @int
 */
protected $price;

// Right
/**
 * @column
 * @decimal 10,2
 */
protected $price;
```

---

#### Annotation Syntax Errors

**Problem:** Roline doesn't recognize your annotation.

**Common mistakes:**

```php
// ❌ WRONG - Missing @column
/**
 * @varchar 255
 */
protected $email;

// ✅ CORRECT
/**
 * @column
 * @varchar 255
 */
protected $email;

// ❌ WRONG - Spaces in enum values
/**
 * @column
 * @enum active, inactive, banned
 */
protected $status;

// ✅ CORRECT - No spaces
/**
 * @column
 * @enum active,inactive,banned
 */
protected $status;

// ❌ WRONG - Wrong foreign key syntax
/**
 * @column
 * @foreign users.id
 */
protected $user_id;

// ✅ CORRECT - Use parentheses
/**
 * @column
 * @foreign users(id)
 */
protected $user_id;
```

---

#### Migration Already Exists

**Problem:** Can't create migration with same name.

```
Error: Migration file already exists
```

**Solutions:**

```bash
# Use a different, more specific name
php roline migration:make add_email_to_users

# Or delete the old migration file if it's a mistake
rm application/database/migrations/2024_01_08_create_users_table.php
```

---

#### Permission Denied Errors

**Problem:** Can't write to file system or database.

```
Error: Permission denied: application/models/UserModel.php
```

**Solutions:**

```bash
# Fix file permissions (Unix/Linux/Mac)
chmod -R 755 application/

# Fix directory ownership
sudo chown -R $USER:$USER application/

# Database permission error - check config/database.php
# Ensure MySQL user has CREATE, ALTER, DROP privileges
```

---

#### CHECK Constraint Not Working

**Problem:** CHECK constraint syntax error or not enforced.

```
SQLSTATE[HY000]: General error: 3820 Check constraint 'users_chk_1' is violated
```

**Solutions:**

1. **Requires MySQL 8.0.16+:**

```bash
mysql --version  # Verify you have MySQL 8.0.16 or later
```

2. **Fix constraint syntax:**

```php
// ❌ WRONG - References wrong column
/**
 * @column
 * @int
 * @check quantity > 0
 */
protected $stock;

// ✅ CORRECT - Column name matches
/**
 * @column
 * @int
 * @check stock > 0
 */
protected $stock;
```

---

#### NULL Value in NOT NULL Column

**Problem:** Inserting NULL into non-nullable column.

```
SQLSTATE[23000]: Integrity constraint violation: 1048 Column 'email' cannot be null
```

**Solutions:**

```php
// Option 1: Allow NULL
/**
 * @column
 * @varchar 255
 * @nullable  // Add this
 */
protected $email;

// Option 2: Set default value
/**
 * @column
 * @varchar 255
 * @default ''  // Empty string default
 */
protected $email;
```

---

### Debugging Tips

**Check what Roline sees:**

```bash
# View parsed model schema
php roline model:table-schema User

# List all tables in database
php roline db:tables

# View complete database schema
php roline db:schema

# Export table as SQL to see exact structure
php roline table:export users
```

**Verify database connection:**

```bash
# List all databases (tests connection)
php roline db:list

# If connection fails, check config/database.php
```

**Test with fresh table:**

```bash
# Create test database
php roline db:create test_db

# Update config/database.php to use test_db
# Try creating table fresh
php roline model:table-create User
```

**Common annotation validation:**

- Every column MUST have `@column` annotation
- Type annotation required: `@varchar`, `@int`, `@text`, etc.
- `@unsigned` only works with numeric types
- `@foreign` requires matching column types
- `@enum` and `@set` need comma-separated values (no spaces)
- `@default CURRENT_TIMESTAMP` only works with TIMESTAMP/DATETIME

---
