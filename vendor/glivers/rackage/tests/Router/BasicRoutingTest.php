<?php namespace Tests\Router;

/**
 * Basic Routing Integration Tests
 *
 * Tests fundamental routing functionality - URL-based routing without route definitions.
 * This is the simplest routing mechanism where URLs directly map to controllers and methods.
 *
 * URL Format: /Controller/method/param1/param2
 *
 * What Gets Tested:
 *   - Default route (homepage)
 *   - Simple controller/method routing
 *   - URL parameters passed to controller methods
 *   - Missing controllers/methods
 *
 * Architecture:
 *   These tests verify the Router class can handle basic URL-to-controller mapping
 *   without any route definitions in config/routes.php. This is the fallback mechanism
 *   when no explicit routes are defined.
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2050 Geoffrey Okongo
 * @category Tests
 * @package Rackage
 * @link https://github.com/glivers/rackage
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0.0
 */

use Tests\RackageTest;

class BasicRoutingTest extends RackageTest
{
    /**
     * Test empty URL (homepage) uses actual default controller
     *
     * When URL is completely empty (like accessing example.com or localhost/rachie),
     * Router should dispatch to the default controller from settings.php.
     * Since settings.php has 'default' => ['controller' => 'Home', 'action' => 'index'],
     * it should use the REAL HomeController (not a test controller).
     *
     * @return void
     */
    public function testEmptyUrlUsesDefaultController()
    {
        // Request empty URL (homepage - like accessing example.com)
        $response = $this->request('');

        // Get the response code
        $code = http_response_code();

        // Truncate response to first 500 characters for readability
        $errorPreview = strlen($response) > 500
            ? substr($response, 0, 500) . '... (truncated)'
            : $response;

        // Should return HTTP 200 (success), not 500 (error)
        // If it fails, show the actual error message from the response
        $this->assertEquals(
            200,
            $code,
            "Empty URL should return HTTP 200 but got {$code}. Error output:\n" . $errorPreview
        );
    }

    /**
     * Test default route with test controller
     *
     * This tests basic routing to a test controller to verify
     * the framework correctly routes to controller@index when only
     * controller name is provided.
     *
     * @return void
     */
    public function testDefaultRoute()
    {
        // Create test controller
        $controllerPath = $this->controllerPath('TestHome');
        $this->trackFile($controllerPath);

        $controllerCode = <<<'PHP'
<?php namespace Controllers;

use Rackage\Controller;

class TestHomeController extends Controller {
    public function index() {
        echo "Test Homepage";
    }
}
PHP;

        file_put_contents($controllerPath, $controllerCode);

        // Request just controller name (should default to index method)
        $response = $this->request('TestHome');

        // Should dispatch to TestHome@index
        $this->assertResponseContains('Test Homepage', $response);
    }

    /**
     * Test simple controller/method routing via URL
     *
     * URL format: /Controller/method
     * Should dispatch to ControllerController::method()
     *
     * @return void
     */
    public function testSimpleControllerMethod()
    {
        // Create test controller
        $controllerPath = $this->controllerPath('TestBlog');
        $this->trackFile($controllerPath);

        $controllerCode = <<<'PHP'
<?php namespace Controllers;

use Rackage\Controller;

class TestBlogController extends Controller {
    public function index() {
        echo "Blog Index";
    }

    public function show() {
        echo "Blog Show";
    }
}
PHP;

        file_put_contents($controllerPath, $controllerCode);

        // Test Controller/method routing
        $response = $this->request('TestBlog/index');
        $this->assertResponseContains('Blog Index', $response);

        $response = $this->request('TestBlog/show');
        $this->assertResponseContains('Blog Show', $response);
    }

    /**
     * Test URL parameters are passed to controller method
     *
     * URL format: /Controller/method/param1/param2
     * Parameters should be passed to method in order
     *
     * @return void
     */
    public function testUrlParametersPassedToMethod()
    {
        // Create test controller
        $controllerPath = $this->controllerPath('TestPosts');
        $this->trackFile($controllerPath);

        $controllerCode = <<<'PHP'
<?php namespace Controllers;

use Rackage\Controller;

class TestPostsController extends Controller {
    public function show($id) {
        echo "Post ID: " . $id;
    }

    public function edit($id, $section) {
        echo "Edit Post {$id}, Section: {$section}";
    }
}
PHP;

        file_put_contents($controllerPath, $controllerCode);

        // Test single parameter
        $response = $this->request('TestPosts/show/123');
        $this->assertResponseContains('Post ID: 123', $response);

        // Test multiple parameters
        $response = $this->request('TestPosts/edit/456/comments');
        $this->assertResponseContains('Edit Post 456, Section: comments', $response);
    }

