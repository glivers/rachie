<?php namespace Rackage;

/**
 * Validation Helper
 *
 * Provides data validation with a fluent interface for form validation,
 * API input validation, and data integrity checks.
 *
 * Static Design:
 *   Main method is static - returns ValidationResult object for chaining.
 *
 * Rule Format:
 *   Rules are pipe-separated strings: 'required|email|min:8|max:100'
 *   Multiple rules per field: ['email' => 'required|email', 'age' => 'numeric|min:18']
 *
 * Usage Patterns:
 *
 *   // Basic validation
 *   $validator = Validation::make($data, [
 *       'email' => 'required|email',
 *       'password' => 'required|min:8',
 *   ]);
 *
 *   if ($validator->fails()) {
 *       $errors = $validator->errors();
 *   }
 *
 *   // Check if validation passed
 *   if ($validator->passes()) {
 *       // Process data
 *   }
 *
 *   // Get first error for a field
 *   $emailError = $validator->first('email');
 *
 *   // Get all errors
 *   $allErrors = $validator->errors();
 *
 * Available Rules:
 *   - required         - Field must be present and not empty
 *   - email            - Valid email address
 *   - numeric          - Numeric value (int or float)
 *   - integer          - Integer value
 *   - string           - String value
 *   - boolean          - Boolean value (true, false, 1, 0, "1", "0")
 *   - array            - Array value
 *   - url              - Valid URL
 *   - ip               - Valid IP address
 *   - alpha            - Alphabetic characters only
 *   - alpha_num        - Alphanumeric characters only
 *   - alpha_dash       - Alphanumeric with dashes and underscores
 *   - min:N            - Minimum length (strings) or value (numbers)
 *   - max:N            - Maximum length (strings) or value (numbers)
 *   - between:min,max  - Between min and max (length or value)
 *   - in:foo,bar,baz   - Value must be in list
 *   - not_in:foo,bar   - Value must not be in list
 *   - same:field       - Must match another field
 *   - different:field  - Must differ from another field
 *   - confirmed        - Must have matching {field}_confirmation field
 *   - regex:/pattern/  - Must match regex pattern
 *   - date             - Valid date string
 *   - json             - Valid JSON string
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2030 Geoffrey Okongo
 * @category Rackage
 * @package Rackage\Validation
 * @link https://github.com/glivers/rackage
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 2.0.1
 */
class Validation {

    /**
     * Create a new validator instance
     *
     * Validates data against the provided rules and returns a ValidationResult
     * object that can be queried for errors.
     *
     * Examples:
     *   $v = Validation::make(Input::get(), [
     *       'email' => 'required|email',
     *       'age' => 'required|numeric|min:18',
     *       'password' => 'required|min:8|confirmed',
     *   ]);
     *
     *   if ($v->fails()) {
     *       foreach ($v->errors() as $field => $errors) {
     *           echo "$field: " . implode(', ', $errors);
     *       }
     *   }
     *
     * @param array $data Data to validate
     * @param array $rules Validation rules per field
     * @return ValidationResult Validation result object
     */
    public static function make(array $data, array $rules)
    {
        $errors = [];

        foreach ($rules as $field => $ruleString) {
            $fieldRules = explode('|', $ruleString);

            foreach ($fieldRules as $rule) {
                $error = self::validateRule($data, $field, $rule);
                if ($error !== null) {
                    $errors[$field][] = $error;
                }
            }
        }

        return new ValidationResult($errors);
    }

    /**
     * Validate a single rule for a field
     *
     * @param array $data The data being validated
     * @param string $field The field name
     * @param string $rule The rule to validate
     * @return string|null Error message or null if valid
     */
    private static function validateRule(array $data, $field, $rule)
    {
        $value = $data[$field] ?? null;

        // Parse rule and parameters
        if (strpos($rule, ':') !== false) {
            list($ruleName, $parameters) = explode(':', $rule, 2);
        } else {
            $ruleName = $rule;
            $parameters = null;
        }

        // Required check
        if ($ruleName === 'required') {
            if (empty($value) && $value !== '0' && $value !== 0) {
                return "$field is required";
            }
        }

        // Skip other validations if field is not present and not required
        if (!isset($data[$field]) && $ruleName !== 'required') {
            return null;
        }

        // Email validation
        if ($ruleName === 'email') {
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return "$field must be a valid email address";
            }
        }

        // Numeric validation
        if ($ruleName === 'numeric') {
            if (!is_numeric($value)) {
                return "$field must be numeric";
            }
        }

        // Integer validation
        if ($ruleName === 'integer') {
            if (!filter_var($value, FILTER_VALIDATE_INT) && $value !== 0 && $value !== '0') {
                return "$field must be an integer";
            }
        }

        // String validation
        if ($ruleName === 'string') {
            if (!is_string($value)) {
                return "$field must be a string";
            }
        }

        // Boolean validation
        if ($ruleName === 'boolean') {
            $valid = in_array($value, [true, false, 1, 0, '1', '0'], true);
            if (!$valid) {
                return "$field must be a boolean";
            }
        }

        // Array validation
        if ($ruleName === 'array') {
            if (!is_array($value)) {
                return "$field must be an array";
            }
        }

