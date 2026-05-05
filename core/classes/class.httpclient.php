<?php
/*
 * Copyright (c) 2022 - 2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */
/**
 * HttpClient class for handling HTTP requests using cURL.
 */
class HttpClient
{
    /**
     * Whether to enable verbose cURL output.
     * @var bool
     */
    private static $verbose = false;

    /**
     * Sets the verbosity level for cURL operations.
     *
     * @param bool $verbose Whether to enable verbose output.
     * @return void
     */
    public static function setVerbose($verbose)
    {
        self::$verbose = (bool) $verbose;
    }

    /**
     * Gets the current verbosity setting.
     *
     * @return bool Current verbosity setting.
     */
    public static function getVerbose()
    {
        return self::$verbose;
    }

    /**
     * Makes a GET request to the specified URL and returns the response data.
     *
     * @param string $url The URL to request.
     * @param array $headers Optional additional headers to include in the request.
     * @return array The response data with 'data' key on success, or 'data' and 'error' keys on failure.
     */
    public static function get($url, $headers = array())
    {
        $ch = curl_init();

        // Parse URL to check if it's localhost
        $parsedUrl = parse_url($url);
        $isLocalhost = isset($parsedUrl['host']) && (
            $parsedUrl['host'] === 'localhost' ||
            $parsedUrl['host'] === '127.0.0.1' ||
            $parsedUrl['host'] === '::1'
        );

        // Set basic cURL options
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, self::$verbose);
        curl_setopt($ch, CURLOPT_URL, $url);

        // Configure SSL verification - allow self-signed for localhost
        if ($isLocalhost) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

