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

    public function getOne(int $id): bool|array |string
    {
        try {
            $query = "SELECT id, name, created_at
                        FROM {$this->getTable()}
                        WHERE id = :id";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":id", $id);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();

            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getAll(): bool|array |string
    {
        try {
            $query = "SELECT id, name
                        FROM {$this->getTable()}";

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