<?php
/**
 * Rachie Custom Autoloader Configuration
 *
 * Register additional PSR-4 namespaces for your Rachie application.
 * This allows you to organize code outside the standard Controllers/Models/Lib structure
 * without modifying composer.json.
 *
 * Common use cases:
 *   - CMS themes and plugins
 *   - Multi-tenant applications
 *   - Modular architecture
 *   - Third-party integrations
 *
 * Format:
 *   'Namespace\\' => 'path/to/directory'
 *
 * Examples:
 *   'Themes\\'   => 'themes/',
 *   'Plugins\\'  => 'plugins/',
 *   'Modules\\'  => 'modules/',
 *   'Services\\' => 'application/services/',
 *
 * IMPORTANT:
 *   - Namespace MUST end with \\
 *   - Path is relative to application root
 *   - Path should NOT start with /
 *   - Follows PSR-4 conventions
 *
 * @category Configuration
 * @package  Rachie
 * @author   Geoffrey Okongo <code@rachie.dev>
 * @license  http://opensource.org/licenses/MIT MIT License
 */

return [
	// Add your custom namespaces here
	// 'Themes\\'   => 'themes/',
	// 'Plugins\\'  => 'plugins/',
	// 'Modules\\'  => 'modules/',
	// 'Services\\' => 'application/services/',
];
