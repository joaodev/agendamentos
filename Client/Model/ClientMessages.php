<?php

namespace Client\Model;
use Core\Db\Model;
use Exception;
use PDO;

class ClientMessages extends Model
{
    public function __construct()
    {
        $this->setTable('system_messages');
    }

    public function getOneByCustomer(int $id, int $customerId): bool|array|string
    {
        try {
            $query = "SELECT m.id, m.parent, m.description, 
                            m.has_read, m.user_id, m.created_at, m.support_id,
                            m.prospect_id, m.budget_id, m.task_id, m.os_id,
                            u.name as userName, c.name as customerName
                        FROM {$this->getTable()} AS m
                        LEFT JOIN user AS u 
                            ON m.user_id = u.id
                        LEFT JOIN customers AS c
                            ON m.customer_id = c.id
                        WHERE m.id = :id
                            AND m.customer_id = :customer_id
                            AND m.deleted = :deleted";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":id", $id);
            $stmt->bindValue(":customer_id", $customerId);
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

    public function getAllByCustomer(int $customerId): bool|array|string
    {
        try {
            $query = "SELECT m.id, m.parent, m.description, 
                            m.has_read, m.user_id, m.created_at, m.support_id,
                            m.prospect_id, m.budget_id, m.task_id, m.os_id,
                            u.name as userName, c.name as customerName
                        FROM {$this->getTable()} AS m
                        LEFT JOIN user AS u 
                            ON m.user_id = u.id
                        LEFT JOIN customers AS c
                            ON m.customer_id = c.id
                        WHERE m.customer_id = :customer_id
                            AND m.deleted = :deleted";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":customer_id", $customerId);
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

    public function getAllUnreadsByCustomer(int $customerId): bool|array|string
    {
        try {
            $query = "SELECT m.id, m.parent, m.description, 
                            m.has_read, m.user_id, m.created_at, m.support_id,
                            m.prospect_id, m.budget_id, m.task_id, m.os_id,
                            u.name as userName, c.name as customerName
                        FROM {$this->getTable()} AS m
                        LEFT JOIN user AS u 
                            ON m.user_id = u.id
                        LEFT JOIN customers AS c
                            ON m.customer_id = c.id
                        WHERE m.customer_id = :customer_id
                            AND m.deleted = :deleted
                            AND m.has_read = :has_read 
                        ORDER BY m.id DESC LIMIT 4";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":customer_id", $customerId);
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

    public function getParentUnreadMessages(string $parent, string $field, int $fieldId, int $customerId): bool|array|string
    {
        try {
            $query = "SELECT id 
                        FROM {$this->getTable()}
                        WHERE parent = :parent
                            AND $field = :field
                            AND customer_id = :customer_id
                            AND deleted = :deleted
                            AND has_read = :has_read";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":parent", $parent);
            $stmt->bindValue(":field", $fieldId);
            $stmt->bindValue(":customer_id", $customerId);
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