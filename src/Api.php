<?php

declare(strict_types=1);

namespace MidoriKocak;

use RuntimeException;

class Api
{
    private array $endpoints = [];
    private array $wildcards = [];

    public function __destruct()
    {
        $method = strtolower($_SERVER['REQUEST_METHOD']);
        $uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

        if (!isset($this->endpoints[$method])) {
            $this->endpoints[$method] = [];
        }
        $compared = $this->compareAgainstWildcards($uri);
        if (!empty($compared)) {
            $fn = $this->endpoints[$method][$compared['pattern']];
            $fn(...$compared['values']);
        } elseif (isset($this->endpoints[$method][$uri])) {
            $fn = $this->endpoints[$method][$uri];
            $fn();
        } else {
            http_response_code(404);
        }
    }

    public function auth(callable $fn, $authenticator)
    {
        if (isset($_SERVER["HTTP_AUTHORIZATION"]) && 0 === strncasecmp($_SERVER["HTTP_AUTHORIZATION"], 'basic ', 6)) {
            $exploded = explode(':', base64_decode(substr($_SERVER["HTTP_AUTHORIZATION"], 6)), 2);
            if (2 == \count($exploded)) {
                list($un, $pw) = $exploded;
            }
        }
        try {
            $authenticator->login($un ?? '', $pw ?? '');
        } catch (\Exception $e) {
            http_response_code(401);
            echo json_encode($e->getMessage());
        }

        $fn();
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
            $passesNewPattern = preg_match("~^" . $newPattern . "$~", $uri, $values);
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
