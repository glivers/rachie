# Rachie PHP Framework

> A lightweight, elegant PHP MVC framework for building web applications quickly.

[![Latest Version](https://img.shields.io/badge/version-2.0-blue)]
[![PHP Version](https://img.shields.io/badge/php-%3E%3D7.0-purple)]
[![License](https://img.shields.io/badge/license-MIT-green)]

## What is Rachie?

Rachie is a simple, lightweight PHP framework that helps you build web applications without the complexity of larger frameworks. If Laravel feels too heavy and pure PHP feels too raw, Rachie is your sweet spot.

## Key Features

- **Simple Routing** - Elegant URL routing with support for parameters and filters
- **MVC Architecture** - Clean separation of concerns
- **Database Layer** - Fluent query builder with PDO prepared statements
- **Template Engine** - Simple `{{ }}` syntax for views
- **Form Validation** - Built-in validation with custom rules
- **Security First** - CSRF protection, XSS prevention, SQL injection prevention
- **File-based Cache** - Simple caching for expensive operations
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
return [
    'default' => 'mysql',
    'mysql' => [
        'host' => 'localhost',
        'username' => 'root',
        'password' => '',
        'database' => 'myapp'
    ]
];
```

### Your First Route

Add to `config/routes.php`:
```php
'hello' => 'Hello@world'
```

Create `application/controllers/HelloController.php`:
```php
<?php namespace Controllers;

class HelloController extends BaseController {
    public function getWorld() {
        echo "Hello, Rachie!";
    }
}
```

Visit `http://localhost/hello/world`

## Documentation

Full documentation: **https://rachie.dev**

## Requirements

- PHP 7.0 or higher
- Apache or Nginx
- MySQL, PostgreSQL, or SQLite
- Composer

## Learning Rachie

- [Documentation](https://rachie.dev)
- [Tutorials](https://rachie.dev/tutorials.html)
- [API Reference](https://rachie.dev/api/)

## Contributing

Thank you for considering contributing to Rachie! Please see our [Contributing Guide](https://rachie.dev/contributing.html).

## Security

If you discover a security vulnerability, please email code@rachie.dev.

## License

Rachie is open-source software licensed under the [MIT license](LICENSE).

## Credits

Created by [Geoffrey Okongo](https://github.com/glivers)