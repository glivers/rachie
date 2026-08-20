<?php namespace Rackage;

/**
 * Html Helper
 *
 * Provides HTML generation utilities including tag builders, attribute management,
 * escaping for security, and common HTML element generators.
 *
 * Static Design:
 *   All methods are static - no instance creation required.
 *
 * Access Patterns:
 *
 *   In Controllers:
 *     use Rackage\Html;
 *
 *     class PageController extends Controller {
 *         public function show() {
 *             $content = Html::escape($userInput);
 *         }
 *     }
 *
 *   In Views:
 *     Html is automatically available (configured in view_helpers).
 *     No 'use' statement needed.
 *
 *     {{ Html::escape($userInput) }}
 *     {{{ Html::link('about', 'About Us') }}}
 *
 * Usage Categories:
 *
 *   1. ESCAPING & SECURITY
 *      - escape()       Escape HTML entities (XSS prevention)
 *      - entities()     Decode HTML entities
 *      - strip()        Remove HTML tags
 *
 *   2. LINKS & MEDIA
 *      - link()         Generate <a> tag
 *      - image()        Generate <img> tag
 *      - mailto()       Generate mailto link
 *
 *   3. ASSETS
 *      - script()       Generate <script> tag
 *      - style()        Generate <link rel="stylesheet"> tag
 *
 *   4. META & HEAD
 *      - meta()         Generate <meta> tag
 *      - favicon()      Generate favicon link
 *
 *   5. LISTS
 *      - ul()           Generate unordered list
 *      - ol()           Generate ordered list
 *      - dl()           Generate definition list
 *
 *   6. UTILITIES
 *      - attributes()   Build HTML attributes from array
 *      - tag()          Generate custom HTML tag
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2030 Geoffrey Okongo
 * @category Rackage
 * @package Rackage\Html
 * @link https://github.com/glivers/rackage
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 2.0.1
 */
class Html {

    /**
     * Private constructor to prevent instantiation
     * @return void
     */
    private function __construct() {}

    /**
     * Prevent cloning
     * @return void
     */
    private function __clone() {}

    // =========================================================================
    // ESCAPING & SECURITY
    // =========================================================================

