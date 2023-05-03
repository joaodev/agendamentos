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

    public function getOne($uuid, $parentUUID)
    {
        try {
            $query = "SELECT o.id, o.uuid, o.title, o.description,
                                o.customer_uuid, o.amount, 
                                o.payment_type_uuid, o.status, 
                                o.created_at, o.updated_at,
                                o.schedule_date, o.file,
                                o.service_uuid, o.schedule_time,
                                s.title as serviceName, 
                                s.description as serviceDescription,
                                c.name as customerName,
                                c.email as customerEmail,
                                c.cellphone as customerCellphone,
                                p.name as paymentTypeName
                        FROM schedules AS o
                        INNER JOIN services AS s
                            ON o.service_uuid = s.uuid
                        LEFT JOIN customers AS c
                            ON o.customer_uuid = c.uuid
                        LEFT JOIN payment_types AS p
                            ON o.payment_type_uuid = p.uuid
                        WHERE o.uuid = :uuid 
                            AND o.deleted = :deleted
                            AND o.parent_uuid = :parent_uuid";

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

    public function getAll($parentUUID): bool|array|string
    {
        try {
            $query = "SELECT o.id, o.uuid, o.title, o.description,
                                o.customer_uuid, o.amount, 
                                o.payment_type_uuid, o.status, 
                                o.created_at, o.updated_at,
                                o.schedule_date, o.schedule_time,
                                s.title as serviceName, 
                                c.name as customerName,
                                p.name as paymentTypeName
                        FROM schedules AS o
                        INNER JOIN services AS s
                            ON o.service_uuid = s.uuid
                        LEFT JOIN customers AS c
                            ON o.customer_uuid = c.uuid
                        LEFT JOIN payment_types AS p
                            ON o.payment_type_uuid = p.uuid
                        WHERE o.deleted = :deleted
                            AND o.parent_uuid = :parent_uuid";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":deleted", "0");
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
   
    public function getAllByMonth($status, $month, $parentUUID): bool|array|string
    {
        try {
            $m = explode("-", $month);
            $d1 = $m[0];
            $d2 = $m[1];

            $whereStatus = "";
            if ($status != '0') {
                $whereStatus = ' o.status = :status AND ';
            }

            $query = "SELECT o.id, o.uuid, o.title, o.description,
                                o.customer_uuid, o.amount, 
                                o.payment_type_uuid, o.status, 
                                o.created_at, o.updated_at,
                                o.schedule_date, o.schedule_time,
                                s.title as serviceName, 
                                c.name as customerName,
                                p.name as paymentTypeName
                        FROM schedules AS o
                        INNER JOIN services AS s
                            ON o.service_uuid = s.uuid
                        LEFT JOIN customers AS c
                            ON o.customer_uuid = c.uuid
                        LEFT JOIN payment_types AS p
                            ON o.payment_type_uuid = p.uuid
                        WHERE $whereStatus o.deleted = :deleted
                            AND o.parent_uuid = :parent_uuid
                            AND YEAR(o.schedule_date) = :d1 AND MONTH(o.schedule_date) = :d2";

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
                        FROM schedules 
                        WHERE status = :status
                        AND deleted = :deleted
                        AND parent_uuid = :parent_uuid
                        AND YEAR(schedule_date) = :d1 AND MONTH(schedule_date) = :d2";

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
                        FROM schedules 
                        WHERE status = :status
                        AND deleted = :deleted
                        AND parent_uuid = :parent_uuid
                        AND YEAR(schedule_date) = :d1 AND MONTH(schedule_date) = :d2";

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

    public function getTotalAmountByStatus($status, $parentUUID)
    {
        try {
            $d1 = date('Y');
            $d2 = date('m');

            $query = "SELECT SUM(amount) as total
                        FROM schedules 
                        WHERE status = :status
                        AND deleted = :deleted
                        AND parent_uuid = :parent_uuid
                        AND YEAR(schedule_date) = :d1 AND MONTH(schedule_date) = :d2";

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

    public function getTotalAmountByMonth($month, $parentUUID)
    {
        try {
            $m = explode("-", $month);
            $d1 = $m[0];
            $d2 = $m[1];

            $query = "SELECT SUM(amount) as total
                        FROM schedules 
                        WHERE status = :status
                        AND deleted = :deleted
                        AND parent_uuid = :parent_uuid
                        AND YEAR(schedule_date) = :d1 AND MONTH(schedule_date) = :d2";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":status", '2');
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