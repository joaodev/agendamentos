<?php

namespace App\Model;

use Core\Db\Model;
use Exception;
use PDO;

class UserPlans extends Model
{
    public function __construct()
    {
        $this->setTable('user_plans');
    }
    
    public function getAllByUser($user): bool|array|string
    {
        try {
            $query = "SELECT up.uuid, up.user_uuid, up.plan_uuid,
                                p.name as planName, up.status, 
                                up.approved_at, up.canceled_at,
                                p.price as planPrice, 
                                p.description as planDescription,
                                p.btn_link as planBtnLink,
                                up.file, up.uploaded_at,
                                up.created_at
                        FROM user_plans AS up
                        INNER JOIN plans AS p
                            ON up.plan_uuid = p.uuid
                        WHERE up.user_uuid = :user_uuid";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":user_uuid", $user);
            $stmt->execute();

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();
            
            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getAllUsersPlans($plan_uuid): bool|array|string
    {
        try {
            $query = "SELECT up.uuid, up.user_uuid, up.plan_uuid,
                                p.name as planName, up.status, 
                                up.approved_at, up.canceled_at,
                                p.price as planPrice, 
                                u.name as userName,
                                up.uploaded_at, up.file,
                                up.created_at
                        FROM user_plans AS up
                        INNER JOIN plans AS p
                            ON up.plan_uuid = p.uuid
                        INNER JOIN user AS u
                            ON up.user_uuid = u.uuid
                        WHERE up.plan_uuid = :plan_uuid";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":plan_uuid", $plan_uuid);
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