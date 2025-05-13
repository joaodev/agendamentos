<?php

namespace Core\Db;

use Exception;
use PDO;

class Model extends InitDb
{
    public function getTable(): string
    {
        try {
            return $this->table;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function setTable(string $table): void
    {
        try {
            $this->table = $table;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    public function find(string $id, string $stringFields, string $idField, string $view = null)
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
                WHERE $idField = :id
            ";

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


    public function findActive(string $id, string $stringFields, string $idField, string $view = null)
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
                WHERE $idField = :id
                AND deleted = :deleted
            ";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":id", $id);
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

    public function findAll(string $stringFields): bool|array |string
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

    public function findOneBy(string $stringFields, string $whereField, string $whereValue): bool|array |string
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
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();

            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function findAllBy(string $stringFields, string $whereField, string $whereValue): bool|array |string
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

    public function findAllActives(string $stringFields): bool|array |string
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

    public function findAllActivesBy(string $stringFields, string $whereField, string $whereValue): bool|array |string
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

    public function fieldExists(string $field, string $value, string $idField, string $id = null): bool|string|null
    {
        try {
            if (!empty($value)) {
                if (!empty($id)) {
                    $where = " AND $idField != '$id' ";
                } else {
                    $where = "";
                }

                $query = "
                    SELECT $idField FROM {$this->getTable()}
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
            } else {
                return null;
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getIdByField(string $field, string $value, string $idField)
    {
        try {
            $query = "
                SELECT $idField FROM {$this->getTable()}
                WHERE $field = :value";
            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":value", $value);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $data = $stmt->fetch(PDO::FETCH_ASSOC);
                $result = $data[$idField];
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
    private static function IdGenerator(int $length = 10): string
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
        return self::IdGenerator(8) . '-'
            . self::IdGenerator(4) . '-'
            . self::IdGenerator(4) . '-'
            . self::IdGenerator(4) . '-'
            . self::IdGenerator(12);
    }

    public function totalData(string $table): int|string
    {
        try {
            $query = "
                SELECT id
                FROM $table 
                WHERE deleted = :deleted
            ";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":deleted", '0');
            $stmt->execute();

            $result = $stmt->rowCount();

            $stmt = null;
            $this->closeDb();

            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getDataForToday(int $status, string $field, string $table, string $customer_id = null): array|string
    {
        try {
            $whereCustomer = "";
            if (!empty($customer_id)) {
                $whereCustomer = " AND customer_id = :customer_id ";
            }

            $d1 = date('Y');
            $d2 = date('m');
            $d3 = date('d');

            $query = "SELECT id
                        FROM $table 
                        WHERE status = :status
                        AND deleted = :deleted
                        AND YEAR($field) = :d1 
                        AND MONTH($field) = :d2
                        AND DAY($field) = :d3
                        $whereCustomer";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":status", $status);
            $stmt->bindValue(":deleted", '0');
            $stmt->bindValue(":d1", $d1);
            $stmt->bindValue(":d2", $d2);
            $stmt->bindValue(":d3", $d3);

            if (!empty($customer_id)) {
                $stmt->bindValue(":customer_id", $customer_id);
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

    public function getDataDelayed(int $status, string $field, string $table): array|string
    {
        try {
            $query = "SELECT id
                        FROM $table 
                        WHERE status = :status
                        AND deleted = :deleted
                        AND $field < :dt
                        ";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":status", $status);
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

    public function getDataDelayedByCustomer(int $status, string $field, string $table, int $customerId): array|string
    {
        try {
            $query = "SELECT id
                        FROM $table 
                        WHERE status = :status
                        AND deleted = :deleted
                        AND customer_id = :customer_id
                        AND $field < :dt
                        ";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":status", $status);
            $stmt->bindValue(":deleted", '0');
            $stmt->bindValue(":customer_id", $customerId);
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

    public function getDataForReport($params, $customer_id)
    {
        if (!empty($params)) {
        
            $join = "";
            $fields = "*";
            $whereDates = " AND tb.created_at BETWEEN '{$params['initial_date']} 00:00:00' 
                            AND '{$params['final_date']} 23:59:59' ";

            if ($params['order_type'] == '1') {
                $orderBy = " ORDER BY tb.created_at ASC ";
            } elseif ($params['order_type'] == '2') {
                $orderBy = " ORDER BY tb.created_at DESC ";
            } else {
                $orderBy = "";
            }

            switch ($_POST['sis_module']) {
                case 1:
                    $whereDates = " AND tb.sale_date BETWEEN '{$params['initial_date']} 00:00:00' 
                                    AND '{$params['final_date']} 23:59:59' ";

                    $fields = "tb.id, tb.description, tb.status,
                                tb.customer_id, tb.sale_date, tb.sale_time,
                                tb.amount, tb.payment_type_id, tb.discount,
                                tb.created_at, tb.updated_at, tb.user_id, 
                                c.name as customerName, c.id as customerCod,
                                c.email as customerEmail, 
                                p.name as paymentTypeName, p.id as paymentTypeCod,
                                u.id as userCod, u.name as userName,
                                u.email as userEmail, u.job_role as userRole";
                    
                    $join .= " LEFT JOIN user AS u ON tb.user_id = u.id ";
                    $join .= " LEFT JOIN customers AS c ON tb.customer_id = c.id ";
                    $join .= " LEFT JOIN payment_types AS p ON tb.payment_type_id = p.id ";
                    break;
                case 2:
                    $whereDates = " AND tb.expense_date BETWEEN '{$params['initial_date']} 00:00:00' 
                                    AND '{$params['final_date']} 23:59:59' ";

                    $fields = "tb.id, tb.title, tb.description,
                                tb.customer_id, tb.user_id, tb.provider_id, 
                                tb.amount, tb.payment_type_id, tb.status, 
                                tb.created_at, tb.updated_at,
                                tb.expense_date, tb.expense_type,
                                c.name as customerName, c.id as customerCod,
                                c.email as customerEmail, 
                                u.name as userName, u.id as userCod,
                                u.email as userEmail, u.job_role as userRole,
                                pr.name as providerName, pr.id as providerCod,
                                pr.email as providerEmail, 
                                p.name as paymentTypeName, p.id as paymentTypeCod";
                    
                    $join .= " LEFT JOIN user AS u ON tb.user_id = u.id ";
                    $join .= " LEFT JOIN customers AS c ON tb.customer_id = c.id ";
                    $join .= " LEFT JOIN providers AS pr ON tb.provider_id = pr.id ";
                    $join .= " LEFT JOIN payment_types AS p ON tb.payment_type_id = p.id ";
                    break; 
                case 3:
                    $whereDates = " AND tb.budget_date BETWEEN '{$params['initial_date']} 00:00:00' 
                                    AND '{$params['final_date']} 23:59:59' ";

                    $fields = "tb.id, tb.description, 
                                tb.customer_id, tb.budget_date, tb.budget_time,
                                tb.budget_total, tb.status, tb.created_at, tb.updated_at,
                                c.name as customerName, c.email as customerEmail,
                                c.id as customerCod";

                    $join .= " LEFT JOIN customers AS c ON tb.customer_id = c.id ";
                    break; 
                case 4:
                    $whereDates = " AND tb.os_date BETWEEN '{$params['initial_date']} 00:00:00' 
                                    AND '{$params['final_date']} 23:59:59' ";

                    $fields = "tb.id, tb.description, tb.customer_id, 
                                    tb.os_date, tb.os_time, tb.os_total,
                                    tb.status, tb.created_at, tb.updated_at,
                                    c.name as customerName, c.email as customerEmail,
                                    c.id as customerCod";

                    $join .= " LEFT JOIN customers AS c ON tb.customer_id = c.id ";
                    break; 
                case 5:
                        $whereDates = " AND tb.schedule_date BETWEEN '{$params['initial_date']} 00:00:00' 
                                        AND '{$params['final_date']} 23:59:59' ";
    
                        $fields = "tb.id, tb.title, tb.description, 
                                    tb.schedule_date, tb.schedule_time,
                                    tb.status, tb.created_at, tb.updated_at,
                                    u.name as userName, u.id as userCod, tb.customer_id, 
                                    u.email as userEmail, u.job_role as userRole,
                                    c.name as customerName, c.id as customerCod,
                                    c.email as customerEmail";
    
                        $join .= " LEFT JOIN customers AS c ON tb.customer_id = c.id ";
                        $join .= " LEFT JOIN user AS u ON tb.user_id = u.id ";
                        break; 
                default:
                    break;
            }

            if ($params['report_limit'] > 1000) {
                $params['report_limit'] = 1000;
            }
            
            $query = "SELECT $fields
                        FROM {$this->getTable()} AS tb
                        $join
                        WHERE tb.deleted = :deleted
                        AND tb.customer_id = :parent_id
                        $whereDates
                        $orderBy
                        LIMIT {$params['report_limit']}
                        ";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":deleted", '0');
            $stmt->bindValue(":parent_id", $customer_id);
            $stmt->execute();

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $stmt = null;
            $this->closeDb();

            return $result;
        } else {
            return [];
        }
    }

    public function getAdminDataForReport($params)
    {
        if (!empty($params)) {
        
            $join = "";
            $fields = "*";
            $whereDates = " AND tb.created_at BETWEEN '{$params['initial_date']} 00:00:00' 
                            AND '{$params['final_date']} 23:59:59' ";
            
            if ($params['order_type'] == '1' && $_POST['sis_module'] != 13) {
                $orderBy = " ORDER BY tb.created_at ASC ";
            } elseif ($params['order_type'] == '2' && $_POST['sis_module'] != 13) {
                $orderBy = " ORDER BY tb.created_at DESC ";
            } elseif ($params['order_type'] == '1' && $_POST['sis_module'] == 13) {
                $orderBy = " ORDER BY tb.log_date ASC ";
            } elseif ($params['order_type'] == '2' && $_POST['sis_module'] == 13) {
                $orderBy = " ORDER BY tb.log_date DESC ";
            } else {
                $orderBy = "";
            }
          
            switch ($_POST['sis_module']) {
                //vendas
                case 1:
                    $whereDates = " AND tb.sale_date BETWEEN '{$params['initial_date']} 00:00:00' 
                                    AND '{$params['final_date']} 23:59:59' ";

                    $fields = "tb.id, tb.description, tb.status,
                                tb.customer_id, tb.sale_date, tb.sale_time,
                                tb.amount, tb.payment_type_id, tb.discount,
                                tb.created_at, tb.updated_at, tb.user_id, 
                                c.name as customerName, c.id as customerCod,
                                c.email as customerEmail, 
                                p.name as paymentTypeName, p.id as paymentTypeCod,
                                u.id as userCod, u.name as userName,
                                u.email as userEmail, u.job_role as userRole";
                    
                    $join .= " LEFT JOIN user AS u ON tb.user_id = u.id ";
                    $join .= " LEFT JOIN customers AS c ON tb.customer_id = c.id ";
                    $join .= " LEFT JOIN payment_types AS p ON tb.payment_type_id = p.id ";
                    break;
                //contas
                case 2:
                    $whereDates = " AND tb.expense_date BETWEEN '{$params['initial_date']} 00:00:00' 
                                    AND '{$params['final_date']} 23:59:59' ";

                    $fields = "tb.id, tb.title, tb.description,
                                tb.customer_id, tb.user_id, tb.provider_id, 
                                tb.amount, tb.payment_type_id, tb.status, 
                                tb.created_at, tb.updated_at,
                                tb.expense_date, tb.expense_type,
                                c.name as customerName, c.id as customerCod,
                                c.email as customerEmail, 
                                u.name as userName, u.id as userCod,
                                u.email as userEmail, u.job_role as userRole,
                                pr.name as providerName, pr.id as providerCod,
                                pr.email as providerEmail, 
                                p.name as paymentTypeName, p.id as paymentTypeCod";
                    
                    $join .= " LEFT JOIN user AS u ON tb.user_id = u.id ";
                    $join .= " LEFT JOIN customers AS c ON tb.customer_id = c.id ";
                    $join .= " LEFT JOIN providers AS pr ON tb.provider_id = pr.id ";
                    $join .= " LEFT JOIN payment_types AS p ON tb.payment_type_id = p.id ";
                    break; 
                //orçamentos
                case 3:
                    $whereDates = " AND tb.budget_date BETWEEN '{$params['initial_date']} 00:00:00' 
                                    AND '{$params['final_date']} 23:59:59' ";

                    $fields = "tb.id, tb.description, 
                                tb.customer_id, tb.budget_date, tb.budget_time,
                                tb.budget_total, tb.status, tb.created_at, tb.updated_at,
                                c.name as customerName, c.email as customerEmail,
                                c.id as customerCod";
                    
                    $join .= " LEFT JOIN customers AS c ON tb.customer_id = c.id ";
                    break; 
                //os
                case 4:
                    $whereDates = " AND tb.os_date BETWEEN '{$params['initial_date']} 00:00:00' 
                                    AND '{$params['final_date']} 23:59:59' ";

                    $fields = "tb.id, tb.description, 
                                    tb.customer_id, tb.os_date, tb.os_time, tb.os_total,
                                    tb.status, tb.created_at, tb.updated_at,
                                    c.name as customerName, c.email as customerEmail,
                                    c.id as customerCod";
                    
                    $join .= " LEFT JOIN customers AS c ON tb.customer_id = c.id ";
                    break; 
                //agendamentos
                case 5:
                    $whereDates = " AND tb.schedule_date BETWEEN '{$params['initial_date']} 00:00:00' 
                                    AND '{$params['final_date']} 23:59:59' ";
                    
                    $fields = "tb.id, tb.title, tb.description, 
                                tb.schedule_date, tb.schedule_time,
                                tb.status, tb.created_at, tb.updated_at,
                                tb.user_id, u.name as userName, 
                                u.id as userCod, u.email as userEmail,
                                u.job_role as userRole, tb.customer_id, 
                                c.name as customerName, c.id as customerCod,
                                c.email as customerEmail";

                    $join .= " LEFT JOIN user AS u ON tb.user_id = u.id ";
                    $join .= " LEFT JOIN customers AS c ON tb.customer_id = c.id ";
                    break;
                //controle de estoque
                case 6:
                    $fields = "tb.id, tb.item_id, 
                                tb.description, tb.control_type, 
                                tb.quantity, tb.new_quantity, 
                                tb.created_at, tb.updated_at,
                                i.name as itemName, i.id as itemCod";
                    
                    $join .= " INNER JOIN items AS i ON tb.item_id = i.id ";
                    break;
                //folha de ponto
                case 7:
                    $whereDates = " AND tb.work_date BETWEEN '{$params['initial_date']} 00:00:00' 
                                    AND '{$params['final_date']} 23:59:59' ";
                    
                    $fields = "tb.id, tb.user_id, tb.work_date,
                                tb.start_time, tb.lunch_start_time, tb.lunch_end_time, 
                                tb.end_time, tb.description, tb.created_at, tb.updated_at,
                                u.name as userName, u.id as userCod";

                    $join .= " LEFT JOIN user AS u ON tb.user_id = u.id ";
                    break;
                //prospecções
                case 8:
                    $fields = "tb.id, tb.user_id, tb.name, tb.email, 
                                tb.phone, tb.cellphone, tb.city, tb.state, tb.description,
                                tb.status, tb.created_at, tb.updated_at,
                                u.id as userCod, u.name as userName,
                                u.email as userEmail, u.job_role as userRole";

                    $join .= " LEFT JOIN user AS u ON tb.user_id = u.id ";
                    break;
                //tarefas
                case 9:
                    $whereDates = " AND tb.task_date BETWEEN '{$params['initial_date']} 00:00:00' 
                                    AND '{$params['final_date']} 23:59:59' ";
                    
                    $fields = "tb.id, tb.title, tb.description, tb.task_date, tb.task_time,
                                tb.status, tb.created_at, tb.updated_at";
                    break;
                //compras
                case 10:
                    $whereDates = " AND tb.purchase_date BETWEEN '{$params['initial_date']} 00:00:00' 
                                    AND '{$params['final_date']} 23:59:59' ";
                    
                    $fields = "tb.id, tb.user_id, tb.title, tb.description, 
                                tb.purchase_date, tb.purchase_time,
                                tb.amount, tb.payment_type_id, tb.status,
                                tb.created_at, tb.updated_at, 
                                p.name as paymentTypeName, p.id as paymentTypeCod,
                                u.id as userCod, u.name as userName,
                                u.email as userEmail, u.job_role as userRole";
                    
                    $join .= " LEFT JOIN user AS u ON tb.user_id = u.id ";
                    $join .= " LEFT JOIN payment_types AS p ON tb.payment_type_id = p.id ";
                    break;
                //fluxo de caixa
                case 11:
                    $fields = "tb.id, tb.purchase_id, sale_id, expense_id, 
                                tb.title, tb.mov_type,
                                tb.description, tb.amount, tb.status,
                                tb.created_at, tb.updated_at";
                    break;
                //usuários
                case 12:
                    $fields = "tb.id, tb.name, tb.email, tb.status, tb.role_id,
                                tb.cellphone, tb.job_role, r.name as role, tb.phone, tb.whatsapp,
                                r.is_admin, tb.created_at, tb.updated_at, tb.file,
                                tb.postal_code, tb.address, tb.number, tb.complement, 
                                tb.neighborhood, tb.city, tb.state, tb.document_1, tb.document_2,
                                tb.salary, tb.start_date, tb.end_date, 
                                tb.birthdate, tb.gender, tb.auth2factor";
                    
                    $join .= " INNER JOIN role AS r ON tb.role_id = r.id ";
                    break;
                //logs
                case 13:
                    $whereDates = " AND tb.log_date BETWEEN '{$params['initial_date']} 00:00:00' 
                                    AND '{$params['final_date']} 23:59:59' ";

                    $fields = "tb.id, tb.log_user_id, tb.log_action, tb.log_date, 
                                tb.log_ip, tb.log_user_agent, tb.log_status, 
                                u.name as username";

                    $join .= " INNER JOIN user AS u ON tb.log_user_id = u.id ";
                    break;
                default:
                    break;
            }

            if ($params['report_limit'] > 1000) {
                $params['report_limit'] = 1000;
            }

            $query = "SELECT $fields
                        FROM {$this->getTable()} AS tb
                        $join
                        WHERE tb.deleted = :deleted
                        $whereDates
                        $orderBy
                        LIMIT {$params['report_limit']}
                        ";

            $stmt = $this->openDb()->prepare($query);

            if (!empty($params['view_type']) && $params['view_type'] == '2') {
                $stmt->bindValue(":deleted", '1');
            } else {
                $stmt->bindValue(":deleted", '0');
            }

            $stmt->execute();

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $stmt = null;
            $this->closeDb();

            return $result;
        } else {
            return [];
        }
    }

    public function verifyNotification(string $parent, string $module, int $id, int $userId): bool|array|string
    {
        try {
            $today = date('Y-m-d');

            $query = "SELECT id
                        FROM system_notifications
                        WHERE parent = :parent
                            AND user_id = :user_id
                            AND $module = :module
                            AND created_at BETWEEN '$today 00:00:00' 
                                AND '$today 23:59:59'";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":parent", $parent);
            $stmt->bindValue(":user_id", $userId);
            $stmt->bindValue(":module", $id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
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

    public function getTotalUnreadsByUser(int $userId): bool|array|string
    {
        try {
            $query = "SELECT COUNT(id) as total
                        FROM {$this->getTable()}
                        WHERE user_id = :user_id
                            AND deleted = :deleted
                            AND has_read = :has_read";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":user_id", $userId);
            $stmt->bindValue(":deleted", '0');
            $stmt->bindValue(":has_read",'0');
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();
            
            return $result['total'];
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getTotalUnreadsByCustomer(int $customerId): bool|array|string
    {
        try {
            $query = "SELECT COUNT(id) as total
                        FROM {$this->getTable()}
                        WHERE customer_id = :customer_id
                            AND deleted = :deleted
                            AND has_read = :has_read";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":customer_id", $customerId);
            $stmt->bindValue(":deleted", '0');
            $stmt->bindValue(":has_read",'0');
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();
            
            return $result['total'];
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}