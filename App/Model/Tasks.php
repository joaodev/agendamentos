<?php

namespace App\Model;

use Core\Db\Model;

class Tasks extends Model
{
    public function __construct()
    {
        $this->setTable('tasks');
    }
}