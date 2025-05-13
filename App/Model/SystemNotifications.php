<?php 

namespace App\Model;

use Core\Db\Model;
use Exception;
use PDO;

class SystemNotifications extends Model
{
    public function __construct()
    {
        $this->setTable('system_notifications');
    }

    public function getOneByUser(int $id, int $userId): bool|array|string
    {
        try {
            $query = "SELECT n.id, n.parent, n.description, n.sale_id, 
                            n.has_read, n.user_id, n.created_at,
                            n.schedule_id, n.prospect_id, n.purchase_id, 
                            n.expense_id, n.budget_id, n.task_id, n.os_id,
                            n.support_id, n.time_sheet_id, n.for_files,
                            n.item_control_id
                        FROM {$this->getTable()} AS n
                        WHERE n.id = :id
                            AND n.user_id = :user_id
                            AND n.deleted = :deleted";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":id", $id);
            $stmt->bindValue(":user_id", $userId);
            $stmt->bindValue(":deleted", 0);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

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
            $query = "SELECT n.id, n.parent, n.description, n.sale_id, 
                            n.has_read, n.user_id, n.created_at,
                            n.schedule_id, n.prospect_id, n.purchase_id, 
                            n.expense_id, n.budget_id, n.task_id, n.os_id,
                            n.support_id, n.for_files
                        FROM {$this->getTable()} AS n
                        WHERE n.user_id = :user_id
                            AND n.deleted = :deleted";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":user_id", $userId);
            $stmt->bindValue(":deleted", 0);
            $stmt->execute();

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();
            
            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getAllUnreadsByUser(int $userId): bool|array|string
    {
        try {
            $query = "SELECT n.id, n.parent, n.description, n.sale_id,
                            n.has_read, n.user_id, n.created_at,
                            n.schedule_id, n.prospect_id, n.purchase_id, 
                            n.expense_id, n.budget_id, n.task_id, n.os_id,
                            n.support_id, n.for_files
                        FROM {$this->getTable()} AS n
                        WHERE n.user_id = :user_id
                            AND n.deleted = :deleted
                            AND n.has_read = :has_read 
                        ORDER BY n.id DESC LIMIT 4";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":user_id", $userId);
            $stmt->bindValue(":deleted", 0);
            $stmt->bindValue(":has_read",'0');
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