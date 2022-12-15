<?php

namespace Core\Di;

class Container
{
    public static function getClass($name, $namespace)
    {
        $str_class = "\\" . ucfirst($namespace)
            . "\\Model\\" . ucfirst($name);
        return new $str_class();
    }
}
