<?php
/**
 * Validator
 *
 * Server-side validation with chainable rules.
 */

declare(strict_types=1);

namespace Core\Helpers;

class Validator
{
    /** @var array<string, string[]> Collected errors keyed by field name */
    private array $errors = [];

    /** @var array<string, mixed> Input data being validated */
    private array $data = [];

    /**
     * Load data to validate (usually $_POST).
     *
     * @param  array $data
     * @return static
     */
    public function load(array $data): static
    {
        $this->data   = $data;
        $this->errors = [];
        return $this;
    }

    /**
     * Assert a field is not empty.
     *
     * @param  string $field
     * @param  string $label
     * @return static
     */
    public function required(string $field, string $label = ''): static
    {
        $label = $label ?: ucfirst($field);
        $value = trim((string) ($this->data[$field] ?? ''));
        if ($value === '') {
            $this->errors[$field][] = "{$label} is required.";
        }
        return $this;
    }

    /**
     * Assert a field is a valid email address.
     *
     * @param  string $field
     * @return static
     */
    public function email(string $field): static
    {
        $value = trim((string) ($this->data[$field] ?? ''));
        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = 'Please enter a valid email address.';
        }
        return $this;
    }

    /**
     * Assert a field meets minimum length.
     *
     * @param  string $field
     * @param  int    $min
     * @return static
     */
    public function min(string $field, int $min): static
    {
        $value = (string) ($this->data[$field] ?? '');
        if (mb_strlen($value) > 0 && mb_strlen($value) < $min) {
            $this->errors[$field][] = ucfirst($field) . " must be at least {$min} characters.";
        }
        return $this;
    }

    /**
     * Assert a field does not exceed maximum length.
     *
     * @param  string $field
     * @param  int    $max
     * @return static
     */
    public function max(string $field, int $max): static
    {
        $value = (string) ($this->data[$field] ?? '');
        if (mb_strlen($value) > $max) {
            $this->errors[$field][] = ucfirst($field) . " must not exceed {$max} characters.";
        }
        return $this;
    }

    /**
     * Assert a field matches another field (e.g., password confirmation).
     *
     * @param  string $field
     * @param  string $other
     * @param  string $label
     * @return static
     */
    public function matches(string $field, string $other, string $label = ''): static
    {
        if (($this->data[$field] ?? '') !== ($this->data[$other] ?? '')) {
            $label = $label ?: ucfirst($field);
            $this->errors[$field][] = "{$label} does not match.";
        }
        return $this;
    }

    /**
     * Assert a numeric field is greater than a minimum.
     *
     * @param  string    $field
     * @param  float|int $min
     * @return static
     */
    public function numericMin(string $field, float|int $min): static
    {
        $value = $this->data[$field] ?? null;
        if ($value !== null && is_numeric($value) && (float) $value < $min) {
            $this->errors[$field][] = ucfirst($field) . " must be at least {$min}.";
        }
        return $this;
    }

    /**
     * Assert a field passes a strong-password regex.
     *
     * @param  string $field
     * @return static
     */
    public function strongPassword(string $field): static
    {
        $value = (string) ($this->data[$field] ?? '');
        if ($value !== '' && !preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $value)) {
            $this->errors[$field][] = 'Password must be 8+ chars, include an uppercase letter, a number, and a symbol.';
        }
        return $this;
    }

    /**
     * Check if validation passed.
     *
     * @return bool
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /**
     * Check if validation failed.
     *
     * @return bool
     */
    public function fails(): bool
    {
        return !$this->passes();
    }

    /**
     * Return all validation errors.
     *
     * @return array<string, string[]>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Return a flat list of error messages.
     *
     * @return string[]
     */
    public function errorList(): array
    {
        $list = [];
        foreach ($this->errors as $messages) {
            foreach ($messages as $msg) {
                $list[] = $msg;
            }
        }
        return $list;
    }

    /**
     * Get the first error for a specific field.
     *
     * @param  string $field
     * @return string
     */
    public function firstError(string $field): string
    {
        return $this->errors[$field][0] ?? '';
    }
}
