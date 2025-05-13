<?php

namespace App\Model;

use Core\Db\Model;
use Exception;
use PDO;

class Subcategories extends Model
{
    public function __construct()
    {
        $this->setTable('subcategories');
    }

    public function getOne(int $id, bool $deleted = true): bool|array|string
    {
        try {
            $withDeleted = "";
            if ($deleted) {
                $withDeleted = " AND s.deleted = :deleted";
            }

            $query = "SELECT s.id, s.category_id, s.title, 
                            s.status, s.created_at, s.updated_at, s.deleted
                        FROM {$this->getTable()} AS s
                        WHERE s.id = :id
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
            $query = "SELECT id, category_id, title, status, 
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

    public function getAllData(int $categoryId): bool|array|string
    {
        try {
            $query = "SELECT id, category_id, title, status, 
                            created_at, updated_at
                        FROM {$this->getTable()}
                        WHERE deleted = :deleted
                            AND category_id = :category_id
                        ORDER BY title";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":deleted", '0');
            $stmt->bindValue(":category_id", $categoryId);
            $stmt->execute();

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();

            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getAllActivesByCategory(int $categoryId): bool|array|string
    {
        try {
            $query = "SELECT id, category_id, title, status, 
                            created_at, updated_at
                        FROM {$this->getTable()}
                        WHERE status = :status 
                            AND deleted = :deleted
                            AND category_id = :category_id
                        ORDER BY title";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":status", '1');
            $stmt->bindValue(":deleted", '0');
            $stmt->bindValue(":category_id", $categoryId);
            $stmt->execute();

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();

            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function subcategoryExists(string $field, string $value, string $idField, int $id = null, int $catId): bool|string
    {
        try {
            if (!empty($id)) {
                $where = " AND $idField != '$id' ";
            } else {
                $where = "";
            }

            $query = "SELECT $idField FROM {$this->getTable()} 
                        WHERE $field = :value $where 
                        AND category_id = :category_id";
                        
            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":value", $value);
            $stmt->bindValue(":category_id", $catId);
            $stmt->execute();

            if ($stmt->rowCount() >= 1) {
                $result = true;
            } else {
                $result = false;
            }

            $stmt = null;
            $this->closeDb();

            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getTotalByCategory(string $category_id): int|string
    {
        try {
            $query = "SELECT COUNT(id) as total
                        FROM {$this->getTable()} 
                        WHERE category_id = :category_id
                        AND deleted = :deleted";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":category_id", $category_id);
            $stmt->bindValue(":deleted", '0');
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();

            if ($result) {
                return $result['total'];
            } else {
                return 0;
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}