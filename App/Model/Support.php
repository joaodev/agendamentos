<?php 

namespace App\Model;

use Core\Db\Model;
use Exception;
use PDO;

class Support extends Model
{
    public function __construct()
    {
        $this->setTable('support');
    }

    public function getOne(int $id, int $customerId = null, bool $deleted = true): bool|array|string
    {
        try {
            $withDeleted = "";
            if ($deleted) {
                $withDeleted = " AND s.deleted = :deleted ";
            }

            $whereCustomer = "";
            if (!empty($customerId)) {
                $whereCustomer = " AND s.customer_id = :customer_id ";
            }

            $query = "SELECT s.id, s.title, s.description, s.call_answer,
                            s.status, s.created_at, s.updated_at,
                            s.customer_id, c.id as customerCod, 
                            c.name as customerName, c.email as customerEmail,
                            s.user_id, u.id as userCod, s.deleted,
                            u.name as userName, u.email as userEmail
                        FROM {$this->getTable()} AS s
                        LEFT JOIN customers AS c 
                            ON s.customer_id = c.id
                        LEFT JOIN user AS u 
                            ON s.user_id = u.id
                        WHERE s.id = :id  
                            $withDeleted
                            $whereCustomer";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":id", $id);
            
            if ($deleted) {
                $stmt->bindValue(":deleted", "0");
            }

            if (!empty($customerId)) {
                $stmt->bindValue(":customer_id", $customerId);
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

    public function getAll(int $customerId = null): bool|array|string
    {
        try {
            $whereCustomer = "";
            if (!empty($customerId)) {
                $whereCustomer = " AND s.customer_id = :customer_id ";
            }

            $query = "SELECT s.id, s.title, s.description, s.call_answer,
                            s.status, s.created_at, s.updated_at,
                            s.customer_id, c.id as customerCod, 
                            c.name as customerName, c.email as customerEmail,
                            s.user_id, u.id as userCod, 
                            u.name as userName, u.email as userEmail
                        FROM {$this->getTable()} AS s
                        LEFT JOIN customers AS c 
                            ON s.customer_id = c.id
                        LEFT JOIN user AS u 
                            ON s.user_id = u.id
                        WHERE s.deleted = :deleted
                        $whereCustomer";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":deleted", '0');

            if (!empty($customerId)) {
                $stmt->bindValue(":customer_id", $customerId);
            }

            $stmt->execute();

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();

            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getAllByUser(int $userId): bool|array|string
    {
        try {
            $query = "SELECT s.id, s.title, s.description, s.call_answer,
                            s.status, s.created_at, s.updated_at,
                            s.customer_id, c.id as customerCod, 
                            c.name as customerName, c.email as customerEmail,
                            s.user_id, u.id as userCod, 
                            u.name as userName, u.email as userEmail
                        FROM {$this->getTable()} AS s
                        LEFT JOIN customers AS c 
                            ON s.customer_id = c.id
                        LEFT JOIN user AS u 
                            ON s.user_id = u.id
                        WHERE s.deleted = :deleted
                            AND s.user_id = :user_id";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":deleted", '0');
            $stmt->bindValue(":user_id", $userId);

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