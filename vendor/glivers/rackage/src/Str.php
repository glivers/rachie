<?php namespace Rackage;

/**
 * String Helper
 *
 * Provides string manipulation utilities for common operations like
 * case conversion, truncation, slugification, and pattern matching.
 *
 * Static Design:
 *   All methods are static - no instance creation required.
 *
 * Usage Patterns: 
 *
 *   // Case conversion
 *   Str::slug('My Blog Post');        // my-blog-post
 *   Str::camel('user_name');          // userName
 *   Str::snake('userName');           // user_name
 *   Str::studly('user_name');         // UserName
 *   Str::title('hello world');        // Hello World
 *
 *   // Truncation
 *   Str::limit('Long text...', 10);   // Long text...
 *   Str::words('One two three', 2);   // One two...
 *
 *   // Checks
 *   Str::startsWith('Hello', 'He');   // true
 *   Str::endsWith('World', 'ld');     // true
 *   Str::contains('Hello', 'ell');    // true
 *
 *   // Extraction
 *   Str::after('name@domain.com', '@');    // domain.com
 *   Str::before('name@domain.com', '@');   // name
 *   Str::between('[text]', '[', ']');      // text
 *
 *   // Manipulation
 *   Str::replace('foo', 'bar', 'foo is foo');  // bar is bar
 *   Str::remove('world', 'hello world');       // hello
 *   Str::repeat('ab', 3);                      // ababab
 *   Str::reverse('hello');                     // olleh
 *
 *   // Pluralization
 *   Str::plural('child');      // children
 *   Str::singular('children'); // child
 *
 *   // Random generation
 *   Str::random(16);           // jD8sK3mP9nQ2xR4t
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2030 Geoffrey Okongo
 * @category Rackage
 * @package Rackage\Str
 * @link https://github.com/glivers/rackage
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 2.0.1
 */
class Str {

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
    // CASE CONVERSION
    // =========================================================================

    /**
     * Convert string to URL-friendly slug
     *
     * Converts string to lowercase, replaces spaces and special characters
     * with hyphens, and removes multiple consecutive hyphens.
     *
     * Examples:
     *   Str::slug('My Blog Post');           // my-blog-post
     *   Str::slug('Hello World!');           // hello-world
     *   Str::slug('Product #123');           // product-123
     *   Str::slug('Special   Spaces');       // special-spaces
     *
     * @param string $string String to convert
     * @param string $separator Separator character (default '-')
     * @return string URL-friendly slug
     */
    public static function slug($string, $separator = '-')
    {
        $string = self::lower($string);
        $string = preg_replace('/[^a-z0-9]+/', $separator, $string);
        $string = trim($string, $separator);
        return preg_replace('/' . preg_quote($separator) . '+/', $separator, $string);
    }

    /**
     * Convert string to camelCase
     *
     * Examples:
     *   Str::camel('user_name');       // userName
     *   Str::camel('first-name');      // firstName
     *   Str::camel('hello world');     // helloWorld
     *
     * @param string $string String to convert
     * @return string camelCase string
     */
    public static function camel($string)
    {
        $string = self::studly($string);
        return self::lcfirst($string);
    }

    /**
     * Convert string to snake_case
     *
     * Examples:
     *   Str::snake('userName');        // user_name
     *   Str::snake('FirstName');       // first_name
     *   Str::snake('helloWorld');      // hello_world
     *
     * @param string $string String to convert
     * @param string $delimiter Delimiter (default '_')
     * @return string snake_case string
     */
    public static function snake($string, $delimiter = '_')
    {
        $string = preg_replace('/\s+/', '', $string);
        $string = preg_replace('/(.)(?=[A-Z])/u', '$1' . $delimiter, $string);
        return self::lower($string);
    }

    /**
     * Convert string to StudlyCase (PascalCase)
     *
     * Examples:
     *   Str::studly('user_name');      // UserName
     *   Str::studly('first-name');     // FirstName
     *   Str::studly('hello world');    // HelloWorld
     *
     * @param string $string String to convert
     * @return string StudlyCase string
     */
    public static function studly($string)
    {
        $string = str_replace(['-', '_'], ' ', $string);
        $string = ucwords($string);
        return str_replace(' ', '', $string);
    }

