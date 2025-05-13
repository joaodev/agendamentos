<?php

namespace Core\Di;

use Core\Db\Model;

class Container
{
    public static function getClass(string $name, string $namespace): Model
    {
        $str_class = "\\" . ucfirst($namespace)
            . "\\Model\\" . ucfirst($name);
        return new $str_class();
    }
}