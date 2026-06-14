<?php
/**
 * Security Helper
 *
 * Provides XSS sanitisation, CSRF token management,
 * and input filtering utilities.
 */

declare(strict_types=1);

namespace Core\Helpers;

class Security
{
    /**
     * Sanitise a value for safe HTML output (XSS prevention).
     *
     * @param  mixed $value
     * @return string
     */
    public static function h(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Generate (or retrieve) the CSRF token for the current session.
     *
     * @return string
     */
    public static function csrfToken(): string
    {
        if (empty($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }

    /**
     * Render a hidden CSRF input field.
     *
     * @return string
     */
    public static function csrfField(): string
    {
        $token = self::csrfToken();
        return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . $token . '">';
    }

    /**
     * Validate the CSRF token submitted via POST.
     *
     * @return bool
     */
    public static function verifyCsrf(): bool
    {
        $submitted = $_POST[CSRF_TOKEN_NAME] ?? '';
        $stored    = $_SESSION[CSRF_TOKEN_NAME] ?? '';

        if (empty($submitted) || empty($stored)) {
            return false;
        }
        return hash_equals($stored, $submitted);
    }

    /**
     * Strip tags and trim a string for safe storage.
     *
     * @param  string $input
     * @return string
     */
    public static function clean(string $input): string
    {
        return trim(strip_tags($input));
    }

    /**
     * Validate an email address.
     *
     * @param  string $email
     * @return bool
     */
    public static function isEmail(string $email): bool
    {
        return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Validate a password meets requirements.
     * Min 8 chars, at least 1 uppercase, 1 digit, 1 special char.
     *
     * @param  string $password
     * @return bool
     */
    public static function isStrongPassword(string $password): bool
    {
        return (bool) preg_match(
            '/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/',
            $password
        );
    }

    /**
     * Hash a plain-text password using bcrypt.
     *
     * @param  string $password
     * @return string
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Verify a plain-text password against a bcrypt hash.
     *
     * @param  string $password
     * @param  string $hash
     * @return bool
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Resolve a product image value to a usable URL.
     *
     * Accepts either:
     *   - A full external URL  (e.g. https://images.unsplash.com/...)
     *   - A bare filename      (e.g. photo.jpg)
     *
     * External URLs are returned as-is.
     * Bare filenames are prefixed with UPLOAD_URL so the browser can load them.
     *
     * Usage in templates:
     *   <img src="<?= Security::h(Security::imageUrl($product['image'])) ?>">
     *
     * @param  string $image  Value stored in the products.image column
     * @return string         Absolute URL ready for use in an <img src> attribute
     */
    public static function imageUrl(string $image): string
    {
        if (filter_var($image, FILTER_VALIDATE_URL)) {
            return $image;                                          // already a full URL
        }
        return rtrim(UPLOAD_URL, '/') . '/' . ltrim($image, '/'); // local upload
    }

    /**
     * Generate a UUID v4.
     *
     * @return string
     */
    public static function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}