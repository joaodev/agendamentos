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

    public function getOne(int $id): bool|array |string
    {
        try {
            $query = "SELECT id, name
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
            $query = "SELECT id, name, view_id, 
                            create_id, update_id, delete_id
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