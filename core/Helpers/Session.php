<?php
/**
 * Session Helper
 *
 * Handles session initialisation, timeout, flash messages,
 * and authentication state checks.
 */

declare(strict_types=1);

namespace Core\Helpers;

class Session
{
    /**
     * Start the session with secure settings.
     *
     * @return void
     */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'domain'   => '',
                'secure'   => false, // set true on HTTPS
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_name('AURA_SESS');
            session_start();
        }
        self::checkTimeout();
    }

    /**
     * Check session timeout and destroy if expired.
     *
     * @return void
     */
    private static function checkTimeout(): void
    {
        if (isset($_SESSION['_last_activity'])) {
            if (time() - $_SESSION['_last_activity'] > SESSION_TIMEOUT) {
                self::destroy();
                return;
            }
        }
        $_SESSION['_last_activity'] = time();
    }

    /**
     * Set a session value.
     *
     * @param  string $key
     * @param  mixed  $value
     * @return void
     */
    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Get a session value.
     *
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Remove a session key.
     *
     * @param  string $key
     * @return void
     */
    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Check if a session key exists.
     *
     * @param  string $key
     * @return bool
     */
    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Set a flash message (shown once on next request).
     *
     * @param  string $type    success|error|info|warning
     * @param  string $message
     * @return void
     */
    public static function flash(string $type, string $message): void
    {
        $_SESSION['_flash'][$type][] = $message;
    }

    /**
     * Get and clear all flash messages.
     *
     * @return array
     */
    public static function getFlash(): array
    {
        $flash = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $flash;
    }

    /**
     * Destroy the current session entirely.
     *
     * @return void
     */
    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
    }

    /**
     * Check if the current visitor is logged in.
     *
     * @return bool
     */
    public static function isLoggedIn(): bool
    {
        return self::has('user_id');
    }

    /**
     * Check if the current user is an admin.
     *
     * @return bool
     */
    public static function isAdmin(): bool
    {
        return self::get('user_role') === 'admin';
    }

    /**
     * Require authentication — redirect to login if not logged in.
     *
     * @param  string $redirect URL to return after login
     * @return void
     */
    public static function requireAuth(string $redirect = ''): void
    {
        if (!self::isLoggedIn()) {
            $back = $redirect ?: ($_SERVER['REQUEST_URI'] ?? '');
            self::flash('error', 'Please log in to continue.');
            header('Location: ' . APP_URL . '/login.php?redirect=' . urlencode($back));
            exit;
        }
    }

    /**
     * Require admin role — redirect to home if not admin.
     *
     * @return void
     */
    public static function requireAdmin(): void
    {
        self::requireAuth();
        if (!self::isAdmin()) {
            self::flash('error', 'Access denied.');
            header('Location: ' . APP_URL . '/index.php');
            exit;
        }
    }
}
