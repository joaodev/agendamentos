<?php

namespace App\Model;

use Core\Db\Model;

class Services extends Model
{
    public function __construct()
    {
        $this->setTable('services');
    }
}