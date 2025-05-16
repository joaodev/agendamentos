<?php

namespace App\Model;

use Core\Db\Model;
use Exception;
use PDO;

class Items extends Model
{
    public function __construct()
    {
        $this->setTable('items');
    }

    public function getOne(int $id, bool $deleted = true): bool|array|string
    {
        try {
            $withDeleted = "";
            if ($deleted) {
                $withDeleted = "AND p.deleted = :deleted";
            }

            $query = "SELECT p.id, p.category_id, p.subcategory_id, 
                            p.name, p.description, p.price, 
                            p.quantity, p.min_quantity, p.file,
                            p.status, p.created_at, p.updated_at,
                            c.title as categoryTitle, c.id as categoryCod,
                            s.title as subcategoryTitle, s.id as subcategoryCod,
                            p.deleted
                        FROM {$this->getTable()} AS p
                        INNER JOIN categories AS c
                            ON p.category_id = c.id
                        LEFT JOIN subcategories AS s
                            ON p.subcategory_id = s.id
                        WHERE p.id = :id 
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

    public function getAll(): bool|array|string
    {
        try {
            $query = "SELECT p.id, p.category_id, p.name, p.subcategory_id, 
                            p.description, p.price, 
                            p.quantity, p.min_quantity, p.file,
                            p.status, p.created_at, p.updated_at,
                            c.title as categoryTitle, p.provider_id, c.id as categoryCod,
                            (SELECT SUM(C.quantity) FROM {$this->getTable()}_control C 
                                WHERE C.item_id = p.id AND C.control_type = '1') AS qtdEntradas,
                            (SELECT SUM(C.quantity) FROM {$this->getTable()}_control C 
                                WHERE C.item_id = p.id AND C.control_type = '2') AS qtdSaidas
                        FROM {$this->getTable()} AS p
                        INNER JOIN categories AS c
                            ON p.category_id = c.id
                        WHERE p.deleted = :deleted 
                        ORDER BY p.name";

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

    public function getAllActives(): bool|array|string
    {
        try {
            $query = "SELECT p.id, p.category_id, p.name, p.subcategory_id, 
                            p.description, p.price, p.file, 
                            p.quantity, p.min_quantity,
                            p.status, p.created_at, p.updated_at,
                            c.title as categoryTitle
                        FROM {$this->getTable()} AS p
                        INNER JOIN categories AS c
                            ON p.category_id = c.id
                        WHERE p.deleted = :deleted 
                            AND p.status = :status
                        ORDER BY p.name";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":deleted", '0');
            $stmt->bindValue(":status", '1');
            $stmt->execute();

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();

            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function searchData(string $postData): bool|array|string
    {
        try {
            $query = "SELECT id, name, quantity, price
                        FROM {$this->getTable()}
                        WHERE deleted = '0' AND status = '1' 
                            AND (name LIKE '%$postData%' OR id LIKE '%$postData%')
                        ORDER BY name  LIMIT 15";

            $stmt = $this->openDb()->query($query);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();

            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function totalItems(): int|string
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