    /**
     * Convert string to kebab-case
     *
     * Examples:
     *   Str::kebab('userName');        // user-name
     *   Str::kebab('FirstName');       // first-name
     *   Str::kebab('hello world');     // hello-world
     *
     * @param string $string String to convert
     * @return string kebab-case string
     */
    public static function kebab($string)
    {
        return self::snake($string, '-');
    }

    /**
     * Convert string to UPPERCASE
     *
     * Example:
     *   Str::upper('hello');  // HELLO
     *
     * @param string $string String to convert
     * @return string Uppercase string
     */
    public static function upper($string)
    {
        return mb_strtoupper($string, 'UTF-8');
    }

    /**
     * Convert string to lowercase
     *
     * Example:
     *   Str::lower('HELLO');  // hello
     *
     * @param string $string String to convert
     * @return string Lowercase string
     */
    public static function lower($string)
    {
        return mb_strtolower($string, 'UTF-8');
    }

    /**
     * Convert string to Title Case
     *
     * Examples:
     *   Str::title('hello world');     // Hello World
     *   Str::title('the quick fox');   // The Quick Fox
     *
     * @param string $string String to convert
     * @return string Title case string
     */
    public static function title($string)
    {
        return mb_convert_case($string, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Capitalize first character
     *
     * Example:
     *   Str::ucfirst('hello');  // Hello
     *
     * @param string $string String to capitalize
     * @return string String with first character capitalized
     */
    public static function ucfirst($string)
    {
        return self::upper(mb_substr($string, 0, 1)) . mb_substr($string, 1);
    }

    /**
     * Lowercase first character
     *
     * Example:
     *   Str::lcfirst('Hello');  // hello
     *
     * @param string $string String to modify
     * @return string String with first character lowercased
     */
    public static function lcfirst($string)
    {
        return self::lower(mb_substr($string, 0, 1)) . mb_substr($string, 1);
    }

    // =========================================================================
    // TRUNCATION
    // =========================================================================

    /**
     * Limit string to specified length
     *
     * Truncates string to specified length and appends suffix if truncated.
     *
     * Examples:
     *   Str::limit('Hello World', 5);           // Hello...
     *   Str::limit('Hello World', 5, '');       // Hello
     *   Str::limit('Hello World', 20);          // Hello World
     *   Str::limit('Long text here', 8, '→');   // Long tex→
     *
     * @param string $string String to limit
     * @param int $limit Maximum length
     * @param string $end Suffix to append if truncated (default '...')
     * @return string Limited string
     */
    public static function limit($string, $limit = 100, $end = '...')
    {
        if (mb_strlen($string) <= $limit) {
            return $string;
        }

        return mb_substr($string, 0, $limit) . $end;
    }

    /**
     * Limit string to specified number of words
     *
     * Examples:
     *   Str::words('One two three four', 2);      // One two...
     *   Str::words('One two three', 5);           // One two three
     *   Str::words('One two three', 2, '');       // One two
     *
     * @param string $string String to limit
     * @param int $words Number of words
     * @param string $end Suffix to append if truncated (default '...')
     * @return string Limited string
     */
    public static function words($string, $words = 100, $end = '...')
    {
        preg_match('/^\s*+(?:\S++\s*+){1,' . $words . '}/u', $string, $matches);

        if (!isset($matches[0]) || mb_strlen($string) === mb_strlen($matches[0])) {
            return $string;
        }

        return rtrim($matches[0]) . $end;
    }

    // =========================================================================
    // CHECKS
    // =========================================================================

    /**
     * Check if string starts with substring
     *
     * Examples:
     *   Str::startsWith('Hello World', 'Hello');  // true
     *   Str::startsWith('Hello World', 'World');  // false
     *   Str::startsWith('Hello', 'hello');        // false (case-sensitive)
     *
     * @param string $haystack String to search in
     * @param string $needle Substring to search for
     * @return bool True if string starts with substring
     */
    public static function startsWith($haystack, $needle)
    {
        return $needle !== '' && mb_strpos($haystack, $needle) === 0;
    }

    /**
     * Check if string ends with substring
     *
     * Examples:
     *   Str::endsWith('Hello World', 'World');  // true
     *   Str::endsWith('Hello World', 'Hello');  // false
     *   Str::endsWith('World', 'world');        // false (case-sensitive)
     *
     * @param string $haystack String to search in
     * @param string $needle Substring to search for
     * @return bool True if string ends with substring
     */
    public static function endsWith($haystack, $needle)
    {
        return $needle !== '' && mb_substr($haystack, -mb_strlen($needle)) === $needle;
    }

    /**
     * Check if string contains substring
     *
     * Examples:
     *   Str::contains('Hello World', 'llo');   // true
     *   Str::contains('Hello World', 'xyz');   // false
     *   Str::contains('Hello', 'hello');       // false (case-sensitive)
     *
     * @param string $haystack String to search in
     * @param string $needle Substring to search for
     * @return bool True if string contains substring
     */
    public static function contains($haystack, $needle)
    {
        return $needle !== '' && mb_strpos($haystack, $needle) !== false;
    }

    // =========================================================================
    // EXTRACTION
    // =========================================================================

    /**
     * Get substring after first occurrence of delimiter
     *
     * Examples:
     *   Str::after('name@domain.com', '@');     // domain.com
     *   Str::after('hello-world', '-');         // world
     *   Str::after('no-delimiter', 'x');        // no-delimiter
     *
     * @param string $string String to search
     * @param string $delimiter Delimiter
     * @return string Substring after delimiter or original string
     */
    public static function after($string, $delimiter)
    {
        $pos = mb_strpos($string, $delimiter);

        if ($pos === false) {
            return $string;
        }

        return mb_substr($string, $pos + mb_strlen($delimiter));
    }

    /**
     * Get substring before first occurrence of delimiter
     *
     * Examples:
     *   Str::before('name@domain.com', '@');    // name
     *   Str::before('hello-world', '-');        // hello
     *   Str::before('no-delimiter', 'x');       // no-delimiter
     *
     * @param string $string String to search
     * @param string $delimiter Delimiter
     * @return string Substring before delimiter or original string
     */
    public static function before($string, $delimiter)
    {
        $pos = mb_strpos($string, $delimiter);

        if ($pos === false) {
            return $string;
        }

        return mb_substr($string, 0, $pos);
    }

    /**
     * Get substring between two delimiters
     *
     * Examples:
     *   Str::between('[text]', '[', ']');          // text
     *   Str::between('Hello (World)', '(', ')');   // World
     *   Str::between('no match', '[', ']');        // (empty string)
     *
     * @param string $string String to search
     * @param string $from Start delimiter
     * @param string $to End delimiter
     * @return string Substring between delimiters or empty string
     */
    public static function between($string, $from, $to)
    {
        $string = self::after($string, $from);
        return self::before($string, $to);
    }

    // =========================================================================
    // MANIPULATION
    // =========================================================================

    /**
     * Replace all occurrences of search with replacement
     *
     * Examples:
     *   Str::replace('o', '0', 'foo');           // f00
     *   Str::replace('world', 'universe', 'hello world');  // hello universe
     *
     * @param string $search String to search for
     * @param string $replace Replacement string
     * @param string $subject String to search in
     * @return string String with replacements
     */
    public static function replace($search, $replace, $subject)
    {
        return str_replace($search, $replace, $subject);
    }

    /**
     * Replace first occurrence of search with replacement
     *
     * Examples:
     *   Str::replaceFirst('o', '0', 'foo');     // f0o
     *   Str::replaceFirst('l', 'L', 'hello');   // heLlo
     *
     * @param string $search String to search for
     * @param string $replace Replacement string
     * @param string $subject String to search in
     * @return string String with first replacement
     */
    public static function replaceFirst($search, $replace, $subject)
    {
        $pos = mb_strpos($subject, $search);

        if ($pos === false) {
            return $subject;
        }

        return mb_substr($subject, 0, $pos) . $replace . mb_substr($subject, $pos + mb_strlen($search));
    }

    /**
     * Replace last occurrence of search with replacement
     *
     * Examples:
     *   Str::replaceLast('o', '0', 'foo');      // fo0
     *   Str::replaceLast('l', 'L', 'hello');    // helLo
     *
     * @param string $search String to search for
     * @param string $replace Replacement string
     * @param string $subject String to search in
     * @return string String with last replacement
     */
    public static function replaceLast($search, $replace, $subject)
    {
        $pos = mb_strrpos($subject, $search);

        if ($pos === false) {
            return $subject;
        }

        return mb_substr($subject, 0, $pos) . $replace . mb_substr($subject, $pos + mb_strlen($search));
    }

    /**
     * Remove all occurrences of substring
     *
     * Examples:
     *   Str::remove('world', 'hello world');   // hello
     *   Str::remove(' ', 'hello world');       // helloworld
     *
     * @param string $search String to remove
     * @param string $subject String to search in
     * @return string String with substring removed
     */
    public static function remove($search, $subject)
    {
        return self::replace($search, '', $subject);
    }

    /**
     * Repeat string N times
     *
     * Examples:
     *   Str::repeat('ab', 3);    // ababab
     *   Str::repeat('x', 5);     // xxxxx
     *
     * @param string $string String to repeat
     * @param int $times Number of times to repeat
     * @return string Repeated string
     */
    public static function repeat($string, $times)
    {
        return str_repeat($string, $times);
    }

    /**
     * Reverse string
     *
     * Examples:
     *   Str::reverse('hello');   // olleh
     *   Str::reverse('12345');   // 54321
     *
     * @param string $string String to reverse
     * @return string Reversed string
     */
    public static function reverse($string)
    {
        return strrev($string);
    }

    /**
     * Pad string on the left
     *
     * Examples:
     *   Str::padLeft('5', 3, '0');      // 005
     *   Str::padLeft('test', 10, '-');  // ------test
     *
     * @param string $string String to pad
     * @param int $length Total length after padding
     * @param string $pad Padding character (default ' ')
     * @return string Padded string
     */
    public static function padLeft($string, $length, $pad = ' ')
    {
        return str_pad($string, $length, $pad, STR_PAD_LEFT);
    }

    /**
     * Pad string on the right
     *
     * Examples:
     *   Str::padRight('5', 3, '0');      // 500
     *   Str::padRight('test', 10, '-');  // test------
     *
     * @param string $string String to pad
     * @param int $length Total length after padding
     * @param string $pad Padding character (default ' ')
     * @return string Padded string
     */
    public static function padRight($string, $length, $pad = ' ')
    {
        return str_pad($string, $length, $pad, STR_PAD_RIGHT);
    }

    /**
     * Pad string on both sides
     *
     * Examples:
     *   Str::padBoth('5', 5, '0');      // 00500
     *   Str::padBoth('test', 10, '-');  // ---test---
     *
     * @param string $string String to pad
     * @param int $length Total length after padding
     * @param string $pad Padding character (default ' ')
     * @return string Padded string
     */
    public static function padBoth($string, $length, $pad = ' ')
    {
        return str_pad($string, $length, $pad, STR_PAD_BOTH);
    }

    // =========================================================================
    // GENERATION
    // =========================================================================

    /**
     * Generate random string
     *
     * Generates a cryptographically secure random string using
     * alphanumeric characters (a-z, A-Z, 0-9).
     *
     * Examples:
     *   Str::random(16);  // jD8sK3mP9nQ2xR4t
     *   Str::random(8);   // aB3dE5gH
     *   Str::random(32);  // (32 character random string)
     *
     * @param int $length Length of random string (default 16)
     * @return string Random string
     */
    public static function random($length = 16)
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $string = '';

        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[random_int(0, 61)];
        }

        return $string;
    }

