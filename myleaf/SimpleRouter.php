<?php

/**
 * Simple Router Implementation
 * Provides app() and response() global functions for easy routing
 */
class SimpleRouter
{
    private static $instance = null;
    private $routes = [];
    private $currentRoute = null;
    private $groupPrefix = '';
    private $notFoundRoute = null;
    private $basePath = '';

    /**
     * Get the singleton instance of SimpleRouter
     * @return SimpleRouter The singleton instance of SimpleRouter
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        $this->basePath = dirname($_SERVER['SCRIPT_NAME']) ?? '';
    }

    /**
     * Define a GET route
     * @param string $pattern The URL pattern for the route
     * @param callable $handler The handler function to execute when the route is matched
     * @return SimpleRouter $this
     */
    public function get($pattern, $handler)
    {
        $this->addRoute('GET', $pattern, $handler);
        return $this;
    }

    /**
     * Define a POST route
     * @param string $pattern The URL pattern for the route
     * @param callable $handler The handler function to execute when the route is matched
     * @return SimpleRouter $this
     */
    public function post($pattern, $handler)
    {
        $this->addRoute('POST', $pattern, $handler);
        return $this;
    }

    /**
     * Define a PUT route
     * @param string $pattern The URL pattern for the route
     * @param callable $handler The handler function to execute when the route is matched
     * @return SimpleRouter $this
     */
    public function put($pattern, $handler)
    {
        $this->addRoute('PUT', $pattern, $handler);
        return $this;
    }

    /**
     * Define a DELETE route
     * @param string $pattern The URL pattern for the route
     * @param callable $handler The handler function to execute when the route is matched
     * @return SimpleRouter $this
     */
    public function delete($pattern, $handler)
    {
        $this->addRoute('DELETE', $pattern, $handler);
        return $this;
    }

    /**
     * Define a PATCH route
     * @param string $pattern The URL pattern for the route
     * @param callable $handler The handler function to execute when the route is matched
     * @return SimpleRouter $this
     */
    public function patch($pattern, $handler)
    {
        $this->addRoute('PATCH', $pattern, $handler);
        return $this;
    }

    /**
     * Define a route that matches all HTTP methods
     * @param string $pattern The URL pattern for the route
     * @param callable $handler The handler function to execute when the route is matched
     * @return SimpleRouter $this
     */
    public function all($pattern, $handler)
    {
        $this->addRoute('ALL', $pattern, $handler);
        return $this;
    }

    /**
     * Define a group of routes with a common prefix
     * @param string $prefix The common prefix for the group of routes
     * @param callable $callback The callback function that defines the routes in the group
     * @return SimpleRouter $this
     */
    public function group($prefix, $callback)
    {
        $oldPrefix = $this->groupPrefix;
        $this->groupPrefix = $oldPrefix . $prefix;

        // Wywołaj callback z trasami w grupie
        call_user_func($callback);

        // Przywróć poprzedni prefix
        $this->groupPrefix = $oldPrefix;
        return $this;
    }

    /**
     * Adds a route to the router
     * @param string $method The HTTP method for the route (e.g., GET, POST)
     * @param string $pattern The URL pattern for the route
     * @param callable $handler The handler function to execute when the route is matched
     * @return void
     */
    private function addRoute($method, $pattern, $handler)
    {
        // Dodaj prefix z grupy do wzorca
        $fullPattern = $this->groupPrefix . $pattern;

        // Obsługa dwóch formatów parametrów:
        // 1. {param} - proste parametry (wszystko co nie jest /)
        // 2. (regex) - zaawansowane regex patterns

        $regexPattern = $fullPattern;

        // Zamień {param} na grupę capturującą dla prostych parametrów
        $regexPattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $regexPattern);

        // Parametry w nawiasach () pozostają jako są - to już są regexy
        // Np. (\d+) dla liczb, ([a-z]+) dla małych liter, itp.

        $regexPattern = '#^' . $regexPattern . '$#';

        // Grupuj trasy po metodach dla szybszego wyszukiwania
        if (!isset($this->routes[$method])) {
            $this->routes[$method] = [];
        }

        $this->routes[$method][] = [
            'pattern' => $fullPattern,
            'regex' => $regexPattern,
            'handler' => $handler
        ];
    }

    /**
     * Get the current matched route
     * @return array|null The current matched route or null if no route is matched
     */
    public function currentRoute()
    {
        return $this->currentRoute;
    }

    /**
     * Sets a custom handler for 404 Not Found responses
     * @param callable $handler The handler function to execute when no route is matched
     * @return SimpleRouter $this
     */
    public function setNotFound($handler)
    {
        $this->notFoundRoute = $handler;
        return $this;
    }

    /**
     * Sets the base path for the application (useful if app is not in web root)
     * @param string $path The base path to set (e.g., '/api')
     * @return SimpleRouter $this
     */
    public function setBasePath($path)
    {
        $this->basePath = $path;
        return $this;
    }

    /**
     * Runs the router to handle the incoming request
     * @return void
     */
    public function run()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Remove base path if it exists
        $basePath = $this->basePath;
        if (strpos($uri, $basePath) === 0) {
            $uri = substr($uri, strlen($basePath));
        }

        if (empty($uri)) {
            $uri = '/';
        }

        // Optymalizacja: przygotuj oba warianty URI z góry jeśli potrzeba
        $urisToTry = [$uri];
        if (substr($uri, -1) !== '/') {
            $urisToTry[] = $uri . '/';
        }

        // Sprawdź tylko trasy dla danej metody HTTP (znaczna optymalizacja!)
        $routesToCheck = array_merge(
            $this->routes[$method] ?? [],
            $this->routes['ALL'] ?? []
        );

        // Try to match routes - sprawdź oba warianty URI dla każdej trasy
        foreach ($routesToCheck as $route) {
            foreach ($urisToTry as $testUri) {
                if (preg_match($route['regex'], $testUri, $matches)) {
                    // Remove full match from parameters
                    array_shift($matches);

                    // Store current route for response helper
                    $this->currentRoute = $route;

                    // Call handler with parameters
                    return call_user_func_array($route['handler'], $matches);
                }
            }
        }

        // 404 Not Found - jeśli dotarliśmy tutaj, znaczy że ani trasa ani plik statyczny nie istnieje
        if ($this->notFoundRoute) {
            return call_user_func($this->notFoundRoute, $uri, $method);
        } else {
            response()->json(['error' => 'Endpoint not found', 'path' => $uri, 'method' => $method], 404);
        }
    }
}

/**
 * Global helper function to get router instance
 * @return SimpleRouter
 */
function app()
{
    return SimpleRouter::getInstance();
}
