<?php

namespace App\Model;

use Core\Db\Model;
use Exception;
use PDO;

class Resource extends Model
{
    public function __construct()
    {
        $this->setTable('resource');
    }

    public function getOne($uuid)
    {
        try {
            $query = "
                SELECT uuid, name, created_at
                FROM resource
                WHERE uuid = :uuid
            ";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":uuid", $uuid);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();

            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getAll(): bool|array|string
    {
        try {
            $query = "
                SELECT uuid, name
                FROM resource
            ";

            $stmt = $this->openDb()->query($query);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();

            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}