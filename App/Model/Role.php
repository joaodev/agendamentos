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

    public function getOne(int $id, bool $deleted = true): bool|array |string
    {
        try {
            $withDeleted = "";
            if ($deleted) {
                $withDeleted = " AND r.deleted = :deleted ";
            }

            $query = "SELECT r.id, r.name, r.is_admin, 
                            r.created_at, r.updated_at, r.deleted
                        FROM {$this->getTable()} AS r
                        WHERE r.id = :id 
                            $withDeleted";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":id", $id);
            
            if ($deleted) {
                $stmt->bindValue(":deleted", '0');
            }

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
            $query = "SELECT id, name, is_admin
                        FROM {$this->getTable()}
                        WHERE deleted = :deleted
                        ORDER BY name";

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