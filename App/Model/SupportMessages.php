<?php

namespace App\Model;

use Core\Db\Model;
use Exception;
use PDO;

class SupportMessages extends Model
{
    public function __construct()
    {
        $this->setTable('support_messages');
    }

    public function getAllByCall(int $id): bool|array|string
    {
        try {
            $query = "SELECT o.id, o.parent_id, o.customer_id, 
                            o.user_id,  o.description, o.created_at,
                            u.name as username, c.name as customername,
                            u.file as userAvatar, c.file as customerAvatar
                        FROM {$this->getTable()} AS o
                        LEFT JOIN user AS u 
                            ON o.user_id = u.id
                        LEFT JOIN customers AS c
                            ON o.customer_id = c.id
                        WHERE o.parent_id = :id
                        ORDER BY o.created_at";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":id", $id);
            $stmt->execute();

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();
            
            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getLastByCall(int $id): bool|array|string
    {
        try {
            $query = "SELECT o.id, o.parent_id, o.customer_id, 
                            o.user_id,  o.description, o.created_at,
                            u.name as username, c.name as customername,
                            u.file as userAvatar, c.file as customerAvatar
                        FROM {$this->getTable()} AS o
                        LEFT JOIN user AS u 
                            ON o.user_id = u.id
                        LEFT JOIN customers AS c
                            ON o.customer_id = c.id
                        WHERE o.parent_id = :id
                        ORDER BY o.created_at DESC ";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":id", $id);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();
            
            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}