    /**
     * Escape HTML special characters to prevent XSS
     *
     * Converts < > & " ' to HTML entities.
     * Use this for all user input displayed in HTML.
     *
     * Examples:
     *   echo Html::escape($userInput);
     *   echo Html::escape('<script>alert("XSS")</script>');
     *   // Outputs: &lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;
     *
     * In views (automatic via {{ }} tags):
     *   {{ $userInput }}  // Auto-escaped
     *
     * @param string $string String to escape
     * @return string Escaped string safe for HTML output
     */
    public static function escape($string)
    {
        if ($string === null) {
            return '';
        }

        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Decode HTML entities back to characters
     *
     * Converts HTML entities back to their original characters.
     *
     * Examples:
     *   $decoded = Html::entities('&lt;p&gt;Hello&lt;/p&gt;');
     *   // Returns: "<p>Hello</p>"
     *
     * @param string $string String with HTML entities
     * @return string Decoded string
     */
    public static function entities($string)
    {
        if ($string === null) {
            return '';
        }

        return html_entity_decode($string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Remove HTML and PHP tags from string
     *
     * Strips all HTML/PHP tags. Optionally allow specific tags.
     *
     * Examples:
     *   $clean = Html::strip('<p>Hello <b>World</b></p>');
     *   // Returns: "Hello World"
     *
     *   $clean = Html::strip('<p>Hello <b>World</b></p>', '<b>');
     *   // Returns: "Hello <b>World</b>"
     *
     * @param string $string String to clean
     * @param string $allowedTags Tags to allow (e.g., '<b><i>')
     * @return string Cleaned string
     */
    public static function strip($string, $allowedTags = '')
    {
        if ($string === null) {
            return '';
        }

        return strip_tags($string, $allowedTags);
    }

    // =========================================================================
    // LINKS & MEDIA
    // =========================================================================

    /**
     * Generate HTML link (anchor tag)
     *
     * Creates an <a> tag with proper URL and attributes.
     *
     * Examples:
     *   Html::link('about', 'About Us');
     *   // <a href="http://site.com/about">About Us</a>
     *
     *   Html::link('contact', 'Contact', ['class' => 'btn']);
     *   // <a href="http://site.com/contact" class="btn">Contact</a>
     *
     *   Html::link('profile', 'My Profile', ['id' => 'profile-link', 'data-user' => '123']);
     *   // <a href="http://site.com/profile" id="profile-link" data-user="123">My Profile</a>
     *
     *   Html::link('https://google.com', 'Google', ['target' => '_blank']);
     *   // <a href="https://google.com" target="_blank">Google</a>
     *
     * @param string $url URL or path to link to
     * @param string $text Link text
     * @param array $attributes HTML attributes
     * @return string HTML link tag
     */
    public static function link($url, $text, array $attributes = [])
    {
        // Use Url helper for internal links, keep external URLs as-is
        if (!preg_match('/^https?:\/\//', $url)) {
            $url = Url::link($url);
        }

        $attributes['href'] = $url;
        $attrString = self::attributes($attributes);

        return '<a' . $attrString . '>' . self::escape($text) . '</a>';
    }

    /**
     * Generate image tag
     *
     * Creates an <img> tag with proper attributes.
     *
     * Examples:
     *   Html::image('photo.jpg', 'My Photo');
     *   // <img src="http://site.com/public/assets/photo.jpg" alt="My Photo">
     *
     *   Html::image('logo.png', 'Logo', ['class' => 'logo', 'width' => '200']);
     *   // <img src="..." alt="Logo" class="logo" width="200">
     *
     *   Html::image('https://example.com/image.jpg', 'External Image');
     *   // <img src="https://example.com/image.jpg" alt="External Image">
     *
     * @param string $src Image source (path or URL)
     * @param string $alt Alt text
     * @param array $attributes HTML attributes
     * @return string HTML img tag
     */
    public static function image($src, $alt = '', array $attributes = [])
    {
        // Use Url::assets() for relative paths, keep external URLs as-is
        if (!preg_match('/^https?:\/\//', $src)) {
            $src = Url::assets($src);
        }

        $attributes['src'] = $src;
        $attributes['alt'] = $alt;
        $attrString = self::attributes($attributes);

        return '<img' . $attrString . '>';
    }

    /**
     * Generate mailto link
     *
     * Creates a mailto: link with optional text.
     *
     * Examples:
     *   Html::mailto('hello@example.com');
     *   // <a href="mailto:hello@example.com">hello@example.com</a>
     *
     *   Html::mailto('support@example.com', 'Contact Support');
     *   // <a href="mailto:support@example.com">Contact Support</a>
     *
     *   Html::mailto('info@example.com', 'Email Us', ['class' => 'email-link']);
     *   // <a href="mailto:info@example.com" class="email-link">Email Us</a>
     *
     * @param string $email Email address
     * @param string $text Link text (defaults to email address)
     * @param array $attributes HTML attributes
     * @return string HTML mailto link
     */
    public static function mailto($email, $text = null, array $attributes = [])
    {
        $text = $text ?? $email;
        $attributes['href'] = 'mailto:' . $email;
        $attrString = self::attributes($attributes);

        return '<a' . $attrString . '>' . self::escape($text) . '</a>';
    }

    // =========================================================================
    // ASSETS
    // =========================================================================

    /**
     * Generate script tag
     *
     * Creates a <script> tag for JavaScript files.
     *
     * Examples:
     *   Html::script('app.js');
     *   // <script src="http://site.com/public/assets/app.js"></script>
     *
     *   Html::script('https://cdn.example.com/lib.js');
     *   // <script src="https://cdn.example.com/lib.js"></script>
     *
     *   Html::script('custom.js', ['defer' => true]);
     *   // <script src="..." defer></script>
     *
     * @param string $src Script source (path or URL)
     * @param array $attributes HTML attributes
     * @return string HTML script tag
     */
    public static function script($src, array $attributes = [])
    {
        // Use Url::assets() for relative paths, keep external URLs as-is
        if (!preg_match('/^https?:\/\//', $src)) {
            $src = Url::assets($src);
        }

        $attributes['src'] = $src;
        $attrString = self::attributes($attributes);

        return '<script' . $attrString . '></script>';
    }

    /**
     * Generate stylesheet link tag
     *
     * Creates a <link rel="stylesheet"> tag for CSS files.
     *
     * Examples:
     *   Html::style('app.css');
     *   // <link rel="stylesheet" href="http://site.com/public/assets/app.css">
     *
     *   Html::style('https://cdn.example.com/lib.css');
     *   // <link rel="stylesheet" href="https://cdn.example.com/lib.css">
     *
     *   Html::style('print.css', ['media' => 'print']);
     *   // <link rel="stylesheet" href="..." media="print">
     *
     * @param string $href Stylesheet source (path or URL)
     * @param array $attributes HTML attributes
     * @return string HTML link tag
     */
    public static function style($href, array $attributes = [])
    {
        // Use Url::assets() for relative paths, keep external URLs as-is
        if (!preg_match('/^https?:\/\//', $href)) {
            $href = Url::assets($href);
        }

        $attributes['rel'] = 'stylesheet';
        $attributes['href'] = $href;
        $attrString = self::attributes($attributes);

        return '<link' . $attrString . '>';
    }

    // =========================================================================
    // META & HEAD
    // =========================================================================

    /**
     * Generate meta tag
     *
     * Creates a <meta> tag with name and content.
     *
     * Examples:
     *   Html::meta('description', 'My awesome website');
     *   // <meta name="description" content="My awesome website">
     *
     *   Html::meta('viewport', 'width=device-width, initial-scale=1');
     *   // <meta name="viewport" content="width=device-width, initial-scale=1">
     *
     *   Html::meta('og:title', 'Page Title', ['property' => 'og:title']);
     *   // <meta property="og:title" content="Page Title">
     *
     * @param string $name Meta name
     * @param string $content Meta content
     * @param array $attributes Additional attributes
     * @return string HTML meta tag
     */
    public static function meta($name, $content, array $attributes = [])
    {
        // If 'property' not set, use 'name'
        if (!isset($attributes['property'])) {
            $attributes['name'] = $name;
        }

        $attributes['content'] = $content;
        $attrString = self::attributes($attributes);

        return '<meta' . $attrString . '>';
    }

    /**
     * Generate favicon link
     *
     * Creates a favicon <link> tag.
     *
     * Examples:
     *   Html::favicon('favicon.ico');
     *   // <link rel="icon" type="image/x-icon" href="http://site.com/public/assets/favicon.ico">
     *
     *   Html::favicon('favicon.png', 'image/png');
     *   // <link rel="icon" type="image/png" href="...">
     *
     * @param string $href Favicon path
     * @param string $type MIME type (default: image/x-icon)
     * @return string HTML link tag
     */
    public static function favicon($href, $type = 'image/x-icon')
    {
        if (!preg_match('/^https?:\/\//', $href)) {
            $href = Url::assets($href);
        }

        return '<link rel="icon" type="' . $type . '" href="' . $href . '">';
    }

    // =========================================================================
    // LISTS
    // =========================================================================

    /**
     * Generate unordered list (ul)
     *
     * Creates a <ul> with list items from array.
     *
     * Examples:
     *   Html::ul(['Apple', 'Banana', 'Cherry']);
     *   // <ul><li>Apple</li><li>Banana</li><li>Cherry</li></ul>
     *
     *   Html::ul(['Home', 'About', 'Contact'], ['class' => 'nav']);
     *   // <ul class="nav"><li>Home</li><li>About</li><li>Contact</li></ul>
     *
     * @param array $items List items
     * @param array $attributes HTML attributes for <ul>
     * @return string HTML unordered list
     */
    public static function ul(array $items, array $attributes = [])
    {
        return self::listing('ul', $items, $attributes);
    }

    /**
     * Generate ordered list (ol)
     *
     * Creates an <ol> with list items from array.
     *
     * Examples:
     *   Html::ol(['First', 'Second', 'Third']);
     *   // <ol><li>First</li><li>Second</li><li>Third</li></ol>
     *
     *   Html::ol(['Step 1', 'Step 2'], ['class' => 'steps', 'start' => '5']);
     *   // <ol class="steps" start="5"><li>Step 1</li><li>Step 2</li></ol>
     *
     * @param array $items List items
     * @param array $attributes HTML attributes for <ol>
     * @return string HTML ordered list
     */
    public static function ol(array $items, array $attributes = [])
    {
        return self::listing('ol', $items, $attributes);
    }

    /**
     * Generate definition list (dl)
     *
     * Creates a <dl> with terms and definitions.
     *
     * Examples:
     *   Html::dl(['Term 1' => 'Definition 1', 'Term 2' => 'Definition 2']);
     *   // <dl><dt>Term 1</dt><dd>Definition 1</dd><dt>Term 2</dt><dd>Definition 2</dd></dl>
     *
     * @param array $items Associative array of term => definition
     * @param array $attributes HTML attributes for <dl>
     * @return string HTML definition list
     */
    public static function dl(array $items, array $attributes = [])
    {
        $html = '<dl' . self::attributes($attributes) . '>';

        foreach ($items as $term => $definition) {
            $html .= '<dt>' . self::escape($term) . '</dt>';
            $html .= '<dd>' . self::escape($definition) . '</dd>';
        }

        $html .= '</dl>';

        return $html;
    }

    /**
     * Internal method for generating ul/ol lists
     *
     * @param string $type List type (ul or ol)
     * @param array $items List items
     * @param array $attributes HTML attributes
     * @return string HTML list
     */
    private static function listing($type, array $items, array $attributes = [])
    {
        $html = '<' . $type . self::attributes($attributes) . '>';

        foreach ($items as $item) {
            $html .= '<li>' . self::escape($item) . '</li>';
        }

        $html .= '</' . $type . '>';

        return $html;
    }

    // =========================================================================
    // UTILITIES
    // =========================================================================

    /**
     * Build HTML attributes from array
     *
     * Converts associative array to HTML attribute string.
     * Boolean attributes (like 'disabled', 'checked') are handled properly.
     *
     * Examples:
     *   Html::attributes(['class' => 'btn', 'id' => 'submit']);
     *   // ' class="btn" id="submit"'
     *
     *   Html::attributes(['disabled' => true, 'data-value' => '123']);
     *   // ' disabled data-value="123"'
     *
     *   Html::attributes(['checked' => false, 'name' => 'agree']);
     *   // ' name="agree"' (false attributes are omitted)
     *
     * @param array $attributes Associative array of attributes
     * @return string HTML attribute string (starts with space if not empty)
     */
    public static function attributes(array $attributes)
    {
        $html = [];

        foreach ($attributes as $key => $value) {
            // Skip null values
            if ($value === null) {
                continue;
            }

            // Boolean attributes (disabled, checked, selected, etc.)
            if (is_bool($value)) {
                if ($value) {
                    $html[] = $key;  // Just the attribute name
                }
                continue;
            }

            // Regular attributes
            $html[] = $key . '="' . self::escape($value) . '"';
        }

        return count($html) > 0 ? ' ' . implode(' ', $html) : '';
    }

    /**
     * Generate custom HTML tag
     *
     * Creates any HTML tag with content and attributes.
     *
     * Examples:
     *   Html::tag('p', 'Hello World');
     *   // <p>Hello World</p>
     *
     *   Html::tag('div', 'Content', ['class' => 'container']);
     *   // <div class="container">Content</div>
     *
     *   Html::tag('span', 'Label', ['id' => 'label', 'data-id' => '5']);
     *   // <span id="label" data-id="5">Label</span>
     *
     *   Html::tag('br', null);  // Self-closing
     *   // <br>
     *
     * @param string $tag Tag name
     * @param string|null $content Tag content (null for self-closing)
     * @param array $attributes HTML attributes
     * @return string HTML tag
     */
    public static function tag($tag, $content = null, array $attributes = [])
    {
        $attrString = self::attributes($attributes);

        if ($content === null) {
            // Self-closing tag
            return '<' . $tag . $attrString . '>';
        }

        return '<' . $tag . $attrString . '>' . $content . '</' . $tag . '>';
    }
}
