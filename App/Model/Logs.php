<?php

namespace App\Model;

use Core\Db\Model;
use Exception;
use PDO;

class Logs extends Model
{
    public function __construct()
    {
        $this->setTable('logs');
    }

    public function getLogsByMonth(string $month): bool|array |string
    {
        try {
            $infoDate = explode("-", $month, 2);

            $query = "SELECT l.id, l.log_user_id, l.log_action, l.log_date, 
                            l.log_ip, l.log_user_agent, l.log_status, 
                            u.name as username
                        FROM {$this->getTable()} AS l
                            INNER JOIN user AS u 
                                ON l.log_user_id = u.id
                        WHERE MONTH(l.log_date) = :d1  
                            AND YEAR(l.log_date) = :d2
                        ORDER BY l.log_date DESC";

            $stmt = $this->openDb()->prepare($query);
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

    public function getOne(int $id): bool|array |string
    {
        try {
            $query = "SELECT l.id, l.log_user_id, l.log_action, l.log_date, 
                            l.log_ip, l.log_user_agent, l.log_status, 
                            u.name as username
                        FROM {$this->getTable()} AS l
                            INNER JOIN user AS u 
                                ON l.log_user_id = u.id
                        WHERE l.id = :id";

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