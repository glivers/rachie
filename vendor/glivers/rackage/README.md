# Rackage

**Core engine for the Rachie PHP Framework**

Rackage provides the MVC architecture, routing system, database abstraction, templating engine, and utilities that power the Rachie framework. While Rachie is the complete application framework, Rackage is the engine that makes it work.

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Architecture](#architecture)
- [Routing](#routing)
- [Controllers](#controllers)
- [Models](#models)
- [Views](#views)
- [Core Classes](#core-classes)
- [Testing](#testing)
- [License](#license)

## Requirements

- PHP 7.1 or higher
- MySQLi extension (for database features)
- Apache with mod_rewrite (or equivalent)

## Installation

Rackage is installed automatically as a dependency of Rachie:

```bash
composer require glivers/rachie
```

It should not be installed standalone - use the [Rachie framework](https://github.com/glivers/rachie) instead.

## Architecture

Rackage implements a classic MVC pattern with these core components:

### Component Flow

```
Request → Router → Controller → Model/View → Response
```

1. **Router** - Parses URLs, matches routes, dispatches to controllers
2. **Controller** - Handles business logic, coordinates models and views
3. **Model** - Database operations with query builder
4. **View** - Template rendering with compilation
5. **Registry** - Centralized configuration storage
6. **Input** - Unified request data access

## Routing

### URL-Based Routing (No Configuration Required)

Rackage routes URLs directly to controllers without any route definitions:

```
URL Format: /Controller/method/param1/param2

Examples:
/Blog/show/123        → BlogController::show('123')
/User/edit/456        → UserController::edit('456')
/Admin/dashboard      → AdminController::dashboard()
```

### HTTP Method Prefixes

Controllers can use HTTP verb prefixes for RESTful routing:

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

### Route Definitions

For custom URLs that differ from controller names:

```php
// config/routes.php
return [
    'blog' => 'Posts',                    // /blog → PostsController
    'contact' => 'Pages@contact',         // /contact → PagesController::contact()
    'profile' => 'User@show/id',          // /profile/123 → $id = '123'
    'blog/*' => 'Blog@show/slug',         // /blog/my-post → $slug = 'my-post'
    'products/*' => 'Products@show/slug', // Wildcard routes
];
```

### Route Priority

Routes are checked in this order:

1. Exact matches (`'about' => 'Pages@about'`)
2. Pattern matches (`'blog/*' => 'Blog@show/slug'`)
3. URL-based routing (`/Controller/method`)
4. Catch-all (if enabled)
5. 404 error

### Catch-All Routing (CMS Mode)

Enable in `config/settings.php` for dynamic content:

```php
'routing' => [
    'catch_all' => true,
    'catch_all_controller' => 'Pages',
    'catch_all_method' => 'show',
]
```

Perfect for CMS, e-commerce, or any app with database-driven URLs.

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

        View::with(['post' => $post])->render('blog.show');
    }

    public function postCreate() {
        $data = [
            'title' => Input::get('title'),
            'content' => Input::get('content')
        ];

        Posts::save($data);

        redirect('blog');
    }
}
```

### Method Filters (Middleware)

Use `@before` and `@after` annotations for middleware-style filters:

```php
/**
 * @before checkAuth
 * @before checkAdmin
 * @after logActivity
 */
class AdminController extends Controller {
    public $enable_filters = true;  // Enable filter system

    protected function checkAuth() {
        if (!Session::has('user_id')) {
            redirect('login');
        }
    }

    protected function checkAdmin() {
        // Verify admin privileges
    }

    protected function logActivity() {
        // Log admin actions
    }

    public function dashboard() {
        // Filters run automatically
    }
}
```

## Models

Models extend `Rackage\Model` and provide a fluent query builder. **All methods are static** - the constructor is private.

### Basic Model

```php
<?php namespace Models;

use Rackage\Model;

class PostsModel extends Model {
    protected static $table = 'posts';
    protected static $update_timestamps = true;  // Auto-manage timestamps
}
```

### Query Builder (Chainable Methods)

```php
use Models\Posts;

// SELECT with WHERE
$posts = Posts::where('status', 'published')
              ->order('created_at', 'desc')
              ->limit(10)
              ->all();

// SELECT specific fields
$posts = Posts::select(['id', 'title', 'created_at'])
              ->where('views > ?', 100)
              ->all();

// Complex queries
$posts = Posts::select(['id', 'title'])
              ->where('status = ? AND views > ?', 'published', 1000)
              ->order('created_at', 'desc')
              ->limit(20, 2)  // Page 2, 20 per page
              ->all();

// JOIN queries
$posts = Posts::leftJoin('users', 'posts.user_id = users.id', ['users.name'])
              ->where('posts.status', 'published')
              ->all();

// DISTINCT
$categories = Posts::select(['category'])->unique()->all();
```

### Execution Methods

```php
// Get all results
$posts = Posts::where('status', 'published')->all();

// Get first result only
$post = Posts::where('slug', $slug)->first();

// Count results
$count = Posts::where('status', 'published')->count();
```

### Insert/Update

```php
// Insert new record
Posts::save([
    'title' => 'New Post',
    'content' => 'Content here',
    'status' => 'draft'
]);

// Update with WHERE
Posts::where('id', 123)->save(['title' => 'Updated Title']);

// Bulk insert/update
Posts::saveBulk(
    [
        ['title' => 'Post 1', 'content' => 'Content 1'],
        ['title' => 'Post 2', 'content' => 'Content 2']
    ],
    ['title', 'content']
);
```

### Delete

```php
// Delete with WHERE
Posts::where('status', 'draft')->delete();
Posts::where('id', 123)->delete();
```

### Convenience Methods

```php
// Get by ID
$post = Posts::getById(123);

// Update by ID
Posts::saveById(['id' => 123, 'title' => 'Updated']);

// Delete by ID
Posts::deleteById(123);

// Get by timestamps
$posts = Posts::getByDateCreated('2024-01-15');
$posts = Posts::getByDateModified('2024-01-15');
```

### Raw SQL

```php
// Execute raw query when needed
$result = Posts::rawQuery("SELECT * FROM posts WHERE MATCH(content) AGAINST('search')");
```

### Complete Model API

**Query Builder (Chainable):**
- `select(array $fields)` - Select specific fields
- `where(...)` - Variadic WHERE conditions
- `leftJoin(string $table, string $condition, array $fields)` - LEFT JOIN
- `order(string $field, string $direction)` - ORDER BY
- `limit(int $limit, int $page)` - LIMIT with pagination
- `unique()` - DISTINCT results

**Execution (Terminal):**
- `all()` - Get all results
- `first()` - Get first result
- `count()` - Count results

**Insert/Update:**
- `save(array $data)` - Insert or update
- `saveBulk(array $data, array $fields, array $ids, $key)` - Bulk operations

**Delete:**
- `delete()` - Delete matching records

**Convenience:**
- `getById(int $id)` - Get by ID
- `saveById(array $data)` - Update by ID
- `deleteById(int $id)` - Delete by ID
- `getByDateCreated(string $date)` - Get by creation date
- `getByDateModified(string $date)` - Get by modification date

**Advanced:**
- `rawQuery(string $sql)` - Execute raw SQL

## Views

Views use a powerful template engine with directives and layouts.

### Basic View Rendering

```php
use Rackage\View;

// Render with data
View::with(['user' => $user, 'posts' => $posts])->render('dashboard');

// JSON response
View::json(['status' => 'success', 'data' => $results]);

// Get compiled content without rendering
$compiled = View::get('emails.welcome');
```

### Template Directives

#### Echo Statements

```php
// ESCAPED (secure, prevents XSS)
{{ $username }}
{{ $post->title }}

// RAW/UNESCAPED (dangerous with user input!)
{{{ $htmlContent }}}

// WITH DEFAULT VALUE
{{ $name or 'Guest' }}
{{ $title or 'Untitled' }}

// ESCAPED ECHO - output literal {{ }}
@{{ This will show as {{ }} }}
```

#### Control Structures

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

// FOR
@for($i = 0; $i < 10; $i++)
    <p>Item {{ $i }}</p>
@endfor

// WHILE
@while($record = $results->fetch())
    <p>{{ $record->name }}</p>
@endwhile
```

#### Loop with Empty Fallback

```php
// LOOPELSE - foreach with automatic empty state
@loopelse($users as $user)
    <div>{{ $user->name }}</div>
@empty
    <p>No users found</p>
@endloop
```

#### Layout Inheritance

```php
// CHILD VIEW (views/dashboard.php)
@extends('layouts/admin')

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

// PARENT LAYOUT (views/layouts/admin.php)
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

#### File Inclusion

```php
// Include partial templates
@include('partials/header')
@include('components/sidebar')

<div class="content">
    @include('partials/alerts')
</div>
```

### View Helpers (Auto-Imported)

These classes are available in ALL view files without `use` statements:

```php
// URL GENERATION
Url::base()                          // https://example.com/
Url::assets('style.css')             // https://example.com/public/assets/style.css

// PATH HELPERS
Path::view('blog/show')              // Absolute path to view file
Path::upload('photo.jpg')            // Absolute path to upload

// HTML ESCAPING
HTML::escape($userInput)             // Escape HTML entities (XSS prevention)

// SECURITY
Security::hash($password)            // Hash password
Security::verify($input, $hash)      // Verify hash

// DATE FORMATTING
Date::format($timestamp, 'Y-m-d')    // Format dates
Date::now()                          // Current timestamp

// SESSION
Session::get('user_id')              // Get session value
Session::set('key', 'value')         // Set session value

// ARRAY UTILITIES
Arr::get($array, 'key', 'default')   // Safe array access
Arr::exists('key', $array)           // Check if key exists

// STRING UTILITIES
Str::slug('My Title')                // Convert to URL slug
Str::limit($text, 100)               // Truncate string

// COOKIES
Cookie::get('name')                  // Get cookie
Cookie::set('name', 'value', 86400)  // Set cookie

// INPUT
Input::get('username')               // Get from GET/POST/URL params
Input::post('email')                 // Get from POST only

// REGISTRY
Registry::settings()                 // Get all settings
Registry::get('database')            // Get database config
```

## Core Classes

### Input

Unified access to request data (GET, POST, URL parameters):

```php
use Rackage\Input;

// Automatically combines GET, POST, and URL route parameters
$username = Input::get('username');
$email = Input::get('email');
$id = Input::get('id');

// POST only
$password = Input::post('password');

// Check if exists
if (Input::has('user_id')) {
    // ...
}
```

### Registry

Centralized configuration storage:

```php
use Rackage\Registry;

// Access configuration
$settings = Registry::settings();
$database = Registry::get('database');
$cache = Registry::get('cache');

// Store instances
Registry::set('logger', $loggerInstance);
$logger = Registry::make('logger');
```

### Session

Session management:

```php
use Rackage\Session;

// Set session data
Session::set('user_id', 123);
Session::set('username', 'john');

// Get session data
$userId = Session::get('user_id');
$username = Session::get('username', 'Guest');  // With default

// Check if exists
if (Session::has('user_id')) {
    // User is logged in
}

// Delete session data
Session::delete('temp_data');

// Destroy entire session
Session::destroy();
```

### CSRF Protection

```php
use Rackage\CSRF;

// Generate token (in form)
<input type="hidden" name="csrf_token" value="<?= CSRF::token() ?>">

// Validate token (in controller)
if (!CSRF::validate(Input::post('csrf_token'))) {
    die('Invalid CSRF token');
}
```

### Validation

```php
use Rackage\Validate;

$validator = new Validate();

$validator->check([
    'username' => Input::get('username'),
    'email' => Input::get('email'),
    'age' => Input::get('age')
], [
    'username' => ['required', 'min:3', 'max:20'],
    'email' => ['required', 'email'],
    'age' => ['required', 'numeric', 'min:18']
]);

if ($validator->fails()) {
    $errors = $validator->errors();
    // Handle validation errors
}
```

### Security

```php
use Rackage\Security;

// Hash password
$hash = Security::hash('user_password');

// Verify password
if (Security::verify('user_password', $hash)) {
    // Password is correct
}

// Generate random token
$token = Security::token(32);  // 32-byte token
```

### File Handling

```php
use Rackage\File;

// Check if file exists
$exists = File::exists('/path/to/file.txt')->exists;

// Read file
$content = File::read('/path/to/file.txt')->content;

// Write file
File::write('/path/to/file.txt', 'content');

// Delete file
File::delete('/path/to/file.txt');

// Delete directory recursively
File::deleteDir('/path/to/directory');

// Check if directory
$isDir = File::isDir('/path/to/directory')->isDir;

// Join paths (cross-platform)
$path = File::join('application', 'controllers', 'HomeController.php');

// Get file extension
$ext = File::extension('/path/to/file.jpg')->extension;

// Glob pattern matching
$files = File::glob('*.php')->files;
$files = File::glob('src/**/*.php')->files;  // Recursive
```

### Path Helpers

```php
use Rackage\Path;

// Get application paths
Path::app()           // /path/to/application/
Path::base()          // /path/to/project/
Path::sys()           // /path/to/system/
Path::vault()         // /path/to/vault/
Path::tmp()           // /path/to/vault/tmp/
Path::view('blog')    // /path/to/application/views/blog.php
```

### URL Helpers

```php
use Rackage\Url;

// Base URL
Url::base()                    // https://example.com/

// Asset URLs
Url::assets('css/style.css')   // https://example.com/public/assets/css/style.css
Url::uploads('photo.jpg')      // https://example.com/public/uploads/photo.jpg

// Build URLs
Url::to('blog/show/123')       // https://example.com/blog/show/123
```

### String Utilities

```php
use Rackage\Str;

// Generate slug
Str::slug('My Blog Post')      // my-blog-post

// Truncate string
Str::limit('Long text...', 50) // Long text... (truncated to 50 chars)

// Random string
Str::random(16)                // Generate random string

// Check if contains
Str::contains('haystack', 'needle')

// Check if starts with
Str::startsWith('hello world', 'hello')

// Check if ends with
Str::endsWith('hello world', 'world')
```

### Array Utilities

```php
use Rackage\Arr;

// Safe array access
$value = Arr::get($array, 'key', 'default');

// Check if key exists
$exists = Arr::exists('key', $array);

// Get first element
$first = Arr::first($array);

// Get last element
$last = Arr::last($array);

// Flatten multi-dimensional array
$flat = Arr::flatten($multiArray);
```

## Testing

Rackage includes a comprehensive test infrastructure using PHPUnit.

### Running Tests

From the Rachie root directory:

```bash
# Run all Rackage tests
composer test:rackage

# Or use PHPUnit directly
./phpunit.phar --configuration vendor/glivers/rackage/phpunit.xml
```

### Test Structure

```
vendor/glivers/rackage/tests/
├── RackageTest.php           # Base test class
├── start.php                 # Test bootstrap
└── Router/
    └── BasicRoutingTest.php  # Router tests
```

### Writing Tests

Extend `RackageTest` for framework tests:

```php
<?php namespace Tests\Router;

use Tests\RackageTest;

class CustomRoutingTest extends RackageTest {
    public function testCustomRoute() {
        // Create test controller
        $controllerPath = $this->controllerPath('TestBlog');
        $this->trackFile($controllerPath);

        file_put_contents($controllerPath, '<?php namespace Controllers; ...');

        // Test routing
        $response = $this->request('TestBlog/index');
        $this->assertResponseContains('Expected output', $response);
    }
}
```

### Test Helpers

```php
// Path helpers
$this->controllerPath('TestHome')  // Get controller path
$this->modelPath('TestPost')       // Get model path
$this->viewPath('test/view')       // Get view path

// File tracking (auto-cleanup)
$this->trackFile($filePath)        // Track file for deletion after test

// HTTP simulation
$response = $this->request('url', 'GET', [], $customRoutes, $settingsOverride);

// Custom assertions
$this->assertResponseContains('text', $response)
$this->assertResponseNotContains('text', $response)
$this->assertViewRendered($response)
$this->assertSessionHas('key')
$this->assertSessionMissing('key')
$data = $this->assertJsonResponse($response)
```

## License

MIT License

Copyright (c) 2015 - 2030 Geoffrey Okongo

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

---

**For detailed documentation, tutorials, and guides, visit [rachie.dev](https://rachie.dev)**

**Main Rachie repository:** [github.com/glivers/rachie](https://github.com/glivers/rachie)