    // =========================================================================
    // PLURALIZATION
    // =========================================================================

    /**
     * Singular/plural patterns
     */
    private static $singulars = [
        "/(matr)ices$/" => "\\1ix",
        "/(vert|ind)ices$/" => "\\1ex",
        "/^(ox)en/" => "\\1",
        "/(alias)es$/" => "\\1",
        "/([octop|vir])i$/" => "\\1us",
        "/(cris|ax|test)es$/" => "\\1is",
        "/(shoe)s$/" => "\\1",
        "/(o)es$/" => "\\1",
        "/(bus|campus)es$/" => "\\1",
        "/([m|l])ice$/" => "\\1ouse",
        "/(x|ch|ss|sh)es$/" => "\\1",
        "/(m)ovies$/" => "\\1ovie",
        "/(s)eries$/" => "\\1eries",
        "/([^aeiouy]|qu)ies$/" => "\\1y",
        "/([lr])ves$/" => "\\1f",
        "/(tive)s$/" => "\\1",
        "/(hive)s$/" => "\\1",
        "/([^f])ves$/" => "\\1fe",
        "/(^analy)ses$/" => "\\1sis",
        "/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/" => "\\1sis",
        "/([ti])a$/" => "\\1um",
        "/(p)eople$/" => "\\1erson",
        "/(m)en$/" => "\\1an",
        "/(s)tatuses$/" => "\\1tatus",
        "/(c)hildren$/" => "\\1hild",
        "/(n)ews$/" => "\\1ews",
        "/([^u])s$/" => "\\1"
    ];

