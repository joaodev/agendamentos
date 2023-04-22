<?php

namespace App\Model;

use Core\Db\Model;
use Exception;
use PDO;

class Privilege extends Model
{
    public function __construct()
    {
        $this->setTable('privilege');
    }

    public function getOne($uuid)
    {
        try {
            $query = "
            SELECT p.uuid, p.role_uuid, p.resource_uuid, p.module_uuid,
                rl.name as role, res.name as resource, md.name as module,
                p.created_at, p.status
                FROM privilege AS p
                INNER JOIN role AS rl ON p.role_uuid = rl.uuid
                INNER JOIN resource AS res ON p.resource_uuid = res.uuid
                INNER JOIN modules AS md ON p.module_uuid = md.uuid
                WHERE p.uuid = :uuid
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
    
    public function getAll(): bool|array|string
    {
        try {
            $query = "
                SELECT p.uuid, p.role_uuid, p.resource_uuid, p.module_uuid, p.status,
                        rl.name as role, res.name as resource, md.name as module
                FROM privilege AS p
                INNER JOIN role AS rl ON p.role_uuid = rl.uuid
                INNER JOIN resource AS res ON p.resource_uuid = res.uuid
                INNER JOIN modules AS md ON p.module_uuid = md.uuid
                ORDER BY md.name 
            ";

            $stmt = $this->openDb()->query($query);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();

            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getAllByRoleUuid($uuid): bool|array|string
    {
        try {
            $query = "
                SELECT p.uuid, p.role_uuid, p.resource_uuid, p.module_uuid, p.status,
                rl.name as role, res.name as resource, md.name as module
                FROM privilege AS p
                INNER JOIN role AS rl ON p.role_uuid = rl.uuid
                INNER JOIN resource AS res ON p.resource_uuid = res.uuid
                INNER JOIN modules AS md ON p.module_uuid = md.uuid
                WHERE p.role_uuid = :role_uuid
            ";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":role_uuid", $uuid);
            $stmt->execute();

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();

            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function cleanUserPrivileges($user): bool|string
    {
        try {
            $query = "DELETE FROM privilege
                        WHERE user_uuid = :user_uuid";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":user_uuid", $user);
            $stmt->execute();

            $stmt = null;
            $this->closeDb();

            return true;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}