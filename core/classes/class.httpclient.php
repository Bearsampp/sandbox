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
     * Makes a GET request to the specified URL and returns the response data.
     *
     * @param string $url The URL to request.
     * @param array $headers Optional additional headers to include in the request.
     * @return string The response data.
     */
    public static function get($url, $headers = array())
    {
        $ch = curl_init();
        // Set basic cURL options
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // Merge with default headers if any
        $defaultHeaders = self::getDefaultHeaders();
        $allHeaders = array_merge($defaultHeaders, $headers);
        if (!empty($allHeaders)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $allHeaders);
        }
        $data = curl_exec($ch);
        if (curl_errno($ch)) {
            Log::error('CURL Error: ' . curl_error($ch));
        }
        // curl_close() is deprecated in PHP 8.5+ as it has no effect since PHP 8.0
        // The resource is automatically closed when it goes out of scope
        if (PHP_VERSION_ID < 80500) {
            curl_close($ch);
        }
        return trim($data);
    }
    /**
     * Makes a request to a JSON API and returns the response data.
     *
     * @param string $url The API URL to request.
     * @param array $headers Optional additional headers to include in the request.
     * @return string The JSON response data.
     */
    public static function getApiJson($url, $headers = array())
    {
        // Add GitHub API headers by default for API requests
        $apiHeaders = self::getApiHeaders();
        $allHeaders = array_merge($apiHeaders, $headers);
        return self::get($url, $allHeaders);
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
}