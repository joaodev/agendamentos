<?php

namespace App\Model;

use Core\Db\Model;
use Exception;
use PDO;

class Module extends Model
{
    public function __construct()
    {
        $this->setTable('modules');
    }

    public function getOne($uuid)
    {
        try {
            $query = "
                SELECT uuid, name
                FROM modules
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
                SELECT uuid, name, view_uuid, 
                        create_uuid, update_uuid, delete_uuid
                FROM modules
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