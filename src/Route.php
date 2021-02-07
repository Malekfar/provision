<?php

namespace Malekfar\Provision;

use \Illuminate\Support\Facades\Route as Router;
class Route extends Router
{
    private $parameters;
    private $method;

    public function __construct($method, $parameters)
    {
        $this->method       = $method;
        $this->parameters   = $parameters;
    }

    public static function __callStatic($method, $parameters)
    {
        $router = new Route($method, $parameters);
        return $router->callRouter($method, $parameters);
    }

    private function callRouter()
    {
        $action = $this->parameters[1];
        $method = $this->method;
        if(
            $action instanceof \Closure ||
            !in_array($this->method, [
                'get', 'post', 'put', 'delete', 'patch'
            ])
        )
            return Router::$method(...$this->parameters);

        $this->parameters[1] = [
            Provision::class, $action
        ];

        return Router::$method(...$this->parameters);
    }
}