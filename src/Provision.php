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
            $errors = ['message' => $e->getMessage()];
            foreach ($e->errors() as $key => $error)
                $errors ['errors'][$key][] = $error[count($error)-1];

            return response()->json($errors, $e->status);
        } catch (\Exception $e) {
            dd($e);
        }
    }

    private function modelBinding($arguments)
    {
        $argNumber = 0;
        $reflection = new \ReflectionMethod($this->className, $this->method);
        foreach ($arguments as $argument) {
            if(is_object($argument)){
                $argumentClass = new \ReflectionClass(get_class($argument));
                if(
                    ($reflection->getParameters()[$argNumber]->getClass()->getShortName() == $argumentClass->getShortName()) &&
                    ($reflection->getParameters()[$argNumber]->getClass()->getName() != $argumentClass->getName())
                ) {
                    $castedArg = $reflection->getParameters()[$argNumber]->getClass()->getName();
                    $model = new $castedArg;
                    $castedClass = $model::find($argument->id);
                    app()->bind($castedArg, function () use ($castedClass) {
                        return $castedClass;
                    });
                    $argNumber++;
                    continue;
                }
                app()->bind(get_class($argument), function () use($argument) {
                    return $argument;
                });
            }
            else {
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
