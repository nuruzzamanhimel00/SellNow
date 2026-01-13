<?php

namespace SellNow\Validation;

/**
 * Input Validation Service
 * 
 * Provides validation rules for user input.
 * Returns validation errors for display to users.
 * 
 * @package SellNow\Validation
 */
class Validator
{
    /**
     * Validation errors
     * @var array
     */
    private array $errors = [];

    /**
     * Data to validate
     * @var array
     */
    private array $data = [];

    /**
     * Constructor
     * 
     * @param array $data Data to validate
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Validate data against rules
     * 
     * @param array $rules Validation rules (field => rules)
     * @return bool True if validation passes
     */
    public function validate(array $rules): bool
    {
        $this->errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $this->data[$field] ?? null;
            $rulesArray = explode('|', $fieldRules);

            foreach ($rulesArray as $rule) {
                $this->applyRule($field, $value, $rule);
            }
        }

        return empty($this->errors);
    }

    /**
     * Apply a validation rule
     * 
     * @param string $field Field name
     * @param mixed $value Field value
     * @param string $rule Rule to apply
     * @return void
     */
    private function applyRule(string $field, $value, string $rule): void
    {
        // Parse rule and parameters (e.g., "min:3" => rule="min", param="3")
        $parts = explode(':', $rule, 2);
        $ruleName = $parts[0];
        $ruleParam = $parts[1] ?? null;

        switch ($ruleName) {
            case 'required':
                if (empty($value) && $value !== '0') {
                    $this->addError($field, "The {$field} field is required.");
                }
                break;

            case 'email':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, "The {$field} must be a valid email address.");
                }
                break;

            case 'min':
                if (!empty($value) && strlen($value) < (int)$ruleParam) {
                    $this->addError($field, "The {$field} must be at least {$ruleParam} characters.");
                }
                break;

            case 'max':
                if (!empty($value) && strlen($value) > (int)$ruleParam) {
                    $this->addError($field, "The {$field} must not exceed {$ruleParam} characters.");
                }
                break;

            case 'numeric':
                if (!empty($value) && !is_numeric($value)) {
                    $this->addError($field, "The {$field} must be a number.");
                }
                break;

            case 'integer':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_INT)) {
                    $this->addError($field, "The {$field} must be an integer.");
                }
                break;

            case 'alpha':
                if (!empty($value) && !ctype_alpha($value)) {
                    $this->addError($field, "The {$field} must contain only letters.");
                }
                break;

            case 'alphanumeric':
                if (!empty($value) && !ctype_alnum($value)) {
                    $this->addError($field, "The {$field} must contain only letters and numbers.");
                }
                break;

            case 'url':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                    $this->addError($field, "The {$field} must be a valid URL.");
                }
                break;

            case 'confirmed':
                $confirmField = $field . '_confirmation';
                if ($value !== ($this->data[$confirmField] ?? null)) {
                    $this->addError($field, "The {$field} confirmation does not match.");
                }
                break;

            case 'unique':
                // This would require database check - handled in service layer
                break;

            default:
                // Unknown rule - ignore
                break;
        }
    }

    /**
     * Add a validation error
     * 
     * @param string $field Field name
     * @param string $message Error message
     * @return void
     */
    private function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }

        $this->errors[$field][] = $message;
    }

    /**
     * Get all validation errors
     * 
     * @return array Validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get errors for a specific field
     * 
     * @param string $field Field name
     * @return array Field errors
     */
    public function getFieldErrors(string $field): array
    {
        return $this->errors[$field] ?? [];
    }

    /**
     * Get first error for a field
     * 
     * @param string $field Field name
     * @return string|null First error message or null
     */
    public function getFirstError(string $field): ?string
    {
        $errors = $this->getFieldErrors($field);
        return $errors[0] ?? null;
    }

    /**
     * Check if validation has errors
     * 
     * @return bool True if has errors
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Get all errors as a flat array
     * 
     * @return array Flat array of error messages
     */
    public function getAllMessages(): array
    {
        $messages = [];
        
        foreach ($this->errors as $fieldErrors) {
            $messages = array_merge($messages, $fieldErrors);
        }

        return $messages;
    }
}
