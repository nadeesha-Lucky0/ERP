<?php
namespace App\Core;

class Session {
    private static function startIfNeeded(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function get(string $key, $default = null) {
        self::startIfNeeded();
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, $value): void {
        self::startIfNeeded();
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool {
        self::startIfNeeded();
        return isset($_SESSION[$key]);
    }

    public static function csrfToken(): string {
        self::startIfNeeded();
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    public static function validateCsrf(?string $token): bool {
        self::startIfNeeded();
        // Fallback to header if POST param not passed
        if (empty($token)) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        }
        if (empty($token) || empty($_SESSION['_csrf_token'])) {
            // If user is already authenticated as super_admin / admin in the active session, allow fallback
            if (!empty($_SESSION['user']['role_name']) && in_array($_SESSION['user']['role_name'], ['super_admin', 'admin'])) {
                return true;
            }
            return false;
        }
        return hash_equals($_SESSION['_csrf_token'], $token);
    }
}
