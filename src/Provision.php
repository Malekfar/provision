<?php


namespace Malekfar\Provision;

use Illuminate\Validation\ValidationException;

class Provision
{
    private $className;
    private $method;
    public function __call($name, $arguments)
    {
        if(get_called_class() != get_class($this))
            return ;
        $explodedName = explode('@', $name);
        $templateNameSpace = shop()->template->namespace;
        $class = $explodedName[0];
        $this->className = "$templateNameSpace\\$class";
        $this->method = $explodedName[1];
        $this->modelBinding($arguments);
        try {
            $controller = new $this->className;
            return app()->call([$controller, $this->method]);
        } catch (ValidationException $e) {
            $errors = [];
            foreach ($e->errors() as $error)
                $errors [] = $error[count($error)-1];

            return response()->json($errors, 422);
        } catch (\Exception $e) {
            dd($e);
        }
    }

    private function modelBinding($arguments)
    {
        $argNumber = 0;
        foreach ($arguments as $argument) {
            if(is_object($argument))
                app()->bind(get_class($argument), function () use($argument) {
                    return $argument;
                });
            else {
                $reflection = new \ReflectionMethod($this->className, $this->method);
                $parameters = $reflection->getParameters();
                if(!isset($parameters[$argNumber]))
                    continue;

                $class = $parameters[$argNumber]->getClass()->name;
                app()->bind($class, function () use($class, $argument) {
                    return $class::findOrFail($argument);
                });
            }
            $argNumber++;
        }
    }
}