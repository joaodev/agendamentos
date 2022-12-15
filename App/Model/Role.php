<?php

namespace App\Model;

use Core\Db\Model;
use Exception;
use PDO;

class Role extends Model
{
    public function __construct()
    {
        $this->setTable('role');
    }

    public function getOne($uuid)
    {
        try {
            $query = "
                SELECT uuid, name, is_admin, created_at, updated_at
                FROM role
                WHERE uuid = :uuid AND deleted = :deleted
            ";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":uuid", $uuid);
            $stmt->bindValue(":deleted", '0');
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
                SELECT uuid, name, is_admin
                FROM role
                WHERE deleted = :deleted
                ORDER BY name
            ";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":deleted", '0');
            $stmt->execute();

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();

            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}