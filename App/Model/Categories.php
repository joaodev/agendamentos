<?php

namespace App\Model;

use Core\Db\Model;
use Exception;
use PDO;

class Categories extends Model
{
    public function __construct()
    {
        $this->setTable('categories');
    }

    public function getOne(int $id, bool $deleted = true): bool|array|string
    {
        try {
            $withDeleted = "";
            if ($deleted) {
                $withDeleted = "AND deleted = :deleted";
            }
            
            $query = "SELECT c.id, c.title, c.status, 
                            c.created_at, c.updated_at, c.deleted
                        FROM {$this->getTable()} AS c
                        WHERE c.id = :id 
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

    public function getOneActiveById(int $id): bool|array|string
    {
        try {
            $query = "SELECT id, title, status, 
                            created_at, updated_at
                        FROM {$this->getTable()}
                        WHERE id = :id
                            AND deleted = :deleted
                            AND status = :status";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":id", $id);
            $stmt->bindValue(":deleted", '0');
            $stmt->bindValue(":status", '1');
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();

            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function totalCategories(): int|string
    {
        try {
            $query = "SELECT id
                        FROM {$this->getTable()} 
                        WHERE deleted = :deleted";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":deleted", "0");
            $stmt->execute();

            $result = $stmt->rowCount();

            $stmt = null;
            $this->closeDb();

            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}