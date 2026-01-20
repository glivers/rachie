<?php
/**
 * Route Configuration
 *
 * Define custom URL routes for your application.
 * Routes are OPTIONAL - URL-based routing works without any routes defined.
 *
 * Use routes when your URL should differ from the controller/method name.
 * This creates cleaner, more user-friendly URLs.
 *
 * Route Priority (checked in this order):
 *   1. Exact matches ('about', 'contact')
 *   2. Pattern matches ('blog/*', 'products/*')
 *   3. URL-based routing (/Controller/method)
 *   4. Catch-all (if enabled in settings.php)
 *   5. 404 error page
 *
 * Empty URL: Uses default controller/method from settings.php
 * Catch-All Routing: See settings.php → 'routing' → 'catch_all' for CMS-style apps
 *
 * @category Configuration
 * @package  Rachie
 */

return array(

	// ===========================================================================
	// BASIC ROUTES
	// ===========================================================================

	/**
	 * Map a URL path to a specific controller and method.
	 *
	 * Format: 'urlpath' => 'Controller@method'
	 *
	 * The URL path becomes a friendly alias for the controller action.
	 * Additional URL segments after the path become method parameters.
	 */
	'blog' => 'Posts',				 // /blog → PostsController
	'contact' => 'Pages@contact',    // /contact → PagesController::contact()

	// ===========================================================================
	// COMPOUND ROUTES (2-segment matching)
	// ===========================================================================

	/**
	 * Compound routes match two URL segments (e.g., 'admin/users').
	 * Useful for namespacing admin panels, APIs, or avoiding URL conflicts.
	 *
	 * Format: 'segment1/segment2' => 'Controller@method'
	 *
	 * Example:
	 *   Route: 'admin/users' => 'home'
	 *   URL: /admin/users/search
	 *   Result: HomeController::search()
	 *
	 * Priority: Compound routes are checked BEFORE single-segment routes.
	 * So 'admin/users' takes precedence over 'admin'.
	 */
	//'admin/users' => 'Home',           // Route to HomeController, method from URL

	// ===========================================================================
	// ROUTES WITH NAMED PARAMETERS
	// ===========================================================================

	/**
	 * Define parameter names in your route for cleaner code.
	 *
	 * Format: 'urlpath' => 'Controller@method/param1/param2'
	 *
	 * Parameters are:
	 *   - Passed as method arguments: public function show($id) { }
	 *   - Accessible via Input::get('id')
	 *
	 * Example:
	 *   Route: 'profile' => 'User@show/id'
	 *   URL: /profile/123
	 *   Result: UserController::show($id='123')
	 *   Access: Input::get('id') returns '123'
	 */

	'profile' => 'User@show/id',
	'post' => 'Blog@view/slug',

	// ===========================================================================
	// PATTERN ROUTES (WILDCARD MATCHING)
	// ===========================================================================

	/**
	 * Pattern routes use wildcards (*) to match dynamic URL segments.
	 * Useful for CMS, blogs, e-commerce where you have many similar URLs.
	 *
	 * Format: 'prefix/*' => 'Controller@method/param'
	 *
	 * The wildcard (*) captures everything after the prefix and passes it
	 * as a parameter to your controller method.
	 *
	 * Examples:
	 *   Route: 'blog/*' => 'Blog@show/slug'
	 *   URL: /blog/my-awesome-post
	 *   Result: BlogController::show($slug='my-awesome-post')
	 *
	 *   Route: 'products/*' => 'Products@show/slug'
	 *   URL: /products/nike-air-max-270
	 *   Result: ProductsController::show($slug='nike-air-max-270')
	 *
	 * Multi-segment wildcards:
	 *   URL: /blog/2024/january/my-post
	 *   Result: $slug='2024/january/my-post' (entire path after 'blog/')
	 *
	 * Priority: Pattern routes are checked AFTER exact matches.
	 * So 'blog' (exact) is checked before 'blog/*' (pattern).
	 */

	'blog/*' => 'Blog@show/slug',
	'products/*' => 'Products@show/slug',
	'docs/*' => 'Documentation@show/path',

	// ===========================================================================
	// URL-BASED ROUTING (NO ROUTES NEEDED)
	// ===========================================================================

	/**
	 * Routes are completely optional. Without routes, URLs map directly to controllers.
	 *
	 * Format: /Controller/method/param1/param2
	 *
	 * Examples:
	 *   /Blog/show/123 → BlogController::show('123')
	 *   /User/edit/456 → UserController::edit('456')
	 *   /Admin/dashboard → AdminController::dashboard()
	 *
	 * HTTP Method Prefixes:
	 *   Controller methods can be prefixed with HTTP verbs (get, post, put, delete).
	 *   The framework automatically routes to the correct method based on request type.
	 *
	 *   Examples:
	 *     GET  /User/profile → UserController::getProfile()
	 *     POST /User/profile → UserController::postProfile()
	 *
	 *   If no prefixed method exists, it falls back to the unprefixed method.
	 *     /User/profile → UserController::profile() (any HTTP method)
	 *
	 * See controller documentation for more details on HTTP method routing.
	 */

);