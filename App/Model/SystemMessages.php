<?php 

namespace App\Model;

use Core\Db\Model;
use Exception;
use PDO;

class SystemMessages extends Model
{
    public function __construct() 
    {
        $this->setTable('system_messages');
    }

    public function getOneByUser(int $id, int $userId): bool|array|string
    {
        try {
            $query = "SELECT m.id, m.parent, m.description, 
                            m.has_read, m.user_id, m.created_at, m.support_id,
                            m.prospect_id, m.budget_id, m.task_id, m.os_id,
                            u.name as userName, c.name as customerName,
                            u.file as userAvatar, c.file as customerAvatar
                        FROM {$this->getTable()} AS m
                        LEFT JOIN user AS u 
                            ON m.user_id = u.id
                        LEFT JOIN customers AS c
                            ON m.customer_id = c.id
                        WHERE m.id = :id
                            AND m.user_id = :user_id
                            AND m.deleted = :deleted";

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
            $query = "SELECT m.id, m.parent, m.description, 
                            m.has_read, m.user_id, m.created_at, m.support_id,
                            m.prospect_id, m.budget_id, m.task_id, m.os_id,
                            u.name as userName, c.name as customerName,
                            u.file as userAvatar, c.file as customerAvatar
                        FROM {$this->getTable()} AS m
                        LEFT JOIN user AS u 
                            ON m.user_id = u.id
                        LEFT JOIN customers AS c
                            ON m.customer_id = c.id
                        WHERE m.user_id = :user_id
                            AND m.deleted = :deleted";

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
            $query = "SELECT m.id, m.parent, m.description, 
                            m.has_read, m.user_id, m.created_at, m.support_id,
                            m.prospect_id, m.budget_id, m.task_id, m.os_id,
                            u.name as userName, c.name as customerName,
                            u.file as userAvatar, c.file as customerAvatar
                        FROM {$this->getTable()} AS m
                        LEFT JOIN user AS u 
                            ON m.user_id = u.id
                        LEFT JOIN customers AS c
                            ON m.customer_id = c.id
                        WHERE m.user_id = :user_id
                            AND m.deleted = :deleted
                            AND m.has_read = :has_read 
                        ORDER BY m.id DESC LIMIT 4";

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

    public function getParentUnreadMessages(string $parent, string $field, int $fieldId, int $userId): bool|array|string
    {
        try {
            $query = "SELECT id 
                        FROM {$this->getTable()}
                        WHERE parent = :parent
                            AND $field = :field
                            AND user_id = :user_id
                            AND deleted = :deleted
                            AND has_read = :has_read";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":parent", $parent);
            $stmt->bindValue(":field", $fieldId);
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