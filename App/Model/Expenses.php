<?php

namespace App\Model;

use Core\Db\Model;
use Exception;
use PDO;

class Expenses extends Model
{
    public function __construct()
    {
        $this->setTable('expenses');
    }

    public function getOne($uuid)
    {
        try {
            $query = "SELECT o.id, o.uuid, o.title, o.description,
                                o.customer_uuid, o.amount, 
                                o.payment_type_uuid, o.status, 
                                o.created_at, o.updated_at,
                                o.expense_date, o.file,
                                c.name as customerName,
                                p.name as paymentTypeName
                        FROM expenses AS o
                        LEFT JOIN customers AS c
                            ON o.customer_uuid = c.uuid
                        LEFT JOIN payment_types AS p
                            ON o.payment_type_uuid = p.uuid
                        WHERE o.uuid = :uuid AND o.deleted = :deleted";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":uuid", $uuid);
            $stmt->bindValue(":deleted", "0");
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
            $query = "SELECT o.id, o.uuid, o.title, o.description,
                                o.customer_uuid, o.amount, 
                                o.payment_type_uuid, o.status, 
                                o.created_at, o.updated_at,
                                o.expense_date,
                                c.name as customerName,
                                p.name as paymentTypeName
                        FROM expenses AS o
                        LEFT JOIN customers AS c
                            ON o.customer_uuid = c.uuid
                        LEFT JOIN payment_types AS p
                            ON o.payment_type_uuid = p.uuid
                        WHERE o.deleted = :deleted";

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

    public function getTotalByStatus($status)
    {
        try {
            $d1 = date('Y-m') . '-01';
            $d2 = date('Y-m') . '-31';

            $query = "SELECT SUM(amount) as total
                        FROM expenses 
                        WHERE status = :status
                        AND deleted = :deleted
                        AND expense_date BETWEEN :d1 AND :d2";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":status", $status);
            $stmt->bindValue(":d1", $d1);
            $stmt->bindValue(":d2", $d2);
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