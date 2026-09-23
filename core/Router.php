<?php
class Router {
    private $routes = [];
    private $params = [];
    
    public function get($path, $handler) {
        $this->routes['GET'][$path] = $handler;
    }
    
    public function post($path, $handler) {
        $this->routes['POST'][$path] = $handler;
    }
    
    public function dispatch($method, $uri) {
        // Remove base path
        $basePath = '/unidia/public';
        if (strpos($uri, $basePath) === 0) {
            $uri = substr($uri, strlen($basePath));
        }
        
        // Remove query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }
        
        // Remove leading/trailing slashes
        $uri = trim($uri, '/');
        
        // Default to login if empty
        if (empty($uri)) {
            $uri = 'login';
        }
        
        // Debug - uncomment to see what route is being requested
        // echo "Request URI: " . $uri . "<br>";
        
        // Check for matching route
        foreach ($this->routes[$method] ?? [] as $route => $handler) {
            // Convert route parameters like {id} to regex
            $pattern = preg_replace('/\{[a-z]+\}/', '([0-9]+)', $route);
            $pattern = '#^' . $pattern . '$#';
            
            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches);
                
                if (is_callable($handler)) {
                    return call_user_func_array($handler, $matches);
                }
                
                if (is_string($handler)) {
                    $parts = explode('@', $handler);
                    $controllerName = $parts[0];
                    $methodName = $parts[1];
                    
                    $controllerFile = __DIR__ . '/../app/controllers/' . $controllerName . '.php';
                    
                    if (file_exists($controllerFile)) {
                        require_once $controllerFile;
                        if (class_exists($controllerName)) {
                            $controller = new $controllerName();
                            return call_user_func_array([$controller, $methodName], $matches);
                        } else {
                            die("Class not found: " . $controllerName);
                        }
                    } else {
                        die("Controller file not found: " . $controllerFile);
                    }
                }
            }
        }
        
        // No route found - show 404
        http_response_code(404);
        require_once __DIR__ . '/../app/views/errors/404.php';
        exit;
    }

    public function add($method, $route, $handler) {
        // Convert route to regex pattern
        $pattern = preg_replace('/\//', '\/', $route);
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([a-zA-Z0-9_]+)', $pattern);
        $pattern = '/^' . $pattern . '$/';
        
        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'handler' => $handler
        ];
    }

    public function get($route, $handler) {
        $this->add('GET', $route, $handler);
    }

    public function post($route, $handler) {
        $this->add('POST', $route, $handler);
    }

    public function put($route, $handler) {
        $this->add('PUT', $route, $handler);
    }

    public function delete($route, $handler) {
        $this->add('DELETE', $route, $handler);
    }

    public function resolve($method, $uri) {
        // Remove query string if present
        $uri = strtok($uri, '?');
        
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            
            if (preg_match($route['pattern'], $uri, $matches)) {
                array_shift($matches);
                $this->params = $matches;
                
                $handler = $route['handler'];
                $parts = explode('@', $handler);
                $controllerName = $parts[0];
                $methodName = $parts[1];
                
                return [
                    'controller' => $controllerName,
                    'method' => $methodName,
                    'params' => $matches
                ];
            }
        }
        
        return null;
    }

    public function getParams() {
        return $this->params;
    }
}
?>