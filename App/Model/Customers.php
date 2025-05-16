<?php

namespace App\Model;

use Core\Db\Model;
use Exception;
use PDO;

class Customers extends Model
{
    public function __construct()
    {
        $this->setTable('customers');
    }

    public function getOne(int $id, bool $deleted = true): bool|array|string
    {
        try {
            $withDeleted = "";
            if ($deleted) {
                $withDeleted = "AND c.deleted = :deleted";
            }

            $query = "SELECT c.id, c.name, c.doc_type, c.document_1, c.document_2,
                            c.email, c.phone, c.cellphone, c.postal_code, c.address,
                            c.number, c.complement, c.neighborhood, c.city, c.state,
                            c.status, c.created_at, c.updated_at, c.deleted
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

    public function getAll(): bool|array|string
    {
        try {
            $query = "SELECT id, name, doc_type, document_1, document_2,
                            email, phone, cellphone, postal_code, address,
                            number, complement, neighborhood, city, state,
                            status, created_at, updated_at
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

    public function getAllActives(): bool|array|string
    {
        try {
            $query = "SELECT id, name, doc_type, document_1, document_2,
                            email, phone, cellphone, postal_code, address,
                            number, complement, neighborhood, city, state,
                            status, created_at, updated_at
                        FROM {$this->getTable()}
                        WHERE deleted = :deleted 
                            AND status = :status
                        ORDER BY name";

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
            $query = "SELECT id, name, email, 
                            document_1, document_2
                        FROM {$this->getTable()}
                        WHERE deleted = '0' AND status = '1'
                            AND (name LIKE '%$postData%' 
                            OR id LIKE '%$postData%' 
                            OR document_1 LIKE '%$postData%'
                            OR document_2 LIKE '%$postData%'
                            OR email LIKE '%$postData%')
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

    public function totalCustomers(): int|string
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