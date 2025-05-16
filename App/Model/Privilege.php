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

    public function getOne(int $id): bool|array |string
    {
        try {
            $query = "SELECT p.id, p.role_id, p.resource_id, p.module_id,
                            rl.name as role, res.name as resource, md.name as module,
                            p.created_at, p.status
                        FROM {$this->getTable()} AS p
                        INNER JOIN role AS rl ON p.role_id = rl.id
                        INNER JOIN resource AS res ON p.resource_id = res.id
                        INNER JOIN modules AS md ON p.module_id = md.id
                        WHERE p.id = :id";

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

    public function getAll(): bool|array |string
    {
        try {
            $query = "SELECT p.id, p.role_id, p.resource_id, p.module_id, p.status,
                            rl.name as role, res.name as resource, md.name as module
                        FROM {$this->getTable()} AS p
                        INNER JOIN role AS rl ON p.role_id = rl.id
                        INNER JOIN resource AS res ON p.resource_id = res.id
                        INNER JOIN modules AS md ON p.module_id = md.id
                        ORDER BY md.name";

            $stmt = $this->openDb()->query($query);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();

            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getAllByRoleId(int $id): bool|array |string
    {
        try {
            $query = "SELECT p.id, p.role_id, p.resource_id, p.module_id, p.status,
                            rl.name as role, res.name as resource, md.name as module
                        FROM {$this->getTable()} AS p
                        INNER JOIN role AS rl ON p.role_id = rl.id
                        INNER JOIN resource AS res ON p.resource_id = res.id
                        INNER JOIN modules AS md ON p.module_id = md.id
                        WHERE p.role_id = :role_id";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":role_id", $id);
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
            $query = "DELETE FROM {$this->getTable()}
                        WHERE user_id = :user_id";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":user_id", $user);
            $stmt->execute();

            $stmt = null;
            $this->closeDb();

            return true;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}