            // Configure CA bundle for better compatibility
            $caBundle = self::getCABundlePath();
            if ($caBundle && file_exists($caBundle)) {
                curl_setopt($ch, CURLOPT_CAINFO, $caBundle);
            }
        }

        // Merge with default headers if any
        $defaultHeaders = self::getDefaultHeaders();
        $allHeaders = array_merge($defaultHeaders, $headers);
        if (!empty($allHeaders)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $allHeaders);
        }

        $data = curl_exec($ch);

        $error = null;
        $errorCode = curl_errno($ch);
        if ($errorCode) {
            $error = curl_error($ch);
            $errorType = self::categorizeCurlError($errorCode, $error, $isLocalhost);
            Log::error('CURL Error (' . $errorType . '): ' . $error . ' (URL: ' . $url . ')');
        }

        // curl_close() is deprecated in PHP 8.0+ as it has no effect since PHP 8.0
        // The resource is automatically closed when it goes out of scope
        if (PHP_VERSION_ID < 80000) {
            curl_close($ch);
        }

        // Always return array with consistent structure
        if ($error !== null) {
            return array('data' => trim($data), 'error' => $error, 'error_type' => self::categorizeCurlError($errorCode, $error, $isLocalhost));
        }

        return array('data' => trim($data));
    }

    /**
     * Makes a request to a JSON API and returns the response data.
     *
     * @param string $url The API URL to request.
     * @param array $headers Optional additional headers to include in the request.
     * @return array An array with 'data' key containing the JSON response string on success,
     *               or 'data', 'error', and 'error_type' keys on failure.
     *               Structure: ['data' => string, 'error' => string, 'error_type' => string]
     */
    public static function getApiJson($url, $headers = array())
    {
        $apiHeaders = self::getApiHeaders();
        $allHeaders = array_merge($apiHeaders, $headers);
        return self::get($url, $allHeaders);
    }

    /**
     * Retrieves HTTP headers from a given URL.
     *
     * @param string $url The URL from which to fetch the headers.
     * @param array $headers Optional additional headers to include in the request.
     * @return array The headers array with 'headers' key on success, or 'headers' and 'error' keys on failure.
     */
    public static function getCurlHeaders($url, $headers = array())
    {
        $ch = curl_init();

        // Parse URL to check if it's localhost
        $parsedUrl = parse_url($url);
        $isLocalhost = isset($parsedUrl['host']) && (
            $parsedUrl['host'] === 'localhost' ||
            $parsedUrl['host'] === '127.0.0.1' ||
            $parsedUrl['host'] === '::1'
        );

        // Set basic cURL options
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, self::$verbose);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request to get headers only
        curl_setopt($ch, CURLOPT_URL, $url);

        // Configure SSL verification - allow self-signed for localhost
        if ($isLocalhost) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

            // Configure CA bundle for better compatibility
            $caBundle = self::getCABundlePath();
            if ($caBundle && file_exists($caBundle)) {
                curl_setopt($ch, CURLOPT_CAINFO, $caBundle);
            }
        }

        // Merge with default headers if any
        $defaultHeaders = self::getDefaultHeaders();
        $allHeaders = array_merge($defaultHeaders, $headers);
        if (!empty($allHeaders)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $allHeaders);
        }

        $response = curl_exec($ch);

        $error = null;
        $errorCode = curl_errno($ch);
        if ($errorCode) {
            $error = curl_error($ch);
            $errorType = self::categorizeCurlError($errorCode, $error, $isLocalhost);
            Log::error('CURL Error getting headers (' . $errorType . '): ' . $error . ' (URL: ' . $url . ')');
        }

        // curl_close() is deprecated in PHP 8.0+ as it has no effect since PHP 8.0
        // The resource is automatically closed when it goes out of scope
        if (PHP_VERSION_ID < 80000) {
            curl_close($ch);
        }

        // Always return array with consistent structure
        if ($error !== null) {
            return array('headers' => array(), 'error' => $error, 'error_type' => self::categorizeCurlError($errorCode, $error, $isLocalhost));
        }

        // Parse headers from response
        $parsedHeaders = array();
        if (!empty($response)) {
            $responseLines = explode("\n", $response);
            foreach ($responseLines as $line) {
                $line = trim($line);
                if (!empty($line)) {
                    $parsedHeaders[] = $line;
                }
            }
        }

        return array('headers' => $parsedHeaders);
    }

    /**
     * Gets the path to the CA bundle file.
     *
     * @return string|null Path to CA bundle or null if not found.
     */
    private static function getCABundlePath()
    {
        // Try common CA bundle locations
        $possiblePaths = array(
            // Windows CA store
            'C:/Windows/System32/certs/cacert.pem',
            // Common locations
            dirname(__DIR__, 2) . '/ssl/cacert.pem',
            // PHP's default location
            ini_get('curl.cainfo'),
            // Environment variable
            getenv('CURL_CA_BUNDLE'),
        );

        foreach ($possiblePaths as $path) {
            if ($path && file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Gets the default headers for HTTP requests.
     *
     * @return array The default headers.
     */
    private static function getDefaultHeaders()
    {
        return array();
    }

    /**
     * Gets the headers required for GitHub API requests.
     *
     * @return array The GitHub API headers.
     */
    private static function getApiHeaders()
    {
        return array(
            'User-Agent: ' . APP_GITHUB_USERAGENT,
            'Accept: application/vnd.github.v3+json'
        );
    }

    /**
     * Categorizes cURL errors for better logging and debugging.
     *
     * @param int $errorCode The cURL error code.
     * @param string $errorMessage The cURL error message.
     * @param bool $isLocalhost Whether the request was made to a localhost URL.
     * @return string The error category.
     */
    private static function categorizeCurlError($errorCode, $errorMessage, $isLocalhost)
    {
        // Connection errors (e.g., DNS resolution, connection refused, timeouts)
        if (in_array($errorCode, array(
            CURLE_COULDNT_RESOLVE_HOST,  // 6
            CURLE_COULDNT_CONNECT,       // 7
            CURLE_OPERATION_TIMEOUTED,   // 28
            CURLE_RECV_ERROR,           // 56
            CURLE_SEND_ERROR            // 55
        ))) {
            return 'connection';
        }

        // SSL/TLS certificate and verification errors
        if (in_array($errorCode, array(
            CURLE_SSL_CACERT,           // 60 - CA certificate verification failed
            CURLE_SSL_PEER_CERTIFICATE, // 60 - Peer certificate cannot be authenticated
            CURLE_SSL_CERTPROBLEM,     // 58 - Certificate problem
            CURLE_SSL_CIPHER,          // 59 - Failed to use specified cipher
            CURLE_SSL_CONNECT_ERROR,   // 35 - SSL connection error
            CURLE_SSL_ENGINE_NOTFOUND, // 53 - SSL engine not found
            CURLE_SSL_ENGINE_SETFAILED // 54 - Cannot set SSL crypto engine as default
        ))) {
            // For localhost, treat SSL errors as self-signed certificate issues
            return $isLocalhost ? 'self-signed' : 'ssl';
        }

        // Other errors
        return 'other';
    }

    /**
     * Retrieves HTTP headers from a given URL using either cURL or fopen, depending on availability.
     *
     * @param   string  $pingUrl  The URL to ping for headers.
     *
     * @return array An array of HTTP headers.
     */
    public static function getHttpHeaders($pingUrl)
    {
        if (function_exists('curl_version')) {
            $result = self::getCurlHttpHeaders($pingUrl);
        } else {
            $result = self::getFopenHttpHeaders($pingUrl);
        }

        if (!empty($result)) {
            $rebuildResult = array();
            foreach ($result as $row) {
                $row = trim($row);
                if (!empty($row)) {
                    $rebuildResult[] = $row;
                }
            }
            $result = $rebuildResult;

            Log::debug('getHttpHeaders:');
            foreach ($result as $header) {
                Log::debug('-> ' . $header);
            }
        }

        return $result;
    }

    /**
     * Retrieves HTTP headers from a given URL using the fopen function.
     *
     * This method creates a stream context with SSL verification enabled for security.
     * It attempts to open the URL and read the HTTP response headers.
     *
     * @param   string  $url  The URL from which to fetch the headers.
     *
     * @return array An array of headers if successful, otherwise an empty array.
     */
    public static function getFopenHttpHeaders($url)
    {
        $result = array();

        $context = stream_context_create(array(
            'ssl' => array(
                'verify_peer'       => true,
                'verify_peer_name'  => true,
                'allow_self_signed' => false,
            )
        ));

        $fp = @fopen($url, 'r', false, $context);
        if ($fp) {
            $meta   = stream_get_meta_data($fp);
            $result = isset($meta['wrapper_data']) ? $meta['wrapper_data'] : $result;
            fclose($fp);
        }

        return $result;
    }

    /**
     * Retrieves HTTP headers from a given URL using cURL.
     *
     * This method uses HttpClient for secure TLS verification.
     *
     * @param   string  $url  The URL from which to fetch the headers.
     *
     * @return array An array of headers if successful, otherwise an empty array.
     */
    public static function getCurlHttpHeaders($url)
    {
        $result = self::getCurlHeaders($url);

        // Handle error response from HttpClient
        if (is_array($result) && isset($result['error'])) {
            Log::error('Failed to get headers via HttpClient::getCurlHeaders: ' . $result['error']);
            return array();
        }

        // Extract headers array from consistent HttpClient response structure
        return isset($result['headers']) ? $result['headers'] : array();
    }


    /**
     * Checks the current state of the internet connection.
     *
     * This method attempts to reach a well-known website (e.g., www.google.com) to determine the state of the internet connection.
     * It returns `true` if the connection is successful, otherwise it returns `false`.
     *
     * @return bool True if the internet connection is active, false otherwise.
     */
    public static function checkInternetState()
    {
        $connected = @fsockopen('www.google.com', 80);
        if ($connected) {
            fclose($connected);

            return true; // Internet connection is active
        } else {
            return false; // Internet connection is not active
        }
    }
}
