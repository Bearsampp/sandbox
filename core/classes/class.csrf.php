<?php
/*
 * Copyright (c) 2021-2025 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Class Csrf
 *
 * Provides CSRF (Cross-Site Request Forgery) protection for the Bearsampp application.
 * This class handles token generation, validation, and management to prevent CSRF attacks.
 *
 * Features:
 * - Secure token generation using cryptographically secure random bytes
 * - Session-based token storage
 * - Token expiration (default: 2 hours)
 * - Token regeneration for enhanced security
 * - Automatic cleanup of expired tokens
 *
 * Usage:
 * ```php
 * // Generate and get token for forms/AJAX
 * $token = Csrf::getToken();
 *
 * // Validate token from request
 * if (!Csrf::validateToken($_POST['csrf_token'])) {
 *     die('CSRF validation failed');
 * }
 * ```
 */
class Csrf
{
    /**
     * Session key for storing CSRF tokens
     */
    const SESSION_KEY = 'bearsampp_csrf_tokens';

    /**
     * Token expiration time in seconds (default: 2 hours)
     */
    const TOKEN_EXPIRATION = 7200;

    /**
     * Maximum number of tokens to store per session
     * This prevents session bloat from token accumulation
     */
    const MAX_TOKENS = 10;

    /**
     * Initializes the CSRF protection system.
     * Starts the session if not already started and cleans up expired tokens.
     *
     * @return void
     */
    public static function init()
    {
        // Harden session settings before the session is started
        self::configureSession();

        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Initialize token storage if not exists
        if (!isset($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = [];
        }

        // Clean up expired tokens
        self::cleanupExpiredTokens();
    }

    /**
     * Hardens the session configuration before a session is started.
     * These settings are also present in the bundled php.ini, but are applied
     * here as defense in depth in case that file is customized.
     *
     * @return void
     */
    private static function configureSession()
    {
        // Only relevant before the session has been started
        if (session_status() !== PHP_SESSION_NONE || session_id() !== '') {
            return;
        }

        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);

        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');
    }

    /**
     * Generates a new CSRF token and stores it in the session.
     *
     * @return string The generated CSRF token
     * @throws Exception If random_bytes() fails
     */
    public static function generateToken()
    {
        self::init();

        // Fail closed: never fall back to weak randomness. random_bytes()
        // throws if no secure source of entropy is available.
        $token = bin2hex(random_bytes(32));

        // Store token with timestamp
        $_SESSION[self::SESSION_KEY][$token] = time();

        // Limit number of stored tokens
        if (count($_SESSION[self::SESSION_KEY]) > self::MAX_TOKENS) {
            // Remove oldest token
            $oldestToken = array_key_first($_SESSION[self::SESSION_KEY]);
            unset($_SESSION[self::SESSION_KEY][$oldestToken]);
        }

        // Log token generation without exposing token material
        Log::debug('CSRF token generated successfully');

        return $token;
    }

    /**
     * Gets the current CSRF token, generating a new one if none exists.
     *
     * @return string The CSRF token
     */
    public static function getToken()
    {
        self::init();

        // If no tokens exist, generate one
        if (empty($_SESSION[self::SESSION_KEY])) {
            return self::generateToken();
        }

        // Return the most recent token
        $tokens = $_SESSION[self::SESSION_KEY];
        end($tokens);
        $latestToken = key($tokens);

        // Check if latest token is expired
        if (time() - $tokens[$latestToken] > self::TOKEN_EXPIRATION) {
            // Generate new token if expired
            return self::generateToken();
        }

        return $latestToken;
    }

    /**
     * Validates a CSRF token.
     *
     * @param string|null $token The token to validate
     * @param bool $removeAfterValidation Whether to remove the token after successful validation (one-time use)
     * @return bool True if token is valid, false otherwise
     */
    public static function validateToken($token, $removeAfterValidation = false)
    {
        self::init();

        // Check if token is provided
        if (empty($token) || !is_string($token)) {
            Log::warning('CSRF validation failed: No token provided');
            return false;
        }

        // Check if token exists in session
        if (!isset($_SESSION[self::SESSION_KEY][$token])) {
            Log::warning('CSRF validation failed: Token not found in session');
            return false;
        }

        // Check if token is expired
        $tokenTimestamp = $_SESSION[self::SESSION_KEY][$token];
        if (time() - $tokenTimestamp > self::TOKEN_EXPIRATION) {
            Log::warning('CSRF validation failed: Token expired');
            unset($_SESSION[self::SESSION_KEY][$token]);
            return false;
        }

        // Token is valid
        Log::debug('CSRF token validated successfully');

        // Remove token if one-time use is requested
        if ($removeAfterValidation) {
            unset($_SESSION[self::SESSION_KEY][$token]);
        }

        return true;
    }

    /**
     * Validates a CSRF token from the request (POST body or header).
     * Checks $_POST['csrf_token'] first, then the X-CSRF-Token header.
     * Also verifies the request is same-origin.
     *
     * @param bool $removeAfterValidation Whether to remove the token after successful validation
     * @return bool True if token is valid, false otherwise
     */
    public static function validateRequest($removeAfterValidation = false)
    {
        // Reject cross-origin requests before checking the token.
        // Combined with SameSite=Strict cookies this blocks classic CSRF and
        // DNS-rebinding attacks.
        if (!self::validateOrigin()) {
            return false;
        }

        // Accept the token from the POST body or a dedicated header only.
        // Tokens in the query string leak via Referer headers, logs and
        // browser history.
        if (isset($_POST['csrf_token'])) {
            return self::validateToken($_POST['csrf_token'], $removeAfterValidation);
        }

        // Check custom header (for AJAX requests)
        $headers = self::getAllHeaders();

        // Check for X-CSRF-Token header (case-insensitive)
        foreach ($headers as $key => $value) {
            if (strtolower($key) === 'x-csrf-token') {
                return self::validateToken($value, $removeAfterValidation);
            }
        }

        Log::warning('CSRF validation failed: No token in request');
        return false;
    }

