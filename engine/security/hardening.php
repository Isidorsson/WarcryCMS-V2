<?php
/**
 * Warcry CMS V2 - Security hardening layer
 * Safe, framework-free helpers for legacy PHP pages.
 */
if (!defined('init_engine')) {
    header('HTTP/1.0 404 not found');
    exit;
}

if (!function_exists('warcry_security_headers')) {
    function warcry_security_headers(): void
    {
        if (headers_sent()) {
            return;
        }
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        header('X-XSS-Protection: 0');
        // Soft CSP: blocks object/embed injection while keeping inline legacy JS/CSS working.
        header("Content-Security-Policy: default-src 'self' https: data: blob:; script-src 'self' 'unsafe-inline' 'unsafe-eval' https: http://www.google-analytics.com https://www.google-analytics.com; style-src 'self' 'unsafe-inline' https:; img-src 'self' data: https: http://wow.zamimg.com http://*.zamimg.com https://wow.zamimg.com https://*.zamimg.com; frame-src 'self' https://www.youtube.com https://www.youtube-nocookie.com; object-src 'none'; base-uri 'self'; frame-ancestors 'self'; form-action 'self' https:;");
    }
}

if (!function_exists('warcry_e')) {
    function warcry_e($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('warcry_clean_string')) {
    function warcry_clean_string($value, int $maxLength = 255): string
    {
        $value = trim((string)$value);
        $value = str_replace("\0", '', $value);
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $maxLength, 'UTF-8');
        }
        return substr($value, 0, $maxLength);
    }
}

if (!function_exists('warcry_db_like_escape')) {
    function warcry_db_like_escape($value): string
    {
        return addcslashes(warcry_clean_string($value, 255), "\\%_'");
    }
}

// Compatibility for old DataTables files that still call mysql_real_escape_string().
// This prevents fatal errors on PHP 7/8 and escapes LIKE/search input safely.
if (!function_exists('mysql_real_escape_string')) {
    function mysql_real_escape_string($value)
    {
        return warcry_db_like_escape($value);
    }
}

if (!function_exists('warcry_csrf_token')) {
    function warcry_csrf_token(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return '';
        }
        if (empty($_SESSION['WARCRY_CSRF_TOKEN'])) {
            $_SESSION['WARCRY_CSRF_TOKEN'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['WARCRY_CSRF_TOKEN'];
    }
}

if (!function_exists('warcry_csrf_field')) {
    function warcry_csrf_field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . warcry_e(warcry_csrf_token()) . '">';
    }
}

if (!function_exists('warcry_csrf_verify')) {
    function warcry_csrf_verify(): bool
    {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return true;
        }
        $posted = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        return isset($_SESSION['WARCRY_CSRF_TOKEN']) && hash_equals($_SESSION['WARCRY_CSRF_TOKEN'], (string)$posted);
    }
}

warcry_security_headers();
