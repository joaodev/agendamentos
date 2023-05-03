<?php

namespace Core\Db;

use Exception;
use PDO;

class Model extends InitDb
{
    public function getTable()
    {
        try {
            return $this->table;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function setTable($table): void
    {
        try {
            $this->table = $table;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    public function find($uuid, $stringFields, $uuidField, $view = null)
    {
        try {
            if (!empty($view)) {
                $table = $view;
            } else {
                $table = $this->getTable();
            }

            $query = "
                SELECT $stringFields
                FROM $table
                WHERE $uuidField = :uuid
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


    public function findActive($uuid, $stringFields, $uuidField, $view = null)
    {
        try {
            if (!empty($view)) {
                $table = $view;
            } else {
                $table = $this->getTable();
            }

            $query = "
                SELECT $stringFields
                FROM $table
                WHERE $uuidField = :uuid
                AND deleted = :deleted
            ";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":uuid", $uuid);
            $stmt->bindValue(":deleted", '0');
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();

            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function findAll($stringFields): bool|array|string
    {
        try {
            $query = "
                SELECT $stringFields
                FROM {$this->getTable()}
                WHERE deleted = :deleted
            ";

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

    public function findAllBy($stringFields, $whereField, $whereValue): bool|array|string
    {
        try {
            $query = "
                SELECT $stringFields
                FROM {$this->getTable()}
                WHERE
                    $whereField = :whereValue
                    AND deleted = :deleted
            ";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":whereValue", $whereValue);
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

    public function findAllActives($stringFields): bool|array|string
    {
        try {
            $query = "
                SELECT $stringFields
                FROM {$this->getTable()}
                WHERE deleted = :deleted
                AND status = :status
            ";

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

    public function findAllActivesBy($stringFields, $whereField, $whereValue): bool|array|string
    {
        try {
            $query = "
                SELECT $stringFields
                FROM {$this->getTable()}
                WHERE 
                    $whereField = :whereValue
                    AND deleted = :deleted
                    AND status = :status
            ";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":whereValue", $whereValue);
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

    public function fieldExists($field, $value, $uuidField, $uuid = null): bool|string
    {
        try {
            if (!empty($uuid)) {
                $where = " AND $uuidField != '$uuid' ";
            } else { $where = ""; }

            $query = "
                SELECT $uuidField FROM {$this->getTable()}
                WHERE $field = :value $where";
            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":value", $value);
            $stmt->execute();

            if ($stmt->rowCount() >= 1) {
                $result = true;
            } else {
                $result = false;
            }

            $stmt = null;
            $this->closeDb();

            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getUuidByField($field, $value, $uuidField) {
        try {
            $query = "
                SELECT $uuidField FROM {$this->getTable()}
                WHERE $field = :value";
            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":value", $value);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $data = $stmt->fetch(PDO::FETCH_ASSOC);
                $result = $data[$uuidField];
            } else {
                $result = 0;
            }

            $stmt = null;
            $this->closeDb();

            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * @throws Exception
     */
    private static function UuidGenerator($length = 10): string
    {
        if (function_exists("random_bytes")) {
            $bytes = random_bytes(ceil($length / 2));
        } elseif (function_exists("openssl_random_pseudo_bytes")) {
            $bytes = openssl_random_pseudo_bytes(ceil($length / 2));
        } else {
            throw new Exception("no cryptographically secure random function available");
        }

        return substr(bin2hex($bytes), 0, $length);
    }

    /**
     * @throws Exception
     */
    public function NewUUID(): string
    {
        return self::UuidGenerator(8) . '-'
            . self::UuidGenerator(4) . '-'
            . self::UuidGenerator(4) . '-'
            . self::UuidGenerator(4) . '-'
            . self::UuidGenerator(12);
    }

    public function totalData($table, $parentUUID): int|string
    {
        try {
            $query = "
                SELECT uuid
                FROM $table 
                WHERE deleted = :deleted
                AND parent_uuid = :parent_uuid
            ";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":deleted", '0');
            $stmt->bindValue(":parent_uuid", $parentUUID);
            $stmt->execute();
            
            $result = $stmt->rowCount();

            $stmt = null;
            $this->closeDb();

            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function totalMonthlyData($month, $table, $column, $parent): int|string
    {
        try {
            $m = explode("-", $month);
            $d1 = $m[0];
            $d2 = $m[1];
            
            $query = "
                SELECT uuid
                FROM $table 
                WHERE deleted = :deleted
                AND parent_uuid = :parent_uuid
                AND YEAR($column) = :d1 AND MONTH($column) = :d2
            ";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":deleted", '0');
            $stmt->bindValue(":parent_uuid", $parent);
            $stmt->bindValue(":d1", $d1);
            $stmt->bindValue(":d2", $d2);
            $stmt->execute();
            
            $result = $stmt->rowCount();

            $stmt = null;
            $this->closeDb();

            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getTotalForToday($status, $parentUUID, $field, $table)
    {
        try {
            $d1 = date('Y');
            $d2 = date('m');
            $d3 = date('d');

            $query = "SELECT uuid
                        FROM $table 
                        WHERE status = :status
                        AND deleted = :deleted
                        AND parent_uuid = :parent_uuid
                        AND YEAR($field) = :d1 
                        AND MONTH($field) = :d2
                        AND DAY($field) = :d3
                        ";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":status", $status);
            $stmt->bindValue(":deleted", '0');
            $stmt->bindValue(":parent_uuid", $parentUUID);
            $stmt->bindValue(":d1", $d1);
            $stmt->bindValue(":d2", $d2);
            $stmt->bindValue(":d3", $d3);
            $stmt->execute();

            $result = $stmt->rowCount();

            $stmt = null;
            $this->closeDb();
            
            if ($result) {
                return $result;
            } else {
                return 0;
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getTotalDelayed($status, $parentUUID, $field, $table)
    {
        try {
            $query = "SELECT uuid
                        FROM $table 
                        WHERE status = :status
                        AND deleted = :deleted
                        AND parent_uuid = :parent_uuid
                        AND $field < :dt
                        ";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":status", $status);
            $stmt->bindValue(":deleted", '0');
            $stmt->bindValue(":parent_uuid", $parentUUID);
            $stmt->bindValue(":dt", date('Y-m-d'));
            $stmt->execute();

            $result = $stmt->rowCount();

            $stmt = null;
            $this->closeDb();
            
            if ($result) {
                return $result;
            } else {
                return 0;
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getDataForReport($params, $parentUUID)
    {
        if (!empty($params)) {

            $join = "";
            $fields = "*";
            $whereDates = " AND tb.created_at BETWEEN '{$params['initial_date']} 00:00:00' 
                            AND '{$params['final_date']} 23:59:59' ";

            switch ($_POST['sis_module']) {
                case 1:
                    $whereDates = " AND tb.schedule_date BETWEEN '{$params['initial_date']} 00:00:00' 
                                    AND '{$params['final_date']} 23:59:59' ";

                    $fields = "tb.uuid, tb.schedule_date, tb.schedule_time, tb.amount,
                                tb.description, 
                                tb.status, tb.created_at, tb.updated_at,
                                sv.title as serviceName, 
                                ct.name as customerName,
                                pt.name as paymentTypeName";
                    
                    $join = " INNER JOIN services AS sv ON tb.service_uuid = sv.uuid ";
                    $join .= " LEFT JOIN customers AS ct ON tb.customer_uuid = ct.uuid ";
                    $join .= " LEFT JOIN payment_types AS pt ON tb.payment_type_uuid = pt.uuid ";
                    break;
                case 2:
                    $fields = "tb.uuid, tb.name, tb.email, tb.phone, tb.cellphone, 
                                tb.document_1, tb.document_2,
                                tb.postal_code, tb.address, tb.number, tb.complement, 
                                tb.neighborhood, tb.city, tb.state,
                                tb.status, tb.created_at, tb.updated_at";
                    break;
                case 3:
                    $whereDates = " AND tb.expense_date BETWEEN '{$params['initial_date']} 00:00:00' 
                                    AND '{$params['final_date']} 23:59:59' ";
                    
                    $fields = "tb.uuid, tb.expense_date,
                                tb.title, tb.description, tb.amount, 
                                tb.status, tb.created_at, tb.updated_at, 
                                pt.name as paymentTypeName,
                                ct.name as customerName";
                    
                    $join .= " INNER JOIN payment_types AS pt ON tb.payment_type_uuid = pt.uuid ";
                    $join .= " LEFT JOIN customers AS ct ON tb.customer_uuid = ct.uuid ";
                    break;
                case 4:
                    $whereDates = " AND tb.revenue_date BETWEEN '{$params['initial_date']} 00:00:00' 
                                    AND '{$params['final_date']} 23:59:59' ";

                    $fields = "tb.uuid, tb.revenue_date,
                                tb.title, tb.description, tb.amount, 
                                tb.status, tb.created_at, tb.updated_at, 
                                pt.name as paymentTypeName,
                                ct.name as customerName";
                    
                    $join .= " INNER JOIN payment_types AS pt ON tb.payment_type_uuid = pt.uuid ";
                    $join .= " LEFT JOIN customers AS ct ON tb.customer_uuid = ct.uuid ";
                    break;
                case 5:
                    $fields = "tb.uuid, tb.title, tb.description, tb.price, 
                                tb.status, tb.created_at, tb.updated_at";
                    break;
                case 6:
                    $whereDates = " AND tb.task_date BETWEEN '{$params['initial_date']} 00:00:00' 
                                    AND '{$params['final_date']} 23:59:59' ";
                                
                    $fields = "tb.uuid, tb.title, tb.description, tb.task_date, tb.task_time,
                                tb.status, tb.created_at, tb.updated_at";
                    break;
                default:
                    break;
            }

            $query = "SELECT $fields
                        FROM {$this->getTable()} AS tb
                        $join
                        WHERE tb.deleted = :deleted
                        AND tb.parent_uuid = :parent_uuid
                        $whereDates
                        LIMIT {$params['report_limit']}
                        ";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":deleted", '0');
            $stmt->bindValue(":parent_uuid", $parentUUID);
            $stmt->execute();

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $stmt = null;
            $this->closeDb();

            return $result;
        } else {
            return [];
        }
    }
}
