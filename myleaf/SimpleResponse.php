<?php

/**
 * SimpleResponse class - provides methods to send different types of responses (JSON, text, HTML) and set HTTP status codes and headers. It also includes a helper function to get an instance of SimpleResponse.
 */
class SimpleResponse
{
    /**
     * Sends a JSON response
     * @param mixed $data The data to send as JSON
     * @param int $statusCode The HTTP status code (default: 200)
     * @return SimpleResponse $this
     */
    public function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        return $this;
    }

    /**
     * Sends a plain text response
     * @param string $text The text to send in the response
     * @param int $statusCode The HTTP status code (default: 200)
     * @return SimpleResponse $this
     */
    public function text($text, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: text/plain');
        echo $text;
        return $this;
    }

    /**
     * Sends an HTML response
     * @param string $html The HTML content to send in the response
     * @param int $statusCode The HTTP status code (default: 200)
     * @return SimpleResponse $this
     */
    public function html($html, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: text/html');
        echo $html;
        return $this;
    }

    /**
     * Sets the HTTP status code for the response
     * @param int $code The HTTP status code to set
     * @return SimpleResponse $this
     */
    public function status($code)
    {
        http_response_code($code);
        return $this;
    }

    /**
     * Exits the script immediately without sending any response. It is recommended to use this method after sending a response using json(), text(), or html() methods.
     * @return void
     */
    public function exit()
    {
        exit;
    }

    /**
     * Sets a custom header for the response
     * @param string $name The name of the header
     * @param string $value The value of the header
     * @return SimpleResponse $this
     */
    public function header($name, $value)
    {
        header("$name: $value");
        return $this;
    }

    /**
     * Redirects to a different URL with an optional status code (default: 302)
     * @param string $url The URL to redirect to
     * @param int $statusCode The HTTP status code for the redirection (default: 302)
     * @return SimpleResponse $this
     */
    public function redirect($url, $statusCode = 302)
    {
        http_response_code($statusCode);
        header("Location: $url");
        return $this;
    }
}

/**
 * Global helper function to get response instance
 * @return SimpleResponse
 */
function response()
{
    return new SimpleResponse();
}
