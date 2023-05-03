<?php

namespace App\Model;

use Core\Db\Model;
use Exception;
use PDO;

class Tasks extends Model
{
    public function __construct()
    {
        $this->setTable('tasks');
    }

    public function getAllByMonth($status, $month, $parentUUID): bool|array|string
    {
        try {
            $m = explode("-", $month);
            $d1 = $m[0];
            $d2 = $m[1];

            $whereStatus = "";
            if ($status != '0') {
                $whereStatus = ' status = :status AND ';
            }

            $query = "SELECT uuid, title, description, task_date, task_time,
                                status, created_at, updated_at
                        FROM tasks
                        WHERE $whereStatus deleted = :deleted
                            AND parent_uuid = :parent_uuid
                            AND YEAR(task_date) = :d1 AND MONTH(task_date) = :d2";

            $stmt = $this->openDb()->prepare($query);
            if ($status != '0') {
                $stmt->bindValue(":status", $status);
            }
            $stmt->bindValue(":deleted", "0");
            $stmt->bindValue(":parent_uuid", $parentUUID);
            $stmt->bindValue(":d1", $d1);
            $stmt->bindValue(":d2", $d2);
            $stmt->execute();

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();
            
            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
    
    public function getTotalByStatus($status, $parentUUID)
    {
        try {
            $d1 = date('Y');
            $d2 = date('m');

            $query = "SELECT COUNT(uuid) as total
                        FROM tasks 
                        WHERE status = :status
                        AND deleted = :deleted
                        AND parent_uuid = :parent_uuid
                        AND YEAR(task_date) = :d1 AND MONTH(task_date) = :d2";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":status", $status);
            $stmt->bindValue(":deleted", '0');
            $stmt->bindValue(":parent_uuid", $parentUUID);
            $stmt->bindValue(":d1", $d1);
            $stmt->bindValue(":d2", $d2);
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

    public function getTotalByStatusByMonth($status, $month, $parentUUID)
    {
        try {
            $month = explode("/", $month, 2);
            $d1 = $month[1];
            $d2 = $month[0];

            $query = "SELECT COUNT(uuid) as total
                        FROM tasks 
                        WHERE status = :status
                        AND deleted = :deleted
                        AND parent_uuid = :parent_uuid
                        AND YEAR(task_date) = :d1 AND MONTH(task_date) = :d2";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":status", $status);
            $stmt->bindValue(":deleted", '0');
            $stmt->bindValue(":parent_uuid", $parentUUID);
            $stmt->bindValue(":d1", $d1);
            $stmt->bindValue(":d2", $d2);
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