    /**
     * Test default action is used when URL has controller but no method
     *
     * URL format: /Controller
     * Should dispatch to Controller::index() (default action from settings)
     *
     * @return void
     */
    public function testDefaultActionWhenNoMethodInUrl()
    {
        // Create test controller
        $controllerPath = $this->controllerPath('TestUsers');
        $this->trackFile($controllerPath);

        $controllerCode = <<<'PHP'
<?php namespace Controllers;

use Rackage\Controller;

class TestUsersController extends Controller {
    public function index() {
        echo "Users Listing";
    }
}
PHP;

        file_put_contents($controllerPath, $controllerCode);

        // Request just controller name (no method)
        $response = $this->request('TestUsers');

        // Should default to index() method
        $this->assertResponseContains('Users Listing', $response);
    }

    /**
     * Test missing controller shows error (not exit in testing mode)
     *
     * When a controller doesn't exist, framework should show error page
     * but NOT call exit() in testing mode (ROLINE_INSTANCE defined).
     *
     * @return void
     */
    public function testMissingControllerShowsError()
    {
        // Request a controller that doesn't exist
        $response = $this->request('NonExistentController/index');

        // Should show error message (not exit)
        $this->assertResponseContains('error', strtolower($response));
    }

    /**
     * Test missing method shows error (not exit in testing mode)
     *
     * When a method doesn't exist on controller, framework should show error
     * but NOT call exit() in testing mode (ROLINE_INSTANCE defined).
     *
     * @return void
     */
    public function testMissingMethodShowsError()
    {
        // Create controller but without the requested method
        $controllerPath = $this->controllerPath('TestIncomplete');
        $this->trackFile($controllerPath);

        $controllerCode = <<<'PHP'
<?php namespace Controllers;

use Rackage\Controller;

class TestIncompleteController extends Controller {
    public function index() {
        echo "Index method exists";
    }
}
PHP;

        file_put_contents($controllerPath, $controllerCode);

        // Request a method that doesn't exist
        $response = $this->request('TestIncomplete/nonExistentMethod');

        // Should show error message (not exit)
        $this->assertResponseContains('error', strtolower($response));
    }

    /**
     * Test controller with no parameters in method signature
     *
     * Verify that methods without parameters work correctly even when
     * URL contains parameters (parameters should be ignored/padded as null).
     *
     * @return void
     */
    public function testMethodWithNoParameters()
    {
        // Create test controller
        $controllerPath = $this->controllerPath('TestAbout');
        $this->trackFile($controllerPath);

        $controllerCode = <<<'PHP'
<?php namespace Controllers;

use Rackage\Controller;

class TestAboutController extends Controller {
    public function index() {
        echo "About Page";
    }
}
PHP;

        file_put_contents($controllerPath, $controllerCode);

        // Request with extra URL segments (should be ignored)
        $response = $this->request('TestAbout/index/extra/segments');

        // Should still work
        $this->assertResponseContains('About Page', $response);
    }

    /**
     * Test method parameters are padded with null when fewer URL segments
     *
     * When method expects more parameters than URL provides, Router should
     * pad parameter array with null values.
     *
     * @return void
     */
    public function testMethodParametersPaddedWithNull()
    {
        // Create test controller
        $controllerPath = $this->controllerPath('TestSearch');
        $this->trackFile($controllerPath);

        $controllerCode = <<<'PHP'
<?php namespace Controllers;

use Rackage\Controller;

class TestSearchController extends Controller {
    public function results($query = null, $page = null) {
        $output = "Query: " . ($query ?? 'none');
        $output .= ", Page: " . ($page ?? 'none');
        echo $output;
    }
}
PHP;

        file_put_contents($controllerPath, $controllerCode);

        // Request with one parameter (method expects two)
        $response = $this->request('TestSearch/results/test');

        // Second parameter should be null
        $this->assertResponseContains('Query: test', $response);
        $this->assertResponseContains('Page: none', $response);

        // Request with no parameters
        $response = $this->request('TestSearch/results');

        // Both parameters should be null
        $this->assertResponseContains('Query: none', $response);
        $this->assertResponseContains('Page: none', $response);
    }

