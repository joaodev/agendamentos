<?php

namespace App\Model;

use Core\Db\Model;
use Exception;
use PDO;

class SupportResponsibles extends Model
{
    public function __construct()
    {
        $this->setTable('support_responsibles');
    }

    public function getAllBySupport(int $supportId): bool|string|array
    {
        try {
            $query = "SELECT o.id, o.user_id,
                            u.id as userCod, u.name as userName,
                            u.email as userEmail, u.file as userAvatar,
                            u.job_role as userRole
                        FROM {$this->getTable()} AS o
                        INNER JOIN user AS u
                            ON o.user_id = u.id
                        WHERE o.support_id = :support_id";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":support_id", $supportId);
            $stmt->execute();

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();

            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getResponsibleExists(int $supportId, int $userId): bool|array|string
    {
        try {
            $query = "SELECT id
                        FROM {$this->getTable()}
                        WHERE support_id = :support_id
                            AND user_id = :user_id";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":support_id", $supportId);
            $stmt->bindValue(":user_id", $userId);
            $stmt->execute();

            $result = $stmt->rowCount();

            $stmt = null;
            $this->closeDb();

            return $result > 0 ? true : false;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}