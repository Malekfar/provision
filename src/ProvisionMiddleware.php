<?php

namespace Malekfar\Provision;

use Closure;

class ProvisionMiddleware
{
    public function handle($request, Closure $next)
    {
        $controller = request()->route()->getAction()['controller'];
        $explodedName = explode('@', $controller);
        $class = $explodedName[1];
        $method = $explodedName[2];
        $templateNameSpace = shop()->template->namespace;
        $className = "$templateNameSpace\\$class";
        $controller = new $className;
        foreach ($controller->getMiddleware() as $middleware)
            if(count($middleware['options'])) {
                if(isset($middleware['options']['except'])) {
                    if(!in_array($method, $middleware['options']['except']))
                        $middleware['middleware']($request, $next);
                } else if(isset($middleware['options']['only'])) {
                    if(in_array($method, $middleware['options']['only']))
                        $middleware['middleware']($request, $next);
                }
            } else {
                $middleware['middleware']($request, $next);
            }

        return $next($request);
    }
}
