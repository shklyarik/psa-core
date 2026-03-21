<?php

namespace Psa\Core\Web;

/**
 * Class Request
 *
 * Provides a simple abstraction for accessing HTTP request data.
 */
class Request
{
    /**
     * Retrieve a value from the query string ($_GET).
     *
     * @param string $key The query parameter key.
     * @param mixed $default Default value returned if the key does not exist.
     *
     * @return mixed The value from $_GET or the default value.
     */
    public function get($key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Decode the JSON payload from the request body.
     *
     * Reads raw input from php://input and decodes it into a PHP object.
     *
     * @return mixed Returns the decoded JSON as an object by default,
     *               or null if the input is empty or invalid JSON.
     */
    public function json()
    {
        return json_decode(file_get_contents('php://input'));
    }
}