<?php

namespace App\Model;

use Core\Db\Model;
use Exception;
use PDO;

class UseTerms extends Model
{
    public function __construct()
    {
        $this->setTable('use_terms');
    }

    public function getOne()
    {
        try {
            $query = "
                SELECT uuid, description
                FROM use_terms
                ORDER BY id LIMIT 1
            ";

            $stmt = $this->openDb()->query($query);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();

            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}