    private static $plurals = [
        "/^(ox)$/" => "\\1en",
        "/([m|l])ouse$/" => "\\1ice",
        "/(matr|vert|ind)ix|ex$/" => "\\1ices",
        "/(x|ch|ss|sh)$/" => "\\1es",
        "/([^aeiouy]|qu)y$/" => "\\1ies",
        "/(hive)$/" => "\\1s",
        "/(?:([^f])fe|([lr])f)$/" => "\\1\\2ves",
        "/sis$/" => "ses",
        "/([ti])um$/" => "\\1a",
        "/(p)erson$/" => "\\1eople",
        "/(m)an$/" => "\\1en",
        "/(c)hild$/" => "\\1hildren",
        "/(buffal|tomat)o$/" => "\\1oes",
        "/(bu|campu)s$/" => "\\1ses",
        "/(alias|status|virus)$/" => "\\1es",
        "/(octop)us$/" => "\\1i",
        "/(ax|cris|test)is$/" => "\\1es",
        "/s$/" => "s",
        "/$/" => "s"
    ];

    /**
     * Convert word to singular form
     *
     * Examples:
     *   Str::singular('children');  // child
     *   Str::singular('people');    // person
     *   Str::singular('mice');      // mouse
     *   Str::singular('tests');     // test
     *
     * @param string $string Word to convert
     * @return string Singular form
     */
    public static function singular($string)
    {
        foreach (self::$singulars as $pattern => $replacement) {
            if (preg_match($pattern, $string)) {
                return preg_replace($pattern, $replacement, $string);
            }
        }

        return $string;
    }