    /**
     * Test query parameters with empty URL (homepage)
     *
     * URL: /?utm_source=google&utm_medium=cpc
     * Query params should be accessible via Input::get()
     *
     * @return void
     */
    public function testQueryParametersWithEmptyUrl()
    {
        // Override default controller to use test controller
        $controllerPath = $this->controllerPath('TestHome');
        $this->trackFile($controllerPath);

        $controllerCode = <<<'PHP'
<?php namespace Controllers;

use Rackage\Controller;
use Rackage\Input;

class TestHomeController extends Controller {
    public function index() {
        echo "utm_source: " . (Input::get('utm_source') ?? 'none');
        echo ", utm_medium: " . (Input::get('utm_medium') ?? 'none');
    }
}
PHP;

        file_put_contents($controllerPath, $controllerCode);

        // Simulate query string by setting $_GET before request
        $_GET['utm_source'] = 'google';
        $_GET['utm_medium'] = 'cpc';

        // Request with settings override to use TestHome controller
        $response = $this->request('', 'GET', [], null, [
            'default' => ['controller' => 'TestHome', 'action' => 'index']
        ]);

        // Should be HTTP 200 and contain query params
        $this->assertEquals(200, http_response_code());
        $this->assertResponseContains('utm_source: google', $response);
        $this->assertResponseContains('utm_medium: cpc', $response);
    }

    /**
     * Test query parameters with controller/method URL
     *
     * URL: /TestPosts/show/123?source=email&ref=newsletter
     * Both URL params (123) and query params (source, ref) should work
     *
     * @return void
     */
    public function testQueryParametersWithControllerMethod()
    {
        // Create test controller
        $controllerPath = $this->controllerPath('TestPosts');
        $this->trackFile($controllerPath);

        $controllerCode = <<<'PHP'
<?php namespace Controllers;

use Rackage\Controller;
use Rackage\Input;

class TestPostsController extends Controller {
    public function show($id) {
        echo "Post ID: " . $id;
        echo ", Source: " . (Input::get('source') ?? 'none');
        echo ", Ref: " . (Input::get('ref') ?? 'none');
    }
}
PHP;

        file_put_contents($controllerPath, $controllerCode);

        // Simulate query string
        $_GET['source'] = 'email';
        $_GET['ref'] = 'newsletter';

        // Request with URL parameter AND query parameters
        $response = $this->request('TestPosts/show/123');

        // Should contain both URL param and query params
        $this->assertEquals(200, http_response_code());
        $this->assertResponseContains('Post ID: 123', $response);
        $this->assertResponseContains('Source: email', $response);
        $this->assertResponseContains('Ref: newsletter', $response);
    }

    /**
     * Test query parameters don't interfere with routing
     *
     * URL: /TestBlog/index?page=2&sort=date
     * Query params should not affect controller/method resolution
     *
     * @return void
     */
    public function testQueryParametersDontInterfereWithRouting()
    {
        // Create test controller
        $controllerPath = $this->controllerPath('TestBlog');
        $this->trackFile($controllerPath);

        $controllerCode = <<<'PHP'
<?php namespace Controllers;

use Rackage\Controller;
use Rackage\Input;

class TestBlogController extends Controller {
    public function index() {
        echo "Blog Index";
        echo ", Page: " . (Input::get('page') ?? '1');
        echo ", Sort: " . (Input::get('sort') ?? 'default');
    }
}
PHP;

        file_put_contents($controllerPath, $controllerCode);

        // Simulate query string with pagination and sorting
        $_GET['page'] = '2';
        $_GET['sort'] = 'date';

        // Request should route correctly despite query params
        $response = $this->request('TestBlog/index');

        // Should route to correct controller and access query params
        $this->assertEquals(200, http_response_code());
        $this->assertResponseContains('Blog Index', $response);
        $this->assertResponseContains('Page: 2', $response);
        $this->assertResponseContains('Sort: date', $response);
    }
}

