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

    public function getOne($uuid, $parentUUID)
    {
        try {
            $query = "
                SELECT id, uuid, name, doc_type, document_1, document_2,
                        email, phone, cellphone, postal_code, address,
                        number, complement, neighborhood, city, state,
                        status, created_at, updated_at
                FROM customers
                WHERE uuid = :uuid 
                    AND deleted = :deleted 
                    AND parent_uuid = :parent_uuid
            ";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":uuid", $uuid);
            $stmt->bindValue(":deleted", '0');
            $stmt->bindValue(":parent_uuid", $parentUUID);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();

            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getAll($parentUUID): bool|array|string
    {
        try {
            $query = "
                SELECT id, uuid, name, doc_type, document_1, document_2,
                        email, phone, cellphone, postal_code, address,
                        number, complement, neighborhood, city, state,
                        status, created_at, updated_at
                FROM customers
                WHERE deleted = :deleted 
                    AND parent_uuid = :parent_uuid
                ORDER BY name 
            ";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":deleted", '0');
            $stmt->bindValue(":parent_uuid", $parentUUID);
            $stmt->execute();

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();

            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getAllActives($parentUUID): bool|array|string
    {
        try {
            $query = "
                SELECT id, uuid, name, doc_type, document_1, document_2,
                        email, phone, cellphone, postal_code, address,
                        number, complement, neighborhood, city, state,
                        status, created_at, updated_at
                FROM customers
                WHERE deleted = :deleted 
                    AND status = :status
                    AND parent_uuid = :parent_uuid
                ORDER BY name 
            ";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":deleted", '0');
            $stmt->bindValue(":status", '1');
            $stmt->bindValue(":parent_uuid", $parentUUID);
            $stmt->execute();

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();

            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function searchData($postData, $parentUUID): bool|array|string
    {
        try {
            $query = "
                SELECT uuid, name
                FROM customers
                WHERE parent_uuid = '$parentUUID' AND  name LIKE '%$postData%' OR id LIKE '%$postData%'
                ORDER BY name  LIMIT 5
            ";

            $stmt   = $this->openDb()->query($query);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();

            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function totalCustomers($parentUUID): int|string
    {
        try {
            $query = "
                SELECT uuid
                FROM customers 
                WHERE deleted = :deleted
                    AND parent_uuid = :parent_uuid
            ";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":deleted", "0");
            $stmt->bindValue(":parent_uuid", $parentUUID);
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