<?php

namespace App\Model;

use Core\Db\Model;
use Core\Db\Crud;
use Exception;
use PDO;

class Financial extends Model
{
    public function __construct()
    {
        $this->setTable('financial');
    }

    public function getAllByMonth(string $month): bool|array|string
    {
        try {
            $infoDate = explode("-", $month, 2);

            $query = "SELECT f.id, f.purchase_id, f.expense_id, f.sale_id,
                            f.parent_type, f.title, f.mov_type,
                            f.description, f.amount, f.status,
                            f.created_at, f.updated_at
                        FROM {$this->getTable()} AS f
                        WHERE f.deleted = :deleted
                            AND MONTH(f.created_at) = :d1  
                            AND YEAR(f.created_at) = :d2";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":deleted", '0');
            $stmt->bindValue(":d1", $infoDate[1]);
            $stmt->bindValue(":d2", $infoDate[0]);
            $stmt->execute();

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();

            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getTotalFinancialByMonth(string $type, string $month): float|string
    {
        try {
            $infoDate = explode("/", $month, 2);
            $query = "SELECT SUM(amount) as total
                        FROM {$this->getTable()} 
                        WHERE mov_type = :mov_type 
                        AND status = :status
                        AND deleted = :deleted
                        AND MONTH(created_at) = :d1  
                        AND YEAR(created_at) = :d2";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":mov_type", $type);
            $stmt->bindValue(":status", '2');
            $stmt->bindValue(":deleted", '0');
            $stmt->bindValue(":d1", $infoDate[0]);
            $stmt->bindValue(":d2", $infoDate[1]);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();

            if ($result) {
                return $result['total'] ? $result['total'] : 0;
            } else {
                return 0;
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function saveFinancialLog(array $params): bool
    {
        $crud = new Crud();
        $crud->setTable('financial');
        $crud->create($params);
        
        return true;
    }

    public function getByParent(int $id, string $column)
    {
        try {
            $parentColumn = 'f.' . $column;
            $query = "SELECT f.id, f.purchase_id, f.expense_id, f.sale_id, 
                            f.parent_type, f.title, f.mov_type,
                            f.description, f.amount, f.status,
                            f.created_at, f.updated_at
                        FROM {$this->getTable()} AS f
                        WHERE f.deleted = :deleted
                            AND $parentColumn = :parent_id";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":deleted", '0');
            $stmt->bindValue(":parent_id", $id);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();

            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
    

    public function dataInFinancial(int $id, string $field): bool
    {
        if (!empty($id) && !empty($field)) {
            $query = "SELECT $field
                    FROM {$this->getTable()}
                    WHERE $field = :fieldId 
                        AND deleted = :deleted";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":fieldId", $id);
            $stmt->bindValue(":deleted", '0');

            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();

            if ($result) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}