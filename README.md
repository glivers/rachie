# Rachie PHP Framework

> A lightweight, elegant PHP MVC framework for building web applications quickly.

[![Latest Version](https://img.shields.io/badge/version-2.0-blue)]
[![PHP Version](https://img.shields.io/badge/php-%3E%3D7.1-purple)]
[![License](https://img.shields.io/badge/license-MIT-green)]

## Table of Contents

- [What is Rachie?](#what-is-rachie)
- [Key Features](#key-features)
- [Quick Start](#quick-start)
- [Boot Sequence](#boot-sequence)
- [Routing](#routing)
- [Controllers](#controllers)
- [Models](#models)
- [Views](#views)
- [Configuration](#configuration)
- [CLI Tool (Roline)](#cli-tool-roline)
- [Testing](#testing)
- [Requirements](#requirements)
- [Documentation](#documentation)
- [Contributing](#contributing)
- [License](#license)

## What is Rachie?

Rachie is a simple, lightweight PHP framework that helps you build web applications without the complexity of larger frameworks. If most frameworks feels too heavy and pure PHP feels too raw, Rachie is your sweet spot.

## Key Features

- **Simple Routing** - URL-based routing works without configuration, custom routes when needed
- **MVC Architecture** - Clean separation of concerns
- **Query Builder** - Fluent database interface with 40+ methods
- **Template Engine** - Simple `{{ }}` syntax with layout inheritance
- **Form Validation** - Built-in validation with custom rules
- **Security First** - CSRF protection, XSS prevention, SQL injection prevention
- **CLI Tool (Roline)** - Generate controllers, models, run migrations
- **Zero Configuration** - Works out of the box

## Quick Start

### Installation

```bash
composer create-project glivers/rachie myapp
cd myapp
```

### Configuration

Edit `config/database.php`:

```php
'mysql' => [
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'myapp'
]
```

### Your First Route

Create `application/controllers/HelloController.php`:

```php
<?php namespace Controllers;

use Rackage\Controller;

class HelloController extends Controller {
    public function getIndex() {
        echo "Hello, Rachie!";
    }
}
```

Visit `http://localhost/myapp/Hello/index`

## Boot Sequence

Understanding how Rachie processes requests:

### 1. Request Entry

**Root `.htaccess`** → Redirects all requests to `public/` directory

**`public/.htaccess`** → If not a real file/directory:
```apache
RewriteRule ^(.*)$ index.php?_rachie_route=$1 [QSA,L]
```

**`public/index.php`** → Entry point:
- Sets error logging to `vault/logs/error.log`
- Defines `RACHIE_START` constant
- Requires `system/bootstrap.php`

### 2. Bootstrap (`system/bootstrap.php`)

- Validates required files exist
- Loads configs: `settings.php`, `database.php`, `cache.php`, `mail.php`
- Sets timezone from config
- Defines `DEV` constant (dev vs production mode)
- Registers error handlers
- Starts PHP session
- Loads Composer autoloader
- Loads application constants
- Stores everything in `Registry`
- Requires `system/start.php` (web requests only)

### 3. Start (`system/start.php`)

- Initializes `Input::setGet()->setPost()`
- Loads `config/routes.php`
- Creates `Router` instance
- Calls `Router::dispatch()`

### 4. Router Dispatch

- Parses URL
- Matches routes (exact → pattern → URL-based)
- Resolves controller and method
- Executes filters (`@before`, `@after`)
- Dispatches to controller method

### 5. Controller & View

- Controller method executes
- Renders view with `View::render()`

## Routing

### URL-Based Routing (No Configuration)

URLs map directly to controllers without route definitions:

```
Format: /Controller/method/param1/param2

Examples:
/Blog/show/123        → BlogController::show('123')
/User/edit/456        → UserController::edit('456')
```

### Custom Routes

Define in `config/routes.php`:

```php
return [
    // Basic route
    'blog' => 'Posts',                    // /blog → PostsController

    // Controller and method
    'contact' => 'Pages@contact',         // /contact → PagesController::contact()

    // Named parameters
    'profile' => 'User@show/id',          // /profile/123 → $id = '123'

    // Wildcard routes
    'blog/*' => 'Blog@show/slug',         // /blog/my-post → $slug = 'my-post'
];
```

### Route Priority

Routes are checked in this order:

1. Exact matches (`'about' => 'Pages@about'`)
2. Pattern matches (`'blog/*' => 'Blog@show/slug'`)
3. URL-based routing (`/Controller/method`)
4. Catch-all (if enabled)
5. 404 error

### Catch-All Routing

Perfect for CMS or dynamic content. Enable in `config/settings.php`:

```php
'routing' => [
    'catch_all' => true,
    'controller' => 'Pages',
    'method' => 'show',
]
```

Now any unmatched URL like `/about` or `/privacy` routes to `PagesController::show($slug)`.

## Controllers

Controllers extend `Rackage\Controller` and handle application logic.

### Basic Controller

```php
<?php namespace Controllers;

use Rackage\Controller;
use Rackage\View;
use Models\Posts;

class BlogController extends Controller {
    public function getShow($id) {
        $post = Posts::where('id', $id)->first();
        View::render('blog.show', ['post' => $post]);
    }
}
```

### HTTP Verb Routing

Prefix methods with HTTP verbs for RESTful routing:

```php
class UserController extends Controller {
    public function getProfile() {
        // Handles GET /User/profile
    }

    public function postProfile() {
        // Handles POST /User/profile
    }

    public function putProfile() {
        // Handles PUT /User/profile
    }

    public function deleteProfile() {
        // Handles DELETE /User/profile
    }
}
```

If no prefixed method exists, falls back to unprefixed method (`profile()`).

### Available Properties

```php
$this->request_start_time  // Request start timestamp
$this->site_title          // Application title from config
$this->settings            // All settings array
```

## Models

Models extend `Rackage\Model` and use a fluent query builder. **All methods are static** - no instance creation needed.

### Basic Model

```php
<?php namespace Models;

use Rackage\Model;

class PostsModel extends Model {
    protected static $table = 'posts';
    protected static $timestamps = true;  // Auto-manage created_at/updated_at
}
```

### Query Builder

**Select & Where:**

```php
use Models\Posts;

// Basic query
$posts = Posts::where('status', 'published')->all();

// Multiple conditions
$posts = Posts::where('status = ? AND views > ?', 'published', 100)->all();
```

**Order & Limit:**

```php
// Order results
$posts = Posts::order('created_at', 'desc')->limit(10)->all();

// Pagination
$posts = Posts::limit(20, 2)->all();  // 20 per page, page 2
```

**Joins:**

```php
// Left join
$posts = Posts::leftJoin('users', 'posts.user_id = users.id', ['users.name'])
              ->where('posts.status', 'published')
              ->all();
```

**Advanced Queries:**

```php
// Group by with having
$stats = Posts::select(['category', 'COUNT(*) as count'])
              ->groupBy('category')
              ->having('count > ?', 10)
              ->all();

// WHERE IN
$posts = Posts::whereIn('category', ['tech', 'science'])->all();

// WHERE BETWEEN
$posts = Posts::whereBetween('views', 100, 1000)->all();

// WHERE LIKE
$posts = Posts::whereLike('title', '%tutorial%')->all();

// Full-text search
$posts = Posts::whereFulltext(['title', 'content'], 'search term')->all();
```

### Insert & Update

```php
// Insert
Posts::save([
    'title' => 'New Post',
    'content' => 'Content here',
    'status' => 'draft'
]);

// Update with WHERE
Posts::where('id', 123)->save(['title' => 'Updated Title']);
```

### Delete

```php
// Delete with WHERE
Posts::where('status', 'draft')->delete();

// Delete by ID
Posts::deleteById(123);
```

### Retrieval Methods

```php
// Get all results
$posts = Posts::where('status', 'published')->all();

// Get first result
$post = Posts::where('slug', $slug)->first();

// Count results
$count = Posts::where('status', 'published')->count();

// Get by ID
$post = Posts::getById(123);

// Check if exists
$exists = Posts::where('slug', $slug)->exists();

// Pluck single column
$titles = Posts::pluck('title');
```

### Transactions

```php
Posts::transaction();

try {
    Posts::save(['title' => 'Post 1']);
    Posts::save(['title' => 'Post 2']);
    Posts::commit();
} catch (Exception $e) {
    Posts::rollback();
}
```

### Increment/Decrement

```php
// Increment views
Posts::where('id', 123)->increment('views');

// Decrement by amount
Posts::where('id', 123)->decrement('stock', 5);
```

### Raw SQL

```php
// Raw query
$results = Posts::rawQuery("SELECT * FROM posts WHERE created_at > NOW() - INTERVAL 7 DAY");

// With parameter binding
$results = Posts::rawQueryWithBinding(
    "SELECT * FROM posts WHERE status = ? AND views > ?",
    'published',
    100
);
```

## Views

Views use a powerful template engine with directives and layout inheritance.

### Rendering Views

```php
use Rackage\View;

// Basic render
View::render('blog.show', ['post' => $post]);

// With HTTP status code
View::render('maintenance', [], 503);

// Method chaining
View::with(['user' => $user])
    ->with(['posts' => $posts])
    ->render('dashboard');

// JSON response
View::json(['status' => 'success'], 200);

// Error pages
View::error(404);
View::error(500, ['message' => 'Database error']);
```

### Template Directives

**Echo Tags:**

```php
// ESCAPED (secure, default) - prevents XSS
{{ $username }}
{{ $post->title }}

// RAW/UNESCAPED - outputs HTML as-is
{{{ $htmlContent }}}

// With default value
{{ $name or 'Guest' }}
```

**Control Structures:**

```php
// IF / ELSEIF / ELSE
@if($user->isAdmin())
    <p>Admin Panel</p>
@elseif($user->isModerator())
    <p>Moderator Panel</p>
@else
    <p>User Panel</p>
@endif

// FOREACH
@foreach($posts as $post)
    <h2>{{ $post->title }}</h2>
@endforeach
```

**Loop with Empty Fallback:**

```php
@loopelse($users as $user)
    <div>{{ $user->name }}</div>
@empty
    <p>No users found</p>
@endloop
```

**Layout Inheritance:**

```php
<!-- Child view: views/dashboard.php -->
@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    <div class="dashboard">
        <!-- Content here -->
    </div>
@endsection

@section('scripts')
    @parent
    <script src="/js/dashboard.js"></script>
@endsection

<!-- Parent layout: views/layouts/admin.php -->
<!DOCTYPE html>
<html>
<head>
    <title>{{ $title or 'Admin' }}</title>
</head>
<body>
    <main>@section('content'):</main>

    @section('scripts')
        <script src="/js/app.js"></script>
    @endsection
</body>
</html>
```

**File Inclusion:**

```php
@include('partials.header')
@include('components.sidebar')
```

### View Helpers

These classes are auto-imported in all views:

```php
// URL Generation
Url::base()                    // https://example.com/
Url::assets('style.css')       // https://example.com/public/assets/style.css

// HTML Escaping
HTML::escape($userInput)       // Escape HTML entities

// Security
Security::hash($password)
Security::verify($input, $hash)

// Session
Session::get('user_id')
Session::set('key', 'value')

// Input
Input::get('username')         // From GET/POST/URL params
Input::post('email')           // From POST only

// Array/String utilities
Arr::get($array, 'key', 'default')
Str::slug('My Title')          // my-title
```

## Configuration

### Main Settings (`config/settings.php`)

```php
'timezone' => 'America/New_York'     // Application timezone
'dev' => true                        // Development mode (true) or production (false)
'title' => 'My App'                  // Site title
'protocol' => 'auto'                 // 'http', 'https', or 'auto'
'url_separator' => '/'               // URL component separator

// Default controller/method
'default' => [
    'controller' => 'Home',
    'action' => 'Index'
]

// Template engine
'template_engine' => true            // Enable template compilation
'template_echo_tags' => ['{{', '}}'] // Escaped echo tags
'template_raw_tags' => ['{{{', '}}}'] // Raw echo tags

// Routing
'routing' => [
    'catch_all' => false,            // Enable catch-all routing
    'controller' => 'Pages',
    'method' => 'show'
]

// Error pages
'error_pages' => [
    '404' => 'errors.404',
    '500' => 'errors.500'
]
```

### Database Config (`config/database.php`)

```php
'default' => 'mysql',

'mysql' => [
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'myapp',
    'port' => '3306',
    'charset' => 'utf8mb4'
]
```

## CLI Tool (Roline)

Rachie includes a command-line tool for common tasks:

```bash
# Run Roline
php roline

# Generate controller
php roline make:controller Posts

# Generate model
php roline make:model Post

# Run migrations
php roline migrate

# Rollback migrations
php roline migrate:rollback

# Create migration
php roline make:migration create_posts_table

# Export database
php roline db:export
```

## Testing

Rachie uses PHPUnit for testing:

```bash
# Run all tests
composer test

# Run application tests
composer test:app

# Run framework tests
composer test:rackage
```

### Writing Tests

```php
<?php namespace Tests;

use Tests\RachieTest;

class ControllerTest extends RachieTest {
    public function testHomepage() {
        $response = $this->request('');
        $this->assertEquals(200, http_response_code());
        $this->assertResponseContains('Welcome', $response);
    }
}
```

## Requirements

- PHP 7.1 or higher
- Apache with mod_rewrite or Nginx
- MySQL, PostgreSQL, or SQLite
- Composer

## Documentation

Full documentation: **https://rachie.dev**

- [Getting Started](https://rachie.dev/getting-started.html)
- [Routing Guide](https://rachie.dev/routing.html)
- [Model Documentation](https://rachie.dev/models.html)
- [View Templates](https://rachie.dev/views.html)
- [API Reference](https://rachie.dev/api/)

## Contributing

Thank you for considering contributing to Rachie! Please see our [Contributing Guide](https://rachie.dev/contributing.html).

## Security

If you discover a security vulnerability, please email code@rachie.dev.

## License

Rachie is open-source software licensed under the [MIT license](LICENSE).

## Credits

Created by [Geoffrey Okongo](https://github.com/glivers)

**Core Package:** [Rackage](https://github.com/glivers/rackage) - The engine powering Rachie

**CLI Tool:** [Roline](https://github.com/glivers/roline) - Command-line interface for Rachie
