<?php

namespace App\Model;

use Core\Db\Model;
use Exception;
use PDO;

class PaymentTypes extends Model
{
    public function __construct()
    {
        $this->setTable('payment_types');
    }

    public function getOne(int $id, bool $deleted = true): bool|array|string
    {
        try {
            $withDeleted = "";
            if ($deleted) {
                $withDeleted = "AND p.deleted = :deleted";
            }

            $query = "SELECT p.id, p.name, p.status, 
                            p.created_at, p.updated_at, p.deleted
                        FROM {$this->getTable()} AS p
                        WHERE p.id = :id
                            $withDeleted";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":id", $id);
            
            if ($deleted) {
                $stmt->bindValue(":deleted", '0');
            }

            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();

            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getOneActiveById(int $id): bool|array|string
    {
        try {
            $query = "SELECT id, name, status, 
                            created_at, updated_at
                        FROM {$this->getTable()}
                        WHERE id = :id
                            AND deleted = :deleted
                            AND status = :status";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":id", $id);
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
            $query = "SELECT id, name, status, 
                            created_at, updated_at
                        FROM {$this->getTable()}
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
            $query = "SELECT id, name, status, created_at, updated_at
                        FROM {$this->getTable()}
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

    public function totalPaymentTypes(): int|string
    {
        try {
            $query = "SELECT id
                        FROM {$this->getTable()} 
                        WHERE deleted = :deleted";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":deleted", "0");
            $stmt->execute();

            $result = $stmt->rowCount();

            $stmt = null;
            $this->closeDb();

            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}