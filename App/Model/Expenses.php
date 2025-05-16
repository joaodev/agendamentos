<?php

namespace App\Model;

use Core\Db\Model;
use Core\Db\Crud;
use Exception;
use PDO;

class Expenses extends Model
{
    public function __construct()
    {
        $this->setTable('expenses');
    }

    public function getOne(int $id, int $customerId = null, bool $deleted = true): bool|array |string
    {
        try {
            $withDeleted = "";
            if ($deleted) {
                $withDeleted = " AND o.deleted = :deleted ";
            }
            
            $whereCustomer = "";
            if (!empty($customerId)) {
                $whereCustomer = " AND o.customer_id = :customer_id ";
            }

            $query = "SELECT o.id, o.title, o.description,
                            o.customer_id, o.user_id, o.provider_id, 
                            o.amount, o.payment_type_id, o.status, 
                            o.created_at, o.updated_at,
                            o.expense_date, o.expense_type, o.deleted,
                            c.name as customerName, c.id as customerCod,
                            c.email as customerEmail, 
                            u.name as userName, u.id as userCod,
                            u.email as userEmail, u.job_role as userRole,
                            pr.name as providerName, pr.id as providerCod,
                            pr.email as providerEmail, 
                            p.name as paymentTypeName, p.id as paymentTypeCod
                        FROM {$this->getTable()} AS o
                        LEFT JOIN customers AS c
                            ON o.customer_id = c.id
                        LEFT JOIN user AS u
                            ON o.user_id = u.id
                        LEFT JOIN providers AS pr
                            ON o.provider_id = pr.id
                        LEFT JOIN payment_types AS p
                            ON o.payment_type_id = p.id
                        WHERE o.id = :id 
                            $withDeleted 
                            $whereCustomer";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":id", $id);

            if ($deleted) {
                $stmt->bindValue(":deleted", "0");
            }

            if (!empty($customerId)) {
                $stmt->bindValue(":customer_id", $customerId);
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

    public function getAll(): bool|array |string
    {
        try {
            $query = "SELECT o.id, o.title, o.description,
                            o.customer_id, o.user_id, o.provider_id, 
                            o.amount, o.payment_type_id, o.status, 
                            o.created_at, o.updated_at,
                            o.expense_date, o.expense_type,
                            c.name as customerName,
                            u.name as userName,
                            pr.name as providerName,
                            p.name as paymentTypeName
                        FROM {$this->getTable()} AS o
                        LEFT JOIN customers AS c
                            ON o.customer_id = c.id
                        LEFT JOIN user AS u
                            ON o.user_id = u.id
                        LEFT JOIN providers AS pr
                            ON o.provider_id = pr.id
                        LEFT JOIN payment_types AS p
                            ON o.payment_type_id = p.id
                        WHERE o.deleted = :deleted";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":deleted", "0");
            $stmt->execute();

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();

            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getAllByMonth(string $status, string $month, int $customerId = null): bool|array |string
    {
        try {
            $infoDate = explode("-", $month, 2);

            $whereStatus = "";
            if ($status != '0') {
                $whereStatus = ' o.status = :status AND ';
            }

            $whereCustomer = "";
            if (!empty($customerId)) {
                $whereCustomer = " AND o.customer_id = :customer_id ";
            }

            $query = "SELECT o.id, o.title, o.description,
                            o.customer_id, o.user_id, o.provider_id, 
                            o.amount, o.payment_type_id, o.status, 
                            o.created_at, o.updated_at,
                            o.expense_date, o.expense_type,
                            c.name as customerName,
                            u.name as userName,
                            pr.name as providerName,
                            p.name as paymentTypeName
                        FROM {$this->getTable()} AS o
                        LEFT JOIN customers AS c
                            ON o.customer_id = c.id
                        LEFT JOIN user AS u
                            ON o.user_id = u.id
                        LEFT JOIN providers AS pr
                            ON o.provider_id = pr.id
                        LEFT JOIN payment_types AS p
                            ON o.payment_type_id = p.id
                        WHERE $whereStatus o.deleted = :deleted
                            AND MONTH(o.expense_date) = :d1  
                            AND YEAR(o.expense_date) = :d2
                            $whereCustomer";

            $stmt = $this->openDb()->prepare($query);
            if ($status != '0') {
                $stmt->bindValue(":status", $status);
            }
            $stmt->bindValue(":deleted", "0");
            $stmt->bindValue(":d1", $infoDate[1]);
            $stmt->bindValue(":d2", $infoDate[0]);

            if (!empty($customerId)) {
                $stmt->bindValue(":customer_id", $customerId);
            }

            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();

            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getTotalByStatus(string $status): float|string
    {
        try {
            $query = "SELECT SUM(amount) as total
                        FROM {$this->getTable()} 
                        WHERE status = :status
                        AND deleted = :deleted";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":status", $status);
            $stmt->bindValue(":deleted", '0');
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();

            if ($result) {
                return $result['total'] ? $result['total'] : 0;
            } else {
                return 0;
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getTotalByStatusByMonth(string $type, string $month): float|string
    {
        try {
            $infoDate = explode("/", $month, 2);
            $query = "SELECT SUM(amount) as total
                        FROM {$this->getTable()} 
                        WHERE expense_type = :expense_type 
                        AND status = :status
                        AND deleted = :deleted
                        AND MONTH(expense_date) = :d1  
                        AND YEAR(expense_date) = :d2";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":expense_type", $type);
            $stmt->bindValue(":status", '1');
            $stmt->bindValue(":deleted", '0');
            $stmt->bindValue(":d1", $infoDate[0]);
            $stmt->bindValue(":d2", $infoDate[1]);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();

            if ($result) {
                return $result['total'] ? $result['total'] : 0;
            } else {
                return 0;
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getTotalAmountByMonth(string $month): float|string
    {
        try {
            $infoDate = explode("/", $month, 2);

            $query = "SELECT SUM(amount) as total
                        FROM {$this->getTable()} 
                        WHERE status = :status
                        AND deleted = :deleted
                        AND MONTH(expense_date) = :d1  
                        AND YEAR(expense_date) = :d2";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":status", '2');
            $stmt->bindValue(":deleted", '0');
            $stmt->bindValue(":d1", $infoDate[0]);
            $stmt->bindValue(":d2", $infoDate[1]);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();

            if ($result) {
                return $result['total'] ? $result['total'] : 0;
            } else {
                return 0;
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function saveExpense(array $params): bool
    {
        $crud = new Crud();
        $crud->setTable('expenses');
        
        $newExpense = $crud->create($params);
        if ($newExpense) {
            return $newExpense;
        } else {
            return false;
        }
        
    }

    public function getTotalExpensesByMonthAndCustomer(string $type, string $status, string $month, int $customerId): float|string
    {
        try {
            $infoDate = explode("/", $month, 2);
            $query = "SELECT SUM(amount) as total
                        FROM {$this->getTable()} 
                        WHERE expense_type = :expense_type 
                        AND status = :status
                        AND customer_id = :customer_id
                        AND deleted = :deleted
                        AND MONTH(expense_date) = :d1  
                        AND YEAR(expense_date) = :d2";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":expense_type", $type);
            $stmt->bindValue(":status", $status);
            $stmt->bindValue(":customer_id", $customerId);
            $stmt->bindValue(":deleted", '0');
            $stmt->bindValue(":d1", $infoDate[0]);
            $stmt->bindValue(":d2", $infoDate[1]);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();

            if ($result) {
                return $result['total'] ? $result['total'] : 0;
            } else {
                return 0;
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getTotalExpensesByCustomer(string $type, string $status, int $customerId): float|string
    {
        try {
            $query = "SELECT SUM(amount) as total
                        FROM {$this->getTable()} 
                        WHERE expense_type = :expense_type 
                        AND status = :status
                        AND customer_id = :customer_id
                        AND deleted = :deleted";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":expense_type", $type);
            $stmt->bindValue(":status", $status);
            $stmt->bindValue(":customer_id", $customerId);
            $stmt->bindValue(":deleted", '0');
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();

            if ($result) {
                return $result['total'] ? $result['total'] : 0;
            } else {
                return 0;
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function totalPendingExpenses(string $status, string $month): float|string
    {
        try {
            $infoDate = explode("/", $month, 2);
            $query = "SELECT COUNT(id) as total
                        FROM {$this->getTable()} 
                        WHERE status = :status
                        AND deleted = :deleted
                        AND MONTH(expense_date) = :d1  
                        AND YEAR(expense_date) = :d2";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":status", $status);
            $stmt->bindValue(":deleted", '0');
            $stmt->bindValue(":d1", $infoDate[0]);
            $stmt->bindValue(":d2", $infoDate[1]);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();

            if ($result) {
                return $result['total'] ? $result['total'] : 0;
            } else {
                return 0;
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getExpensesDelayedByCustomer(string $type, int $status, string $field, string $table, int $customerId): array|string
    {
        try {
            $query = "SELECT id
                        FROM $table 
                        WHERE expense_type = :expense_type
                        AND status = :status
                        AND customer_id = :customer_id
                        AND deleted = :deleted
                        AND $field < :dt
                        ";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":expense_type", $type);
            $stmt->bindValue(":status", $status);
            $stmt->bindValue(":customer_id", $customerId);
            $stmt->bindValue(":deleted", '0');
            $stmt->bindValue(":dt", date('Y-m-d'));
            $stmt->execute();

            $result = $stmt->rowCount();

            $stmt = null;
            $this->closeDb();

            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function osInExpense(int $osId): bool
    {
        if (!empty($osId)) {
            $query = "SELECT os_id
                    FROM {$this->getTable()}
                    WHERE os_id = :os_id 
                        AND deleted = :deleted";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":os_id", $osId);
            $stmt->bindValue(":deleted", '0');

            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();

            if ($result) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}