    /**
     * Convert word to plural form
     *
     * Examples:
     *   Str::plural('child');    // children
     *   Str::plural('person');   // people
     *   Str::plural('mouse');    // mice
     *   Str::plural('test');     // tests
     *
     * @param string $string Word to convert
     * @return string Plural form
     */
    public static function plural($string)
    {
        foreach (self::$plurals as $pattern => $replacement) {
            if (preg_match($pattern, $string)) {
                return preg_replace($pattern, $replacement, $string);
            }
        }

        return $string;
    }

    // =========================================================================
    // LEGACY METHODS (kept for backwards compatibility)
    // =========================================================================

    /**
     * Find position of substring
     *
     * Returns position of substring or -1 if not found.
     *
     * Examples:
     *   Str::indexOf('hello world', 'world');   // 6
     *   Str::indexOf('hello', 'xyz');           // -1
     *
     * @param string $string String to search in
     * @param string $substring Substring to find
     * @param int|null $offset Starting offset
     * @return int Position or -1 if not found
     */
    public static function indexOf($string, $substring, $offset = null)
    {
        $position = strpos($string, $substring, $offset);
        return is_int($position) ? $position : -1;
    }

    /**
     * Strip HTML/XML/PHP tags from string
     *
     * Example:
     *   Str::removeTags('<p>Hello</p>');  // Hello
     *
     * @param string $string String to clean
     * @return string String without tags
     */
    public static function removeTags($string)
    {
        return strip_tags($string);
    }
}
