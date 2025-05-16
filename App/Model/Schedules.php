<?php

namespace App\Model;

use Core\Db\Model;
use Exception;
use PDO;

class Schedules extends Model
{
    public function __construct()
    {
        $this->setTable('schedules');
    }

    public function getOne(int $id, int $customerId = null, bool $deleted = true): bool|array|string
    {
        try {
            $withDeleted = "";
            if ($deleted) {
                $withDeleted = " AND t.deleted = :deleted ";
            }

            $whereCustomer = "";
            if (!empty($customerId)) {
                $whereCustomer = " AND t.customer_id = :customer_id ";
            }

            $query = "SELECT t.id, t.title, t.description, 
                            t.schedule_date, t.schedule_time,
                            t.status, t.created_at, t.updated_at,
                            t.user_id, u.name as userName, 
                            u.id as userCod, u.email as userEmail,
                            u.job_role as userRole, t.customer_id, 
                            c.name as customerName, c.id as customerCod,
                            c.email as customerEmail, t.deleted
                        FROM {$this->getTable()} AS t
                        LEFT JOIN user AS u 
                            ON t.user_id = u.id
                        LEFT JOIN customers AS c
                            ON t.customer_id = c.id
                        WHERE t.id = :id 
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

    public function getAll(): bool|array|string
    {
        try {
            $query = "SELECT t.id, t.title, t.description, 
                            t.schedule_date, t.schedule_time,
                            t.status, t.created_at, t.updated_at,
                            u.name as userName, u.id as userCod, t.customer_id, 
                            u.email as userEmail, u.job_role as userRole,
                            c.name as customerName, c.id as customerCod,
                            c.email as customerEmail
                        FROM {$this->getTable()} AS t
                        LEFT JOIN user AS u 
                            ON t.user_id = u.id
                        LEFT JOIN customers AS c
                            ON t.customer_id = c.id
                        WHERE t.deleted = :deleted";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":deleted", "0");
            $stmt->execute();

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();
            
            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getAllByCustomer(int $customerId): bool|array|string
    {
        try {
            $query = "SELECT t.id, t.title, t.description, 
                            t.schedule_date, t.schedule_time,
                            t.status, t.created_at, t.updated_at,
                            u.name as userName, u.id as userCod, t.customer_id, 
                            u.email as userEmail, u.job_role as userRole,
                            c.name as customerName, c.id as customerCod,
                            c.email as customerEmail
                        FROM {$this->getTable()} AS t
                        LEFT JOIN user AS u 
                            ON t.user_id = u.id
                        LEFT JOIN customers AS c
                            ON t.customer_id = c.id
                        WHERE t.deleted = :deleted
                            AND t.customer_id = :customer_id";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":deleted", "0");
            $stmt->bindValue(":customer_id", $customerId);
            $stmt->execute();

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();
            
            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getTotalByStatus(string $status)
    {
        try {
            $query = "SELECT COUNT(id) as total
                        FROM {$this->getTable()} 
                        WHERE status = :status
                        AND deleted = :deleted";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":status", $status);
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

    public function getTotalByStatusAndCustomer(string $status, int $customerId)
    {
        try {
            $query = "SELECT COUNT(id) as total
                        FROM {$this->getTable()} 
                        WHERE status = :status
                        AND deleted = :deleted
                        AND customer_id = :customer_id";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":status", $status);
            $stmt->bindValue(":deleted", '0');
            $stmt->bindValue(":customer_id", $customerId);
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