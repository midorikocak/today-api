<?php

declare(strict_types=1);

namespace MidoriKocak;

use \Exception;

class Api
{
    private array $endpoints = [];
    private array $wildcards = [];

    private string $origin;
    private int $responseCode;

    private $authenticator;

    public function __construct()
    {
        $this->origin = '*';
        $this->responseCode = 200;

        header("Access-Control-Allow-Origin: $this->origin");
        header('Content-Type: application/json; charset=UTF-8');
        header('Access-Control-Allow-Methods: OPTIONS, POST, GET, PUT, DELETE');
        header('Access-Control-Allow-Headers: Authorization, Origin, X-Requested-With, Content-Type, Accept');
    }

    public function setPrefix(string $prefix)
    {

        $prefixEndpoints = [];
        $prefix = trim($prefix, '/');

        foreach ($this->endpoints as $methodName => $item) {

            if (!isset($prefixEndpoints[$methodName])) {
                $prefixEndpoints[$methodName] = [];
            }

            foreach ($item as $key => $value) {
                $key = $key == '' ? $key : '/'.$key;
                $prefixEndpoints[$methodName][$prefix.$key] = $value;
            }
        }

        $this->endpoints = $prefixEndpoints;

        $prefixWildcards = array_map(
            function ($item) use ($prefix) {
                return $prefix.'/'.$item;
            }, $this->wildcards);

        $this->wildcards = $prefixWildcards;
    }

    private function isOptions()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS' && $this->checkOrigin($this->origin)) {
            header('Access-Control-Max-Age: 1728000');
            header('Content-Length: 0');
            header('Content-Type: text/plain');
            $this->responseCode = 200;
            return true;
        }

        header('Access-Control-Max-Age: 3600');
        header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');
        return false;
    }

    private function checkOrigin(): bool
    {
        if ($this->origin != '*' && !$_SERVER['HTTP_ORIGIN'] == $this->origin) {
            header('HTTP/1.1 403 Access Forbidden');
            header('Content-Type: text/plain');
            return false;
        }
        return true;
    }

    public function __destruct()
    {
        $method = strtolower($_SERVER['REQUEST_METHOD']);
        if ($method == 'options') {
            $this->isOptions();
        } else {
            $uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

            // Ignore uri that starts .php file extension
            //$uri = preg_replace('/^(.*?\.php\/{0,1})/', '', $uri);
            if (!isset($this->endpoints[$method])) {
                $this->endpoints[$method] = [];
            }
            $compared = $this->compareAgainstWildcards($uri);
            if (!empty($compared)) {
                $fn = $this->endpoints[$method][$compared['pattern']];
                try {
                    $this->responseCode = 200;
                    $fn(...$compared['values']);
                } catch (\Exception $e) {
                    echo json_encode($e->getMessage());
                    $this->responseCode = 400;
                }
            } elseif (isset($this->endpoints[$method][$uri])) {
                $fn = $this->endpoints[$method][$uri];
                try {
                    $this->responseCode = 200;
                    $fn();
                } catch (\Exception $e) {
                    echo json_encode($e->getMessage());
                    $this->responseCode = 400;
                }
            }
        }
        if ($this->responseCode && !headers_sent()) {
            http_response_code($this->responseCode);
        }
    }

    public function responseCode(int $code)
    {
        $this->responseCode = $code;
    }

    public function auth(callable $fn)
    {
        if (isset($_SERVER["HTTP_AUTHORIZATION"]) && 0 === strncasecmp($_SERVER["HTTP_AUTHORIZATION"], 'basic ', 6)) {
            $exploded = explode(':', base64_decode(substr($_SERVER["HTTP_AUTHORIZATION"], 6)), 2);
            if (2 == \count($exploded)) {
                list($un, $pw) = $exploded;
            }
            try {
                $this->authenticator->login($un ?? '', $pw ?? '');
                $fn();
            } catch (\Exception $e) {
                $this->responseCode = 401;
                echo json_encode($e->getMessage());
            }
        }
    }

    public function setAuthenticator($authenticator)
    {
        $this->authenticator = $authenticator;
    }

    private function compareAgainstWildcards($uri)
    {
        foreach ($this->wildcards as $wildcard) {
            $compareUri = $this->compareUri($uri, $wildcard);
            if (!empty($compareUri)) {
                return $compareUri;
            }
        }
        return [];
    }

    public function compareUri($uri, $pattern)
    {
        // does url have brackets?
        $hasBrackets = preg_match_all('/{(.+)}/', $pattern, $vars);
        if ($hasBrackets) {
            $newPattern = preg_replace('/{.+?}/m', '([^/{}]+)', $pattern);
            $passesNewPattern = preg_match("~^".$newPattern."$~", $uri, $values);
            array_shift($values);
            if ($passesNewPattern) {
                return [
                    'pattern' => $pattern,
                    'uri' => $uri,
                    'vars' => $vars,
                    'values' => array_values($values),
                ];
            }
        }
        return [];
    }

    public function hasBrackets($uri)
    {
        return preg_match('/{(.*?)}/', $uri);
    }

    public function __call($name, $arguments)
    {

        if (!isset($this->endpoints[$name])) {
            $this->endpoints[$name] = [];
        }

        if (sizeof($arguments) != 2) {
            return;
        }

        if (is_string($arguments[0]) && is_callable($arguments[1])) {

            $endpoint = parse_url(trim($arguments[0], '/'), PHP_URL_PATH);

            if ($this->hasBrackets($arguments[0])) {
                $this->wildcards[] = $endpoint;
            }
            $this->endpoints[$name][$endpoint] = $arguments[1];
        }
    }
}
