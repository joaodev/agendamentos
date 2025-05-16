<?php

namespace App\Model;

use Core\Db\Model;

class UserDevices extends Model
{
    public function __construct()
    {
        $this->setTable('user_devices');
    }
}