    /**
     * Validates that the request originates from the same host that serves the
     * homepage. Uses the Origin header when present and falls back to the
     * Referer header. Browsers always send Origin on cross-origin POST
     * requests, so a missing Origin/Referer on a state-changing request is
     * treated as invalid.
     *
     * @return bool True if the request is same-origin, false otherwise
     */
    private static function validateOrigin()
    {
        $host = isset($_SERVER['HTTP_HOST']) ? (string)$_SERVER['HTTP_HOST'] : '';
        if ($host === '') {
            Log::warning('CSRF validation failed: HTTP_HOST not available');
            return false;
        }
        $host = strtolower(preg_replace('/:\d+$/', '', $host));

        $origin = isset($_SERVER['HTTP_ORIGIN']) ? (string)$_SERVER['HTTP_ORIGIN'] : '';
        if ($origin !== '') {
            $originHost = strtolower((string)parse_url($origin, PHP_URL_HOST));
            if ($originHost === '') {
                Log::warning('CSRF validation failed: Unparseable Origin header');
                return false;
            }
            if ($originHost !== $host) {
                Log::warning('CSRF validation failed: Origin host "' . $originHost . '" does not match "' . $host . '"');
                return false;
            }
            return true;
        }

        $referer = isset($_SERVER['HTTP_REFERER']) ? (string)$_SERVER['HTTP_REFERER'] : '';
        if ($referer !== '') {
            $refererHost = strtolower((string)parse_url($referer, PHP_URL_HOST));
            if ($refererHost === '') {
                Log::warning('CSRF validation failed: Unparseable Referer header');
                return false;
            }
            if ($refererHost !== $host) {
                Log::warning('CSRF validation failed: Referer host "' . $refererHost . '" does not match "' . $host . '"');
                return false;
            }
            return true;
        }

        Log::warning('CSRF validation failed: Missing Origin and Referer headers');
        return false;
    }

    /**
     * Gets all HTTP headers in a cross-compatible way.
     * Works with both Apache and FastCGI/CGI environments.
     *
     * @return array Associative array of headers
     */
    private static function getAllHeaders()
    {
        // Use getallheaders() if available (Apache)
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if ($headers !== false) {
                return $headers;
            }
        }

        // Fallback for FastCGI/CGI environments
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            // Extract HTTP headers from $_SERVER
            if (substr($key, 0, 5) === 'HTTP_') {
                // Convert HTTP_X_CSRF_TOKEN to X-Csrf-Token
                $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[$headerName] = $value;
            }
            // Handle CONTENT_TYPE and CONTENT_LENGTH specially
            elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
                $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $key))));
                $headers[$headerName] = $value;
            }
        }

        return $headers;
    }

    /**
     * Removes expired tokens from the session.
     *
     * @return int Number of tokens removed
     */
    private static function cleanupExpiredTokens()
    {
        if (!isset($_SESSION[self::SESSION_KEY])) {
            return 0;
        }

        $removed = 0;
        $currentTime = time();

        foreach ($_SESSION[self::SESSION_KEY] as $token => $timestamp) {
            if ($currentTime - $timestamp > self::TOKEN_EXPIRATION) {
                unset($_SESSION[self::SESSION_KEY][$token]);
                $removed++;
            }
        }

        if ($removed > 0) {
            Log::debug("Cleaned up $removed expired CSRF tokens");
        }

        return $removed;
    }

    /**
     * Regenerates the CSRF token.
     * Useful after sensitive operations or login.
     *
     * @return string The new CSRF token
     */
    public static function regenerateToken()
    {
        self::init();

        // Clear all existing tokens
        $_SESSION[self::SESSION_KEY] = [];

        // Generate new token
        return self::generateToken();
    }

    /**
     * Gets the token as a hidden input field for forms.
     *
     * @return string HTML hidden input field
     */
    public static function getTokenField()
    {
        $token = self::getToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Gets the token as a meta tag for inclusion in HTML head.
     * Useful for AJAX requests.
     *
     * @return string HTML meta tag
     */
    public static function getTokenMeta()
    {
        $token = self::getToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Validates request and sends JSON error response if validation fails.
     * This is a convenience method for AJAX endpoints.
     *
     * @param bool $removeAfterValidation Whether to remove the token after successful validation
     * @return void Exits with JSON error if validation fails
     */
    public static function validateOrDie($removeAfterValidation = false)
    {
        if (!self::validateRequest($removeAfterValidation)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'CSRF validation failed',
                'message' => 'Invalid or expired security token. Please refresh the page and try again.'
            ]);
            exit;
        }
    }

    /**
     * Gets statistics about current CSRF tokens.
     * Useful for debugging and monitoring.
     *
     * @return array Statistics about tokens
     */
    public static function getStats()
    {
        self::init();

        $tokens = $_SESSION[self::SESSION_KEY] ?? [];
        $currentTime = time();
        $expired = 0;
        $valid = 0;

        foreach ($tokens as $timestamp) {
            if ($currentTime - $timestamp > self::TOKEN_EXPIRATION) {
                $expired++;
            } else {
                $valid++;
            }
        }

        return [
            'total' => count($tokens),
            'valid' => $valid,
            'expired' => $expired,
            'max_tokens' => self::MAX_TOKENS,
            'expiration_seconds' => self::TOKEN_EXPIRATION
        ];
    }
}