        // URL validation
        if ($ruleName === 'url') {
            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                return "$field must be a valid URL";
            }
        }

        // IP validation
        if ($ruleName === 'ip') {
            if (!filter_var($value, FILTER_VALIDATE_IP)) {
                return "$field must be a valid IP address";
            }
        }

        // Alpha (letters only)
        if ($ruleName === 'alpha') {
            if (!preg_match('/^[a-zA-Z]+$/', $value)) {
                return "$field must contain only letters";
            }
        }

        // Alpha-numeric
        if ($ruleName === 'alpha_num') {
            if (!preg_match('/^[a-zA-Z0-9]+$/', $value)) {
                return "$field must contain only letters and numbers";
            }
        }

        // Alpha-dash (alphanumeric with dashes and underscores)
        if ($ruleName === 'alpha_dash') {
            if (!preg_match('/^[a-zA-Z0-9_-]+$/', $value)) {
                return "$field must contain only letters, numbers, dashes, and underscores";
            }
        }

        // Min validation (length for strings, value for numbers)
        if ($ruleName === 'min') {
            $min = (int)$parameters;
            if (is_numeric($value)) {
                if ($value < $min) {
                    return "$field must be at least $min";
                }
            } else {
                if (strlen($value) < $min) {
                    return "$field must be at least $min characters";
                }
            }
        }

        // Max validation (length for strings, value for numbers)
        if ($ruleName === 'max') {
            $max = (int)$parameters;
            if (is_numeric($value)) {
                if ($value > $max) {
                    return "$field must not exceed $max";
                }
            } else {
                if (strlen($value) > $max) {
                    return "$field must not exceed $max characters";
                }
            }
        }

        // Between validation
        if ($ruleName === 'between') {
            list($min, $max) = explode(',', $parameters);
            $min = (int)$min;
            $max = (int)$max;

            if (is_numeric($value)) {
                if ($value < $min || $value > $max) {
                    return "$field must be between $min and $max";
                }
            } else {
                $len = strlen($value);
                if ($len < $min || $len > $max) {
                    return "$field must be between $min and $max characters";
                }
            }
        }

        // In list validation
        if ($ruleName === 'in') {
            $list = explode(',', $parameters);
            if (!in_array($value, $list, true)) {
                return "$field must be one of: " . implode(', ', $list);
            }
        }

        // Not in list validation
        if ($ruleName === 'not_in') {
            $list = explode(',', $parameters);
            if (in_array($value, $list, true)) {
                return "$field must not be one of: " . implode(', ', $list);
            }
        }

        // Same as another field
        if ($ruleName === 'same') {
            $otherField = $parameters;
            $otherValue = $data[$otherField] ?? null;
            if ($value !== $otherValue) {
                return "$field must match $otherField";
            }
        }

        // Different from another field
        if ($ruleName === 'different') {
            $otherField = $parameters;
            $otherValue = $data[$otherField] ?? null;
            if ($value === $otherValue) {
                return "$field must be different from $otherField";
            }
        }

        // Confirmed (must have matching {field}_confirmation)
        if ($ruleName === 'confirmed') {
            $confirmField = $field . '_confirmation';
            $confirmValue = $data[$confirmField] ?? null;
            if ($value !== $confirmValue) {
                return "$field confirmation does not match";
            }
        }

        // Regex pattern matching
        if ($ruleName === 'regex') {
            if (!preg_match($parameters, $value)) {
                return "$field format is invalid";
            }
        }

        // Date validation
        if ($ruleName === 'date') {
            if (strtotime($value) === false) {
                return "$field must be a valid date";
            }
        }

        // JSON validation
        if ($ruleName === 'json') {
            json_decode($value);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return "$field must be valid JSON";
            }
        }

        return null;
    }
}

/**
 * ValidationResult
 *
 * Represents the result of a validation operation.
 * Provides methods to check validation status and retrieve errors.
 *
 * Usage:
 *   $result = Validate::make($data, $rules);
 *
 *   if ($result->fails()) {
 *       $errors = $result->errors();
 *   }
 *
 *   if ($result->passes()) {
 *       // Process data
 *   }
 */
class ValidationResult {

    /**
     * Validation errors
     * @var array
     */
    private $errors = [];

    /**
     * Create validation result
     *
     * @param array $errors Validation errors
     */
    public function __construct(array $errors = [])
    {
        $this->errors = $errors;
    }

    /**
     * Check if validation failed
     *
     * Example:
     *   if ($validator->fails()) {
     *       return View::json($validator->errors(), 422);
     *   }
     *
     * @return bool True if validation failed
     */
    public function fails()
    {
        return !empty($this->errors);
    }

    /**
     * Check if validation passed
     *
     * Example:
     *   if ($validator->passes()) {
     *       User::create($data);
     *   }
     *
     * @return bool True if validation passed
     */
    public function passes()
    {
        return empty($this->errors);
    }

    /**
     * Get all validation errors
     *
     * Returns array of errors grouped by field:
     *   [
     *       'email' => ['email is required', 'email must be valid'],
     *       'age' => ['age must be at least 18']
     *   ]
     *
     * Example:
     *   foreach ($validator->errors() as $field => $messages) {
     *       echo "$field: " . implode(', ', $messages);
     *   }
     *
     * @return array Validation errors
     */
    public function errors()
    {
        return $this->errors;
    }

    /**
     * Get first error message for a field
     *
     * Returns the first error message for the specified field,
     * or null if field has no errors.
     *
     * Example:
     *   $emailError = $validator->first('email');
     *   if ($emailError) {
     *       echo $emailError;
     *   }
     *
     * @param string $field Field name
     * @return string|null First error message or null
     */
    public function first($field)
    {
        return $this->errors[$field][0] ?? null;
    }

    /**
     * Check if a specific field has errors
     *
     * Example:
     *   if ($validator->has('email')) {
     *       echo $validator->first('email');
     *   }
     *
     * @param string $field Field name
     * @return bool True if field has errors
     */
    public function has($field)
    {
        return isset($this->errors[$field]);
    }
}
