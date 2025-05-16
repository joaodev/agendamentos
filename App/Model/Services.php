<?php

namespace App\Model;

use Core\Db\Model;
use Exception;
use PDO;

class Services extends Model
{
    public function __construct()
    {
        $this->setTable('services');
    }

    public function getOne(int $id, bool $deleted = true): bool|array|string
    {
        try {
            $withDeleted = "";
            if ($deleted) {
                $withDeleted = "AND s.deleted = :deleted";
            }

            $query = "SELECT s.id, s.title, s.price, s.service_type,
                            s.status, s.created_at, s.updated_at, s.deleted
                        FROM {$this->getTable()} AS s
                        WHERE s.id = :id 
                            $withDeleted";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":id", $id);
            
            if ($deleted) {
                $stmt->bindValue(":deleted", "0");
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

    public function searchData(string $postData): bool|array|string
    {
        try {
            $query = "SELECT id, title, price
                        FROM {$this->getTable()}
                        WHERE deleted = '0' AND status = '1' 
                            AND (title LIKE '%$postData%' OR id LIKE '%$postData%')
                        ORDER BY title  LIMIT 15";

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