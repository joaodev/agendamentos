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

    public function getOne($uuid, $parentUUID): bool|array|string
    {
        try {
            $query = "SELECT t.uuid, t.title, t.description, t.task_date, t.task_time,
                                t.status, t.created_at, t.updated_at,
                                t.user_uuid, u.name as userName
                        FROM tasks AS t
                        LEFT JOIN user AS u 
                            ON t.user_uuid = u.uuid
                        WHERE t.uuid = :uuid 
                            AND t.deleted = :deleted
                            AND t.parent_uuid = :parent_uuid";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":uuid", $uuid);
            $stmt->bindValue(":deleted", "0");
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

    public function getAllByMonth($status, $month, $parentUUID): bool|array|string
    {
        try {
            $m = explode("-", $month);
            $d1 = $m[0];
            $d2 = $m[1];

            $whereStatus = "";
            if ($status != '0') {
                $whereStatus = ' t.status = :status AND ';
            }

            $query = "SELECT t.uuid, t.title, t.description, t.task_date, t.task_time,
                                t.status, t.created_at, t.updated_at,
                                u.name as userName
                        FROM tasks AS t
                        LEFT JOIN user AS u 
                            ON t.user_uuid = u.uuid
                        WHERE $whereStatus t.deleted = :deleted
                            AND t.parent_uuid = :parent_uuid
                            AND YEAR(t.task_date) = :d1 AND MONTH(t.task_date) = :d2";

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