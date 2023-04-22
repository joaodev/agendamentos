<?php

namespace App\Model;

use Core\Db\Model;
use Exception;
use PDO;

class Plans extends Model
{
    public function __construct()
    {
        $this->setTable('plans');
    }

    public function getOne($uuid)
    {
        try {
            $query = "
                SELECT uuid, name, description, price, 
                    total_customers, total_services, total_schedules,
                    total_tasks, total_revenues, total_expenses, btn_link,
                    status, created_at, updated_at
                FROM plans
                WHERE uuid = :uuid
            ";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":uuid", $uuid);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();

            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getOneActiveByUuid($uuid)
    {
        try {
            $query = "
                SELECT uuid, name, description, price, 
                    total_customers, total_services, total_schedules, 
                    total_tasks, total_revenues, total_expenses, btn_link,
                    status, created_at, updated_at
                FROM plans
                WHERE uuid = :uuid
                    AND deleted = :deleted
                    AND status = :status
            ";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":uuid", $uuid);
            $stmt->bindValue(":deleted", '0');
            $stmt->bindValue(":status", '1');
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
            $query = "
                 SELECT uuid, name,  description, price, 
                    total_customers, total_services, total_schedules,
                    total_tasks, total_revenues, total_expenses, btn_link,
                    status, created_at, updated_at
                FROM plans
                WHERE deleted = :deleted
                ORDER BY name";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":deleted", '0');
            $stmt->execute();

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();

            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getAllActives(): bool|array|string
    {
        try {
            $query = "
                 SELECT uuid, name,  description, price, 
                    total_customers, total_services, total_schedules,
                    total_tasks, total_revenues, total_expenses, btn_link,
                    status, created_at, updated_at
                FROM plans
                WHERE deleted = :deleted AND status = :status
                ORDER BY name";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":deleted", '0');
            $stmt->bindValue(":status", '1');
            $stmt->execute();

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();

            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getAllPlans(): bool|array|string
    {
        try {
            $query = "
                 SELECT uuid, name,  description, price, 
                    total_customers, total_services, total_schedules,
                    total_tasks, total_revenues, total_expenses, btn_link,
                    status, created_at, updated_at
                FROM plans
                WHERE deleted = :deleted AND status = :status
                ORDER BY id";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":deleted", '0');
            $stmt->bindValue(":status", '1');
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